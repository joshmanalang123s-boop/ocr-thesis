<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipAudit extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'membership_audits';

    protected $fillable = [
        'member_id',
        'user_id',
        'user_name',
        'action',
        'details',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function log(string $action, ?int $memberId = null, array $details = []): self
    {
        return self::create([
            'member_id' => $memberId,
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name ?? 'System Administrator',
            'action' => $action,
            'details' => $details,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}

