# SRRIS

Service Requests Information System (SRRIS) is a Laravel and Inertia application for submitting PTRI walk-in and appointment service requests.

## Request flows

- **Walk-in:** select a service, provide client details, review, and submit.
- **Appointment:** choose a preferred date and time, select a service, provide client details, review, and submit.

Both flows look up clients by email and update an existing client record when one is found. Appointment requests save their date and time in the `service_requests` table and set `is_appointment` to `true`.

See [Service Request Flows](docs/service-request-flows.md) for the detailed user journey, validation behavior, and persistence rules.
See [Project Stack and Structure](docs/project-stack.md) for the technologies and directory map.

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

The project skill at [.claude/skills/service-request-flows/SKILL.md](.claude/skills/service-request-flows/SKILL.md) describes the shared rules for changing public request flows.
