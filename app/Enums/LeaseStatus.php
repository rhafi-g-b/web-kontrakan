<?php

declare(strict_types=1);

namespace App\Enums;

enum LeaseStatus: string
{
    case Active = 'active';
    case Ended = 'ended';
}