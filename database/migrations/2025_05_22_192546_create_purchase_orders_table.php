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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique(); // Auto generate
            $table->foreignId('distributor_id')->constrained('distributor')->onDelete('cascade');
            $table->date('po_date');
            $table->date('due_date')->nullable(); // Jatuh tempo
            $table->enum('payment_method', ['cash', 'transfer', 'credit'])->default('transfer');
            $table->string('top')->nullable(); // Misalnya: 30 hari, 3x cicil
            $table->text('notes')->nullable();

            $table->decimal('subtotal', 16, 2)->default(0);
            $table->decimal('discount', 16, 2)->default(0);
            $table->decimal('tax', 16, 2)->default(0);
            $table->decimal('total', 16, 2)->default(0);

            $table->enum('status', ['draft', 'confirmed', 'full received', 'cancelled', 'partial'])->default('draft');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
