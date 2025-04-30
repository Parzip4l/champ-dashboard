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
        Schema::create('production_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_batch_id')->constrained()->onDelete('cascade');
            $table->enum('step', ['step1', 'step2']); // Tangki Masak atau Tangki Olah
            $table->string('kategori'); // oli, lemak, dll
            $table->string('tipe')->nullable(); // misal: Service, Minarex
            $table->string('jenis')->nullable(); // misal: Tembak, Pancing
            $table->decimal('qty', 10, 2);
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_materials');
    }
};
