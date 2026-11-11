@if(!empty($ledger_details['pagination']))
    <div class="col-md-12 no-print" id="contact_ledger_pagination">
        <div class="ledger-page-info" data-start="{{ $ledger_details['pagination']->firstItem() ?? 0 }}"
             data-end="{{ $ledger_details['pagination']->lastItem() ?? 0 }}"
             data-total="{{ $ledger_details['pagination']->total() }}"></div>
        <div class="text-right">{{ $ledger_details['pagination']->links() }}</div>
    </div>
@endif
