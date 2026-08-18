<?php

namespace App\Services;

use App\Models\FixedTask;
use App\Models\FixedTaskItem;
use Illuminate\Support\Facades\DB;
use App\Models\Staff;

class FixedTaskService
{public function create(array $data)
{
    return DB::transaction(function () use ($data) {

        $staff = Staff::findOrFail($data['staff_id']);

        $onLeave = $staff->leaves()
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', now()->toDateString())
            ->whereDate('end_date', '>=', now()->toDateString())
            ->exists();

        // ممنوع إنشاء Fixed Task لعامل بإجازة أو خارج الشيفت
        if ($onLeave || !$staff->isWorkingNow()) {
            return null;
        }

        $task = FixedTask::create([
            'staff_id' => $data['staff_id'],
            'dep_id'   => $data['dep_id'],
            'name_ar'  => $data['name_ar'],
            'name_en'  => $data['name_en'],
            'estimated_minutes' => $data['estimated_minutes'] ?? 30,
            'weight' => $data['weight'] ?? 1,
            'is_active' => $data['is_active'] ?? true,
        ]);

        if (!empty($data['items']) && is_array($data['items'])) {

            foreach ($data['items'] as $item) {
                FixedTaskItem::create([
                    'fixed_task_id' => $task->id,
                    'name_ar'       => $item['name_ar'],
                    'name_en'       => $item['name_en'],
                ]);
            }
        }

        return $task->load('items');
    });
}}