<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlateEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
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
        'is_free_grace',
        'applied_rate_snapshot',
        'rate_id',
        'payment_status',
        'status',
        'remarks',
        'is_manually_corrected',
        'corrected_by',
        'corrected_at',
    ];

    /**
     * Relationship: The parking session this entry belongs to.
     */
    public function session()
    {
        return $this->belongsTo(ParkingSession::class, 'session_id');
    }

    /**
     * Relationship: The parking rate applied to this entry.
     */
    public function rate()
    {
        return $this->belongsTo(ParkingRate::class, 'rate_id');
    }

    /**
     * Relationship: Audit trails of manual corrections on this entry.
     */
    public function audits()
    {
        return $this->hasMany(PlateEntryAudit::class, 'plate_entry_id')->orderBy('created_at', 'desc');
    }

    /**
     * Relationship: User who made manual correction.
     */
    public function corrector()
    {
        return $this->belongsTo(User::class, 'corrected_by');
    }

    protected $casts = [
        'entry_time' => 'datetime',
        'exit_time' => 'datetime',
        'entry_confidence' => 'decimal:2',
        'exit_confidence' => 'decimal:2',
        'parking_fee' => 'decimal:2',
        'is_free_grace' => 'boolean',
        'applied_rate_snapshot' => 'array',
        'is_manually_corrected' => 'boolean',
        'corrected_at' => 'datetime',
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

    /**
     * Check if recognition confidence is low (< 70%).
     */
    public function isLowConfidence(): bool
    {
        $conf = $this->average_confidence;
        return $conf !== null && $conf < 70.0;
    }

    /**
     * Check if this transaction qualified for 15-minute free grace period.
     */
    public function isFreeGrace(): bool
    {
        return (bool)$this->is_free_grace || ($this->duration_minutes !== null && $this->duration_minutes <= 15 && $this->hasExited());
    }

    /**
     * Get formatted parking fee display.
     */
    public function getFeeFormattedAttribute(): string
    {
        if ($this->parking_fee === null) {
            return '—';
        }
        if ((float)$this->parking_fee === 0.0 || $this->isFreeGrace()) {
            return '₱0.00 (FREE)';
        }
        return '₱' . number_format((float)$this->parking_fee, 2);
    }
}
