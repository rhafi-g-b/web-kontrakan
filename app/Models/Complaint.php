<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ComplaintStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class Complaint extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => ComplaintStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
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
}