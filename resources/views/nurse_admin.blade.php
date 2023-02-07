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
                    <h5 class="card-title">Nurses</h5>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover text-center">
                        <thead>
                        <tr>
                            <th>Firstname</th>
                            <th>Middlename</th>
                            <th>Lastname</th>
                            <th>Suffix</th>
                            <th>Station(s)</th>
                            <th style="width:4%"></th>
                        </tr>
                        </thead>
                        <tbody id="nurse_list">

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @include('modals/common')
    @include('modals/nurse_admin')


@stop
@section('js')
    @include('js.nurse_admin')
@stop
