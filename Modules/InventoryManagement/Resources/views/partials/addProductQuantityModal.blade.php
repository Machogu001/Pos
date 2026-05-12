<div class="modal" tabindex="-1" id="addProductQuantityModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">@lang('inventory::inventory.current_product_qty')</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.close') }}"></button>
            </div>
            <div class="modal-body">
                <p>@lang('inventory::inventory.current_product_qty')</p>
                <input type="number" name="quantity" id="inputProductQuantity">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="closeProductQuantityModal">@lang('inventory::inventory.close')</button>
                <button type="button" class="btn btn-primary" id="saveProductQuantity">@lang('inventory::inventory.save_changes')</button>
            </div>
        </div>
    </div>
</div>
