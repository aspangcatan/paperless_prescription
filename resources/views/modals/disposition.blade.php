<div class="modal fade" id="disposition_modal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="card-title p-1" style="font-size: 24px">Patient Disposition</div>
                <button type="button" style="background: transparent !important;" class="btn" data-bs-dismiss="modal"
                        aria-label="Close">X
                </button>
            </div>
            <div class="modal-body" style="background: rgb(241,244,252)">
                <div class="form-floating mb-3">
                    <select class="form-control" id="disposition_status">
                        <option>Admitted</option>
                        <option>Absconded</option>
                        <option>Died</option>
                        <option>Discharged</option>
                        <option>Discharged against medical advise</option>
                        <option>Transferred</option>
                    </select>
                    <label>Disposition status</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="datetime-local" class="form-control" id="disposition_date">
                    <label>Date</label>
                </div>

                <div class="text-end mt-5">
                    <button class="btn btn-secondary" data-bs-dismiss="modal" style="min-width: 103.58px">CANCEL</button>
                    <button class="btn btn-primary" id="btn_submit" style="min-width: 103.58px">SET</button>
                </div>
            </div>
        </div>
    </div>
</div>
