@extends('master')
@section('content')
    <div class="col-12">
        <div class="d-flex justify-content-end mb-2">
            <div>
                <input class="form-control" placeholder="Search by firstname" id="filter_fname"/>
            </div>
            <div class="ms-2 me-1">
                <input class="form-control" placeholder="Search by lastname" id="filter_lname"/>
            </div>
            <div class="ms-1">
                <button class="btn btn-success" id="btn_search_nurse">
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

    @include('modals/common')
    @include('modals/nurse_admin')


@stop
@section('js')
    @include('js.nurse_admin')
@stop
