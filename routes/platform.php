<?php

declare(strict_types=1);

use App\Orchid\Screens\Content\HomeWidgetEditScreen;
use App\Orchid\Screens\Content\HomeWidgetItemEditScreen;
use App\Orchid\Screens\Content\HomeWidgetListScreen;
use App\Orchid\Screens\Interaction\Networking\ConnectionRequestListScreen;
use App\Orchid\Screens\Interaction\Networking\ConversationMonitorScreen;
use Illuminate\Support\Facades\Route;
use Tabuna\Breadcrumbs\Trail;

// --- SCREENS IMPORTS ---
use App\Orchid\Screens\PlatformScreen;

// System
use App\Orchid\Screens\Role\RoleEditScreen;
use App\Orchid\Screens\Role\RoleListScreen;
use App\Orchid\Screens\User\UserEditScreen;
use App\Orchid\Screens\User\UserListScreen;
use App\Orchid\Screens\User\UserProfileScreen;

// Notification Config & CMS
use App\Orchid\Screens\Event\EventSettingScreen;

// Exhibition
use App\Orchid\Screens\Company\CompanyListScreen;
use App\Orchid\Screens\Company\CompanyEditScreen;
use App\Orchid\Screens\Exhibitor\ExhibitorUserListScreen;
use App\Orchid\Screens\Product\ProductListScreen;
use App\Orchid\Screens\Product\ProductEditScreen;
use App\Orchid\Screens\ProductCategory\ProductCategoryListScreen;
use App\Orchid\Screens\ProductCategory\ProductCategoryEditScreen;

// Program
use App\Orchid\Screens\Conference\ConferenceListScreen;
use App\Orchid\Screens\Conference\ConferenceEditScreen;
use App\Orchid\Screens\Speaker\SpeakerListScreen;
use App\Orchid\Screens\Speaker\SpeakerEditScreen;

// Features
use App\Orchid\Screens\Feature\AwardListScreen;

// Interaction
use App\Orchid\Screens\Appointment\AppointmentListScreen;
use App\Orchid\Screens\Contact\ContactRequestListScreen;
use App\Orchid\Screens\Interaction\ConversationListScreen;
use App\Orchid\Screens\Interaction\ConversationViewScreen;


/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
*/

// Main
Route::screen('/main', PlatformScreen::class)
    ->name('platform.main');

// Profile
Route::screen('profile', UserProfileScreen::class)
    ->name('platform.profile')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Profile'), route('platform.profile')));

// =========================================================================
// 1. APP CONFIGURATION & CMS
// =========================================================================

Route::screen('event/settings', EventSettingScreen::class)
    ->name('platform.event.settings')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Event Configuration', route('platform.event.settings')));

// =========================================================================
// 2. COMPANIES & PRODUCTS
// =========================================================================

// Companies
Route::screen('companies/create', CompanyEditScreen::class)
    ->name('platform.companies.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.companies.list')
        ->push('Add Company'));

Route::screen('companies/{company}/edit', CompanyEditScreen::class)
    ->name('platform.companies.edit')
    ->breadcrumbs(fn (Trail $trail, $company) => $trail
        ->parent('platform.companies.list')
        ->push($company->name));

Route::screen('companies', CompanyListScreen::class)
    ->name('platform.companies.list')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Companies', route('platform.companies.list')));

// Exhibitor Team
Route::screen('exhibitors/team', ExhibitorUserListScreen::class)
    ->name('platform.exhibitors.team')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Exhibitor Team', route('platform.exhibitors.team')));

// Product Categories
Route::screen('product-categories/create', ProductCategoryEditScreen::class)
    ->name('platform.product-categories.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.product-categories.list') // Fixed route name ref
        ->push('Create Category'));

Route::screen('product-categories/{category}/edit', ProductCategoryEditScreen::class)
    ->name('platform.product-categories.edit')
    ->breadcrumbs(fn (Trail $trail, $category) => $trail
        ->parent('platform.product-categories.list') // Fixed route name ref
        ->push($category->name));

Route::screen('product-categories', ProductCategoryListScreen::class)
    ->name('platform.product-categories.list') // MATCHING MENU
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Product Categories', route('platform.product-categories.list')));

// Products
Route::screen('products/create', ProductEditScreen::class)
    ->name('platform.products.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.products.list')
        ->push('Create Product'));

Route::screen('products/{product}/edit', ProductEditScreen::class)
    ->name('platform.products.edit')
    ->breadcrumbs(fn (Trail $trail, $product) => $trail
        ->parent('platform.products.list')
        ->push($product->name));

Route::screen('products', ProductListScreen::class)
    ->name('platform.products.list') // MATCHING MENU
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Products', route('platform.products.list')));


// =========================================================================
// 3. PROGRAM & AGENDA
// =========================================================================

