# Multiple Medicines per Rx with Per-Medicine Approval — Design

Supersedes the reverted [per-medicine ICC review](2026-08-19-per-medicine-icc-review-design.md) and
[per-medicine days approved](2026-08-19-per-medicine-days-approved-design.md) designs. Reuses their
validation approach, and fixes what they left open: no transactions, Pharmacy/Nurse still acting on the
whole Rx, a second dispensing cycle colliding with `rendered_prescriptions`' unique key, and no Rx-level status.

## Decisions (confirmed 2026-10-27)

| Topic | Decision |
|---|---|
| Medicines per Rx | Unlimited. All medicines share the Rx's type (type stays on `prescriptions`). |
| Who decides per medicine | IPCC, as today: Restricted Rxs and Rxs marked "for extension". Monitored / Non-Monitored still skip IPCC and every medicine is auto-approved for 7 days. |
| Days | Set only on approval, per medicine: by IPCC on AMS Review, or 7 automatically for Monitored / Non-Monitored. The doctor does not enter days (corrected 2026-10-27). `days_requested` stays as an optional column holding legacy values. |
| Approved days | Per medicine, set by IPCC. Same choices as today (1 / 3 / 7 / 14 / Other), no new cap. |
| Rejection reason | Required per rejected medicine. |
| Pharmacy / Nurse | See every medicine with its status badge; can act only on approved ones. |
| Rx-level status | Pending (nothing decided) · Partially approved (mixed, or some still pending) · Approved (all) · Rejected (all). |
| Existing data | Production already has `prescribed_medicines.status` (from the August migration); `prescription_logs.prescribed_medicine_id` does not exist anywhere. |
| Tests | Run against the local `.env` database, inside transactions that roll back. Only after confirming every `*_HOST` is `127.0.0.1`. |

## Schema (additive only, nullable or defaulted, no drops)

`prescribed_medicines`
- `status` tinyint not null default 0 — **only added if missing** (`Schema::hasColumn`), so production's existing column is kept. 0 pending, 1 approved, 2 rejected, 3 withdrawn (doctor removed a rejected medicine).
- `days_requested` int null
- `days_approved` int null
- `decision_remarks` text null — rejection reason, or IPCC's note on an approval
- `decided_by` int null, `decided_at` datetime null
- index on (`prescription_id`, `status`)

`prescriptions`
- `review_status` tinyint null — 0 pending, 1 partially approved, 2 approved, 3 rejected. Derived from the medicines and rewritten by one helper after every decision; never edited directly. The existing `status` (active 1 / cycle ended 0) is unchanged.

`concurrent_therapy`
- `prescribed_medicine_id` bigint unsigned null, FK → `prescribed_medicines.id` on delete set null. Replaces matching by description text. Reports keep reading this table, so per-medicine days flow into the AMS summary and its Excel export unchanged.

`prescription_logs`
- `prescribed_medicine_id` bigint unsigned null, FK as above. Null for every Rx-level log, so existing log reads are unaffected.

### Backfill (in the migration, chunked, idempotent)

Every existing Rx has exactly one medicine, so each legacy medicine takes its Rx's current position:

| Legacy Rx state | Medicine `status` | Days / decider |
|---|---|---|
| In `icc_reviews` | 0 pending | — |
| In `returned_prescriptions`, or ended from Returned (`status` 0) | 2 rejected | reason from the latest reject log, tags stripped |
| Anywhere else (passed IPCC or skipped it) | 1 approved | `days_approved`, `approved_by`, `date_approved` from its `concurrent_therapy` row |

`days_requested` ← the Rx's `days_requested`. `concurrent_therapy.prescribed_medicine_id` is set where the row is an approval row (`date_approved` not null) on a one-medicine Rx. `review_status` is then computed for every Rx. Rxs with no medicines (type 6 cancellations) keep `review_status` null.

`down()` drops only the columns each migration added, and never the pre-existing `status` column on production.

## Backend

One service, `App\Services\PrescriptionWorkflow`, holds the per-medicine rules so the controllers stay thin. Every write runs in `DB::transaction` and locks the Rx's queue row and medicines with `lockForUpdate()`, so two reviewers can't overwrite each other.

**Create** (`insertPrescription`) — validate the request server-side, build each medicine from whitelisted fields only (no more raw `insert($request->medications)`). No days are taken from the prescriber. Auto-approve types 5/8 (status 1, 7 days, `concurrent_therapy` linked by id). Replace the manual cleanup-on-error with a transaction.

