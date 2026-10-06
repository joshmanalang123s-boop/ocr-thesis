<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ParkingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_code',
        'started_at',
        'ended_at',
        'status',
        'total_revenue',
        'total_vehicles',
        'currently_parked',
        'completed_sessions',
        'total_duration_minutes',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'total_revenue' => 'decimal:2',
        'total_vehicles' => 'integer',
        'currently_parked' => 'integer',
        'completed_sessions' => 'integer',
        'total_duration_minutes' => 'integer',
    ];

    /**
     * Relationship: Plate entries belonging to this session
     */
    public function entries()
    {
        return $this->hasMany(PlateEntry::class, 'session_id');
    }

    /**
     * Get the active session, or create a new one if none exists.
     */
    public static function getActive(): self
    {
        $session = self::where('status', 'active')->latest('started_at')->first();

        if (!$session) {
            $count = self::count() + 1;
            $code = 'SES-' . date('Ymd') . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
            $session = self::create([
                'session_code' => $code,
                'started_at' => now(),
                'status' => 'active',
                'total_revenue' => 0.00,
                'total_vehicles' => 0,
                'currently_parked' => 0,
                'completed_sessions' => 0,
                'total_duration_minutes' => 0,
            ]);
        }

        return $session;
    }

    /**
     * Calculate current live metrics of this session.
     */
    public function calculateMetrics(): array
    {
        $totalVehicles = $this->entries()->count();
        $currentlyParked = $this->entries()->where('status', 'entered')->count();
        $completedSessions = $this->entries()->where('status', 'exited')->count();
        $totalRevenue = (float)$this->entries()->where('status', 'exited')->sum('parking_fee');
        $totalDurationMinutes = (int)$this->entries()->where('status', 'exited')->sum('duration_minutes');

        $hours = intdiv($totalDurationMinutes, 60);
        $mins = $totalDurationMinutes % 60;
        $formattedDuration = $hours > 0 ? "{$hours}h {$mins}m" : ($mins > 0 ? "{$mins}m" : "0m");

        return [
            'total_vehicles' => $totalVehicles,
            'currently_parked' => $currentlyParked,
            'completed_sessions' => $completedSessions,
            'total_revenue' => $totalRevenue,
            'formatted_revenue' => '₱' . number_format($totalRevenue, 2),
            'total_duration_minutes' => $totalDurationMinutes,
            'formatted_duration' => $formattedDuration,
            'session_start_time' => $this->started_at ? $this->started_at->format('M d, Y • h:i:s A') : 'N/A',
            'session_end_time' => now()->format('M d, Y • h:i:s A'),
        ];
    }
}
