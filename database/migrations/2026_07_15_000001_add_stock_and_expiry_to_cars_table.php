<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            // How many units of this exact car the seller has (dealers often sell several of the same model)
            $table->unsignedInteger('quantity')->default(1)->after('doors');
            // How many of those units have already been sold
            $table->unsignedInteger('sold_count')->default(0)->after('quantity');
            // Listing auto-expires from the public market after N days unless renewed
            $table->timestamp('expires_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn(['quantity', 'sold_count', 'expires_at']);
        });
    }
};
