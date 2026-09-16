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
- `ServiceRequest::booted()` already writes the first activity-log entry on `created` — do not log that event again in the controller's `store()`, it would duplicate the entry.

## Address fields (Region / Province / Municipality)

- These are API-driven, not static option lists. `App\Services\PsgcClient` proxies the public PSGC API and `App\Http\Controllers\AddressController` exposes `address/regions`, `address/regions/{region}/provinces`, and `address/provinces/{province}/municipalities`. The walk-in/appointment forms cascade: selecting a region loads its provinces (or municipalities directly for NCR-style regions with none), and selecting a province loads its municipalities.
- Municipality names are normalized server-side (e.g. `"City of Manila"` → `"Manila City"`) — don't re-implement that formatting client-side.
- When prefilling a returning client's saved address strings back into the selects, resolve them to PSGC codes (see `hydrateAddressCascade()` in `walk-in.js`/`appointment.js`) rather than matching on label text alone.

## Service selection UI

- The 6-service `ServiceCard` grid (`resources/js/components/ServiceCard.js`) uses AOS (`data-aos="fade-up"`, staggered `data-aos-delay`) for reveal animation, and the illustration-per-service map lives in `resources/js/utils/service-illustrations.js` — import it rather than redefining the map per page.
- Because the service list is fetched from the database, show a skeleton/loading state first and only call `AOS.init()`/`AOS.refreshHard()` once the real cards are in the DOM (see `isLoadingServices`/`revealServices()`); initializing AOS before the cards exist means their animation never fires.

Read [Service Request Flows](../../../docs/service-request-flows.md) for the current routes and user-facing sequence.
