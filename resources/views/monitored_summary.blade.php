@extends('master')
@section('content')
    <div class="col-12">
        <div class="page-head">
            <div>
                <p class="eyebrow">Reports</p>
                <h1>Monitored prescriptions</h1>
                <p class="page-sub">Monitored antimicrobials with the quantities rendered and returned.</p>
            </div>
            <div class="page-actions">
                <button class="btn btn-outline-primary d-none" id="btn_download">
                    <i class="bi bi-download"></i> Export to Excel
                </button>
            </div>
        </div>

        <div class="card list-card">
            <div class="toolbar">
                <div class="input-icon flex-grow-1">
                    <i class="bi bi-search"></i>
                    <input class="form-control" placeholder="Patient" id="filter_patient"/>
                </div>
                <div class="input-icon flex-grow-1">
                    <i class="bi bi-capsule"></i>
                    <input class="form-control" placeholder="Medicine" id="filter_description"/>
                </div>
                <div class="input-icon">
                    <i class="bi bi-calendar3"></i>
                    <input name="datefilter" class="form-control" placeholder="Date range" id="filter_date" readonly/>
                </div>
                <button class="btn btn-primary" id="btn_search">
                    <i class="bi bi-funnel"></i> Apply
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-sm m-0 data-table report-table">
                    <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Age</th>
                        <th>Sex</th>
                        <th>Ward</th>
                        <th>Date prescribed</th>
                        <th>Physician</th>
                        <th>Medicine</th>
                        <th>Qty rendered</th>
                        <th>Qty returned</th>
                    </tr>
                    </thead>
                    <tbody id="ams_summary"></tbody>
                </table>
            </div>
        </div>
    </div>
@stop
@section('js')
    @include('js.monitored_summary')
@stop
