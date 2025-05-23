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
        Schema::table('distributor', function (Blueprint $table) {
            $table->string('category')->nullable()->after('address_details'); // barang / jasa
            $table->boolean('is_active')->default(true)->after('category');
            $table->text('notes')->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('distributor', function (Blueprint $table) {
            $table->dropColumn(['category', 'is_active', 'notes']);
        });
    }
};
