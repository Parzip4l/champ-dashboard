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
        Schema::create('list_orders', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_terima_order')->nullable();
            $table->date('maks_kirim')->nullable();
            $table->string('customer')->nullable(); // Toko/Customer/Dropshipper
            $table->string('tujuan')->nullable();
            $table->boolean('ppn')->default(false); // Yes/No
            $table->string('no_so')->nullable();
            $table->string('expedisi')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('status')->nullable(); // Ontime/Delay
            $table->timestamps();
        });

        // Tabel untuk detail barang yang dikirimkan
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('list_order_id')->constrained('list_orders')->onDelete('cascade'); // Hubungan ke tabel list_orders
            $table->string('nama_produk');
            $table->integer('total_order');
            $table->integer('jumlah_kirim');
            $table->integer('sisa_belum_kirim')->nullable();
            $table->date('tanggal_kirim')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('list_orders');
    }
};
