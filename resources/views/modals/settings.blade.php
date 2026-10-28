{{-- Change password dialog; master.blade.php drives it by id. --}}
<div class="modal fade" id="settings_modal" tabindex="-1" aria-labelledby="settings_modal_title" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-sm-plus">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-heading">
                    <span class="modal-icon"><i class="bi bi-key"></i></span>
                    <h5 class="modal-title" id="settings_modal_title">Change password</h5>
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
                <div class="d-flex flex-column gap-3">
                    <div>
                        <label class="form-label" for="old_password">Current password</label>
                        <input type="password" class="form-control" id="old_password" autocomplete="current-password"/>
                    </div>
                    <div>
                        <label class="form-label" for="new_password">New password</label>
                        <input type="password" class="form-control" id="new_password" autocomplete="new-password"/>
                        <div class="form-text">At least 4 characters.</div>
                    </div>
                    <div>
                        <label class="form-label" for="confirm_password">Confirm new password</label>
                        <input type="password" class="form-control" id="confirm_password" autocomplete="new-password"/>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="btn_change_password">Update password</button>
            </div>
        </div>
    </div>
</div>
