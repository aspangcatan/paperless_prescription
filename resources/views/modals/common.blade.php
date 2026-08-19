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

<div class="modal fade" id="warning_modal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-center">
                <div class="p-3 w-100 text-center">
                    <i class="bi bi-exclamation-triangle-fill text-white" style="font-size: 80px;"></i>
                </div>
            </div>
            <div class="modal-body">
                <div class="text-secondary font-weight-bold fs-4 text-center">Warning!</div>
                <div class="mt-2 text-secondary fs-5">
                    <div>
                        By clicking submit, You agree to cancel all pending medications particular to this patient.
                    </div>
                    <div class="mt-3">
                        <div class="form-check ps-0">
                            <input type="checkbox" id="i_agree">
                            <label class="form-check-label">I agree</label>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-4">
                    <button class="btn btn-lg btn-secondary" data-bs-dismiss="modal" aria-label="Close">Close</button>
                    <button class="btn btn-lg btn-success" id="btn_proceed_cancel" disabled>Submit</button>
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
                    <button type="button" style="background: transparent !important;" class="btn" data-bs-dismiss="modal" aria-label="Close">X</button>
                </div>

                <h6 class="card-title" id="remarks_message"></h6>
                <textarea class="form-control" style="height: 100px" id="common_remarks"></textarea>

                <div id="reject_outcome_container">
                    <h6 class="card-title">Specific reason <small>(optional)</small></h6>
                    <div class="form-check ps-0">
                        <input type="checkbox" name="reject_outcome">
                        <label class="form-check-label">
                            Wrong indication
                        </label>
                    </div>
                    <div class="form-check ps-0">
                        <input type="checkbox" name="reject_outcome">
                        <label class="form-check-label">
                            Double request
                        </label>
                    </div>
                    <div class="form-check ps-0">
                        <input type="checkbox" name="reject_outcome">
                        <label class="form-check-label">
                            Incomplete data
                        </label>
                    </div>
                    <div class="form-check ps-0">
                        <input type="checkbox" name="reject_outcome">
                        <label class="form-check-label">
                            Discharged
                        </label>
                    </div>
                    <div class="form-check ps-0">
                        <input type="checkbox" name="reject_outcome">
                        <label class="form-check-label">
                            Hama
                        </label>
                    </div>
                    <div class="form-check ps-0">
                        <input type="checkbox" name="reject_outcome">
                        <label class="form-check-label">
                            Transferred
                        </label>
                    </div>
                    <div class="form-check ps-0">
                        <input type="checkbox" name="reject_outcome">
                        <label class="form-check-label">
                            Died
                        </label>
                    </div>
                    <div class="form-check ps-0">
                        <input type="checkbox" name="reject_outcome">
                        <label class="form-check-label">
                            Absconded
                        </label>
                    </div>
                </div>

                <hr/>
                <button class="btn btn-primary mt-2 float-end" id="btn_proceed">Proceed</button>
            </div>
        </div>
    </div>
</div>
