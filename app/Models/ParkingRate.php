<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParkingRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'grace_period_minutes',
        'base_rate',
        'base_hours',
        'hourly_rate',
        'motorcycle_base_rate',
        'motorcycle_hourly_rate',
        'truck_base_rate',
        'truck_hourly_rate',
        'lost_ticket_fee',
        'overnight_fee',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'grace_period_minutes' => 'integer',
        'base_rate' => 'decimal:2',
        'base_hours' => 'integer',
        'hourly_rate' => 'decimal:2',
        'motorcycle_base_rate' => 'decimal:2',
        'motorcycle_hourly_rate' => 'decimal:2',
        'truck_base_rate' => 'decimal:2',
        'truck_hourly_rate' => 'decimal:2',
        'lost_ticket_fee' => 'decimal:2',
        'overnight_fee' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the active parking rate configuration, or fallback/create default if not present.
     */
    public static function getActiveRate(): self
    {
        $rate = self::where('is_active', true)->latest('updated_at')->first();

        if (!$rate) {
            $rate = self::create([
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
            ]);
        }

        return $rate;
    }

    /**
     * Calculate parking fee with mandatory 15-minute free grace period.
     *
     * Rules:
     * - Duration <= 15 minutes: Fee is ₱0.00 (Free). Exactly 15 minutes is Free.
     * - Duration > 15 minutes: Calculate according to configured rates (Base fee for initial hours + hourly fee for succeeding).
     *
     * @param int $durationMinutes Total parking duration in minutes
     * @param string $vehicleType Type of vehicle ('car', 'motorcycle', 'truck', 'van')
     * @return array Breakdown containing fee, is_free, rate_description, and snapshot
     */
    public function calculateFee(int $durationMinutes, string $vehicleType = 'car'): array
    {
        // Enforce mandatory minimum 15-minute grace period
        $graceMinutes = max(15, (int)($this->grace_period_minutes ?? 15));

        // Format duration readable
        $hoursPart = intdiv($durationMinutes, 60);
        $minsPart = $durationMinutes % 60;
        $formattedDuration = $hoursPart > 0 ? "{$hoursPart}h {$minsPart}m" : "{$minsPart}m";

        // Determine rate category based on vehicle type
        $vType = strtolower($vehicleType ?: 'car');
        $baseRate = (float)$this->base_rate;
        $baseHours = max(1, (int)$this->base_hours);
        $hourlyRate = (float)$this->hourly_rate;

        if ($vType === 'motorcycle') {
            $baseRate = (float)($this->motorcycle_base_rate ?? 30.00);
            $hourlyRate = (float)($this->motorcycle_hourly_rate ?? 15.00);
        } elseif ($vType === 'truck') {
            $baseRate = (float)($this->truck_base_rate ?? 80.00);
            $hourlyRate = (float)($this->truck_hourly_rate ?? 30.00);
        }

        // 1. MANDATORY 15-MINUTE FREE PARKING CHECK
        // Vehicles that exit within 15 minutes (or exactly at 15 minutes) are charged ₱0.00.
        if ($durationMinutes <= $graceMinutes) {
            return [
                'fee' => 0.00,
                'formatted_fee' => '₱0.00',
                'is_free' => true,
                'duration_minutes' => $durationMinutes,
                'formatted_duration' => $formattedDuration,
                'grace_period_applied' => true,
                'grace_period_minutes' => $graceMinutes,
                'billed_hours' => 0,
                'base_rate' => $baseRate,
                'base_hours' => $baseHours,
                'hourly_rate' => $hourlyRate,
                'rate_id' => $this->id,
                'rate_name' => $this->name,
                'rule_applied' => "Free Grace Period (≤ {$graceMinutes} mins) — ₱0.00",
                'snapshot' => [
                    'rate_id' => $this->id,
                    'rate_name' => $this->name,
                    'grace_period_minutes' => $graceMinutes,
                    'base_rate' => $baseRate,
                    'base_hours' => $baseHours,
                    'hourly_rate' => $hourlyRate,
                    'vehicle_type' => $vType,
                    'duration_minutes' => $durationMinutes,
                    'billed_hours' => 0,
                    'is_free' => true,
                    'calculated_fee' => 0.00,
                ],
            ];
        }

        // 2. PARKING DURATION > 15 MINUTES: Apply configured rates
        $billedHours = max(1, (int)ceil($durationMinutes / 60));

        if ($billedHours <= $baseHours) {
            $fee = $baseRate;
            $ruleApplied = "Base Rate: ₱" . number_format($baseRate, 2) . " (First {$baseHours}h)";
        } else {
            $succeedingHours = $billedHours - $baseHours;
            $succeedingAmount = $succeedingHours * $hourlyRate;
            $fee = $baseRate + $succeedingAmount;
            $ruleApplied = "Base ₱" . number_format($baseRate, 2) . " ({$baseHours}h) + {$succeedingHours}h × ₱" . number_format($hourlyRate, 2) . "/h";
        }

        return [
            'fee' => round($fee, 2),
            'formatted_fee' => '₱' . number_format($fee, 2),
            'is_free' => false,
            'duration_minutes' => $durationMinutes,
            'formatted_duration' => $formattedDuration,
            'grace_period_applied' => false,
            'grace_period_minutes' => $graceMinutes,
            'billed_hours' => $billedHours,
            'base_rate' => $baseRate,
            'base_hours' => $baseHours,
            'hourly_rate' => $hourlyRate,
            'rate_id' => $this->id,
            'rate_name' => $this->name,
            'rule_applied' => $ruleApplied,
            'snapshot' => [
                'rate_id' => $this->id,
                'rate_name' => $this->name,
                'grace_period_minutes' => $graceMinutes,
                'base_rate' => $baseRate,
                'base_hours' => $baseHours,
                'hourly_rate' => $hourlyRate,
                'vehicle_type' => $vType,
                'duration_minutes' => $durationMinutes,
                'billed_hours' => $billedHours,
                'is_free' => false,
                'calculated_fee' => round($fee, 2),
            ],
        ];
    }
}
