#!/usr/bin/env python3
"""
Generate a UML Class Diagram for a Laravel project.

Run from the Laravel project root:
    python generate_class_diagram.py

Reads:
    app/Models/**/*.php
    database/migrations/**/*.php

Writes:
    hotel-management-class-diagram.drawio

No Laravel/PHP files are modified. Only Python standard library is used.
"""

from __future__ import annotations

import html
import re
import sys
import uuid
from pathlib import Path
from collections import OrderedDict, defaultdict

# ============================================================
# Configuration
# ============================================================

ROOT = Path(__file__).resolve().parent
MODELS_DIR = ROOT / "app" / "Models"
MIGRATIONS_DIR = ROOT / "database" / "migrations"
OUT = ROOT / "hotel-management-class-diagram.drawio"

DEBUG = "--debug" in sys.argv

# ============================================================
# Utilities
# ============================================================

def gid() -> str:
    return uuid.uuid4().hex[:12]


def esc(value: str) -> str:
    return html.escape(str(value), quote=True)


def clean_php(text: str) -> str:
    """
    Remove PHP comments while preserving strings reasonably well
    for the type of static parsing performed here.
    """
    text = re.sub(r"/\*.*?\*/", "", text, flags=re.S)
    text = re.sub(r"//.*", "", text)
    return text


def snake(name: str) -> str:
    name = re.sub(r"([a-z0-9])([A-Z])", r"\1_\2", name)
    return re.sub(r"[^a-zA-Z0-9_]", "_", name).lower()


def short_class_name(value: str) -> str:
    return value.split("\\")[-1]


def normalize_type(value: str) -> str:
    value = value.strip()
    value = value.replace("\\", "")
    value = re.sub(r"\s+", " ", value)
    return value


# ------------------------------------------------------------
# Proper English pluralization (mimics Laravel's Str::plural
# closely enough for typical table names). The previous
# "just add s" approach silently produced wrong table names
# for words like "category" -> "categorys" (should be
# "categories"), "status" -> "statuss" (should be "statuses")
# and uncountable words like "staff" -> "staffs" (should stay
# "staff"). Those mismatches are what broke relationship
# detection: a model's guessed table name has to match the
# migration's real table name, or no edge can ever be drawn.
# ------------------------------------------------------------

UNCOUNTABLE_WORDS = {
    "staff", "series", "species", "equipment", "information",
    "data", "news", "fish", "sheep", "deer", "moose", "aircraft",
    "software", "furniture",
}

IRREGULAR_WORDS = {
    "person": "people",
    "child": "children",
    "man": "men",
    "woman": "women",
    "tooth": "teeth",
    "foot": "feet",
    "mouse": "mice",
    "goose": "geese",
}


def pluralize(word: str) -> str:
    if not word:
        return word
    lower = word.lower()
    if lower in UNCOUNTABLE_WORDS:
        return word
    if lower in IRREGULAR_WORDS:
        return IRREGULAR_WORDS[lower]
    if re.search(r"(s|ss|sh|ch|x|z)$", lower):
        return word + "es"
    if re.search(r"[^aeiou]y$", lower):
        return word[:-1] + "ies"
    if lower.endswith("fe"):
        return word[:-2] + "ves"
    if re.search(r"[^f]f$", lower):
        return word[:-1] + "ves"
    return word + "s"


def pluralize_snake(base_snake: str) -> str:
    """Pluralize only the final segment of a snake_case name,
    e.g. 'room_category' -> 'room_categories'."""
    parts = base_snake.split("_")
    parts[-1] = pluralize(parts[-1])
    return "_".join(parts)


# ============================================================
# Model Parsing
# ============================================================

RELATION_METHODS = OrderedDict([
    ("belongsTo", "N:1"),
    ("hasOne", "1:1"),
    ("hasMany", "1:N"),
    ("belongsToMany", "N:N"),
    ("hasManyThrough", "1:N"),
    ("hasOneThrough", "1:1"),
    ("morphTo", "N:1"),
    ("morphOne", "1:1"),
    ("morphMany", "1:N"),
    ("morphToMany", "N:N"),
    ("morphedByMany", "N:N"),
])


