@extends('layouts.app')

@section('title', __('stocktake.add_stocktake'))

@section('content')
<div class="row">
    <div class="col-md-12">
        {{-- Validation Errors --}}
        @include('stocktake.partials.validation_errors')

        {{-- Status Messages --}}
        @include('stocktake.partials.status_message')

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h3 class="card-title">@lang('stocktake.add_stocktake')</h3>
            </div>
            <div class="card-body">
                {!! Form::open(['route' => 'stocktakes.store', 'method' => 'post', 'id' => 'stocktake_form']) !!}
                
                <div class="row mb-4">
                    {{-- Business Location --}}
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('location_id', __('business.business_location') . ':*') !!}
                            {!! Form::select('location_id', $locations, $default_location, [
                                'class' => 'form-control select2',
                                'required',
                                'style' => 'width: 100%;',
                                'id' => 'location_id'
                            ]) !!}
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div class="col-md-8">
                        <div class="form-group">
                            {!! Form::label('additional_notes', __('stocktake.additional_notes') . ':') !!}
                            {!! Form::textarea('additional_notes', old('additional_notes'), [
                                'class' => 'form-control',
                                'rows' => 3,
                                'placeholder' => __('stocktake.notes_placeholder')
                            ]) !!}
                        </div>
                    </div>
                </div>

                {{-- Products Table Section --}}
                <div class="row mt-3">
                    <div class="col-md-12">
                        {{-- Row Filter --}}
                        <div class="form-group mb-3">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa fa-filter"></i></span>
                                <input type="text" class="form-control" id="row_filter"
                                       placeholder="@lang('stocktake.filter_added_products')">
                            </div>
                        </div>

                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-bordered table-striped" id="items_table">
                                <thead class="thead-dark">
                                    <tr>
                                        <th width="20%">@lang('stocktake.product_name')</th>
                                        <th width="12%">@lang('stocktake.product_lot_no')</th>
                                        <th width="12%">@lang('stocktake.product_expiry_date')</th>
                                        <th width="12%">@lang('stocktake.current_stock_report')</th>
                                        <th width="12%">@lang('stocktake.stocktake_counted_qty') *</th>
                                        <th width="12%">@lang('stocktake.stocktake_variance')</th>
                                        <th width="10%">@lang('stocktake.action')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Initial blank row --}}
                                    <tr class="editable-row" data-index="0">
                                        <td class="product-search">
                                            <input type="text" class="form-control search-input"
                                                   placeholder="@lang('stocktake.search_product')" autocomplete="off">
                                            <div class="autocomplete-results" style="display: none;"></div>
                                        </td>
                                        <td class="lot-number">
                                            <input type="text" class="form-control lot-input"
                                                   placeholder="@lang('stocktake.optional')">
                                        </td>
                                        <td class="expiry-date">
                                            <input type="text" class="form-control expiry-input datepicker"
                                                   placeholder="@lang('stocktake.optional')">
                                        </td>
                                        <td class="current-stock">-</td>
                                        <td class="counted-qty">
                                            <input type="number" class="form-control counted-input"
                                                   placeholder="0.00" min="0" step="0.0001" disabled>
                                        </td>
                                        <td class="stock-variance">-</td>
                                        <td class="action">
                                            <button type="button" class="btn btn-danger btn-sm remove-row" disabled>
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            <button type="button" class="btn btn-info" id="add_blank_row">
                                <i class="fa fa-plus"></i> @lang('stocktake.add_blank_row')
                            </button>
                        </div>
                    </div>
                </div>
                        
                <div class="keyboard-hint mt-3 p-3 bg-light rounded">
                    <p class="mb-1"><strong>@lang('stocktake.keyboard_shortcuts'):</strong></p>
                    <div>
                        <span class="badge bg-secondary me-2">Tab</span> @lang('stocktake.navigate_between_fields')
                        <span class="badge bg-secondary mx-2">Enter</span> @lang('stocktake.confirm_counted_quantity_next_row')
                        <span class="badge bg-secondary mx-2">↑ ↓</span> @lang('stocktake.navigate_rows_search_results')
                    </div>
                    <p class="mt-2 mb-0 text-muted"><small>@lang('stocktake.note_mouse_delete_rows')</small></p>
                </div>

                {{-- Submit Button --}}
                <div class="row mt-4">
                    <div class="col-md-12 text-center">
                        <button type="submit" class="btn btn-primary btn-lg" id="submit_btn">
                            <i class="fa fa-save"></i> @lang('stocktake.save')
                        </button>
                    </div>
                </div>

                {!! Form::close() !!}
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script>
$(document).ready(function() {
    // Initialize select2 dropdowns
    $('.select2').select2();

    let row_index = 1; // Start from 1 since we have initial row at index 0
    let added_products = [];
    let current_autocomplete_index = -1;
    let current_row_index = 0;

    // Initialize datepicker
    function initDatepicker(element) {
        element.datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true,
            yearRange: 'c-5:c+5',
            showButtonPanel: true
        });
    }
    
    // Initialize datepicker for existing elements
    $('.expiry-input').each(function() {
        initDatepicker($(this));
    });

    // Add blank row function
    function addBlankRow() {
        const newRow = `
            <tr class="editable-row" data-index="${row_index}">
                <td class="product-search">
                    <input type="text" class="form-control search-input" 
                           placeholder="@lang('stocktake.search_product')" autocomplete="off">
                    <div class="autocomplete-results"></div>
                </td>
                <td class="lot-number">
                    <input type="text" class="form-control lot-input" 
                           placeholder="@lang('stocktake.optional')">
                </td>
                <td class="expiry-date">
                    <input type="text" class="form-control expiry-input" 
                           placeholder="@lang('stocktake.optional')">
                </td>
                <td class="current-stock">-</td>
                <td class="counted-qty">
                    <input type="number" class="form-control counted-input" 
                           placeholder="0.00" min="0" step="0.0001" disabled>
                </td>
                <td class="stock-variance">-</td>
                <td class="action">
                    <button type="button" class="btn btn-danger btn-sm remove-row" disabled>
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        
        $('#items_table tbody').append(newRow);
        
        // Initialize datepicker for the new expiry input
        initDatepicker($(`#items_table tbody tr[data-index="${row_index}"] .expiry-input`));
        
        // Focus on the new row's search input
        setTimeout(() => {
            const newInput = $(`#items_table tbody tr[data-index="${row_index}"] .search-input`);
            newInput.focus();
            current_row_index = row_index;
        }, 100);
        
        row_index++;
    }

    // Add blank row button
    $('#add_blank_row').on('click', function() {
        addBlankRow();
    });

    // Load products for autocomplete - UPDATED to match controller structure
    function loadProducts(search, callback) {
        let location_id = $('#location_id').val();
        
        if (!location_id) {
            toastr.error('@lang("stocktake.select_location_first")');
            callback([]);
            return;
        }

        if (!search || search.length < 2) {
            callback([]);
            return;
        }

        // Use the correct route that matches your controller
        $.ajax({
            url: '{{ route("stocktakes.get-products") }}',
            data: {
                term: search,
                location_id: location_id
            },
            success: function(response) {
                if (response.success && response.products) {
                    callback(response.products);
                } else {
                    callback([]);
                }
            },
            error: function(xhr) {
                console.error('Search error:', xhr);
                toastr.error('@lang("stocktake.something_went_wrong")');
                callback([]);
            }
        });
    }

    // Show autocomplete results
    function showAutocompleteResults(input, products) {
        const resultsContainer = input.closest('.product-search').find('.autocomplete-results');
        resultsContainer.empty();
        
        if (products.length === 0) {
            resultsContainer.hide();
            return;
        }
        
        products.forEach((product, index) => {
            const variation_name = product.variation_name ? ` (${product.variation_name})` : '';
            const isAdded = added_products.includes(product.variation_id);
            const addClass = isAdded ? 'text-muted' : '';
            const addIcon = isAdded ? 'fa-check' : 'fa-plus';
            
            const item = `
                <div class="autocomplete-item ${addClass} ${index === current_autocomplete_index ? 'selected' : ''}" 
                    data-index="${index}"
                    data-product-id="${product.product_id}"
                    data-variation-id="${product.variation_id}"
                    data-product-variation-id="${product.product_variation_id}"
                    data-product-name="${product.name}"
                    data-sub-sku="${product.sub_sku || product.sku}"
                    data-current-stock="${product.qty_available}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${product.name}${variation_name}</strong>
                            <div class="text-muted small">${product.sub_sku || product.sku}</div>
                            <div class="text-muted small">Stock: ${product.formatted_qty_available || product.qty_available}</div>
                        </div>
                        <div>
                            <i class="fa ${addIcon} mr-1"></i>
                        </div>
                    </div>
                </div>
            `;
            resultsContainer.append(item);
        });
        
        resultsContainer.show();
    }

    // Handle search input
    $(document).on('input', '.search-input', function() {
        const searchTerm = $(this).val();
        const currentRow = $(this).closest('tr');
        current_row_index = currentRow.data('index');
        
        loadProducts(searchTerm, function(products) {
            showAutocompleteResults($(this), products);
        }.bind(this));
    });

    // Handle autocomplete item selection
    $(document).on('click', '.autocomplete-item:not(.text-muted)', function() {
        selectAutocompleteItem($(this));
    });

    function selectAutocompleteItem(item) {
        const product = {
            product_id: item.data('product-id'),
            variation_id: item.data('variation-id'),
            product_variation_id: item.data('product-variation-id'),
            name: item.data('product-name'),
            sku: item.data('sub-sku'),
            qty_available: item.data('current-stock')
        };
        
        const currentRow = $(`#items_table tbody tr[data-index="${current_row_index}"]`);
        populateProductRow(currentRow, product);
        
        // Hide autocomplete
        currentRow.find('.autocomplete-results').hide();
        current_autocomplete_index = -1;
    }

    // Populate product row with data - UPDATED to include product_variation_id
    function populateProductRow(row, product) {
        if (added_products.includes(product.variation_id)) {
            toastr.error('@lang("stocktake.already_added")');
            return;
        }

        added_products.push(product.variation_id);
        
        // Populate row data
        row.find('.search-input').val(product.name);
        row.find('.current-stock').text(product.qty_available);
        row.find('.counted-input').prop('disabled', false).val(product.qty_available);
        row.find('.remove-row').prop('disabled', false);
        
        // Add hidden fields for form submission - INCLUDING product_variation_id as required by controller
        row.append(`
            <input type="hidden" name="items[${current_row_index}][product_id]" value="${product.product_id}">
            <input type="hidden" name="items[${current_row_index}][variation_id]" value="${product.variation_id}">
            <input type="hidden" name="items[${current_row_index}][product_variation_id]" value="${product.product_variation_id}">
        `);
        
        // Add new blank row if this is the last row
        if (row.is(':last-child')) {
            addBlankRow();
        }
        
        // Focus on counted quantity and select the value
        const countedInput = row.find('.counted-input');
        countedInput.focus();
        countedInput.select();
        
        // Set names for input fields
        row.find('.lot-input').attr('name', `items[${current_row_index}][lot_number]`);
        row.find('.expiry-input').attr('name', `items[${current_row_index}][expiry_date]`);
        row.find('.counted-input').attr('name', `items[${current_row_index}][counted_quantity]`);
        
        // Recalculate variance
        recalculateVariance(row);
    }

    // Keyboard navigation in autocomplete
    $(document).on('keydown', '.search-input', function(e) {
        const resultsContainer = $(this).closest('.product-search').find('.autocomplete-results');
        const items = resultsContainer.find('.autocomplete-item:not(.text-muted)');
        const currentRow = $(this).closest('tr');
        current_row_index = currentRow.data('index');
        
        if (items.length === 0 || resultsContainer.css('display') === 'none') {
            return;
        }
        
        // Down arrow
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            current_autocomplete_index = (current_autocomplete_index + 1) % items.length;
            updateAutocompleteSelection(items);
            return;
        }
        
        // Up arrow
        if (e.key === 'ArrowUp') {
            e.preventDefault();
            current_autocomplete_index = (current_autocomplete_index - 1 + items.length) % items.length;
            updateAutocompleteSelection(items);
            return;
        }
        
        // Enter to select
        if (e.key === 'Enter') {
            e.preventDefault();
            if (current_autocomplete_index >= 0) {
                const selectedItem = items.eq(current_autocomplete_index);
                selectAutocompleteItem(selectedItem);
            }
            return;
        }
    });
    
    function updateAutocompleteSelection(items) {
        items.removeClass('selected');
        if (current_autocomplete_index >= 0) {
            items.eq(current_autocomplete_index).addClass('selected');
            
            // Scroll to selected item
            const selectedItem = items.eq(current_autocomplete_index);
            const container = selectedItem.closest('.autocomplete-results');
            const containerTop = container.offset().top;
            const containerHeight = container.height();
            const selectedTop = selectedItem.offset().top;
            const selectedHeight = selectedItem.height();
            
            if (selectedTop < containerTop) {
                container.scrollTop(container.scrollTop() - (containerTop - selectedTop));
            } else if (selectedTop + selectedHeight > containerTop + containerHeight) {
                container.scrollTop(container.scrollTop() + (selectedTop + selectedHeight - containerTop - containerHeight));
            }
        }
    }

    // Highlight counted quantity when focused
    $(document).on('focus', '.counted-input', function() {
        $(this).select();
    });

    // Handle keyboard navigation
    $(document).on('keydown', '.search-input, .lot-input, .expiry-input, .counted-input', function(e) {
        const row = $(this).closest('tr');
        const rowIndex = row.index();
        
        // Highlight current row
        $('tr').removeClass('table-active');
        row.addClass('table-active');
        
        // Tab key navigation
        if (e.key === 'Tab') {
            e.preventDefault();
            
            if ($(this).hasClass('search-input')) {
                row.find('.lot-input').focus();
            } else if ($(this).hasClass('lot-input')) {
                row.find('.expiry-input').focus();
            } else if ($(this).hasClass('expiry-input')) {
                row.find('.counted-input').focus();
            } else if ($(this).hasClass('counted-input')) {
                const nextRow = row.next('tr');
                if (nextRow.length) {
                    nextRow.find('.search-input').focus();
                } else {
                    addBlankRow();
                }
            }
        }
        
        // Enter key in counted quantity
        if (e.key === 'Enter' && $(this).hasClass('counted-input')) {
            e.preventDefault();
            const nextRow = row.next('tr');
            if (nextRow.length) {
                nextRow.find('.search-input').focus();
            } else {
                addBlankRow();
            }
        }
        
        // Arrow key navigation between rows
        if (e.key === 'ArrowDown' && !$(this).hasClass('search-input')) {
            e.preventDefault();
            const nextRow = row.next('tr');
            if (nextRow.length) {
                nextRow.find('.search-input').focus();
            }
        }
        
        if (e.key === 'ArrowUp' && !$(this).hasClass('search-input')) {
            e.preventDefault();
            const prevRow = row.prev('tr');
            if (prevRow.length) {
                prevRow.find('.search-input').focus();
            }
        }
    });

    // Handle row filtering
    $('#row_filter').on('input', function() {
        const filter = $(this).val().toLowerCase();
        
        $('#items_table tbody tr').each(function() {
            const productName = $(this).find('.search-input').val() || '';
            const sku = $(this).find('.current-stock').data('sku') || '';
            
            if (productName.toLowerCase().includes(filter) || sku.toLowerCase().includes(filter)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // Handle row removal
    $(document).on('click', '.remove-row', function() {
        const row = $(this).closest('tr');
        const variation_id = row.find('input[name*="[variation_id]"]').val();
        
        if (variation_id) {
            added_products = added_products.filter(id => id != variation_id);
        }
        
        // Remove hidden fields for form submission
        row.find('input[type="hidden"]').remove();
        
        row.remove();
        
        // If no rows left, add a blank row
        if ($('#items_table tbody tr').length === 0) {
            addBlankRow();
        }
    });

    // Recalculate variance function
    function recalculateVariance($row) {
        const currentStock = parseFloat($row.find('.current-stock').text()) || 0;
        const countedQty = parseFloat($row.find('.counted-input').val()) || 0;
        const variance = countedQty - currentStock;

        const $varianceCell = $row.find('.stock-variance');
        $varianceCell.text(variance.toFixed(4));

        if (variance > 0) {
            $varianceCell.css('color', 'green');
        } else if (variance < 0) {
            $varianceCell.css('color', 'red');
        } else {
            $varianceCell.css('color', 'black');
        }
    }

    // When counted quantity changes
    $(document).on('input', '.counted-input', function() {
        const $row = $(this).closest('tr');
        recalculateVariance($row);
    });

    // Validate form before submitting - UPDATED to match controller validation
    $('#stocktake_form').on('submit', function(e) {
        let hasProducts = false;
        let validQuantities = true;
        let productCount = 0;
        
        // Debug: log all form data
        const formData = new FormData(this);
        console.log('Form data:', Object.fromEntries(formData));
        
        $('#items_table tbody tr').each(function() {
            const productName = $(this).find('.search-input').val();
            const countedQty = $(this).find('.counted-input').val();
            const productId = $(this).find('input[name*="[product_id]"]').val();
            const variationId = $(this).find('input[name*="[variation_id]"]').val();
            const productVariationId = $(this).find('input[name*="[product_variation_id]"]').val();
            
            console.log('Row data:', { productId, variationId, productVariationId, productName, countedQty });
            
            // Check if row has a product
            if (productName && productName.trim() !== '') {
                hasProducts = true;
                productCount++;
                
                // Validate all required fields are present - UPDATED to include product_variation_id
                if (!productId || !variationId || !productVariationId) {
                    toastr.error('@lang("stocktake.missing_product_data")');
                    validQuantities = false;
                    return false;
                }
                
                // Validate counted quantity
                if (countedQty === '' || parseFloat(countedQty) < 0) {
                    validQuantities = false;
                    $(this).find('.counted-input').addClass('is-invalid');
                } else {
                    $(this).find('.counted-input').removeClass('is-invalid');
                }
            }
        });
        
        console.log('Validation results:', { hasProducts, validQuantities, productCount });
        
        // Check for at least one product
        if (!hasProducts) {
            e.preventDefault();
            toastr.error('@lang("stocktake.at_least_one_product_required")');
            return false;
        }
        
        // Check all quantities are valid
        if (!validQuantities) {
            e.preventDefault();
            toastr.error('@lang("stocktake.invalid_quantities")');
            return false;
        }

        // Show loading state
        $('#submit_btn').prop('disabled', true)
            .html('<i class="fa fa-spinner fa-spin"></i> @lang("stocktake.saving")...');
        
        // Form will submit normally
    });
    
    // Prevent form submission on Enter key in inputs
    $(document).on('keypress', 'input', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            return false;
        }
    });

    // Close autocomplete when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.product-search').length) {
            $('.autocomplete-results').hide();
        }
    });

    // Handle location change - clear products if location changes
    $('#location_id').on('change', function() {
        const newLocationId = $(this).val();
        if (newLocationId) {
            // Clear all existing products when location changes
            $('#items_table tbody tr').each(function() {
                const variation_id = $(this).find('input[name*="[variation_id]"]').val();
                if (variation_id) {
                    added_products = added_products.filter(id => id != variation_id);
                }
                
                // Reset the row
                $(this).find('.search-input').val('');
                $(this).find('.lot-input').val('');
                $(this).find('.expiry-input').val('');
                $(this).find('.current-stock').text('-');
                $(this).find('.counted-input').val('').prop('disabled', true);
                $(this).find('.stock-variance').text('-');
                $(this).find('.remove-row').prop('disabled', true);
                $(this).find('input[type="hidden"]').remove();
            });
            
            // Keep only one blank row
            const rows = $('#items_table tbody tr');
            if (rows.length > 1) {
                rows.slice(1).remove();
            }
            
            row_index = 1;
            current_row_index = 0;
            
            toastr.info('@lang("stocktake.location_changed_cleared_products")');
        }
    });
});
</script>

