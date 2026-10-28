<div class="modal fade" id="nurses_modal" tabindex="-1" aria-labelledby="nurses_modal_title" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-heading">
                    <span class="modal-icon"><i class="bi bi-people"></i></span>
                    <div>
                        <h5 class="modal-title" id="nurses_modal_title">Nurse stations</h5>
                        <p class="modal-sub">Pick the stations this nurse receives prescriptions for.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <section class="form-section">
                    <h6 class="section-title"><i class="bi bi-person-vcard"></i> Nurse</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="fname">First name</label>
                            <input type="text" class="form-control" id="fname" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="mname">Middle name</label>
                            <input type="text" class="form-control" id="mname" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="lname">Last name</label>
                            <input type="text" class="form-control" id="lname" readonly>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="suffix">Suffix</label>
                            <input type="text" class="form-control" id="suffix" readonly>
                        </div>
                    </div>
                </section>
                <section class="form-section mb-0">
                    <h6 class="section-title"><i class="bi bi-hospital"></i> Stations</h6>
                    {{-- js/nurse_admin fills this with one checkbox + label per station. --}}
                    <div class="choice-group" id="stations"></div>
                </section>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="btn_save"><i class="bi bi-check2"></i> Save stations</button>
            </div>
        </div>
    </div>
</div>
