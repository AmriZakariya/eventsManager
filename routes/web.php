<?php

use Illuminate\Support\Facades\Route;
use App\Models\Event;
use App\Models\Company;

Route::get('/', function () {
    return view('welcome');
});
