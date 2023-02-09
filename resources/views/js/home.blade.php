<script src="js/moment.min.js"></script>
<script src="js/constant.js"></script>
<script src="js/websocket.js?v={{ date('ymdhis') }}"></script>
<script>
    const prescription_modal = new bootstrap.Modal(document.getElementById('prescription_modal'));
    const definitive_modal = new bootstrap.Modal(document.getElementById('definitive_modal'));
    const patient_modal = new bootstrap.Modal(document.getElementById('patient_modal'));
    const medicine_modal = new bootstrap.Modal(document.getElementById('medicine_modal'));

    const success_modal = new bootstrap.Modal(document.getElementById('success_modal'));
    const error_modal = new bootstrap.Modal(document.getElementById('error_modal'));
    let definitive_details = {};
    $(document).ready(function () {

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

        $("#btn_notification").click(function () {
            if (notification_counter > 0) {
                window.location.href = '{{ route('returned_prescription') }}';
            }
        });

        $("#form_prescription_type").change(function () {
            if ($(this).val() == "2") {
                $(".ams_container").removeClass("d-none")
            } else {
                $(".ams_container").addClass("d-none")
            }
        });

        $("input[name='indication_use']").change(function () {
            $("#btn_show_definitive").prop("disabled", true);
            if ($(this).next().text() == "Definitive" || $(this).next().text() == "Empiric then definitive") {
                $("#btn_show_definitive").prop("disabled", false);
                definitive_modal.toggle();
            }
        });

        $("#btn_show_definitive").click(function () {
            if ($("input[name='indication_use']:checked").next().text() == "Definitive" || $("input[name='indication_use']:checked").next().text() == "Empiric then definitive")
                definitive_modal.toggle();
        });

        $("#btn_search_prescription").click(function () {
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

        $("#btn_view_cart").click(function () {
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

                    console.log(data.result)
                    $("#hospital_id").val(data.result.hpatcode)
                    $("#fname").val(data.result.patfirst)
                    $("#mname").val(data.result.patmiddle)
                    $("#lname").val(data.result.patlast)
                    $("#suffix").val(data.result.patsuffix)
                    $("#birthdate").val(moment(data.result.patbdate).format("YYYY-MM-DD"));
                    $("#sex").val(data.result.patsex);
                }).catch(err => {
                console.log(err);
            });
        });

        $("#btn_save_patient").click(function () {
            //@TODO: CHECK IF EMPTY
            const hospital_id = $("#hospital_id").val().trim();
            const fname = $("#fname").val().trim().toUpperCase();
            const mname = $("#mname").val().trim().toUpperCase();
            const lname = $("#lname").val().trim().toUpperCase();
            const suffix = $("#suffix").val().trim().toUpperCase();
            const birthdate = $("#birthdate").val();
            const sex = $("#sex").val();
            const params = {
                hospital_id: hospital_id,
                fname: fname,
                mname: mname,
                lname: lname,
                suffix: suffix,
                birthdate: birthdate,
                sex: sex
            }

            insertPatient(params);
        });

        $("#btn_save_definitive").click(function () {
            const definitive_date = $("#definitive_date").val();
            const definitive_specimen = $("#definitive_specimen").val().trim();

            const organisms = [];
            for (let i = 0; i < $("#indication_use_details tr").length; i++) {
                const tr = $("#indication_use_details tr:eq(" + i + ")");
                const checkbox = tr.find("td:eq(0) input");

                if (checkbox.is(":checked")) {
                    let details = "";
                    const organism = tr.find("td:eq(1)").text().trim();
                    const pattern = tr.find("td:eq(2) input[type='radio']:checked").next().text();
                    if (i == 1 || i == 15) {
                        details = tr.find("td:eq(1) input").val().trim();
                    }

                    organisms.push({row_id: i, organism: organism, details: details, pattern: pattern});
                }
            }

            let is_valid = true;
            $("#definitive_modal .is-invalid").removeClass("is-invalid");
            $("#definitive_modal .error_container").addClass("d-none");
            $("#definitive_modal .error_list").empty();

            if (definitive_date == "") {
                is_valid = false;
                $("#definitive_modal .error_list").append("<li>Date is required</li>");
            }

            if (definitive_specimen == "") {
                is_valid = false;
                $("#definitive_modal .error_list").append("<li>Specimen is required</li>");
            }

            if (organisms.length < 1) {
                is_valid = false;
                $("#definitive_modal .error_list").append("<li>Specify at least 1 organism/pattern</li>");
            }


            if (!is_valid) {
                $("#definitive_modal .error_container").removeClass("d-none");
                return;
            }

            definitive_details = {
                definitive_date: definitive_date,
                definitive_specimen: definitive_specimen,
                organisms: organisms
            }

            definitive_modal.toggle();
        });

        $("#btn_submit").click(function () {
            const hospital_id = $("#form_hospital_id").text().trim();
            const name = $("#form_patient").text().trim();
            const birthdate = moment($("#form_birthdate").text().trim(), "MM/DD/YYYY").format("YYYY-MM-DD");
            const sex = $("#form_sex").text().trim();
            const station = $("#form_station").val();
            const ward = $("#form_ward").val();
            const bed_no = $("#form_bed_no").val();
            const remarks = $("#form_remarks").val();
            const urgency = ($("#checkbox_urgent").is(":checked")) ? "Urgent" : "Normal";
            const type = $("#form_prescription_type").val();
            const drug_allergy = $("#form_drug_allergy").val();
            const weight = $("#form_weight").val();
            const height = $("#form_height").val();
            const serum_creatinine = $("#form_serum_creatinine").val();
            const creatinine_clearance = $("#form_creatinine_clearance").val();
            const sgpt = $("#form_sgpt").val().trim();
            const sgot = $("#form_sgot").val().trim();

            let is_valid = true;
            $("#prescription_modal .is-invalid").removeClass("is-invalid");
            $("#prescription_modal .error_container").addClass("d-none");
            $("#prescription_modal .error_list").empty();

            let params = {};

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

            if (type == "") {
                is_valid = false;
                $("#prescription_modal .error_list").append("<li>Prescription type is required</li>");
            }

            //CHECK IF PRESCRIPTION TYPE IS AMS
            if (type == 2) {

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

                if (sgpt == "") {
                    is_valid = false;
                    $("#prescription_modal .error_list").append("<li>SGPT is required, put '0' if not applicable</li>");
                }

                if (sgot == "") {
                    is_valid = false;
                    $("#prescription_modal .error_list").append("<li>SGOT is required, put '0' if not applicable</li>");
                }

                const indication_use = $("input[name='indication_use']:checked").next().text().trim();

                if (indication_use == "") {
                    is_valid = false;
                    $("#prescription_modal .error_list").append("<li>Indication for use is required</li>");
                }

                if ((indication_use == "Definitive" || indication_use == "Empiric then definitive") && jQuery.isEmptyObject(definitive_details)) {
                    is_valid = false;
                    $("#prescription_modal .error_list").append("<li>Definitive organisms/patterns is required</li>");
                }


                const focus_infections = [];
                for (let i = 0; i < $("input[name='focus_infection']").length; i++) {
                    if ($("input[name='focus_infection']:eq(" + i + ")").is(":checked")) {
                        focus_infections.push({description: FOCUS_INFECTION_ARRAY[i]});
                    }
                }

                if (focus_infections.length < 1) {
                    is_valid = false;
                    $("#prescription_modal .error_list").append("<li>Focus Infection is required</li>");
                }

                const culture_sents = [];
                for (let i = 0; i < $("input[name='culture_sent']").length; i++) {
                    if ($("input[name='culture_sent']:eq(" + i + ")").is(":checked")) {
                        const culture_sent = CULTURE_SENT_ARRAY[i];
                        let details = "";
                        if (culture_sent == "Others") {
                            details = $("#culture_sent").val().trim();
                        }

                        if (culture_sent == "Others" && details == "") {
                            is_valid = false;
                            $("#prescription_modal .error_list").append("<li>Culture sent (Others) please specify</li>");
                            continue;
                        }

                        culture_sents.push({description: culture_sent, details: details});
                    }
                }

                if (culture_sents.length < 1) {
                    is_valid = false;
                    $("#prescription_modal .error_list").append("<li>Culture sent is required</li>");
                }

                const immunocompromising_conditions = [];
                for (let i = 0; i < $("input[name='immunocompromising_condition']").length; i++) {
                    if ($("input[name='immunocompromising_condition']:eq(" + i + ")").is(":checked")) {
                        const immunocompromising_condition = IMMUNO_ARRAY[i];
                        let details = "";
                        if (immunocompromising_condition == "Others") {
                            details = $("#immunocompromising_condition").val().trim();
                        }

                        if (immunocompromising_condition == "Others" && details == "") {
                            is_valid = false;
                            $("#prescription_modal .error_list").append("<li>Immunocompromising condition (Others) please specify</li>");
                            continue;
                        }

                        immunocompromising_conditions.push({
                            description: immunocompromising_condition,
                            details: details
                        });
                    }
                }

                if (immunocompromising_conditions.length < 1) {
                    is_valid = false;
                    $("#prescription_modal .error_list").append("<li>Immunocompromising condition is required</li>");
                }

                let infectious_details = null;
                let infectious_description = $("input[name='infectious_diagnosis']:checked").next().text();
                if (infectious_description == "Others") {
                    infectious_details = $("#infectious_diagnosis").val().trim();
                }

                if (infectious_description == "") {
                    is_valid = false;
                    $("#prescription_modal .error_list").append("<li>Infectious diagnosis is required</li>");
                }

                if (infectious_description == "Others" && infectious_details == "") {
                    is_valid = false;
                    $("#prescription_modal .error_list").append("<li>Infectious diagnosis (Others) please specify</li>");
                }

                const infectious_diagnosis = {
                    description: infectious_description,
                    details: infectious_details
                }

                const sepsis_criterias = [];
                for (let i = 0; i < $("input[name='sepsis_criteria']").length; i++) {
                    if ($("input[name='sepsis_criteria']:eq(" + i + ")").is(":checked")) {
                        sepsis_criterias.push({description: SEPSIS_CRITERIA_ARRAY[i]});
                    }
                }

                if (sepsis_criterias.length < 1) {
                    is_valid = false;
                    $("#prescription_modal .error_list").append("<li>Sepsis criteria is required</li>");
                }

                const dysfunction_criterias = [];
                for (let i = 0; i < $("input[name='dysfunction_criteria']").length; i++) {
                    if ($("input[name='dysfunction_criteria']:eq(" + i + ")").is(":checked")) {
                        dysfunction_criterias.push({description: DYSFUNCTION_CRITERIA_ARRAY[i]});
                    }
                }

                if (dysfunction_criterias.length < 1) {
                    is_valid = false;
                    $("#prescription_modal .error_list").append("<li>Dysfunction criteria is required</li>");
                }

                params.indication_use = {
                    description: indication_use,
                    definitive_date: (definitive_details.definitive_date === undefined) ? null : definitive_details.definitive_date,
                    specimen: (definitive_details.definitive_specimen === undefined) ? null : definitive_details.definitive_specimen,
                    organisms: (definitive_details.organisms === undefined) ? [] : definitive_details.organisms
                };

                params.focus_infections = focus_infections;
                params.culture_sents = culture_sents;
                params.immunocompromising_conditions = immunocompromising_conditions;
                params.infectious_diagnosis = infectious_diagnosis;
                params.sepsis_criterias = sepsis_criterias;
                params.dysfunction_criterias = dysfunction_criterias;
            }

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

                if (quantity < 1 || quantity == "") {
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

            const patient = {
                hospital_id: hospital_id,
                name: name,
                birthdate: birthdate,
                sex: sex,
                station: station,
                ward: ward,
                bed_no: bed_no,
                remarks: remarks,
                urgency: urgency,
                type: type,
                drug_allergy: drug_allergy,
                weight: weight,
                height: height,
                serum_creatinine: serum_creatinine,
                creatinine_clearance: creatinine_clearance,
                sgpt: sgpt,
                sgot: sgot
            }

            params.patient = patient;
            params.medications = medications;

            insertPrecription(params);
        });

        $("#search_medicine").keypress(function (e) {
            if (e.which == 13) {
                getMedicine($(this).val().trim());
            }
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
        });
    }

    function makePrescription(id) {
        const hospital_id = $("#patient_id_" + id + " td:eq(0)").text();
        const name = $("#patient_id_" + id + " td:eq(1)").text();
        const birthdate = $("#patient_id_" + id + " td:eq(2)").text();
        const sex = $("#patient_id_" + id + " td:eq(3)").text();

        $("#form_hospital_id").text(hospital_id);
        $("#form_patient").text(name);
        $("#form_birthdate").text(birthdate);
        $("#form_age").text(moment().diff(moment(birthdate, "MM/DD/YYYY"), 'years'));
        $("#form_sex").text(sex);
        $("#form_station").val("");
        $("#form_ward").val("");
        $("#form_bed_no").val("");
        $("#form_remarks").val("");
        $("#checkbox_urgent").prop("checked", false);
        $("#checkbox_urgent").prop("disabled", false);
        $("#medication_list").empty();

        $("#prescription_modal input").prop("disabled", false);
        $("#prescription_modal input[type='checkbox']").prop("disabled", false);
        $("#prescription_modal input[type='radio']").prop("disabled", false);
        $("#prescription_modal select").prop("disabled", false);

        $("#btn_view_cart").removeClass("d-none");
        $("#btn_submit").removeClass("d-none");
        $("#form_remarks").removeClass("d-none");
        $("#transaction_container").addClass("d-none");

        patient_modal.toggle();
        prescription_modal.toggle();


    }

    function viewPrescription(id) {
        $("#btn_view_cart").addClass("d-none");
        $("#btn_submit").addClass("d-none");
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
        const sgpt = $("#prescription_id_" + id + " td:eq(15)").text();
        const sgot = $("#prescription_id_" + id + " td:eq(16)").text();
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
        $("#form_sgpt").val(sgpt);
        $("#form_sgot").val(sgot);



        if (type == "2") {
            $(".ams_container").removeClass("d-none");
        }


        $("#prescription_modal input").prop("disabled", true);
        $("#prescription_modal input[type='checkbox']").prop("disabled", true);
        $("#prescription_modal input[type='radio']").prop("disabled", true);
        $("#prescription_modal select").prop("disabled", true);


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
                    //POPULATE AMS

                    if (!jQuery.isEmptyObject(data.ams)) {
                        const ams = data.ams;

                        const indication_index = INDICATION_USE_ARRAY.indexOf(ams.indication_use.description);
                        $("input[name='indication_use']:eq(" + indication_index + ")").prop("checked", true);
                        $("#btn_show_definitive").prop("disabled", true);

                        if (ams.indication_use.description == "Definitive" || ams.indication_use.description == "Empiric then definitive") {
                            $("#btn_show_definitive").prop("disabled", false);

                            $("#definitive_date").val(ams.indication_use.definitive_date);
                            $("#definitive_specimen").val(ams.indication_use.specimen);
                            ams.indication_details.forEach(it => {
                                const tr = $("#indication_use_details tr:eq(" + it.row_id + ")");
                                tr.find("td:eq(0) input").prop("checked", true);
                                tr.find("td:eq(1) input").val(it.details);

                                for (let i = 0; i < tr.find("input[type='radio']").length; i++) {
                                    const radio = tr.find("input[type='radio']:eq(" + i + ")");
                                    if (radio.next().text().trim() == it.pattern.trim()) {
                                        radio.prop("checked", true);
                                    }
                                }
                            });
                        }


                        const focus_infection = ams.focus_infection;
                        for (let i = 0; i < focus_infection.length; i++) {
                            const focus_infection_index = FOCUS_INFECTION_ARRAY.indexOf(focus_infection[i].description);
                            $("input[name='focus_infection']:eq(" + focus_infection_index + ")").prop("checked", true);
                        }

                        const culture_sent = ams.culture_sent;
                        for (let i = 0; i < culture_sent.length; i++) {
                            const culture_sent_index = CULTURE_SENT_ARRAY.indexOf(culture_sent[i].description);
                            $("input[name='culture_sent']:eq(" + culture_sent_index + ")").prop("checked", true);
                            if (culture_sent[i].description == "Others") {
                                $("#culture_sent").val(culture_sent[i].details);
                            }
                        }

                        const immuno = ams.immuno;
                        for (let i = 0; i < immuno.length; i++) {
                            const immuno_index = IMMUNO_ARRAY.indexOf(immuno[i].description);
                            $("input[name='immunocompromising_condition']:eq(" + immuno_index + ")").prop("checked", true);
                            if (immuno[i].description == "Others") {
                                $("#immunocompromising_condition").val(immuno[i].details);
                            }
                        }

                        const infectious_index = INFECTIOUS_DIAGNOSIS_ARRAY.indexOf(ams.infectious.description);
                        $("input[name='infectious_diagnosis']:eq(" + infectious_index + ")").prop("checked", true);
                        if (ams.infectious.description == "Others") {
                            $("#infectious_diagnosis").val(ams.infectious.details);
                        }

                        const sepsis_criteria = ams.sepsis_criteria;
                        for (let i = 0; i < sepsis_criteria.length; i++) {
                            const sepsis_criteria_index = SEPSIS_CRITERIA_ARRAY.indexOf(sepsis_criteria[i].description);
                            $("input[name='sepsis_criteria']:eq(" + sepsis_criteria_index + ")").prop("checked", true);
                        }

                        const dysfunction_criteria = ams.dysfunction_criteria;
                        for (let i = 0; i < dysfunction_criteria.length; i++) {
                            const dysfunction_criteria_index = DYSFUNCTION_CRITERIA_ARRAY.indexOf(dysfunction_criteria[i].description);
                            $("input[name='dysfunction_criteria']:eq(" + dysfunction_criteria_index + ")").prop("checked", true);
                        }

                    }

                    $("#medication_list").empty();
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
        });
    }

    function getMedicine(key) {
        fetch("{{ url('medicine/get') }}?medicine=" + key, {
            method: "GET"
        })
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
                alert(data.message);
            }).catch(err => {
            console.log(err);
        });
    }

    function insertPrecription(params) {
        prescription_modal.toggle();
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
                    $("#error_message").text(data.message);
                    error_modal.toggle();
                    return;
                }

                $("#success_message").text(data.message);
                success_modal.toggle();
                getPrescriptions();

                //SEND NOTIFICATION TO NURSE
                if (params.patient.type == "2") {
                    sendWebsocketServer({
                        action: "NOTIFY ROLE",
                        body: {role: "ICC"}
                    })
                } else {
                    sendWebsocketServer({
                        action: "NOTIFY STATION",
                        body: {station: params.patient.station}
                    })
                }
            }).catch(err => {
            $("#spinner").addClass("d-none");
            $("#error_message").text(err);
            error_modal.toggle();
        });
    }

    function getPrescriptions() {
        const filter_patient = $("#filter_patient").val().trim();
        const filter_date = $("#filter_date").val().trim();
        const page = 0;
        fetch("{{ url('prescription/get') }}?filter_patient=" + filter_patient +
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
                                <td class="d-none">` + it.type + `</td>
                                <td class="d-none">` + it.drug_allergy + `</td>
                                <td class="d-none">` + it.weight + `</td>
                                <td class="d-none">` + it.height + `</td>
                                <td class="d-none">` + it.serum_creatinine + `</td>
                                <td class="d-none">` + it.creatinine_clearance + `</td>
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
            $("#prescription_list").empty();
            $("#prescription_list").append("<tr><td colspan='6'>Something went wrong, please refresh browser</td></tr>");
            console.log(err);
        });
    }

    function editMedicine(id) {
        prescribed_med_id = id;
        medicine_modal.toggle();
        $("#search_medicine").val($("#medication_id_" + id + " td:eq(0)").text().trim());
        getMedicine($("#search_medicine").val());
    }

    function deleteMedicine(id) {
        $("#medication_id_" + id).remove();
    }

    function appendMedicine(id) {
        //CHECK IF MEDICINE ALREADY IN PRESCRIPTIONS
        const description = $("#" + id + " td:eq(0)").text();
        if (checkIfMedExists(description)) {
            alert(description + " already exists in prescription");
            return;
        }
        const unit = $("#" + id + " td:eq(1)").text();
        const count = $("#medication_list tr").length;
        let tr = "<tr id='medication_id_" + count + "'>";
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
        tr += "<td><input class='form-control text-center' type='number' /></td>";
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
        $(".ams_container").addClass("d-none");
        $("#prescription_modal input").val("");
        $("#prescription_modal input[type='checkbox']").prop("checked", false);
        $("#prescription_modal input[type='radio']").prop("checked", false);
        $("#prescription_modal select").val("");
        $("#medication_list").empty();
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
