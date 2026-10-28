@extends('master')
@section('content')
    <div class="col-12">
        <div class="page-head">
            <div>
                <p class="eyebrow">Administration</p>
                <h1>Nurse management</h1>
                <p class="page-sub">Assign nurses to the stations whose prescriptions they receive.</p>
            </div>
        </div>

        <div class="card list-card">
            <div class="toolbar">
                <div class="input-icon flex-grow-1">
                    <i class="bi bi-person"></i>
                    <input class="form-control" placeholder="First name" id="filter_fname"/>
                </div>
                <div class="input-icon flex-grow-1">
                    <i class="bi bi-person"></i>
                    <input class="form-control" placeholder="Last name" id="filter_lname"/>
                </div>
                <button class="btn btn-primary" id="btn_search_nurse">
                    <i class="bi bi-funnel"></i> Apply
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover m-0 data-table">
                    <thead>
                    <tr>
                        <th>First name</th>
                        <th>Middle name</th>
                        <th>Last name</th>
                        <th>Suffix</th>
                        <th>Station(s)</th>
                        <th style="width: 4%"></th>
                    </tr>
                    </thead>
                    <tbody id="nurse_list"></tbody>
                </table>
            </div>

            @include('layouts.pager')
        </div>
    </div>

    @include('modals/common')
    @include('modals/nurse_admin')
@stop
@section('js')
    @include('js.nurse_admin')
@stop
