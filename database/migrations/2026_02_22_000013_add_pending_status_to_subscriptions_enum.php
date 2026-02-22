<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE subscriptions MODIFY status ENUM('pending', 'active', 'expired', 'cancelled') NOT NULL DEFAULT 'pending'");
        } elseif ($driver === 'sqlite') {
            // SQLite does not enforce enum types like MySQL; no table alteration needed.
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("UPDATE subscriptions SET status = 'cancelled' WHERE status = 'pending'");
            DB::statement("ALTER TABLE subscriptions MODIFY status ENUM('active', 'expired', 'cancelled') NOT NULL DEFAULT 'active'");
        } elseif ($driver === 'sqlite') {
            // SQLite does not enforce enum types like MySQL; no table alteration needed.
        }
    }
};

