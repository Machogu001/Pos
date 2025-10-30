@extends('layouts.app')

@section('title', __('stocktake.stocktake') . ' - ' . $stocktake->reference_no)

@section('content')
<div class="modal-dialog modal-xl" role="document">
	<div class="modal-content">
		<div class="modal-header">
		   <style>
    .empty-state {
        padding: 3rem;
        text-align: center;
        background-color: #f8f9fa;
        border-radius: 4px;
        width: 100%;
    }
    .empty-state-icon {
        font-size: 3rem;
        color: #adb5bd;
        margin-bottom: 1rem;
    }
    .empty-state h4 {
        margin-bottom: 0.5rem;
        color: #343a40;
    }
    .empty-state-subtext {
        color: #6c757d;
        margin-bottom: 1.5rem;
    }
    .variance-badge {
        font-size: 0.9em;
        min-width: 50px;
        display: inline-block;
    }
    .busy-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 9999;
        display: flex;
        justify-content: center;
        align-items: center;
        color: white;
        font-size: 1.5rem;
    }
</style>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-light">
                <h3 class="card-title">
                    <i class="fas fa-clipboard-list"></i> 
                    @lang('stocktake.stocktake') - {{ $stocktake->reference_no }}
                </h3>
                @if($stocktake->status == 'in_progress')
                <div class="card-tools">
                    <button type="button" class="btn btn-success" id="complete_stocktake">
                        <i class="fas fa-check-circle"></i> @lang('stocktake.complete_stocktake')
                    </button>
                </div>
                @endif
            </div>
            
            <div class="card-body">
                <!-- Summary Section -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <p><strong><i class="fas fa-store"></i> @lang('business.business_location'):</strong> 
                        {{ $stocktake->location->name }}</p>
                    </div>
                    <div class="col-md-4">
                        <p><strong><i class="far fa-clock"></i> @lang('stocktake.started_at'):</strong> 
                         {{ \Carbon\Carbon::parse($stocktake->transaction_date)->format(session('business.date_format', 'm/d/Y') . ' H:i') }}
                        </p>
                    </div>
                    <div class="col-md-4">
                        <p><strong><i class="fas fa-info-circle"></i> @lang('stocktake.status'):</strong> 
    @php
        $statusColors = [
            'completed' => 'success',
            'in_progress' => 'warning',
            'pending' => 'info',
            'cancelled' => 'danger'
        ];
        $color = $statusColors[$stocktake->status] ?? 'secondary';
    @endphp
    <span class="badge badge-{{ $color }}">
        @if($stocktake->status == 'in_progress')
            @lang('stocktake.in_progress')
        @elseif($stocktake->status == 'completed')
            @lang('stocktake.completed')
        @elseif($stocktake->status == 'pending')
            @lang('stocktake.pending')
        @elseif($stocktake->status == 'cancelled')
            @lang('stocktake.cancelled')
        @else
            {{ ucfirst(str_replace('_', ' ', $stocktake->status)) }}
        @endif
    </span>
