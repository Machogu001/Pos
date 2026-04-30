@extends('layouts.app')

@section('title', __('stocktake.add_stocktake'))

@section('content')
<div class="tw-max-w-full tw-px-4 tw-py-4">

    {{-- Validation Errors --}}
    @include('stocktake.partials.validation_errors')
    {{-- Status Messages --}}
    @include('stocktake.partials.status_message')

    {!! Form::open(['route' => 'stocktakes.store', 'method' => 'post', 'id' => 'stocktake_form']) !!}

    {{-- Page Header Card --}}
    <div class="tw-bg-gradient-to-r tw-from-blue-700 tw-to-indigo-700 tw-rounded-2xl tw-shadow-lg tw-mb-5 tw-px-6 tw-py-5 tw-flex tw-items-center tw-justify-between">
        <div class="tw-flex tw-items-center tw-gap-3">
            <div class="tw-bg-white/20 tw-rounded-xl tw-p-2.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="tw-size-6 tw-text-white" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2"/>
                    <path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z"/>
                    <path d="M9 12l2 2l4 -4"/>
                </svg>
            </div>
            <div>
                <h1 class="tw-text-xl tw-font-bold tw-text-white tw-leading-tight">@lang('stocktake.add_stocktake')</h1>
                <p class="tw-text-blue-200 tw-text-sm tw-mt-0.5">Record physical stock counts and identify variances</p>
            </div>
        </div>
        <a href="{{ route('stocktakes.index') }}" class="tw-inline-flex tw-items-center tw-gap-2 tw-bg-white/15 hover:tw-bg-white/25 tw-text-white tw-text-sm tw-font-medium tw-px-4 tw-py-2 tw-rounded-lg tw-transition-all tw-duration-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="tw-size-4" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6"/></svg>
            Back to list
        </a>
    </div>

    {{-- Setup Row: Location + Notes --}}
    <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-3 tw-gap-4 tw-mb-5">

        {{-- Business Location --}}
        <div class="tw-bg-white tw-rounded-2xl tw-shadow-sm tw-border tw-border-gray-100 tw-p-5">
            <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="tw-size-4 tw-text-blue-600" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 21l18 0"/><path d="M5 21v-16a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v16"/><path d="M9 21v-4a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v4"/></svg>
                @lang('business.business_location') <span class="tw-text-red-500">*</span>
            </label>
            {!! Form::select('location_id', $locations, $default_location, [
                'class' => 'form-control select2',
                'required',
                'style' => 'width: 100%;',
                'id' => 'location_id'
            ]) !!}
        </div>

        {{-- Additional Notes --}}
        <div class="tw-bg-white tw-rounded-2xl tw-shadow-sm tw-border tw-border-gray-100 tw-p-5 md:tw-col-span-2">
            <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="tw-size-4 tw-text-indigo-500" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M13 20l7 -7"/><path d="M13 20v-6h-6"/><path d="M4.012 16.737a2.005 2.005 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c.75 0 1.158 .385 1.5 1"/></svg>
                @lang('stocktake.additional_notes')
            </label>
            {!! Form::textarea('additional_notes', old('additional_notes'), [
                'class' => 'form-control',
                'rows' => 2,
                'placeholder' => __('stocktake.notes_placeholder')
            ]) !!}
        </div>
    </div>

    {{-- Products Table Card --}}
    <div class="tw-bg-white tw-rounded-2xl tw-shadow-sm tw-border tw-border-gray-100 tw-mb-5">

        {{-- Card Header --}}
        <div class="tw-flex tw-items-center tw-justify-between tw-px-6 tw-py-4 tw-border-b tw-border-gray-100">
            <div class="tw-flex tw-items-center tw-gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="tw-size-5 tw-text-indigo-600" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3l8 4.5l0 9l-8 4.5l-8 -4.5l0 -9l8 -4.5"/><path d="M12 12l8 -4.5"/><path d="M12 12l0 9"/><path d="M12 12l-8 -4.5"/></svg>
                <h2 class="tw-font-semibold tw-text-gray-800">Products</h2>
                <span id="product_count_badge" class="tw-bg-indigo-100 tw-text-indigo-700 tw-text-xs tw-font-bold tw-px-2 tw-py-0.5 tw-rounded-full">0 added</span>
            </div>
            {{-- Filter --}}
            <div class="tw-relative tw-w-64">
                <svg xmlns="http://www.w3.org/2000/svg" class="tw-absolute tw-left-3 tw-top-1/2 -tw-translate-y-1/2 tw-size-4 tw-text-gray-400" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"/><path d="M21 21l-6 -6"/></svg>
                <input type="text" id="row_filter" class="tw-w-full tw-pl-9 tw-pr-4 tw-py-2 tw-text-sm tw-border tw-border-gray-200 tw-rounded-lg focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-indigo-300" placeholder="@lang('stocktake.filter_added_products')">
            </div>
        </div>

        {{-- Table --}}
        <div class="tw-overflow-x-auto">
            <table class="tw-w-full tw-text-sm" id="items_table">
                <thead>
                    <tr class="tw-bg-gray-50 tw-text-xs tw-font-semibold tw-text-gray-500 tw-uppercase tw-tracking-wide tw-sticky tw-top-0 tw-z-10">
                        <th class="tw-px-4 tw-py-3 tw-text-left" style="width:24%">@lang('stocktake.product_name')</th>
                        <th class="tw-px-4 tw-py-3 tw-text-left" style="width:11%">@lang('stocktake.product_lot_no')</th>
                        <th class="tw-px-4 tw-py-3 tw-text-left" style="width:11%">@lang('stocktake.product_expiry_date')</th>
                        <th class="tw-px-4 tw-py-3 tw-text-center" style="width:11%">@lang('stocktake.current_stock_report')</th>
                        <th class="tw-px-4 tw-py-3 tw-text-center" style="width:13%">@lang('stocktake.stocktake_counted_qty') <span class="tw-text-red-400">*</span></th>
                        <th class="tw-px-4 tw-py-3 tw-text-center" style="width:12%">@lang('stocktake.stocktake_variance')</th>
                        <th class="tw-px-4 tw-py-3 tw-text-center" style="width:8%">@lang('stocktake.action')</th>
                    </tr>
                </thead>
                <tbody class="tw-divide-y tw-divide-gray-100">
                    {{-- Initial blank row --}}
                    <tr class="editable-row tw-hover:bg-blue-50 tw-transition-colors" data-index="0">
                        <td class="product-search tw-px-3 tw-py-2">
                            <input type="text" class="form-control search-input tw-text-sm"
                                   placeholder="@lang('stocktake.search_product')" autocomplete="off">
                            <div class="autocomplete-results" style="display: none;"></div>
                        </td>
                        <td class="lot-number tw-px-3 tw-py-2">
                            <input type="text" class="form-control lot-input tw-text-sm"
                                   placeholder="@lang('stocktake.optional')">
                        </td>
                        <td class="expiry-date tw-px-3 tw-py-2">
                            <input type="text" class="form-control expiry-input datepicker tw-text-sm"
                                   placeholder="@lang('stocktake.optional')">
                        </td>
                        <td class="current-stock tw-px-3 tw-py-2 tw-text-center tw-font-mono tw-text-gray-500">-</td>
                        <td class="counted-qty tw-px-3 tw-py-2">
                            <input type="number" class="form-control counted-input tw-text-sm tw-text-center"
                                   placeholder="0.00" min="0" step="0.0001" disabled>
                        </td>
                        <td class="stock-variance tw-px-3 tw-py-2 tw-text-center tw-font-semibold tw-font-mono">-</td>
                        <td class="action tw-px-3 tw-py-2 tw-text-center">
                            <button type="button" class="btn btn-sm remove-row tw-bg-red-50 tw-text-red-500 hover:tw-bg-red-100 tw-border-0 tw-rounded-lg tw-px-2.5 tw-py-1" disabled>
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Card Footer --}}
        <div class="tw-px-6 tw-py-3 tw-border-t tw-border-gray-100 tw-bg-gray-50 tw-rounded-b-2xl">
            <button type="button" id="add_blank_row"
                class="tw-inline-flex tw-items-center tw-gap-2 tw-text-sm tw-font-medium tw-text-indigo-600 hover:tw-text-indigo-800 tw-bg-indigo-50 hover:tw-bg-indigo-100 tw-px-4 tw-py-2 tw-rounded-lg tw-transition-all tw-duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="tw-size-4" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14"/><path d="M5 12l14 0"/></svg>
                @lang('stocktake.add_blank_row')
            </button>
        </div>
    </div>

    {{-- Bottom Bar: Shortcuts + Save --}}
    <div class="tw-flex tw-flex-col md:tw-flex-row tw-items-start md:tw-items-center tw-justify-between tw-gap-4 tw-bg-white tw-rounded-2xl tw-shadow-sm tw-border tw-border-gray-100 tw-px-6 tw-py-4">

        {{-- Keyboard Shortcuts --}}
        <div class="tw-flex tw-flex-col tw-gap-1">
            <p class="tw-text-xs tw-font-semibold tw-text-gray-500 tw-uppercase tw-tracking-wide tw-mb-1">@lang('stocktake.keyboard_shortcuts')</p>
            <div class="tw-flex tw-flex-wrap tw-items-center tw-gap-x-4 tw-gap-y-1 tw-text-sm tw-text-gray-600">
                <span><kbd class="tw-bg-gray-100 tw-text-gray-700 tw-border tw-border-gray-300 tw-rounded tw-px-1.5 tw-py-0.5 tw-font-mono tw-text-xs">Tab</kbd> @lang('stocktake.navigate_between_fields')</span>
                <span><kbd class="tw-bg-gray-100 tw-text-gray-700 tw-border tw-border-gray-300 tw-rounded tw-px-1.5 tw-py-0.5 tw-font-mono tw-text-xs">Enter</kbd> @lang('stocktake.confirm_counted_quantity_next_row')</span>
                <span><kbd class="tw-bg-gray-100 tw-text-gray-700 tw-border tw-border-gray-300 tw-rounded tw-px-1.5 tw-py-0.5 tw-font-mono tw-text-xs">↑ ↓</kbd> @lang('stocktake.navigate_rows_search_results')</span>
            </div>
            <p class="tw-text-xs tw-text-gray-400 tw-mt-1">@lang('stocktake.note_mouse_delete_rows')</p>
        </div>

        {{-- Submit --}}
        <button type="submit" id="submit_btn"
            class="tw-inline-flex tw-items-center tw-gap-2 tw-bg-gradient-to-r tw-from-blue-600 tw-to-indigo-600 hover:tw-from-blue-700 hover:tw-to-indigo-700 tw-text-white tw-font-semibold tw-px-8 tw-py-3 tw-rounded-xl tw-shadow-md tw-shadow-blue-200 tw-transition-all tw-duration-200 tw-text-base tw-whitespace-nowrap">
            <svg xmlns="http://www.w3.org/2000/svg" class="tw-size-5" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2"/><path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M14 4l0 4l-6 0l0 -4"/></svg>
            @lang('stocktake.save')
        </button>
    </div>

    {!! Form::close() !!}
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
            <tr class="editable-row tw-transition-colors" data-index="${row_index}">
                <td class="product-search tw-px-3 tw-py-2">
                    <input type="text" class="form-control search-input tw-text-sm" 
                           placeholder="@lang('stocktake.search_product')" autocomplete="off">
                    <div class="autocomplete-results"></div>
                </td>
                <td class="lot-number tw-px-3 tw-py-2">
                    <input type="text" class="form-control lot-input tw-text-sm" 
                           placeholder="@lang('stocktake.optional')">
                </td>
                <td class="expiry-date tw-px-3 tw-py-2">
                    <input type="text" class="form-control expiry-input tw-text-sm" 
                           placeholder="@lang('stocktake.optional')">
                </td>
                <td class="current-stock tw-px-3 tw-py-2 tw-text-center tw-font-mono tw-text-gray-500">-</td>
                <td class="counted-qty tw-px-3 tw-py-2">
                    <input type="number" class="form-control counted-input tw-text-sm tw-text-center" 
                           placeholder="0.00" min="0" step="0.0001" disabled>
                </td>
                <td class="stock-variance tw-px-3 tw-py-2 tw-text-center tw-font-semibold tw-font-mono">-</td>
                <td class="action tw-px-3 tw-py-2 tw-text-center">
                    <button type="button" class="btn btn-sm remove-row tw-bg-red-50 tw-text-red-500 hover:tw-bg-red-100 tw-border-0 tw-rounded-lg tw-px-2.5 tw-py-1" disabled>
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

        // Position the fixed dropdown below the input
        const rect = input[0].getBoundingClientRect();
        resultsContainer.css({
            top:   rect.bottom + 4 + 'px',
            left:  rect.left + 'px',
            width: rect.width + 'px'
        });
        
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
        const $input = $(this);
        
        loadProducts(searchTerm, function(products) {
            showAutocompleteResults($input, products);
        });
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
        $varianceCell.removeClass('variance-positive variance-negative variance-zero');

        if (variance > 0) {
            $varianceCell.addClass('variance-positive');
        } else if (variance < 0) {
            $varianceCell.addClass('variance-negative');
        } else {
            $varianceCell.addClass('variance-zero');
        }

        // Update product count badge
        $('#product_count_badge').text(added_products.length + ' added');
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
    /* ── Autocomplete dropdown ───────────────────────────── */
    .product-search { position: relative; }
    .autocomplete-results {
        position: fixed;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        box-shadow: 0 8px 24px rgba(0,0,0,.12);
        z-index: 9999;
        max-height: 220px;
        overflow-y: auto;
        min-width: 260px;
        display: none;
    }
    .autocomplete-item {
        padding: 9px 14px;
        cursor: pointer;
        border-bottom: 1px solid #f1f5f9;
        transition: background .12s;
    }
    .autocomplete-item:last-child { border-bottom: none; }
    .autocomplete-item:hover,
    .autocomplete-item.selected { background: #eef2ff; }
    .autocomplete-item.text-muted { color: #94a3b8; cursor: not-allowed; }
    .autocomplete-results::-webkit-scrollbar { width: 6px; }
    .autocomplete-results::-webkit-scrollbar-track { background: #f8fafc; border-radius: 3px; }
    .autocomplete-results::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }

    /* ── Table row states ────────────────────────────────── */
    #items_table tbody tr { transition: background .15s; }
    #items_table tbody tr:hover { background: #f8faff; }
    .table-active { background: #eff6ff !important; }
    .table-active td { box-shadow: inset 0 0 0 1px #bfdbfe; }

    /* ── Inputs inside table ─────────────────────────────── */
    #items_table .form-control {
        border-radius: 8px;
        border-color: #e2e8f0;
        font-size: .8rem;
        padding: .3rem .6rem;
        transition: border-color .2s, box-shadow .2s;
    }
    #items_table .form-control:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99,102,241,.15);
        outline: none;
    }
    .counted-input:disabled { background: #f1f5f9; color: #94a3b8; }
    .is-invalid { border-color: #ef4444 !important; }
    .lot-input, .expiry-input { width: 100%; }

    /* ── Datepicker ──────────────────────────────────────── */
    .ui-datepicker {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px;
        box-shadow: 0 8px 24px rgba(0,0,0,.12);
    }
    .ui-datepicker-header {
        background: #4f46e5;
        color: #fff;
        border: none;
        border-radius: 6px 6px 0 0;
    }
    .ui-datepicker-prev, .ui-datepicker-next { cursor: pointer; }
    .ui-datepicker-calendar .ui-state-default {
        border: none;
        background: #f8fafc;
        border-radius: 4px;
    }
    .ui-datepicker-calendar .ui-state-active {
        background: #4f46e5;
        color: #fff;
    }

    /* ── Variance colours ────────────────────────────────── */
    .variance-positive { color: #16a34a; }
    .variance-negative { color: #dc2626; }
    .variance-zero     { color: #64748b; }
</style>
@endsection