// Shared by the prescription pages: escaping, per-medicine status badges and medicine rows.
// Medicine rows are read by class (.med-*) and data attributes, never by cell position.

const MEDICINE_STATUS = {PENDING: 0, APPROVED: 1, REJECTED: 2, WITHDRAWN: 3};
const REVIEW_STATUS_LABELS = ["Pending", "Partially approved", "Approved", "Rejected"];
const DAYS_APPROVED_CHOICES = [1, 3, 7, 14];

function escapeHtml(value) {
    if (value === null || value === undefined) {
        return "";
    }
    return String(value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
}

// For list cells the page scripts read back with .text(): null and undefined stay "null" / "undefined",
// exactly as the unescaped templates produced, so existing == "null" checks keep working.
function escapeCell(value) {
    return escapeHtml(String(value));
}

function reviewStatusPill(review_status) {
    if (review_status === null || review_status === undefined || review_status === "") {
        return "";
    }
    const index = parseInt(review_status, 10);
    const tone = ["pending", "partial", "approved", "rejected"][index];
    return "<span class='review-pill review-" + tone + "'>" + REVIEW_STATUS_LABELS[index] + "</span>";
}

function medicineStatusBadge(med) {
    const status = parseInt(med.status, 10);
    let badge = "";
    if (status === MEDICINE_STATUS.APPROVED) {
        badge = "<span class='med-badge med-approved'><i class='bi bi-check-circle-fill'></i> Approved" +
            (med.days_approved ? " · " + escapeHtml(med.days_approved) + " day" + (med.days_approved == 1 ? "" : "s") : "") + "</span>";
        if (med.date_rendered) {
            badge += " <span class='med-badge med-dispensed'><i class='bi bi-capsule'></i> Dispensed</span>";
        }
    } else if (status === MEDICINE_STATUS.REJECTED) {
        badge = "<span class='med-badge med-rejected'><i class='bi bi-x-circle-fill'></i> Rejected</span>";
    } else if (status === MEDICINE_STATUS.WITHDRAWN) {
        badge = "<span class='med-badge med-withdrawn'><i class='bi bi-slash-circle'></i> Withdrawn</span>";
    } else {
        badge = "<span class='med-badge med-pending'><i class='bi bi-hourglass-split'></i> Pending</span>";
    }
    return badge;
}

// Reason or note under the medicine name, with who decided it.
function medicineDecisionNote(med) {
    const status = parseInt(med.status, 10);
    if (!med.decision_remarks && !med.decided_by_name) {
        return "";
    }
    const label = (status === MEDICINE_STATUS.REJECTED) ? "Reason" : "Note";
    let note = "";
    if (med.decision_remarks) {
        note += "<span class='fw-semibold'>" + label + ":</span> " + escapeHtml(med.decision_remarks);
    }
    if (med.decided_by_name && (status === MEDICINE_STATUS.APPROVED || status === MEDICINE_STATUS.REJECTED)) {
        note += (note ? " · " : "") + "by " + escapeHtml(med.decided_by_name);
    }
    return "<div class='med-note " + (status === MEDICINE_STATUS.REJECTED ? "med-note-rejected" : "") + "'>" + note + "</div>";
}

// Read-only medicine row with its status. actionHtml, when given, follows the badge in the last cell.
function infoMedicineRow(med, actionHtml) {
    return "<tr class='med-row' data-id='" + escapeHtml(med.id) + "' data-status='" + escapeHtml(med.status) + "'>" +
        "<td><div class='fw-semibold med-description'>" + escapeHtml(med.description) + "</div>" + medicineDecisionNote(med) + "</td>" +
        "<td>" + escapeHtml(med.dosage_strength) + " " + escapeHtml(med.dosage_unit) + "</td>" +
        "<td>" + escapeHtml(med.unit) + "</td>" +
        "<td>" + escapeHtml(med.frequency) + "</td>" +
        "<td colspan='2' class='med-status-cell'>" + medicineStatusBadge(med) + (actionHtml || "") + "</td>" +
        "</tr>";
}

function optionList(values, selected) {
    return values.map(value => "<option" + (value == selected ? " selected" : "") + ">" + escapeHtml(value) + "</option>").join("");
}

// Editable row on the doctor's form: new medicines (no id) and rejected ones being corrected (with id).
function doctorMedicineRow(med) {
    const is_rejected = parseInt(med.status, 10) === MEDICINE_STATUS.REJECTED;
    return "<tr class='med-row med-editable' data-id='" + escapeHtml(med.id || "") + "'>" +
        "<td><div class='fw-semibold med-description'>" + escapeHtml(med.description) + "</div>" +
        (is_rejected ? medicineDecisionNote(med) : "") + "</td>" +
        "<td><div class='d-flex gap-1'>" +
        "<input class='form-control text-center med-strength' type='number' min='0' step='any' value='" + escapeHtml(med.dosage_strength || "") + "' aria-label='Strength'/>" +
        "<select class='form-select med-strength-unit' aria-label='Strength unit'>" + optionList(STRENGTH_UNIT, med.dosage_unit) + "</select>" +
        "</div></td>" +
        "<td class='med-unit'>" + escapeHtml(med.unit) + "</td>" +
        "<td><select class='form-select med-frequency' aria-label='Frequency'>" + optionList([""].concat(FREQUENCIES), med.frequency) + "</select></td>" +
        "<td colspan='2' class='text-nowrap'>" +
        (is_rejected ? medicineStatusBadge(med) + " " : "") +
        "<button type='button' class='btn btn-sm btn-light' onclick='editMedicine(this)' title='Change medicine'><i class='bi bi-arrow-left-right'></i></button> " +
        "<button type='button' class='btn btn-sm btn-light text-danger' onclick='deleteMedicine(this)' title='Remove medicine'><i class='bi bi-trash'></i></button>" +
        "</td></tr>";
}

// Reads every editable row. Returns {medications, errors}; each error names its medicine.
function readDoctorMedicines() {
    const medications = [];
    const errors = [];
    $("#medication_list tr.med-editable").each(function () {
        const row = $(this);
        const description = row.find(".med-description").text().trim();
        const dosage_strength = row.find(".med-strength").val().trim();
        const dosage_unit = row.find(".med-strength-unit").val();
        const frequency = row.find(".med-frequency").val();
        const before = errors.length;

        if (dosage_strength === "") errors.push(description + ": dosage strength amount is required");
        if (!dosage_unit) errors.push(description + ": dosage strength unit is required");
        if (!frequency) errors.push(description + ": frequency is required");

        row.find(".med-strength").toggleClass("is-invalid", dosage_strength === "");
        row.find(".med-strength-unit").toggleClass("is-invalid", !dosage_unit);
        row.find(".med-frequency").toggleClass("is-invalid", !frequency);

        if (errors.length === before) {
            medications.push({
                id: row.data("id") || null,
                description: description,
                unit: row.find(".med-unit").text().trim(),
                dosage_strength: dosage_strength,
                dosage_unit: dosage_unit,
                frequency: frequency
            });
        }
    });
    return {medications: medications, errors: errors};
}

const QUICK_REJECT_REASONS = ["Wrong indication", "Double request", "Incomplete data"];

// IPCC decision strip under a pending medicine: approve with its own days, or reject with a reason.
function iccDecisionRow(med) {
    const id = escapeHtml(med.id);
    const days = DAYS_APPROVED_CHOICES.map(day =>
        "<div class='choice'><input type='radio' name='days_" + id + "' id='days_" + id + "_" + day + "' value='" + day + "'>" +
        "<label for='days_" + id + "_" + day + "'>" + day + "</label></div>").join("");
    const reasons = QUICK_REJECT_REASONS.map(reason =>
        "<button type='button' class='btn btn-sm btn-light quick-reason'>" + escapeHtml(reason) + "</button>").join(" ");

    return "<tr class='decision-row' data-id='" + id + "'><td colspan='6'>" +
        "<div class='decision-grid'>" +
        "<div class='decision-choice choice-group'>" +
        "<div class='choice'><input type='radio' name='decision_" + id + "' id='approve_" + id + "' value='approved'><label for='approve_" + id + "'><i class='bi bi-check-lg'></i> Approve</label></div>" +
        "<div class='choice choice-reject'><input type='radio' name='decision_" + id + "' id='reject_" + id + "' value='rejected'><label for='reject_" + id + "'><i class='bi bi-x-lg'></i> Reject</label></div>" +
        "</div>" +
        "<div class='decision-days d-none'><span class='form-label mb-0'>Days approved</span><div class='choice-group'>" + days +
        "<div class='choice choice-other'><input type='radio' name='days_" + id + "' id='days_" + id + "_other' value='other'>" +
        "<label for='days_" + id + "_other'>Other</label>" +
        "<input type='number' min='1' step='1' class='form-control form-control-sm days-other' placeholder='Days' aria-label='Other number of days'></div>" +
        "</div></div>" +
        "<div class='decision-remarks d-none'>" +
        "<div class='quick-reasons d-none'>" + reasons + "</div>" +
        "<textarea class='form-control form-control-sm decision-text' rows='2'></textarea>" +
        "</div>" +
        "</div></td></tr>";
}

// Shows the days picker for Approve, a required reason for Reject, an optional note for Approve.
function bindIccDecisionRows() {
    $("#medication_list").off(".decision")
        .on("change.decision", ".decision-row input[name^='decision_']", function () {
            const row = $(this).closest(".decision-row");
            const approved = $(this).val() === "approved";
            row.toggleClass("is-approved", approved).toggleClass("is-rejected", !approved);
            row.find(".decision-days").toggleClass("d-none", !approved);
            row.find(".decision-remarks").removeClass("d-none");
            row.find(".quick-reasons").toggleClass("d-none", approved);
            row.find(".decision-text").attr("placeholder", approved ? "Note for the pharmacy and doctor (optional)" : "Reason for rejecting this medicine (required)");
        })
        .on("click.decision", ".quick-reason", function () {
            $(this).closest(".decision-row").find(".decision-text").val($(this).text()).removeClass("is-invalid");
        })
        .on("focus.decision", ".days-other", function () {
            $(this).closest(".choice-other").find("input[type='radio']").prop("checked", true);
        });
}

// Collects the decided rows. Undecided medicines are left out and stay pending.
function readIccDecisions() {
    const decisions = [];
    const errors = [];
    $("#medication_list .decision-row").each(function () {
        const row = $(this);
        const id = row.data("id");
        const decision = row.find("input[name='decision_" + id + "']:checked").val();
        const name = row.prev(".med-row").find(".med-description").text().trim();
        const text = row.find(".decision-text").val().trim();
        row.find(".is-invalid").removeClass("is-invalid");
        if (!decision) {
            return;
        }

        const item = {id: id, decision: decision, remarks: text};
        if (decision === "approved") {
            let days = row.find("input[name='days_" + id + "']:checked").val();
            if (days === "other") {
                days = row.find(".days-other").val().trim();
            }
            if (!/^\d+$/.test(days || "") || parseInt(days, 10) < 1) {
                errors.push(name + ": choose the number of days approved");
                row.find(".days-other").addClass("is-invalid");
            }
            item.days_approved = parseInt(days, 10);
        } else if (text === "") {
            errors.push(name + ": a rejection reason is required");
            row.find(".decision-text").addClass("is-invalid");
        }
        decisions.push(item);
    });
    return {decisions: decisions, errors: errors};
}

// Activity log row. Remarks arrive as plain text with the decision the log recorded.
function activityLogRow(it) {
    const badges = {
        approved: "<span class='med-badge med-approved'>Approved</span> ",
        rejected: "<span class='med-badge med-rejected'>Rejected</span> ",
        cycle_end: "<span class='med-badge med-withdrawn'>Cycle end</span> "
    };
    const name = [it.fname, it.mname, it.lname].filter(Boolean).map(escapeHtml).join(" ");
    return "<tr>" +
        "<td>" + name + " <span class='badge bg-primary ms-2'>" + escapeHtml(it.origin) + "</span></td>" +
        "<td>" + (badges[it.decision] || "") + escapeHtml(it.remarks) + "</td>" +
        "<td><div class='badge bg-success p-1'>" + escapeHtml(it.released_to) + "</div></td>" +
        "<td>" + moment(it.date_forwarded).format("MM/DD/YYYY hh:mm:ss A") + "</td>" +
        "</tr>";
}
