<div class="modal fade" id="medicine_modal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-body">
                <h6 class="card-title">Medicine Information</h6>
                <div class="text-white bg-danger rounded p-2 error_container d-none">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <div>There are some errors, please correct them below:</div>
                    </div>
                    <ul class="error_list"></ul>
                </div>

                <div class="row mb-3 mt-2">
                    <label class="col-sm-3 col-form-label">Description</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="medicine_description">
                    </div>
                </div>
                <div class="row mb-3 d-none">
                    <label class="col-sm-3 col-form-label">Unit</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="medicine_unit">
                    </div>
                </div>
                <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Type</label>
                    <div class="col-sm-9">
                        <select class="form-control" id="medicine_type">
                            <option value="" selected>Select type</option>
                            <option value="1" disabled>Non-Restricted</option>
                            <option value="2">Restricted</option>
                            <option value="3">Seventh Day Auto Stop</option>
                            <option value="5">Monitored</option>
                        </select>
                    </div>
                </div>
                <div class="form-check">
                    <input  type="checkbox" name="medicine_status">
                    <label class="form-check-label">
                        Active
                    </label>
                </div>
                <div class="text-center mt-5">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">CLOSE</button>
                    <button class="btn btn-primary" id="btn_save_medicine">SAVE</button>
                </div>
            </div>
        </div>
    </div>
</div>
