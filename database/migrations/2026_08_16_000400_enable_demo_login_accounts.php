<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereIn('username', ['admin', 'siti.rahmawati', 'rizky.pratama', 'dewi.anggraini', 'arif.nugroho', 'nadia.putri', 'fajar.maulana', 'lina.kurnia'])
            ->update(['password' => Hash::make('DemoAksiSoft2026!'), 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Passwords cannot be restored safely after a one-way hash update.
    }
};
