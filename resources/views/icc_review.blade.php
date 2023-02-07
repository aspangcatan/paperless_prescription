@extends('master')
@section('content')
    <div class="col-12">
        <div class="d-flex justify-content-end mb-2">
            <div>
                <input class="form-control" placeholder="Filter patient" id="filter_patient"/>
            </div>
            <div class="ms-2 me-1">
                <input type="text" class="form-control" placeholder="Filter date" id="filter_date"/>
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
                    <h5 class="card-title">For Reviews</h5>

                    <button class="btn btn-sm btn-secondary" id="btn_notification">
                        <i class="bi bi-bell"></i>
                        <span class="badge bg-danger badge-number"></span>
                    </button>

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
    @include('modals/common')

@stop
@section('js')
    @include('js.icc_review')
@stop
