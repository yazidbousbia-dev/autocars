<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE cars MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'sold', 'expired') DEFAULT 'pending'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE cars MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'sold') DEFAULT 'pending'");
    }
};