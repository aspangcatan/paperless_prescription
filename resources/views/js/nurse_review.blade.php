<script src="js/moment.min.js"></script>
<script src="js/constant.js"></script>
<script src="js/websocket.js?v={{ date('ymdhis') }}"></script>
<script>
    const prescription_modal = new bootstrap.Modal(document.getElementById('prescription_modal'));
    const success_modal = new bootstrap.Modal(document.getElementById('success_modal'));
    const error_modal = new bootstrap.Modal(document.getElementById('error_modal'));
    const remarks_modal = new bootstrap.Modal(document.getElementById('remarks_modal'));
    let button_clicked = "";
    let prescription_id;

    let definitive_details = {};
    $(document).ready(function () {

        STATIONS.forEach(it => {
            $("#form_station").append("<option>" + it + "</option>");
        });

        $("#filter_date").focusin(function () {
            $(this).attr('type', 'date')
        });

        $("#filter_date").focusout(function () {
            $(this).attr('type', 'text')
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
            button_clicked = $(this).text();
            prescription_id = $(this).val();

            $("#remarks_message").text("Reason for approval");
            remarks_modal.toggle();
        });

        $("#btn_reject").click(function () {
            button_clicked = $(this).text();
            prescription_id = $(this).val();

            $("#remarks_message").text("Reason for rejection");
            remarks_modal.toggle();
        });

        $("#btn_proceed").click(function () {
            const params = {
                id: prescription_id,
                origin: "NURSE",
                remarks: $("#common_remarks").val().trim()
            }

            remarks_modal.toggle();
            if (button_clicked == "APPROVE") {
                approvePrescription(params);
            } else if (button_clicked == "REJECT") {
                rejectPrescription(params);
            }
        });

        getPrescriptions();
    });

    function viewPrescription(id) {
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
        $("#checkbox_urgent").prop("checked", (urgency == "Urgent"));
        $("#form_prescription_type").val(type);
        $("#form_drug_allergy").val(drug_allergy);
        $("#form_weight").val(weight);
        $("#form_height").val(height);
        $("#form_serum_creatinine").val(serum_creatinine);
        $("#form_creatinine_clearance").val(creatinine_clearance);
        $("#form_transaminases").val(transaminases);
        $("#form_sgpt").val(sgpt);
        $("#form_sgot").val(sgot);

        if (type == "2") {
            $(".ams_container").removeClass("d-none");
        }

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
        });
    }

    function getMedications(id) {
        $("#medication_list").empty();
        $("#medication_list").append("<tr><td colspan='7'>Loading medications...</td></tr>");
        fetch("{{ url('prescription/get') }}/" + id, {
            method: "GET"
        })
            .then(res => res.json())
            .then(data => {
                $("#microbiological_result_list").empty();
                $("#medication_list").empty();

                if (data.code == 200) {
                    if (!jQuery.isEmptyObject(data.ams)) {
                        const ams = data.ams;

                        $("#indication_reason").val(ams.indication_use.reason);
                        const indication_index = INDICATION_USE_ARRAY.indexOf(ams.indication_use.description);
                        $("input[name='indication_use']:eq(" + indication_index + ")").prop("checked", true);

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
                                            <td>` + it.description + `</td>
                                            <td>` + it.dosage_strength + ' ' + it.dosage_unit + `</td>
                                            <td>` + it.unit + `</td>
                                            <td>` + it.quantity_ordered + `</td>
                                            <td>` + it.frequency + `</td>
                                            <td></td>
                                         </tr>`;

                        $("#medication_list").append(tr);
                    });
                }
            }).catch(err => {
            console.log(err);
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
        })
    }

    function getPrescriptions() {
        const filter_patient = $("#filter_patient").val().trim();
        const filter_date = $("#filter_date").val().trim();
        const page = 0;
        fetch("{{ url('nurse_review/get') }}?filter_patient=" + filter_patient +
            "&filter_date=" + filter_date +
            "&page=" + page, {
            method: "GET"
        })
            .then(res => res.json())
            .then(data => {
                $("#prescription_list").empty();
                if (data.results.length < 1) {
                    $("#prescription_list").append("<tr><td colspan='6'>No record found</td></tr>");
                    return;
                }

                data.results.forEach(it => {
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
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="viewPrescription(` + it.id + `)">
                                        <i class="bi bi-prescription"></i>
                                    </button>
                                </td>
                            </tr>`;

                    $("#prescription_list").append(tr);
                });
            }).catch(err => {
            console.log(err);
        });
    }

    function approvePrescription(params) {
        prescription_modal.toggle();
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
                    $("#error_message").text(data.message);
                    error_modal.toggle();
                    return;
                }

                $("#success_message").text(data.message);
                success_modal.toggle();

                sendWebsocketServer({
                    action: "NOTIFY ROLE",
                    body: {role: "Pharmacist"}
                })
                getPrescriptions();
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

                sendWebsocketServer({
                    action: "NOTIFY ID",
                    body: {id: data.id}
                })
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
        $(".ams_container").addClass("d-none");
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
