@extends('master')
@section('content')
    <div class="col-12">
        <div class="d-flex justify-content-end mb-2">
            <div class="me-1">
                <select class="form-control" id="filter_type">
                    <option value="">Filter type</option>
                    <option value="2">Restricted</option>
                    <option value="5">Monitored</option>
                    <option value="8">Non-monitored</option>
                </select>
            </div>
            <div class="ms-1 me-1">
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
                    <h5 class="card-title">AMS Summary</h5>
                    <button class="btn btn-primary" id="btn_download">
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
                            <th style="width: 10%">Prescribed by</th>
                            <th style="width: 10%">Date prescribed</th>
                            <th style="width: 10%">Attending Physician</th>
                            <th>Antibiotic</th>
                            <th style="width: 10%">Approved by</th>
                            <th style="width: 5%">Days<br />approved</th>
                            <th>Date<br />Approved</th>
                            <th style="width: 7%">Start date</th>
                            <th style="width: 7%">Stop date</th>
                        </tr>
                        </thead>
                        <tbody id="ams_summary" style="font-size: 12px"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop
@section('js')
    @include('js.ams_summary')
@stop
