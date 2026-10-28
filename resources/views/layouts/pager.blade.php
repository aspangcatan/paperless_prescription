{{-- Page scripts drive these by id: they fill #page_items_count and toggle #btn_prev / #btn_next. --}}
<div class="card-footer" id="pagination_container">
    <div class="pager">
        <span class="pager-label">Showing <strong id="page_items_count"></strong></span>
        <div class="pager-buttons">
            <button class="btn btn-sm btn-light d-none" id="btn_prev" title="Previous page">
                <i class="bi bi-chevron-left"></i>
            </button>
            <button class="btn btn-sm btn-light" id="btn_next" title="Next page">
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>
    </div>
</div>
