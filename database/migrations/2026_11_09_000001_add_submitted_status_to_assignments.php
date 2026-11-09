<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE assignments MODIFY status ENUM('pending', 'approved', 'rejected', 'cancelled', 'completed', 'submitted') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::table('assignments')->where('status', 'submitted')->update(['status' => 'approved']);
            DB::statement("ALTER TABLE assignments MODIFY status ENUM('pending', 'approved', 'rejected', 'cancelled', 'completed') NOT NULL DEFAULT 'pending'");
        }
    }
};
