<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PlateEntrySeeder extends Seeder
{
    /**
     * Seed the plate_entries table with realistic sample data.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $entries = [
            // Currently parked vehicles (status = entered)
            [
                'plate_number' => 'ABC-1234',
                'entry_time' => $now->copy()->subHours(2)->subMinutes(15),
                'exit_time' => null,
                'entry_confidence' => 96.50,
                'exit_confidence' => null,
                'duration_minutes' => null,
                'vehicle_type' => 'car',
                'gate_entry' => 'Gate 1',
                'gate_exit' => null,
                'entry_image_path' => null,
                'exit_image_path' => null,
                'parking_fee' => null,
                'payment_status' => 'unpaid',
                'status' => 'entered',
                'remarks' => null,
                'created_at' => $now->copy()->subHours(2)->subMinutes(15),
                'updated_at' => $now->copy()->subHours(2)->subMinutes(15),
            ],
            [
                'plate_number' => 'XYZ-5678',
                'entry_time' => $now->copy()->subHours(1)->subMinutes(30),
                'exit_time' => null,
                'entry_confidence' => 93.20,
                'exit_confidence' => null,
                'duration_minutes' => null,
                'vehicle_type' => 'motorcycle',
                'gate_entry' => 'Gate 1',
                'gate_exit' => null,
                'entry_image_path' => null,
                'exit_image_path' => null,
                'parking_fee' => null,
                'payment_status' => 'unpaid',
                'status' => 'entered',
                'remarks' => null,
                'created_at' => $now->copy()->subHours(1)->subMinutes(30),
                'updated_at' => $now->copy()->subHours(1)->subMinutes(30),
            ],
            [
                'plate_number' => 'PQR-9012',
                'entry_time' => $now->copy()->subMinutes(45),
                'exit_time' => null,
                'entry_confidence' => 98.10,
                'exit_confidence' => null,
                'duration_minutes' => null,
                'vehicle_type' => 'car',
                'gate_entry' => 'Gate 1',
                'gate_exit' => null,
                'entry_image_path' => null,
                'exit_image_path' => null,
                'parking_fee' => null,
                'payment_status' => 'unpaid',
                'status' => 'entered',
                'remarks' => null,
                'created_at' => $now->copy()->subMinutes(45),
                'updated_at' => $now->copy()->subMinutes(45),
            ],

            // Exited vehicles (status = exited, with complete data)
            [
                'plate_number' => 'LMN-3456',
                'entry_time' => $now->copy()->subHours(5),
                'exit_time' => $now->copy()->subHours(2),
                'entry_confidence' => 94.80,
                'exit_confidence' => 91.50,
                'duration_minutes' => 180,
                'vehicle_type' => 'car',
                'gate_entry' => 'Gate 1',
                'gate_exit' => 'Gate 2',
                'entry_image_path' => null,
                'exit_image_path' => null,
                'parking_fee' => 5.00,
                'payment_status' => 'paid',
                'status' => 'exited',
                'remarks' => null,
                'created_at' => $now->copy()->subHours(5),
                'updated_at' => $now->copy()->subHours(2),
            ],
            [
                'plate_number' => 'UVW-7890',
                'entry_time' => $now->copy()->subHours(8),
                'exit_time' => $now->copy()->subHours(3),
                'entry_confidence' => 97.30,
                'exit_confidence' => 95.00,
                'duration_minutes' => 300,
                'vehicle_type' => 'van',
                'gate_entry' => 'Gate 1',
                'gate_exit' => 'Gate 2',
                'entry_image_path' => null,
                'exit_image_path' => null,
                'parking_fee' => 9.00,
                'payment_status' => 'paid',
                'status' => 'exited',
                'remarks' => null,
                'created_at' => $now->copy()->subHours(8),
                'updated_at' => $now->copy()->subHours(3),
            ],
            [
                'plate_number' => 'DEF-2468',
                'entry_time' => $now->copy()->subHours(4)->subMinutes(20),
                'exit_time' => $now->copy()->subHours(1)->subMinutes(10),
                'entry_confidence' => 89.60,
                'exit_confidence' => 92.40,
                'duration_minutes' => 190,
                'vehicle_type' => 'motorcycle',
                'gate_entry' => 'Gate 1',
                'gate_exit' => 'Gate 2',
                'entry_image_path' => null,
                'exit_image_path' => null,
                'parking_fee' => 5.00,
                'payment_status' => 'paid',
                'status' => 'exited',
                'remarks' => null,
                'created_at' => $now->copy()->subHours(4)->subMinutes(20),
                'updated_at' => $now->copy()->subHours(1)->subMinutes(10),
            ],
            [
                'plate_number' => 'GHI-1357',
                'entry_time' => $now->copy()->subDay()->subHours(3),
                'exit_time' => $now->copy()->subDay()->subHours(1),
                'entry_confidence' => 99.10,
                'exit_confidence' => 97.80,
                'duration_minutes' => 120,
                'vehicle_type' => 'truck',
                'gate_entry' => 'Gate 1',
                'gate_exit' => 'Gate 2',
                'entry_image_path' => null,
                'exit_image_path' => null,
                'parking_fee' => 5.00,
                'payment_status' => 'paid',
                'status' => 'exited',
                'remarks' => 'Large delivery truck — oversized bay assigned',
                'created_at' => $now->copy()->subDay()->subHours(3),
                'updated_at' => $now->copy()->subDay()->subHours(1),
            ],
        ];

        DB::table('plate_entries')->insert($entries);
    }
}
