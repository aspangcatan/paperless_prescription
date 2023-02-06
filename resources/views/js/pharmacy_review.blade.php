<script src="js/moment.min.js"></script>
<script>
    const prescription_modal = new bootstrap.Modal(document.getElementById('prescription_modal'));
    const success_modal = new bootstrap.Modal(document.getElementById('success_modal'));
    const error_modal = new bootstrap.Modal(document.getElementById('error_modal'));
    const remarks_modal = new bootstrap.Modal(document.getElementById('remarks_modal'));
    const render_modal = new bootstrap.Modal(document.getElementById('render_modal'));

    let button_clicked = "";
    let prescription_id;
    $(document).ready(function () {

        $("#quantity_render_th").text("Quantity Render");

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

        $("#btn_search_prescription").click(function () {
            getPrescriptions();
        });

        $("#btn_submit").click(function () {
            button_clicked = $(this).text();
            $("#rendered_list").empty();
            $(".is-invalid").removeClass("is-invalid");


            let is_filled_up = true;
            let li = "";
            for (let i = 0; i < $("#medication_list tr").length; i++) {
                const tr = $("#medication_list tr:eq(" + i + ")");
                const description = tr.find("td:eq(1)").text();
                const quantity = tr.find("td:eq(6) input").val();
                if (!quantity) {
                    tr.find("td:eq(6) input").addClass("is-invalid");
                    is_filled_up = false;
                    continue;
                }

                if (quantity > 0) {
                    li += `<li style="font-size: 13px">
                                    <span class="me-2">` + description + `</span><i class="bi bi-check-circle-fill text-success"></i>
                               </li>`;
                } else {
                    li += `<li style="font-size: 13px">
                                    <span class="me-2" ">` + description + `</span><i class="bi bi-exclamation-circle-fill text-danger"></i>
                               </li>`;
                }
            }

            $("#rendered_list").html(li);

            if (!is_filled_up) {
                alert("Please fill in quantity render");
                return;
            }

            render_modal.toggle();
        });

        $("#btn_reject").click(function () {
            button_clicked = $(this).text();
            $("#remarks_message").text("Reason for rejection");
            remarks_modal.toggle();
        });

        $("#btn_proceed").click(function () {
            const params = {
                id: prescription_id,
                origin: "PHARMACY",
                remarks: $("#common_remarks").val().trim()
            }

            remarks_modal.toggle();
            if (button_clicked == "APPROVE") {
                approvePrescription(params);
            } else if (button_clicked == "REJECT") {
                rejectPrescription(params);
            }
        });

        $("#btn_render").click(function () {
            const remarks = $("#pharmacy_remarks").val().trim();
            $(".is-invalid").removeClass("is-invalid");
            if (remarks == "") {
                alert("Please fill in remarks");
                $("#pharmacy_remarks").addClass("is-invalid");
                return;
            }

            const medications = [];
            for (let i = 0; i < $("#medication_list tr").length; i++) {
                const tr = $("#medication_list tr:eq(" + i + ")");
                const id = tr.find("td:eq(0)").text();
                const quantity = tr.find("td:eq(6) input").val();

                medications.push({id: id, quantity_rendered: quantity});
            }

            const params = {
                prescription_id: prescription_id,
                remarks: remarks,
                medications: medications
            }

            renderPrescription(params);
        });

        getPrescriptions();
    });

    function viewPrescription(id) {
        prescription_id = id;
        $("#btn_view_cart").addClass("d-none");
        $("#btn_submit").val(id);
        $("#btn_reject").val(id);
        $("#btn_submit").text("APPROVE");
        $("#form_remarks").addClass("d-none");
        $("#transaction_container").removeClass("d-none");

        $("#form_station").prop("disabled", true);
        $("#form_ward").prop("disabled", true);
        $("#form_bed_no").prop("disabled", true);
        $("#checkbox_urgent").prop("disabled", true);

        const urgency = $("#prescription_id_" + id + " td:eq(0)").text();
        const hospital_id = $("#prescription_id_" + id + " td:eq(1)").text();
        const name = $("#prescription_id_" + id + " td:eq(2)").text();
        const station = $("#prescription_id_" + id + " td:eq(3)").text();
        const created_at = $("#prescription_id_" + id + " td:eq(4)").text();
        const sex = $("#prescription_id_" + id + " td:eq(5)").text();
        const birthdate = $("#prescription_id_" + id + " td:eq(6)").text();
        const ward = $("#prescription_id_" + id + " td:eq(7)").text();
        const bed_no = $("#prescription_id_" + id + " td:eq(8)").text();
        const age = moment(created_at).diff(moment(birthdate), 'years');

        $("#form_hospital_id").text(hospital_id);
        $("#form_patient").text(name);
        $("#form_birthdate").text(birthdate);
        $("#form_age").text(age);
        $("#form_sex").text(sex);
        $("#form_station").val(station);
        $("#form_ward").val(ward);
        $("#form_bed_no").val(bed_no);
        $("#checkbox_urgent").prop("checked", (urgency == "Urgent"));

        getMedications(id);
        getPrescriptionLogs(id);
        prescription_modal.toggle();
    }

    function getMedications(id) {
        $("#medication_list").empty();
        $("#medication_list").append("<tr><td colspan='7'>Loading medications...</td></tr>");
        fetch("{{ url('prescription/get') }}/" + id, {
            method: "GET"
        })
            .then(res => res.json())
            .then(data => {
                if (data.code == 200) {
                    $("#medication_list").empty();
                    data.results.forEach(it => {
                        const tr = `<tr>
                                            <td class="d-none">` + it.id + `</td>
                                            <td>` + it.description + `</td>
                                            <td>` + it.dosage_strength + ' ' + it.dosage_unit + `</td>
                                            <td>` + it.unit + `</td>
                                            <td>` + it.quantity_ordered + `</td>
                                            <td>` + it.frequency + `</td>
                                            <td colspan="2">
                                                <input class="form-control text-center number-only"/>
                                            </td>
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
        fetch("{{ url('pharmacy_review/get') }}?filter_patient=" + filter_patient +
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
                                <td>` + moment(it.created_at).format("MM/DD/YYYY hh:mm:ss A") + `</td>
                                <td class="d-none">` + it.sex + `</td>
                                <td class="d-none">` + it.birthdate + `</td>
                                <td class="d-none">` + it.ward + `</td>
                                <td class="d-none">` + it.bed_no + `</td>
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

    function renderPrescription(params) {
        render_modal.toggle();
        prescription_modal.toggle();
        $("#spinner").removeClass("d-none");
        fetch("api/render_prescription", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
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
            }).catch(err => {
            $("#error_message").text(err);
            error_modal.toggle();
        });
    }

    function rejectPrescription(params) {
        prescription_modal.toggle();
        $("#spinner").removeClass("d-none");
        fetch("api/reject_prescription", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
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
</script>
