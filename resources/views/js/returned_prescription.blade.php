<script src="js/moment.min.js"></script>
<script src="js/constant.js?v={{ date('ymdhis') }}"></script>
<script src="js/websocket.js?v={{ date('ymdhis') }}"></script>
<script>
    const prescription_modal = new bootstrap.Modal(document.getElementById('prescription_modal'));
    const medicine_modal = new bootstrap.Modal(document.getElementById('medicine_modal'));
    const microbiological_modal = new bootstrap.Modal(document.getElementById('microbiological_modal'));

    const success_modal = new bootstrap.Modal(document.getElementById('success_modal'));
    const error_modal = new bootstrap.Modal(document.getElementById('error_modal'));
    let button_clicked = "";
    let prescription_id;
    let prescribed_med_id;
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

        $("body").on("click", "#concurrent_list tr", function () {
            $("#concurrent_list tr.active").removeClass("active");
            if ($("#form_prescription_type").val() == '4') {
                //CHECK IF SELECTED CONCURRENT STOP DATE DOES NOT EXCEEDS TODAY'S DATE
                const selected_stop_date = moment($(this).find("td:eq(4)").text(), "MM/DD/YYYY");
                if (moment(selected_stop_date).diff(moment(), 'days') < 0) {
                    alert("Stop date must not below today's date");
                    return;
                }
                $(this).addClass("active");
            }
        });

        $("#form_station").change(function () {
            $("#form_ward").val($(this).val());
            $("#form_bed_no").val("N/A");
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
                    console.log("SHEESH")
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

        $("#btn_notification").click(function () {
            $(this).find("span").text("");
            $(this).addClass("btn-secondary");
            $(this).removeClass("btn-danger");
            $(this).removeClass("blink");
            notification_counter = 0;
            getPrescriptions();
        });

        $("#btn_search_prescription").click(function () {
            getPrescriptions();
        });

        $("#btn_view_cart").click(function () {
            prescribed_med_id = "";
            medicine_modal.toggle();
            getMedicine($("#search_medicine").val().trim());
        });

        $("#btn_add_microbiological").click(function () {
            $("#btn_save_microbiological").val("");
            clearMicrobiological();
            microbiological_modal.toggle();

        });

        $("#search_medicine").keypress(function (e) {
            if (e.which == 13) {
                getMedicine($(this).val().trim());
            }
        });

        $("#btn_reject").click(function () {
            let is_valid = true;
            $("#prescription_modal .is-invalid").removeClass("is-invalid");
            $("#prescription_modal .error_container").addClass("d-none");
            $("#prescription_modal .error_list").empty();


            const remarks = $("#form_remarks").val().trim();
            if (remarks == "") {
                is_valid = false;
                $("#prescription_modal .error_list").append("<li>Please specify reason for the deletion of Rx</li>");
            }

            if (!is_valid) {
                $("#prescription_modal .error_container").removeClass("d-none");
                return;
            }

            if (confirm("Are you sure you want to delete this prescription?")) {
                deletePrescription({id: prescription_id, remarks: remarks});
            }
        });

        $("#btn_submit").click(function () {
            const hospital_id = $("#form_hospital_id").text().trim();
            const name = $("#form_patient").text().trim();
            const birthdate = moment($("#form_birthdate").text().trim(), "MM/DD/YYYY").format("YYYY-MM-DD");
            const sex = $("#form_sex").text().trim();
            const station = $("#form_station").val();
            const ward = $("#form_ward").val();
            const bed_no = $("#form_bed_no").val();
            const attending_physician = $("#form_attending_physician").val();
            const remarks = $("#form_remarks").val();
            const urgency = ($("#checkbox_urgent").is(":checked")) ? "Urgent" : "Normal";
            const type = $("#form_prescription_type").val();
            const drug_allergy = $("#form_drug_allergy").val();
            const weight = $("#form_weight").val();
            const height = $("#form_height").val();
            const serum_creatinine = $("#form_serum_creatinine").val();
            const creatinine_clearance = $("#form_creatinine_clearance").val();
            const transaminases = $("#form_transaminases").val();
            const sgpt = $("#form_sgpt").val().trim();
            const sgot = $("#form_sgot").val().trim();
            const days_requested = $("#form_days_requested").val().trim();

            let params = {};
            let is_valid = true;
            $("#prescription_modal .is-invalid").removeClass("is-invalid");
            $("#prescription_modal .error_container").addClass("d-none");
            $("#prescription_modal .error_list").empty();

            if (hospital_id == "") {
                is_valid = false;
                $("#prescription_modal .error_list").append("<li>Hospital ID is required</li>");
            }

            if (name == "") {
                is_valid = false;
                $("#prescription_modal .error_list").append("<li>Patient is required</li>");
            }

            if (birthdate == "") {
                is_valid = false;
                $("#prescription_modal .error_list").append("<li>Birthdate is required</li>");
            }

            if (sex == "") {
                is_valid = false;
                $("#prescription_modal .error_list").append("<li>Sex is required</li>");
            }

            if (station == "") {
                is_valid = false;
                $("#prescription_modal .error_list").append("<li>Station is required</li>");
            }

            if (ward == "") {
                is_valid = false;
                $("#prescription_modal .error_list").append("<li>Ward is required</li>");
            }

            if (bed_no == "") {
                is_valid = false;
                $("#prescription_modal .error_list").append("<li>Bed no is required</li>");
            }

            if (attending_physician == "") {
                is_valid = false;
                $("#prescription_modal .error_list").append("<li>Attending physician is required</li>");
            }

            if (type == "") {
                is_valid = false;
                $("#prescription_modal .error_list").append("<li>Prescription type is required</li>");
            }

            const microbiologicals = [];
            const concurrent_therapy = [];
            let intervention = {}
            //CHECK IF PRESCRIPTION TYPE IS AMS
            const medications = [];

            if (type != 6) {
                if (type !== "1") {
                    if (drug_allergy == "") {
                        is_valid = false;
                        $("#prescription_modal .error_list").append("<li>Drug allergy is required, put N/A if not applicable</li>");
                    }

                    if (weight == "") {
                        is_valid = false;
                        $("#prescription_modal .error_list").append("<li>Weight is required, put '0' if not applicable</li>");
                    }

                    if (height == "") {
                        is_valid = false;
                        $("#prescription_modal .error_list").append("<li>Height is required, put '0' if not applicable</li>");
                    }

                    if (serum_creatinine == "") {
                        is_valid = false;
                        $("#prescription_modal .error_list").append("<li>Serum creatinine is required, put '0' if not applicable</li>");
                    }

                    if (creatinine_clearance == "") {
                        is_valid = false;
                        $("#prescription_modal .error_list").append("<li>Creatinine clearance is required, put '0' if not applicable</li>");
                    }

                    if (sgpt == "" && type == "3") {
                        is_valid = false;
                        $("#prescription_modal .error_list").append("<li>SGPT is required, put '0' if not applicable</li>");
                    }

                    if (sgot == "" && type == "3") {
                        is_valid = false;
                        $("#prescription_modal .error_list").append("<li>SGOT is required, put '0' if not applicable</li>");
                    }

                    if (transaminases == "" && type == "2") {
                        is_valid = false;
                        $("#prescription_modal .error_list").append("<li>Transaminases is required, put '0' if not applicable</li>");
                    }

                    if (days_requested == "" && type == "3") {
                        is_valid = false;
                        $("#prescription_modal .error_list").append("<li>Duration requested is required, put '0' if not applicable</li>");
                    }

                    if (type == "2") {
                        const indication_use = $("input[name='indication_use']:checked").next().text().trim();
                        if (indication_use == "") {
                            is_valid = false;
                            $("#prescription_modal .error_list").append("<li>Indication for use is required</li>");
                        }

                        params.indication_use = {
                            description: indication_use
                        };
                    }

                    for (let i = 0; i < $("#concurrent_list tr").length; i++) {
                        const concurrent_date = $("#concurrent_list tr:eq(" + i + ")").find("td:eq(0)").text();
                        const antimicrobial = $("#concurrent_list tr:eq(" + i + ")").find("td:eq(1)").text();
                        const dosing_regimen = $("#concurrent_list tr:eq(" + i + ")").find("td:eq(2)").text();
                        const start_date = $("#concurrent_list tr:eq(" + i + ")").find("td:eq(3)").text();
                        const stop_date = $("#concurrent_list tr:eq(" + i + ")").find("td:eq(4)").text();
                        const buttons = $("#concurrent_list tr:eq(" + i + ")").find("td:eq(5) button");
                        const days_approved = $("#concurrent_list tr:eq(" + i + ") td:nth-last-child(2)").text();
                        const for_switch = ($("#concurrent_list tr:eq(" + i + ")").hasClass("active")) ? 1 : 0;
                        //CHECK IF NEWLY ADDED
                        if (buttons.length > 0) {
                            concurrent_therapy.push({
                                hospital_id: hospital_id,
                                concurrent_date: moment(concurrent_date).format("YYYY-MM-DD"),
                                antimicrobial: antimicrobial,
                                dosing_regimen: dosing_regimen,
                                start_date: moment(start_date).format("YYYY-MM-DD"),
                                stop_date: moment(stop_date).format("YYYY-MM-DD"),
                                days_approved: days_approved,
                                for_switch: for_switch
                            });
                        }
                    }

                    let concurrent_id = 0;
                    if ($("#concurrent_list tr.active td:last-child button").length < 1) {
                        concurrent_id = $("#concurrent_list tr.active td:last-child").text();
                    }

                    intervention = {
                        concurrent_id: concurrent_id
                    }

                    for (let i = 0; i < $("#microbiological_result_list tr").length; i++) {
                        const date = $("#microbiological_result_list tr:eq(" + i + ")").find("td:eq(0)").text();
                        const specimen = $("#microbiological_result_list tr:eq(" + i + ")").find("td:eq(1)").text();
                        const pathogen = $("#microbiological_result_list tr:eq(" + i + ")").find("td:eq(2)").text();

                        microbiologicals.push({
                            date: moment(date).format("YYYY-MM-DD"),
                            specimen: specimen,
                            pathogen: pathogen
                        });
                    }
                }


                for (let i = 0; i < $("#medication_list tr").length; i++) {
                    let medication_flag = true;
                    const description = $("#medication_list tr:eq(" + i + ")").find("td:eq(0)").text();
                    const dosage_strength = $("#medication_list tr:eq(" + i + ")").find("td:eq(1) input").val();
                    const dosage_unit = $("#medication_list tr:eq(" + i + ")").find("td:eq(1) select").val();
                    const unit = $("#medication_list tr:eq(" + i + ")").find("td:eq(2)").text();
                    const quantity_ordered = $("#medication_list tr:eq(" + i + ")").find("td:eq(3) input").val();
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
                            quantity_ordered: 0,
                            frequency: frequency
                        });
                    }
                }

                if (medications.length < 1 && is_valid) {
                    is_valid = false;
                    $("#prescription_modal .error_list").append("<li>There must be a medication</li>");
                }

                if (medications.length > 1 && is_valid && (type != "1" && type != "5")) {
                    is_valid = false;
                    $("#prescription_modal .error_list").append("<li>Maximum of 1 medication per prescription</li>");
                }

                if (remarks == "") {
                    is_valid = false;
                    $("#prescription_modal .error_list").append("<li>Please specify remarks or instructions for the receiver</li>");
                }

                if ($("#concurrent_list tr.active").length < 1 && type == "4") {
                    is_valid = false;
                    $("#prescription_modal .error_list").append("<li>Please select concurrent AMS therapy to be switched</li>");
                }
            }

            if (!is_valid) {
                $("#prescription_modal .error_container").removeClass("d-none");
                return;
            }

            const patient = {
                hospital_id: hospital_id,
                name: name,
                birthdate: birthdate,
                sex: sex,
                station: station,
                ward: ward,
                bed_no: bed_no,
                attending_physician: attending_physician,
                remarks: remarks,
                urgency: urgency,
                type: type,
                drug_allergy: drug_allergy,
                weight: weight,
                height: height,
                serum_creatinine: serum_creatinine,
                creatinine_clearance: creatinine_clearance,
                transaminases: transaminases,
                sgpt: sgpt,
                sgot: sgot,
                days_requested: days_requested
            }

            params.id = prescription_id;
            params.patient = patient;
            params.medications = medications;
            if (type != "1") {
                if (type == "4")
                    params.intervention = intervention;
                else
                    params.microbiologicals = microbiologicals;

                params.concurrent_therapy = concurrent_therapy;
            }

            updatePrescription(params);
        });

        getPrescriptions();
    });

    function viewPrescription(id) {
        prescription_id = id;
        $("#btn_submit").val(id);
        $("#btn_reject").val(id);
        $("#btn_add_concurrent").addClass("d-none");
        $("#btn_reject").text("DELETE RX");
        $("#btn_submit").text("UPDATE");

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


        $("#btn_reject").text("Delete RX");

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
                    if ($("#form_prescription_type").val() == "4") {
                        tr = "<tr class='pointer'>";
                        if (it.for_switch == prescription_id)
                            tr = "<tr class='pointer active'>";
                    }
                    tr += "<td>" + moment(it.concurrent_date).format("MM/DD/YYYY") + " </td>";
                    tr += "<td>" + it.antimicrobial + "</td>";
                    tr += "<td>" + it.dosing_regimen + "</td>";
                    tr += "<td>" + moment(it.start_date).format("MM/DD/YYYY") + "</td>";
                    tr += "<td>" + moment(it.stop_date).format("MM/DD/YYYY") + "</td>";
                    tr += "<td></td>";
                    tr += "<td></td>";
                    tr += "<td class='d-none'>" + it.id + "</td>";
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

                    for (let i = 0; i < data.results.length; i++) {
                        const it = data.results[i];
                        let tr = "<tr>";
                        tr += "<td>" + it.description + "</td>";
                        tr += "<td>";
                        tr += "<div class='d-flex'>";
                        tr += "<input class='form-control text-center w-50' type='number' value='" + it.dosage_strength + "'/>"
                        tr += "<select class='form-control text-center w-50'>";
                        for (let j = 0; j < STRENGTH_UNIT.length; j++) {
                            if (it.dosage_unit == STRENGTH_UNIT[j])
                                tr += "<option selected>" + STRENGTH_UNIT[j] + "</option>"
                            else
                                tr += "<option>" + STRENGTH_UNIT[j] + "</option>"
                        }
                        tr += "</select>";
                        tr += "</div>";
                        tr += "</td>";
                        tr += "<td>" + it.unit + "</td>";
                        tr += "<td class='d-none'><input class='form-control text-center' type='number' value='" + it.quantity_ordered + "'/></td>"
                        tr += "<td>";
                        tr += "<select class='form-control text-center'>";
                        for (let j = 0; j < FREQUENCIES.length; j++) {
                            if (it.frequency == FREQUENCIES[j])
                                tr += "<option selected>" + FREQUENCIES[j] + "</option>"
                            else
                                tr += "<option>" + FREQUENCIES[j] + "</option>"
                        }
                        tr += "</select>";
                        tr += "</td>";
                        tr += "<td>";
                        tr += "<button class='btn btn-sm btn-success' onclick='editMedicine(" + i + ")'><i class='bi bi-pencil-fill'></i></button>"
                        tr += "</td>";
                        tr += "<td>";
                        tr += "<button class='btn btn-sm btn-danger' onclick='deleteMedicine(" + i + ")'><i class='bi bi-trash-fill'></i></button>"
                        tr += "</td>";
                        tr += "</tr>";

                        $("#medication_list").append(tr);
                    }
                }
            }).catch(err => {
            console.log(err);
            toastr.error("Something went wrong", "Ooops");
        });
    }

    function editMedicine(id) {
        prescribed_med_id = id;
        medicine_modal.toggle();
        $("#search_medicine").val($("#medication_list tr:eq(" + id + ") td:eq(0)").text().trim());
        getMedicine($("#search_medicine").val());
    }

    function editMicrobiological(id) {
        microbiological_modal.toggle();
        //SET FORMS DATA
        const date = $("#microbiological_result_list tr:eq(" + id + ") td:eq(0)").text().trim();
        const specimen = $("#microbiological_result_list tr:eq(" + id + ") td:eq(1)").text().trim();
        const pathogen = $("#microbiological_result_list tr:eq(" + id + ") td:eq(2)").text().trim();

        $("#microbiological_date").val(moment(date, "MM/DD/YYYY").format("YYYY-MM-DD"));
        $("#microbiological_specimen").val(specimen);
        $("#microbiological_pathogen").val(pathogen);
        $("#btn_save_microbiological").val(id);
    }

    function deleteMedicine(id) {
        $("#medication_list tr:eq(" + id + ")").remove();
    }

    function deleteMicrobiological(id) {
        $("#microbiological_result_list tr:eq(" + id + ")").remove();
    }

    $("#btn_save_microbiological").click(function () {
        const date = $("#microbiological_date").val();
        const specimen = $("#microbiological_specimen").val();
        const pathogen = $("#microbiological_pathogen").val();

        let is_valid = true;
        $("#microbiological_modal .is-invalid").removeClass("is-invalid");
        $("#microbiological_modal .error_container").addClass("d-none");
        $("#microbiological_modal .error_list").empty();

        if (date == "") {
            is_valid = false;
            $("#microbiological_modal .error_list").append("<li>Date is required</li>");
        }

        if (specimen == "") {
            is_valid = false;
            $("#microbiological_modal .error_list").append("<li>Specimen is required</li>");
        }

        if (pathogen == "") {
            is_valid = false;
            $("#microbiological_modal .error_list").append("<li>Pathogen is required</li>");
        }

        if (!is_valid) {
            $("#microbiological_modal .error_container").removeClass("d-none");
            return;
        }

        const count = $("#microbiological_result_list tr").length;
        if ($(this).val() == "") {
            let tr = "<tr>";
            tr += "<td>" + moment(date).format("MM/DD/YYYY") + " </td>";
            tr += "<td>" + specimen + "</td>";
            tr += "<td>" + pathogen + "</td>";
            tr += "<td>";
            tr += "<button class='btn btn-sm btn-success' onclick='editMicrobiological(" + count + ")'><i class='bi bi-pencil-fill'></i></button>"
            tr += "</td>";
            tr += "<td>";
            tr += "<button class='btn btn-sm btn-danger' onclick='deleteMicrobiological(" + count + ")'><i class='bi bi-trash-fill'></i></button>"
            tr += "</td>";
            tr += "</tr>";

            $("#microbiological_result_list").append(tr);
        } else {
            $("#microbiological_result_list tr:eq(" + $(this).val() + ") td:eq(0)").text(moment(date).format("MM/DD/YYYY"));
            $("#microbiological_result_list tr:eq(" + $(this).val() + ") td:eq(1)").text(specimen);
            $("#microbiological_result_list tr:eq(" + $(this).val() + ") td:eq(2)").text(pathogen);
        }

        clearMicrobiological();
    });

    function appendMedicine(id) {
        const description = $("#" + id + " td:eq(0)").text();
        if (checkIfMedExists(description)) {
            alert(description + " already exists in prescription");
            return;
        }
        const unit = $("#" + id + " td:eq(1)").text();
        const count = $("#medication_list tr").length;

        if (prescribed_med_id === "") {
            //APPEND NEW RECORD
            if (count >= 1 && $("#form_prescription_type").val() != "1") {
                alert("Maximum of 1 medication only if Antiobiotic prescription");
                return;
            }

            let tr = "<tr>";
            tr += "<td>" + description + "</td>";
            tr += "<td>";
            tr += "<div class='d-flex'>";
            tr += "<input class='form-control text-center w-50' type='number'/>"
            tr += "<select class='form-control text-center w-50'>";
            for (let i = 0; i < STRENGTH_UNIT.length; i++) {
                tr += "<option>" + STRENGTH_UNIT[i] + "</option>"
            }
            tr += "</select>";
            tr += "</div>";
            tr += "</td>";
            tr += "<td class='d-none'>" + unit + "</td>";
            tr += "<td class='d-none'><input class='form-control text-center' type='number' value='1' min='1'/></td>";
            tr += "<td>";
            tr += "<select class='form-control text-center'>";
            for (let i = 0; i < FREQUENCIES.length; i++) {
                tr += "<option>" + FREQUENCIES[i] + "</option>"
            }
            tr += "</select>";
            tr += "</td>";
            tr += "<td>";
            tr += "<button class='btn btn-sm btn-success' onclick='editMedicine(" + count + ")'><i class='bi bi-pencil-fill'></i></button>"
            tr += "</td>";
            tr += "<td>";
            tr += "<button class='btn btn-sm btn-danger' onclick='deleteMedicine(" + count + ")'><i class='bi bi-trash-fill'></i></button>"
            tr += "</td>";
            tr += "</tr>";

            $("#medication_list").append(tr);
        } else {
            $("#medication_list tr:eq(" + prescribed_med_id + ") td:eq(0)").text(description);
            $("#medication_list tr:eq(" + prescribed_med_id + ") td:eq(1) input").val("");
            $("#medication_list tr:eq(" + prescribed_med_id + ") td:eq(1) select").val("");
            $("#medication_list tr:eq(" + prescribed_med_id + ") td:eq(2)").text(unit);
            $("#medication_list tr:eq(" + prescribed_med_id + ") td:eq(3) input").val("");
            $("#medication_list tr:eq(" + prescribed_med_id + ") td:eq(4) select").val("");
        }
    }

    function getMedicine(key) {
        fetch("{{ url('medicine/get') }}?medicine=" + key +
            "&type=" + $("#form_prescription_type").val(), {
                method: "GET"
            }
        )
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
            toastr.error("Something went wrong", "Ooops");
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
            toastr.error("Something went wrong", "Ooops");
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
                    $("#prescription_list").append("<tr><td colspan='7'>No record found</td></tr>");
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
                                <td class="d-none">` + it.days_requested + `</td>
                                <td class="d-none">` + it.attending_physician + `</td>
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
            toastr.error("Something went wrong", "Ooops");
        });
    }

    function deletePrescription(params) {
        prescription_modal.toggle();
        fetch('{{ route('delete_prescription') }}', {
            method: "DELETE",
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
            }).catch(err => {
            console.log(err);
            toastr.error("Something went wrong", "Ooops");
        });
    }

    function updatePrescription(params) {
        prescription_modal.toggle();
        $("#spinner").removeClass("d-none");
        fetch('{{ route('update_prescription') }}', {
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

                if ($("#form_prescription_type").val() !== "1" && $("#form_prescription_type").val() !== "5") {
                    insert("ICC", {id: 1}, function (message) {
                        deleteChild("ICC");
                    });
                } else {
                    insert("stations/" + params.patient.station, {id: 1}, function (message) {
                        deleteChild("stations/" + params.patient.station);
                    });
                }

                getPrescriptions();
            }).catch(err => {
            console.log(err);
            toastr.error("Something went wrong", "Ooops");
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

    function clearConcurrent() {
        $("#concurrent_date").val("");
        $("#concurrent_antimicrobial").val("");
        $("#concurrent_dosing_regimen").val("");
        $("#concurrent_start_date").val("");
        $("#concurrent_stop_date").val("");
    }

    function clearMicrobiological() {
        $("#microbiological_date").val("");
        $("#microbiological_specimen").val("");
        $("#microbiological_pathogen").val("");
    }

    function checkIfMedExists(_description) {
        let is_exists = false;
        for (let i = 0; i < $("#medication_list tr").length; i++) {
            const description = $("#medication_list tr:eq(" + i + ") td:eq(0)").text().trim();
            if (_description == description) {
                is_exists = true;
                break;
            }
        }
        return is_exists;
    }

    function logout() {
        window.location.href = '{{ route('logout') }}';
    }
</script>
