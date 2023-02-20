@extends('master')
@section('content')
    <div class="col-12">
        <div class="d-flex justify-content-end mb-2">
            <div>
                <input class="form-control" placeholder="Search medicine" id="filter_description"/>
            </div>
            <div>
                <input type="date" class="form-control" placeholder="Search medicine" id="filter_date"/>
            </div>
            <div class="ms-1">
                <button class="btn btn-success" id="btn_search_medicine">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title">Returned Medicines</h5>
                </div>
                <div class="table-responsive">
                    <table class="table text-center">
                        <thead>
                        <tr>
                            <th>Hospital ID</th>
                            <th>Patient</th>
                            <th>Ward</th>
                            <th>Medicine</th>
                            <th>Amount</th>
                            <th>Returned By</th>
                            <th>Date Returned</th>
                            <th>Received By</th>
                            <th>Date Received</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody id="medicine_list"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop
@section('js')
    @include('js.returned_meds')
@stop
