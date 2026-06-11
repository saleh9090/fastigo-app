<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('company_subscriptions')) {
            Schema::create('company_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')
                    ->constrained()
                    ->cascadeOnDelete();
                $table->foreignId('subscription_package_id')
                    ->nullable()
                    ->constrained('subscription_packages')
                    ->nullOnDelete();
                $table->date('subscription_start')->nullable();
                $table->date('subscription_end')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('company_subscriptions', 'subscription_package_id')) {
            Schema::table('company_subscriptions', function (Blueprint $table) {
                $table->foreignId('subscription_package_id')
                    ->nullable()
                    ->after('company_id')
                    ->constrained('subscription_packages')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('company_subscriptions', 'subscription_start')) {
            Schema::table('company_subscriptions', function (Blueprint $table) {
                $table->date('subscription_start')->nullable()->after('subscription_package_id');
            });
        }

        if (! Schema::hasColumn('company_subscriptions', 'subscription_end')) {
            Schema::table('company_subscriptions', function (Blueprint $table) {
                $table->date('subscription_end')->nullable()->after('subscription_start');
            });
        }

        if (
            Schema::hasColumn('companies', 'subscription_package_id') ||
            Schema::hasColumn('companies', 'subscription_start') ||
            Schema::hasColumn('companies', 'subscription_end')
        ) {
            $hasLegacyPlanName = Schema::hasColumn('company_subscriptions', 'plan_name');
            $hasLegacyStartsAt = Schema::hasColumn('company_subscriptions', 'starts_at');
            $hasLegacyEndsAt = Schema::hasColumn('company_subscriptions', 'ends_at');

            DB::table('companies')
                ->whereNotNull('subscription_package_id')
                ->orWhereNotNull('subscription_start')
                ->orWhereNotNull('subscription_end')
                ->orderBy('id')
                ->get()
                ->each(function ($company) use ($hasLegacyPlanName, $hasLegacyStartsAt, $hasLegacyEndsAt): void {
                    $values = [
                        'subscription_package_id' => $company->subscription_package_id,
                        'subscription_start' => $company->subscription_start,
                        'subscription_end' => $company->subscription_end,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if ($hasLegacyPlanName) {
                        $values['plan_name'] = DB::table('subscription_packages')
                            ->where('id', $company->subscription_package_id)
                            ->value('name') ?? 'Subscription';
                    }

                    if ($hasLegacyStartsAt) {
                        $values['starts_at'] = $company->subscription_start ?? now()->toDateString();
                    }

                    if ($hasLegacyEndsAt) {
                        $values['ends_at'] = $company->subscription_end;
                    }

                    DB::table('company_subscriptions')->updateOrInsert(
                        ['company_id' => $company->id],
                        $values,
                    );
                });
        }

        if (Schema::hasColumn('companies', 'subscription_package_id')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropConstrainedForeignId('subscription_package_id');
            });
        }

        $oldDateColumns = array_filter(
            ['subscription_start', 'subscription_end'],
            fn (string $column): bool => Schema::hasColumn('companies', $column),
        );

        if ($oldDateColumns) {
            Schema::table('companies', function (Blueprint $table) use ($oldDateColumns) {
                $table->dropColumn($oldDateColumns);
            });
        }
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('subscription_package_id')
                ->nullable()
                ->after('id')
                ->constrained('subscription_packages')
                ->nullOnDelete();
            $table->date('subscription_start')->nullable()->after('address');
            $table->date('subscription_end')->nullable()->after('subscription_start');
        });

        DB::table('company_subscriptions')
            ->whereIn('id', function ($query): void {
                $query->selectRaw('MAX(id)')
                    ->from('company_subscriptions')
                    ->groupBy('company_id');
            })
            ->orderBy('company_id')
            ->get()
            ->each(function ($subscription): void {
                DB::table('companies')
                    ->where('id', $subscription->company_id)
                    ->update([
                        'subscription_package_id' => $subscription->subscription_package_id,
                        'subscription_start' => $subscription->subscription_start,
                        'subscription_end' => $subscription->subscription_end,
                    ]);
            });

        Schema::dropIfExists('company_subscriptions');
    }
};
