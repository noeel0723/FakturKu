# FakturKu Smoke Test Checklist

## 1. Core Navigation
- Open `/dashboard`, `/clients`, `/products`, `/invoices`.
- Open each new Ops module from sidebar: Quotes, Recurring, Credit Notes, Aging Report, Reminders, Tax Profiles, Reconciliation, Export Center.
- Confirm pages render properly on desktop and mobile widths.

## 2. Product Active Toggle
- Create product as `Inactive` in `/products/create`.
- Edit product to `Active` and save.
- Verify status badge/filter reflects current active state in `/products`.

## 3. Quote to Invoice
- Create quote in `/ops/quotes` with at least one item.
- Click `Convert to Invoice`.
- Verify redirect to generated invoice detail page.

## 4. Recurring Billing
- Create recurring template in `/ops/recurring`.
- Click `Generate Due Invoices`.
- Confirm flash message reports generated invoices.
- Verify new invoice appears in `/invoices`.

## 5. Credit Note Flow
- Create credit note in `/ops/credit-notes` for an existing invoice.
- Apply credit note.
- Verify credit note status changes to `applied`.
- Verify invoice total changes accordingly.

## 6. Aging Report
- Open `/ops/aging-report`.
- Change As-Of date and refresh.
- Verify bucket cards and outstanding table update.

## 7. Reminder Engine
- Open `/ops/reminders` and run reminder job.
- Verify flash summary includes sent/skipped/failed counts.
- Verify new rows appear in reminder logs table.

## 8. Tax Profiles
- Create tax profile in `/ops/tax-profiles`.
- Run preview calculation with base amount.
- Verify displayed tax and grand total values.

## 9. Reconciliation Dashboard
- Open `/ops/reconciliation`.
- Verify provider summary loads.
- Verify mismatch table appears and links to invoice details.

## 10. Attachments
- Open any invoice detail page `/invoices/show/{id}`.
- Upload PDF or image in attachment block.
- Verify uploaded file appears in list and is viewable.

## 11. Export Center
- Open `/ops/exports`.
- Download CSV and XLS for invoices/payments/clients.
- Open API JSON links and verify response structure.

## 12. Role Guard (RBAC)
- Set `DEFAULT_USER_ROLE` in `.env` to `staff` then reload app.
- Verify restricted Ops pages (finance/owner only) return "Forbidden".
- Set role back to `owner` and verify full access returns.
