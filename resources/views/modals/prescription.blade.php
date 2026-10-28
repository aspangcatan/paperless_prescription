{{--
    Shared by the prescription, review, returned and track pages. Their scripts depend on every
    id and class here, read each radio's value from the label right after it (`.next().text()`),
    and compare #btn_submit / #btn_reject text exactly ("REJECT", "APPROVE"), so keep those
    labels as plain text without icons.
--}}
<div class="modal fade" id="prescription_modal" tabindex="-1" aria-labelledby="prescription_modal_title" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-heading">
                    <span class="modal-icon"><i class="bi bi-prescription2"></i></span>
                    <div>
                        <h5 class="modal-title" id="prescription_modal_title">Antimicrobial prescription</h5>
                        <p class="modal-sub">Patient details, clinical data and the antimicrobial order.</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-light d-none" id="btn_download" title="Download latest prescription">
                        <i class="bi bi-download"></i> Download
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body">
                <div class="alert-error error_container d-none">
                    <div class="d-flex align-items-center fw-semibold">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <div>Some details need fixing:</div>
                    </div>
                    <ul class="error_list"></ul>
                </div>

                <section class="form-section">
                    <h6 class="section-title"><i class="bi bi-person-vcard"></i> Patient</h6>
                    <div class="info-grid">
                        <div class="info-tile info-tile-wide">
                            <span class="info-label">Patient</span>
                            <span class="info-value" id="form_patient">N/A</span>
                        </div>
                        <div class="info-tile">
                            <span class="info-label">Hospital ID</span>
                            <span class="info-value" id="form_hospital_id">N/A</span>
                        </div>
                        <div class="info-tile">
                            <span class="info-label">Birthdate</span>
                            <span class="info-value" id="form_birthdate">N/A</span>
                        </div>
                        <div class="info-tile">
                            <span class="info-label">Age</span>
                            <span class="info-value" id="form_age">N/A</span>
                        </div>
                        <div class="info-tile">
                            <span class="info-label">Sex</span>
                            <span class="info-value" id="form_sex">N/A</span>
                        </div>
                    </div>
                </section>

                <section class="form-section">
                    <h6 class="section-title"><i class="bi bi-card-checklist"></i> Order details</h6>
                    <div class="row g-3">
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label" for="form_station">Station</label>
                            <select class="form-select" id="form_station">
                                <option value="">Select station</option>
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-3 d-none">
                            <label class="form-label" for="form_ward">Ward</label>
                            <input class="form-control" id="form_ward"/>
                        </div>
                        <div class="col-md-6 col-lg-3 d-none">
                            <label class="form-label" for="form_bed_no">Bed no.</label>
                            <input class="form-control" id="form_bed_no"/>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label" for="form_attending_physician">Attending physician</label>
                            <input class="form-control" id="form_attending_physician"/>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label" for="form_prescription_type">Type</label>
                            <select class="form-select" id="form_prescription_type">
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
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label" for="form_for_extension">For extension?</label>
                            <select class="form-select" id="form_for_extension">
                                <option value="">Select option</option>
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                    </div>
                </section>

                {{-- The script shows or hides every .ams_container together, by prescription type. --}}
                <section class="form-section ams_container">
                    <h6 class="section-title"><i class="bi bi-activity"></i> Clinical data</h6>
                    <div class="row g-3">
                        <div class="col-md-4 col-lg-3 ams_container">
                            <label class="form-label" for="form_drug_allergy">Drug allergy</label>
                            <input class="form-control" id="form_drug_allergy"/>
                        </div>
                        <div class="col-md-4 col-lg-3 ams_container">
                            <label class="form-label" for="form_weight">Weight (kg)</label>
                            <input type="number" class="form-control" id="form_weight"/>
                        </div>
                        <div class="col-md-4 col-lg-3 ams_container">
                            <label class="form-label" for="form_height">Height (cm)</label>
                            <input type="number" class="form-control" id="form_height"/>
                        </div>
                        <div class="col-md-4 col-lg-3 ams_container">
                            <label class="form-label" for="form_serum_creatinine">Serum creatinine</label>
                            <input class="form-control" id="form_serum_creatinine"/>
                        </div>
                        <div class="col-md-4 col-lg-3 ams_container">
                            <label class="form-label" for="form_creatinine_clearance">Creatinine clearance</label>
                            <input class="form-control" id="form_creatinine_clearance"/>
                        </div>
                        <div class="col-md-4 col-lg-3 ams_container d-none" id="transaminases_container">
                            <label class="form-label" for="form_transaminases">Transaminases</label>
                            <input class="form-control" id="form_transaminases"/>
                        </div>
                        <div class="col-md-4 col-lg-3 ams_container" id="sgpt_container">
                            <label class="form-label" for="form_sgpt">SGPT</label>
                            <input class="form-control" id="form_sgpt"/>
                        </div>
                        <div class="col-md-4 col-lg-3 ams_container" id="sgot_container">
                            <label class="form-label" for="form_sgot">SGOT</label>
                            <input class="form-control" id="form_sgot"/>
                        </div>
                        <div class="col-md-4 col-lg-3 ams_container" id="days_requested_container">
                            <label class="form-label" for="form_days_requested">Duration requested (days)</label>
                            <input type="number" class="form-control" id="form_days_requested"/>
                        </div>
                    </div>
                </section>

                <div class="cancellation_container">
                    <section class="form-section">
                        <h6 class="section-title"><i class="bi bi-person-check"></i> Patient outcome</h6>
                        <div class="choice-group">
                            <div class="choice">
                                <input type="radio" name="outcome" id="outcome_discharged">
                                <label for="outcome_discharged">Discharged</label>
                            </div>
                            <div class="choice">
                                <input type="radio" name="outcome" id="outcome_hama">
                                <label for="outcome_hama">Hama</label>
                            </div>
                            <div class="choice">
                                <input type="radio" name="outcome" id="outcome_transferred">
                                <label for="outcome_transferred">Transferred</label>
                            </div>
                            <div class="choice">
                                <input type="radio" name="outcome" id="outcome_died">
                                <label for="outcome_died">Died</label>
                            </div>
                            <div class="choice">
                                <input type="radio" name="outcome" id="outcome_absconded">
                                <label for="outcome_absconded">Absconded</label>
                            </div>
                            <div class="choice choice-other">
                                <input type="radio" name="outcome" id="outcome_others">
                                <label for="outcome_others">Others</label>
                                <input class="form-control form-control-sm" id="outcome" placeholder="Specify"/>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="ams_container">
                    <section class="form-section">
                        <h6 class="section-title"><i class="bi bi-clock-history"></i> Concurrent antimicrobial therapy</h6>
                        <div class="table-responsive table-scroll">
                            <table class="table data-table table-sm m-0" id="concurrent_table">
                                <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Antimicrobial</th>
                                    <th>Dosing regimen</th>
                                    <th>Start date</th>
                                    <th>Stop date</th>
                                    <th colspan="2" style="width: 8%"></th>
                                </tr>
                                </thead>
                                <tbody id="concurrent_list"></tbody>
                            </table>
                        </div>
                    </section>

                    <section class="form-section callout" id="intervention_form_container">
                        <h6 class="section-title"><i class="bi bi-capsule"></i> Your patient meets the criteria to switch to oral (PO) antimicrobials</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <p class="fw-semibold mb-1">Clinical stability</p>
                                <ul class="criteria">
                                    <li>Afebrile</li>
                                    <li>Downward trend or normalization of inflammatory markers
                                        <small class="d-block">(C-reactive protein, white blood cell count, procalcitonin)</small>
                                    </li>
                                    <li>Stable vital signs
                                        <small class="d-block">(No unexplained tachycardia, hypotension, tachypnea)</small>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <p class="fw-semibold mb-1">Able to tolerate oral intake</p>
                                <ul class="criteria">
                                    <li>Patient is not nil by mouth</li>
                                    <li>Tolerating oral diet, medications and/or enteral feeds</li>
                                    <li>Oral absorption is not compromised
                                        <small class="d-block">(e.g. no vomiting or diarrhea, malabsorptive disorder)</small>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </section>

                    <section class="form-section" id="indication_use_container">
                        <h6 class="section-title"><i class="bi bi-bullseye"></i> Indication for use</h6>
                        <div class="choice-group">
                            <div class="choice">
                                <input type="radio" name="indication_use" id="indication_prophylactic">
                                <label for="indication_prophylactic">Prophylactic</label>
                            </div>
                            <div class="choice">
                                <input type="radio" name="indication_use" id="indication_empiric">
                                <label for="indication_empiric">Empiric</label>
                            </div>
                            <div class="choice">
                                <input type="radio" name="indication_use" id="indication_definitive">
                                <label for="indication_definitive">Definitive</label>
                            </div>
                        </div>
                    </section>

                    <section class="form-section" id="microbiological_container">
                        <div class="section-head">
                            <h6 class="section-title"><i class="bi bi-virus"></i> Microbiological results</h6>
                            <button class="btn btn-sm btn-outline-primary" id="btn_add_microbiological">
                                <i class="bi bi-plus-lg"></i> Add result
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table data-table table-sm m-0">
                                <thead>
                                <tr>
                                    <th style="width: 12%">Date</th>
                                    <th style="width: 30%">Specimen</th>
                                    <th style="width: 50%">Pathogen identified &amp; susceptibility results</th>
                                    <th colspan="2" style="width: 8%"></th>
                                </tr>
                                </thead>
                                <tbody id="microbiological_result_list"></tbody>
                            </table>
                        </div>
                    </section>

                    <div class="row g-3">
                        <div class="col-lg-6" id="additional_labs_container">
                            <section class="form-section h-100">
                                <label class="section-title" for="additional_labs"><i class="bi bi-file-earmark-medical"></i> Additional laboratory information</label>
                                <textarea class="form-control" rows="6" id="additional_labs" placeholder="e.g. UTZ, CT scan"></textarea>
                            </section>
                        </div>
                        <div class="col">
                            <section class="form-section h-100">
                                <label class="section-title" for="diagnosis"><i class="bi bi-clipboard2-pulse"></i> Diagnosis</label>
                                <textarea class="form-control" rows="6" id="diagnosis"></textarea>
                            </section>
                        </div>
                    </div>
                </div>

                <section class="form-section rx-section" id="medication_container">
                    <div class="section-head">
                        <div>
                            <h6 class="section-title"><span class="rx-mark">Rx</span> Antimicrobial order <span id="rx_review_status"></span></h6>
                            <p class="section-hint" id="medication_hint">Add every antimicrobial for this patient. Each one is reviewed on its own.</p>
                        </div>
                        <button class="btn btn-sm btn-primary" id="btn_view_cart">
                            <i class="bi bi-plus-lg"></i> Add antimicrobial
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table data-table v-center m-0">
                            <thead>
                            {{-- Every page builds rows in this order: see public/js/prescription-ui.js. --}}
                            <tr id="table_header">
                                <th>Medicine</th>
                                <th style="width: 20%">Strength</th>
                                <th style="width: 9%">Unit</th>
                                <th style="width: 18%">Frequency</th>
                                <th colspan="2" style="width: 20%" id="quantity_render_th">Status</th>
                            </tr>
                            </thead>
                            <tbody id="medication_list"></tbody>
                        </table>
                    </div>
                    <div class="urgent-toggle">
                        <input type="checkbox" class="form-check-input" id="checkbox_urgent">
                        <label for="checkbox_urgent">
                            <i class="bi bi-lightning-charge-fill"></i> Mark as urgent
                        </label>
                    </div>
                </section>

                <section class="form-section" id="transaction_container">
                    <h6 class="section-title"><i class="bi bi-list-check"></i> Activity log</h6>
                    <div class="table-responsive">
                        <table class="table data-table table-sm m-0">
                            <thead>
                            <tr>
                                <th>Origin</th>
                                <th>Remarks</th>
                                <th>Forwarded to</th>
                                <th>Date forwarded</th>
                            </tr>
                            </thead>
                            <tbody id="transaction_list"></tbody>
                        </table>
                    </div>
                </section>

                <textarea class="form-control" rows="4" id="form_remarks" placeholder="Remarks"></textarea>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-danger" id="btn_reject">REJECT</button>
                <button class="btn btn-primary" id="btn_submit">SUBMIT</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="patient_modal" tabindex="-1" aria-labelledby="patient_modal_title" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-heading">
                    <span class="modal-icon"><i class="bi bi-person-plus"></i></span>
                    <div>
                        <h5 class="modal-title" id="patient_modal_title">Select a patient</h5>
                        <p class="modal-sub">Find the patient locally or in MEDIX, or add them, then make the Rx.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-lg-8 order-lg-2">
                        <section class="form-section h-100">
                            <h6 class="section-title"><i class="bi bi-search"></i> Search patient</h6>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label" for="search_firstname">First name</label>
                                    <input class="form-control" placeholder="e.g. Juan" id="search_firstname"/>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="search_lastname">Last name</label>
                                    <input class="form-control" placeholder="e.g. Dela Cruz" id="search_lastname"/>
                                </div>
                                <div class="col-md-6">
                                    <button class="btn btn-primary w-100" id="btn_search_local">
                                        <i class="bi bi-database"></i> Search locally
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <button class="btn btn-outline-primary w-100" id="btn_search_medix">
                                        <i class="bi bi-cloud"></i> Search MEDIX
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive mt-3">
                                <table class="table table-hover data-table m-0">
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
                        </section>
                    </div>
                    <div class="col-lg-4 order-lg-1">
                        <section class="form-section h-100">
                            <h6 class="section-title"><i class="bi bi-person-lines-fill"></i> Add or update patient</h6>
                            <div class="row g-2">
                                <div class="col-12">
                                    <label class="form-label" for="hospital_id">Hospital ID</label>
                                    <input type="text" class="form-control" id="hospital_id">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="fname">First name</label>
                                    <input type="text" class="form-control" id="fname">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="mname">Middle name</label>
                                    <input type="text" class="form-control" id="mname">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="lname">Last name</label>
                                    <input type="text" class="form-control" id="lname">
                                </div>
                                <div class="col-6">
                                    <label class="form-label" for="suffix">Suffix</label>
                                    <input type="text" class="form-control" id="suffix">
                                </div>
                                <div class="col-6">
                                    <label class="form-label" for="sex">Sex</label>
                                    <select class="form-select" id="sex">
                                        <option value="">Select</option>
                                        <option value="M">MALE</option>
                                        <option value="F">FEMALE</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="birthdate">Birthdate</label>
                                    <input type="date" class="form-control" id="birthdate">
                                </div>
                                <div class="col-12 mt-3">
                                    <button class="btn btn-primary w-100" id="btn_save_patient">
                                        <i class="bi bi-check2"></i> Save patient
                                    </button>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="medicine_modal" tabindex="-1" aria-labelledby="medicine_modal_title" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-heading">
                    <span class="modal-icon"><i class="bi bi-capsule"></i></span>
                    <div>
                        <h5 class="modal-title" id="medicine_modal_title">Antimicrobials</h5>
                        <p class="modal-sub">Search the formulary and pick one to add to the order.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="input-icon mb-3">
                    <i class="bi bi-search"></i>
                    <input class="form-control" placeholder="Search by description" id="search_medicine"/>
                </div>
                <div class="table-responsive table-scroll-lg">
                    <table class="table table-hover data-table m-0">
                        <thead>
                        <tr>
                            <th>Description</th>
                            <th>Unit</th>
                            <th style="width: 5%"></th>
                        </tr>
                        </thead>
                        <tbody id="medicine_list">
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="microbiological_modal" tabindex="-1" aria-labelledby="microbiological_modal_title" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-heading">
                    <span class="modal-icon"><i class="bi bi-virus"></i></span>
                    <h5 class="modal-title" id="microbiological_modal_title">Add microbiological result</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert-error error_container d-none">
                    <div class="d-flex align-items-center fw-semibold">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <div>Some details need fixing:</div>
                    </div>
                    <ul class="error_list"></ul>
                </div>
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label" for="microbiological_date">Date</label>
                        <input type="date" class="form-control" id="microbiological_date">
                    </div>
                    <div class="col-md-7">
                        <label class="form-label" for="microbiological_specimen">Specimen</label>
                        <input type="text" class="form-control" id="microbiological_specimen">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="microbiological_pathogen">Pathogen identified &amp; susceptibility results</label>
                        <textarea class="form-control" rows="4" id="microbiological_pathogen"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="btn_save_microbiological"><i class="bi bi-check2"></i> Save result</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="concurrent_modal" tabindex="-1" aria-labelledby="concurrent_modal_title" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-heading">
                    <span class="modal-icon"><i class="bi bi-clock-history"></i></span>
                    <h5 class="modal-title" id="concurrent_modal_title">Add concurrent antimicrobial therapy</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert-error error_container d-none">
                    <div class="d-flex align-items-center fw-semibold">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <div>Some details need fixing:</div>
                    </div>
                    <ul class="error_list"></ul>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="concurrent_date">Date</label>
                        <input type="date" class="form-control" id="concurrent_date">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="concurrent_antimicrobial">Antimicrobial</label>
                        <input type="text" class="form-control" id="concurrent_antimicrobial">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="concurrent_dosing_regimen">Dosing regimen</label>
                        <input type="text" class="form-control" id="concurrent_dosing_regimen">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="concurrent_start_date">Start date</label>
                        <input type="date" class="form-control" id="concurrent_start_date">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="concurrent_stop_date">Stop date</label>
                        <input type="date" class="form-control" id="concurrent_stop_date">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="btn_save_concurrent"><i class="bi bi-check2"></i> Save therapy</button>
            </div>
        </div>
    </div>
</div>
