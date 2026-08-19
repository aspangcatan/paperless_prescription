<div class="modal fade" id="prescription_modal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <div class="card-title p-1" style="font-size: 24px">Make Rx</div>
                <button type="button" style="background: transparent !important;" class="btn" data-bs-dismiss="modal"
                        aria-label="Close">X
                </button>
            </div>
            <div class="modal-body" style="background: rgb(241,244,252)">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <h6 class="card-title">Patient Information</h6>
                            <div>
                                <button class="btn btn-sm btn-success d-none" id="btn_download">
                                    <i class="bi bi-download"></i>
                                </button>
                            </div>
                        </div>

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
                            <div class="col-md-4 mt-2 d-none">
                                <div class="text-center">
                                    <input class="form-control bg-white fw-bold text-center" id="form_ward"/>
                                    <small>(Ward)</small>
                                </div>
                            </div>
                            <div class="col-md-4 mt-2 d-none">
                                <div class="text-center">
                                    <input class="form-control bg-white fw-bold text-center" id="form_bed_no"/>
                                    <small>(Bed No.)</small>
                                </div>
                            </div>
                            <div class="col-md-4 mt-2">
                                <div class="text-center">
                                    <input class="form-control bg-white fw-bold text-center"
                                           id="form_attending_physician"/>
                                    <small>(Attending Physician)</small>
                                </div>
                            </div>
                            <div class="col-md-4 mt-2">
                                <div class="text-center">
                                    <select class="form-control fw-bold text-center bg-white"
                                            id="form_prescription_type">
                                        <option value="">Select type</option>
                                        <option hidden="hidden" value="1" disabled>Non-Restricted</option>
                                        <option value="2">Restricted</option>
                                        <option hidden="hidden" value="3" disabled>Seventh Day Auto Stop</option>
                                        <option hidden="hidden" value="4" disabled>Intravenous to Oral</option>
                                        <option value="5">Monitored</option>
                                        <option value="8">Non-Monitored</option>
                                        <option hidden="hidden" value="7">Switch Medication</option>
                                        <option value="6">Cancellation</option>
                                    </select>
                                    <small>(Type)</small>
                                </div>
                            </div>
                            <div class="col-md-4 mt-2">
                                <div class="text-center">
                                    <select class="form-control fw-bold text-center bg-white"
                                            id="form_for_extension">
                                        <option value="">Select option</option>
                                        <option value="1">Yes</option>
                                        <option value="0">No</option>
                                    </select>
                                    <small>(For Extension?)</small>
                                </div>
                            </div>

                            <div class="col-md-4 mt-2 ams_container">
                                <div class="text-center">
                                    <input class="form-control bg-white fw-bold text-center" id="form_drug_allergy"/>
                                    <small>(Drug Allergy)</small>
                                </div>
                            </div>
                            <div class="col-md-4 mt-2 ams_container">
                                <div class="text-center">
                                    <input type="number" class="form-control bg-white fw-bold text-center"
                                           id="form_weight"/>
                                    <small>(Weight (kg))</small>
                                </div>
                            </div>
                            <div class="col-md-4 mt-2 ams_container">
                                <div class="text-center">
                                    <input type="number" class="form-control bg-white fw-bold text-center"
                                           id="form_height"/>
                                    <small>(Height (cm))</small>
                                </div>
                            </div>
                            <div class="col-md-4 mt-2 ams_container">
                                <div class="text-center">
                                    <input class="form-control bg-white fw-bold text-center"
                                           id="form_serum_creatinine"/>
                                    <small>(Serum Creatinine</small>
                                </div>
                            </div>
                            <div class="col-md-4 mt-2 ams_container">
                                <div class="text-center">
                                    <input class="form-control bg-white fw-bold text-center"
                                           id="form_creatinine_clearance"/>
                                    <small>(Creatinine clearance)</small>
                                </div>
                            </div>
                            <div class="col-md-4 mt-2 ams_container d-none" id="transaminases_container">
                                <div class="text-center">
                                    <input class="form-control bg-white fw-bold text-center"
                                           id="form_transaminases"/>
                                    <small>(Transaminases)</small>
                                </div>
                            </div>
                            <div class="col-md-4 mt-2 ams_container" id="sgpt_container">
                                <div class="text-center">
                                    <input class="form-control bg-white fw-bold text-center"
                                           id="form_sgpt"/>
                                    <small>(SGPT)</small>
                                </div>
                            </div>
                            <div class="col-md-4 mt-2 ams_container" id="sgot_container">
                                <div class="text-center">
                                    <input class="form-control bg-white fw-bold text-center"
                                           id="form_sgot"/>
                                    <small>(SGOT)</small>
                                </div>
                            </div>
                            <div class="col-md-4 mt-2 ams_container" id="days_requested_container">
                                <div class="text-center">
                                    <input type="number" class="form-control bg-white fw-bold text-center"
                                           id="form_days_requested"/>
                                    <small>(Duration requested (days))</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="cancellation_container">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title">Patient Outcomes</h6>
                            <div class="form-check ps-0">
                                <input type="radio" name="outcome">
                                <label class="form-check-label">
                                    Discharged
                                </label>
                            </div>
                            <div class="form-check ps-0">
                                <input type="radio" name="outcome">
                                <label class="form-check-label">
                                    Hama
                                </label>
                            </div>
                            <div class="form-check ps-0">
                                <input type="radio" name="outcome">
                                <label class="form-check-label">
                                    Transferred
                                </label>
                            </div>
                            <div class="form-check ps-0">
                                <input type="radio" name="outcome">
                                <label class="form-check-label">
                                    Died
                                </label>
                            </div>
                            <div class="form-check ps-0">
                                <input type="radio" name="outcome">
                                <label class="form-check-label">
                                    Absconded
                                </label>
                            </div>
                            <div class="form-check ps-0">
                                <input type="radio" name="outcome">
                                <label class="form-check-label">Others</label>
                                <div class="d-flex">
                                    <div>
                                        <input class="form-control" id="outcome"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="ams_container">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="card-title">Concurrent Antimicrobial Therapy</h6>
                            </div>

                            <div class="table-responsive" style="max-height: 300px !important;">
                                <table class="table text-center" id="concurrent_table">
                                    <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Antimicrobial</th>
                                        <th>Dosing Regimen</th>
                                        <th>Start Date</th>
                                        <th>Stop Date</th>
                                        <th colspan="2" style="width: 8%"></th>
                                    </tr>
                                    </thead>
                                    <tbody id="concurrent_list" style="font-size: 12px"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="card" id="intervention_form_container">
                        <div class="card-body">
                            <h6 class="card-title">Your patient fulfills the following criteria to switch to oral (PO)
                                antimicrobial</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div>Clinical Stability:</div>
                                    <ul>
                                        <li>Afebrile</li>
                                        <li>Downward trend or normalization of
                                            Inflammatory markers
                                            <small class="d-block">
                                                (C-reactive protein, white blood cell count, procalcitonin)
                                            </small>
                                        </li>
                                        <li>
                                            Stable vital signs
                                            <small class="d-block">
                                                (No unexplained tachycardia, hypotension, tachypnea)
                                            </small>
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <div>Able to tolerate oral intake:</div>
                                    <ul class="mt-2">
                                        <li>Patient is not nil by mouth</li>
                                        <li>Tolerating oral diet, medications and/or enteral feeds</li>
                                        <li>Oral abosrption is not compromised
                                            <small class="d-block">
                                                (e.g. No vomiting or diarrhea, malabsorptive disorder)
                                            </small>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card" id="indication_use_container">
                        <div class="card-body">
                            <h6 class="card-title">Indication for use</h6>
                            <div class="form-check">
                                <input type="radio"
                                       name="indication_use">
                                <label class="form-check-label">Prophylactic</label>
                            </div>
                            <div class="form-check">
                                <input type="radio"
                                       name="indication_use">
                                <label class="form-check-label">Empiric</label>
                            </div>
                            <div class="form-check">
                                <input type="radio"
                                       name="indication_use">
                                <label class="form-check-label">Definitive</label>
                            </div>
                        </div>
                    </div>
                    <div class="card" id="microbiological_container">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="card-title">Microbiological Results</h6>
                                <div>
                                    <button class="btn btn-sm btn-success" id="btn_add_microbiological">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table text-center">
                                    <thead>
                                    <tr>
                                        <th style="width: 10%">Date</th>
                                        <th style="width: 32%">Specimen</th>
                                        <th style="width:50%">Pathogen identified & susceptibility results</th>
                                        <th colspan="2" style="width: 8%"></th>
                                    </tr>
                                    </thead>
                                    <tbody id="microbiological_result_list"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="card" id="additional_labs_container">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="card-title">Additional Laboratory Information</h6>
                            </div>
                            <textarea class="form-control" rows="10" id="additional_labs"
                                      placeholder="(e.g. UTZ, CT Scan)"></textarea>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="card-title">Diagnosis</h6>
                            </div>
                            <textarea class="form-control" rows="10" id="diagnosis" placeholder=""></textarea>
                        </div>
                    </div>
                </div>
                <div class="card" id="medication_container">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="card-title fs-4">Rx</h6>
                            <div>
                                <button class="btn btn-sm btn-success" id="btn_view_cart">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive d-none" id="approved_medication_container">
                            <div class="small text-muted mb-1">Already approved, with Pharmacy:</div>
                            <table class="table text-center v-center p-1 m-0">
                                <thead>
                                <tr>
                                    <th>Medicine</th>
                                    <th style="width: 20%">Strength</th>
                                    <th style="width: 10%">Unit</th>
                                    <th style="width: 15%">Frequency</th>
                                    <th style="width: 16%"></th>
                                </tr>
                                </thead>
                                <tbody id="approved_medication_list" style="font-size: 12px">
                                </tbody>
                            </table>
                        </div>
                        <div class="table-responsive">
                            <table class="table text-center v-center p-1 m-0">
                                <thead>
                                <tr id="table_header">
                                    <th>Medicine</th>
                                    <th style="width: 20%">Strength</th>
                                    <th style="width: 10%">Unit</th>
                                    <th style="width: 2%" class="d-none">Quantity<br/>Ordered</th>
                                    <th style="width: 15%">Frequency</th>
                                    <th colspan="2" style="width: 16%" id="quantity_render_th"></th>
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
                                        <input type="checkbox" id="checkbox_urgent">
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
                    <button class="btn btn-danger" id="btn_reject" style="min-width: 103.58px">REJECT</button>
                    <button class="btn btn-primary" id="btn_submit" style="min-width: 103.58px">SUBMIT</button>
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
                <button type="button" style="background: transparent !important;" class="btn" data-bs-dismiss="modal"
                        aria-label="Close">X
                </button>
            </div>
            <div class="modal-body" style="background: rgb(241,244,252)">
                <div class="row">
                    <div class="col-md-4 d-none">
                        <div class="card">
                            <div class="card-body">
                                <div class="mt-3 fs-5 fw-bold">Search patient in HOMIS</div>
                                <div class="form-floating mt-3">
                                    <input type="text" class="form-control" id="hospital_id" placeholder="">
                                    <label>Hospital ID</label>
                                </div>
                                <div class="form-floating mt-3">
                                    <input type="text" class="form-control bg-white" id="fname" placeholder="">
                                    <label>Firstname</label>
                                </div>
                                <div class="form-floating mt-3">
                                    <input type="text" class="form-control bg-white" id="mname" placeholder="">
                                    <label>Middlename</label>
                                </div>
                                <div class="form-floating mt-3">
                                    <input type="text" class="form-control bg-white" id="lname" placeholder="">
                                    <label>Lastname</label>
                                </div>
                                <div class="form-floating mt-3">
                                    <input type="text" class="form-control bg-white" id="suffix" placeholder=""
                                    >
                                    <label>Suffix</label>
                                </div>
                                <div class="form-floating mt-3">
                                    <input type="date" class="form-control bg-white" id="birthdate" placeholder=""
                                    >
                                    <label>Birthdate</label>
                                </div>
                                <div class="form-floating mt-3">
                                    <select class="form-control bg-white" id="sex">
                                        <option value="">Select sex</option>
                                        <option value="M">MALE</option>
                                        <option value="F">FEMALE</option>
                                    </select>
                                    <label>Sex</label>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <button class="btn form-control btn-secondary" id="btn_find">SEARCH
                                        </button>
                                    </div>
                                    <div class="col-md-12 mt-2">
                                        <button class="btn form-control btn-info text-white" id="btn_find_medix">SEARCH MEDIX
                                        </button>
                                    </div>
                                    <div class="col-md-12 mt-2">
                                        <button class="btn form-control btn-success" id="btn_save_patient">ADD / UPDATE
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-3 d-none" id="medix_results_container">
                                    <div class="small text-danger mb-1 d-none" id="medix_search_status"></div>
                                    <div class="table-responsive" style="max-height: 250px !important;">
                                        <table class="table table-sm table-hover text-center">
                                            <thead>
                                            <tr>
                                                <th scope="col">Hospital No</th>
                                                <th scope="col">Name</th>
                                                <th scope="col">Birthdate</th>
                                                <th scope="col">Sex</th>
                                                <th scope="col" style="width: 4%"></th>
                                            </tr>
                                            </thead>
                                            <tbody id="medix_patient_list"></tbody>
                                        </table>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input class="form-control mb-3" placeholder="Search patient &#xF52A"
                                           id="search_firstname"/>
                                    <label>Search firstname</label>
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
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <button class="btn form-control btn-success" id="btn_search_local">
                                    <i class="bi bi-search"></i> Search Locally
                                </button>
                            </div>
                            <div class="col-md-6">
                                <button class="btn form-control btn-info text-white" id="btn_search_medix">
                                    <i class="bi bi-search"></i> Search MEDIX
                                </button>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Patient List</h5>
                                <div class="table-responsive">
                                    <table class="table table-hover text-center">
                                        <thead>
                                        <tr>
                                            <th scope="col">Source</th>
                                            <th scope="col">Hospital ID</th>
                                            <th scope="col">Name</th>
                                            <th scope="col">Birthdate</th>
                                            <th scope="col">Sex</th>
                                            <th scope="col" style="width: 4%"></th>
                                        </tr>
                                        </thead>
                                        <tbody id="patient_list">
                                        <tr class="no_record_row">
                                            <td colspan="6">No record found</td>
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
</div>

<div class="modal fade" id="medicine_modal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-body">
                <div class="text-center text-dark mb-5" style="font-size: 30px">Antimicroibals</div>
                <input class="form-control mb-3" placeholder="Search by description" id="search_medicine"/>
                <div class="card m-0 p-0">
                    <div class="card-body m-0 p-0">
                        <div class="table-responsive" style="max-height: 500px !important;">
                            <table class="table table-hover text-center">
                                <thead>
                                <tr>
                                    <th style="width: 95%">Description</th>
                                    <th style="width: 5%"></th>
                                </tr>
                                </thead>
                                <tbody id="medicine_list">
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

<div class="modal fade" id="microbiological_modal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <h6 class="card-title">Add Microbiological Results</h6>
                <div class="text-white bg-danger rounded p-2 error_container d-none">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <div>There are some errors, please correct them below:</div>
                    </div>
                    <ul class="error_list"></ul>
                </div>
                <div class="row mb-3 mt-2">
                    <label for="inputText" class="col-sm-2 col-form-label">Date</label>
                    <div class="col-sm-10">
                        <input type="date" class="form-control" id="microbiological_date">
                    </div>
                </div>
                <div class="row mb-3">
                    <label for="inputText" class="col-sm-2 col-form-label">Specimen</label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control" id="microbiological_specimen">
                    </div>
                </div>
                <div class="row mb-3">
                    <label for="inputText" class="col-sm-12 col-form-label">Pathogen identified & susceptibility
                        results</label>
                    <div class="col-sm-12">
                        <textarea class="form-control" style="height: 100px" id="microbiological_pathogen"></textarea>
                    </div>
                </div>

                <div class="text-center mt-5">
                    <button class="btn btn-primary" id="btn_save_microbiological">SAVE</button>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">CLOSE</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="concurrent_modal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <h6 class="card-title">Add Concurrent Antimicrobial Therapy</h6>
                <div class="text-white bg-danger rounded p-2 error_container d-none">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <div>There are some errors, please correct them below:</div>
                    </div>
                    <ul class="error_list"></ul>
                </div>
                <div class="row mb-3 mt-2">
                    <label for="inputText" class="col-sm-3 col-form-label">Date</label>
                    <div class="col-sm-9">
                        <input type="date" class="form-control" id="concurrent_date">
                    </div>
                </div>
                <div class="row mb-3">
                    <label for="inputText" class="col-sm-3 col-form-label">Antimicrobial</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="concurrent_antimicrobial">
                    </div>
                </div>
                <div class="row mb-3">
                    <label for="inputText" class="col-sm-3 col-form-label">Dosing Regimen</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="concurrent_dosing_regimen">
                    </div>
                </div>
                <div class="row mb-3">
                    <label for="inputText" class="col-sm-3 col-form-label">Start Date</label>
                    <div class="col-sm-9">
                        <input type="date" class="form-control" id="concurrent_start_date">
                    </div>
                </div>
                <div class="row mb-3">
                    <label for="inputText" class="col-sm-3 col-form-label">Stop Date</label>
                    <div class="col-sm-9">
                        <input type="date" class="form-control" id="concurrent_stop_date">
                    </div>
                </div>
                <div class="text-center mt-5">
                    <button class="btn btn-primary" id="btn_save_concurrent">SAVE</button>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">CLOSE</button>
                </div>
            </div>
        </div>
    </div>
</div>
