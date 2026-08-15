<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\ServiceRequestService;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
use Illuminate\Support\Facades\Schedule;

Schedule::command('rooms:update-statuses')->everyMinute();



Schedule::call(function () {

    app(ServiceRequestService::class)
        ->reassignDelayedRequests();

})->everyMinute();