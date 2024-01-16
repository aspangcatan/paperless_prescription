<aside id="sidebar" class="sidebar">
    <ul class="sidebar-nav" id="sidebar-nav">
        @if(session('access_rights') == "Doctor" || session('access_rights') == "ICC")
            <li class="nav-item">
                <a class="nav-link collapsed" href="{{ route('prescription') }}">
                    <i class="bi bi-grid"></i>
                    <span>Prescription</span>
                </a>
            </li>
        @endif
        @if(session('access_rights') == "Nurse" || session('access_rights') == "Nurse Admin")
            <li class="nav-item">
                <a class="nav-link collapsed" href="{{ route('nurse_review') }}">
                    <i class="bi bi-grid"></i>
                    <span>Nurse Review</span>
                </a>
            </li>
            <li class="nav-item d-none">
                <a class="nav-link collapsed" href="{{ route('rendered_prescription') }}">
                    <i class="bi bi-grid"></i>
                    <span>Tagged Prescriptions</span>
                </a>
            </li>
        @endif
        @if(session('access_rights') == "ICC")
            <li class="nav-item">
                <a class="nav-link collapsed" href="{{ route('icc_review') }}">
                    <i class="bi bi-grid"></i>
                    <span>AMS Review</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" href="{{ route('ams_summary') }}">
                    <i class="bi bi-grid"></i>
                    <span>AMS Summary</span>
                </a>
            </li>
            <li class="nav-item d-none">
                <a class="nav-link collapsed" href="{{ route('monitored_summary') }}">
                    <i class="bi bi-grid"></i>
                    <span>Monitored Prescription</span>
                </a>
            </li>
        @endif
        @if(session('access_rights') == "Pharmacist" || session('access_rights') == "Pharmacist Admin")
            <li class="nav-item">
                <a class="nav-link collapsed" href="{{ route('pharmacy_review') }}">
                    <i class="bi bi-grid"></i>
                    <span>Pharmacy Review</span>
                </a>
            </li>
            <li class="nav-item d-none">
                <a class="nav-link collapsed" href="{{ route('returned_meds') }}">
                    <i class="bi bi-grid"></i>
                    <span>Returned Medicines</span>
                </a>
            </li>
            <li class="nav-item d-none">
                <a class="nav-link collapsed" href="{{ route('issuances') }}">
                    <i class="bi bi-grid"></i>
                    <span>Medicine Issuances</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link collapsed" href="{{ route('ams_summary') }}">
                    <i class="bi bi-grid"></i>
                    <span>AMS Summary</span>
                </a>
            </li>
        @endif
            @if(session('access_rights') == "Doctor" || session('access_rights') == "ICC")
            <li class="nav-item">
                <a class="nav-link collapsed" href="{{ route('returned_prescription') }}">
                    <i class="bi bi-grid"></i>
                    <span>Returned Prescription</span>
                </a>
            </li>
        @endif
            <li class="nav-item">
                <a class="nav-link collapsed" href="{{ route('medicine') }}">
                    <i class="bi bi-grid"></i>
                    <span>Medicine Dictionary</span>
                </a>
            </li>
        @if(session('access_rights') == "Pharmacist Admin")

        @endif
        @if(session('access_rights') == "Nurse Admin")
            <li class="nav-item">
                <a class="nav-link collapsed" href="{{ route('nurse_admin') }}">
                    <i class="bi bi-grid"></i>
                    <span>Nurse Management</span>
                </a>
            </li>
        @endif
            <li class="nav-item d-none">
                <a class="nav-link collapsed" href="{{ route('patient_disposition') }}">
                    <i class="bi bi-search"></i>
                    <span>Patient Disposition</span>
                </a>
            </li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="{{ route('track_prescription') }}">
                <i class="bi bi-search"></i>
                <span>Track Prescription</span>
            </a>
        </li>
    </ul>
</aside><!-- End Sidebar-->
