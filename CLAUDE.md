# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.4. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record a rule with `record-rule` only when the user explicitly asks for one. Instructions for the work at hand are not rules, no matter how emphatic: "remove this typo", "use X here" are work to do, not rules to record. Never record a rule on your own initiative, as a byproduct of a change, or to summarize what you just did. When the user does ask, pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Use `record-rule` rather than your native memory or notes tool, because native memory is personal and session-scoped, while only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>

## Project overview

SRRIS (Service Requests Information System) is a Laravel + Inertia app for PTRI: the public site lets visitors submit **walk-in** and **appointment** service requests, and an authenticated admin side (role-gated via Spatie Permission) manages those requests, users, roles, and SMTP configuration.

## Commands

```bash
composer install && npm install   # install deps
php artisan migrate               # apply schema
composer run dev                  # run Laravel + queue + Vite together (artisan dev)
npm run build                     # production frontend assets
```

Tests (PHPUnit, no Pest):
```bash
php artisan test --compact tests/Feature/WalkInControllerTest.php   # one file
php artisan test --compact --filter=testName                        # one test
vendor/bin/pint --dirty --format agent                               # format PHP after edits
```

## Architecture

### Two parallel public request flows, one shared backend shape

`WalkInController` and `AppointmentController` (`app/Http/Controllers`) are structured identically: an `index` page, a `findClient` email lookup, a `validateDetails` step-validation endpoint, and a `store`. Appointment additionally has `validateBooking` for the date/time step. Both write to the same `clients` and `service_requests` tables in one transaction — `is_appointment` plus `appointment_date`/`appointment_time` are the only persisted differences. `ServiceRequest::booted()` automatically writes the first activity-log entry ("submitted"/"scheduled") on `created`, so `store()` in both controllers must not also log that event manually — it would duplicate the entry. See `docs/service-request-flows.md` and the `service-request-flows` skill before touching either flow; changes to shared behavior (client lookup-by-email, `fullname` derivation, terms confirmation, `FeedbackModal`) must stay in sync across both.

### Enums are the single source of truth for classification values

`app/Enums/*` (`ClientType`, `ClientService`, `ClientSource`, `ClientBusinessRole`, `ClientEnterpriseSize`, `ClientMarket`) back both `Rule::enum()` validation in Form Requests/`ClientService` and the option lists rendered in the Vue forms. Never hardcode these values in a controller or component — add/edit the enum case (with its `label()`) instead.

### Admin CRUD goes through a Service class, not directly through the controller

`App\Services\ClientService` owns pagination, find/create/update/delete, and validation for `Client`; `Admin\ClientController` calls into it rather than validating/persisting inline. Follow this pattern for new admin-managed resources. Note: `App\Services\AccountService` exists for the same purpose on `User` but `Admin\AdminManagementController::saveUser()` currently validates and persists inline instead (including `username`, `role`, `profile_image`, and the `services` sync below) — this is known drift from the intended pattern, not a model to copy for new resources.

### Per-user service scoping restricts which requests an admin can see

The `users_services` pivot table (`App\Models\UserService`, `User::services(): HasMany`) assigns each admin user zero or more `App\Enums\ClientService` values. `Admin\ServiceRequestController::index()` filters the requests list to `whereIn('service', $assignedServices)` — an admin with no assigned services sees zero requests. The user form's "Choose services" button opens `ServiceSelectModal` (a multi-select grid of illustrated `ServiceCard`s, reusing `resources/js/utils/service-illustrations.js`) to manage this per user. `database/seeders/AdminUserSeeder.php` assigns all `ClientService` cases to the `iamcarlllemos@gmail.com` superadmin account so it isn't scoped out of any requests — keep that seeder in sync if `ClientService` gains new cases.

### Admin routes are role-gated and module pages are dynamic

Everything under `Route::prefix('admin')` (see `routes/web.php`) except login requires `auth` + `role:superadmin` (Spatie). Simple admin pages that don't need a dedicated controller are served by `AdminModuleController@show`, which maps a `{module}` route segment to an Inertia page (`admin/module`) from a fixed allow-list — add new lightweight admin pages there rather than wiring a bespoke controller.

### Account safety: no self-delete, no deleting the superadmin role