</p>
                    </div>
                </div>

                @if($stocktake->additional_notes)
                <div class="row mb-4">
                    <div class="col-md-12">
                        <p><strong><i class="fas fa-sticky-note"></i> @lang('stocktake.notes'):</strong> 
                        {{ $stocktake->additional_notes }}</p>
                    </div>
                </div>
                @endif

                <!-- Stocktake Form -->
                <form id="stocktake_form" action="{{ route('stocktakes.update', $stocktake->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="stocktake_items_table">
                            <thead class="thead-dark">
                                <tr>
                                    <th width="30%">@lang('stocktake.product_name')</th>
                                    <th width="10%">@lang('product.sku')</th>
                                    <th width="15%">@lang('stocktake.system_quantity')</th>
                                    <th width="15%">@lang('stocktake.counted_quantity')</th>
                                    <th width="15%">@lang('stocktake.variance')</th>
                                    <th width="15%">@lang('stocktake.notes')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stocktake->items as $item)
                                <tr>
                                    <td>
                                        <strong>{{ $item->product->name }}</strong>
                                        @if($item->variation->name != 'DUMMY')
                                            <br><small class="text-muted">{{ $item->variation->name }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $item->variation->sub_sku }}</td>
                                    <td class="text-center">{{ $item->system_quantity }}</td>
                                    <td>
                                        <input type="number" class="form-control counted_quantity" 
                                            name="items[{{ $item->id }}][counted_quantity]" 
                                            value="{{ $item->counted_quantity ?? $item->system_quantity }}" 
                                            min="0" step="any" required>
                                        <input type="hidden" name="items[{{ $item->id }}][id]" value="{{ $item->id }}">
                                    </td>
                                    <td class="variance text-center">
                                        <span class="badge variance-badge variance-value">
                                            {{ ($item->counted_quantity ?? $item->system_quantity) - $item->system_quantity }}
                                        </span>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" 
                                            name="items[{{ $item->id }}][notes]" 
                                            value="{{ $item->notes }}" 
                                            placeholder="@lang('stocktake.add_notes')">
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <div class="empty-state-icon">
                                                <i class="fas fa-box-open"></i>
                                            </div>
                                            <h4>@lang('stocktake.no_products_in_stocktake')</h4>
                                            <p class="empty-state-subtext">
                                                @lang('stocktake.add_products_in_create_page')
                                            </p>
                                            <!-- Add a back button with the correct route -->
                                            <a href="{{ route('stocktakes.index') }}" class="btn btn-primary mt-3">
                                                <i class="fas fa-arrow-left"></i> @lang('stocktake.back')
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if(count($stocktake->items) > 0)
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="additional_notes">@lang('stocktake.additional_notes')</label>
                                <textarea class="form-control" id="additional_notes" 
                                    name="additional_notes" rows="3">{{ $stocktake->additional_notes }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-2">
                        <div class="col-md-12 text-center">
                            <button type="submit" class="btn btn-primary px-5" id="save_stocktake">
                                <i class="fas fa-save"></i> @lang('stocktake.save')
                            </button>
                            <!-- Add a back button with the correct route -->
                            <a href="{{ route('stocktakes.index') }}" class="btn btn-default px-5 ml-2">
                                <i class="fas fa-arrow-left"></i> @lang('stocktake.back')
                            </a>
                        </div>
                    </div>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>

<div id="busy-overlay" class="busy-overlay" style="display: none;">
    <div class="text-center">
        <i class="fas fa-spinner fa-spin fa-3x"></i>
        <p class="mt-3">@lang('stocktake.server_busy')</p>
    </div>
</div>
@endsection

@section('javascript')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Initialize variance calculation on page load
    $('.counted_quantity').each(function() {
        calculateVariance($(this));
    });

    // Calculate variance on quantity change
    $(document).on('input', '.counted_quantity', function() {
        calculateVariance($(this));
    });

    function calculateVariance(inputElement) {
        var row = inputElement.closest('tr');
        var system_qty = parseFloat(row.find('td:eq(2)').text()) || 0;
        var counted_qty = parseFloat(inputElement.val()) || 0;
        var variance = counted_qty - system_qty;
        
        var varianceElement = row.find('.variance-value');
        varianceElement.text(variance.toFixed(2));
        
        // Update styling based on variance
        varianceElement.removeClass('badge-danger badge-success badge-warning');
        if (variance < 0) {
            varianceElement.addClass('badge-danger');
        } else if (variance > 0) {
            varianceElement.addClass('badge-success');
        } else {
            varianceElement.addClass('badge-warning');
        }
    }

    // Form submission with loading state
    $('#stocktake_form').on('submit', function(e) {
        e.preventDefault();
        var btn = $(this).find('#save_stocktake');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> @lang('stocktake.saving')');
        
        $.ajax({
            method: 'POST',
            url: $(this).attr('action'),
            data: $(this).serialize(),
            success: function(response) {
                btn.prop('disabled', false).html('<i class="fas fa-save"></i> @lang('stocktake.save')');
                if (response.success) {
                    toastr.success(response.msg);
                } else {
                    toastr.error(response.msg);
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fas fa-save"></i> @lang('stocktake.save')');
                toastr.error(xhr.responseJSON.message || '@lang('stocktake.something_went_wrong')');
            }
        });
    });

    // Complete stocktake button - guaranteed working version
    $(document).on('click', '#complete_stocktake', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: '@lang('stocktake.confirm_complete_title')',
            text: '@lang('stocktake.confirm_complete_message')',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '@lang('stocktake.yes')',
            cancelButtonText: '@lang('stocktake.no')'
        }).then((result) => {
            if (result.isConfirmed) {
                var btn = $('#complete_stocktake');
                $('#busy-overlay').show();
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> @lang('stocktake.processing')');
                
                $.ajax({
                    type: 'POST',
                    url: "{{ route('stocktakes.complete', $stocktake->id) }}",
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        $('#busy-overlay').hide();
                        if (response.success) {
                            toastr.success(response.msg);
                            setTimeout(function() {
                                window.location.href = response.redirect || "{{ route('stocktakes.index') }}";
                            }, 1500);
                        } else {
                            btn.prop('disabled', false).html('<i class="fas fa-check-circle"></i> @lang('stocktake.complete_stocktake')');
                            toastr.error(response.msg);
                        }
                    },
                    error: function(xhr) {
                        $('#busy-overlay').hide();
                        btn.prop('disabled', false).html('<i class="fas fa-check-circle"></i> @lang('stocktake.complete_stocktake')');
                        var errorMsg = xhr.responseJSON && xhr.responseJSON.message 
                            ? xhr.responseJSON.message 
                            : '@lang('stocktake.something_went_wrong')';
                        toastr.error(errorMsg);
                    }
                });
            }
        });
    });
});
</script>
@endsection