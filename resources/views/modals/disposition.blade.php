<div class="modal fade" id="disposition_modal" tabindex="-1" aria-labelledby="disposition_modal_title" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-sm-plus">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-heading">
                    <span class="modal-icon"><i class="bi bi-person-lines-fill"></i></span>
                    <h5 class="modal-title" id="disposition_modal_title">Patient disposition</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-column gap-3">
                    <div>
                        <label class="form-label" for="disposition_status">Disposition status</label>
                        <select class="form-select" id="disposition_status">
                            <option>Admitted</option>
                            <option>Absconded</option>
                            <option>Died</option>
                            <option>Discharged</option>
                            <option>Discharged against medical advise</option>
                            <option>Transferred</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="disposition_date">Date</label>
                        <input type="datetime-local" class="form-control" id="disposition_date">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="btn_submit">Set disposition</button>
            </div>
        </div>
    </div>
</div>
