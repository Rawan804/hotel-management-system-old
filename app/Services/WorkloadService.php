<?php

namespace App\Services;

use App\Models\Staff;

class WorkloadService
{
    // زيادة الحمل
    public static function add(Staff $staff, int $weight): void
    {
        $staff->increment('service_load', $weight);
    }

    // إنقاص الحمل
    public static function remove(Staff $staff, int $weight): void
    {
        $staff->decrement('service_load', $weight);
    }
}