<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 50)->default('branch_employee')->change();
        });

        DB::table('users')->where('role', 'admin')->update(['role' => 'platform_admin']);
        DB::table('users')->whereIn('role', ['owner', 'manager'])->update(['role' => 'company_manager']);
        DB::table('users')->where('role', 'employee')->update(['role' => 'branch_employee']);
        DB::table('users')->where('role', 'customer')->update(['role' => 'public_customer']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', [
                'admin',
                'owner',
                'manager',
                'employee',
            ])->default('employee')->change();
        });
    }
};
