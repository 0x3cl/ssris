<?php

namespace App\Enums;

enum FormTemplateKey: string
{
    case FeedbackReminder = 'feedback-reminder';
    case PaymentReminder = 'payment-reminder';
    case AppointmentVerified = 'appointment-verified';
    case AppointmentCancellation = 'appointment-cancellation';
    case AppointmentReschedule = 'appointment-reschedule';

    public function label(): string
    {
        return match ($this) {
            self::FeedbackReminder => 'Feedback Reminder',
            self::PaymentReminder => 'Payment Reminder',
            self::AppointmentVerified => 'Appointment Verified',
            self::AppointmentCancellation => 'Appointment Cancellation',
            self::AppointmentReschedule => 'Appointment Reschedule',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::FeedbackReminder => 'Sent when a service request is ready for pick-up and the client still has to submit the Customer Satisfaction Feedback.',
            self::PaymentReminder => 'Sent when a service request has an outstanding order of payment waiting to be settled.',
            self::AppointmentVerified => 'Sent when a booked appointment has been reviewed and confirmed by the receiving officer.',
            self::AppointmentCancellation => 'Sent when a booked appointment is cancelled by the receiving officer.',
            self::AppointmentReschedule => 'Sent when a booked appointment is moved by the receiving officer to a new schedule.',
        };
    }
}
