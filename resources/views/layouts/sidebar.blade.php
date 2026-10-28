@php
    $role = session('access_rights');
    // NiceAdmin marks every link except the current page as "collapsed".
    $navClass = function ($route) {
        return request()->routeIs($route) ? 'nav-link' : 'nav-link collapsed';
    };
@endphp
<aside id="sidebar" class="sidebar">
    <ul class="sidebar-nav" id="sidebar-nav">
        <li class="nav-heading">Prescriptions</li>
        @if($role == "Doctor" || $role == "ICC")
            <li class="nav-item">
                <a class="{{ $navClass('prescription') }}" href="{{ route('prescription') }}">
                    <i class="bi bi-prescription2"></i>
                    <span>Prescription</span>
                </a>
            </li>
        @endif
        @if($role == "Nurse" || $role == "Nurse Admin")
            <li class="nav-item">
                <a class="{{ $navClass('nurse_review') }}" href="{{ route('nurse_review') }}">
                    <i class="bi bi-clipboard2-pulse"></i>
                    <span>Nurse Review</span>
                </a>
            </li>
            <li class="nav-item d-none">
                <a class="{{ $navClass('rendered_prescription') }}" href="{{ route('rendered_prescription') }}">
                    <i class="bi bi-tags"></i>
                    <span>Tagged Prescriptions</span>
                </a>
            </li>
        @endif
        @if($role == "ICC")
            <li class="nav-item">
                <a class="{{ $navClass('icc_review') }}" href="{{ route('icc_review') }}">
                    <i class="bi bi-shield-check"></i>
                    <span>AMS Review</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="{{ $navClass('ams_summary') }}" href="{{ route('ams_summary') }}">
                    <i class="bi bi-bar-chart-line"></i>
                    <span>AMS Summary</span>
                </a>
            </li>
            <li class="nav-item d-none">
                <a class="{{ $navClass('monitored_summary') }}" href="{{ route('monitored_summary') }}">
                    <i class="bi bi-eye"></i>
                    <span>Monitored Prescription</span>
                </a>
            </li>
        @endif
        @if($role == "Pharmacist" || $role == "Pharmacist Admin")
            <li class="nav-item">
                <a class="{{ $navClass('pharmacy_review') }}" href="{{ route('pharmacy_review') }}">
                    <i class="bi bi-capsule"></i>
                    <span>Pharmacy Review</span>
                </a>
            </li>
            <li class="nav-item d-none">
                <a class="{{ $navClass('returned_meds') }}" href="{{ route('returned_meds') }}">
                    <i class="bi bi-arrow-return-left"></i>
                    <span>Returned Medicines</span>
                </a>
            </li>
            <li class="nav-item d-none">
                <a class="{{ $navClass('issuances') }}" href="{{ route('issuances') }}">
                    <i class="bi bi-box-seam"></i>
                    <span>Medicine Issuances</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="{{ $navClass('ams_summary') }}" href="{{ route('ams_summary') }}">
                    <i class="bi bi-bar-chart-line"></i>
                    <span>AMS Summary</span>
                </a>
            </li>
        @endif
        @if($role == "Doctor" || $role == "ICC")
            <li class="nav-item">
                <a class="{{ $navClass('returned_prescription') }}" href="{{ route('returned_prescription') }}">
                    <i class="bi bi-arrow-counterclockwise"></i>
                    <span>Returned Prescription</span>
                </a>
            </li>
        @endif

        <li class="nav-heading">Reference</li>
        <li class="nav-item">
            <a class="{{ $navClass('medicine') }}" href="{{ route('medicine') }}">
                <i class="bi bi-journal-medical"></i>
                <span>Medicine Dictionary</span>
            </a>
        </li>
        @if($role == "Nurse Admin")
            <li class="nav-item">
                <a class="{{ $navClass('nurse_admin') }}" href="{{ route('nurse_admin') }}">
                    <i class="bi bi-people"></i>
                    <span>Nurse Management</span>
                </a>
            </li>
        @endif
        <li class="nav-item d-none">
            <a class="{{ $navClass('patient_disposition') }}" href="{{ route('patient_disposition') }}">
                <i class="bi bi-person-lines-fill"></i>
                <span>Patient Disposition</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="{{ $navClass('track_prescription') }}" href="{{ route('track_prescription') }}">
                <i class="bi bi-search"></i>
                <span>Track Prescription</span>
            </a>
        </li>
    </ul>
</aside><!-- End Sidebar-->
