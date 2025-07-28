<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('city_distribuidor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->foreignId('distribuidor_id')->constrained('distribuidores')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['city_id', 'distribuidor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('city_distribuidor');
    }
};
