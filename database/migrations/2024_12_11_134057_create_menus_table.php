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
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Nama menu
            $table->string('icon')->nullable(); // Ikon menu
            $table->string('url')->nullable(); // URL atau route
            $table->integer('parent_id')->nullable(); // Untuk submenu
            $table->boolean('is_active')->default(true); // Status aktif
            $table->integer('order')->default(0); // Urutan menu
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
