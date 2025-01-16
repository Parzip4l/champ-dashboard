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
        Schema::create('rnd_details_rstgrease', function (Blueprint $table) {
            $table->id();
            $table->string('master_id');
            $table->string('trial_method');
            $table->string('trial_result');
            $table->string('issue')->nullable();
            $table->string('improvement_ideas')->nullable();
            $table->date('improvement_schedule')->nullable(); 
            $table->string('competitor_comparison')->nullable();
            $table->string('status');
            $table->string('created_by');
            $table->timestamps();
        });

        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rnd_details_rstgrease');
    }
};