def model_class(text: str, fallback: str) -> str:
    match = re.search(r"\bclass\s+([A-Za-z_][A-Za-z0-9_]*)", text)
    return match.group(1) if match else fallback


def model_parent(text: str) -> str | None:
    match = re.search(
        r"\bclass\s+[A-Za-z_][A-Za-z0-9_]*\s+extends\s+([A-Za-z_][A-Za-z0-9_\\]*)",
        text
    )
    if not match:
        return None
    return short_class_name(match.group(1))


def model_interfaces(text: str) -> list[str]:
    match = re.search(
        r"\bclass\s+[A-Za-z_][A-Za-z0-9_]*"
        r"(?:\s+extends\s+[A-Za-z_][A-Za-z0-9_\\]*)?"
        r"\s+implements\s+([^{]+)",
        text
    )
    if not match:
        return []
    return [short_class_name(x.strip()) for x in match.group(1).split(",") if x.strip()]


def model_traits(text: str) -> list[str]:
    traits = []
    for match in re.finditer(r"\buse\s+([^;{]+);", text):
        value = match.group(1).strip()
        if "::" in value:
            continue
        for item in value.split(","):
            item = item.strip()
            if item:
                traits.append(short_class_name(item))
    return list(dict.fromkeys(traits))


def table_from_model(class_name: str, text: str) -> str:
    match = re.search(r"protected\s+\$table\s*=\s*['\"]([^'\"]+)['\"]", text)
    if match:
        return match.group(1)
    irregular = {
        "User": "users",
        "Person": "people",
    }
    if class_name in irregular:
        return irregular[class_name]
    return pluralize_snake(snake(class_name))


def parse_fillable(text: str) -> list[str]:
    match = re.search(r"protected\s+\$fillable\s*=\s*\[(.*?)\]", text, flags=re.S)
    if not match:
        return []
    return re.findall(r"['\"]([^'\"]+)['\"]", match.group(1))


def parse_guarded(text: str) -> list[str]:
    match = re.search(r"protected\s+\$guarded\s*=\s*\[(.*?)\]", text, flags=re.S)
    if not match:
        return []
    return re.findall(r"['\"]([^'\"]+)['\"]", match.group(1))


def parse_hidden(text: str) -> list[str]:
    match = re.search(r"protected\s+\$hidden\s*=\s*\[(.*?)\]", text, flags=re.S)
    if not match:
        return []
    return re.findall(r"['\"]([^'\"]+)['\"]", match.group(1))


def parse_casts(text: str) -> dict[str, str]:
    casts = {}
    match = re.search(r"protected\s+\$casts\s*=\s*\[(.*?)\]", text, flags=re.S)
    if not match:
        return casts
    body = match.group(1)
    for item in re.finditer(r"['\"]([^'\"]+)['\"]\s*=>\s*['\"]([^'\"]+)['\"]", body):
        casts[item.group(1)] = item.group(2)
    return casts


def parse_methods(text: str) -> list[str]:
    methods = []
    for match in re.finditer(
        r"(?:public|protected|private)?\s*function\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(",
        text
    ):
        name = match.group(1)
        if name not in methods:
            methods.append(name)
    return methods


FUNC_START_RE = re.compile(
    r"(?:public|protected|private)?\s*function\s+"
    r"([A-Za-z_][A-Za-z0-9_]*)\s*\([^)]*\)[^{;]*\{",
    re.S
)


def parse_relations(text: str):
    """
    Split the class body into individual method boundaries first,
    then search for a relation call ONLY within each method's own
    body. This avoids the classic bug where a non-greedy regex
    spanning the whole class body attaches a relation call found
    in method B to the name of method A (happens whenever two
    methods share the same relation type, e.g. two belongsTo()).
    """
    relations = []

    starts = [(m.group(1), m.end()) for m in FUNC_START_RE.finditer(text)]
    if not starts:
        return relations

    boundaries = [s[1] for s in starts[1:]] + [len(text)]

    for (name, body_start), body_end in zip(starts, boundaries):
        body = text[body_start:body_end]
        for method, cardinality in RELATION_METHODS.items():
            rel_match = re.search(
                rf"return\s+\$this\s*->\s*{method}"
                rf"\s*\(\s*([A-Za-z_][A-Za-z0-9_\\]*)::class",
                body
            )
            if rel_match:
                relations.append({
                    "name": name,
                    "target": short_class_name(rel_match.group(1)),
                    "method": method,
                    "cardinality": cardinality,
                })
                break

    return relations


