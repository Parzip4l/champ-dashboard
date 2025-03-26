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
        Schema::create('mnt_parts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id'); // Tipe harus sama dengan id di items
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        
            $table->foreign('item_id')->references('id')->on('mnt_item')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mnt_parts');
    }
};
