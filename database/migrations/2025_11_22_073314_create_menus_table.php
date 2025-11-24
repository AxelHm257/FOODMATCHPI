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
        Schema::create('menus', function (Blueprint $table) {
            $table->id();

            // Llave foránea que referencia al proveedor dueño del menú (REQ-FUN 008)
            $table->foreignId('provider_id')->constrained('providers')->onDelete('cascade');

            // Campos del menú
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 8, 2); // Precio con 8 dígitos en total, 2 decimales
            $table->string('image_url')->nullable();
            $table->boolean('is_available')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
