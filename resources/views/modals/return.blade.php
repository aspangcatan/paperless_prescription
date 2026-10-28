<div class="modal fade" id="return_modal" tabindex="-1" aria-labelledby="return_modal_title" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-sm-plus">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-heading">
                    <span class="modal-icon"><i class="bi bi-arrow-return-left"></i></span>
                    <h5 class="modal-title" id="return_modal_title">Return medication</h5>
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
                <div class="d-flex flex-column gap-3">
                    <div>
                        <label class="form-label" for="return_amount">Amount</label>
                        <input type="number" class="form-control" id="return_amount"/>
                    </div>
                    <div>
                        <label class="form-label" for="return_remarks">Remarks</label>
                        <textarea class="form-control" rows="4" id="return_remarks"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="btn_return">Proceed</button>
            </div>
        </div>
    </div>
</div>
