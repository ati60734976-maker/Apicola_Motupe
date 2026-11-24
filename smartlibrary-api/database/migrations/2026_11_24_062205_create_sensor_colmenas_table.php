<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sensor_colmenas', function (Blueprint $table) {
            $table->id('id_sensor'); // PK personalizada INT AUTO_INCREMENT
            $table->string('apiario', 100);
            $table->decimal('temperatura', 5, 2); // Soporta valores decimales como 32.0, 36.5, etc.
            $table->decimal('humedad', 5, 2);     // Soporta porcentajes con decimales
            $table->string('mensaje_alerta', 100);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensor_colmenas');
    }
};