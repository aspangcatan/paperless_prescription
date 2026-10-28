@extends('master')
@section('content')
    <div class="col-12">
        <div class="page-head">
            <div>
                <p class="eyebrow">Reports</p>
                <h1>AMS summary</h1>
                <p class="page-sub">Antimicrobial use across the hospital: who prescribed, who approved, and for how long.</p>
            </div>
            <div class="page-actions">
                <button class="btn btn-outline-primary" id="btn_download">
                    <i class="bi bi-download"></i> Export to Excel
                </button>
            </div>
        </div>

        <div class="card list-card">
            <div class="toolbar">
                <select class="form-select toolbar-select" id="filter_type">
                    <option value="">All types</option>
                    <option value="2">Restricted</option>
                    <option value="5">Monitored</option>
                    <option value="8">Non-monitored</option>
                </select>
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
                        <th>Prescribed by</th>
                        <th>Date prescribed</th>
                        <th>Attending physician</th>
                        <th>Antibiotic</th>
                        <th>Approved by</th>
                        <th>Days approved</th>
                        <th>Date approved</th>
                        <th>Start date</th>
                        <th>Stop date</th>
                    </tr>
                    </thead>
                    <tbody id="ams_summary"></tbody>
                </table>
            </div>
        </div>
    </div>
@stop
@section('js')
    @include('js.ams_summary')
@stop
