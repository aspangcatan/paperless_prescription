<div class="modal fade" id="return_modal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <div class="text-end">
                    <button type="button" style="background: transparent !important;" class="btn" data-bs-dismiss="modal" aria-label="Close">X</button>
                </div>
                <h5 class="card-title">Return Medication</h5>
                <div class="text-white bg-danger rounded p-2 error_container d-none">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <div>There are some errors, please correct them below:</div>
                    </div>
                    <ul class="error_list"></ul>
                </div>
                <div>
                    <h6>Amount</h6>
                    <div>
                        <input type="number" class="form-control" id="return_amount"/>
                    </div>
                </div>

                <h6 class="card-title">Write some remarks</h6>
                <textarea class="form-control" style="height: 100px" id="return_remarks"></textarea>
                <div class="mt-2 text-end">
                    <button class="btn btn-primary ms-1" id="btn_return">Proceed</button>
                </div>
            </div>
        </div>
    </div>
</div>
