<?php

use Illuminate\Support\Facades\Route;
use App\Models\Event;
use App\Models\Company;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-architecture', function () {
    // 1. Create a Test Event
    $event = Event::firstOrCreate(
        ['name' => 'Tech Demo 2024'],
        [
            'start_date' => now(),
            'end_date' => now()->addDays(3),
            'description' => 'A test event to validate schema.'
        ]
    );

    // 2. Create a Test Company
    $company = Company::firstOrCreate(
        ['name' => 'Future Corp'],
        [
            'email' => 'contact@futurecorp.com',
            'domain_field' => 'AI & Robotics'
        ]
    );

    // 3. Link them (Test the Pivot Table)
    // We attach the company to the event with a booth number
    if (!$event->companies()->where('company_id', $company->id)->exists()) {
        $event->companies()->attach($company->id, [
            'booth_number' => 'A-100',
            'map_coordinates' => json_encode(['x' => 50, 'y' => 100])
        ]);
    }

    // 4. Return the result
    return response()->json([
        'status' => 'Success',
        'message' => 'Database relationships are working!',
        'event' => $event->load('companies'), // Should show the company inside the event
    ]);
});
