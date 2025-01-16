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
        Schema::create('rnd_master_rstgrease', function (Blueprint $table) {
            $table->id();
            $table->string('batch_code');
            $table->string('product_name');
            $table->date('expected_start_date');
            $table->date('expected_end_date');
            $table->string('created_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rnd_master_rstgrease');
    }
};