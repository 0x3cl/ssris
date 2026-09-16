# Service Request Flows

SRRIS supports two public request paths: walk-in requests and appointment requests. Both use the same client record and service-request table.

## Walk-in requests

The walk-in form has three steps:

1. Select a service and supply an email address.
2. Complete or update client information.
3. Review the request, agree to the terms, and submit.

The submitted `service_requests` row has `is_appointment` set to `false`. Appointment date and time remain empty.

## Appointment requests

The appointment form has four steps:

1. Select a preferred booking date and time.
2. Select a service and supply an email address.
3. Complete or update client information.
4. Review the request, agree to the terms, and submit.

The submitted `service_requests` row has `is_appointment` set to `true` and stores the selected `appointment_date` and `appointment_time`.

## Client records

The email lookup runs before the client-details step. If an active client with that email exists, the form is prefilled. On submission, the application updates that client record; otherwise, it creates a client record. The full name is derived from the submitted first and last names.

## Address selection

Region, Province, and Municipality are cascading `<select>` fields backed by the public PSGC (Philippine Standard Geographic Code) API, not a static list: selecting a region loads its provinces (or its municipalities directly, for NCR-style regions that have none), and selecting a province loads its municipalities. `App\Services\PsgcClient` proxies and caches the upstream API and normalizes municipality names (e.g. "City of Manila" becomes "Manila City"); `App\Http\Controllers\AddressController` exposes it as public JSON endpoints under `address/...`. A returning client's previously saved address strings are resolved back to PSGC codes to prefill the selects.

## Activity log

Every submitted request automatically gets a first activity-log entry ("Walk-in service request submitted" or "Appointment request submitted for ...") written by a `ServiceRequest` model event on creation — the controllers do not write this entry themselves.

## Validation and feedback

All request validation is performed by Laravel Form Requests. The frontend sends validation requests before advancing from booking and client-detail steps, then displays errors beside the applicable fields and scrolls to the first error. The email is read-only after lookup and is validated again at final submission.

The reusable `FeedbackModal` component shows the completion message after a successful request. It accepts configurable copy, icon, tone, and action properties for future flows.

## Follow-up feedback

R&D requests that have reached the feedback stage can issue a time-limited public feedback link. The public feedback form is served from `/feedback/{token}` and stores the submitted response against that link. The feedback link is generated and managed from the admin request workflow, not from the public request forms.
