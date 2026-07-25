<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // the client asking to buy
            $table->string('phone')->nullable(); // contact number for this specific request
            $table->text('message')->nullable(); // ex: "3ndi interet, khasni description o offre"
            // pending: nouvelle demande | contacted: l'admin 3iyet/raslou | confirmed: deal conclu | cancelled
            $table->enum('status', ['pending', 'contacted', 'confirmed', 'cancelled'])->default('pending');
            $table->timestamps();

            $table->index(['status', 'car_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
    }
};
