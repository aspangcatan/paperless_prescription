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
                    <h5 class="card-title">Medicine Issuance Summary</h5>
                </div>
                <div class="table-responsive">
                    <table class="table text-center">
                        <thead>
                        <tr>
                            <th>Description</th>
                            <th>Unit</th>
                            <th>Quantity Rendered</th>
                            <th>Quantity Returned</th>
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
    @include('js.issuance')
@stop
