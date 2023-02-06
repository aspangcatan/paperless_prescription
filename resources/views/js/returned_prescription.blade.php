<script src="js/moment.min.js"></script>
<script>
    const prescription_modal = new bootstrap.Modal(document.getElementById('prescription_modal'));
    const medicine_modal = new bootstrap.Modal(document.getElementById('medicine_modal'));

    const success_modal = new bootstrap.Modal(document.getElementById('success_modal'));
    const error_modal = new bootstrap.Modal(document.getElementById('error_modal'));
    let button_clicked = "";
    let prescription_id;
    let prescribed_med_id;
    const INDICATION_USE_ARRAY = ["Prophylactic", "Empiric", "Definitive", "Empiric escalation", "Empiric de-escalation", "Empiric then definitive"];
    const FOCUS_INFECTION_ARRAY = ["HENT", "OCULAR", "Skin/Skin structure", "Bones/Joints", "Respiratory", "Cardiovascular", "GI/Intraabdominal", "Genito-urinary", "CNS"];
    const CULTURE_SENT_ARRAY = ["Blood", "Respiratory", "Urine", "CSF", "Wound", "Others"];
    const IMMUNO_ARRAY = ["None", "Chronic pulmonary", "Chronic liver disease", "Chronic kidney disease", "Diabetes mellitus, poorly controlled",
        "Malignancy", "Immunocompromised state", "Connective tissue disease", "Chronic steroid use", "Others"];
    const INFECTIOUS_DIAGNOSIS_ARRAY = ["Deep-seated infections (e.g. abscess, empyema)", "Staphylococcus aureus bacteremia", "Meningitis or encephalitis",
        "Septic arthritis", "Osteomyelitis", "Endocarditis", "Necrotizing soft tissue infections", "Infected implants or prosthesis", "Febrile neutropenia", "Others"];
    const SEPSIS_CRITERIA_ARRAY = ["HR >90/min", "RR >20min or pCO2 <32 MMG", "Temperature ≥38°C or ≤36°C", "WBC >12,000 or <4,000 or >10% immature bands", "N/A"];
    const DYSFUNCTION_CRITERIA_ARRAY = ["SBP <90 or droppped >40 mmHg from baseline", "New onset jaundice", "Platelet <100,000/uL or INR >1.5 not on anticoagulation",
        "Upper or Lower GI bleeding", "Respiratory failure requiring ventilation; hypoxemiaL saO2 <90% air", "Urine Output <0.5 mL/kg/hr for > 6hr or azotemia (creatinine 2x baseline or >1.3 mg/dL)", "N/A"];

    let definitive_details = {};
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

                if(infectious_description == ""){
                    is_valid = false;
                    $("#prescription_modal .error_list").append("<li>Infectious diagnosis is required</li>");
                }

                if(infectious_description == "Others" && infectious_details == ""){
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
            params.id = prescription_id;

            console.log(params);

            updatePrescription(params);
        });

        getPrescriptions();
    });

    function viewPrescription(id) {
        prescription_id = id;
        $("#btn_submit").val(id);
        $("#btn_reject").val(id);
        $("#btn_submit").text("UPDATE");

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

        // $("#prescription_modal input").prop("disabled", true);
        // $("#prescription_modal input[type='checkbox']").prop("disabled", true);
        // $("#prescription_modal input[type='radio']").prop("disabled", true);
        // $("#prescription_modal select").prop("disabled", true);

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
                    if (!jQuery.isEmptyObject(data.ams)) {
                        const ams = data.ams;

                        const indication_index = INDICATION_USE_ARRAY.indexOf(ams.indication_use.description);
                        $("input[name='indication_use']:eq(" + indication_index + ")").prop("checked", true);

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

    function clearForms() {
        $(".ams_container").addClass("d-none");
        $("#prescription_modal input").val("");
        $("#prescription_modal input[type='checkbox']").prop("checked", false);
        $("#prescription_modal input[type='radio']").prop("checked", false);
        $("#prescription_modal select").val("");
        $("#medication_list").empty();
    }
</script>
