{{-- js/pharmacy_review tells the two actions apart by their trimmed text, so keep "Proceed" and "Return to Doctor" as plain text. --}}
<div class="modal fade" id="render_modal" tabindex="-1" aria-labelledby="render_modal_title" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-heading">
                    <span class="modal-icon"><i class="bi bi-capsule"></i></span>
                    <h5 class="modal-title" id="render_modal_title">Render medications</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="form-label">Medications</p>
                <ul class="render-list" id="rendered_list"></ul>
                <label class="form-label" for="pharmacy_remarks">Remarks</label>
                <textarea class="form-control" rows="4" id="pharmacy_remarks"></textarea>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-danger" id="btn_render_doctor">Return to Doctor</button>
                <button class="btn btn-primary" id="btn_render">Proceed</button>
            </div>
        </div>
    </div>
</div>
