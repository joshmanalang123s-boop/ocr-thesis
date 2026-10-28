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
        // 1. Membership Pricing & Configuration Settings
        if (!Schema::hasTable('membership_settings')) {
            Schema::create('membership_settings', function (Blueprint $table) {
                $table->id();
                $table->decimal('default_monthly_price', 8, 2)->default(2000.00)->comment('Default 1-month membership fee');
                $table->decimal('price_3_months', 8, 2)->default(6000.00)->comment('Default 3-month membership fee');
                $table->decimal('price_6_months', 8, 2)->default(12000.00)->comment('Default 6-month membership fee');
                $table->decimal('price_12_months', 8, 2)->default(24000.00)->comment('Default 12-month membership fee');
                $table->integer('expiration_warning_days')->default(7)->comment('Days before expiration to show warning');
                $table->boolean('allow_overnight')->default(true);
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });

            // Seed default pricing row (₱2,000 / month)
            DB::table('membership_settings')->insert([
                'default_monthly_price' => 2000.00,
                'price_3_months' => 6000.00,
                'price_6_months' => 12000.00,
                'price_12_months' => 24000.00,
                'expiration_warning_days' => 7,
                'allow_overnight' => true,
                'notes' => 'Default monthly membership pricing: ₱2,000 for one month',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Members Table
        if (!Schema::hasTable('members')) {
            Schema::create('members', function (Blueprint $table) {
                $table->id();
                $table->string('full_name', 100);
                $table->string('plate_number', 20)->index();
                $table->string('clean_plate', 20)->index()->comment('Normalized uppercase alphanumeric plate for OCR lookup');
                $table->enum('vehicle_type', ['car', 'motorcycle', 'truck', 'other'])->default('car');
                $table->string('contact_number', 30)->nullable();
                $table->string('email', 100)->nullable();
                $table->date('start_date');
                $table->date('end_date')->index();
                $table->integer('duration_months')->default(1);
                $table->decimal('price', 8, 2)->default(2000.00);
                $table->enum('payment_status', ['paid', 'unpaid'])->default('unpaid')->index();
                $table->string('payment_reference', 100)->nullable();
                $table->enum('status', ['active', 'suspended', 'cancelled'])->default('active')->index();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        // 3. Member Payments Table (Separate from ordinary parking fees)
        if (!Schema::hasTable('member_payments')) {
            Schema::create('member_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('member_id')->index();
                $table->enum('payment_type', ['initial', 'renewal', 'adjustment'])->default('initial');
                $table->decimal('amount', 8, 2);
                $table->integer('duration_months')->default(1);
                $table->date('start_date');
                $table->date('end_date');
                $table->enum('payment_status', ['paid', 'unpaid', 'refunded'])->default('paid')->index();
                $table->string('payment_method', 50)->default('cash');
                $table->string('payment_reference', 100)->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->string('received_by', 100)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            });
        }

        // 4. Membership Audit Logs Table
        if (!Schema::hasTable('membership_audits')) {
            Schema::create('membership_audits', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('member_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('user_name', 100)->default('System Administrator');
                $table->string('action', 50); // REGISTER, RENEW, CONFIRM_PAYMENT, UPDATE_PRICE, DEACTIVATE, etc.
                $table->json('details')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // 5. Update plate_entries to link with membership
        Schema::table('plate_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('plate_entries', 'is_member')) {
                $table->boolean('is_member')->default(false)->after('rate_id')->index();
            }
            if (!Schema::hasColumn('plate_entries', 'member_id')) {
                $table->unsignedBigInteger('member_id')->nullable()->after('is_member')->index();
            }
            if (!Schema::hasColumn('plate_entries', 'covered_by_membership')) {
                $table->boolean('covered_by_membership')->default(false)->after('member_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plate_entries', function (Blueprint $table) {
            if (Schema::hasColumn('plate_entries', 'covered_by_membership')) {
                $table->dropColumn('covered_by_membership');
            }
            if (Schema::hasColumn('plate_entries', 'member_id')) {
                $table->dropColumn('member_id');
            }
            if (Schema::hasColumn('plate_entries', 'is_member')) {
                $table->dropColumn('is_member');
            }
        });

        Schema::dropIfExists('membership_audits');
        Schema::dropIfExists('member_payments');
        Schema::dropIfExists('members');
        Schema::dropIfExists('membership_settings');
    }
};

