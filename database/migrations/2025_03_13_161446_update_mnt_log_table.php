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
        Schema::table('mnt_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('item_id')->after('maintenance_id');
            $table->unsignedBigInteger('part_id')->after('item_id');
            $table->enum('status', ['baik', 'perlu perbaikan'])->default('baik')->after('performed_at');
            $table->string('maintenance_by')->after('status');
            $table->text('notes')->nullable()->change(); // Mengizinkan notes kosong
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mnt_logs', function (Blueprint $table) {
            $table->dropColumn(['item_id', 'part_id', 'status', 'maintenance_by']);
            $table->text('notes')->nullable(false)->change(); // Mengembalikan notes seperti semula
        });
    }
};
