<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\ServiceRequestService;
use Illuminate\Support\Facades\Schedule;

Schedule::command('leave:apply-statuses')->dailyAt('00:01');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::command('rooms:update-statuses')->everyMinute();
Schedule::command('staff:update-statuses')->everyMinute();



Schedule::call(function () {

    app(ServiceRequestService::class)
        ->reassignDelayedRequests();

})->everyMinute();

