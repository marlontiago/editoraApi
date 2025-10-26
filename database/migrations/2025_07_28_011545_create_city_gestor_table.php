<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('city_gestor')) {
            Schema::create('city_gestor', function (Blueprint $table) {
                $table->id();
                $table->foreignId('city_id')->constrained()->cascadeOnDelete();
                $table->foreignId('gestor_id')->constrained('gestores')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['city_id', 'gestor_id'], 'city_gestor_unique');

                $table->index('city_id');
                $table->index('gestor_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('city_gestor');
    }
};
