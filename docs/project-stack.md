# Project Stack and Structure

## Stack

| Layer | Technology |
| --- | --- |
| Backend | PHP 8.4, Laravel 13 |
| Server-rendered application bridge | Inertia.js 3 for Laravel |
| Frontend | Vue 3 with JavaScript components |
| Styling | Tailwind CSS 4 |
| Build tool | Vite 8 with Laravel Vite plugin |
| Database access | Laravel Eloquent and query builder |
| Roles and permissions | Spatie Laravel Permission 8 |
| Tests | PHPUnit 12 |
| Code formatting | Laravel Pint |

## Directory map

| Path | Purpose |
| --- | --- |
| `app/Enums` | Values shared by backend validation and frontend option lists. |
| `app/Http/Controllers` | Inertia page data, request endpoints, and persistence orchestration. |
| `app/Http/Requests` | Server-side validation for final request submission. |
| `app/Models` | Eloquent records such as `Client`. |
| `app/Services` | Reusable domain operations, including client creation and updates. |
| `database/migrations` | Database schema for clients and service requests. |
| `resources/js/components` | Reusable Vue UI pieces, modals, and steppers. |
| `resources/js/pages` | Inertia page components: landing page, walk-in flow, and appointment flow. |
| `routes/web.php` | Public web routes for each request flow. |
| `docs` | Product and implementation documentation. |
| `.claude/skills` | Project skills that give coding agents domain-specific guidance. |

## Request-flow conventions

Walk-in and appointment flows share client lookup, client persistence, enum-backed options, server validation, terms confirmation, and `FeedbackModal`. Appointment requests additionally validate and store their date and time. See [Service Request Flows](service-request-flows.md) for the detailed behavior.