def parse_models():
    models = OrderedDict()
    if not MODELS_DIR.exists():
        return models

    for path in sorted(MODELS_DIR.rglob("*.php")):
        raw = path.read_text(encoding="utf-8", errors="ignore")
        text = clean_php(raw)
        cls = model_class(text, path.stem)

        models[cls] = {
            "class": cls,
            "table": table_from_model(cls, text),
            "path": str(path.relative_to(ROOT)),
            "parent": model_parent(text),
            "interfaces": model_interfaces(text),
            "traits": model_traits(text),
            "fillable": parse_fillable(text),
            "guarded": parse_guarded(text),
            "hidden": parse_hidden(text),
            "casts": parse_casts(text),
            "methods": parse_methods(text),
            "relations": parse_relations(text),
            "columns": OrderedDict(),
            "group": "Other",
        }

        if DEBUG:
            rels = models[cls]["relations"]
            print(f"  [model] {cls} -> table='{models[cls]['table']}' "
                  f"relations={[(r['name'], r['method'], r['target']) for r in rels]}")

    return models

# ============================================================
# Migration Parsing
# ============================================================

def parse_migrations():
    tables = OrderedDict()
    fks = []
    if not MIGRATIONS_DIR.exists():
        return tables, fks

    for path in sorted(MIGRATIONS_DIR.rglob("*.php")):
        text = clean_php(path.read_text(encoding="utf-8", errors="ignore"))

        for match in re.finditer(
            r"""
            Schema::create(?:IfNotExists)?
            \s*\(
                \s*['"]([^'"]+)['"]
                \s*,\s*
                function\s*\([^)]*\)\s*\{
            """,
            text, flags=re.X
        ):
            table = match.group(1)
            start = match.end()
            next_schema = re.search(r"\bSchema::(?:create|table|drop|rename)\b", text[start:])
            block = text[start:start + next_schema.start()] if next_schema else text[start:]
            if table not in tables:
                tables[table] = {"columns": OrderedDict(), "path": str(path.relative_to(ROOT))}
            parse_table_block(table, block, tables, fks)

        for match in re.finditer(
            r"""
            Schema::table
            \s*\(
                \s*['"]([^'"]+)['"]
                \s*,\s*
                function\s*\([^)]*\)\s*\{
            """,
            text, flags=re.X
        ):
            table = match.group(1)
            start = match.end()
            next_schema = re.search(r"\bSchema::(?:create|table|drop|rename)\b", text[start:])
            block = text[start:start + next_schema.start()] if next_schema else text[start:]
            if table not in tables:
                tables[table] = {"columns": OrderedDict(), "path": str(path.relative_to(ROOT))}
            parse_table_block(table, block, tables, fks)

    unique = []
    seen = set()
    for fk in fks:
        key = tuple(fk)
        if key not in seen:
            seen.add(key)
            unique.append(fk)

    if DEBUG:
        print(f"  [migrations] tables found: {list(tables.keys())}")
        print(f"  [migrations] fks found: {unique}")

    return tables, unique


def guess_ref_table(column: str) -> str:
    """Guess the referenced table for a foreignId() column that has
    no explicit ->constrained('table') / ->references() call, e.g.
    'department_id' -> 'departments', 'category_id' -> 'categories',
    'staff_id' -> 'staff'. Uses proper pluralization instead of a
    blind '+ s', which is what silently broke edges for irregular
    words like category/status/staff."""
    base = re.sub(r"_id$", "", column)
    return pluralize_snake(base)


