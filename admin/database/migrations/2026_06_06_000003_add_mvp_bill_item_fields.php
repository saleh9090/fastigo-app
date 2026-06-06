<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'type')) {
            Schema::table('products', function (Blueprint $table) {
                $table->enum('type', ['service', 'product'])->default('service')->after('name');
            });
        }

        if (! Schema::hasColumn('bills', 'payment_method')) {
            Schema::table('bills', function (Blueprint $table) {
                $table->enum('payment_method', ['cash', 'card', 'bank_transfer', 'mixed'])->default('cash')->after('payment_status');
            });
        }

        if (! Schema::hasColumn('bill_items', 'item_name')) {
            Schema::table('bill_items', function (Blueprint $table) {
                $table->string('item_name')->nullable()->after('product_id');
            });
        }

        if (! Schema::hasColumn('bill_items', 'item_type')) {
            Schema::table('bill_items', function (Blueprint $table) {
                $table->enum('item_type', ['service', 'product'])->default('service')->after('item_name');
            });
        }
    }

    public function down(): void
    {
        Schema::table('bill_items', function (Blueprint $table) {
            $table->dropColumn(['item_name', 'item_type']);
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
