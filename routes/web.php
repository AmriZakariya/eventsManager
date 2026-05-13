<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\ContactRequest;
use App\Models\Event;
use App\Models\Company;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/terms-and-conditions', function () {
    return view('pages.terms');
})->name('terms.conditions');

Route::get('/delete-account', function () {
    return view('pages.delete-account');
})->name('account.delete.request');

Route::post('/delete-account', function (Request $request) {
    $validated = $request->validate([
        'name' => ['nullable', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:255'],
        'message' => ['nullable', 'string', 'max:3000'],
    ]);

    ContactRequest::create([
        'name' => $validated['name'] ?? null,
        'email' => $validated['email'],
        'subject' => 'Account deletion request',
        'message' => trim(($validated['message'] ?? '') . "\n\nRequest type: Delete account and associated personal data."),
        'is_handled' => false,
    ]);

    return back()->with('status', 'Your account deletion request has been received. Our support team will contact you by email.');
})->name('account.delete.submit');

Route::view('/reset-password-landing', 'pages.reset-password')->name('password.reset.landing');
