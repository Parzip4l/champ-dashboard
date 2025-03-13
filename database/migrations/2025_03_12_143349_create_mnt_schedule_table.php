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
        Schema::create('mnt_schedule', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('mnt_item')->onDelete('cascade');
            $table->enum('schedule', ['daily', 'weekly', 'monthly', 'custom']);
            $table->date('next_maintenance');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mnt_schedule');
    }
};
