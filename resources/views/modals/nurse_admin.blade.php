<div class="modal fade" id="nurses_modal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <div class="text-center text-dark mb-5" style="font-size: 30px">Nurse Management</div>
                <div class="form-floating mt-3">
                    <input type="text" class="form-control" id="fname" placeholder="" readonly>
                    <label>Firstname</label>
                </div>
                <div class="form-floating mt-3">
                    <input type="text" class="form-control" id="mname" placeholder="" readonly>
                    <label>Middlename</label>
                </div>
                <div class="form-floating mt-3">
                    <input type="text" class="form-control" id="lname" placeholder="" readonly>
                    <label>Lastname</label>
                </div>
                <div class="form-floating mt-3">
                    <input type="text" class="form-control" id="suffix" placeholder="" readonly>
                    <label>Suffix</label>
                </div>
                <div class="card mt-3">
                    <div class="card-body">
                        <h6 class="card-title">Stations</h6>
                        <div class="row" id="stations">

                        </div>
                    </div>
                </div>
                <div class="text-center mt-5">
                    <button class="btn btn-primary" id="btn_save">SAVE</button>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">CLOSE</button>
                </div>
            </div>
        </div>
    </div>
</div>
