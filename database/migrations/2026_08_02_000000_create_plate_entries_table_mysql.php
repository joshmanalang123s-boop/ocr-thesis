<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plate_entries', function (Blueprint $table) {
            $table->id();

            // Core plate identification
            $table->string('plate_number', 20)->index();

            // Entry & Exit timestamps (the capstone core requirement)
            $table->timestamp('entry_time')->nullable()->comment('When the vehicle entered the facility');
            $table->timestamp('exit_time')->nullable()->comment('When the vehicle exited the facility');

            // OCR confidence scores (separate for entry & exit scans)
            $table->decimal('entry_confidence', 5, 2)->nullable()->comment('OCR confidence at entry gate (0.00-100.00)');
            $table->decimal('exit_confidence', 5, 2)->nullable()->comment('OCR confidence at exit gate (0.00-100.00)');

            // Computed parking duration
            $table->integer('duration_minutes')->nullable()->comment('Total parking duration in minutes, computed on exit');

            // Vehicle classification
            $table->enum('vehicle_type', ['car', 'motorcycle', 'truck', 'van', 'unknown'])->default('car');

            // Gate tracking
            $table->string('gate_entry', 50)->default('Gate 1')->comment('Which gate the vehicle entered from');
            $table->string('gate_exit', 50)->nullable()->comment('Which gate the vehicle exited from');

            // Image evidence (proof for capstone)
            $table->string('entry_image_path', 255)->nullable()->comment('Path to the captured entry image');
            $table->string('exit_image_path', 255)->nullable()->comment('Path to the captured exit image');

            // Parking fee & payment
            $table->decimal('parking_fee', 8, 2)->nullable()->comment('Computed parking fee amount');
            $table->enum('payment_status', ['unpaid', 'paid', 'waived'])->default('unpaid');

            // Vehicle status
            $table->enum('status', ['entered', 'exited'])->default('entered');

            // Admin remarks
            $table->text('remarks')->nullable()->comment('Admin notes, flagged plates, special cases');

            // Laravel auto-timestamps
            $table->timestamps();

            // Indexes for fast dashboard queries
            $table->index('entry_time');
            $table->index('exit_time');
            $table->index('status');
            $table->index('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('plate_entries');
    }
};
