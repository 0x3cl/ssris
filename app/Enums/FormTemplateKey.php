<?php

namespace App\Enums;

enum FormTemplateKey: string
{
    case FeedbackReminder = 'feedback-reminder';
    case PaymentReminder = 'payment-reminder';
    case AppointmentConfirmed = 'appointment-confirmed';
    case AppointmentCancellation = 'appointment-cancellation';
    case AppointmentReschedule = 'appointment-reschedule';
    case ServiceRequestReceipt = 'service-request-receipt';
    case TestNotification = 'test-notification';

    public function label(): string
    {
        return match ($this) {
            self::FeedbackReminder => 'Feedback Follow-up',
            self::PaymentReminder => 'Payment Reminder',
            self::AppointmentConfirmed => 'Appointment Confirmation',
            self::AppointmentCancellation => 'Appointment Cancellation',
            self::AppointmentReschedule => 'Appointment Rescheduling',
            self::ServiceRequestReceipt => 'Service Request Confirmation',
            self::TestNotification => 'Test Notification',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::FeedbackReminder => 'Sent when a service request is ready for pick-up and the client still has to submit the Customer Satisfaction Feedback.',
            self::PaymentReminder => 'Sent when a service request has an outstanding order of payment waiting to be settled.',
            self::AppointmentConfirmed => 'Sent when a booked appointment has been reviewed and confirmed by the receiving officer.',
            self::AppointmentCancellation => 'Sent when a booked appointment is cancelled by the receiving officer.',
            self::AppointmentReschedule => 'Sent when a booked appointment is moved by the receiving officer to a new schedule.',
            self::ServiceRequestReceipt => 'Sent to acknowledge receipt of a new Plant Tour service request, summarizing the details received.',
            self::TestNotification => 'A generic sample notice used to confirm the SMTP configuration is delivering emails correctly.',
        };
    }
}
