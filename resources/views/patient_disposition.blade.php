@extends('master')
@section('content')
    <div class="col-12">
        <div class="d-flex justify-content-end mb-2">
            <div>
                <input class="form-control" placeholder="Search firstname" id="filter_firstname"/>
            </div>
            <div>
                <input class="form-control" placeholder="Search lastname" id="filter_lastname"/>
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
                    <h5 class="card-title">Patient List</h5>
                </div>
                <div class="table-responsive">
                    <table class="table text-center">
                        <thead>
                        <tr>
                            <th>Hospital ID</th>
                            <th>Patient</th>
                            <th>Birthdate</th>
                            <th>Gender</th>
                            <th>Disposition Status</th>
                            <th colspan="2" style="width:4%"></th>
                        </tr>
                        </thead>
                        <tbody id="patient_list"></tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-dark" id="pagination_container">
                <div class="d-flex align-items-center justify-content-center">
                    <button class="btn btn-sm d-none" style="background: transparent !important;" id="btn_prev">
                        <i class="bi bi-caret-left"></i>
                    </button>
                    <div id="page_items_count"></div>
                    <button class="btn btn-sm" style="background: transparent !important;" id="btn_next">
                        <i class="bi bi-caret-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @include('modals/disposition')
@stop
@section('js')
    @include('js.patient_disposition')
@stop
