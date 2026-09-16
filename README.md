# SRRIS

Service Requests Information System (SRRIS) is a Laravel and Inertia application for PTRI public service requests and administration.

## Request flows

- **Walk-in:** select a service, provide client details, review, and submit.
- **Appointment:** choose a preferred date and time, select a service, provide client details, review, and submit.

Both flows look up clients by email and update an existing client record when one is found. Appointment requests save their date and time in the `service_requests` table and set `is_appointment` to `true`.

See [Service Request Flows](docs/service-request-flows.md) for the detailed user journey, validation behavior, and persistence rules.
See [Project Stack and Structure](docs/project-stack.md) for the technologies and directory map.
See [Admin Operations](docs/admin-operations.md) for request processing, feedback, PDF generation, and administrator modules.

## Local development

Install dependencies, configure `.env`, run the database migrations, then start the Laravel and Vite development servers:

```bash
composer install
npm install
php artisan migrate
composer run dev
```

To create production frontend assets:

```bash
npm run build
```

## Agent guidance

Project skills provide focused guidance for coding agents:

- [Public request flows](.claude/skills/service-request-flows/SKILL.md)
- [Admin service operations](.claude/skills/admin-service-operations/SKILL.md)
