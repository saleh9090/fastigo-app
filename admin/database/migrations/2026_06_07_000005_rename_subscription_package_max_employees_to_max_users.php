<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subscription_packages')) {
            return;
        }

        if (Schema::hasColumn('subscription_packages', 'max_employees') && ! Schema::hasColumn('subscription_packages', 'max_users')) {
            Schema::table('subscription_packages', function (Blueprint $table) {
                $table->renameColumn('max_employees', 'max_users');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('subscription_packages')) {
            return;
        }

        if (Schema::hasColumn('subscription_packages', 'max_users') && ! Schema::hasColumn('subscription_packages', 'max_employees')) {
            Schema::table('subscription_packages', function (Blueprint $table) {
                $table->renameColumn('max_users', 'max_employees');
            });
        }
    }
};
