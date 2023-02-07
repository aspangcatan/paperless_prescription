<div class="modal fade" id="success_modal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-center">
                <div class="w-100 text-center">
                    <i class="bi bi-emoji-smile text-white" style="font-size: 80px;"></i>
                </div>
            </div>
            <div class="modal-body">
                <div class="text-secondary fw-bold   fs-4 text-center">Success!</div>
                <div class="mt-2 text-secondary fs-5 text-center" id="success_message"></div>
                <div class="text-center mt-4">
                    <button class="btn btn-lg btn-danger" data-bs-dismiss="modal" aria-label="Close">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="error_modal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-center">
                <div class="p-3 w-100 text-center">
                    <i class="bi bi-emoji-frown text-white" style="font-size: 80px;"></i>
                </div>
            </div>
            <div class="modal-body">
                <div class="text-secondary font-weight-bold fs-4 text-center">Ooops!</div>
                <div class="mt-2 text-secondary fs-5 text-center" id="error_message"></div>
                <div class="text-center mt-4">
                    <button class="btn btn-lg btn-danger" data-bs-dismiss="modal" aria-label="Close">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="remarks_modal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <div class="text-end">
                    <button type="button" class="btn-close fs-3" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <h5 class="card-title">Give remarks</h5>
                <h6 id="remarks_message"></h6>
                <textarea class="form-control" style="height: 100px" id="common_remarks"></textarea>

                @if(session('access_rights') == "ICC")
                    <div id="days_approved_container" class="mt-3">
                        <h6>No. days approved</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="days_approved">
                                    <label class="form-check-label">1</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="days_approved">
                                    <label class="form-check-label">3</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="days_approved">
                                    <label class="form-check-label">7</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="days_approved">
                                    <label class="form-check-label">14</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="days_approved">
                                    <div class="d-flex align-items-center">
                                        <label class="form-check-label">Others</label>
                                        <input type="number" class="underline ms-1" id="days_approved"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                <hr/>
                <button class="btn btn-primary mt-2 float-end" id="btn_proceed">Proceed</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="print_rx_modal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <div class="text-end">
                    <button type="button" class="btn-close fs-3" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <h5 class="card-title">Printing Rx</h5>
                <div id="print_prescription" class="p-2">
                    <div class="d-flex align-items-center justify-content-center mb-3">
                        <div>
                            <img src="images/logo.png" class="img-title" />
                        </div>
                        <div class="text-center ml-3">
                            <div class="font-weight-bold">VICENTE SOTTO MEMORIAL MEDICAL CENTER</div>
                            <div>B. Rodriguez Street, Cebu City, Philippines 6000</div>
                            <div>Tel. No: 253-9891</div>
                        </div>
                    </div>
                    <hr />
                    <div class="patient-information mt-5">
                        <div class="d-flex">
                            <div class="flex-grow-1">
                                <div class="d-flex">
                                    <div class="flex-grow-2 mr-2">Patient:</div>
                                    <div class="flex-grow-1 border-bottom border-dark font-weight-bold"
                                         id="unrendered_patient"></div>
                                </div>
                                <div class="d-flex">
                                    <div class="flex-grow-2 mr-2">Age & Sex:</div>
                                    <div class="flex-grow-1 border-bottom border-dark font-weight-bold"
                                         id="unrendered_patient_age">23/M</div>
                                </div>
                                <div class="d-flex">
                                    <div class="flex-grow-2 mr-2">Ward No:</div>
                                    <div class="flex-grow-1 border-bottom border-dark font-weight-bold"
                                         id="unrendered_patient_ward">23/M</div>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex">
                                    <div class="flex-grow-2 ml-2 mr-2">Date:</div>
                                    <div class="flex-grow-1 border-bottom border-dark font-weight-bold"
                                         id="unrendered_date"></div>
                                </div>
                                <div class="d-flex">
                                    <div class="flex-grow-2 ml-2 mr-2">Nurse Station:</div>
                                    <div class="flex-grow-1 border-bottom border-dark font-weight-bold"
                                         id="unrendered_patient_station">WARD-11LRU</div>
                                </div>
                                <div class="d-flex">
                                    <div class="flex-grow-2 ml-2 mr-2">Bed No:</div>
                                    <div class="flex-grow-1 border-bottom border-dark font-weight-bold"
                                         id="unrendered_patient_bed">WARD-11LRU</div>
                                </div>
                            </div>
                        </div>
                        <div class="title font-weight-bold mb-2 mt-5 d-flex justify-content-between">Rx
                        </div>
                        <div class="ml-3 font-weight-bold mb-2" id="unrendered_drugs">
                        </div>
                        <div class="d-flex justify-content-end mt-2">
                            <div class="d-flex flex-column">
                                <div class="text-center">
                                    <img src="images/no_signature.png" id="doctor_signature" height="100"
                                         width="100" />
                                </div>
                                <div class="font-weight-bold border-bottom border-dark text-center"
                                     id="unrendered_doctor"></div>
                                <div>[doctor's tru-dat bearing full name, license # & S2 license #]</div>
                                <div><small id="doctor_pcr_no"></small></div>
                                <div><small id="s2_license"></small></div>
                                <div><small id="license_validity"></small></div>
                            </div>
                        </div>
                    </div>
                </div>
                <button class="btn btn-primary mt-2 float-end" id="btn_proceed">Proceed</button>
            </div>
        </div>
    </div>
</div>