def parse_table_block(table, block, tables, fks):
    columns = tables[table]["columns"]

    special = [
        (r"\$table->id\s*\(\s*['\"]?([^'\")]*)['\"]?\s*\)", "BIGINT", "PK"),
        (r"\$table->bigIncrements\s*\(\s*['\"]([^'\"]+)['\"]\s*\)", "BIGINT", "PK"),
        (r"\$table->increments\s*\(\s*['\"]([^'\"]+)['\"]\s*\)", "INT", "PK"),
        (r"\$table->uuid\s*\(\s*['\"]([^'\"]+)['\"]\s*\)", "UUID", ""),
        (r"\$table->ulid\s*\(\s*['\"]([^'\"]+)['\"]\s*\)", "ULID", ""),
    ]
    for rx, typ, flag in special:
        for match in re.finditer(rx, block):
            name = match.group(1) or "id"
            columns.setdefault(name, [typ, flag])

    methods = {
        "string": "VARCHAR", "text": "TEXT", "mediumText": "MEDIUMTEXT",
        "longText": "LONGTEXT", "integer": "INT", "unsignedInteger": "INT UNSIGNED",
        "bigInteger": "BIGINT", "unsignedBigInteger": "BIGINT UNSIGNED",
        "tinyInteger": "TINYINT", "unsignedTinyInteger": "TINYINT UNSIGNED",
        "smallInteger": "SMALLINT", "unsignedSmallInteger": "SMALLINT UNSIGNED",
        "mediumInteger": "MEDIUMINT", "unsignedMediumInteger": "MEDIUMINT UNSIGNED",
        "decimal": "DECIMAL", "float": "FLOAT", "double": "DOUBLE",
        "boolean": "BOOLEAN", "date": "DATE", "dateTime": "DATETIME",
        "datetime": "DATETIME", "timestamp": "TIMESTAMP", "time": "TIME",
        "json": "JSON", "jsonb": "JSONB", "binary": "BLOB", "enum": "ENUM",
        "ipAddress": "IP", "macAddress": "MAC", "year": "YEAR",
    }
    for method, typ in methods.items():
        rx = re.compile(rf"\$table->{method}\s*\(\s*['\"]([^'\"]+)['\"]([^)]*)\)")
        for match in rx.finditer(block):
            name = match.group(1)
            args = match.group(2)
            display_type = typ
            if method in ("string", "decimal", "float", "double", "enum"):
                numbers = re.findall(r"\d+", args)
                if numbers:
                    display_type += "(" + ",".join(numbers[:2]) + ")"
            columns.setdefault(name, [display_type, ""])

    if re.search(r"\$table->timestamps\s*\(", block):
        columns.setdefault("created_at", ["TIMESTAMP", ""])
        columns.setdefault("updated_at", ["TIMESTAMP", ""])

    if re.search(r"\$table->softDeletes\s*\(", block):
        columns.setdefault("deleted_at", ["TIMESTAMP", ""])

    for match in re.finditer(
        r"""
        \$table->foreignId
        \s*\(
            \s*['"]([^'"]+)['"]
        \s*\)
        ([^;]*);
        """,
        block, flags=re.X
    ):
        column = match.group(1)
        tail = match.group(2)
        columns.setdefault(column, ["BIGINT UNSIGNED", "FK"])

        constrained = re.search(
            r"->constrained\s*\(\s*['\"]([^'\"]+)['\"]\s*(?:,\s*['\"]([^'\"]+)['\"]\s*)?\)",
            tail
        )
        if constrained:
            ref_table = constrained.group(1)
            ref_column = constrained.group(2) or "id"
        else:
            # No explicit table given to constrained() -- Laravel itself
            # infers the table name from the column here, using proper
            # pluralization. We now mirror that instead of a naive '+s'.
            ref_table = guess_ref_table(column)
            ref_column = "id"

        references = re.search(r"->references\s*\(\s*['\"]([^'\"]+)['\"]\s*\)", tail)
        if references:
            ref_column = references.group(1)

        fks.append((table, column, ref_table, ref_column))

    for match in re.finditer(
        r"""
        \$table->foreign
        \s*\(
            \s*['"]([^'"]+)['"]
        \s*\)
        ([^;]*);
        """,
        block, flags=re.X
    ):
        column = match.group(1)
        tail = match.group(2)
        on_match = re.search(r"->on\s*\(\s*['\"]([^'\"]+)['\"]\s*\)", tail)
        references = re.search(r"->references\s*\(\s*['\"]([^'\"]+)['\"]\s*\)", tail)
        if on_match:
            columns.setdefault(column, ["BIGINT UNSIGNED", "FK"])
            fks.append((table, column, on_match.group(1),
                        references.group(1) if references else "id"))

