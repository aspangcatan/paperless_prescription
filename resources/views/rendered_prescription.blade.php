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
                    <h5 class="card-title">Tagged Prescriptions</h5>
                </div>

                <div class="table-responsive">
                    <table class="table text-center">
                        <thead>
                        <tr>
                            <th></th>
                            <th>Hospital ID</th>
                            <th>Patient</th>
                            <th>Station</th>
                            <th>Date Rendered</th>
                            <th>Date Prescribed</th>
                            <th colspan="2" style="width:4%"></th>
                        </tr>
                        </thead>
                        <tbody id="prescription_list"></tbody>
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
    @include('modals/prescription')
    @include('modals/return')
@stop
@section('js')
    @include('js.rendered_prescription')
@stop
