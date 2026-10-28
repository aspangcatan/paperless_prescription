@extends('master')
@section('content')
    <div class="col-12">
        <div class="page-head">
            <div>
                <p class="eyebrow">Antimicrobial Stewardship</p>
                <h1>Track prescription</h1>
                <p class="page-sub">Find any prescription and follow it through review, dispensing and receipt.</p>
            </div>
        </div>

        <div class="card list-card">
            <div class="toolbar">
                <div class="input-icon flex-grow-1">
                    <i class="bi bi-search"></i>
                    <input class="form-control" placeholder="Search patient name" id="filter_patient"/>
                </div>
                <div class="input-icon">
                    <i class="bi bi-hospital"></i>
                    <select class="form-select" id="filter_station">
                        <option value="" selected>All stations</option>
                    </select>
                </div>
                <div class="input-icon">
                    <i class="bi bi-calendar3"></i>
                    <input type="date" class="form-control" id="filter_date"/>
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
@stop
@section('js')
    @include('js.track_prescription')
@stop
