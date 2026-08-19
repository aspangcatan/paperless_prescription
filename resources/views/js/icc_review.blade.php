<script src="js/moment.min.js"></script>
<script src="js/constant.js?v={{ date('ymdhis') }}"></script>
<script src="js/websocket.js?v={{ date('ymdhis') }}"></script>
<script>
    const prescription_modal = new bootstrap.Modal(document.getElementById('prescription_modal'));
    const success_modal = new bootstrap.Modal(document.getElementById('success_modal'));
    const error_modal = new bootstrap.Modal(document.getElementById('error_modal'));
    const warning_modal = new bootstrap.Modal(document.getElementById('warning_modal'));
    const remarks_modal = new bootstrap.Modal(document.getElementById('remarks_modal'));
    let button_clicked = "";
    let prescription_id;
    let page = 0;
    let definitive_details = {};
    $(document).ready(function () {
        observe("ICC", function (snapshot) {
            if (snapshot.val()) {
                notification_counter++;
                toastr.success('New Request')
                speechSynthesis.speak(speaker);
                $("#btn_notification").removeClass("btn-secondary");
                $("#btn_notification").addClass("btn-danger");
                $("#btn_notification").addClass("blink");
                $("#btn_notification").find("span").text(notification_counter);
            }
        });

        $("input[name='reject_outcome']").change(function () {
            const new_value = $(this).prop("checked");
            $("input[name='reject_outcome']").prop("checked", false);
            $(this).prop("checked", new_value);
            if (new_value) {
                $("#common_remarks").val($(this).next().text().trim());
                $("#common_remarks").prop("disabled", true);
            } else {
                $("#common_remarks").val("");
                $("#common_remarks").prop("disabled", false);
            }
        });

        STATIONS.forEach(it => {
            $("#form_station").append("<option>" + it + "</option>");
        });

        $("#filter_date").focusin(function () {
            $(this).attr('type', 'date')
        });

        $("#filter_date").focusout(function () {
            $(this).attr('type', 'text')
        });

        $("#btn_next").click(function () {
            page++;
            getPrescriptions();
            $("#btn_prev").removeClass("d-none");
        });

        $("#btn_prev").click(function () {
            if (page == 0) return;
            if (page == 1) {
                $("#btn_prev").addClass("d-none");
            }
            page--;
            getPrescriptions();
        });

        $("#form_prescription_type").change(function () {
            $("#intervention_form_container").addClass("d-none");
            $("#indication_use_container").addClass("d-none");
            $("#transaminases_container").addClass("d-none");
            $("#microbiological_container").removeClass("d-none");
            $("#sgpt_container").removeClass("d-none");
            $("#sgot_container").removeClass("d-none");
            $("#days_requested_container").removeClass("d-none");
            $("#concurrent_table tbody tr").removeClass("pointer");
            $("#concurrent_table tbody tr").removeClass("active");
            $(".ams_container").removeClass("d-none");
            $("#medication_container").removeClass("d-none")
            $("#medication_list").empty();
            $("#medicine_list").empty();
            $("#additional_labs_container").addClass("d-none");
            $("#form_remarks").removeClass("d-none");
            $(".cancellation_container").addClass("d-none");
            switch ($(this).val()) {
                case "":
                    $(".ams_container").addClass("d-none");
                    break;
                case "1":
                    $(".ams_container").addClass("d-none");
                    break;
                case "2":
                    $("#indication_use_container").removeClass("d-none");
                    $("#transaminases_container").removeClass("d-none");
                    $("#sgpt_container").addClass("d-none");
                    $("#sgot_container").addClass("d-none");
                    $("#days_requested_container").addClass("d-none");
                    $("#additional_labs_container").removeClass("d-none");
                    break;
                case "5":
                    $("#transaminases_container").removeClass("d-none");
                    $("#sgpt_container").addClass("d-none");
                    $("#sgot_container").addClass("d-none");
                    $("#days_requested_container").addClass("d-none");
                    $("#additional_labs_container").removeClass("d-none");
                    break
                case "4":
                    //INTERVENTION ON GOING
                    $("#intervention_form_container").removeClass("d-none");
                    $("#microbiological_container").addClass("d-none");
                    $("#days_requested_container").addClass("d-none");
                    $("#concurrent_table tbody tr").addClass("pointer");
                    break;
                case "6":
                    //HIDE ALL EXCEPT REMARKS
                    $(".cancellation_container").removeClass("d-none");
                    $("#form_remarks").addClass("d-none");
                    $("#intervention_form_container").addClass("d-none");
                    $("#indication_use_container").addClass("d-none");
                    $("#transaminases_container").addClass("d-none");
                    $("#microbiological_container").addClass("d-none");
                    $("#sgpt_container").addClass("d-none");
                    $("#sgot_container").addClass("d-none");
                    $("#days_requested_container").addClass("d-none");
                    $("#concurrent_table tbody tr").addClass("pointer");
                    $("#concurrent_table tbody tr").addClass("active");
                    $(".ams_container").addClass("d-none");
                    $("#medication_container").addClass("d-none")
                    break;
                case "7":
                    $("#concurrent_table tbody tr").addClass("pointer");
                    break;
                case "8":
                    $("#days_requested_container").addClass("d-none");
                    $("#additional_labs_container").removeClass("d-none");
                    break;
            }
        });

        $("#btn_search_prescription").click(function () {
            page = 0;
            getPrescriptions();
        });

        $("#btn_notification").click(function () {
            $(this).find("span").text("");
            $(this).addClass("btn-secondary");
            $(this).removeClass("btn-danger");
            $(this).removeClass("blink");
            notification_counter = 0;
            getPrescriptions();
        });

        $("#btn_submit").click(function () {
            if ($("#form_prescription_type").val() == "6") {
                return;
            }

            const rows = $("#medication_list tr[data-medicine-id]");
            if (rows.length < 1) {
                alert("There are no medicines to decide on");
                return;
            }

            let has_approved = false;
            let has_rejected = false;
            let is_valid = true;

            rows.each(function () {
                const decision = $(this).find(".medicine_decision:checked").val();
                const remarks = $(this).find(".medicine_remarks").val().trim();
                if (decision === "rejected") {
                    has_rejected = true;
                    if (remarks === "") {
                        is_valid = false;
                    }
                } else {
                    has_approved = true;
                }
            });

            if (!is_valid) {
                alert("Please specify remarks for every rejected medicine");
                return;
            }

            button_clicked = "SUBMIT_DECISIONS";
            prescription_id = $(this).val();

            $("#remarks_message").text("Additional notes (optional)");
            $("#common_remarks").val("");
            $("#common_remarks").prop("required", false);
            $("#reject_outcome_container").toggleClass("d-none", !has_rejected);
            $("input[name='reject_outcome']").prop("checked", false).change();
            $("#days_approved_container").toggleClass("d-none", !has_approved);
            remarks_modal.toggle();
        });

        $("#i_agree").change(function () {
            if ($(this).is(":checked")) {
                $("#btn_proceed_cancel").prop("disabled", false);
            } else {
                $("#btn_proceed_cancel").prop("disabled", true);
            }
        });

        $("#btn_reject").click(function () {
            button_clicked = $(this).text();
            prescription_id = $(this).val();

            //DISPLAY WARNING DIALOG
            if ($("#form_prescription_type").val() == "6") {
                warning_modal.toggle();
                $("#i_agree").prop("checked", false).change();
                $("#btn_proceed_cancel").prop("disabled", true);
                return;
            }

            $("#days_approved_container").addClass("d-none");
            $("#reject_outcome_container").removeClass("d-none");
            $("#remarks_message").text("Reason for rejection");
            $("#common_remarks").val("");
            $("input[name='reject_outcome']").prop("checked", false).change();
            remarks_modal.toggle();
        });

        $("#btn_proceed_cancel").click(function () {
            const params = {
                id: prescription_id,
                hospital_id: $("#form_hospital_id").text().trim(),
                type: $("#form_prescription_type").val(),
                origin: "ICC",
                outcome: null,
                remarks: "Prescriber wishes to cancel pending medications"
            }

            warning_modal.toggle();
            rejectPrescription(params);
        });

        $("#btn_proceed").click(function () {
            const remarks = $("#common_remarks").val().trim();
            const outcome = $("input[name='reject_outcome']:checked").next().text().trim();
            if (remarks == "" && button_clicked != "SUBMIT_DECISIONS") {
                alert("Please specify remarks or instructions for the receiver");
                return;
            }

            const params = {
                id: prescription_id,
                hospital_id: $("#form_hospital_id").text().trim(),
                type: $("#form_prescription_type").val(),
                origin: "ICC",
                outcome: (outcome == "") ? null : outcome,
                remarks: remarks
            }

            if (button_clicked == "SUBMIT_DECISIONS") {
                const medications = [];
                let has_approved = false;

                $("#medication_list tr[data-medicine-id]").each(function () {
                    const decision = $(this).find(".medicine_decision:checked").val();
                    if (decision === "approved") {
                        has_approved = true;
                    }
                    medications.push({
                        id: $(this).data("medicine-id"),
                        decision: decision,
                        remarks: $(this).find(".medicine_remarks").val().trim()
                    });
                });

                let days_approved = "";
                if (has_approved) {
                    days_approved = $("input[name='days_approved']:checked").next().text();
                    if (days_approved == "") {
                        alert("Please specify number of days");
                        return;
                    }
                    if (isNaN(days_approved)) {
                        days_approved = $("#days_approved").val();
                    }
                    if (days_approved == "" || days_approved < 1) {
                        alert("Please specify number of days");
                        return;
                    }
                }

                params.medications = medications;
                params.days_approved = days_approved;
                decidePrescriptionMedications(params);
            } else if (button_clicked == "REJECT") {
                rejectPrescription(params);
            }
        });

        getPrescriptions();
    });

    function viewPrescription(id) {
        prescription_id = id;
        $("#btn_view_cart").addClass("d-none");
        $("#btn_add_microbiological").addClass("d-none");
        $("#btn_add_concurrent").addClass("d-none");
        $("#btn_submit").val(id);
        $("#btn_reject").val(id);
        $("#btn_submit").text("APPROVE");
        $("#form_remarks").addClass("d-none");
        $("#transaction_container").removeClass("d-none");

        const urgency = $("#prescription_id_" + id + " td:eq(0)").text();
        const hospital_id = $("#prescription_id_" + id + " td:eq(1)").text();
        const name = $("#prescription_id_" + id + " td:eq(2)").text();
        const station = $("#prescription_id_" + id + " td:eq(3)").text();
        const created_at = $("#prescription_id_" + id + " td:eq(4)").text();
        const sex = $("#prescription_id_" + id + " td:eq(5)").text();
        const birthdate = $("#prescription_id_" + id + " td:eq(6)").text();
        const ward = $("#prescription_id_" + id + " td:eq(7)").text();
        const bed_no = $("#prescription_id_" + id + " td:eq(8)").text();
        const type = $("#prescription_id_" + id + " td:eq(9)").text();
        const drug_allergy = $("#prescription_id_" + id + " td:eq(10)").text();
        const weight = $("#prescription_id_" + id + " td:eq(11)").text();
        const height = $("#prescription_id_" + id + " td:eq(12)").text();
        const serum_creatinine = $("#prescription_id_" + id + " td:eq(13)").text();
        const creatinine_clearance = $("#prescription_id_" + id + " td:eq(14)").text();
        const transaminases = $("#prescription_id_" + id + " td:eq(15)").text();
        const sgpt = ($("#prescription_id_" + id + " td:eq(16)").text().trim() == "null") ? "" : $("#prescription_id_" + id + " td:eq(16)").text();
        const sgot = ($("#prescription_id_" + id + " td:eq(17)").text().trim() == "null") ? "" : $("#prescription_id_" + id + " td:eq(17)").text();
        const days_requested = $("#prescription_id_" + id + " td:eq(18)").text();
        const attending_physician = $("#prescription_id_" + id + " td:eq(19)").text();
        const additional_labs = $("#prescription_id_" + id + " td:eq(20)").text();
        const outcome = $("#prescription_id_" + id + " td:eq(21)").text();
        const diagnosis = $("#prescription_id_" + id + " td:eq(22)").text();
        const for_extension = $("#prescription_id_" + id + " td:eq(23)").text();
        const age = moment(created_at).diff(moment(birthdate), 'years');

        clearForms();

        $("#form_hospital_id").text(hospital_id);
        $("#form_patient").text(name);
        $("#form_birthdate").text(birthdate);
        $("#form_age").text(age);
        $("#form_age").text(formatFullAge(moment(birthdate).format("MM/DD/YYYY"),moment(created_at)));
        $("#form_sex").text(sex);
        $("#form_station").val(station);
        $("#form_ward").val(ward);
        $("#form_bed_no").val(bed_no);
        $("#form_attending_physician").val(attending_physician);
        $("#checkbox_urgent").prop("checked", (urgency == "Urgent"));
        $("#form_for_extension").val(for_extension);
        $("#form_prescription_type").val(type).change();
        $("#form_drug_allergy").val(drug_allergy);
        $("#form_weight").val(weight);
        $("#form_height").val(height);
        $("#form_serum_creatinine").val(serum_creatinine);
        $("#form_creatinine_clearance").val(creatinine_clearance);
        $("#form_transaminases").val(transaminases);
        $("#form_sgpt").val(sgpt);
        $("#form_sgot").val(sgot);
        $("#form_days_requested").val(days_requested);
        $("#additional_labs").val(additional_labs);
        $("#diagnosis").val(diagnosis);

        $("#prescription_modal input").prop("disabled", true);
        $("#prescription_modal input[type='checkbox']").prop("disabled", true);
        $("#prescription_modal input[type='radio']").prop("disabled", true);
        $("#prescription_modal select").prop("disabled", true);
        $("#prescription_modal textarea").prop("disabled", true);

        $("input[name='outcome']").prop("checked", false);
        $("#outcome").val("");

        if (type == 6) {
            $("#btn_submit").addClass("d-none");
            $("#btn_reject").removeClass("d-none");
            $("#btn_reject").text("Cancel pending");

            const outcome_index = OUTCOMES.indexOf(outcome);
            if (outcome_index < 0) {
                $("input[name='outcome']:eq(5)").prop("checked", true);
                $("#outcome").val(outcome);
            } else {
                $("input[name='outcome']:eq(" + outcome_index + ")").prop("checked", true);
            }
        } else {
            $("#btn_submit").removeClass("d-none");
            $("#btn_submit").text("SUBMIT DECISIONS");
            $("#btn_reject").addClass("d-none");
        }

        $("#form_remarks").addClass("d-none");
        getMedications(id);
        getPrescriptionLogs(id);
        getConcurrentTherapy(hospital_id);
        prescription_modal.toggle();
    }

    function getConcurrentTherapy(hospital_id) {
        $("#concurrent_list").empty();
        $("#concurrent_list").append("<tr class='text-center'><td colspan='5'>Loading concurrents, please wait ...</td></tr>");
        fetch("{{ route('getConcurrentTherapy') }}?hospital_id=" + hospital_id, {
            method: "GET"
        })
            .then(res => res.json())
            .then(data => {
                $("#concurrent_list").empty();
                if (data.code == 500) {
                    $("#concurrent_list").append("<tr class='text-center'><td colspan='5'>Something went wrong while loading concurrent, please refresh browser</td></tr>");
                    return;
                }

                data.results.forEach(it => {
                        let tr = "<tr>";
                        if (it.for_switch == prescription_id && ($("#form_prescription_type").val() == "4" || $("#form_prescription_type").val() == "7"))
                            tr = "<tr class='pointer active'>";

                        tr += "<td>" + moment(it.concurrent_date).format("MM/DD/YYYY") + " </td>";
                        tr += "<td>" + it.antimicrobial + "</td>";
                        tr += "<td>" + it.dosing_regimen + "</td>";
                        tr += "<td>" + moment(it.start_date).format("MM/DD/YYYY") + "</td>";
                        tr += "<td>" + moment(it.stop_date).format("MM/DD/YYYY") + "</td>";
                        tr += "<td></td>";
                        tr += "<td></td>";
                        tr += "</tr>";

                        $("#concurrent_list").append(tr);
                    }
                );
            }).catch(err => {
            console.log(err);
            toastr.error("Something went wrong", "Ooops");
        });
    }

    function getMedications(id) {
        $("#medication_list").empty();
        $("#medication_list").append("<tr><td colspan='7'>Loading medications...</td></tr>");
        $("#microbiological_result_list").empty();
        const type_filter = ($("#form_prescription_type").val() == "6") ? "" : "?status=0";
        fetch("{{ url('prescription/get') }}/" + id + type_filter, {
            method: "GET"
        })
            .then(res => res.json())
            .then(data => {
                $("#medication_list").empty();
                if (data.code == 200) {
                    const ams = data.ams;
                    if (!jQuery.isEmptyObject(ams.indication_use)) {
                        const indication_index = INDICATION_USE_ARRAY.indexOf(ams.indication_use.description);
                        $("input[name='indication_use']:eq(" + indication_index + ")").prop("checked", true);
                    }
                    if (!jQuery.isEmptyObject(ams.intervention)) {
                        const clinical_index = CLINICAL_STABILITY.indexOf(ams.intervention.clinical_stability);
                        const oral_index = ORAL_INTAKE.indexOf(ams.intervention.oral_intake);

                        $("input[name='clinical_stability']:eq(" + clinical_index + ")").prop("checked", true);
                        $("input[name='oral_intake']:eq(" + oral_index + ")").prop("checked", true);
                    }

                    if (!jQuery.isEmptyObject(ams.microbiologicals)) {
                        ams.microbiologicals.forEach(it => {
                            let tr = "<tr>";
                            tr += "<td>" + moment(it.date).format("MM/DD/YYYY") + " </td>";
                            tr += "<td>" + it.specimen + "</td>";
                            tr += "<td colspan='3'>" + it.pathogen + "</td>";
                            tr += "</tr>";

                            $("#microbiological_result_list").append(tr);
                        });
                    }

                    const type = $("#form_prescription_type").val();

                    data.results.forEach((it, index) => {
                        if (type == "6") {
                            const tr = `<tr>
                                                <td>` + it.description + `</td>
                                                <td>` + it.dosage_strength + ' ' + it.dosage_unit + `</td>
                                                <td>` + it.unit + `</td>
                                                <td class="d-none"><input type="number" class="form-control text-center" value="` + it.quantity_ordered + `"/></td>
                                                <td>` + it.frequency + `</td>
                                                <td></td>
                                             </tr>`;

                            $("#medication_list").append(tr);
                            return;
                        }

                        const tr = `<tr data-medicine-id="` + it.id + `">
                                            <td>` + it.description + `</td>
                                            <td>` + it.dosage_strength + ' ' + it.dosage_unit + `</td>
                                            <td>` + it.unit + `</td>
                                            <td class="d-none"><input type="number" class="form-control text-center" value="` + it.quantity_ordered + `"/></td>
                                            <td>` + it.frequency + `</td>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input medicine_decision" type="radio" name="medicine_decision_` + index + `" value="approved" checked>
                                                    <label class="form-check-label">Approve</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input medicine_decision" type="radio" name="medicine_decision_` + index + `" value="rejected">
                                                    <label class="form-check-label">Reject</label>
                                                </div>
                                                <textarea class="form-control mt-1 d-none medicine_remarks" placeholder="Reason for rejection" rows="2"></textarea>
                                            </td>
                                         </tr>`;

                        $("#medication_list").append(tr);
                    });

                    if (type != "6") {
                        $(".medicine_decision").change(function () {
                            const textarea = $(this).closest("tr").find(".medicine_remarks");
                            if ($(this).val() === "rejected") {
                                textarea.removeClass("d-none");
                            } else {
                                textarea.addClass("d-none").val("");
                            }
                        });
                    }
                }
            }).catch(err => {
            console.log(err);
            $("#medication_list").empty();
            $("#medication_list").append("<tr><td colspan='7'>Something went wrong, please reload page</td></tr>");
        });
    }

    function getPrescriptionLogs(id) {
        $("#transaction_list").empty();
        $("#transaction_list").append("<tr><td colspan='4'>Loading transactions...</td></tr>");

        fetch("{{ url('prescription/get/logs') }}/" + id, {
            method: "GET"
        })
            .then(res => res.json())
            .then(data => {
                if (data.code == 200) {
                    $("#transaction_list").empty();
                    data.results.forEach(it => {
                        $("#transaction_list").append(activityLog(it));
                    });
                }
            }).catch(err => {
            console.log(err);
            $("#transaction_list").empty();
            $("#transaction_list").append("<tr><td colspan='4'>Something went wrong, please reload page</td></tr>");
        })
    }

    function getPrescriptions() {
        const filter_patient = $("#filter_patient").val().trim();
        const filter_date = $("#filter_date").val().trim();
        const filter_station = "";
        $("#prescription_list").empty();
        $("#prescription_list").append("<tr><td colspan='6'>Loading data, please wait ...</td></tr>");
        fetch("{{ url('icc_review/get') }}?filter_patient=" + filter_patient +
            "&filter_date=" + filter_date +
            "&filter_station=" + filter_station +
            "&page=" + page, {
            method: "GET"
        })
            .then(res => res.json())
            .then(data => {
                $("#pagination_container").addClass("d-none")
                $("#prescription_list").empty();
                $("#btn_next").addClass("d-none");
                if (data.results.length < 1) {
                    $("#prescription_list").append("<tr><td colspan='6'>No record found</td></tr>");
                    return;
                }

                $("#pagination_container").removeClass("d-none")
                let max_visible = data.results.length;
                if (max_visible == 11) {
                    max_visible = 10;
                    $("#btn_next").removeClass("d-none");
                }

                $("#page_items_count").text(((page * 10) + 1) + " - " + ((page * 10) + max_visible));

                for (let i = 0; i < max_visible; i++) {
                    const it = data.results[i];
                    const bg = (it.urgency == "Urgent") ? "bg-danger" : "bg-success";
                    const tr = ` <tr id='prescription_id_` + it.id + `'>
                                <td><span class="badge ` + bg + `">` + it.urgency + `</span></td>
                                <td>` + it.hospital_id + `</td>
                                <td>` + it.name + `</td>
                                <td>` + it.station + `</td>
                                <td>` + moment(it.created_at).format("MM/DD/YYYY hh:mm:ss A") + `</td>
                                <td class="d-none">` + it.sex + `</td>
                                <td class="d-none">` + it.birthdate + `</td>
                                <td class="d-none">` + it.ward + `</td>
                                <td class="d-none">` + it.bed_no + `</td>
                                <td class="d-none">` + it.type + `</td>
                                <td class="d-none">` + it.drug_allergy + `</td>
                                <td class="d-none">` + it.weight + `</td>
                                <td class="d-none">` + it.height + `</td>
                                <td class="d-none">` + it.serum_creatinine + `</td>
                                <td class="d-none">` + it.creatinine_clearance + `</td>
                                <td class="d-none">` + it.transaminases + `</td>
                                <td class="d-none">` + it.sgpt + `</td>
                                <td class="d-none">` + it.sgot + `</td>
                                <td class="d-none">` + it.days_requested + `</td>
                                <td class="d-none">` + it.attending_physician + `</td>
                                <td class="d-none">` + it.additional_labs + `</td>
                                <td class="d-none">` + it.outcome + `</td>
                                <td class="d-none">` + it.diagnosis + `</td>
                                <td class="d-none">` + it.for_extension + `</td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="viewPrescription(` + it.id + `)">
                                        <i class="bi bi-prescription"></i>
                                    </button>
                                </td>
                            </tr>`;

                    $("#prescription_list").append(tr);
                }
            }).catch(err => {
            console.log(err);
            $("#prescription_list").empty();
            $("#prescription_list").append("<tr><td colspan='6'>Something went wrong, please reload page</td></tr>");
        });
    }

    function approvePrescription(params) {
        $("#spinner").removeClass("d-none");
        fetch('{{ route('approve_prescription') }}', {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            body: JSON.stringify(params)
        })
            .then(res => res.json())
            .then(data => {
                $("#spinner").addClass("d-none");
                if (data.code == 500) {
                    toastr.error(data.message, "Ooops");
                    return;
                }

                remarks_modal.toggle();
                prescription_modal.toggle();
                toastr.success(data.message, "Information");

                insert("PHARMACY", {id: 1}, function (message) {
                    deleteChild("PHARMACY");
                });

                getPrescriptions();
            }).catch(err => {
            $("#spinner").addClass("d-none");
            if ((err + "").includes("SyntaxError: Unexpected token")) {
                toastr.error("Please try again", "Server Connection Timeout")
                return;
            }
            toastr.error("Something went wrong", "Ooops")
        });
    }

    function decidePrescriptionMedications(params) {
        $("#spinner").removeClass("d-none");
        fetch('{{ route('decide_prescription') }}', {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            body: JSON.stringify(params)
        })
            .then(res => res.json())
            .then(data => {
                $("#spinner").addClass("d-none");
                if (data.code == 500) {
                    toastr.error(data.message, "Ooops");
                    return;
                }

                remarks_modal.toggle();
                prescription_modal.toggle();
                toastr.success(data.message, "Information");

                const has_approved = params.medications.some(m => m.decision === "approved");
                if (has_approved) {
                    insert("PHARMACY", {id: 1}, function (message) {
                        deleteChild("PHARMACY");
                    });
                }

                getPrescriptions();
            }).catch(err => {
            $("#spinner").addClass("d-none");
            if ((err + "").includes("SyntaxError: Unexpected token")) {
                toastr.error("Please try again", "Server Connection Timeout")
                return;
            }
            toastr.error("Something went wrong", "Ooops")
        });
    }

    function rejectPrescription(params) {
        $("#spinner").removeClass("d-none");
        fetch('{{ route('reject_prescription') }}', {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            body: JSON.stringify(params)
        })
            .then(res => res.json())
            .then(data => {
                $("#spinner").addClass("d-none");
                if (data.code == 500) {
                    toastr.error(data.message, "Ooops");
                    return;
                }

                if (params.type != "6") {
                    remarks_modal.toggle();
                }
                prescription_modal.toggle();
                toastr.success(data.message, "Information");

                insert("prescriber/" + data.id, {id: 1}, function (message) {
                    deleteChild("prescriber/" + data.id);
                });

                getPrescriptions();
            }).catch(err => {
            $("#spinner").addClass("d-none");
            if ((err + "").includes("SyntaxError: Unexpected token")) {
                toastr.error("Please try again", "Server Connection Timeout")
                return;
            }
            toastr.error("Something went wrong", "Ooops")
        });
    }

    function activityLog(it) {
        return `<tr>
                      <td>` + it.fname + ' ' + it.mname + ' ' + it.lname + ` <span class="badge bg-primary ms-2">` + it.origin + `</span></td>
                      <td>` + it.remarks + `</td>
                      <td><div class="badge bg-success p-1">` + it.released_to + `</div></td>
                      <td>` + moment(it.date_forwarded).format("MM/DD/YYYY hh:mm:ss A") + `</td>
                    </tr>`
    }

    function clearForms() {
        $("#prescription_modal input").val("");
        $("#prescription_modal input[type='checkbox']").prop("checked", false);
        $("#prescription_modal input[type='radio']").prop("checked", false);
        $("#prescription_modal select").val("");
        $("#prescription_modal textarea").val("");
        $("#microbiological_result_list").empty();
        $("#medication_list").empty();
    }

    function logout() {
        window.location.href = '{{ route('logout') }}';
    }
</script>
