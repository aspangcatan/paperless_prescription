# Per-Medicine ICC Review — Design

## Purpose

Let a doctor put multiple medicines on one antimicrobial prescription, and let ICC approve some of those medicines while rejecting others in the same review — instead of today's all-or-nothing model, where the entire prescription (every medicine on it) lives or dies as a single unit with one shared remark. Approved medicines proceed to Pharmacy immediately; rejected medicines go back to the doctor for correction, without touching the medicines that already passed.

## Background

Investigation of the current codebase found:

- The backend already supports multiple medicines per prescription (`PrescribedMedicine::insert($medications)` bulk-inserts an arbitrary-length array, `getPrescriptionDetails` already returns the full list). The "only one medication" limit is purely front-end, enforced inconsistently — and buggily (a `"5ork"` typo in `resources/views/js/home.blade.php:1641`) — in three separate places: `home.blade.php:708-711`, `home.blade.php:1638-1645`, and `resources/views/js/returned_prescription.blade.php:766-771`.
- There is currently **no per-medicine status or remarks anywhere** in the approval flow. State is modeled entirely as row-existence in five queue tables (`icc_reviews`, `pharmacy_reviews`, `nurse_reviews`, `returned_prescriptions`, `rendered_prescriptions`), each with a `prescription_id` unique constraint — a prescription is always in exactly one queue, moved by deleting the row in one table and inserting a row in the next. Approve/reject actions (`PrescriptionController::approvePrescription`/`rejectPrescription`) act on the whole `prescription_id` and write a single `prescription_logs` row with one shared `remarks` string.
- The one place with genuine per-medicine data today is Pharmacy's render step (`quantity_rendered`/`quantity_returned` on `prescribed_medicines`), but even there the availability check is all-or-nothing: one short medicine blocks the entire render.
- `prescriptions.type` (Restricted/Monitored/Non-Monitored/etc.) governs whole-prescription routing (whether ICC is skipped, multi-queue cancellation for type 6) and has no per-medicine equivalent — this feature doesn't change that.

## Allowing Multiple Medicines

The "max 1 medicine" restriction exists in three places today, all front-end, all inconsistent (see Background) — and it likely existed *because* the system couldn't previously handle rejecting one medicine out of several cleanly. Now that ICC can decide per medicine, that rationale goes away: the restriction is removed entirely, for every prescription type that reaches ICC (and for types 1/5/8, which skip ICC and go straight to Pharmacy, since Pharmacy already handles multiple medicines per prescription fine today via its existing per-row `quantity_rendered` flow).

Concretely: delete the count checks at `home.blade.php:708-711`, `home.blade.php:1638-1645` (including the `"5ork"` typo — the whole block goes, not just the typo), and `returned_prescription.blade.php:766-771` (and its mirrored submit-time check). No new UI is needed to *add* multiple medicines — `appendMedicine()` already adds a row to `#medication_list` per call and the submit path already sends the full array; the count checks were the only thing preventing more than one row from ever being added.

Type `6`'s "cancel other pending prescriptions" reject branch is unaffected — it operates on `prescription_id`/patient/physician, not on medicine count, and continues to work the same way regardless of how many medicines are on each prescription involved.

## Scope

