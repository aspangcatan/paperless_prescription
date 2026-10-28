@extends('master')
@section('content')
    <div class="col-12">
        <div class="page-head">
            <div>
                <p class="eyebrow">Patients</p>
                <h1>Patient disposition</h1>
                <p class="page-sub">Record whether a patient is admitted, discharged, transferred or has died.</p>
            </div>
        </div>

        <div class="card list-card">
            <div class="toolbar">
                <div class="input-icon flex-grow-1">
                    <i class="bi bi-person"></i>
                    <input class="form-control" placeholder="First name" id="filter_firstname"/>
                </div>
                <div class="input-icon flex-grow-1">
                    <i class="bi bi-person"></i>
                    <input class="form-control" placeholder="Last name" id="filter_lastname"/>
                </div>
                <button class="btn btn-primary" id="btn_search_prescription">
                    <i class="bi bi-funnel"></i> Apply
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover m-0 data-table">
                    <thead>
                    <tr>
                        <th>Hospital ID</th>
                        <th>Patient</th>
                        <th>Birthdate</th>
                        <th>Sex</th>
                        <th>Disposition status</th>
                        <th colspan="2" style="width: 4%"></th>
                    </tr>
                    </thead>
                    <tbody id="patient_list"></tbody>
                </table>
            </div>

            @include('layouts.pager')
        </div>
    </div>
    @include('modals/disposition')
@stop
@section('js')
    @include('js.patient_disposition')
@stop
