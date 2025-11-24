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
        Schema::create('providers', function (Blueprint $table) {
            $table->id();

            // Llave foránea que referencia a la tabla 'users' (El usuario que es el dueño del proveedor)
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Datos del perfil del proveedor (REQ-NF-006)
            $table->string('name');
            $table->string('contact')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('location');
            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
