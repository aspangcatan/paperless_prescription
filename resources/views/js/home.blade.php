<script src="js/moment.min.js"></script>
<script src="js/constant.js?v={{ date('ymdhis') }}"></script>
<script src="js/websocket.js?v={{ date('ymdhis') }}"></script>
<script>
    const prescription_modal = new bootstrap.Modal(document.getElementById('prescription_modal'));
    const patient_modal = new bootstrap.Modal(document.getElementById('patient_modal'));
    const medicine_modal = new bootstrap.Modal(document.getElementById('medicine_modal'));
    const microbiological_modal = new bootstrap.Modal(document.getElementById('microbiological_modal'));
    const concurrent_modal = new bootstrap.Modal(document.getElementById('concurrent_modal'));

    const success_modal = new bootstrap.Modal(document.getElementById('success_modal'));
    const error_modal = new bootstrap.Modal(document.getElementById('error_modal'));
    const warning_modal = new bootstrap.Modal(document.getElementById('warning_modal'));
    let prescription_id = "";
    let page = 0;
    let is_loaded = false;
    const db_ref = "prescriber/" + $("#code").text().trim();
    $(document).ready(function () {
        $("#btn_download").removeClass("d-none");

        observe(db_ref, function (snapshot) {
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

        $("#btn_reject").addClass("d-none");

        STATIONS.forEach(it => {
            $("#form_station").append("<option>" + it + "</option>");
        });

        $("#filter_date").focusin(function () {
            $(this).attr('type', 'date')
        });

        $("#filter_date").focusout(function () {
            $(this).attr('type', 'text')
        });

        $("#btn_download").click(function () {
            const button = $(this);
            const hospital_id = $("#form_hospital_id").text().trim();
            button.prop("disabled", true);
            fetch('{{ route('getLatestPrescription') }}?hospital_id=' + hospital_id, {
                method: "GET"
            })
                .then(res => res.json())
                .then(data => {
                    button.prop("disabled", false);
                    if (data.code == 500) {
                        toastr.error(data.message, "Ooops");
                        return;
                    }

                    const info = data.result;
                    $("#form_station").val(info.station);
                    $("#form_ward").val(info.ward);
                    $("#form_prescription_type").val(info.type).change();
                    $("#form_attending_physician").val(info.attending_physician);
                    $("#form_drug_allergy").val(info.drug_allergy);
                    $("#form_weight").val(info.weight);
                    $("#form_height").val(info.height);
                    $("#form_serum_creatinine").val(info.serum_creatinine);
                    $("#form_creatinine_clearance").val(info.creatinine_clearance);
                    $("#form_transaminases").val(info.transaminases);
                    $("#form_sgpt").val(info.sgpt);
                    $("#form_sgot").val(info.sgot);
                    $("#additional_labs").val(info.additional_labs);

                }).catch(err => {
                toastr.error(err, "Ooops");
            })
        });

        $("#btn_notification").click(function () {
            if (notification_counter > 0) {
                window.location.href = '{{ route('returned_prescription') }}';
            }
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

        $("body").on("click", "#concurrent_list tr", function () {
            $("#concurrent_list tr.active").removeClass("active");
            if ($("#form_prescription_type").val() == "4" || $("#form_prescription_type").val() == "7") {
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
            }
        });

        $("#btn_search_prescription").click(function () {
            page = 0;
            getPrescriptions();
        });

        $("#btn_search_patient").click(function () {
            const search_keyword = $("#search_keyword").val().trim();
            if (!search_keyword) return;

            searchPatient(search_keyword);
        });

        $("#btn_add_prescription").click(function () {
            clearForms();
            patient_modal.toggle();
        });

        $("#btn_add_concurrent").click(function () {
            $("#btn_save_concurrent").val("");
            clearConcurrent();
            concurrent_modal.toggle();
        });

        $("#btn_add_microbiological").click(function () {
            $("#btn_save_microbiological").val("");
            clearMicrobiological();
            microbiological_modal.toggle();
        });

        $("#btn_view_cart").click(function () {
            prescribed_med_id = "";
            medicine_modal.toggle();
            getMedicine("");
        });

        $("#btn_find").click(function () {
            const key = $("#hospital_id").val().trim();
            if (key == "") return;

            $(this).prop("disabled", true);
            $(this).html(` <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                           <span class="sr-only">Loading...</span>`);
            $("#fname").val("")
            $("#mname").val("")
            $("#lname").val("")
            $("#suffix").val("")
            $("#birthdate").val("");
            $("#sex").val("");

            fetch("{{ url('homis_patient/get') }}/" + key, {
                method: "GET"
            })
                .then(res => res.json())
                .then(data => {

                    $("#btn_find").prop("disabled", false);
                    $("#btn_find").html("SEARCH");

                    if (data.code == 500) {
                        console.log(data.message);
                        return;
                    }

                    if (!data.result) {
                        alert("No patient found");
                        return;
                    }

                    $("#hospital_id").val(data.result.hpatcode)
                    $("#fname").val(data.result.patfirst)
                    $("#mname").val(data.result.patmiddle)
                    $("#lname").val(data.result.patlast)
                    $("#suffix").val(data.result.patsuffix)
                    $("#birthdate").val(moment(data.result.patbdate).format("YYYY-MM-DD"));
                    $("#sex").val(data.result.patsex);
                }).catch(err => {
                toastr.error("Something went wrong", "Ooops");
            });
        });

        $("#btn_save_patient").click(function () {
            const hospital_id = $("#hospital_id").val().trim();
            const fname = $("#fname").val().trim().toUpperCase();
            const mname = $("#mname").val().trim().toUpperCase();
            const lname = $("#lname").val().trim().toUpperCase();
            const suffix = $("#suffix").val().trim().toUpperCase();
            const birthdate = $("#birthdate").val();
            const sex = $("#sex").val();

            if (fname == "" || lname == "") {
                alert("Please search patient before adding/updating");
                return;
            }

            const params = {
                hospital_id: hospital_id,
                fname: fname,
                mname: mname,
                lname: lname,
                suffix: suffix,
                birthdate: birthdate,
                sex: sex
            }

            $(this).prop("disabled", true);
            $(this).html(` <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                           <span class="sr-only">Loading...</span>`);

            insertPatient(params);
        });

        $("#btn_save_concurrent").click(function () {
            const concurrent_date = $("#concurrent_date").val();
            const antimicrobial = $("#concurrent_antimicrobial").val().trim();
            const dosing_regimen = $("#concurrent_dosing_regimen").val().trim();
            const start_date = $("#concurrent_start_date").val();
            const stop_date = $("#concurrent_stop_date").val();

            let is_valid = true;
            $("#concurrent_modal .is-invalid").removeClass("is-invalid");
            $("#concurrent_modal .error_container").addClass("d-none");
            $("#concurrent_modal .error_list").empty();

            if (concurrent_date == "") {
                is_valid = false;
                $("#concurrent_modal .error_list").append("<li>Date is required</li>");
            }

            if (antimicrobial == "") {
                is_valid = false;
                $("#concurrent_modal .error_list").append("<li>Antimicrobial is required</li>");
            }

            if (dosing_regimen == "") {
                is_valid = false;
                $("#concurrent_modal .error_list").append("<li>Dosing regimen is required</li>");
            }

            if (start_date == "") {
                is_valid = false;
                $("#concurrent_modal .error_list").append("<li>Start date is required</li>");
            }

            if (stop_date == "") {
                is_valid = false;
                $("#concurrent_modal .error_list").append("<li>Stop date is required</li>");
            }


            if (moment(stop_date).diff(moment(start_date), 'days') < 0) {
                is_valid = false;
                $("#concurrent_modal .error_list").append("<li>Stop date must be equal or greater than start date</li>");
            }

            if (!is_valid) {
                $("#concurrent_modal .error_container").removeClass("d-none");
                return;
            }

            const days_approved = moment(stop_date).diff(moment(start_date), 'days') + 1;
            if ($(this).val() == "") {
                const count = $("#concurrent_list tr").length;
                let tr = "<tr>";
                if ($("#form_prescription_type").val() == "4" || $("#form_prescription_type").val() == "7")
                    tr = "<tr class='pointer'>";

                tr += "<td>" + moment(concurrent_date).format("MM/DD/YYYY") + " </td>";
                tr += "<td>" + antimicrobial + "</td>";
                tr += "<td>" + dosing_regimen + "</td>";
                tr += "<td>" + moment(start_date).format("MM/DD/YYYY") + "</td>";
                tr += "<td>" + moment(stop_date).format("MM/DD/YYYY") + "</td>";
                tr += "<td>";
                tr += "<button class='btn btn-sm btn-success' onclick='editConcurrent(" + count + ")'><i class='bi bi-pencil-fill'></i></button>"
                tr += "</td>";
                tr += "<td>";
                tr += "<button class='btn btn-sm btn-danger' onclick='deleteConcurrent(" + count + ")'><i class='bi bi-trash-fill'></i></button>"
                tr += "</td>";
                tr += "<td class='d-none'>" + days_approved + "</td>"
                tr += "<td class='d-none'>0</td>"
                tr += "</tr>";

                $("#concurrent_list").append(tr);
                toastr.success('Information', 'New item added')
            } else {
                $("#concurrent_list tr:eq(" + $(this).val() + ") td:eq(0)").text(moment(concurrent_date).format("MM/DD/YYYY"));
                $("#concurrent_list tr:eq(" + $(this).val() + ") td:eq(1)").text(antimicrobial);
                $("#concurrent_list tr:eq(" + $(this).val() + ") td:eq(2)").text(dosing_regimen);
                $("#concurrent_list tr:eq(" + $(this).val() + ") td:eq(3)").text(moment(start_date).format("MM/DD/YYYY"));
                $("#concurrent_list tr:eq(" + $(this).val() + ") td:eq(4)").text(moment(stop_date).format("MM/DD/YYYY"));
                toastr.success('Information', 'Item updated')
            }
            clearConcurrent();
        });

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
                let tr = "<tr id='micro_id_" + count + "'>";
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
                toastr.success('Information', 'New item added');
            } else {
                $("#microbiological_result_list tr:eq(" + $(this).val() + ") td:eq(0)").text(moment(date).format("MM/DD/YYYY"));
                $("#microbiological_result_list tr:eq(" + $(this).val() + ") td:eq(1)").text(specimen);
                $("#microbiological_result_list tr:eq(" + $(this).val() + ") td:eq(2)").text(pathogen);
                toastr.success('Information', 'Item updated');
            }

            clearMicrobiological();
        });

        $("#btn_submit").click(function () {
            const hospital_id = $("#form_hospital_id").text().trim();
            const name = $("#form_patient").text().trim();
            const birthdate = moment($("#form_birthdate").text().trim(), "MM/DD/YYYY").format("YYYY-MM-DD");
            const sex = $("#form_sex").text().trim();
            const station = $("#form_station").val();
            const ward = $("#form_ward").val();
            const bed_no = "N/A"
            const attending_physician = $("#form_attending_physician").val();
            let remarks = $("#form_remarks").val();
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
            const additional_labs = $("#additional_labs").val().trim();
            const diagnosis = $("#diagnosis").val().trim();
            let outcome = null;

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

            if (attending_physician == "") {
                is_valid = false;
                $("#prescription_modal .error_list").append("<li>Attending physician is required</li>");
            }

            if (type == "") {
                is_valid = false;
                $("#prescription_modal .error_list").append("<li>Prescription type is required</li>");
            }

            if (diagnosis == "") {
                is_valid = false;
                $("#prescription_modal .error_list").append("<li>Diagnosis is required</li>");
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

                    if (additional_labs == "" && type == "2") {
                        is_valid = false;
                        $("#prescription_modal .error_list").append("<li>Please specify Additional Labs, put 'N/A' if not applicable</li>");
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

                if ($("#concurrent_list tr.active").length < 1 && (type == "4" || type == "7")) {
                    is_valid = false;
                    $("#prescription_modal .error_list").append("<li>Please select concurrent AMS therapy to be switched</li>");
                }
            } else {
                outcome = $("input[name='outcome']:checked").next().text().trim();

                if (outcome == "Others") {
                    outcome = $("#outcome").val().trim();
                }

                remarks = outcome;

                if (outcome == "") {
                    is_valid = false;
                    $("#prescription_modal .error_list").append("<li>Please specify patient outcome</li>");
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
                days_requested: days_requested,
                additional_labs: additional_labs,
                outcome: outcome,
                diagnosis: diagnosis
            }

            params.patient = patient;
            if (type != "6") {
                params.medications = medications;
                if (type != "1") {
                    params.concurrent_therapy = concurrent_therapy;
                    if (type == "4")
                        params.intervention = intervention;
                    else {
                        if (type == "7")
                            params.intervention = intervention;
                        params.microbiologicals = microbiologicals;
                    }

                }
            }

            insertPrecription(params);
        });

        $("#search_medicine").keypress(function (e) {
            if (e.which == 13) {
                getMedicine($(this).val().trim());
            }
        });

        $("#btn_search_local").click(function () {
            const firstname = $("#search_firstname").val().trim();
            const lastname = $("#search_lastname").val().trim();

            if (firstname == "" && lastname == "") return;

            getPatients(firstname, lastname);
        });

        $("#search_firstname,#search_lastname").keypress(function (e) {
            if (e.which == 13) {
                const firstname = $("#search_firstname").val().trim();
                const lastname = $("#search_lastname").val().trim();

                if (firstname == "" && lastname == "") return;

                getPatients(firstname, lastname);
            }
        });

        getPrescriptions();
    });

    function searchPatient(key) {
        fetch("{{ url('patient/get') }}/" + key, {
            method: "GET"
        })
            .then(res => res.json())
            .then(data => {
                if (data.code == 500) {
                    alert("Something went wrong while searching patient");
                    console.log(data.message)
                    return;
                }

                if (data.result.length < 1) {
                    return alert("Patient not found");
                }

                $("#fname").val(data.result[0].patfirst);
                $("#mname").val(data.result[0].patmiddle);
                $("#lname").val(data.result[0].patlast);
                $("#suffix").val(data.result[0].patsuffix);
                $("#birthdate").val(data.result[0].patbdate);
                $("#sex").val(data.result[0].patsex);

            }).catch(err => {
            console.log(err);
            toastr.error("Something went wrong", "Ooops");
        });
    }


    function makePrescription(id) {
        prescription_id = "";
        const hospital_id = $("#patient_id_" + id + " td:eq(0)").text();
        const name = $("#patient_id_" + id + " td:eq(1)").text();
        const birthdate = $("#patient_id_" + id + " td:eq(2)").text();
        const sex = $("#patient_id_" + id + " td:eq(3)").text();

        $("#concurrent_table").removeClass("table-hover");
        $("#concurrent_table tbody tr").removeClass("table-hover");
        $("#form_hospital_id").text(hospital_id);
        $("#form_patient").text(name);
        $("#form_birthdate").text(birthdate);
        $("#form_age").text(moment().diff(moment(birthdate, "MM/DD/YYYY"), 'years'));
        $("#form_sex").text(sex);
        $("#form_station").val("");
        $("#form_ward").val("");
        $("#form_bed_no").val("");
        $("#form_prescription_type").val("").change();
        $("#form_remarks").val("");
        $("#checkbox_urgent").prop("checked", false);
        $("#checkbox_urgent").prop("disabled", false);
        $("#medication_list").empty();

        $("#prescription_modal input").prop("disabled", false);
        $("#prescription_modal input[type='checkbox']").prop("disabled", false);
        $("#prescription_modal input[type='radio']").prop("disabled", false);
        $("#prescription_modal select").prop("disabled", false);
        $("#prescription_modal textarea").prop("disabled", false);

        $("#btn_view_cart").removeClass("d-none");
        $("#btn_add_microbiological").removeClass("d-none");
        $("#btn_add_concurrent").removeClass("d-none");
        $("#btn_submit").removeClass("d-none");
        $("#form_remarks").removeClass("d-none");
        $("#transaction_container").addClass("d-none");
        $(".cancellation_container").addClass("d-none");

        patient_modal.toggle();
        prescription_modal.toggle();
        $("#btn_download").prop("disabled", false);
        getConcurrentTherapy(hospital_id);
    }

    function viewPrescription(id) {
        prescription_id = id;
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
        $("#diagnosis").val(diagnosis);

        $("input[name='outcome']").prop("checked", false);
        $("#outcome").val("");
        if (type == "6") {
            const outcome_index = OUTCOMES.indexOf(outcome);
            if (outcome_index < 0) {
                $("input[name='outcome']:eq(5)").prop("checked", true);
                $("#outcome").val(outcome);
            } else {
                $("input[name='outcome']:eq(" + outcome_index + ")").prop("checked", true);
            }
        }

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
        fetch("{{ url('prescription/get') }}/" + id, {
            method: "GET"
        })
            .then(res => res.json())
            .then(data => {
                $("#medication_list").empty();
                $("#microbiological_result_list").empty();
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
                        for (let i = 0; i < ams.microbiologicals.length; i++) {
                            const it = ams.microbiologicals[i];
                            let tr = "<tr id='micro_id_" + i + "'>";
                            tr += "<td>" + moment(it.date).format("MM/DD/YYYY") + " </td>";
                            tr += "<td>" + it.specimen + "</td>";
                            tr += "<td>" + it.pathogen + "</td>";
                            tr += "<td>";
                            tr += "<button class='btn btn-sm btn-success' onclick='editMicrobiological(" + i + ")'><i class='bi bi-pencil-fill'></i></button>"
                            tr += "</td>";
                            tr += "<td>";
                            tr += "<button class='btn btn-sm btn-danger' onclick='deleteMicrobiological(" + i + ")'><i class='bi bi-trash-fill'></i></button>"
                            tr += "</td>";
                            tr += "</tr>";

                            $("#microbiological_result_list").append(tr);
                        }

                    }

                    for (let i = 0; i < data.results.length; i++) {
                        const it = data.results[i];
                        let tr = "<tr id='medicine_id_" + i + "'>";
                        tr += "<td>" + it.description + "</td>";
                        tr += "<td>";
                        tr += "<div class='d-flex'>";
                        tr += "<input class='form-control text-center w-50' type='number' value='" + it.dosage_strength + "'/>"
                        tr += "<select class='form-control text-center w-50'>";
                        for (let i = 0; i < STRENGTH_UNIT.length; i++) {
                            tr += "<option>" + STRENGTH_UNIT[i] + "</option>"
                        }
                        tr += "</select>";
                        tr += "</div>";
                        tr += "</td>";
                        tr += "<td>" + it.unit + "</td>";
                        tr += "<td class='d-none'><input class='form-control text-center' type='number' value='1' min='1'/></td>";
                        tr += "<td>";
                        tr += "<select class='form-control text-center'>";
                        for (let i = 0; i < FREQUENCIES.length; i++) {
                            tr += "<option>" + FREQUENCIES[i] + "</option>"
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
                        // const tr = `<tr>
                        //                     <td>` + it.description + `</td>
                        //                     <td>` + it.dosage_strength + ' ' + it.dosage_unit + `</td>
                        //                     <td>` + it.unit + `</td>
                        //                     <td class="d-none">` + it.quantity_ordered + `</td>
                        //                     <td>` + it.frequency + `</td>
                        //                     <td></td>
                        //                  </tr>`;
                        //


                        $("#medication_list").append(tr);
                        $("#medicine_id_" + i + " td:eq(1) select").val(it.dosage_unit);
                        $("#medicine_id_" + i + " td:eq(4) select").val(it.frequency);
                    }

                }
            }).catch(err => {
            console.log(err);
            $("#medication_list").empty();
            $("#medication_list").append("<tr><td colspan='6'>Something went wrong, please reload page</td></tr>");
            $("#microbiological_result_list").empty();
            $("#microbiological_result_list").append("<tr><td colspan='5'>Something went wrong, please reload page</td></tr>");
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
                $("#transaction_list").empty();
                if (data.code == 200) {
                    data.results.forEach(it => {
                        $("#transaction_list").append(activityLog(it));
                    });
                }
            }).catch(err => {
            console.log(err);
            $("#transaction_list").empty();
            $("#transaction_list").append("<tr><td colspan='4'>Something went wrong</td></tr>");
        })
    }

    function getPatients(firstname, lastname) {
        $("#patient_list").empty();
        $("#patient_list").append("<tr><td colspan='5'>Searching patient...</td></tr>");
        fetch("{{ url('patient/get') }}?firstname=" + firstname + "&lastname=" + lastname, {
            method: "GET"
        })
            .then(res => res.json())
            .then(data => {
                $("#patient_list").empty();
                if (data.results.length < 1) {
                    $("#patient_list").append("<tr><td colspan='5'>No record found</td></tr>");
                    return;
                }

                data.results.forEach(it => {
                    const name = it.lname + ", " + it.fname + " " + it.mname;
                    const tr = `
                        <tr id='patient_id_` + it.id + `'>
                            <td>` + it.hospital_id + `</td>
                            <td>` + name + `</td>
                            <td>` + moment(it.birthdate).format("MM/DD/YYYY") + `</td>
                            <td>` + it.sex + `</td>
                            <td>
                                <button class="btn btn-sm btn-success" onclick="makePrescription(` + it.id + `)">
                                    <i class="bi bi-prescription"></i>
                                </button>
                            </td>
                        </tr>`;

                    $("#patient_list").append(tr);
                })

            }).catch(err => {
            console.log(err);
            $("#patient_list").empty();
            $("#patient_list").append("<tr><td colspan='5'>Something went wrong</td></tr>");
        });
    }

    function getMedicine(key) {
        fetch("{{ url('medicine/get') }}?medicine=" + key +
            "&type=" + $("#form_prescription_type").val(), {
                method: "GET"
            }
        )
            .then(res => res.json())
            .then(data => {
                //MUST ADD SPINNER
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
            $("#medicine_list").empty();
            $("#medicine_list").append("<tr><td colspan='3' hing went wrong, please search again</td></tr>");
        });
    }

    function insertPatient(params) {
        fetch('{{ route('insert_patient') }}', {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            body: JSON.stringify(params)
        })
            .then(res => res.json())
            .then(data => {
                $("#btn_save_patient").prop("disabled", false);
                $("#btn_save_patient").html("ADD / UPDATE");

                if (data.code == 500) {
                    toastr.success(data.message, "Something went wrong")
                    return;
                }

                $("#search_firstname").val($("#fname").val().trim());
                $("#search_lastname").val($("#lname").val().trim());
                $("#btn_search_local").click();

                $("#hospital_id").val("")
                $("#fname").val("")
                $("#mname").val("")
                $("#lname").val("")
                $("#suffix").val("")
                $("#birthdate").val("");
                $("#sex").val("");
                toastr.success(data.message, "Information");

            }).catch(err => {
            if ((err + "").includes("SyntaxError: Unexpected token")) {
                toastr.error("Please try again", "Server Connection Timeout")
                return;
            }
            toastr.error("Something went wrong", "Ooops");
        });
    }

    function insertPrecription(params) {
        $("#spinner").removeClass("d-none");
        fetch('{{ route('insert_prescription') }}', {
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

                prescription_modal.toggle();
                toastr.success(data.message, "Information");
                getPrescriptions();
                //IF TYPE IS 5 SEND TO NURSE,IPCC AND PHARMACY
                if (params.patient.type === "6") {
                    insert("ICC", {id: 1}, function (message) {
                        deleteChild("ICC");
                    });
                    insert("stations/" + params.patient.station, {id: 1}, function (message) {
                        deleteChild("stations/" + params.patient.station);
                    });
                    insert("PHARMACY", {id: 1}, function (message) {
                        deleteChild("PHARMACY");
                    });

                } else if (params.patient.type !== "1" && params.patient.type !== "5" && params.patient.type !== "8") {
                    insert("ICC", {id: 1}, function (message) {
                        deleteChild("ICC");
                    });
                } else {
                    insert("stations/" + params.patient.station, {id: 1}, function (message) {
                        deleteChild("stations/" + params.patient.station);
                    });
                }
            }).catch(err => {
            $("#spinner").addClass("d-none");
            if ((err + "").includes("SyntaxError: Unexpected token")) {
                toastr.error("Please try again", "Server Connection Timeout")
                return;
            }
            toastr.error("Something went wrong", "Ooops")
        });
    }

    function getPrescriptions() {
        const filter_patient = $("#filter_patient").val().trim();
        const filter_date = $("#filter_date").val().trim();
        $("#prescription_list").empty();
        $("#prescription_list").append("<tr><td colspan='6'>Loading data, please wait ...</td></tr>");
        fetch("{{ url('prescription/get') }}?filter_patient=" + filter_patient +
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
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="viewPrescription(` + it.id + `)">
                                        <i class="bi bi-prescription"></i>
                                    </button>
                                </td>
                            </tr>`;

                    $("#prescription_list").append(tr);
                }
                if (!is_loaded) {
                    getTotalReturned();
                }
            }).catch(err => {
            $("#prescription_list").empty();
            $("#prescription_list").append("<tr><td colspan='6'>Something went wrong, please refresh browser</td></tr>");
            console.log(err);
        });
    }

    function editMedicine(id) {
        prescribed_med_id = id;
        medicine_modal.toggle();
        $("#search_medicine").val($("#medication_list tr:eq(" + id + ") td:eq(0)").text().trim());
        getMedicine($("#search_medicine").val());
    }

    function editConcurrent(id) {
        concurrent_modal.toggle();
        //SET FORMS DATA
        const concurrent_date = $("#concurrent_list tr:eq(" + id + ") td:eq(0)").text().trim();
        const antimicrobial = $("#concurrent_list tr:eq(" + id + ") td:eq(1)").text().trim();
        const dosing_regimen = $("#concurrent_list tr:eq(" + id + ") td:eq(2)").text().trim();
        const start_date = $("#concurrent_list tr:eq(" + id + ") td:eq(3)").text();
        const stop_date = $("#concurrent_list tr:eq(" + id + ") td:eq(4)").text();

        $("#concurrent_date").val(concurrent_date);
        $("#concurrent_antimicrobial").val(antimicrobial);
        $("#concurrent_dosing_regimen").val(dosing_regimen);
        $("#concurrent_start_date").val(moment(start_date, "MM/DD/YYYY").format("YYYY-MM-DD"));
        $("#concurrent_stop_date").val(moment(stop_date, "MM/DD/YYYY").format("YYYY-MM-DD"));

        $("#btn_save_concurrent").val(id);
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

    function deleteConcurrent(id) {
        $("#concurrent_list tr:eq(" + id + ")").remove();
    }

    function deleteMicrobiological(id) {
        $("#micro_id_" + id).remove();
    }

    function appendMedicine(id) {
        //CHECK IF MEDICINE ALREADY IN PRESCRIPTIONS
        const description = $("#" + id + " td:eq(0)").text();
        const unit = $("#" + id + " td:eq(1)").text();
        if (checkIfMedExists(description)) {
            alert(description + " already exists in prescription");
            return;
        }

        const count = $("#medication_list tr").length;

        if (prescribed_med_id !== "") {
            $("#medication_list tr:eq(" + prescribed_med_id + ")").remove();
        } else if (count > 0 && ($("#form_prescription_type").val() !== "1" && $("#form_prescription_type").val() !== "5ork")) {
            alert("Maximum of 1 medication per prescription only");
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
        tr += "<td>" + unit + "</td>";
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
        $("#concurrent_list").empty();
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

    function getTotalReturned() {
        fetch("{{ url('returned_prescription/get/total') }}/", {
            method: "GET"
        })
            .then(res => res.json())
            .then(data => {
                if (data.code == 200) {
                    is_loaded = true;
                    if (data.results > 0) {
                        notification_counter += data.results;
                        $("#btn_notification").removeClass("btn-secondary");
                        $("#btn_notification").addClass("btn-danger");
                        $("#btn_notification").addClass("blink");
                        $("#btn_notification").find("span").text(notification_counter);
                    }
                }
            }).catch(err => {
            console.log(err);
        });
    }

    function logout() {
        window.location.href = '{{ route('logout') }}';
    }
</script>