# ============================================================
# Merge Model / Migration Information
# ============================================================

def model_for_table(models, table):
    for cls, info in models.items():
        if info["table"] == table:
            return cls
    return None


def merge_columns(models, tables):
    for cls, info in models.items():
        table = info["table"]
        if table in tables:
            info["columns"] = tables[table]["columns"]
        else:
            info["columns"] = OrderedDict()
            if DEBUG:
                print(f"  [warn] model '{cls}' expects table '{table}' "
                      f"but no matching migration table was found "
                      f"(closest candidates: {list(tables.keys())})")

# ============================================================
# Groups
# ============================================================

GROUP_MAP = {
    "users": "Auth & Staff", "staff": "Auth & Staff", "departments": "Auth & Staff",
    "personal_access_tokens": "Auth & Staff", "staff_shifts": "Auth & Staff",
    "leave_requests": "Auth & Staff",
    "customers": "Customers & Bookings", "reservations": "Customers & Bookings",
    "reservation_customers": "Customers & Bookings", "bookings": "Customers & Bookings",
    "room_types": "Customers & Bookings", "room_categories": "Customers & Bookings",
    "rooms": "Customers & Bookings", "room_images": "Customers & Bookings",
    "events": "Events & Facilities", "halls": "Events & Facilities",
    "restaurants": "Events & Facilities",
    "services": "Services & Tasks", "service_customers": "Services & Tasks",
    "service_requests": "Services & Tasks", "fixed_tasks": "Services & Tasks",
    "fixed_task_items": "Services & Tasks", "tasks": "Services & Tasks",
    "task_item_statuses": "Services & Tasks",
    "complaints": "Communication", "hotel_news": "Communication",
    "notifications": "Communication",
}


def infer_groups(models):
    for cls, info in models.items():
        info["group"] = GROUP_MAP.get(info["table"], "Other")

# ============================================================
# Class Box
# ============================================================

def class_cell(info, x, y, width, height, cell_id):
    cls = info["class"]
    table = info["table"]
    parent = info["parent"]
    interfaces = info["interfaces"]
    traits = info["traits"]
    columns = info["columns"]
    methods = info["methods"]
    fillable = info["fillable"]
    casts = info["casts"]

    header = f"<B>{esc(cls)}</B>"
    subtitle = f"<FONT POINT-SIZE='9'>{esc(table)}</FONT>"
    if parent:
        subtitle += f"<BR><FONT POINT-SIZE='8'>extends {esc(parent)}</FONT>"
    if interfaces:
        subtitle += f"<BR><FONT POINT-SIZE='8'>implements {esc(', '.join(interfaces))}</FONT>"

    attribute_rows = []
    for name, data in columns.items():
        typ, flag = data
        visibility = "-"
        attribute_rows.append(
            "<TR>"
            f"<TD ALIGN='LEFT'><FONT POINT-SIZE='9'>{visibility} {esc(name)}</FONT></TD>"
            f"<TD ALIGN='LEFT'><FONT POINT-SIZE='9'>{esc(typ)}</FONT></TD>"
            "</TR>"
        )
    if not attribute_rows:
        for name in fillable:
            typ = casts.get(name, "mixed")
            attribute_rows.append(
                "<TR>"
                f"<TD ALIGN='LEFT'><FONT POINT-SIZE='9'>- {esc(name)}</FONT></TD>"
                f"<TD ALIGN='LEFT'><FONT POINT-SIZE='9'>{esc(typ)}</FONT></TD>"
                "</TR>"
            )

    method_rows = []
    relation_names = {r["name"] for r in info["relations"]}
    for method in methods:
        symbol = "+"
        icon = " &#8646;" if method in relation_names else ""
        method_rows.append(
            "<TR><TD ALIGN='LEFT' COLSPAN='2'>"
            f"<FONT POINT-SIZE='9'>{symbol} {esc(method)}(){icon}</FONT>"
            "</TD></TR>"
        )
    if not method_rows:
        method_rows.append(
            "<TR><TD COLSPAN='2'><FONT POINT-SIZE='9'>No methods detected</FONT></TD></TR>"
        )

    trait_line = ""
    if traits:
        trait_line = (
            "<TR><TD ALIGN='LEFT' COLSPAN='2'>"
            f"<FONT POINT-SIZE='8'><I>Traits: {esc(', '.join(traits))}</I></FONT>"
            "</TD></TR>"
        )

    label = (
        "<TABLE BORDER='0' CELLBORDER='0' CELLSPACING='0' CELLPADDING='5' WIDTH='100%'>"
        "<TR><TD ALIGN='CENTER' COLSPAN='2'>" + header + "<BR>" + subtitle + "</TD></TR>"
        "<TR><TD COLSPAN='2' HEIGHT='1' BGCOLOR='#64748b'></TD></TR>"
        "<TR><TD ALIGN='LEFT' COLSPAN='2'><FONT POINT-SIZE='8'><B>ATTRIBUTES</B></FONT></TD></TR>"
        + "".join(attribute_rows)
        + "<TR><TD COLSPAN='2' HEIGHT='1' BGCOLOR='#64748b'></TD></TR>"
        + "<TR><TD ALIGN='LEFT' COLSPAN='2'><FONT POINT-SIZE='8'><B>METHODS</B></FONT></TD></TR>"
        + "".join(method_rows)
        + trait_line
        + "</TABLE>"
    )

    style = (
        "shape=rectangle;rounded=0;whiteSpace=wrap;html=1;"
        "fillColor=#ffffff;strokeColor=#334155;strokeWidth=1;"
        "align=left;verticalAlign=top;spacing=8;fontSize=10;shadow=0;"
    )

    return (
        f'<mxCell id="{cell_id}" value="{esc(label)}" style="{style}" '
        f'vertex="1" parent="1">'
        f'<mxGeometry x="{x}" y="{y}" width="{width}" height="{height}" as="geometry"/>'
        f'</mxCell>'
    )

