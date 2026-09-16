# Admin Operations

The administrator area is available under `/admin` and is protected by Laravel authentication plus the `superadmin` role. It uses the same shared navbar, footer, Tailwind styling, and reusable modal components as the public site.

## Modules

| Module | Purpose |
| --- | --- |
| Dashboard | Request totals, status summaries, monthly navigation, and Chart.js comparisons. |
| Requests | Searchable, paginated request list with request details and workflow actions. |
| Users | Administrator accounts, roles, account status, profile images, and soft deletion. |
| Roles and permissions | Role definitions and read/write module permissions. |
| SMTP configuration | The single outbound mail configuration. |
| Feedback Builder | Survey dimensions, statements, rating scales, questions, visual preview, and PDF output. |

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

The visualizer opens a new tab for survey preview. Its **Print PDF** action calls the TCPDF endpoint at `/admin/feedback-builder/visualize/pdf`; it does not call `window.print()`. The generated document uses a landscape native TCPDF layout so it has no browser URL, date, or page-count headers.

## PDFs

TCPDF is the project PDF renderer. Use it for new PDF work. Do not add DOMPDF back to the project.

- `App\Services\FeedbackPdfService` draws the customer satisfaction form using TCPDF cells, lines, borders, and circles. This avoids the limited HTML/CSS support that caused missing fields and rating headers.
- `App\Services\RddPdfService` draws the R&D service-request form the same way; `RddRequestController::downloadPdf()` calls it and returns the rendered document as a PDF download response.

When adding a form that can span pages, calculate available vertical space before drawing a grouped block. Move a whole question or grouped section to a new page instead of splitting it.

## UI conventions

- Reuse components in `resources/js/components` before adding page-specific UI.
- Admin indexes use server-side filtering, search, entries, and pagination; do not bulk-fetch lists.
- State-changing actions use the shared success and error modal flow. Deletion requires the four-digit confirmation modal.
- Native `<select>` elements inherit the global dropdown arrow and right inset in `resources/css/app.css`.
