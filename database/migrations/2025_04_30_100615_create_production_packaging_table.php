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
        Schema::create('production_packaging', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->foreignId('production_batch_id')->constrained('production_batches')->onDelete('cascade'); // Foreign key ke production_batches
            $table->string('packaging'); // Jenis packaging (drum, pail, pot)
            $table->string('size'); // Ukuran produk
            $table->integer('quantity'); // Kuantitas produk yang diproduksi
            $table->timestamps(); // Created at, updated at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_packaging');
    }
};
