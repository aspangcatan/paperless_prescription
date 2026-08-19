# Per-Medicine Days Approved & Notes — Design

## Purpose

In the per-medicine ICC review flow ([2026-08-19-per-medicine-icc-review-design.md](2026-08-19-per-medicine-icc-review-design.md)), "days approved" and "additional notes" are currently submission-level — one shared value applied to every medicine approved in that submission, collected in a confirmation dialog after the per-row Approve/Reject decisions are made. This moves both to per-medicine: each medicine gets its own days-approved count and its own notes, since different drugs on the same prescription can legitimately need different approval durations.

## Background

Current state (from the just-merged per-medicine ICC review feature): each medicine row has an Approve/Reject radio and a remarks textarea that only appears when Reject is selected (required in that case). Clicking "SUBMIT DECISIONS" opens a shared confirmation dialog (`modals/common.blade.php`'s `#remarks_modal`) containing an optional `#common_remarks` field, a canned rejection-reason picker (shown if anything was rejected), and — gated behind `@if(session('access_rights') == "ICC")`, i.e. ICC-only markup never shared with Pharmacy/Nurse — a `#days_approved_container` radio group (1/3/7/14/Other) applied once to every approved medicine in the batch. The backend (`PrescriptionController::decidePrescriptionMedications`) takes one `days_approved` value on the request, uses it to compute one `$stop_date`, and applies both to every `ConcurrentTherapy` row it creates for approved medicines.

## Changes

### Per-row UI (`resources/views/js/icc_review.blade.php`)

Each non-type-6 medicine row in `#medication_list`:
- The remarks textarea (`.medicine_remarks`) is now **always visible**, not conditionally shown on Reject. Placeholder/label reads as a general note; validation stays required-only-when-rejected, optional-when-approved.
- A new days-approved radio group (`.medicine_days_approved`, same options as today's shared version: 1 / 3 / 7 / 14 / Other-with-custom-number-input) is added to the row, visible **only** when that row's decision is Approve — hidden and its selection cleared when Reject is selected. Radio `name` attributes are per-row (`days_approved_{index}`, mirroring the existing `medicine_decision_{index}` pattern) so rows don't cross-select each other. The "Other" custom-number input in today's shared markup uses a literal `id="days_approved"` — repeated verbatim per row that would be a duplicate-id bug, so the per-row version uses a class (`.medicine_days_approved_custom`) scoped by `.closest("tr")`, not an id.
- The `.medicine_decision` change handler (currently toggling only the remarks textarea's visibility) is extended to also toggle the row's days-approved group.

### Submit-time validation (`#btn_submit` click handler)

- Remarks-required-on-reject check: unchanged.
- New check: every row with decision `approved` must have a days-approved value selected (from its radio group or its "Other" custom input) — if not, block with an alert, same pattern as the existing remarks check.

### Confirmation dialog

- Always opens on "SUBMIT DECISIONS" (per confirmed decision — even when there's nothing left to fill in, it's a final confirm step).
- No longer shows or reads `#common_remarks` for this flow (the per-row remarks fully replace it) or `#days_approved_container` (per-row radios fully replace it, and this block was ICC-only to begin with — see below).
- Still conditionally shows the canned rejection-reason picker (`#reject_outcome_container`) when at least one medicine was rejected, exactly as today.

### Cleanup: remove now-dead shared markup

`modals/common.blade.php`'s `#days_approved_container` block is removed entirely. It was already gated to ICC-only sessions and had exactly one consumer (the submission-level days-approved flow being replaced here) — after this change nothing renders or reads it, so it's deleted rather than left as dead code.

### Backend (`PrescriptionController::decidePrescriptionMedications`)

Payload shape changes: each entry in `medications` gains its own `days_approved` field (required when `decision == 'approved'`, ignored otherwise). The submission-level `days_approved` request field is removed.

Server logic:
- The up-front validation loop gains a per-medication check: if `decision == 'approved'` and `days_approved` is empty, reject the whole request (same "validate everything before writing anything" pattern already used for the remarks-required-on-reject check).
- The `$stop_date` computation moves from once-per-submission (before the per-medicine loop) to inside the per-medicine loop, computed from that medicine's own `days_approved`.
- `ConcurrentTherapy.days_approved` and `.stop_date` use the per-medicine values.
- Everything else is unchanged: ownership checks, the `status == 0` precondition, `outcome` staying submission-level, the type-4/7 `for_switch` handling (which uses today's date, not `days_approved`, and still runs at most once per submission).

## Out of Scope

- Pharmacy/Nurse are unaffected — `#days_approved_container` was never shared with them, and this change doesn't touch their own approve/render flows.
- Type 6 is unaffected — it never uses per-medicine decisions or this dialog's ICC-specific content.
- The canned rejection-outcome picker stays submission-level, unchanged from the original per-medicine ICC review design.
