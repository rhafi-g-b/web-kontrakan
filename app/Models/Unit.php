<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LeaseStatus;
use App\Enums\UnitStatus;
use App\Models\Lease;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_number',
        'monthly_price',
        'status',
        'description',
        'photo_path',
    ];

    protected function casts(): array
    {
        return [
            'monthly_price' => 'integer',
            'status' => UnitStatus::class,
        ];
    }

    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }

    public function activeLease(): HasOne
    {
        return $this->hasOne(Lease::class)->where('status', LeaseStatus::Active);
    }
}