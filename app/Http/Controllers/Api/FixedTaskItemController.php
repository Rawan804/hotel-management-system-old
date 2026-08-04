<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFixedTaskItemRequest;
use App\Models\FixedTaskItem;

class FixedTaskItemController extends Controller
{
    public function store(StoreFixedTaskItemRequest $request)
    {
        $item = FixedTaskItem::create($request->validated());

        return response()->json([
            'message' => app()->getLocale() === 'ar'
                ? 'تم إضافة عنصر المهمة'
                : 'Task item created successfully',

            'data' => $item
        ]);
    }
}