- **ICC review only.** Pharmacy (dispensing) and Nurse (receiving) stay whole-prescription for this pass — they continue to act on whatever set of medicines is currently theirs to act on (see "Downstream filtering" below), just with awareness that the set can now be a subset of a prescription's medicines rather than always all of them.
- Decisions are made **one review submission at a time**: the ICC queue list still shows one row per prescription; opening it shows every medicine with its own Approve/Reject + remarks control, and one Submit posts all decisions together.
- Rejected medicines stay attached to the **same prescription** — the doctor edits and resubmits just those; approved medicines are untouched and already moving through Pharmacy on their own.
- Types that skip ICC entirely (1, 5, 8) are unaffected — there's no ICC step to split at.
- Out of scope: Pharmacy/Nurse per-medicine independence, any "mixed status" visual indicator on list views (only visible once a prescription's detail is opened), any change to type-based routing.

## Schema Changes

1. **`prescribed_medicines`**: add `status` (tinyint, not null, default `0`).
   - `0` = pending (not yet decided by ICC, or freshly resubmitted after rejection)
   - `1` = approved
   - `2` = rejected

2. **`prescription_logs`**: add `prescribed_medicine_id` (unsigned bigint, nullable, FK → `prescribed_medicines.id`, `nullOnDelete()`).
   - Set on log rows written for a per-medicine ICC decision.
   - Left `null` for every existing log type (prescription creation, Pharmacy render, Nurse receipt, whole-prescription reject) — no existing log-reading code needs to change.

No other table changes. `icc_reviews`, `pharmacy_reviews`, `nurse_reviews`, `returned_prescriptions`, `rendered_prescriptions` keep their existing shape (`prescription_id` unique per table). What changes is application logic no longer assuming a `prescription_id` exists in only one queue table at a time — after a split decision, the same prescription can have a row in `pharmacy_reviews` (its approved medicines) and a row in `returned_prescriptions` (its rejected ones) simultaneously. Each table's own unique constraint still holds; nothing enforced cross-table exclusivity, that was only ever an application convention.

## Backend Changes

### New/consolidated endpoint: ICC decision

`POST /icc/decide_prescription` (replaces ICC's use of `/approve_prescription` and `/reject_prescription` — Pharmacy and Nurse continue using those routes for their existing whole-prescription actions).

Payload:
```json
{
  "id": 123,
  "type": "2",
  "hospital_id": "000123",
  "days_approved": 7,
  "outcome": null,
  "medications": [
    { "id": 45, "decision": "approved", "remarks": "" },
    { "id": 46, "decision": "rejected", "remarks": "Dosage exceeds weight-based max" }
  ]
}
```

`days_approved` and `outcome` stay **submission-level, not per-medicine** — same mechanic as today: `days_approved` drives the `ConcurrentTherapy.stop_date` calculation applied to every medicine that ends up `approved` in this submission; `outcome` is the existing optional canned-reason classification, written once to `prescriptions.outcome` if the submission contains at least one rejection (independent of which specific medicine(s) were rejected). Per-medicine granularity only applies to the approve/reject decision itself and its `remarks`.

Server-side, in one transaction:
1. Validate every medicine has a `decision` of `approved`/`rejected`, and every `rejected` decision has non-empty `remarks`. Any violation → `{code: 500, message: ...}`, nothing written.
2. For each medicine: set `prescribed_medicines.status`, and insert one `prescription_logs` row with `prescribed_medicine_id` set and that medicine's `remarks`.
3. `ConcurrentTherapy` bookkeeping (currently written for every medicine on whole-prescription ICC approval, using `days_approved` to compute `stop_date`) now only fires for medicines that ended up `approved`.
4. The existing type `4`/`7` (intervention/switch) `ConcurrentTherapy.for_switch` stop-date update — currently dead in practice since both types are blocked at prescription creation, but still reachable for any pre-existing records — runs at most once per submission if any medicine is approved, exactly as it does today (it targets one specific prior concurrent-therapy record per prescription, not one per medicine).
5. If at least one medicine is `approved` → create a `PharmacyReview` row for this `prescription_id` if one doesn't already exist.
6. If at least one medicine is `rejected` → write `outcome` to `prescriptions.outcome` if provided, and create a `ReturnedPrescription` row for this `prescription_id` if one doesn't already exist.
7. Force-delete the `IccReview` row for this `prescription_id` — this review action is complete.

**Type `6` (the "cancel all pending prescriptions for this patient/physician" flow) is excluded from this endpoint entirely.** It's a fundamentally different action — a cascading cancellation across *multiple* prescriptions, not a per-medicine clinical review of one — and keeps using the existing `/reject_prescription` (origin `ICC`) whole-prescription path and its own confirmation dialog, completely untouched by this feature.

### Downstream medicine-list filtering

Every place that reads a prescription's medicine list for a specific *role's* context filters by `status`:
- Pharmacy's dispensing view → `WHERE status = 1` (approved only).
- ICC's own queue, when re-reviewing a resubmission → `WHERE status = 0` (pending only — a fresh submission's medicines are all pending by default, so this is naturally the full list on first review too).
- The **doctor's own** Returned-prescription detail view is the one exception: it shows **all** medicines regardless of status (approved ones read-only, rejected ones editable) — see Frontend section.

### Doctor resubmission (`ReturnedPrescriptionController::updatePrescription`)

Today this force-deletes *every* `prescribed_medicines` row for the prescription and rebuilds from the submitted form. That changes to: only touch medicines currently `status = 2` (rejected) — update their fields from the resubmitted form, reset `status` to `0` — then create a new `IccReview` row for this `prescription_id` (re-entering the ICC queue). Medicines already `approved` (sitting with Pharmacy) are never touched by this action.

## Frontend Changes

### ICC review (`resources/views/js/icc_review.blade.php`)

ICC, Pharmacy, and Nurse each build their own `#medication_list` row HTML via their own `getMedications(id)` function (only the outer modal shell in `resources/views/modals/prescription.blade.php` is shared) — so this change is confined to ICC's row-building function and doesn't touch Pharmacy's or Nurse's rendering.

Today, approving/rejecting is: click top-level `#btn_submit` ("APPROVE") or `#btn_reject`, which opens `#remarks_modal` to collect one shared `remarks` textarea plus (for approve) a `days_approved` choice or (for reject) a canned `outcome` pick, then POSTs the whole decision. That flow is **kept for type `6`** exactly as-is (its own `#btn_reject` → `warning_modal` → cascading cancel path is untouched). For every other type:

- Each medicine row in `#medication_list` gets its own Approve/Reject control and a remarks textarea (shown/required only when that row is set to Reject).
- The top-level `#btn_submit`/`#btn_reject` pair is replaced by one "Submit Decisions" button. Clicking it validates every row has a decision (and rejected rows have remarks), then opens `#remarks_modal` — now scoped to just the two submission-level fields that still apply across the batch: `days_approved` (shown only if at least one row is Approved) and the canned `outcome` pick (shown only if at least one row is Rejected). Confirming there posts everything to `/icc/decide_prescription`.
- While rewriting this region, remove the dead `updated_quantity` line at `icc_review.blade.php:226` — it only ever reads the first medicine row (`tr:eq(0)`) and nothing server-side ever consumes it.

### Doctor's Returned-prescription view (`resources/views/js/returned_prescription.blade.php`)

This view's `#medication_list` is the same editable row-builder used for creating a new prescription (inputs for dosage/quantity/frequency, add/remove rows) — today it's populated with every medicine on the prescription, all editable. That changes to: fetch **all** medicines (approved + rejected), render `status = approved` rows **read-only** (no inputs, just the medicine's info plus a badge — excluded from the resubmit payload) and `status = rejected` rows in the existing **editable** row form (included in the resubmit payload, same as all rows are today), so the doctor sees full context on their prescription while only being able to touch what actually needs fixing.

## Error Handling Summary

- Missing decision or missing rejection-remarks on submit → client-side block, no request sent.
- Same validation re-checked server-side before any write — a rejected medicine without remarks returns `{code: 500, ...}`, nothing partially written.
- No new network/race-condition handling beyond what the app already does elsewhere (single-clinic internal tool, not worth over-engineering).

## Out of Scope

- Pharmacy and Nurse remain whole-prescription for this pass.
- No visual "mixed status" indicator on the ICC/Pharmacy/doctor *list* views — only visible when a prescription's detail is opened.
- No change to `type`-based routing (ICC-skip for 1/5/8, multi-queue for 6).
- No cap or minimum introduced on how many medicines a prescription can hold — removing the restriction (see "Allowing Multiple Medicines" above) is uncapped, matching what the backend already accepts today.
