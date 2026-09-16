<?php

namespace App\Enums;

enum ServiceRequestLogAction: string
{
    case Created = 'created';
    case Scheduled = 'scheduled';
    case StatusChanged = 'status-changed';
    case AppointmentConfirmed = 'appointment-confirmed';
    case AppointmentRescheduled = 'appointment-rescheduled';
    case AppointmentCancelled = 'appointment-cancelled';
    case PaymentVerified = 'payment-verified';
    case EmailQueued = 'email-queued';
    case EmailSent = 'email-sent';
    case EmailFailed = 'email-failed';
    case FeedbackLinkGenerated = 'feedback-link-generated';
    case FeedbackSubmitted = 'feedback-submitted';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Request Submitted',
            self::Scheduled => 'Appointment Scheduled',
            self::StatusChanged => 'Status Changed',
            self::AppointmentConfirmed => 'Appointment Confirmed',
            self::AppointmentRescheduled => 'Appointment Rescheduled',
            self::AppointmentCancelled => 'Appointment Cancelled',
            self::PaymentVerified => 'Payment Verified',
            self::EmailQueued => 'Email Queued',
            self::EmailSent => 'Email Sent',
            self::EmailFailed => 'Email Failed',
            self::FeedbackLinkGenerated => 'Feedback Form Link Generated',
            self::FeedbackSubmitted => 'Feedback Submitted',
        };
    }
}
