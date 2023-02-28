@extends('master')
@section('content')
    <div class="col-12">
        <div class="d-flex justify-content-end mb-2">
            <div>
                <input class="form-control" placeholder="Search patient" id="filter_patient"/>
            </div>
            <div>
                <select class="form-control" id="filter_station">
                    <option value="" selected>Search station</option>
                </select>
            </div>
            <div>
                <input type="date" class="form-control" placeholder="Search station" id="filter_date"/>
            </div>
            <div class="ms-1">
                <button class="btn btn-success" id="btn_search_prescription">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title">Track Prescription</h5>
                </div>
                <div class="table-responsive">
                    <table class="table text-center">
                        <thead>
                        <tr>
                            <th></th>
                            <th>Hospital ID</th>
                            <th>Patient</th>
                            <th>Station</th>
                            <th>Date Prescribed</th>
                            <th colspan="2" style="width:4%"></th>
                        </tr>
                        </thead>
                        <tbody id="prescription_list"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @include('modals/prescription')
@stop
@section('js')
    @include('js.track_prescription')
@stop
