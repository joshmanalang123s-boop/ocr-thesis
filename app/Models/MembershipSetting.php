<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipSetting extends Model
{
    use HasFactory;

    protected $table = 'membership_settings';

    protected $fillable = [
        'default_monthly_price',
        'price_3_months',
        'price_6_months',
        'price_12_months',
        'expiration_warning_days',
        'allow_overnight',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'default_monthly_price' => 'decimal:2',
        'price_3_months' => 'decimal:2',
        'price_6_months' => 'decimal:2',
        'price_12_months' => 'decimal:2',
        'expiration_warning_days' => 'integer',
        'allow_overnight' => 'boolean',
    ];

    /**
     * Get the active settings record (singleton row).
     */
    public static function getSettings(): self
    {
        $settings = self::first();
        if (!$settings) {
            $settings = self::create([
                'default_monthly_price' => 2000.00,
                'price_3_months' => 6000.00,
                'price_6_months' => 12000.00,
                'price_12_months' => 24000.00,
                'expiration_warning_days' => 7,
                'allow_overnight' => true,
                'notes' => 'Default monthly membership pricing: ₱2,000 for one month',
            ]);
        }
        return $settings;
    }

    /**
     * Compute price for duration months.
     */
    public function getPriceForDuration(int $months): float
    {
        switch ($months) {
            case 3:
                return (float)($this->price_3_months ?? ($this->default_monthly_price * 3));
            case 6:
                return (float)($this->price_6_months ?? ($this->default_monthly_price * 6));
            case 12:
                return (float)($this->price_12_months ?? ($this->default_monthly_price * 12));
            case 1:
            default:
                return (float)($this->default_monthly_price * max(1, $months));
        }
    }
}

