<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Gives the new "Monitor Push Notifications" permission to every role and
 * user that can already view audit logs, so admins see the page right away.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['roles', 'users'] as $table) {
            DB::table($table)->whereNotNull('permissions')->orderBy('id')->each(function ($row) use ($table) {
                $permissions = json_decode($row->permissions, true);

                if (! is_array($permissions) || empty($permissions['platform.audit-logs'])) {
                    return;
                }

                $permissions['platform.push-monitor'] = true;
                DB::table($table)->where('id', $row->id)->update(['permissions' => json_encode($permissions)]);
            });
        }
    }

    public function down(): void
    {
        foreach (['roles', 'users'] as $table) {
            DB::table($table)->whereNotNull('permissions')->orderBy('id')->each(function ($row) use ($table) {
                $permissions = json_decode($row->permissions, true);

                if (is_array($permissions) && array_key_exists('platform.push-monitor', $permissions)) {
                    unset($permissions['platform.push-monitor']);
                    DB::table($table)->where('id', $row->id)->update(['permissions' => json_encode($permissions)]);
                }
            });
        }
    }
};