`AdminManagementController::deleteUser()` and `::deleteRole()` both `abort_if(...)` server-side (you can't delete your own account; the `superadmin` role can never be deleted) — keep both checks if either method is refactored. The frontend hides the corresponding button in `resources/js/pages/admin/users.js`/`roles.js` using the same conditions, which depends on the logged-in user's id being available as `$page.props.auth.user.id`. That prop is shared explicitly in `app/Http/Middleware/HandleInertiaRequests.php::share()` — it is **not** part of Inertia's or Laravel's defaults, so any new page relying on `$page.props.auth` must confirm it's still shared there rather than assuming it exists.

### Frontend: Vue components are plain `.js` files, not `.vue` SFCs

Every file in `resources/js/pages` and `resources/js/components` is `defineComponent({ template: \`...\` })` in a `.js` file — there are no `.vue` files in this project. `vite.config.js` aliases `vue` to the runtime+compiler build specifically to support compiling these inline template strings; don't "fix" that alias or introduce `.vue` SFCs without checking with the user first, since the whole codebase depends on the current setup. Inertia page resolution in `resources/js/app.js` globs `./pages/**/*.js` accordingly.

AOS (`aos` npm package) drives scroll/reveal animation (`data-aos="fade-up"`/`"fade-right"`, `AOS.init()` in `onMounted`). When a page reveals `data-aos` elements that were loaded asynchronously (e.g. services fetched from the DB), don't call `AOS.init()` before that content exists — show a loading state first, then flip to the real content and call `AOS.refreshHard()` in `nextTick()` so AOS picks up the newly-rendered elements (see `walk-in.js`/`appointment.js`'s `isLoadingServices`/`revealServices()`).

### R&D admin workflow

`Admin\RddRequestController` drives the R&D service-request lifecycle end to end: create the request form (`pending` → `for_payment`), verify OP/OR payment details with optional proof attachments (`for_payment` → `awaiting_feedback`), then send/generate feedback links (`awaiting_feedback` → `completed`). Each stage's redirect and the requests-list action links append a `?tab=` query parameter so the admin lands on the next relevant tab (see `useQueryTab` in `resources/js/utils/query-tab.js`); a completed request stays reachable read-only through a "More Info" action on the same feedback page. Payment and feedback reminder emails render seeded `FormTemplate` rows through `FormTemplateMailer`, and any link included in a template body must be a real `FeedbackLinkService`-generated URL, not a hardcoded placeholder. See `docs/admin-operations.md` and the `admin-service-operations` skill before changing this flow.

### Lab, Processing, and Training (TSD) request workflows

`Admin\LabRequestController`, `Admin\ProcessingRequestController`, and `Admin\TrainingRequestController` each drive their own service's request/payment lifecycle, mirroring the R&D workflow's shape. Each has a matching native-TCPDF download service (`LabPdfService`, `ProcessingPdfService`, `TrainingServiceRequestPdfService`, `TrainingServiceFeePdfService`) — see "PDF generation" below.

### PDF generation uses native TCPDF drawing with a shared footer

Every generated PDF (`FeedbackPdfService`, `RddPdfService`, `LabPdfService`, `ProcessingPdfService`, `TrainingServiceRequestPdfService`, `TrainingServiceFeePdfService`) instantiates `App\Services\NumberedPdf` (a `TCPDF` subclass) instead of `TCPDF` directly, with `setPrintFooter(true)`, to get an automatic centered "Page X of Y" footer on every page, including pages added mid-render via `AddPage()`. When a service computes its own page-break threshold (deciding whether content still fits before drawing more), it must reserve enough bottom margin to clear the footer's reserved zone (roughly the last 40pt of the page) — don't let a table's last row or a page's last field land closer than ~15pt from the page bottom, or it will visually overlap the footer text. `FeedbackPdfService` renders **portrait** (not landscape) to match the physical "TSD Form No. 008" form; its rating-table column widths are percentages of the page width (15% dimension / 28% description / remainder split across rating columns) matching the same ratios used by the web preview page (`resources/js/pages/admin/feedback-visualization.js`) — keep both in sync when adjusting one.

### Audit trail

`owen-it/laravel-auditing` is installed; `User` (and other audited models) use the `Auditable` trait/contract. The "Audit Trails" admin module (`Admin\AuditTrailController`, module key `audit-trails`) lists `OwenIt\Auditing\Models\Audit` rows with the acting user and before/after values, and is excluded from the visitor-capture middleware. Login and logout are logged explicitly (not automatically covered by the package) with the acting user attached. `audit-trails` is a **read-only** module in the roles/permissions system (`AdminManagementController::READ_ONLY_MODULES`) — it only ever grants a `.read` permission, never `.write`; `saveRole()` strips any `write` permission submitted for a read-only module server-side, so don't add a write toggle for it in the roles UI.

### PSGC-backed address cascade

`App\Services\PsgcClient` proxies the public PSGC API (`psgc.gitlab.io/api`), caching non-empty responses, and normalizes municipality names (`"City of Manila"` → `"Manila City"`). `App\Http\Controllers\AddressController` exposes this as three public JSON endpoints (`address/regions`, `address/regions/{region}/provinces`, `address/provinces/{province}/municipalities`) used by the walk-in and appointment forms' cascading Region → Province → Municipality `<select>` elements (`regionCode`/`provinceCode`/`municipalityCode` refs, with `hydrateAddressCascade()` resolving a returning client's stored address strings back to PSGC codes). Don't hardcode region/province/municipality option lists — always resolve them through this API.

### Migrated-but-unused modules

Migrations exist for `tour_requests` and `activity_logs`, but there are no corresponding models/controllers yet — treat these as scaffolding for future modules, not dead code to remove. (`rdd_requests`, `lab_requests`, `processing_requests`, and `training_requests`/`training_request_fees` are all now fully implemented — see the workflow sections above.)
