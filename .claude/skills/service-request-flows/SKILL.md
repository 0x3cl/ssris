---
name: service-request-flows
description: Build or modify SRRIS walk-in and appointment request flows, including the shared client lookup, server validation, review, and service-request persistence.
---

# SRRIS Service Request Flows

Use this skill for changes to public walk-in or appointment requests.

This project uses Laravel controllers and Form Requests, Inertia page props, Vue components, and Tailwind CSS. Keep backend flow behavior in `app/Http`, page composition in `resources/js/pages`, and reusable UI in `resources/js/components`.

## Shared behavior

- Services and client classifications come from the application enums; do not duplicate their values in the UI or controllers.
- Look up active clients by email before client details. The entered email becomes read-only after lookup.
- On final submission, update the matching client by email or create a new client. Build `fullname` from first and last name in the controller.
- Use Form Requests for final validation. The frontend may request server validation before a step advances, but must not rely on browser `required` validation.
- Show validation errors beside their fields and scroll only to the first invalid field.
- Use `FeedbackModal` for completion, warning, error, or informational dialogs instead of creating one-off feedback modals.
- Keep public feedback separate from request submission. Feedback links are issued after the request lifecycle reaches its feedback stage and are served through `/feedback/{token}`.
- The public feedback form (`resources/js/pages/feedback-form.js`) confirms with `ConfirmActionModal` before posting the response, since submissions cannot be edited afterward — keep that confirmation step when touching this page.

## Persistence

- Create one `service_requests` row in the same transaction as the client write.
- Walk-in requests set `is_appointment` to `false` and leave appointment fields empty.
- Appointment requests set `is_appointment` to `true` and persist validated `appointment_date` and `appointment_time`.

Read [Service Request Flows](../../../docs/service-request-flows.md) for the current routes and user-facing sequence.
