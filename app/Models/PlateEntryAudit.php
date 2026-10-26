<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlateEntryAudit extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'plate_entry_id',
        'user_id',
        'user_name',
        'action',
        'field_changed',
        'old_value',
        'new_value',
        'reason',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function entry()
    {
        return $this->belongsTo(PlateEntry::class, 'plate_entry_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
