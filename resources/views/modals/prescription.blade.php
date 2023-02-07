<div class="modal fade" id="prescription_modal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <div class="card-title p-1" style="font-size: 24px">Make Rx</div>
                <button type="button" class="btn-close fs-3" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="background: rgb(241,244,252)">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">Patient Information</h6>
                        <div class="text-white bg-danger rounded p-2 error_container d-none">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <div>There are some errors, please correct them below:</div>
                            </div>
                            <ul class="error_list"></ul>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <div class="text-center">
                                    <div class="fw-bold text-dark bg-white form-box" id="form_patient">N/A</div>
                                    <small>(Patient)</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <div class="fw-bold text-dark bg-white form-box" id="form_hospital_id">N/A</div>
                                    <small>(Hospital ID)</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <div class="fw-bold text-dark bg-white form-box" id="form_birthdate">N/A</div>
                                    <small>(Birthdate)</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <div class="fw-bold text-dark bg-white form-box" id="form_age">N/A</div>
                                    <small>(Age)</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <div class="fw-bold text-dark bg-white form-box" id="form_sex">N/A</div>
                                    <small>(Sex)</small>
                                </div>
                            </div>
                            <div class="col-md-4 mt-2">
                                <div class="text-center">
                                    <select class="form-control fw-bold text-center bg-white" id="form_station">
                                        <option value="">Select station</option>
                                    </select>
                                    <small>(Station)</small>
                                </div>
                            </div>
                            <div class="col-md-4 mt-2">
                                <div class="text-center">
                                    <input class="form-control bg-white fw-bold text-center" id="form_ward"/>
                                    <small>(Ward)</small>
                                </div>
                            </div>
                            <div class="col-md-4 mt-2">
                                <div class="text-center">
                                    <input class="form-control bg-white fw-bold text-center" id="form_bed_no"/>
                                    <small>(Bed No.)</small>
                                </div>
                            </div>
                            <div class="col-md-4 mt-2">
                                <div class="text-center">
                                    <select class="form-control fw-bold text-center bg-white"
                                            id="form_prescription_type">
                                        <option value="">Select type</option>
                                        <option value="1">Regular</option>
                                        <option value="2">Anti-Microbial Stewardship Program</option>
                                    </select>
                                    <small>(Prescription Type)</small>
                                </div>
                            </div>
                        </div>
                        <div class="row ams_container">
                            <div class="col-md-4 mt-2">
                                <div class="text-center">
                                    <input class="form-control bg-white fw-bold text-center" id="form_drug_allergy"/>
                                    <small>(Drug Allergy)</small>
                                </div>
                            </div>
                            <div class="col-md-4 mt-2">
                                <div class="text-center">
                                    <input type="number" class="form-control bg-white fw-bold text-center"
                                           id="form_weight"/>
                                    <small>(Weight (kg))</small>
                                </div>
                            </div>
                            <div class="col-md-4 mt-2">
                                <div class="text-center">
                                    <input type="number" class="form-control bg-white fw-bold text-center"
                                           id="form_height"/>
                                    <small>(Height (cm))</small>
                                </div>
                            </div>
                            <div class="col-md-4 mt-2">
                                <div class="text-center">
                                    <input class="form-control bg-white fw-bold text-center"
                                           id="form_serum_creatinine"/>
                                    <small>(Serum Creatinine (mg/dL)</small>
                                </div>
                            </div>
                            <div class="col-md-4 mt-2">
                                <div class="text-center">
                                    <input class="form-control bg-white fw-bold text-center"
                                           id="form_creatinine_clearance"/>
                                    <small>(Creatinine clearance (mL/min))</small>
                                </div>
                            </div>
                            <div class="col-md-4 mt-2">
                                <div class="text-center">
                                    <input class="form-control bg-white fw-bold text-center"
                                           id="form_sgpt"/>
                                    <small>(SGPT (IU/L))</small>
                                </div>
                            </div>
                            <div class="col-md-4 mt-2">
                                <div class="text-center">
                                    <input class="form-control bg-white fw-bold text-center"
                                           id="form_sgot"/>
                                    <small>(SGOT (IU/L))</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card ams_container">
                    <div class="card-body">
                        <h6 class="card-title">Anti-microbial Stewardship (AMS) Program</h6>
                        <div class="row">
                            <div class="col-md-6 mt-2">
                                <div class="card-title text-dark p-0">Indication for use</div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio"
                                                   name="indication_use">
                                            <label class="form-check-label">Prophylactic</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio"
                                                   name="indication_use">
                                            <label class="form-check-label">Empiric</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio"
                                                   name="indication_use">
                                            <label class="form-check-label">Definitive</label>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio"
                                                   name="indication_use">
                                            <label class="form-check-label">Empiric escalation</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio"
                                                   name="indication_use">
                                            <label class="form-check-label">Empiric de-escalation</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio"
                                                   name="indication_use">
                                            <label class="form-check-label">Empiric then definitive</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mt-2">
                                <div class="card-title text-dark p-0">Focus Infection</div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="focus_infection">
                                            <label class="form-check-label">HENT</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="focus_infection">
                                            <label class="form-check-label">OCULAR</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="focus_infection">
                                            <label class="form-check-label">Skin/Skin structure</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="focus_infection">
                                            <label class="form-check-label">Bones/Joints</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="focus_infection">
                                            <label class="form-check-label">Respiratory</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="focus_infection">
                                            <label class="form-check-label">Cardiovascular</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="focus_infection">
                                            <label class="form-check-label">GI/Intraabdominal</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="focus_infection">
                                            <label class="form-check-label">Genito-urinary</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="focus_infection">
                                            <label class="form-check-label">CNS</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr/>
                        <div class="row">
                            <div class="col-md-6 mt-2">
                                <div class="card-title text-dark p-0">Culture Sent</div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="culture_sent">
                                            <label class="form-check-label">Blood</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="culture_sent">
                                            <label class="form-check-label">Respiratory</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="culture_sent">
                                            <label class="form-check-label">Urine</label>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="culture_sent">
                                            <label class="form-check-label">CSF</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="culture_sent">
                                            <label class="form-check-label">Wound</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="culture_sent">
                                            <label class="form-check-label">Others</label>
                                            <input class="underline" id="culture_sent"/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mt-2">
                                <div class="card-title text-dark p-0">Immunocompromising condition</div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="immunocompromising_condition">
                                            <label class="form-check-label">None</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="immunocompromising_condition">
                                            <label class="form-check-label">Chronic pulmonary</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="immunocompromising_condition">
                                            <label class="form-check-label">Chronic liver disease</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="immunocompromising_condition">
                                            <label class="form-check-label">Chronic kidney disease</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="immunocompromising_condition">
                                            <label class="form-check-label">Diabetes mellitus, poorly controlled</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="immunocompromising_condition">
                                            <label class="form-check-label">Malignancy</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="immunocompromising_condition">
                                            <label class="form-check-label">Immunocompromised state</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="immunocompromising_condition">
                                            <label class="form-check-label">Connective tissue disease</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="immunocompromising_condition">
                                            <label class="form-check-label">Chronic steroid use</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="immunocompromising_condition">
                                            <label class="form-check-label">Others</label>
                                            <input class="underline" id="immunocompromising_condition"/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr/>
                        <div class="row">
                            <div class="col-md-12 mt-2">
                                <div class="card-title text-dark p-0">Infectious Diagnosis</div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio"
                                                   name="infectious_diagnosis">
                                            <label class="form-check-label">Deep-seated infections (e.g. abscess,
                                                empyema)</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio"
                                                   name="infectious_diagnosis">
                                            <label class="form-check-label">Staphylococcus aureus bacteremia</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio"
                                                   name="infectious_diagnosis">
                                            <label class="form-check-label">Meningitis or encephalitis</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio"
                                                   name="infectious_diagnosis">
                                            <label class="form-check-label">Septic arthritis</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio"
                                                   name="infectious_diagnosis">
                                            <label class="form-check-label">Osteomyelitis</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio"
                                                   name="infectious_diagnosis">
                                            <label class="form-check-label">Endocarditis</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio"
                                                   name="infectious_diagnosis">
                                            <label class="form-check-label">Necrotizing soft tissue infections</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio"
                                                   name="infectious_diagnosis">
                                            <label class="form-check-label">Infected implants or prosthesis</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio"
                                                   name="infectious_diagnosis">
                                            <label class="form-check-label">Febrile neutropenia</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio"
                                                   name="infectious_diagnosis">
                                            <label class="form-check-label">Others</label>
                                            <input class="underline" id="infectious_diagnosis"/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr/>
                        <div class="row">
                            <div class="col-md-6 mt-2">
                                <div class="card-title text-dark p-0">Sepsis Critieria</div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="sepsis_criteria">
                                            <label class="form-check-label">HR >90/min</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="sepsis_criteria">
                                            <label class="form-check-label">RR >20min or pCO2 &lt;32 MMG</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="sepsis_criteria">
                                            <label class="form-check-label">Temperature &#8805;38&#176;C or &#8804;36&#176;C</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="sepsis_criteria">
                                            <label class="form-check-label">WBC >12,000 or &lt;4,000 or >10% immature
                                                bands</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="sepsis_criteria">
                                            <label class="form-check-label">N/A</label>
                                        </div>
                                    </div>

                                </div>
                            </div>
                            <div class="col-md-6 mt-2">
                                <div class="card-title text-dark p-0">Organ dysfunction criteria</div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="dysfunction_criteria">
                                            <label class="form-check-label">SBP &lt;90 or droppped >40 mmHg from
                                                baseline</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="dysfunction_criteria">
                                            <label class="form-check-label">New onset jaundice</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="dysfunction_criteria">
                                            <label class="form-check-label">
                                                Platelet &lt;100,000/uL or INR >1.5 not on anticoagulation
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="dysfunction_criteria">
                                            <label class="form-check-label">
                                                Upper or Lower GI bleeding
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="dysfunction_criteria">
                                            <label class="form-check-label">
                                                Respiratory failure requiring ventilation; hypoxemiaL saO2 &lt;90% air
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="dysfunction_criteria">
                                            <label class="form-check-label">
                                                Urine Output &lt;0.5 mL/kg/hr for > 6hr or azotemia (creatinine 2x
                                                baseline or >1.3 mg/dL)</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="dysfunction_criteria">
                                            <label class="form-check-label">N/A</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="card-title fs-2">Rx</h6>
                            <div>
                                <button class="btn btn-sm btn-success" id="btn_view_cart">
                                    <i class="bi bi-cart-plus-fill me-2"></i>VIEW CART
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table text-center v-center p-1 m-0">
                                <thead>
                                <tr>
                                    <th>Medicine</th>
                                    <th style="width: 20%">Strength</th>
                                    <th style="width: 10%">Unit</th>
                                    <th style="width: 10%">Quantity</th>
                                    <th style="width: 15%">Frequency</th>
                                    <th colspan="2" style="width: 8%" id="quantity_render_th"></th>
                                </tr>
                                </thead>
                                <tbody id="medication_list" style="font-size: 12px">
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        <tr>
                            <td colspan="7">
                                <div class="d-flex justify-content-center">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="checkbox_urgent">
                                        <label class="form-check-label fw-bold text-danger">
                                            Set as urgent!
                                        </label>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </div>
                </div>

                <div class="card" id="transaction_container">
                    <div class="card-body">
                        <div class="card-title">Activity Logs</div>
                        <div class="table-responsive">
                            <table class="table table-hover text-center">
                                <thead>
                                <tr>
                                    <th>Origin</th>
                                    <th>Remarks</th>
                                    <th>Forwarded to</th>
                                    <th>Date forwarded</th>
                                </tr>
                                </thead>
                                <tbody id="transaction_list" style="font-size: 12px"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <textarea class="form-control" style="height: 150px" id="form_remarks"
                              placeholder="Remarks"></textarea>
                </div>

                <div class="text-end mt-5">
                    <button class="btn btn-danger" id="btn_reject">REJECT</button>
                    <button class="btn btn-primary" id="btn_submit">SUBMIT</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="definitive_modal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <div class="card-title p-1" style="font-size: 24px">Additional Information</div>
                <button type="button" class="btn-close fs-3" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="background: rgb(241,244,252)">
                <div class="card">
                    <div class="card-body">
                        <div class="text-white bg-danger rounded p-2 error_container d-none mt-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <div>There are some errors, please correct them below:</div>
                            </div>
                            <ul class="error_list"></ul>
                        </div>
                        <h6 class="card-title">Definitive Use</h6>
                        <div class="row">
                            <div class="col-md-3">
                                <div>
                                    <label>Date</label>
                                    <input type="date" class="form-control" id="definitive_date"/>
                                </div>
                            </div>
                            <div class="col-md-9">
                                <div>
                                    <label>Specimen</label>
                                    <input type="text" class="form-control" id="definitive_specimen"/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">Sample Organisms</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="text-center">
                                <tr>
                                    <th style="width: 2%"></th>
                                    <th style="width: 78%">Organism</th>
                                    <th style="width: 20%">Susceptibility Result or Specific Resistance Pattern</th>
                                </tr>
                                </thead>
                                <tbody id="indication_use_details">
                                <tr>
                                    <td><input type="checkbox" class="form-check-input"/></td>
                                    <td>Acinetobacterbaumannii</td>
                                    <td>
                                        <div class="d-flex justify-content-between">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="organism_1">
                                                <label class="form-check-label">MDR</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="organism_1">
                                                <label class="form-check-label">XDR</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="organism_1">
                                                <label class="form-check-label">PDR</label>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="form-check-input"/></td>
                                    <td>
                                        <div>Candida, specify species:</div>
                                        <input class="d-inline-block form-control underline"/>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-between">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="organism_2">
                                                <label class="form-check-label">Fluconazole-resistant</label>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="form-check-input"/></td>
                                    <td>Clostridium difficile (by toxin assay only)</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="form-check-input"/></td>
                                    <td>Enterococcus faecium/faecalis</td>
                                    <td>
                                        <div class="d-flex justify-content-between">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="organism_4">
                                                <label class="form-check-label">Vancomycin-resistant</label>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="form-check-input"/></td>
                                    <td>Escherichia coli</td>
                                    <td>
                                        <div class="d-flex justify-content-between">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="organism_5">
                                                <label class="form-check-label">ESBL</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="organism_5">
                                                <label class="form-check-label">CRE</label>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="form-check-input"/></td>
                                    <td>Enterobacteriaceae other than E. coli, Klebsiella</td>
                                    <td>
                                        <div class="d-flex justify-content-between">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="organism_6">
                                                <label class="form-check-label">ESBL</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="organism_6">
                                                <label class="form-check-label">CRE</label>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="form-check-input"/></td>
                                    <td>Haemophilus influenzae</td>
                                    <td>
                                        <div class="d-flex justify-content-between">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="organism_7">
                                                <label class="form-check-label">Ampicilin-resistant</label>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="form-check-input"/></td>
                                    <td>Klebsiella pneumoniae</td>
                                    <td>
                                        <div class="d-flex justify-content-between">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="organism_8">
                                                <label class="form-check-label">ESBL</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="organism_8">
                                                <label class="form-check-label">CRE</label>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="form-check-input"/></td>
                                    <td>Neisseria gonnorrhoeae</td>
                                    <td>
                                        <div class="d-flex justify-content-between">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="organism_9">
                                                <label class="form-check-label">Ceftriaxone-resistant</label>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="form-check-input"/></td>
                                    <td>Pseudomonas aeruginosa</td>
                                    <td>
                                        <div class="d-flex justify-content-between">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="organism_10">
                                                <label class="form-check-label">MDR</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="organism_10">
                                                <label class="form-check-label">XDR</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="organism_10">
                                                <label class="form-check-label">PDR</label>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="form-check-input"/></td>
                                    <td>Salmonella, non-typhoidal</td>
                                    <td>
                                        <div class="d-flex justify-content-between">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="organism_11">
                                                <label class="form-check-label">Ceftriaxone-resistant</label>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="form-check-input"/></td>
                                    <td>Salmonella enterica (typhi)</td>
                                    <td>
                                        <div class="d-flex justify-content-between">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="organism_12">
                                                <label class="form-check-label">Ceftriaxone-resistant</label>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="form-check-input"/></td>
                                    <td>Shigella spp.</td>
                                    <td>
                                        <div class="d-flex justify-content-between">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="organism_13">
                                                <label class="form-check-label">Ciprofloxacin-resistant</label>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="form-check-input"/></td>
                                    <td>Staphylococcus aureus</td>
                                    <td>
                                        <div class="d-flex justify-content-between">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="organism_14">
                                                <label class="form-check-label">Methicilin-resistant</label>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="form-check-input"/></td>
                                    <td>Streptococcus pneumoniae</td>
                                    <td>
                                        <div class="d-flex justify-content-between">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="organism_15">
                                                <label class="form-check-label">Penicilin-resistant</label>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td><input type="checkbox" class="form-check-input"/></td>
                                    <td>
                                        <div>Others, please specify:</div>
                                        <input class="form-control underline" id="infection_focus"/>
                                    </td>
                                    <td></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="text-end mt-2">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">CANCEL</button>
                    <button class="btn btn-primary" id="btn_save_definitive">SAVE</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="patient_modal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <div class="card-title p-1" style="font-size: 24px">Patient Management</div>
                <button type="button" class="btn-close fs-3" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="background: rgb(241,244,252)">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="mt-3 fs-5 fw-bold">Search patient</div>
                                <div class="form-floating mt-3">
                                    <input type="text" class="form-control" id="hospital_id" placeholder="">
                                    <label>Hospital ID</label>
                                </div>
                                <div class="form-floating mt-3">
                                    <input type="text" class="form-control bg-white" id="fname" placeholder="" readonly>
                                    <label>Firstname</label>
                                </div>
                                <div class="form-floating mt-3">
                                    <input type="text" class="form-control bg-white" id="mname" placeholder="" readonly>
                                    <label>Middlename</label>
                                </div>
                                <div class="form-floating mt-3">
                                    <input type="text" class="form-control bg-white" id="lname" placeholder="" readonly>
                                    <label>Lastname</label>
                                </div>
                                <div class="form-floating mt-3">
                                    <input type="text" class="form-control bg-white" id="suffix" placeholder="" readonly>
                                    <label>Suffix</label>
                                </div>
                                <div class="form-floating mt-3">
                                    <input type="date" class="form-control bg-white" id="birthdate" placeholder="" readonly>
                                    <label>Birthdate</label>
                                </div>
                                <div class="form-floating mt-3">
                                    <select class="form-control" id="sex">
                                        <option value="">Select sex</option>
                                        <option value="M">MALE</option>
                                        <option value="F">FEMALE</option>
                                    </select>
                                    <label>Sex</label>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <button class="btn btn-lg form-control btn-secondary" id="btn_find">SEARCH</button>
                                    </div>
                                    <div class="col-md-6">
                                        <button class="btn btn-lg form-control btn-success" id="btn_save_patient">ADD</button>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input class="form-control mb-3" placeholder="Search patient &#xF52A"
                                           id="search_firstname"/>
                                    <label>Search first</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input class="form-control mb-3" placeholder="Search patient &#xF52A"
                                           id="search_lastname"/>
                                    <label>Search lastname</label>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Patient List</h5>

                                <!-- Table with hoverable rows -->
                                <table class="table table-hover text-center">
                                    <thead>
                                    <tr>
                                        <th scope="col">Hospital ID</th>
                                        <th scope="col">Name</th>
                                        <th scope="col">Birthdate</th>
                                        <th scope="col">Sex</th>
                                        <th scope="col" style="width: 4%"></th>
                                    </tr>
                                    </thead>
                                    <tbody id="patient_list">
                                    <tr>
                                        <td colspan="5">No record found</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="medicine_modal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <div class="text-center text-dark mb-5" style="font-size: 30px">Medicine</div>
                <input class="form-control mb-3" placeholder="Search by description" id="search_medicine"/>
                <div class="card m-0 p-0">
                    <div class="card-body m-0 p-0">
                        <div class="table-responsive" style="max-height: 500px !important;">
                            <table class="table table-hover text-center">
                                <thead>
                                <tr>
                                    <th style="width: 70%">Description</th>
                                    <th style="width: 25%">Unit</th>
                                    <th style="width: 5%"></th>
                                </tr>
                                </thead>
                                <tbody id="medicine_list" style="font-size: 12px">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-5">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">CLOSE</button>
                </div>
            </div>
        </div>
    </div>
</div>
