<?php

declare(strict_types=1);

namespace App\Enums;

enum ComplaintStatus: string
{
    case Pending = 'pending';
    case Open = 'open';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
}