<style>
    .autocomplete-results {
        position: absolute;
        background: white;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        z-index: 1000;
        max-height: 200px;
        overflow-y: auto;
        width: 100%;
        display: none;
    }
    
    .autocomplete-item {
        padding: 8px 12px;
        cursor: pointer;
        border-bottom: 1px solid #eee;
    }
    
    .autocomplete-item:hover, .autocomplete-item.selected {
        background-color: #f0f8ff;
    }
    
    .autocomplete-item.text-muted {
        color: #6c757d;
        cursor: not-allowed;
    }
    
    .table-active {
        background-color: rgba(13, 110, 253, 0.1) !important;
    }
    
    .keyboard-hint {
        font-size: 0.9rem;
    }
    
    .product-search {
        position: relative;
    }
    
    .lot-input, .expiry-input {
        width: 100%;
    }
    
    .counted-input:disabled {
        background-color: #e9ecef;
    }
    
    .is-invalid {
        border-color: #dc3545 !important;
    }
    
    /* Scrollbar styling */
    .autocomplete-results::-webkit-scrollbar {
        width: 8px;
    }
    
    .autocomplete-results::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    
    .autocomplete-results::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }
    
    .autocomplete-results::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
    
    /* Datepicker styling */
    .ui-datepicker {
        background: white;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .ui-datepicker-header {
        background: #4361ee;
        color: white;
        border: none;
        border-radius: 2px;
    }
    
    .ui-datepicker-prev, .ui-datepicker-next {
        cursor: pointer;
    }
    
    .ui-datepicker-calendar .ui-state-default {
        border: none;
        background: #f8f9fa;
    }
    
    .ui-datepicker-calendar .ui-state-active {
        background: #4361ee;
        color: white;
    }
    
    /* Highlight counted input when focused */
    .counted-input:focus {
        border-color: #4361ee;
        box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
    }
</style>
@endsection