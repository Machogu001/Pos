@php
    $statusColors = [
        'in_progress' => 'warning',
        'completed' => 'success',
        'cancelled' => 'danger',
        'pending' => 'info',
        'draft' => 'secondary'
    ];

    $statusIcons = [
        'in_progress' => 'fas fa-spinner fa-spin',
        'completed' => 'fas fa-check-circle',
        'cancelled' => 'fas fa-times-circle',
        'pending' => 'fas fa-hourglass-half',
        'draft' => 'fas fa-edit'
    ];

    $statusText = [
        'in_progress' => __('stocktake.in_progress'),
        'completed' => __('stocktake.completed'),
        'cancelled' => __('stocktake.cancelled'),
        'pending' => __('stocktake.pending'),
        'draft' => __('stocktake.draft')
    ];

    $color = $statusColors[$status] ?? 'secondary';
@endphp

<span class="badge bg-{{ $color }} d-inline-flex align-items-center gap-1">
    <i class="{{ $statusIcons[$status] ?? 'fas fa-question-circle' }}"></i>
    {{ $statusText[$status] ?? ucfirst($status) }}
</span>
