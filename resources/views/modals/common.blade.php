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
                <button class="btn btn-primary mt-2 float-end" id="btn_proceed">Proceed</button>
            </div>
        </div>
    </div>
</div>
