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
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('branch_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('bill_number')->unique();
            $table->string('customer_phone');
            $table->decimal('total_amount', 10, 3)->default(0);
            $table->decimal('paid_amount', 10, 3)->default(0);
            $table->decimal('remaining_amount', 10, 3)->default(0);
            $table->enum('payment_status', [
                'unpaid',
                'partial',
                'paid',
            ])->default('unpaid');
            $table->enum('status', [
                'in_process',
                'ready',
                'delivered',
            ])->default('in_process');
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
