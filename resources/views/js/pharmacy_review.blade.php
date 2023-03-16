<script src="js/moment.min.js"></script>
<script src="js/constant.js?v={{ date('ymdhis') }}"></script>
<script src="js/websocket.js?v={{ date('ymdhis') }}"></script>
<script>
    const prescription_modal = new bootstrap.Modal(document.getElementById('prescription_modal'));
    const success_modal = new bootstrap.Modal(document.getElementById('success_modal'));
    const error_modal = new bootstrap.Modal(document.getElementById('error_modal'));
    const warning_modal = new bootstrap.Modal(document.getElementById('warning_modal'));
    const remarks_modal = new bootstrap.Modal(document.getElementById('remarks_modal'));
    const render_modal = new bootstrap.Modal(document.getElementById('render_modal'));
    let definitive_details = {};
    let prescription_id;
    let page = 0;

    $(document).ready(function () {

        observe("PHARMACY", function (snapshot) {
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


        $("#quantity_render_th").text("Availability");

        STATIONS.forEach(it => {
            $("#form_station").append("<option>" + it + "</option>");
        });

        $("body").on("keypress", ".number-only", function (e) {
            if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
                return false;
            }
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
            }
        });

        $("#btn_search_prescription").click(function () {
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
            $("#rendered_list").empty();
            $(".is-invalid").removeClass("is-invalid");

            let is_filled_up = true;
            let li = "";
            for (let i = 0; i < $("#medication_list tr").length; i++) {
                const tr = $("#medication_list tr:eq(" + i + ")");
                const description = tr.find("td:eq(1)").text();
                const quantity_rendered = tr.find("td:eq(6) select").val();
                console.log(quantity_rendered);
                if (!quantity_rendered) {
                    tr.find("td:eq(6) select").addClass("is-invalid");
                    is_filled_up = false;
                    continue;
                }

                if (quantity_rendered > 0) {
                    li += `<li style="font-size: 13px">
                               <span class="me-2">` + description + `</span><i class="bi bi-check-circle-fill text-success"></i>
                           </li>`;
                } else {
                    li += `<li style="font-size: 13px" class="text-danger">
                               <span class="me-2" ">` + description + `</span><i class="bi bi-exclamation-circle-fill"></i>
                           </li>`;
                }
            }

            $("#rendered_list").html(li);

            if (!is_filled_up) {
                alert("Please specify availability status");
                return;
            }

            render_modal.toggle();
        });

        $("#i_agree").change(function () {
            if ($(this).is(":checked")) {
                $("#btn_proceed_cancel").prop("disabled", false);
            } else {
                $("#btn_proceed_cancel").prop("disabled", true);
            }
        });

        $("#btn_reject").click(function () {
            prescription_id = $(this).val();

            //DISPLAY WARNING DIALOG
            if ($("#form_prescription_type").val() == "6") {
                warning_modal.toggle();
                $("#i_agree").prop("checked", false).change();
                $("#btn_proceed_cancel").prop("disabled", true);
                return;
            }

            $("#days_approved_container").addClass("d-none");
            $("#remarks_message").text("Reason for rejection");
            remarks_modal.toggle();
        });

        $("#i_agree").change(function () {
            if ($(this).is(":checked")) {
                $("#btn_proceed_cancel").prop("disabled", false);
            } else {
                $("#btn_proceed_cancel").prop("disabled", true);
            }
        });

        $("#btn_proceed_cancel").click(function () {
            const params = {
                id: prescription_id,
                type: $("#form_prescription_type").val(),
                remarks: "Prescriber wishes to cancel pending medications",
                origin: "PHARMACY"
            }

            warning_modal.toggle();
            rejectPrescription(params);
        });

        $("#btn_reject").click(function () {
            warning_modal.toggle();
            $("#i_agree").prop("checked", false).change();
            $("#btn_proceed_cancel").prop("disabled", true);
        });

        $("#btn_render,#btn_render_doctor").click(function () {
            const remarks = $("#pharmacy_remarks").val().trim();
            $(".is-invalid").removeClass("is-invalid");
            if (remarks == "") {
                alert("Please specify remarks or instructions for the receiver");
                $("#pharmacy_remarks").addClass("is-invalid");
                return;
            }

            const medications = [];
            for (let i = 0; i < $("#medication_list tr").length; i++) {
                const tr = $("#medication_list tr:eq(" + i + ")");
                const id = tr.find("td:eq(0)").text();
                const quantity_rendered = tr.find("td:eq(6) select").val();

                if ($(this).text().trim() == "Proceed" && quantity_rendered <= 0 & $("#form_prescription_type").val() != "1") {
                    alert("Antibiotic medications not allowed to be rendered if not available, must be returned to the doctor");
                    return;
                }
                medications.push({id: id, quantity_rendered: quantity_rendered});
            }

            const params = {
                id: prescription_id,
                type: $("#form_prescription_type").val(),
                remarks: remarks,
                origin: "PHARMACY",
                medications: medications
            }

            if ($(this).text().trim() == "Proceed") {
                renderPrescription(params);
            } else {
                render_modal.toggle();
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
        $("#btn_submit").text("TAG");
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

        $("#prescription_modal input").prop("disabled", true);
        $("#prescription_modal input[type='checkbox']").prop("disabled", true);
        $("#prescription_modal input[type='radio']").prop("disabled", true);
        $("#prescription_modal select").prop("disabled", true);
        $("#prescription_modal textarea").prop("disabled", true);

        if (type == 6) {
            $("#btn_submit").addClass("d-none");
            $("#btn_reject").text("Cancel pending");
            $("#btn_reject").removeClass("d-none");
        } else {
            $("#btn_submit").removeClass("d-none");
            $("#btn_reject").text("REJECT");
            $("#btn_reject").addClass("d-none");
        }

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
                    tr += "<td>" + it.antimicrobial + "</td>";
                    tr += "<td>" + it.dosing_regimen + "</td>";
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
                            tr += "<td>" + it.specimen + "</td>";
                            tr += "<td colspan='3'>" + it.pathogen + "</td>";
                            tr += "</tr>";

                            $("#microbiological_result_list").append(tr);
                        });
                    }

                    data.results.forEach(it => {
                        const tr = `<tr>
                                            <td class="d-none">` + it.id + `</td>
                                            <td>` + it.description + `</td>
                                            <td>` + it.dosage_strength + ' ' + it.dosage_unit + `</td>
                                            <td class="d-none">` + it.unit + `</td>
                                            <td class="d-none">` + it.quantity_ordered + `</td>
                                            <td>` + it.frequency + `</td>
                                            <td colspan="2">
                                                <select class="form-control text-center">
                                                    <option value=""></option>
                                                    <option value="1">Available</option>
                                                    <option value="0">Not Available</option>
                                                </select>
                                            </td>
                                         </tr>`;
                        $("#medication_list").append(tr);
                    });
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
        $("#prescription_list").append("<tr><td colspan='7'>Loading data, please wait ...</td></tr>");
        fetch("{{ url('pharmacy_review/get') }}?filter_patient=" + filter_patient +
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
                    $("#prescription_list").append("<tr><td colspan='7'>No record found</td></tr>");
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
                                <td>` + moment(it.date_forwarded).format("MM/DD/YYYY hh:mm:ss A") + `</td>
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
            $("#prescription_list").append("<tr><td colspan='7'>Something went wrong, please reload page</td></tr>");
        });
    }

    function renderPrescription(params) {
        render_modal.toggle();
        prescription_modal.toggle();
        $("#spinner").removeClass("d-none");
        fetch('{{ route('render_prescription') }}', {
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
                    $("#error_message").text(data.message);
                    error_modal.toggle();
                    return;
                }

                $("#success_message").text(data.message);
                success_modal.toggle();
                getPrescriptions();

                //ADD WEBSOCKET NOTIFY DOCTRS AND NURSES WHO ARE AT THAT STATION
                //PUT NOTIFICATION BELL BESIDE USER ICON
            }).catch(err => {
            $("#error_message").text(err);
            error_modal.toggle();
        });
    }

    function rejectPrescription(params) {
        prescription_modal.toggle();
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
                    $("#error_message").text(data.message);
                    error_modal.toggle();
                    return;
                }

                $("#success_message").text(data.message);
                success_modal.toggle();

                insert("prescriber/" + data.id, {id: 1}, function (message) {
                    deleteChild("prescriber/" + data.id);
                });

                getPrescriptions();
            }).catch(err => {
            $("#error_message").text(err);
            error_modal.toggle();
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
