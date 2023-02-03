@extends('master')
@section('content')
    <div class="col-12">
        <div class="d-flex justify-content-end mb-2">
            <div>
                <input class="form-control" placeholder="Filter patient" id="filter_patient"/>
            </div>
            <div>
                <input class="form-control ms-1 me-1" placeholder="Filter station" id="filter_station"/>
            </div>
            <div class="ms-2 me-1">
                <input type="text" class="form-control" placeholder="Filter date" id="filter_date"/>
            </div>
            <div class="ms-1">
                <button class="btn btn-success" id="btn_search_prescription">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title">For Reviews</h5>
                </div>

                <div class="table-responsive">
                    <table class="table text-center">
                        <thead>
                        <tr>
                            <th></th>
                            <th>Hospital ID</th>
                            <th>Patient</th>
                            <th>Station</th>
                            <th>Date Prescribed</th>
                            <th colspan="2" style="width:4%"></th>
                        </tr>
                        </thead>
                        <tbody id="prescription_list"></tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
    @include('modals/prescription')
    @include('modals/common')
@stop
@section('js')
    <script src="js/jquery.min.js"></script>
    <script src="js/moment.min.js"></script>
    <script>
        const prescription_modal = new bootstrap.Modal(document.getElementById('prescription_modal'));
        const medicine_modal = new bootstrap.Modal(document.getElementById('medicine_modal'));

        const success_modal = new bootstrap.Modal(document.getElementById('success_modal'));
        const error_modal = new bootstrap.Modal(document.getElementById('error_modal'));
        let button_clicked = "";
        let prescription_id;
        let prescribed_med_id;
        $(document).ready(function () {

            $("#filter_date").focusin(function () {
                $(this).attr('type', 'date')
            });

            $("#filter_date").focusout(function () {
                $(this).attr('type', 'text')
            });

            $("#btn_search_prescription").click(function () {
                getPrescriptions();
            });

            $("#btn_view_cart").click(function () {
                prescribed_med_id = "";
                medicine_modal.toggle();
                getMedicine($("#search_medicine").val().trim());
            });

            $("#search_medicine").keypress(function (e) {
                if (e.which == 13) {
                    getMedicine($(this).val().trim());
                }
            });

            $("#btn_submit").click(function () {
                let is_valid = true;
                $("#prescription_modal .is-invalid").removeClass("is-invalid");
                $("#prescription_modal .error_container").addClass("d-none");
                $("#prescription_modal .error_list").empty();

                const remarks = $("#form_remarks").val();
                const urgency = ($("#checkbox_urgent").is(":checked")) ? "Urgent" : "Normal";
                const medications = [];
                for (let i = 0; i < $("#medication_list tr").length; i++) {
                    let medication_flag = true;
                    const description = $("#medication_list tr:eq(" + i + ")").find("td:eq(0)").text();
                    const dosage_strength = $("#medication_list tr:eq(" + i + ")").find("td:eq(1) input").val();
                    const dosage_unit = $("#medication_list tr:eq(" + i + ")").find("td:eq(1) select").val();
                    const unit = $("#medication_list tr:eq(" + i + ")").find("td:eq(2)").text();
                    const quantity = $("#medication_list tr:eq(" + i + ")").find("td:eq(3) input").val();
                    const frequency = $("#medication_list tr:eq(" + i + ")").find("td:eq(4) select").val();

                    if (dosage_strength == "") {
                        is_valid = false;
                        medication_flag = false;
                        $("#prescription_modal .error_list").append("<li>Dosage strength amount is required</li>");
                    }

                    if (dosage_unit == "") {
                        is_valid = false;
                        medication_flag = false;
                        $("#prescription_modal .error_list").append("<li>Dosage strength unit is required</li>");
                    }

                    if (quantity < 0 || quantity == "") {
                        is_valid = false;
                        medication_flag = false;
                        $("#prescription_modal .error_list").append("<li>Quantity must be greater than 0</li>");
                    }

                    if (frequency == "") {
                        is_valid = false;
                        medication_flag = false;
                        $("#prescription_modal .error_list").append("<li>Frequency is required</li>");
                    }

                    if (medication_flag) {
                        medications.push({
                            description: description,
                            unit: unit,
                            dosage_strength: dosage_strength,
                            dosage_unit: dosage_unit,
                            quantity_ordered: quantity,
                            frequency: frequency
                        });
                    }
                }

                if (medications.length < 1 && is_valid) {
                    is_valid = false;
                    $("#prescription_modal .error_list").append("<li>There must be at least 1 medication</li>");
                }

                if (!is_valid) {
                    $("#prescription_modal .error_container").removeClass("d-none");
                    return;
                }

                const params = {
                    prescription_id: prescription_id,
                    urgency: urgency,
                    remarks: remarks,
                    medications: medications,
                }

                updatePrescription(params);
            });

            getPrescriptions();
        });

        function viewPrescription(id) {
            prescription_id = id;
            $("#btn_submit").val(id);
            $("#btn_reject").val(id);
            $("#btn_submit").text("UPDATE");

            $("#form_station").prop("disabled", true);
            $("#form_ward").prop("disabled", true);
            $("#form_bed_no").prop("disabled", true);
            $("#form_remarks").val("");

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
                        for (let i = 0; i < data.results.length; i++) {
                            const it = data.results[i];
                            const tr = `<tr id='medication_id_` + i + `'>
                                        <td>` + it.description + `</td>
                                        <td>
                                            <div class="d-flex">
                                                <input class="form-control text-center w-50" type="number" value="` + it.dosage_strength + `"/>
                                                <select class="form-control text-center w-50">
                                                    <option value="">Select unit</option>
                                                    <option>liter</option>
                                                    <option>mG</option>
                                                    <option>mL</option>
                                                </select>
                                            </div>
                                        </td>
                                        <td>` + it.unit + `</td>
                                        <td>
                                            <input class="form-control text-center" type="number" value="` + it.quantity_ordered + `"/>
                                        </td>
                                        <td>
                                            <select class="form-control text-center">
                                                <option value="">Select frequency</option>
                                                <option>3X/DAY</option>
                                                <option>1X/DAY</option>
                                                <option>EVERYDAY</option>
                                            </select>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-success" onclick="editMedicine(` + i + `)">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-danger" onclick="deleteMedicine(` + i + `)">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </td>
                                    </tr>`;

                            $("#medication_list").append(tr);

                            $("#medication_id_" + i + " td:eq(1) select").val(it.dosage_unit);
                            $("#medication_id_" + i + " td:eq(4) select").val(it.frequency);
                        }
                    }
                }).catch(err => {
                console.log(err);
            });
        }

        function editMedicine(id) {
            prescribed_med_id = id;
            medicine_modal.toggle();
            getMedicine($("#search_medicine").val().trim());
        }

        function deleteMedicine(id) {
            $("#medication_id_" + id).remove();
        }

        function appendMedicine(id) {
            const description = $("#" + id + " td:eq(0)").text();
            const unit = $("#" + id + " td:eq(1)").text();
            const count = $("#medication_list").length;
            if (prescribed_med_id === "") {
                //APPEND NEW RECORD
                const tr = `<tr id='medication_id_` + count + `'>
                                        <td>` + description + `</td>
                                        <td>
                                            <div class="d-flex">
                                                <input class="form-control text-center w-50" type="number"/>
                                                <select class="form-control text-center w-50">
                                                    <option value="">Select unit</option>
                                                    <option>liter</option>
                                                    <option>mG</option>
                                                    <option>mL</option>
                                                </select>
                                            </div>
                                        </td>
                                        <td>` + unit + `</td>
                                        <td>
                                            <input class="form-control text-center" type="number"/>
                                        </td>
                                        <td>
                                            <select class="form-control text-center">
                                                <option value="">Select frequency</option>
                                                <option>3X/DAY</option>
                                                <option>1X/DAY</option>
                                                <option>EVERYDAY</option>
                                            </select>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-success" onclick="editMedicine(` + count + `)">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-danger" onclick="deleteMedicine(` + count + `)">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </td>
                                    </tr>`;
                $("#medication_list").append(tr);
            } else {
                $("#medication_id_" + prescribed_med_id + " td:eq(0)").text(description);
                $("#medication_id_" + prescribed_med_id + " td:eq(1) input").val("");
                $("#medication_id_" + prescribed_med_id + " td:eq(1) select").val("");
                $("#medication_id_" + prescribed_med_id + " td:eq(2)").text(unit);
                $("#medication_id_" + prescribed_med_id + " td:eq(3) input").val("");
                $("#medication_id_" + prescribed_med_id + " td:eq(4) select").val("");
            }
        }

        function getMedicine(key) {
            let url = "{{ url('medicine/get') }}/" + key;
            if (key == "") {
                "{{ url('medicine/get') }}";
            }

            fetch(url, {
                method: "GET"
            })
                .then(res => res.json())
                .then(data => {
                    $("#medicine_list").empty();
                    data.results.forEach(it => {
                        const tr = `
                        <tr id='` + it.id + `'>
                           <td>` + it.description + `</td>
                           <td>` + it.unit + `</td>
                           <td>
                                <button class="btn btn-sm btn-primary" onclick="appendMedicine('` + it.id + `')">
                                    <i class="bi bi-check"></i>
                                </button>
                           </td>
                        </tr>`;
                        $("#medicine_list").append(tr);
                    });
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
            const filter_station = $("#filter_station").val().trim();
            const filter_date = $("#filter_date").val().trim();
            const page = 0;
            fetch("{{ url('returned_prescription/get') }}?filter_patient=" + filter_patient +
                "&filter_date=" + filter_date +
                "&filter_station=" + filter_station +
                "&page=" + page, {
                method: "GET"
            })
                .then(res => res.json())
                .then(data => {
                    $("#prescription_list").empty();

                    if (data.results.length < 1) {
                        $("#prescription_list").append("<tr><td colspan='5'>No record found</td></tr>");
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

        function updatePrescription(params) {
            prescription_modal.toggle();
            $("#spinner").removeClass("d-none");
            fetch("api/update_prescription", {
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
                console.log(err);
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
@stop
