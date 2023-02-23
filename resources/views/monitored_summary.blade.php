@extends('master')
@section('content')
    <div class="col-12">
        <div class="d-flex justify-content-end mb-2">
            <div class="me-1">
                <input class="form-control" placeholder="Search patient" id="filter_patient"/>
            </div>
            <div class="ms-1 me-1">
                <input class="form-control" placeholder="Search medicine" id="filter_description"/>
            </div>
            <div class="ms-1">
                <input name="datefilter" class="form-control" placeholder="Select daterange" id="filter_date" readonly/>
            </div>
            <div class="ms-1">
                <button class="btn btn-success" id="btn_search">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title">Monitored Prescriptions</h5>
                    <button class="btn btn-primary d-none" id="btn_download">
                        <i class="bi bi-download"></i>
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table text-center">
                        <thead>
                        <tr>
                            <th style="width: 10%">Patient</th>
                            <th style="width: 4%">Age</th>
                            <th style="width: 4%">Gender</th>
                            <th style="width: 7%">Ward</th>
                            <th style="width: 10%">Date prescribed</th>
                            <th style="width: 10%">Physician</th>
                            <th>Medicine</th>
                            <th style="width: 5%">Quantity<br />Rendered</th>
                            <th style="width: 5%">Quantity<br />Returned</th>
                        </tr>
                        </thead>
                        <tbody id="ams_summary"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop
@section('js')
    @include('js.monitored_summary')
@stop
