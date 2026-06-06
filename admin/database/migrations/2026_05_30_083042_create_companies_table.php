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
        Schema::create('companies', function (Blueprint $table) {
           $table->id();

$table->string('name');

$table->string('commercial_registration')->nullable();

$table->string('contact_person');

$table->string('phone');

$table->string('email')->nullable();

$table->text('address')->nullable();

$table->date('subscription_start')->nullable();

$table->date('subscription_end')->nullable();

$table->enum('status', [
    'active',
    'suspended'
])->default('active');

$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
