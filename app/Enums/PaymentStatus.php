<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Pending = 'pending';
    case WaitingVerification = 'waiting_verification';
    case Paid = 'paid';
    case Rejected = 'rejected';
}