<div class="modal fade" id="medicine_modal" tabindex="-1" aria-labelledby="medicine_modal_title" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-heading">
                    <span class="modal-icon"><i class="bi bi-capsule"></i></span>
                    <h5 class="modal-title" id="medicine_modal_title">Medicine information</h5>
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
                    <div class="col-12">
                        <label class="form-label" for="medicine_description">Description</label>
                        <input type="text" class="form-control" id="medicine_description">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="medicine_unit">Unit</label>
                        <input type="text" class="form-control" id="medicine_unit">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="medicine_type">Type</label>
                        <select class="form-select" id="medicine_type">
                            <option value="" selected>Select type</option>
                            <option value="1" disabled>Non-Restricted</option>
                            <option value="2">Restricted</option>
                            <option value="3">Seventh Day Auto Stop</option>
                            <option value="5">Monitored</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" name="medicine_status" id="medicine_status">
                            <label class="form-check-label" for="medicine_status">Active</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="btn_save_medicine"><i class="bi bi-check2"></i> Save medicine</button>
            </div>
        </div>
    </div>
</div>
