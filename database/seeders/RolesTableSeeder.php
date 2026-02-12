<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Orchid\Platform\Models\Role;

class RolesTableSeeder extends Seeder
{
    public function run()
    {
        // 1. ADMINISTRATOR (Super User)
        // Access: Everything
        $admin = Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Administrator']
        );

        $admin->permissions = [
            'platform.index' => true, // Can access Admin Panel
            'platform.systems.roles' => true,
            'platform.systems.users' => true,
            'platform.events' => true,
            'platform.companies' => true,
            'platform.conferences' => true,
            'platform.organizers' => true,
            'platform.contacts' => true,
            'platform.products' => true,
        ];
        $admin->save();

        // 2. EXHIBITOR (Professional)
        // Access: Mobile Notification (High Level)
        // Note: We currently give them NO web access ('platform.index' => false)
        // They will use the API.
        $exhibitor = Role::firstOrCreate(
            ['slug' => 'exhibitor'],
            ['name' => 'Exhibitor']
        );

        $exhibitor->permissions = [
            'platform.index' => false, // Cannot login to Admin Panel (Notification only)
            'app.manage_products' => true, // Custom API permission
            'app.scan_badges' => true,     // Custom API permission
        ];
        $exhibitor->save();

        // 3. VISITOR (Public)
        // Access: Mobile Notification (Standard)
        $visitor = Role::firstOrCreate(
            ['slug' => 'visitor'],
            ['name' => 'Visitor']
        );

        $visitor->permissions = [
            'platform.index' => false, // Cannot login to Admin Panel
            'app.view_content' => true,
            'app.book_appointments' => true,
        ];
        $visitor->save();
    }
}