# ============================================================
# Build Diagram
# ============================================================

def build_drawio(models, tables, fks):
    classes = OrderedDict()
    for cls, info in models.items():
        classes[cls] = info

    grouped = defaultdict(list)
    for cls, info in classes.items():
        grouped[info["group"]].append(cls)

    order = [
        "Auth & Staff", "Customers & Bookings", "Events & Facilities",
        "Services & Tasks", "Communication", "Other",
    ]

    class_w = 360
    in_group_gap_x = 70
    in_group_gap_y = 70
    group_gap_x = 160
    cols_per_group = 2
    top_y = 70
    left_x = 40

    positions = {}
    heights = {}
    class_ids = {}
    cells = []
    next_x = left_x

    def estimate_height(info):
        columns = len(info["columns"]) or len(info["fillable"])
        methods = len(info["methods"])
        traits = 1 if info["traits"] else 0
        height = 110 + columns * 22 + methods * 22 + traits * 24
        return max(220, min(height, 900))

    for group in order:
        items = sorted(grouped.get(group, []))
        if not items:
            continue
        rows = [items[i:i + cols_per_group] for i in range(0, len(items), cols_per_group)]
        row_heights = []
        for row in rows:
            row_height = 250
            for cls in row:
                row_height = max(row_height, estimate_height(classes[cls]))
            row_heights.append(row_height)

        group_header = 42
        side_pad = 30
        group_h = group_header + sum(row_heights) + (len(rows) - 1) * in_group_gap_y + 40
        group_w = (
            cols_per_group * class_w
            + (cols_per_group - 1) * in_group_gap_x
            + side_pad * 2
        )

        cells.append(
            f'<mxCell id="{gid()}" value="{esc(group)}" style="'
            f'swimlane;html=1;rounded=0;startSize={group_header};'
            f'fillColor=#ffffff;strokeColor=#94a3b8;strokeWidth=1;'
            f'fontStyle=1;shadow=0;" vertex="1" parent="1">'
            f'<mxGeometry x="{next_x}" y="{top_y}" width="{group_w}" height="{group_h}" as="geometry"/>'
            f'</mxCell>'
        )

        y = top_y + group_header + 20
        for row_index, row in enumerate(rows):
            x = next_x + side_pad
            for cls in row:
                positions[cls] = (x, y)
                info = classes[cls]
                heights[cls] = estimate_height(info)
                class_id = gid()
                class_ids[cls] = class_id
                cells.append(class_cell(info, x, y, class_w, heights[cls], class_id))
                x += class_w + in_group_gap_x
            y += row_heights[row_index] + in_group_gap_y

        next_x += group_w + group_gap_x

    def get_center(cls):
        x, y = positions[cls]
        h = heights[cls]
        return (x + class_w / 2, y + h / 2)

    def connection_points(src_cls, dst_cls):
        """Decide which side of each box an edge should leave/enter
        from, based on relative position, so edges travel along clean
        horizontal/vertical lanes beside the boxes instead of cutting
        diagonally through class bodies."""
        sx, sy = get_center(src_cls)
        tx, ty = get_center(dst_cls)
        dx, dy = tx - sx, ty - sy
        if abs(dx) >= abs(dy):
            if dx >= 0:
                return "exitX=1;exitY=0.5;exitDx=0;exitDy=0;", "entryX=0;entryY=0.5;entryDx=0;entryDy=0;"
            return "exitX=0;exitY=0.5;exitDx=0;exitDy=0;", "entryX=1;entryY=0.5;entryDx=0;entryDy=0;"
        if dy >= 0:
            return "exitX=0.5;exitY=1;exitDx=0;exitDy=0;", "entryX=0.5;entryY=0;entryDx=0;entryDy=0;"
        return "exitX=0.5;exitY=0;exitDx=0;exitDy=0;", "entryX=0.5;entryY=1;entryDx=0;entryDy=0;"

    # ========================================================
    # Inheritance relationships
    # ========================================================
    for cls, info in classes.items():
        parent = info["parent"]
        if not parent or cls not in class_ids or parent not in class_ids:
            continue
        cells.append(
            f'<mxCell id="{gid()}" value="extends" style="'
            f'edgeStyle=orthogonalEdgeStyle;rounded=0;html=1;'
            + "".join(connection_points(cls, parent)) +
            f'endArrow=block;endFill=1;startArrow=none;'
            f'strokeColor=#0f766e;strokeWidth=2;'
            f'fontSize=9;fontColor=#0f766e;" '
            f'edge="1" parent="1" source="{class_ids[cls]}" target="{class_ids[parent]}">'
            f'<mxGeometry relative="1" as="geometry"/></mxCell>'
        )

    # ========================================================
    # Model relationships (declared via Eloquent methods)
    # Drawn as solid blue lines with an open-diamond/arrow pair
    # that reflects direction and an explicit cardinality label
    # (e.g. "1" .. "N") on the line itself, since a bare "N:1"
    # text label in the middle of a long orthogonal edge is easy
    # to lose track of.
    # ========================================================
    drawn_pairs = set()
    for cls, info in classes.items():
        if cls not in class_ids:
            continue
        for relation in info["relations"]:
            target = relation["target"]
            if target not in class_ids:
                if DEBUG:
                    print(f"  [warn] {cls}::{relation['name']}() -> "
                          f"{target} but '{target}' is not a known model class")
                continue

            cardinality = relation["cardinality"]
            left_card, right_card = cardinality.split(":")
            label = f"{left_card} : {right_card}"

            pair_key = (min(cls, target), max(cls, target), relation["name"])
            if pair_key in drawn_pairs:
                continue
            drawn_pairs.add(pair_key)

            exit_style, entry_style = connection_points(cls, target)
            cells.append(
                f'<mxCell id="{gid()}" value="{esc(label)}" style="'
                f'edgeStyle=orthogonalEdgeStyle;rounded=0;orthogonalLoop=1;'
                f'jettySize=auto;html=1;{exit_style}{entry_style}'
                f'startArrow=diamondThin;startFill=0;startSize=10;'
                f'endArrow=open;endFill=1;endSize=10;'
                f'strokeColor=#2563eb;strokeWidth=1.5;'
                f'fontSize=9;fontColor=#1d4ed8;fontStyle=1;'
                f'labelBackgroundColor=#ffffff;" '
                f'edge="1" parent="1" source="{class_ids[cls]}" target="{class_ids[target]}">'
                f'<mxGeometry relative="1" as="geometry"/></mxCell>'
            )

    # ========================================================
    # Migration FK relationships (no matching Eloquent method,
    # e.g. a foreignId() column with no corresponding
    # belongsTo() found in the model). Drawn as dashed grey
    # lines with a plain arrowhead so they read as "structural
    # only" next to the bold blue Eloquent relationships above.
    # ========================================================
    table_to_class = {info["table"]: cls for cls, info in classes.items()}
    unmatched_fks = []

    for src_table, src_col, dst_table, dst_col in fks:
        src_cls = table_to_class.get(src_table)
        dst_cls = table_to_class.get(dst_table)
        if not src_cls or not dst_cls:
            unmatched_fks.append((src_table, src_col, dst_table, dst_col))
            continue
        if src_cls not in class_ids or dst_cls not in class_ids:
            continue

        has_relation = any(
            relation["target"] == dst_cls for relation in classes[src_cls]["relations"]
        )
        if has_relation:
            continue

        exit_style, entry_style = connection_points(src_cls, dst_cls)
        cells.append(
            f'<mxCell id="{gid()}" value="{esc(src_col + " -> " + dst_col)}" style="'
            f'edgeStyle=orthogonalEdgeStyle;rounded=0;dashed=1;html=1;{exit_style}{entry_style}'
            f'endArrow=open;endFill=1;endSize=8;startArrow=none;'
            f'strokeColor=#64748b;strokeWidth=1;'
            f'fontSize=8;fontColor=#475569;labelBackgroundColor=#ffffff;" '
            f'edge="1" parent="1" source="{class_ids[src_cls]}" target="{class_ids[dst_cls]}">'
            f'<mxGeometry relative="1" as="geometry"/></mxCell>'
        )

    if DEBUG and unmatched_fks:
        print("  [warn] foreign keys pointing at a table with no matching model:")
        for row in unmatched_fks:
            print(f"    {row[0]}.{row[1]} -> {row[2]}.{row[3]}  "
                  f"(no model maps to table '{row[2]}')")

    return f'''<?xml version="1.0" encoding="UTF-8"?>
<mxfile host="app.diagrams.net" modified="2026-08-15T00:00:00.000Z" agent="Laravel-Class-Diagram" version="24.7.17" type="device">
  <diagram id="{gid()}" name="Hotel Management Class Diagram">
    <mxGraphModel dx="1600" dy="1000" grid="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" page="1" pageScale="1" pageWidth="1600" pageHeight="1200" math="0" shadow="0">
      <root>
        <mxCell id="0"/>
        <mxCell id="1" parent="0"/>
        {''.join(cells)}
      </root>
    </mxGraphModel>
  </diagram>
</mxfile>
'''

