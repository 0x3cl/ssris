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

## SRRIS Project Context

### Stack

- PHP 8.4 and Laravel 13
- Inertia.js 3 with Vue 3 JavaScript page components
- Tailwind CSS 4 and Vite 8
- MySQL-compatible Laravel database layer, PHPUnit 12, and Laravel Pint
- `owen-it/laravel-auditing` for the audit trail module; `aos` (npm) for scroll-reveal animation; TCPDF for all generated PDFs

### Structure and request flows

- Public routes are in `routes/web.php`; Inertia page data and persistence orchestration belong in `app/Http/Controllers`.
- Server-side request validation belongs in `app/Http/Requests`; shared client writes are handled by `app/Services/ClientService.php`.
- Vue pages live in `resources/js/pages` and reusable UI components in `resources/js/components`.
- Walk-in and appointment requests share email-based client lookup and update-or-create behavior. Use enums for service and client option lists.
- A walk-in creates a `service_requests` record with `is_appointment = false`. An appointment sets `is_appointment = true` and persists `appointment_date` and `appointment_time`. `ServiceRequest::booted()` writes the first activity-log entry automatically on `created` — don't also log it manually in `store()`.
- Region/province/municipality address fields are API-driven, not hardcoded: `App\Services\PsgcClient` + `AddressController` proxy the public PSGC API (`address/regions`, `.../provinces`, `.../municipalities`), and the walk-in/appointment forms cascade Region → Province → Municipality against it.
- Read `docs/service-request-flows.md` and `.claude/skills/service-request-flows/SKILL.md` before making request-flow changes.

### Admin and R&D request workflow

- `Admin\RddRequestController` drives the R&D lifecycle: create the request form (`pending` → `for_payment`), verify OP/OR payment with optional proof attachments (`for_payment` → `awaiting_feedback`), then send/generate feedback links (`awaiting_feedback` → `completed`). A completed request stays reachable read-only through a "More Info" action on the same feedback page. `Admin\LabRequestController`, `Admin\ProcessingRequestController`, and `Admin\TrainingRequestController` mirror this shape for their own services.
- Tabbed admin pages persist the active tab in a `?tab=` query parameter (`resources/js/utils/query-tab.js`, `useQueryTab`); redirects after each stage's save append the next tab so the admin lands there automatically.
- Reminder emails render seeded `FormTemplate` rows through `FormTemplateMailer`. Any link placeholder in a template body must be a real `FeedbackLinkService`-generated URL, never a hardcoded or external one.
- A final, irreversible submit (R&D form submit, payment verification, public feedback submit) confirms first via `ConfirmActionModal` or `CodeConfirmationModal`.
- Every downloadable PDF (`FeedbackPdfService`, `RddPdfService`, `LabPdfService`, `ProcessingPdfService`, `TrainingServiceRequestPdfService`, `TrainingServiceFeePdfService`) uses `App\Services\NumberedPdf` (a `TCPDF` subclass) for an automatic "Page X of Y" footer; reserve enough bottom margin in page-break math so content never overlaps that footer.
- The `users_services` pivot (`User::services()`) scopes which requests an admin can see (`Admin\ServiceRequestController::index()` filters to the admin's assigned `ClientService` values). Manage assignment via the "Choose services" modal on the user form. The `iamcarlllemos@gmail.com` seeded superadmin is assigned all services in `AdminUserSeeder`.
- The "Audit Trails" module is read-only in roles/permissions (`AdminManagementController::READ_ONLY_MODULES`); login/logout are logged explicitly with the acting user.
- A user can never delete their own account, and the `superadmin` role can never be deleted — both are enforced server-side in `AdminManagementController` and mirrored in the Vue list pages via the explicitly-shared `$page.props.auth.user.id` (see `HandleInertiaRequests`).
- Read `docs/admin-operations.md` and `.claude/skills/admin-service-operations/SKILL.md` before making admin or R&D workflow changes.