**IPCC decision** — new `POST /icc/decide_prescription`, ICC role only. Payload: one `{id, decision, days_approved, remarks}` per pending medicine. All rows are validated before anything is written:
- every medicine belongs to this Rx and is still pending
- `decision` is approved or rejected
- approved needs `days_approved` ≥ 1
- rejected needs a reason

Then for each medicine: set its status, days, reason, decider and time; for approvals write a linked `concurrent_therapy` row; write a linked `prescription_logs` row. Approved medicines put the Rx in `pharmacy_reviews`, rejected ones put it in `returned_prescriptions`; both can be true at once. Type 6 keeps the existing whole-Rx cancellation path.

**Pharmacy render** — only medicines that are approved and not yet rendered can be rendered. The request can't touch other medicines. The availability check runs before any write (today it saves some rows and then fails). Pharmacy "Return to Doctor" marks only those pending-dispense medicines rejected (reason = pharmacy remarks) and removes only their `concurrent_therapy` rows.

**Nurse** — receiving uses `firstOrCreate` on `rendered_prescriptions`, so a second dispensing cycle no longer violates the unique key. Nurse reject behaves like Pharmacy's, scoped to the medicines in that cycle.

**Doctor resubmit** (`updatePrescription`) — no longer deletes and recreates every medicine. Approved medicines are never touched. Rejected ones can be edited (back to pending) or withdrawn (status 3), and new medicines can be added (pending). Restricted/for-extension Rxs re-enter IPCC; Monitored/Non-Monitored auto-approve again. Only the Rx's own doctor can resubmit.

**Delete Rx from Returned** — withdraws the rejected medicines. It ends the Rx (`status` 0) only if no approved medicine is still active.

**Details** (`getPrescriptionDetails`) — returns every medicine with its status, days requested/approved, reason and decider, plus `review_status`.

## Security fixes (all found in the audit)

1. **Role ↔ stage.** `origin` is derived from the signed-in role, not trusted from the browser. Nurse can no longer approve as IPCC; each endpoint accepts only its own role.
2. **Mass assignment.** Medicines, microbiological results and concurrent therapies are built from whitelisted fields only.
3. **SQL injection.** `getAmsSummary` / `getMonitoredSummary` use bound parameters instead of pasting dates into `whereRaw`.
4. **Server-side validation** on create, resubmit, decide, render and return-meds.
5. **Ownership.** Doctors can resubmit or delete only their own Rxs.
6. **Stored XSS.** Remarks are written into `prescription_logs` as raw HTML and shown with jQuery `.append`, so a remark containing a script runs in every reviewer's browser. User text is escaped when logs are written, and the list/row templates escape patient names, diagnoses and other free text.
7. **Concurrency.** Row locks plus the "still pending" precondition stop two reviewers from deciding the same medicine twice.
8. Bug, not security: `getMonitoredSummary` filters on a column that doesn't exist (`prescribed_medicines.date_approved`); changed to `date_rendered`.

## Frontend (keeps the new theme)

- **Doctor's Rx form** — the one-medicine checks (including `"5ork"`) are removed on home, returned and track. The medicine table has no Days column on any page: approved durations show as start/stop dates under Concurrent antimicrobial therapy, and in the "Approved · N days" badge.
- **IPCC review** — each medicine row gets Approve / Reject pills, a days-approved picker (1/3/7/14/Other) shown on Approve, and a reason box required on Reject. One "Submit decisions" button. Already-decided medicines show read-only with their badge.
- **Pharmacy / Nurse / Track / Returned** — every medicine shows a status badge (Pending, Approved · N days, Rejected · reason, Withdrawn, Dispensed). Pharmacy's availability select appears only on approved, undispensed rows.
- **Lists** — the Priority column gains the Rx-level status pill (Pending / Partially approved / Approved / Rejected).

## Tests (PHPUnit, `DatabaseTransactions`, local DB only)

`TestCase::setUp` refuses to run unless every database connection's host is `127.0.0.1`. Each test seeds its own Rx and rolls back, leaving the existing 38k records untouched. Covers the 15 requested scenarios: one and several medicines; independent days; approve-one/reject-another; aggregate status; pending stays pending; legacy Rx readable; role and ownership denial; invalid days; a stale (already-decided) decision rejected; a forced failure leaving no partial Rx; and AMS summary output.

## Rollout

1. Back up production (`mysqldump paperless_prescription`).
2. Deploy code and run `php artisan migrate`: four additive migrations, backfill included.
3. Rollback: `php artisan migrate:rollback --step=4` drops only the added columns. Restore the dump if the backfill must be undone.

## Out of scope

Per-medicine Rx type, changing type-based routing, a cap on approved days, redesigning other modules.
