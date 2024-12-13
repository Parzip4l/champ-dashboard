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
    
        // Update order_items table
        Schema::table('order_items', function (Blueprint $table) {
            // Drop the old 'nama_produk' column if needed
            $table->dropColumn('nama_produk');
            
            // Add the new 'nama_produk' column with a foreign key relationship to 'produk' table
            $table->unsignedBigInteger('nama_produk'); // Assuming 'produk' table has 'id' column
            $table->foreign('nama_produk')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse changes for order_items table
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['nama_produk']);
            $table->dropColumn('nama_produk');
        });
    }
};
