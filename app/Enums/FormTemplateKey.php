<?php

namespace App\Enums;

enum FormTemplateKey: string
{
    case TourAccepted = 'tour-accepted';
    case TourRescheduled = 'tour-rescheduled';
    case TourCancelled = 'tour-cancelled';
    case FeedbackReminder = 'feedback-reminder';
    case PaymentReminder = 'payment-reminder';
    case AppointmentConfirmed = 'appointment-confirmed';
    case AppointmentCancellation = 'appointment-cancellation';
    case AppointmentReschedule = 'appointment-reschedule';
    case ServiceRequestReceipt = 'service-request-receipt';
    case TestNotification = 'test-notification';
    case PasswordReset = 'password-reset';

    public function label(): string
    {
        return match ($this) {
            self::TourAccepted => 'Tour Confirmation',
            self::TourRescheduled => 'Tour Rescheduling',
            self::TourCancelled => 'Tour Cancellation',
            self::FeedbackReminder => 'Feedback Follow-up',
            self::PaymentReminder => 'Payment Reminder',
            self::AppointmentConfirmed => 'Appointment Confirmation',
            self::AppointmentCancellation => 'Appointment Cancellation',
            self::AppointmentReschedule => 'Appointment Rescheduling',
            self::ServiceRequestReceipt => 'Service Request Confirmation',
            self::TestNotification => 'Test Notification',
            self::PasswordReset => 'Password Reset',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::TourAccepted => 'Sent when a plant tour request is accepted.',
            self::TourRescheduled => 'Sent with the proposed plant tour schedule and reason.',
            self::TourCancelled => 'Sent when a plant tour request is cancelled, including the reason.',
            self::FeedbackReminder => 'Sent when a service request is ready for pick-up and the client still has to submit the Customer Satisfaction Feedback.',
            self::PaymentReminder => 'Sent when a service request has an outstanding order of payment waiting to be settled.',
            self::AppointmentConfirmed => 'Sent when a booked appointment has been reviewed and confirmed by the receiving officer.',
            self::AppointmentCancellation => 'Sent when a booked appointment is cancelled by the receiving officer.',
            self::AppointmentReschedule => 'Sent when a booked appointment is moved by the receiving officer to a new schedule.',
            self::ServiceRequestReceipt => 'Sent to acknowledge receipt of a new Plant Tour service request, summarizing the details received.',
            self::TestNotification => 'A generic sample notice used to confirm the SMTP configuration is delivering emails correctly.',
            self::PasswordReset => 'Sent to an administrator who requested a password reset link for their account.',
        };
    }
}
