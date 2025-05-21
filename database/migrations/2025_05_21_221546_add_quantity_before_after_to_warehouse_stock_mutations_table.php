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
        Schema::table('warehouse_stocks_mutations', function (Blueprint $table) {
            $table->decimal('quantity_before', 15, 2)->nullable();
            $table->decimal('quantity_after', 15, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouse_stocks_mutations', function (Blueprint $table) {
            $table->dropColumn(['quantity_before', 'quantity_after']);
        });
    }
};
