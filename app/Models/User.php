<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LeaseStatus;
use App\Enums\UserRole;
use App\Models\Lease;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $guarded = ['id'];

    protected $fillable = [
        'name',
        'phone',
        'email',
        'role',
        'ktp_photo_path',
        'is_active',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isTenant(): bool
    {
        return $this->role === UserRole::Tenant;
    }

    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }

    public function activeLease(): HasOne
    {
        return $this->hasOne(Lease::class)->where('status', LeaseStatus::Active);
    }

    public function validatedPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'validated_by');
    }
}