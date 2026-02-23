<?php

use App\Http\Controllers\Api\B2BController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\ConferenceController;
use App\Http\Controllers\Api\ContactRequestController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\LanguageController;
use App\Http\Controllers\Api\NetworkingController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SpeakerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AppConfigController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\InteractionController;
use App\Http\Controllers\Api\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/


Route::get('/companies', [CompanyController::class, 'index']);
Route::get('/companies/{id}', [CompanyController::class, 'show']);

// 3. USER ACTIONS (Auth Required)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/companies/{id}/favorite', [CompanyController::class, 'toggleFavorite']);

    // Networking
    Route::prefix('networking')->group(function () {
        // Discovery: Find new people to connect with
        Route::get('/discover', [NetworkingController::class, 'discover']);

        // Network: See pending requests and accepted friends
        Route::get('/my-network', [NetworkingController::class, 'myNetwork']);

        // Action: Connect, Accept, Decline, or Cancel
        Route::post('/toggle-connection', [NetworkingController::class, 'toggleConnection']);

    });

    // Chat System
    Route::get('/chat/conversations', [ChatController::class, 'conversations']);
    Route::get('/chat/messages/{userId}', [ChatController::class, 'messages']);
    Route::post('/chat/send', [ChatController::class, 'send']);

    // --- PRODUCTS & SHOWCASE ---
    Route::get('/products/categories', [ProductController::class, 'categories']); // Filter Chips
    Route::get('/products', [ProductController::class, 'index']);                 // Product Grid
    Route::get('/products/{id}', [ProductController::class, 'show']);

    // B2B Meetings
    Route::get('/my-appointments', [B2BController::class, 'myAppointments']);
    Route::get('/user-availability/{userId}', [B2BController::class, 'userAvailability']);
    Route::post('/book-meeting', [B2BController::class, 'bookMeeting']);
    Route::put('/appointments/{id}/respond', [B2BController::class, 'respondToMeeting']);
    Route::delete('/appointments/{id}', [B2BController::class, 'cancelAppointment']);
    Route::get('/exhibitors', [B2BController::class, 'exhibitors']);

    Route::get('/conferences', [ConferenceController::class, 'index']);
    Route::get('/speakers', [SpeakerController::class, 'index']);
    Route::get('/speakers/{id}', [SpeakerController::class, 'show']);

    Route::post('/contact/send', [ContactRequestController::class, 'sendMessage']);
    Route::post('/auth/update-avatar', [AuthController::class, 'updateAvatar']);
    Route::get('/auth/stats', [AuthController::class, 'getStats']);
    Route::post('/auth/update-locale', [AuthController::class, 'updateLocale']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Notification Routes
    Route::post('/notifications/device-token', [NotificationController::class, 'saveDeviceToken']); // ADD THIS LINE
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
});

// 4. AUTHENTICATION (Standard Sanctum)
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

Route::prefix('config')->group(function () {
    Route::get('/init', [AppConfigController::class, 'init']);
    Route::get('/minimal', [AppConfigController::class, 'minimal']);
    Route::get('/features', [AppConfigController::class, 'features']);
    Route::get('/home', [HomeController::class, 'index']);
});

Route::get('/languages', [LanguageController::class, 'index']);
Route::get('/languages/{code}/translations', [LanguageController::class, 'translations']);
Route::get('/languages/all', [LanguageController::class, 'all']);
