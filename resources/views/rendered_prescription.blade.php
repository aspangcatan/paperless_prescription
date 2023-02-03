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

        let button_clicked = "";
        let prescription_id;
        $(document).ready(function () {

            $("#quantity_render_th").text("Quantity Render");
            $("#btn_submit").addClass("d-none");
            $("#btn_reject").addClass("d-none");

            $("#filter_date").focusin(function () {
                $(this).attr('type', 'date')
            });

            $("#filter_date").focusout(function () {
                $(this).attr('type', 'text')
            });

            $("#btn_search_prescription").click(function () {
                getPrescriptions();
            });

            getPrescriptions();
        });

        function viewPrescription(id) {
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
                                            <td>` + it.description + `</td>
                                            <td>` + it.dosage_strength + ' ' + it.dosage_unit + `</td>
                                            <td>` + it.unit + `</td>
                                            <td>` + it.quantity_ordered + `</td>
                                            <td>` + it.frequency + `</td>
                                            <td>` + it.quantity_returned + `</td>
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
            const filter_station = $("#filter_station").val().trim();
            const filter_date = $("#filter_date").val().trim();
            const page = 0;
            fetch("{{ url('rendered_prescription/get') }}?filter_patient=" + filter_patient +
                "&filter_date=" + filter_date +
                "&filter_station=" + filter_station +
                "&page=" + page, {
                method: "GET"
            })
                .then(res => res.json())
                .then(data => {
                    console.log(data);
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
