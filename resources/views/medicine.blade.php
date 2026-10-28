@extends('master')
@section('content')
    <div class="col-12">
        <div class="page-head">
            <div>
                <p class="eyebrow">Reference</p>
                <h1>Medicine dictionary</h1>
                <p class="page-sub">Antimicrobials on the formulary, with their stewardship type and status.</p>
            </div>
            @if(session('access_rights') == "Pharmacist Admin")
                <div class="page-actions">
                    <button class="btn btn-primary" id="btn_add_medicine">
                        <i class="bi bi-plus-lg"></i> Add medicine
                    </button>
                </div>
            @endif
        </div>

        <div class="card list-card">
            <div class="toolbar">
                <div class="input-icon flex-grow-1">
                    <i class="bi bi-search"></i>
                    <input class="form-control" placeholder="Search medicine" id="search_medicine"/>
                </div>
                <button class="btn btn-primary" id="btn_search_medicine">
                    <i class="bi bi-funnel"></i> Apply
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover m-0 data-table">
                    <thead>
                    <tr>
                        <th>Description</th>
                        <th>Unit</th>
                        <th style="width: 20%">Type</th>
                        <th style="width: 10%">Status</th>
                        <th style="width: 4%"></th>
                    </tr>
                    </thead>
                    <tbody id="medicine_list"></tbody>
                </table>
            </div>
        </div>
    </div>
    @include('modals/common')
    @include('modals/medicine')
@stop
@section('js')
    @include('js.medicine')
@stop
