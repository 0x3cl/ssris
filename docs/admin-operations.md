# Admin Operations

The administrator area is available under `/admin` and is protected by Laravel authentication plus the `superadmin` role. It uses the same shared navbar, footer, Tailwind styling, and reusable modal components as the public site.

## Modules

| Module | Purpose |
| --- | --- |
| Dashboard | Request totals, status summaries, monthly navigation, and Chart.js comparisons. |
| Requests | Searchable, paginated request list with request details and workflow actions, **scoped to the logged-in admin's assigned services** (see "Per-user service scoping" below). |
| Users | Administrator accounts, roles, account status, profile images, assigned services, and soft deletion. |
| Roles and permissions | Role definitions and read/write module permissions. `audit-trails` is read-only — it only ever grants `.read`. |
| Audit Trails | Read-only log of tracked model changes (`owen-it/laravel-auditing`) plus explicit login/logout events, each with the acting user. |
| SMTP configuration | The single outbound mail configuration. |
| Feedback Builder | Survey dimensions, statements, rating scales, questions, visual preview, and PDF output. |

## Per-user service scoping

The `users_services` pivot table (`App\Models\UserService`, cast to `App\Enums\ClientService`) assigns each admin user zero or more services. `Admin\ServiceRequestController::index()` filters the requests list with `whereIn('service', $assignedServices)` — an admin assigned to no services sees no requests. Assignment happens on the user form via a "Choose services" button that opens `ServiceSelectModal`, a multi-select grid of illustrated `ServiceCard`s (the same component and illustration map used by the public walk-in/appointment service picker). Saving a user replaces its service assignment (delete then re-insert) rather than merging. `database/seeders/AdminUserSeeder.php` assigns every `ClientService` case to the `iamcarlllemos@gmail.com` superadmin account specifically so this scoping doesn't lock it out of any requests — update that seeder if `ClientService` gains a new case.

## Account safety

`AdminManagementController::deleteUser()` and `::deleteRole()` both guard against destructive self-inflicted or irreversible actions server-side: a user can never delete their own account, and the `superadmin` role can never be deleted. The Vue list pages (`admin/users.js`, `admin/roles.js`) hide the corresponding delete button using the same conditions, which depend on the logged-in user's id being available as `$page.props.auth.user.id`. That prop is explicitly shared in `app/Http/Middleware/HandleInertiaRequests.php::share()` — it is not part of Inertia's or Laravel's defaults, so any new page that reads `$page.props.auth` must confirm it's still shared there.

## R&D request workflow

R&D processing proceeds through these stages, all inside `RddRequestController`:

1. Create the R&D service-request form and items (`store`). The request moves to `for_payment`, and the admin lands on the payment page's **Payment verification** tab.
2. Verify OP and official-receipt numbers, optionally attaching OP/OR proof files (`updatePayment`, stored on the local disk and downloadable via `requests.rdd.attachment`). The request moves to `awaiting_feedback`, and the admin lands on the feedback page's **Feedback** tab. A **Send reminder** action (`sendPaymentReminder`) can resend the `PaymentReminder` template while the request is still `for_payment`.
3. Generate or resend a feedback link. **Send reminder** (`sendFeedbackReminder`) generates a fresh `FeedbackLinkService` link on every click and includes its URL as the `{{link}}` placeholder in the `FeedbackReminder` template; **Generate feedback form** (`generateFeedbackLink`) does the same without emailing it. Submitted links expose the saved response for review.
4. Once the client submits feedback, the request moves to `completed`. The requests list shows a **More Info** action for completed R&D requests that reuses the same feedback page, deep-linked to its **Feedback** tab; the page detects `status_value === 'completed'` and hides the reminder/generate-link actions in favor of a read-only summary.

Each stage's redirect appends a `tab` query parameter so the admin lands on the next relevant tab automatically — for example the requests-list action links go straight to `?tab=payment-verification` or `?tab=feedback`, and `store`/`updatePayment` redirect the same way after saving. Use `resources/js/utils/query-tab.js` (`useQueryTab`) for any page that needs the same tab-in-URL behavior.

State-changing submits (the R&D request form's final submit, the public feedback form's submit) confirm through `ConfirmActionModal` before posting; the payment-verification submit uses the four-digit `CodeConfirmationModal` instead, consistent with other destructive/irreversible admin actions.

## Feedback Builder

The builder stores dimensions, statements, rating scales, and free-text questions independently. Dimension and statement ordering is persisted. The builder page remembers its expanded accordion sections in local storage.

The visualizer opens a new tab for survey preview. Its **Print PDF** action calls the TCPDF endpoint at `/admin/feedback-builder/visualize/pdf`; it does not call `window.print()`. The generated document uses a **portrait** native TCPDF layout, matching the physical "TSD Form No. 008" form, so it has no browser URL, date, or page-count headers. The web preview page (`resources/js/pages/admin/feedback-visualization.js`) mirrors the same header, field layout, and ratings-table column proportions as the PDF — keep both in sync when adjusting either.

## PDFs

TCPDF is the project PDF renderer. Use it for new PDF work. Do not add DOMPDF back to the project.

- `App\Services\FeedbackPdfService` draws the customer satisfaction form using TCPDF cells, lines, borders, and squares. This avoids the limited HTML/CSS support that caused missing fields and rating headers.
- `App\Services\RddPdfService`, `App\Services\LabPdfService`, `App\Services\ProcessingPdfService`, `App\Services\TrainingServiceRequestPdfService`, and `App\Services\TrainingServiceFeePdfService` draw their respective service-request/payment forms the same way; each is called by its controller's `downloadPdf()`-style action and returned as a PDF download response.
- All six services instantiate `App\Services\NumberedPdf` (a `TCPDF` subclass) instead of `TCPDF` directly, with `setPrintFooter(true)`. `NumberedPdf::Footer()` draws a centered "Page X of Y" automatically on every page, including pages added mid-render via `AddPage()` — don't hand-draw page numbers per service.

When adding a form that can span pages, calculate available vertical space before drawing a grouped block. Move a whole question or grouped section to a new page instead of splitting it. Any page-break threshold must also reserve room for `NumberedPdf`'s footer (roughly the last 40pt of the page) — a table's last row or a page's last field landing closer than that will visually overlap the footer text.

## UI conventions

- Reuse components in `resources/js/components` before adding page-specific UI.
- Admin indexes use server-side filtering, search, entries, and pagination; do not bulk-fetch lists.
- State-changing actions use the shared success and error modal flow. Deletion requires the four-digit confirmation modal.
- Native `<select>` elements inherit the global dropdown arrow and right inset in `resources/css/app.css`.
