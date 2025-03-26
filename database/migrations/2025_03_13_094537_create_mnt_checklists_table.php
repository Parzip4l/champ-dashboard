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
        Schema::create('mnt_checklists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('part_id');
            $table->string('checklist_item');
            $table->enum('status', ['good', 'need repair', 'bad'])->default('good');
            $table->text('keterangan');
            $table->string('maintained_by');
            $table->timestamps();

            $table->foreign('part_id')->references('id')->on('mnt_parts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mnt_checklists');
    }
};
