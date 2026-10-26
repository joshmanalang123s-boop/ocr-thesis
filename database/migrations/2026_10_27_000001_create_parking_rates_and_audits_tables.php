<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Parking Rates Table
        if (!Schema::hasTable('parking_rates')) {
            Schema::create('parking_rates', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->default('Standard Parking Rate');
                $table->integer('grace_period_minutes')->default(15)->comment('Free parking grace period in minutes');
                $table->decimal('base_rate', 8, 2)->default(50.00)->comment('Initial parking fee for base period');
                $table->integer('base_hours')->default(3)->comment('Number of initial hours covered by base fee');
                $table->decimal('hourly_rate', 8, 2)->default(20.00)->comment('Hourly fee for succeeding hours or fraction thereof');
                $table->decimal('motorcycle_base_rate', 8, 2)->default(30.00);
                $table->decimal('motorcycle_hourly_rate', 8, 2)->default(15.00);
                $table->decimal('truck_base_rate', 8, 2)->default(80.00);
                $table->decimal('truck_hourly_rate', 8, 2)->default(30.00);
                $table->decimal('lost_ticket_fee', 8, 2)->default(150.00);
                $table->decimal('overnight_fee', 8, 2)->default(200.00);
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });

            // Seed initial default rates
            DB::table('parking_rates')->insert([
                'name' => 'Standard Parking Rate',
                'grace_period_minutes' => 15,
                'base_rate' => 50.00,
                'base_hours' => 3,
                'hourly_rate' => 20.00,
                'motorcycle_base_rate' => 30.00,
                'motorcycle_hourly_rate' => 15.00,
                'truck_base_rate' => 80.00,
                'truck_hourly_rate' => 30.00,
                'lost_ticket_fee' => 150.00,
                'overnight_fee' => 200.00,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Parking Rate Audits Table
        if (!Schema::hasTable('parking_rate_audits')) {
            Schema::create('parking_rate_audits', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('user_name', 100)->default('System Administrator');
                $table->string('action', 50)->default('UPDATE_RATES');
                $table->json('previous_rates')->nullable();
                $table->json('new_rates');
                $table->text('reason')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // 3. Plate Entry Audits Table (Record manual corrections & overrides)
        if (!Schema::hasTable('plate_entry_audits')) {
            Schema::create('plate_entry_audits', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('plate_entry_id')->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('user_name', 100)->default('Attendant');
                $table->string('action', 50); // e.g. CORRECT_PLATE, OVERRIDE_DUPLICATE, MANUAL_EXIT
                $table->string('field_changed', 50)->nullable();
                $table->text('old_value')->nullable();
                $table->text('new_value')->nullable();
                $table->text('reason')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // 4. Update plate_entries table
        Schema::table('plate_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('plate_entries', 'is_free_grace')) {
                $table->boolean('is_free_grace')->default(false)->after('parking_fee')->index();
            }
            if (!Schema::hasColumn('plate_entries', 'applied_rate_snapshot')) {
                $table->json('applied_rate_snapshot')->nullable()->after('is_free_grace');
            }
            if (!Schema::hasColumn('plate_entries', 'rate_id')) {
                $table->unsignedBigInteger('rate_id')->nullable()->after('applied_rate_snapshot');
            }
            if (!Schema::hasColumn('plate_entries', 'is_manually_corrected')) {
                $table->boolean('is_manually_corrected')->default(false)->after('remarks');
            }
            if (!Schema::hasColumn('plate_entries', 'corrected_by')) {
                $table->unsignedBigInteger('corrected_by')->nullable()->after('is_manually_corrected');
            }
            if (!Schema::hasColumn('plate_entries', 'corrected_at')) {
                $table->timestamp('corrected_at')->nullable()->after('corrected_by');
            }
        });

        // 5. Update users table with role
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role', 30)->default('admin')->after('email');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plate_entries', function (Blueprint $table) {
            if (Schema::hasColumn('plate_entries', 'is_free_grace')) {
                $table->dropColumn('is_free_grace');
            }
            if (Schema::hasColumn('plate_entries', 'applied_rate_snapshot')) {
                $table->dropColumn('applied_rate_snapshot');
            }
            if (Schema::hasColumn('plate_entries', 'rate_id')) {
                $table->dropColumn('rate_id');
            }
            if (Schema::hasColumn('plate_entries', 'is_manually_corrected')) {
                $table->dropColumn('is_manually_corrected');
            }
            if (Schema::hasColumn('plate_entries', 'corrected_by')) {
                $table->dropColumn('corrected_by');
            }
            if (Schema::hasColumn('plate_entries', 'corrected_at')) {
                $table->dropColumn('corrected_at');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
        });

        Schema::dropIfExists('plate_entry_audits');
        Schema::dropIfExists('parking_rate_audits');
        Schema::dropIfExists('parking_rates');
    }
};
