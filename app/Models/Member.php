<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Member extends Model
{
    use HasFactory;

    protected $table = 'members';

    protected $fillable = [
        'full_name',
        'plate_number',
        'clean_plate',
        'vehicle_type',
        'contact_number',
        'email',
        'start_date',
        'end_date',
        'duration_months',
        'price',
        'payment_status',
        'payment_reference',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'duration_months' => 'integer',
        'price' => 'decimal:2',
    ];

    /**
     * Normalize plate string by uppercase and removing non-alphanumeric chars.
     */
    public static function normalizePlate(?string $plate): string
    {
        if (!$plate) {
            return '';
        }
        return preg_replace('/[^A-Z0-9]/', '', strtoupper($plate));
    }

    /**
     * Boot model to automatically keep clean_plate in sync.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($member) {
            $member->clean_plate = self::normalizePlate($member->plate_number);
        });
    }

    /* ─── RELATIONSHIPS ─── */

    public function payments()
    {
        return $this->hasMany(MemberPayment::class, 'member_id')->orderBy('created_at', 'desc');
    }

    public function audits()
    {
        return $this->hasMany(MembershipAudit::class, 'member_id')->orderBy('created_at', 'desc');
    }

    public function parkingEntries()
    {
        return $this->hasMany(PlateEntry::class, 'member_id')->orderBy('entry_time', 'desc');
    }

    /* ─── STATUS & VALIDITY HELPERS ─── */

    /**
     * Check if membership is currently active and paid.
     */
    public function isCurrentlyActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->payment_status !== 'paid') {
            return false;
        }

        $today = Carbon::today();
        return $this->start_date <= $today && $this->end_date >= $today;
    }

    /**
     * Check if membership is expiring soon.
     */
    public function isExpiringSoon(int $days = 7): bool
    {
        if (!$this->isCurrentlyActive()) {
            return false;
        }

        $today = Carbon::today();
        $threshold = $today->copy()->addDays($days);
        return $this->end_date >= $today && $this->end_date <= $threshold;
    }

    /**
     * Check if membership is expired.
     */
    public function isExpired(): bool
    {
        $today = Carbon::today();
        return $this->end_date < $today;
    }

    /**
     * Computed status attribute for easy UI badge rendering.
     */
    public function getComputedStatusAttribute(): string
    {
        if ($this->status === 'suspended') {
            return 'suspended';
        }

        if ($this->status === 'cancelled') {
            return 'cancelled';
        }

        if ($this->payment_status === 'unpaid') {
            return 'unpaid';
        }

        $today = Carbon::today();
        if ($this->end_date < $today) {
            return 'expired';
        }

        if ($this->end_date <= $today->copy()->addDays(7)) {
            return 'expiring_soon';
        }

        return 'active';
    }

    public function getComputedStatusLabelAttribute(): string
    {
        switch ($this->computed_status) {
            case 'active':
                return 'Active';
            case 'expiring_soon':
                $days = Carbon::today()->diffInDays($this->end_date, false);
                return "Expiring Soon ({$days}d left)";
            case 'expired':
                return 'Expired';
            case 'unpaid':
                return 'Unpaid';
            case 'suspended':
                return 'Suspended';
            case 'cancelled':
                return 'Cancelled';
            default:
                return ucfirst($this->computed_status);
        }
    }

    public function getBadgeClassAttribute(): string
    {
        switch ($this->computed_status) {
            case 'active':
                return 'member-badge-active';
            case 'expiring_soon':
                return 'member-badge-warning';
            case 'expired':
                return 'member-badge-expired';
            case 'unpaid':
                return 'member-badge-unpaid';
            case 'suspended':
            case 'cancelled':
                return 'member-badge-suspended';
            default:
                return 'member-badge-neutral';
        }
    }

    /* ─── SCOPES ─── */

    public function scopeActive($query)
    {
        $today = Carbon::today();
        return $query->where('status', 'active')
            ->where('payment_status', 'paid')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today);
    }

    public function scopeExpiringSoon($query, int $days = 7)
    {
        $today = Carbon::today();
        $threshold = $today->copy()->addDays($days);
        return $query->where('status', 'active')
            ->where('payment_status', 'paid')
            ->whereDate('end_date', '>=', $today)
            ->whereDate('end_date', '<=', $threshold);
    }

    public function scopeExpired($query)
    {
        $today = Carbon::today();
        return $query->where(function ($q) use ($today) {
            $q->whereDate('end_date', '<', $today);
        });
    }

    public function scopeUnpaid($query)
    {
        return $query->where('payment_status', 'unpaid');
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    /* ─── OCR LOOKUP ─── */

    /**
     * Find active member by recognized plate text.
     */
    public static function findActiveMemberByPlate(?string $rawPlate): ?self
    {
        $clean = self::normalizePlate($rawPlate);
        if (strlen($clean) < 3) {
            return null;
        }

        $today = Carbon::today();

        // 1. Exact match on clean plate
        $member = self::where('clean_plate', $clean)
            ->where('status', 'active')
            ->where('payment_status', 'paid')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->first();

        if ($member) {
            return $member;
        }

        // 2. Tolerance match (e.g. OCR char confusions O vs 0, I vs 1)
        $candidates = self::where('status', 'active')
            ->where('payment_status', 'paid')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->get();

        foreach ($candidates as $cand) {
            if (levenshtein($clean, $cand->clean_plate) <= 1 && strlen($clean) >= 5) {
                return $cand;
            }
        }

        return null;
    }

    /**
     * Find ANY member record (even expired/unpaid) for a plate to alert operator.
     */
    public static function findMemberRecordByPlate(?string $rawPlate): ?self
    {
        $clean = self::normalizePlate($rawPlate);
        if (strlen($clean) < 3) {
            return null;
        }

        return self::where('clean_plate', $clean)->latest('end_date')->first();
    }
}

