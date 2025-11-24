<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_provider', 32)->nullable()->after('status');
            $table->string('external_payment_id', 64)->nullable()->after('payment_provider');
            $table->string('payment_status', 20)->default('unpaid')->after('external_payment_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_provider', 'external_payment_id', 'payment_status']);
        });
    }
};

