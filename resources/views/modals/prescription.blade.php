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
                                    <small>(Ward)</small>
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
                                        <option value="1" disabled>Regular</option>
                                        <option value="2">Anti-Microbial Stewardship Program</option>
                                    </select>
                                    <small>(Prescription Type)</small>
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
                            <div class="col-md-4 mt-2 ams_container">
                                <div class="text-center">
                                    <input class="form-control bg-white fw-bold text-center"
                                           id="form_transaminases"/>
                                    <small>(Transaminases)</small>
                                </div>
                            </div>
                            <div class="col-md-4 mt-2 ams_container">
                                <div class="text-center">
                                    <input class="form-control bg-white fw-bold text-center"
                                           id="form_sgpt"/>
                                    <small>(SGPT)</small>
                                </div>
                            </div>
                            <div class="col-md-4 mt-2 ams_container">
                                <div class="text-center">
                                    <input class="form-control bg-white fw-bold text-center"
                                           id="form_sgot"/>
                                    <small>(SGOT)</small>
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
                                <div>
                                    <button class="btn btn-sm btn-success" id="btn_add_concurrent">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table text-center">
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
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title">Indication for use</h6>
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
                            <div class="mt-2">
                                <h6>Include reason for not using recommended antimicrobial</h6>
                                <textarea class="form-control" style="height:100px" id="indication_reason"></textarea>
                            </div>

                        </div>
                    </div>
                    <div class="card">
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
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="card-title fs-4">Rx</h6>
                            <div>
                                <button class="btn btn-sm btn-success" id="btn_view_cart">
                                    <i class="bi bi-plus-lg"></i>
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
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div class="card-title p-1" style="font-size: 24px">Additional Information</div>
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
                                    <th style="width: 58%">Organism</th>
                                    <th style="width: 40%">Susceptibility Result or Specific Resistance Pattern</th>
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
                                    <input type="text" class="form-control bg-white" id="suffix" placeholder=""
                                           readonly>
                                    <label>Suffix</label>
                                </div>
                                <div class="form-floating mt-3">
                                    <input type="date" class="form-control bg-white" id="birthdate" placeholder=""
                                           readonly>
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
                                        <button class="btn btn-lg form-control btn-secondary" id="btn_find">SEARCH
                                        </button>
                                    </div>
                                    <div class="col-md-6">
                                        <button class="btn btn-lg form-control btn-success" id="btn_save_patient">ADD
                                        </button>
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