// Conferences
Route::screen('conferences/create', ConferenceEditScreen::class)
    ->name('platform.conferences.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.conferences.list')
        ->push('New Session'));

Route::screen('conferences/{conference}/edit', ConferenceEditScreen::class)
    ->name('platform.conferences.edit')
    ->breadcrumbs(fn (Trail $trail, $conference) => $trail
        ->parent('platform.conferences.list')
        ->push($conference->title));

Route::screen('conferences', ConferenceListScreen::class)
    ->name('platform.conferences.list')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Agenda', route('platform.conferences.list')));

// Speakers (New)
Route::screen('speakers/create', SpeakerEditScreen::class)
    ->name('platform.speakers.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.speakers.list')
        ->push('Create Speaker'));

Route::screen('speakers/{speaker}/edit', SpeakerEditScreen::class)
    ->name('platform.speakers.edit')
    ->breadcrumbs(fn (Trail $trail, $speaker) => $trail
        ->parent('platform.speakers.list')
        ->push($speaker->full_name));

Route::screen('speakers', SpeakerListScreen::class)
    ->name('platform.speakers.list')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Speakers', route('platform.speakers.list')));


// =========================================================================
// 4. FEATURES & INTERACTION
// =========================================================================

Route::screen('awards', AwardListScreen::class)
    ->name('platform.awards.list')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('HCE Awards', route('platform.awards.list')));

Route::screen('appointments', AppointmentListScreen::class)
    ->name('platform.appointments')
    ->breadcrumbs(fn ($trail) => $trail
        ->parent('platform.index')
        ->push('B2B Meetings', route('platform.appointments')));


// AJAX endpoint for getting exhibitors
Route::get('appointments/get-exhibitors', [AppointmentListScreen::class, 'getExhibitors'])
    ->name('platform.appointments.get-exhibitors');


Route::screen('contacts', ContactRequestListScreen::class)
    ->name('platform.contacts')
    ->breadcrumbs(fn ($trail) => $trail
        ->parent('platform.index')
        ->push('Inbox', route('platform.contacts')));

Route::screen('conversations', ConversationListScreen::class)
    ->name('platform.conversations.list')
    ->breadcrumbs(fn ($trail) => $trail
        ->parent('platform.index')
        ->push('Conversations', route('platform.conversations.list')));

Route::screen('conversations/{user1}/{user2}', ConversationViewScreen::class)
    ->name('platform.conversations.view')
    ->breadcrumbs(fn ($trail) => $trail
        ->parent('platform.conversations.list')
        ->push('Chat History'));

// =========================================================================
// 5. SYSTEM ADMINISTRATION
// =========================================================================

Route::screen('users/{user}/edit', UserEditScreen::class)
    ->name('platform.systems.users.edit')
    ->breadcrumbs(fn (Trail $trail, $user) => $trail
        ->parent('platform.systems.users')
        ->push($user->name, route('platform.systems.users.edit', $user)));

Route::screen('users/create', UserEditScreen::class)
    ->name('platform.systems.users.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.systems.users')
        ->push(__('Create'), route('platform.systems.users.create')));

Route::screen('users', UserListScreen::class)
    ->name('platform.systems.users')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Users'), route('platform.systems.users')));

Route::screen('roles/{role}/edit', RoleEditScreen::class)
    ->name('platform.systems.roles.edit')
    ->breadcrumbs(fn (Trail $trail, $role) => $trail
        ->parent('platform.systems.roles')
        ->push($role->name, route('platform.systems.roles.edit', $role)));

Route::screen('roles/create', RoleEditScreen::class)
    ->name('platform.systems.roles.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.systems.roles')
        ->push(__('Create'), route('platform.systems.roles.create')));

Route::screen('roles', RoleListScreen::class)
    ->name('platform.systems.roles')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Roles'), route('platform.systems.roles')));

// Home Widgets
Route::screen('app/widgets', HomeWidgetListScreen::class)
    ->name('platform.content.widgets.list');

Route::screen('app/widgets/create', HomeWidgetEditScreen::class)
    ->name('platform.content.widgets.create');

Route::screen('app/widgets/{widget}/edit', HomeWidgetEditScreen::class)
    ->name('platform.content.widgets.edit');

// Home Widget Items - FIXED ROUTES
Route::screen('app/widgets/{widget}/items/create', HomeWidgetItemEditScreen::class)
    ->name('platform.content.items.create');

Route::screen('app/widgets/{widget}/items/{item}/edit', HomeWidgetItemEditScreen::class)
    ->name('platform.content.items.edit');


Route::screen('networking/requests', ConnectionRequestListScreen::class)
    ->name('platform.networking.requests');

Route::screen('networking/chats', ConversationMonitorScreen::class)
    ->name('platform.networking.chats');
