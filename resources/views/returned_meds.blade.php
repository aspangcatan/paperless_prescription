@extends('master')
@section('content')
    <div class="col-12">
        <div class="page-head">
            <div>
                <p class="eyebrow">Pharmacy</p>
                <h1>Returned medicines</h1>
                <p class="page-sub">Unused antimicrobials returned by the wards. Receive them to close the record.</p>
            </div>
        </div>

        <div class="card list-card">
            <div class="toolbar">
                <div class="input-icon flex-grow-1">
                    <i class="bi bi-capsule"></i>
                    <input class="form-control" placeholder="Medicine" id="filter_description"/>
                </div>
                <div class="input-icon">
                    <i class="bi bi-calendar3"></i>
                    <input type="date" class="form-control" id="filter_date"/>
                </div>
                <button class="btn btn-primary" id="btn_search_medicine">
                    <i class="bi bi-funnel"></i> Apply
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover m-0 data-table">
                    <thead>
                    <tr>
                        <th>Hospital ID</th>
                        <th>Patient</th>
                        <th>Ward</th>
                        <th>Medicine</th>
                        <th>Amount</th>
                        <th>Returned by</th>
                        <th>Date returned</th>
                        <th>Received by</th>
                        <th>Date received</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody id="medicine_list"></tbody>
                </table>
            </div>
        </div>
    </div>
@stop
@section('js')
    @include('js.returned_meds')
@stop
