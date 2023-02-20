@extends('master')
@section('content')
    <div class="col-12">
        <div class="d-flex justify-content-end mb-2">
            <div>
                <input class="form-control" placeholder="Search medicine" id="search_medicine"/>
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
                    <h5 class="card-title">Medicines</h5>
                    <button class="btn btn-success" id="btn_add_medicine">
                        Add medicine
                        <i class="bi bi-plus-lg ms-2"></i>
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table text-center">
                        <thead>
                        <tr>
                            <th>Description</th>
                            <th>Unit</th>
                            <th style="width: 20%">Type</th>
                            <th style="width: 10%">Status</th>
                            <th style="width:4%"></th>
                        </tr>
                        </thead>
                        <tbody id="medicine_list"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @include('modals/common')
    @include('modals/medicine')
@stop
@section('js')
    @include('js.medicine')
@stop
