{{-- Shared status dialogs and the review remarks dialog. Page scripts read reasons and days from the label after each input. --}}
<div class="modal fade" id="success_modal" tabindex="-1" aria-labelledby="success_modal_title" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-sm-plus">
        <div class="modal-content status-modal">
            <div class="modal-body">
                <span class="status-icon status-success"><i class="bi bi-check-lg"></i></span>
                <h5 class="status-title" id="success_modal_title">Done</h5>
                <p class="status-message" id="success_message"></p>
                <button class="btn btn-primary w-100" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="error_modal" tabindex="-1" aria-labelledby="error_modal_title" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-sm-plus">
        <div class="modal-content status-modal">
            <div class="modal-body">
                <span class="status-icon status-error"><i class="bi bi-x-lg"></i></span>
                <h5 class="status-title" id="error_modal_title">Something went wrong</h5>
                <p class="status-message" id="error_message"></p>
                <button class="btn btn-light w-100" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="warning_modal" tabindex="-1" aria-labelledby="warning_modal_title" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-sm-plus">
        <div class="modal-content status-modal">
            <div class="modal-body">
                <span class="status-icon status-warning"><i class="bi bi-exclamation-lg"></i></span>
                <h5 class="status-title" id="warning_modal_title">Cancel pending medications?</h5>
                <p class="status-message">
                    Submitting will cancel all pending medications for this patient.
                </p>
                <div class="agree-box">
                    <input type="checkbox" class="form-check-input" id="i_agree">
                    <label for="i_agree">I understand and agree</label>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-light flex-fill" data-bs-dismiss="modal">Go back</button>
                    <button class="btn btn-danger flex-fill" id="btn_proceed_cancel" disabled>Submit</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="remarks_modal" tabindex="-1" aria-labelledby="remarks_message" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-heading">
                    <span class="modal-icon"><i class="bi bi-chat-left-text"></i></span>
                    <h5 class="modal-title" id="remarks_message"></h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label class="form-label" for="common_remarks">Remarks</label>
                <textarea class="form-control" rows="4" id="common_remarks"></textarea>

                <div id="reject_outcome_container" class="mt-3">
                    <p class="form-label">Specific reason <span class="text-muted fw-normal">(optional)</span></p>
                    <div class="choice-group">
                        @foreach(['Wrong indication', 'Double request', 'Incomplete data', 'Discharged', 'Hama', 'Transferred', 'Died', 'Absconded'] as $i => $reason)
                            <div class="choice">
                                <input type="checkbox" name="reject_outcome" id="reject_outcome_{{ $i }}">
                                <label for="reject_outcome_{{ $i }}">{{ $reason }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>

                @if(session('access_rights') == "ICC")
                    <div id="days_approved_container" class="mt-3">
                        <p class="form-label">No. of days approved</p>
                        <div class="choice-group">
                            @foreach(['1', '3', '7', '14'] as $days)
                                <div class="choice">
                                    <input type="radio" name="days_approved" id="days_approved_{{ $days }}">
                                    <label for="days_approved_{{ $days }}">{{ $days }}</label>
                                </div>
                            @endforeach
                            <div class="choice choice-other">
                                <input type="radio" name="days_approved" id="days_approved_others">
                                <label for="days_approved_others">Others</label>
                                <input type="number" class="form-control form-control-sm" id="days_approved" placeholder="Days"/>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="btn_proceed">Proceed</button>
            </div>
        </div>
    </div>
</div>