# ============================================================
# Main
# ============================================================

def main():
    if not MODELS_DIR.exists():
        raise SystemExit("ERROR: app/Models was not found.")

    print()
    print("=" * 60)
    print(" Laravel -> UML Class Diagram")
    print("=" * 60)
    print()

    print("Reading Laravel Models...")
    models = parse_models()
    print(f"Models found: {len(models)}")

    print("Reading Laravel migrations...")
    tables, fks = parse_migrations()
    print(f"Tables found: {len(tables)}")
    print(f"Foreign keys found: {len(fks)}")

    merge_columns(models, tables)
    infer_groups(models)

    total_relations = sum(len(info["relations"]) for info in models.values())
    print(f"Eloquent relations found: {total_relations}")

    print("Building draw.io diagram...")
    xml = build_drawio(models, tables, fks)
    OUT.write_text(xml, encoding="utf-8")

    print()
    print("=" * 60)
    print(" CLASS DIAGRAM GENERATED")
    print("=" * 60)
    print()
    print(f"Output: {OUT}")
    print()
    print("Open this file with diagrams.net / draw.io:")
    print("hotel-management-class-diagram.drawio")
    print()
    if not DEBUG:
        print("Tip: run with --debug to see exactly which relations/")
        print("foreign keys were detected (or missed) per model, e.g.:")
        print("    python generate_class_diagram.py --debug")
    print()
    print("No Laravel backend files were modified.")
    print()


if __name__ == "__main__":
    main()
