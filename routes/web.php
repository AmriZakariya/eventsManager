<?php

use Illuminate\Support\Facades\Route;
use App\Models\Event;
use App\Models\Company;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/terms-and-conditions', function () {
    return view('pages.terms');
})->name('terms.conditions');

Route::view('/reset-password-landing', 'pages.reset-password')->name('password.reset.landing');
