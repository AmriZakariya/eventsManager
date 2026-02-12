<?php

declare(strict_types=1);

namespace App\Orchid;

use Orchid\Platform\Dashboard;
use Orchid\Platform\ItemPermission;
use Orchid\Platform\OrchidServiceProvider;
use Orchid\Screen\Actions\Menu;
use Orchid\Support\Color;
use Illuminate\Support\Facades\Route;

class PlatformProvider extends OrchidServiceProvider
{
    /**
     * @param Dashboard $dashboard
     */
    public function boot(Dashboard $dashboard): void
    {
        parent::boot($dashboard);
    }

    /**
     * @return Menu[]
     */
    public function menu(): array
    {
        return [
            // =============================================================
            // 1. LIVE OPERATIONS (High Priority / Daily Tasks)
            // =============================================================
            Menu::make('B2B Meetings')
                ->title('Live Operations')
                ->icon('bs.calendar-check')
                ->route('platform.appointments')
                ->permission('platform.appointments')
                ->badge(fn() => \App\Models\Appointment::where('status', 'pending')->count(), Color::WARNING),

            Menu::make('Inbox Requests')
                ->icon('bs.envelope-paper')
                ->route('platform.contacts')
                ->permission('platform.contacts')
                ->badge(fn() => \App\Models\ContactRequest::where('is_handled', 0)->count(), Color::DANGER),

            Menu::make('Chat Monitoring')
                ->icon('bs.chat-quote')
                ->route('platform.conversations.list') // Ensure this route exists
                ->permission('platform.contacts'),

            Menu::make('Networking Requests')
                ->icon('bs.person-plus')
                ->route('platform.networking.requests')
                ->permission('platform.contacts'),

            // =============================================================
            // 2. EXHIBITION & PARTNERS (Commercial Data)
            // =============================================================
            Menu::make('Companies')
                ->title('Exhibition & Partners')
                ->icon('bs.building')
                ->route('platform.companies.list')
                ->permission('platform.companies'),

            Menu::make('Exhibitor Team')
                ->icon('bs.people-fill')
                ->route('platform.exhibitors.team')
                ->permission('platform.companies'),

            // ✅ CORRECTED: Route name matches ProductListScreen
            Menu::make('Products Gallery')
                ->icon('bs.box-seam')
                ->route('platform.products.list')
                ->permission('platform.products'),

            // ✅ CORRECTED: Route name matches CategoryListScreen
            Menu::make('Product Categories')
                ->icon('bs.tags')
                ->route('platform.product-categories.list')
                ->permission('platform.products'),

            // =============================================================
            // 3. EVENT PROGRAM (Content)
            // =============================================================
            Menu::make('Agenda & Talks')
                ->title('Conference Program')
                ->icon('bs.mic')
                ->route('platform.conferences.list')
                ->permission('platform.conferences'),

            // ✅ CORRECTED: Route name matches SpeakerListScreen
            Menu::make('Speakers')
                ->icon('bs.person-video2')
                ->route('platform.speakers.list')
                ->permission('platform.speakers'), // Ensure permission key matches below

            Menu::make('HCE Awards')
                ->icon('bs.trophy')
                ->route('platform.awards.list')
                ->permission('platform.awards'),

            // =============================================================
            // 4. MOBILE APP CMS (Look & Feel)
            // =============================================================
            // ✅ CORRECTED: This points to the Banner/Home Manager we built
            Menu::make('App Home Screen')
                ->icon('bs.phone')
                ->title('CMS')
                ->route('platform.content.widgets.list')
                ->permission('platform.app.banners'),

            Menu::make('Event Settings')
                ->icon('bs.gear-wide-connected')
                ->route('platform.event.settings')
                ->permission('platform.event.settings'),

            // =============================================================
            // 5. SYSTEM ACCESS (Admin)
            // =============================================================
            Menu::make('Users')
                ->title('Access Control')
                ->icon('bs.person-gear')
                ->route('platform.systems.users')
                ->permission('platform.systems.users'),

            Menu::make('Roles')
                ->icon('bs.shield-lock')
                ->route('platform.systems.roles')
                ->permission('platform.systems.roles'),
        ];
    }

    /**
     * @return ItemPermission[]
     */
    public function permissions(): array
    {
        return [
            ItemPermission::group(__('Live Operations'))
                ->addPermission('platform.appointments', __('Manage Meetings'))
                ->addPermission('platform.contacts', __('Manage Inbox & Chats')),

            ItemPermission::group(__('Exhibition Management'))
                ->addPermission('platform.companies', __('Manage Exhibitors'))
                ->addPermission('platform.products', __('Manage Products')),

            ItemPermission::group(__('Conference Program'))
                ->addPermission('platform.conferences', __('Manage Agenda'))
                ->addPermission('platform.speakers', __('Manage Speakers')) // Added this
                ->addPermission('platform.awards', __('Manage Awards')),

            ItemPermission::group(__('App Configuration'))
                ->addPermission('platform.event.settings', __('Global Settings'))
                ->addPermission('platform.app.banners', __('CMS & Banners')),

            ItemPermission::group(__('System'))
                ->addPermission('platform.systems.roles', __('Manage Roles'))
                ->addPermission('platform.systems.users', __('Manage Users')),
        ];
    }
}
