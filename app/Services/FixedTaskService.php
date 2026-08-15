<?php

namespace App\Services;

use App\Models\FixedTask;
use App\Models\FixedTaskItem;
use Illuminate\Support\Facades\DB;

class FixedTaskService
{
    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {

            $task = FixedTask::create([
                'staff_id' => $data['staff_id'], // 🔥 مهم
                'dep_id'   => $data['dep_id'],
                'name_ar'  => $data['name_ar'],
                'name_en'  => $data['name_en'],
                    'estimated_minutes' => $data['estimated_minutes'] ?? 30,
 'weight' => $data['weight'] ?? 1,
                'is_active'=> $data['is_active'] ?? true,
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
    }
}