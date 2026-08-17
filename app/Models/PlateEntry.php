<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlateEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'plate_number',
        'entry_time',
        'exit_time',
        'entry_confidence',
        'exit_confidence',
        'duration_minutes',
        'vehicle_type',
        'gate_entry',
        'gate_exit',
        'entry_image_path',
        'exit_image_path',
        'parking_fee',
        'payment_status',
        'status',
        'remarks',
    ];

    protected $casts = [
        'entry_time' => 'datetime',
        'exit_time' => 'datetime',
        'entry_confidence' => 'decimal:2',
        'exit_confidence' => 'decimal:2',
        'parking_fee' => 'decimal:2',
        'duration_minutes' => 'integer',
    ];

    /**
     * Check if the vehicle is currently parked.
     */
    public function isParked(): bool
    {
        return $this->status === 'entered';
    }

    /**
     * Check if the vehicle has exited.
     */
    public function hasExited(): bool
    {
        return $this->status === 'exited';
    }

    /**
     * Calculate and set the duration on exit.
     */
    public function calculateDuration(): ?int
    {
        if ($this->entry_time && $this->exit_time) {
            $this->duration_minutes = $this->entry_time->diffInMinutes($this->exit_time);
            return $this->duration_minutes;
        }
        return null;
    }

    /**
     * Get a human-readable duration string.
     */
    public function getDurationFormattedAttribute(): string
    {
        if (!$this->duration_minutes) {
            return 'N/A';
        }

        $hours = intdiv($this->duration_minutes, 60);
        $minutes = $this->duration_minutes % 60;

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }
        return "{$minutes}m";
    }

    /**
     * Get the average confidence between entry and exit.
     */
    public function getAverageConfidenceAttribute(): ?float
    {
        if ($this->entry_confidence && $this->exit_confidence) {
            return round(($this->entry_confidence + $this->exit_confidence) / 2, 2);
        }
        return $this->entry_confidence ?? $this->exit_confidence ?? null;
    }
}
