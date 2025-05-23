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
        Schema::table('purchase_return_items', function (Blueprint $table) {
            // Drop FK lama jika ada
            $table->dropForeign(['purchase_order_item_id']);

            // Tambahkan FK baru yang benar
            $table->foreign('purchase_order_item_id')
                  ->references('id')
                  ->on('purchase_order_items')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->dropForeign(['purchase_order_item_id']);

            // (Opsional) Kembalikan FK lama jika perlu
            $table->foreign('purchase_order_item_id')
                  ->references('id')
                  ->on('purchase_orders');
        });
    }
};
