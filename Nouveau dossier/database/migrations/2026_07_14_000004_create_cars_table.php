<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // owner/seller
            $table->foreignId('dealer_id')->nullable()->constrained('dealers')->nullOnDelete();
            $table->foreignId('brand_id')->constrained();
            $table->string('model');
            $table->year('year');
            $table->unsignedBigInteger('price');
            $table->unsignedInteger('mileage')->default(0);
            $table->enum('fuel_type', ['essence', 'diesel', 'hybride', 'electrique', 'gpl'])->default('essence');
            $table->enum('transmission', ['manuelle', 'automatique'])->default('manuelle');
            $table->enum('condition', ['neuve', 'occasion', 'accidentee'])->default('occasion');
            $table->string('wilaya');
            $table->string('city')->nullable();
            $table->text('description')->nullable();
            $table->string('color')->nullable();
            $table->unsignedInteger('doors')->nullable();
            // pending: waiting admin review | approved: visible publicly | rejected | sold
            $table->enum('status', ['pending', 'approved', 'rejected', 'sold'])->default('pending');
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();

            $table->index(['status', 'brand_id', 'wilaya']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
