<div class="modal fade" id="render_modal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <div class="text-end">
                    <button type="button" style="background: transparent !important;" class="btn" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <h5 class="card-title">Medications:</h5>
                <ul id="rendered_list">

                </ul>
                <h6 class="card-title">Write some remarks</h6>
                <textarea class="form-control" style="height: 100px" id="pharmacy_remarks"></textarea>
                <div class="mt-2 text-end">
                    <button class="btn btn-danger me-1" id="btn_render_doctor">Return to Doctor</button>
                    <button class="btn btn-primary ms-1" id="btn_render">Proceed</button>
                </div>
            </div>
        </div>
    </div>
</div>
