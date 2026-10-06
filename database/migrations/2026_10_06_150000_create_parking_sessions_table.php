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
        if (!Schema::hasTable('parking_sessions')) {
            Schema::create('parking_sessions', function (Blueprint $table) {
                $table->id();
                $table->string('session_code', 50)->unique();
                $table->timestamp('started_at')->useCurrent();
                $table->timestamp('ended_at')->nullable();
                $table->enum('status', ['active', 'ended'])->default('active')->index();
                $table->decimal('total_revenue', 10, 2)->default(0.00);
                $table->integer('total_vehicles')->default(0);
                $table->integer('currently_parked')->default(0);
                $table->integer('completed_sessions')->default(0);
                $table->integer('total_duration_minutes')->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('plate_entries', 'session_id')) {
            Schema::table('plate_entries', function (Blueprint $table) {
                $table->unsignedBigInteger('session_id')->nullable()->after('id')->index();
            });
        }

        // Initialize active session if none exists
        $existingSession = DB::table('parking_sessions')->where('status', 'active')->first();
        if (!$existingSession) {
            $firstEntry = DB::table('plate_entries')->orderBy('entry_time', 'ASC')->first();
            $startTime = ($firstEntry && $firstEntry->entry_time) ? $firstEntry->entry_time : now();

            $initialSessionId = DB::table('parking_sessions')->insertGetId([
                'session_code' => 'SES-' . date('Ymd') . '-001',
                'started_at' => $startTime,
                'status' => 'active',
                'total_revenue' => 0.00,
                'total_vehicles' => 0,
                'currently_parked' => 0,
                'completed_sessions' => 0,
                'total_duration_minutes' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('plate_entries')->whereNull('session_id')->update([
                'session_id' => $initialSessionId,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('plate_entries', 'session_id')) {
            Schema::table('plate_entries', function (Blueprint $table) {
                $table->dropColumn('session_id');
            });
        }

        Schema::dropIfExists('parking_sessions');
    }
};
