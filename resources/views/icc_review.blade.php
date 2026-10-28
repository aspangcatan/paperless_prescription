@extends('master')
@section('content')
    <div class="col-12">
        <div class="page-head">
            <div>
                <p class="eyebrow">IPCC · Stewardship review</p>
                <h1>For review</h1>
                <p class="page-sub">Restricted and monitored antimicrobial orders waiting for your approval.</p>
            </div>
            <div class="page-actions">
                {{-- The script fills the badge and turns this red when new requests arrive. --}}
                <button class="btn btn-secondary btn-icon" id="btn_notification" title="Notifications">
                    <i class="bi bi-bell"></i>
                    <span class="badge bg-danger badge-number"></span>
                </button>
            </div>
        </div>

        <div class="card list-card">
            <div class="toolbar">
                <div class="input-icon flex-grow-1">
                    <i class="bi bi-search"></i>
                    <input class="form-control" placeholder="Search patient name" id="filter_patient"/>
                </div>
                <div class="input-icon">
                    <i class="bi bi-calendar3"></i>
                    <input type="text" class="form-control" placeholder="Date prescribed" id="filter_date"/>
                </div>
                <button class="btn btn-primary" id="btn_search_prescription">
                    <i class="bi bi-funnel"></i> Apply
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover m-0 data-table">
                    <thead>
                    <tr>
                        <th>Priority</th>
                        <th>Hospital ID</th>
                        <th>Patient</th>
                        <th>Station</th>
                        <th>Date prescribed</th>
                        <th colspan="2" class="text-end">Open</th>
                    </tr>
                    </thead>
                    <tbody id="prescription_list"></tbody>
                </table>
            </div>

            @include('layouts.pager')
        </div>
    </div>
    @include('modals/prescription')
    @include('modals/common')

@stop
@section('js')
    @include('js.icc_review')
@stop
