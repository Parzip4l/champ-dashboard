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
        Schema::create('warehouse_stocks_mutations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_location_id');
            $table->unsignedBigInteger('warehouse_item_id');
            $table->enum('type', ['in', 'out']);
            $table->float('quantity');
            $table->text('note')->nullable();
            $table->string('source')->nullable();
            $table->timestamps();

            $table->foreign('warehouse_location_id')->references('id')->on('warehouse_locations')->onDelete('cascade');
            $table->foreign('warehouse_item_id')->references('id')->on('warehouse_items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_stocks_mutations');
    }
};
