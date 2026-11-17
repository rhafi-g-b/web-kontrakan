<?php

declare(strict_types=1);

namespace App\Enums;

enum UnitStatus: string
{
    case Available = 'available';
    case Occupied = 'occupied';
    case Maintenance = 'maintenance';
}