<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LeaseStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Lease extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'agreed_monthly_price' => 'integer',
            'status' => LeaseStatus::class,
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function tenant(): BelongsTo
    {
        $foreignKey = Schema::hasColumn($this->getTable(), 'user_id') ? 'user_id' : 'tenant_id';

        return $this->belongsTo(User::class, $foreignKey);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}