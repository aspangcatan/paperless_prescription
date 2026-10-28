@extends('master')
@section('content')
    {{-- Same content as modals/whats_new.blade.php, laid out as a page. Keep the two in step. --}}
    <div class="col-12 whats-new-page">
        <div class="page-head">
            <div>
                <p class="eyebrow">Release notes · October 2026</p>
                <h1>What's new</h1>
                <p class="page-sub">Prescriptions now hold several antimicrobials, and each one is reviewed on its own.</p>
            </div>
        </div>

        <div class="card wn-hero">
            <div class="wn-hero-video">
                <div class="wn-label"><i class="bi bi-play-circle-fill"></i> Watch · 4½ min</div>
                <h2>One Rx through the full cycle</h2>
                <video controls preload="none" playsinline id="whats_new_page_video"
                       poster="{{ asset('images/whats-new/full-cycle-poster.png') }}">
                    <source src="{{ asset('videos/whats-new/full-cycle-walkthrough.webm') }}?v=2" type="video/webm">
                    Your browser can't play this video.
                    <a href="{{ asset('videos/whats-new/full-cycle-walkthrough.webm') }}?v=2" target="_blank">Download it</a> instead.
                </video>
            </div>
            <div class="wn-hero-steps">
                <h3>The cycle at a glance</h3>
                <ol class="wn-steps">
                    <li>
                        <span class="wn-step-icon"><i class="bi bi-prescription2"></i></span>
                        <div><strong>Doctor</strong> makes one Rx with four antimicrobials</div>
                    </li>
                    <li>
                        <span class="wn-step-icon"><i class="bi bi-shield-check"></i></span>
                        <div><strong>IPCC</strong> approves two, rejects two with a reason</div>
                    </li>
                    <li>
                        <span class="wn-step-icon wn-step-warn"><i class="bi bi-arrow-counterclockwise"></i></span>
                        <div><strong>Doctor</strong> edits one rejected medicine and removes the other</div>
                    </li>
                    <li>
                        <span class="wn-step-icon"><i class="bi bi-shield-check"></i></span>
                        <div><strong>IPCC</strong> approves the edited medicine</div>
                    </li>
                    <li>
                        <span class="wn-step-icon"><i class="bi bi-capsule"></i></span>
                        <div><strong>Pharmacy</strong> dispenses the approved medicines</div>
                    </li>
                    <li>
                        <span class="wn-step-icon wn-step-done"><i class="bi bi-check2-circle"></i></span>
                        <div><strong>Nurse</strong> receives them and ends the cycle</div>
                    </li>
                </ol>
            </div>
        </div>

        <div class="card wn-feature">
            <div class="wn-feature-text">
                <span class="wn-num">1</span>
                <h3>Add more than one medicine per Rx</h3>
                <p>
                    A prescription is no longer limited to one antimicrobial. In <strong>Make Rx</strong>, use
                    <strong>Add antimicrobial</strong> to list every antimicrobial the patient needs in a single
                    prescription. Each one gets its own status.
                </p>
            </div>
            <a class="wn-feature-shot" href="{{ asset('images/whats-new/rx_multi_medicine.png') }}" target="_blank" title="Open full size">
                <img src="{{ asset('images/whats-new/rx_multi_medicine.png') }}"
                     alt="An antimicrobial order with three medicines: two approved for 7 days and one rejected with a reason">
            </a>
        </div>

        <div class="card wn-feature wn-feature-flip">
            <div class="wn-feature-text">
                <span class="wn-num">2</span>
                <h3>AMS Review approves each medicine separately</h3>
                <p>
                    Reviewers <strong>Approve</strong> or <strong>Reject</strong> every medicine on its own, set the
                    approved number of days per medicine, and give a reason when rejecting. Medicines left undecided
                    stay <em>Pending</em>. The prescriber sees the decision and reason under each medicine.
                </p>
            </div>
            <a class="wn-feature-shot" href="{{ asset('images/whats-new/ams_review_per_medicine.png') }}" target="_blank" title="Open full size">
                <img src="{{ asset('images/whats-new/ams_review_per_medicine.png') }}"
                     alt="AMS Review showing Approve and Reject buttons under a pending medicine">
            </a>
        </div>

        <div class="card wn-statuses">
            <div class="wn-feature-text">
                <span class="wn-num">3</span>
                <h3>New approval statuses</h3>
                <p>Every prescription shows an overall status based on its medicines.</p>
            </div>
            <div class="wn-status-grid">
                <div class="wn-status wn-status-pending">
                    <span class="review-pill review-pending">Pending</span>
                    <p>No medicine has been decided yet.</p>
                </div>
                <div class="wn-status wn-status-approved">
                    <span class="review-pill review-approved">Approved</span>
                    <p>All medicines were approved.</p>
                </div>
                <div class="wn-status wn-status-partial">
                    <span class="review-pill review-partial">Partially approved</span>
                    <p>A mix of decisions, e.g. some approved and others rejected or still pending.</p>
                </div>
                <div class="wn-status wn-status-rejected">
                    <span class="review-pill review-rejected">Rejected</span>
                    <p>All medicines were rejected.</p>
                </div>
            </div>
        </div>

        <h2 class="wn-section-title">What changes for you</h2>
        <div class="wn-roles">
            <div class="card wn-role">
                <span class="wn-role-icon"><i class="bi bi-prescription2"></i></span>
                <h4>Doctor</h4>
                <ul>
                    <li>Add several antimicrobials to one Rx.</li>
                    <li>Rejected medicines come back in <strong>Returned Prescription</strong> with the reason.</li>
                    <li>Edit a rejected medicine, remove it, or add a new one. Approved ones are not touched.</li>
                </ul>
            </div>
            <div class="card wn-role">
                <span class="wn-role-icon"><i class="bi bi-shield-check"></i></span>
                <h4>IPCC</h4>
                <ul>
                    <li>Approve or reject each medicine on its own.</li>
                    <li>Choose the approved days per medicine.</li>
                    <li>A reason is required for every rejection.</li>
                </ul>
            </div>
            <div class="card wn-role">
                <span class="wn-role-icon"><i class="bi bi-capsule"></i></span>
                <h4>Pharmacy</h4>
                <ul>
                    <li>Every medicine shows its status.</li>
                    <li>Only approved medicines can be dispensed.</li>
                    <li>Rejected and withdrawn medicines are shown for reference only.</li>
                </ul>
            </div>
            <div class="card wn-role">
                <span class="wn-role-icon"><i class="bi bi-clipboard2-pulse"></i></span>
                <h4>Nurse</h4>
                <ul>
                    <li>See which medicines were approved, for how many days, and dispensed.</li>
                    <li>End the cycle once the medicines are received.</li>
                </ul>
            </div>
        </div>
    </div>
@endsection
