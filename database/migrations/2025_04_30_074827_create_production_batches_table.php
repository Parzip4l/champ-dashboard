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
        Schema::create('production_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_code')->unique();
            $table->string('produk');
            $table->string('tangki_masak');
            $table->enum('status', ['Open', 'Closed']);
            $table->enum('hasil_status', ['ok', 'bs']);
            $table->string('tangki_olah')->nullable();
            $table->string('bahan_bakar_masak')->nullable();
            $table->decimal('qty_bahan_bakar_masak', 10, 2)->nullable();
            $table->string('bahan_bakar_olah')->nullable();
            $table->decimal('qty_bahan_bakar_olah', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_batches');
    }
};
