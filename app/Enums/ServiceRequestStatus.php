<?php

namespace App\Enums;

enum ServiceRequestStatus: string
{
    case Pending = 'pending';
    case AssignSignatories = 'assign-signatories';
    case ForSignature = 'for-signature';
    case ForServiceFee = 'for-service-fee';
    case ForPayment = 'for-payment';
    case AwaitingFeedback = 'awaiting-feedback';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::AssignSignatories => 'Assign Signatories',
            self::ForSignature => 'For Signature',
            self::ForServiceFee => 'For Service Fee',
            self::ForPayment => 'For Payment',
            self::AwaitingFeedback => 'Awaiting Feedback',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }
}
