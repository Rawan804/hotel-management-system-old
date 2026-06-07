<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
use Illuminate\Support\Facades\Mail;

Route::get('/test-mail', function () {

    Mail::raw('Test Email from Laravel', function ($msg) {
        $msg->to('ghesraa02@gmail.com')->subject('Test');
    });

    return 'sent';
});
