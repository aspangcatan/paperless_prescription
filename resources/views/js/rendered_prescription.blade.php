<script src="{{ asset('js/moment.min.js') }}"></script>
<script src="{{ asset('js/constant.js') }}?v={{ date('ymdhis') }}"></script>
<script src="{{ asset('js/prescription-ui.js') }}?v={{ date('ymdhis') }}"></script>
<script src="{{ asset('js/websocket.js') }}?v={{ date('ymdhis') }}"></script>
<script>
    const prescription_modal = new bootstrap.Modal(document.getElementById('prescription_modal'));
    const return_modal = new bootstrap.Modal(document.getElementById('return_modal'));

    let button_clicked = "";
    let prescription_id;
    let med_id = 0;
    let page = 0;
    $(document).ready(function () {
        $("#quantity_render_th").text("Availability");

        $("#btn_reject").addClass("d-none");
        $("#btn_submit").addClass("d-none");

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

        $("#btn_search_prescription").click(function () {
            page = 0;
            getPrescriptions();
        });

        $("#btn_return").click(function () {
            const amount = $("#return_amount").val();
            const remarks = $("#return_remarks").val().trim();

            let is_valid = true;
            $("#return_modal .is-invalid").removeClass("is-invalid");
            $("#return_modal .error_container").addClass("d-none");
            $("#return_modal .error_list").empty();

            if (!amount || amount <= 0) {
                is_valid = false;
                $("#return_modal .error_list").append("<li>Amount must be  greater than 0</li>");
            }

            if (remarks == "") {
                is_valid = false;
                $("#return_modal .error_list").append("<li>Remarks is required</li>");
            }

            if (!is_valid) {
                $("#return_modal .error_container").removeClass("d-none");
                return;
            }

            const params = {
                id: med_id,
                amount: amount,
                remarks: remarks
            }

            return_modal.toggle();
            prescription_modal.toggle();
            $("#spinner").removeClass("d-none");
            fetch("{{route('return_meds')}}", {
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
                    toastr.success(data.message, "Information");
                }).catch(err => {
                console.log(err);
                toastr.error("Something went wrong, please reload page", "Ooops");
            })
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
        const created_at = $("#prescription_id_" + id + " td:eq(5)").text();
        const sex = $("#prescription_id_" + id + " td:eq(6)").text();
        const birthdate = $("#prescription_id_" + id + " td:eq(7)").text();
        const ward = $("#prescription_id_" + id + " td:eq(8)").text();
        const bed_no = $("#prescription_id_" + id + " td:eq(9)").text();
        const type = $("#prescription_id_" + id + " td:eq(10)").text();
        const drug_allergy = $("#prescription_id_" + id + " td:eq(11)").text();
        const weight = $("#prescription_id_" + id + " td:eq(12)").text();
        const height = $("#prescription_id_" + id + " td:eq(13)").text();
        const serum_creatinine = $("#prescription_id_" + id + " td:eq(14)").text();
        const creatinine_clearance = $("#prescription_id_" + id + " td:eq(15)").text();
        const transaminases = $("#prescription_id_" + id + " td:eq(16)").text();
        const sgpt = $("#prescription_id_" + id + " td:eq(17)").text();
        const sgot = $("#prescription_id_" + id + " td:eq(18)").text();
        const days_requested = $("#prescription_id_" + id + " td:eq(19)").text();
        const attending_physician = $("#prescription_id_" + id + " td:eq(20)").text();
        const additional_labs = $("#prescription_id_" + id + " td:eq(21)").text();
        const age = moment(created_at).diff(moment(birthdate), 'years');

        clearForms();

        $("#form_hospital_id").text(hospital_id);
        $("#form_patient").text(name);
        $("#form_birthdate").text(moment(birthdate).format("MM/DD/YYYY"));
        $("#form_age").text(age);
        $("#form_sex").text(sex);
        $("#form_station").val(station);
        $("#form_ward").val(ward);
        $("#form_bed_no").val(bed_no);
        $("#form_attending_physician").val(attending_physician);
        $("#checkbox_urgent").prop("checked", (urgency == "Urgent"));
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

        $("#prescription_modal input").prop("disabled", true);
        $("#prescription_modal input[type='checkbox']").prop("disabled", true);
        $("#prescription_modal input[type='radio']").prop("disabled", true);
        $("#prescription_modal select").prop("disabled", true);
        $("#prescription_modal textarea").prop("disabled", true);


        getMedications(id);
        getPrescriptionLogs(id);
        getConcurrentTherapy(hospital_id);
        prescription_modal.toggle();
    }

    function getConcurrentTherapy(hospital_id) {
        $("#concurrent_list").empty();
        fetch("{{ route('getConcurrentTherapy') }}?hospital_id=" + hospital_id, {
            method: "GET"
        })
            .then(res => res.json())
            .then(data => {
                if (data.code == 500) {
                    $("#concurrent_list").append("<tr class='text-center'><td colspan='5'>Something went wrong while loading concurrent, please refresh browser</td></tr>");
                    return;
                }

                data.results.forEach(it => {
                    let tr = "<tr>";
                    if (it.for_switch == prescription_id && $("#form_prescription_type").val() == "4")
                        tr = "<tr class='pointer active'>";
                    tr += "<td>" + moment(it.concurrent_date).format("MM/DD/YYYY") + " </td>";
                    tr += "<td>" + escapeCell(it.antimicrobial) + "</td>";
                    tr += "<td>" + escapeCell(it.dosing_regimen) + "</td>";
                    tr += "<td>" + moment(it.start_date).format("MM/DD/YYYY") + "</td>";
                    tr += "<td>" + moment(it.stop_date).format("MM/DD/YYYY") + "</td>";
                    tr += "<td></td>";
                    tr += "<td></td>";
                    tr += "</tr>";

                    $("#concurrent_list").append(tr);
                });
            }).catch(err => {
            console.log(err);
            toastr.error("Something went wrong", "Ooops");
        });
    }

    function getMedications(id) {
        $("#medication_list").empty();
        $("#medication_list").append("<tr><td colspan='7'>Loading medications...</td></tr>");
        $("#microbiological_result_list").empty();
        fetch("{{ url('prescription/get') }}/" + id, {
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
                            tr += "<td>" + escapeCell(it.specimen) + "</td>";
                            tr += "<td colspan='3'>" + escapeCell(it.pathogen) + "</td>";
                            tr += "</tr>";

                            $("#microbiological_result_list").append(tr);
                        });
                    }

                    $("#rx_review_status").html(reviewStatusPill(data.review_status));
                    data.results.forEach(it => {
                        // Older records could be dispensed as "Not Available" (quantity 0).
                        const unavailable = (it.date_rendered && it.quantity_rendered == 0)
                            ? " <span class='med-badge med-rejected'>Not available</span>" : "";
                        $("#medication_list").append(infoMedicineRow(it, unavailable));
                    });

                }
            }).catch(err => {
            console.log(err);
            toastr.error("Something went wrong", "Ooops");
        });
    }

    function returnMeds(id) {
        med_id = id;
        return_modal.toggle();
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
            toastr.error("Something went wrong", "Ooops");
        })
    }

    function getPrescriptions() {
        const filter_patient = $("#filter_patient").val().trim();
        const filter_date = $("#filter_date").val().trim();
        const page = 0;
        $("#prescription_list").empty();
        $("#prescription_list").append("<tr><td colspan='6'>Loading data, please wait ...</td></tr>");
        fetch("{{ url('rendered_prescription/get') }}?filter_patient=" + filter_patient +
            "&filter_date=" + filter_date +
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
                                <td><span class="badge ` + bg + `">` + escapeCell(it.urgency) + `</span></td>
                                 <td>` + escapeCell(it.hospital_id) + `</td>
                                <td>` + escapeCell(it.name) + `</td>
                                <td>` + escapeCell(it.station) + `</td>
                                <td>` + moment(it.date_forwarded).format("MM/DD/YYYY hh:mm:ss A") + `</td>
                                <td>` + moment(it.created_at).format("MM/DD/YYYY hh:mm:ss A") + `</td>
                                <td class="d-none">` + escapeCell(it.sex) + `</td>
                                <td class="d-none">` + escapeCell(it.birthdate) + `</td>
                                <td class="d-none">` + escapeCell(it.ward) + `</td>
                                <td class="d-none">` + escapeCell(it.bed_no) + `</td>
                                <td class="d-none">` + escapeCell(it.type) + `</td>
                                <td class="d-none">` + escapeCell(it.drug_allergy) + `</td>
                                <td class="d-none">` + escapeCell(it.weight) + `</td>
                                <td class="d-none">` + escapeCell(it.height) + `</td>
                                <td class="d-none">` + escapeCell(it.serum_creatinine) + `</td>
                                <td class="d-none">` + escapeCell(it.creatinine_clearance) + `</td>
                                <td class="d-none">` + escapeCell(it.transaminases) + `</td>
                                <td class="d-none">` + escapeCell(it.sgpt) + `</td>
                                <td class="d-none">` + escapeCell(it.sgot) + `</td>
                                <td class="d-none">` + escapeCell(it.days_requested) + `</td>
                                <td class="d-none">` + escapeCell(it.attending_physician) + `</td>
                                <td class="d-none">` + escapeCell(it.additional_labs) + `</td>
                                <td>
                                    ` + reviewStatusPill(it.review_status) + `
                                    <button class="btn btn-sm btn-primary" onclick="viewPrescription(` + it.id + `)">
                                        <i class="bi bi-prescription"></i>
                                    </button>
                                </td>
                            </tr>`;

                    $("#prescription_list").append(tr);
                }
            }).catch(err => {
            console.log(err);
            toastr.error("Something went wrong, please reload page", "Ooops");
        });
    }

    function activityLog(it) {
        return activityLogRow(it);
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
