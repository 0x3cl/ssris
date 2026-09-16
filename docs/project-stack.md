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
| Analytics | Chart.js 4 |
| PDF generation | TCPDF 6.11 |
| Tests | PHPUnit 12 |
| Code formatting | Laravel Pint |

## Directory map

| Path | Purpose |
| --- | --- |
| `app/Enums` | Values shared by backend validation and frontend option lists. |
| `app/Http/Controllers` | Inertia page data, request endpoints, administration, and persistence orchestration. |
| `app/Http/Requests` | Server-side validation for final request submission. |
| `app/Models` | Eloquent records such as `Client`. |
| `app/Services` | Reusable domain operations, including client persistence, feedback links, and native TCPDF layouts. |
| `database/migrations` | Database schema for clients, requests, feedback, and administration. |
| `resources/js/components` | Reusable Vue UI pieces, modals, and steppers. |
| `resources/js/pages` | Inertia page components for public flows, feedback forms, and admin modules. |
| `resources/js/pages/admin` | Admin dashboard, requests, R&D workflow, users, roles, SMTP, and feedback builder pages. |
| `resources/js/utils` | Shared browser helpers such as date formatting and query-backed tabs. |
| `resources/views/pdfs` | Blade templates used only where TCPDF HTML rendering is appropriate. |
| `routes/web.php` | Public and administrator routes. |
| `docs` | Product and implementation documentation. |
| `.claude/skills` | Project skills that give coding agents domain-specific guidance. |

## Request-flow conventions

Walk-in and appointment flows share client lookup, client persistence, enum-backed options, server validation, terms confirmation, and `FeedbackModal`. Appointment requests additionally validate and store their date and time. The admin area is protected by Laravel authentication and the `superadmin` role. See [Service Request Flows](service-request-flows.md) and [Admin Operations](admin-operations.md) for the detailed behavior.
