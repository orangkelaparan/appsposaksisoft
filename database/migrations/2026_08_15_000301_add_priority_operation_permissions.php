<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        foreach ([
            'stores.switch' => ['Switch Outlet', 'stores'],
            'expenses.view' => ['View Expenses', 'expenses'],
            'expenses.create' => ['Create Expenses', 'expenses'],
            'expenses.approve' => ['Approve Expenses', 'expenses'],
            'sales.quote' => ['Manage Quotations', 'sales'],
            'sales.order' => ['Manage Sales Orders', 'sales'],
        ] as $slug => [$name, $module]) {
            $permissionId = DB::table('permissions')->where('slug', $slug)->value('id');
            if (! $permissionId) {
                $permissionId = DB::table('permissions')->insertGetId(['name' => $name, 'slug' => $slug, 'module' => $module, 'created_at' => $now, 'updated_at' => $now]);
            }
            $superAdmin = DB::table('roles')->where('slug', 'super-administrator')->value('id');
            if ($superAdmin && ! DB::table('role_permissions')->where('role_id', $superAdmin)->where('permission_id', $permissionId)->exists()) {
                DB::table('role_permissions')->insert(['role_id' => $superAdmin, 'permission_id' => $permissionId]);
            }
        }
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('slug', ['stores.switch', 'expenses.view', 'expenses.create', 'expenses.approve', 'sales.quote', 'sales.order'])->delete();
    }
};
