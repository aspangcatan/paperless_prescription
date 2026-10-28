<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title>E-AMSP</title>
    <meta content="" name="description">
    <meta content="" name="keywords">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Favicons -->
    <link href="{{ asset('images/csmc-logo.png') }}" rel="icon">
    <link href="{{ asset('assets/img/apple-touch-icon.png') }}" rel="apple-touch-icon">

    <!-- Fonts -->
    <link href="{{ asset('css/fonts.css') }}" rel="stylesheet">

<!-- Vendor CSS Files -->
    <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/boxicons/css/boxicons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/quill/quill.snow.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/quill/quill.bubble.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/remixicon/remixicon.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/simple-datatables/style.css') }}" rel="stylesheet">

    <!-- Template Main CSS File -->
    <link href="{{ asset('assets/css/style.css') }}?v={{ date('ymdhis') }}" rel="stylesheet">
    <link href="{{ asset('css/toastr.min.css') }}?v={{ date('ymdhis') }}" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/daterangepicker.css') }}"/>
    <link href="{{ asset('css/app-theme.css') }}?v={{ date('ymdhis') }}" rel="stylesheet">
    <!-- =======================================================
    * Template Name: NiceAdmin - v2.5.0
    * Template URL: https://bootstrapmade.com/nice-admin-bootstrap-admin-html-template/
    * Author: BootstrapMade.com
    * License: https://bootstrapmade.com/license/
    ======================================================== -->
</head>

<body class="theme-v2">
<div class="fullscreen-modal d-none" id="spinner">
    <div class="spinner-border text-light" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
    <div class="text-white fs-5 mt-1">Loading, please wait ...</div>
</div>
<!-- ======= Header ======= -->
@include('layouts.navbar')
<!-- ======= Sidebar ======= -->
@include('layouts.sidebar')
@include('modals.settings')
@include('modals.whats_new')
<main id="main" class="main">
    @hasSection('Title')
        <div class="pagetitle">
            <h1>@yield('Title')</h1>
        </div>
    @endif
    <section class="section dashboard">
        <div class="row">
            @yield('content')
        </div>
    </section>

</main>
<!-- End #main -->
<!-- ======= Footer ======= -->
<footer id="footer" class="footer">
    <div class="copyright">
        &copy; {{ date('Y') }} <strong><span>Cebu South Medical Center</span></strong> · E-AMSP
    </div>
    <div class="credits">
        <!-- All the links in the footer should remain intact. -->
        <!-- You can delete the links only if you purchased the pro version. -->
        <!-- Licensing information: https://bootstrapmade.com/license/ -->
        <!-- Purchase the pro version with working PHP/AJAX contact form: https://bootstrapmade.com/nice-admin-bootstrap-admin-html-template/ -->
        Designed by <a href="https://bootstrapmade.com/">BootstrapMade</a>
    </div>
</footer><!-- End Footer -->

<a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i
        class="bi bi-arrow-up-short"></i></a>

<!-- Vendor JS Files -->
<script src="{{ asset('assets/vendor/apexcharts/apexcharts.min.js') }}"></script>
<script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/vendor/chart.js/chart.umd.js') }}"></script>
<script src="{{ asset('assets/vendor/echarts/echarts.min.js') }}"></script>
<script src="{{ asset('assets/vendor/quill/quill.min.js') }}"></script>
<script src="{{ asset('assets/vendor/simple-datatables/simple-datatables.js') }}"></script>
<script src="{{ asset('assets/vendor/tinymce/tinymce.min.js') }}"></script>
<script src="{{ asset('assets/vendor/php-email-form/validate.js') }}"></script>
<!-- Template Main JS File -->
<script src="{{ asset('assets/js/main.js') }}"></script>
<script src="{{ asset('js/jquery.min.js') }}"></script>
<script src="{{ asset('js/toastr.min.js') }}"></script>
<script>
    const settings_modal = new bootstrap.Modal(document.getElementById('settings_modal'));
    const whats_new_el = document.getElementById('whats_new_modal');
    const whats_new_modal = new bootstrap.Modal(whats_new_el);
    const whats_new_key = "whats_new_hidden_" + whats_new_el.dataset.user + "_" + whats_new_el.dataset.version;

    function isWhatsNewHidden() {
        try {
            return localStorage.getItem(whats_new_key) === "1";
        } catch (e) {
            return false;
        }
    }

    $(document).ready(function () {
        if (whats_new_el.dataset.autoOpen === "1" && !isWhatsNewHidden()) {
            whats_new_modal.show();
        }

        $("#whats_new_hide").prop("checked", isWhatsNewHidden());

        whats_new_el.addEventListener('hidden.bs.modal', function () {
            document.getElementById('whats_new_video').pause();
            try {
                if ($("#whats_new_hide").is(":checked")) {
                    localStorage.setItem(whats_new_key, "1");
                } else {
                    localStorage.removeItem(whats_new_key);
                }
            } catch (e) {
            }
        });

        $("#open_whats_new").click(function (e) {
            e.preventDefault();
            whats_new_modal.show();
        });

        $("#change_password").click(function (e) {
            e.preventDefault();
            settings_modal.toggle();
        });

        $("#btn_change_password").click(function () {
            let is_valid = true;
            $("#settings_modal .is-invalid").removeClass("is-invalid");
            $("#settings_modal .error_container").addClass("d-none");
            $("#settings_modal .error_list").empty();

            const old_password = $("#old_password").val().trim();
            const new_password = $("#new_password").val().trim();
            const confirm_password = $("#confirm_password").val().trim();

            if (old_password == "") {
                is_valid = false;
                $("#settings_modal .error_list").append("<li>Old password is required</li>");
                $("#old_password").addClass("is-invalid");
            }

            if (new_password == "") {
                is_valid = false;
                $("#settings_modal .error_list").append("<li>New password is required</li>");
                $("#old_password").addClass("is-invalid");
            }

            if (confirm_password == "") {
                is_valid = false;
                $("#settings_modal .error_list").append("<li>Confirm password is required</li>");
                $("#old_password").addClass("is-invalid");
            }

            if(new_password.length < 4){
                is_valid = false;
                $("#settings_modal .error_list").append("<li>New password must at least 4 characters</li>");
                $("#new_password").addClass("is-invalid");
            }

            if(new_password != confirm_password){
                is_valid = false;
                $("#settings_modal .error_list").append("<li>Password mismatch</li>");
                $("#new_password").addClass("is-invalid");
            }

            if (!is_valid) {
                $("#settings_modal .error_container").removeClass("d-none");
                return;
            }

            const params = {
                old_password: old_password,
                new_password: new_password,
                confirm_password: confirm_password
            }

            fetch('{{route('changePassword')}}', {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                body: JSON.stringify(params)
            })
                .then(res => res.json())
                .then(data => {
                    if (data.code == 500) {
                        toastr.error(data.message, "Something went wrong")
                        return;
                    }

                    settings_modal.toggle();
                    toastr.success(data.message, "Information");
                    $("#old_password").val("");
                    $("#new_password").val("");
                    $("#confirm_password").val("");
                }).catch(err => {
                alert(err);
            });
        });
    });
</script>
@include('js.firebase-config')
@yield('js')
</body>

</html>
