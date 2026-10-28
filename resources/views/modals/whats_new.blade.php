{{--
    Shown once after signing in (session flash "whats_new") until the user ticks "Don't show this again",
    and any time from "What's new" in the profile menu. Bump data-version when the content changes
    so everyone sees it again.
--}}
<div class="modal fade" id="whats_new_modal" tabindex="-1" aria-labelledby="whats_new_modal_title" aria-hidden="true"
     data-version="2026-10-per-medicine" data-auto-open="{{ session('whats_new') ? '1' : '0' }}" data-user="{{ Auth::id() }}">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-heading">
                    <span class="modal-icon"><i class="bi bi-stars"></i></span>
                    <div>
                        <h5 class="modal-title" id="whats_new_modal_title">What's new in E-AMSP</h5>
                        <p class="modal-sub">Prescriptions now hold several antimicrobials, each reviewed on its own.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <section class="whats-new-video">
                    <h6><i class="bi bi-play-circle"></i> Watch one Rx go through the full cycle <span>4½ min</span></h6>
                    <p>
                        Doctor → IPCC → back to the doctor to edit one rejected medicine and remove another →
                        IPCC → Pharmacy → Nurse ends the cycle.
                    </p>
                    <video controls preload="none" playsinline id="whats_new_video"
                           poster="{{ asset('images/whats-new/full-cycle-poster.png') }}">
                        <source src="{{ asset('videos/whats-new/full-cycle-walkthrough.webm') }}?v=2" type="video/webm">
                        Your browser can't play this video.
                        <a href="{{ asset('videos/whats-new/full-cycle-walkthrough.webm') }}?v=2" target="_blank">Download it</a> instead.
                    </video>
                </section>

                <section class="whats-new-item">
                    <div class="whats-new-num">1</div>
                    <div class="whats-new-text">
                        <h6>Add more than one medicine per Rx</h6>
                        <p>
                            A prescription is no longer limited to one antimicrobial. In <strong>Make Rx</strong>, use
                            <strong>Add antimicrobial</strong> to list every antimicrobial the patient needs in a single
                            prescription. Each one gets its own status.
                        </p>
                        <a href="{{ asset('images/whats-new/rx_multi_medicine.png') }}" target="_blank" title="Open full size">
                        <img src="{{ asset('images/whats-new/rx_multi_medicine.png') }}" class="whats-new-shot"
                             alt="An antimicrobial order with three medicines: two approved for 7 days and one rejected with a reason"></a>
                    </div>
                </section>

                <section class="whats-new-item">
                    <div class="whats-new-num">2</div>
                    <div class="whats-new-text">
                        <h6>AMS Review approves each medicine separately</h6>
                        <p>
                            Reviewers <strong>Approve</strong> or <strong>Reject</strong> every medicine on its own, set
                            the approved number of days per medicine, and give a reason when rejecting. Medicines left
                            undecided stay <em>Pending</em>. The prescriber sees the decision and reason under each medicine.
                        </p>
                        <a href="{{ asset('images/whats-new/ams_review_per_medicine.png') }}" target="_blank" title="Open full size">
                        <img src="{{ asset('images/whats-new/ams_review_per_medicine.png') }}" class="whats-new-shot"
                             alt="AMS Review showing Approve and Reject buttons under a pending medicine"></a>
                    </div>
                </section>

                <section class="whats-new-item">
                    <div class="whats-new-num">3</div>
                    <div class="whats-new-text">
                        <h6>New approval statuses</h6>
                        <p>Every prescription shows an overall status based on its medicines:</p>
                        <ul class="whats-new-status">
                            <li><span class="review-pill review-pending">Pending</span> No medicine has been decided yet.</li>
                            <li><span class="review-pill review-approved">Approved</span> All medicines were approved.</li>
                            <li><span class="review-pill review-partial">Partially approved</span> A mix of decisions, e.g. some approved and others rejected or still pending.</li>
                            <li><span class="review-pill review-rejected">Rejected</span> All medicines were rejected.</li>
                        </ul>
                    </div>
                </section>
            </div>
            <div class="modal-footer justify-content-between">
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" id="whats_new_hide">
                    <label class="form-check-label" for="whats_new_hide">Don't show this again</label>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a class="btn btn-light" href="{{ route('whats_new') }}">Open full page</a>
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Got it</button>
                </div>
            </div>
        </div>
    </div>
</div>
