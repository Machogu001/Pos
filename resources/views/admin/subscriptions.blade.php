@extends('layouts.app')

@section('title', __('payment.subscription_management'))

@section('content')
<!-- Header Section -->
<div class="bg-gradient-primary text-white py-4 mb-4">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="h3 mb-2 fw-bold">
                    <i class="fas fa-users-cog me-3"></i>{{ __('payment.subscription_management') }}
                </h1>
                <p class="mb-0 opacity-75">Monitor and manage all user subscriptions and payments</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-light btn-lg shadow-sm">
                    <i class="fas fa-arrow-left me-2"></i>{{ __('payment.back_to_dashboard') }}
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid px-4">

    <!-- Success/Error Messages -->
    <div id="ajaxAlerts"></div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Summary Cards -->
    <div class="row g-4 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 bg-success text-white">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-1 text-white-50">Active Subscriptions</h6>
                            <h2 class="mb-0 fw-bold">{{ $subscriptions->where('status', 'active')->count() }}</h2>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 bg-warning text-dark">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-1 text-dark-50">Pending Payments</h6>
                            <h2 class="mb-0 fw-bold">{{ $subscriptions->where('status', 'pending')->count() }}</h2>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-clock fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 bg-secondary text-white">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-1 text-white-50">Expired</h6>
                            <h2 class="mb-0 fw-bold">{{ $subscriptions->where('status', 'expired')->count() }}</h2>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-calendar-times fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 bg-info text-white">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-1 text-white-50">Monthly Revenue</h6>
                            <h2 class="mb-0 fw-bold">Ksh {{ number_format($subscriptions->where('status', 'active')->sum('amount'), 0) }}</h2>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-dollar-sign fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-header bg-white py-4 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">{{ __('payment.all_payments') }}</h5>
                    <p class="text-muted mb-0">{{ __('payment.showing_x_of_y', ['count' => $subscriptions->count(), 'total' => $subscriptions->total() ?? $subscriptions->count()]) }}</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#bulkActionModal">
                        <i class="fas fa-tasks me-1"></i>Bulk Actions
                    </button>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createManualModal">
                        <i class="fas fa-plus me-1"></i>Create Manual
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <!-- Filters -->
            <form id="filterForm" method="GET" action="{{ route('admin.subscriptions') }}">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label">{{ __('payment.status_filter') }}</label>
                        <select class="form-select" name="status" id="statusFilter">
                            <option value="">{{ __('payment.all_statuses') }}</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>{{ __('payment.active') }}</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>{{ __('payment.pending') }}</option>
                            <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>{{ __('payment.expired') }}</option>
                            <option value="canceled" {{ request('status') == 'canceled' ? 'selected' : '' }}>{{ __('payment.canceled') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('payment.billing_cycle_filter') }}</label>
                        <select class="form-select" name="billing_cycle" id="billingCycleFilter">
                            <option value="">{{ __('payment.all_cycles') }}</option>
                            <option value="monthly" {{ request('billing_cycle') == 'monthly' ? 'selected' : '' }}>{{ __('payment.monthly') }}</option>
                            <option value="quarterly" {{ request('billing_cycle') == 'quarterly' ? 'selected' : '' }}>{{ __('payment.quarterly') }}</option>
                            <option value="yearly" {{ request('billing_cycle') == 'yearly' ? 'selected' : '' }}>{{ __('payment.yearly') }}</option>
                        </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-secondary me-2" id="resetFilters">
                            <i class="fas fa-refresh me-1"></i> {{ __('payment.reset_filters') }}
                        </button>
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="{{ __('payment.search_placeholder') }}" 
                                   id="searchInput" value="{{ request('search') }}">
                            <button class="btn btn-outline-primary" type="submit" id="searchButton">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('payment.id') }}</th>
                            <th>{{ __('payment.user') }}</th>
                            <th>{{ __('payment.business') }}</th>
                            <th>{{ __('payment.plan') }}</th>
                            <th>{{ __('payment.billing_cycle') }}</th>
                            <th>{{ __('payment.amount') }}</th>
                            <th>{{ __('payment.start_date') }}</th>
                            <th>{{ __('payment.end_date') }}</th>
                            <th>{{ __('payment.status') }}</th>
                            <th>{{ __('payment.mpesa_receipt') }}</th>
                            <th>{{ __('payment.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subscriptions ?? [] as $subscription)
                        <tr class="subscription-row" data-status="{{ $subscription->status }}" data-billing-cycle="{{ $subscription->billing_cycle }}">
                            <td>{{ $subscription->id }}</td>
                            <td>{{ $subscription->user->username ?? ($subscription->user->name ?? 'N/A') }}</td>
                            <td>{{ $subscription->user->business->name ?? 'N/A' }}</td>
                            <td>{{ $subscription->plan_name }}</td>
                            <td>
                                <span class="badge bg-info text-capitalize">{{ $subscription->billing_cycle }}</span>
                            </td>
                            <td>Ksh {{ number_format($subscription->amount, 2) }}</td>
                            <td>{{ optional($subscription->start_date)->format('M d, Y') }}</td>
                            <td>{{ optional($subscription->end_date)->format('M d, Y') }}</td>
                            <td>
                                <span class="badge subscription-status {{ $subscription->status }} text-capitalize">
                                    {{ $subscription->status }}
                                </span>
                            </td>
                            <td>{{ $subscription->mpesa_receipt ?? 'N/A' }}</td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @if($subscription->status === 'active' || $subscription->status === 'expired')
                                        <form action="{{ route('admin.subscriptions.renew', $subscription) }}" method="POST" class="ajax-form d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <span class="btn-text">
                                                    <i class="fas fa-sync-alt me-1"></i> {{ __('payment.renew') }}
                                                </span>
                                                <span class="btn-loading d-none">
                                                    <span class="spinner-border spinner-border-sm" role="status"></span>
                                                </span>
                                            </button>
                                        </form>
                                    @endif

                                    @if($subscription->status === 'pending')
                                        <form action="{{ route('admin.subscriptions.activate', $subscription) }}" method="POST" class="ajax-form d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-primary">
                                                <span class="btn-text">
                                                    <i class="fas fa-check me-1"></i> {{ __('payment.activate') }}
                                                </span>
                                                <span class="btn-loading d-none">
                                                    <span class="spinner-border spinner-border-sm" role="status"></span>
                                                </span>
                                            </button>
                                        </form>
                                    @endif

                                    @if($subscription->status === 'canceled')
                                        <span class="badge bg-secondary">{{ __('payment.no_actions') }}</span>
                                    @endif
                                    
                                    <!-- View Details Button -->
                                    <button type="button" class="btn btn-sm btn-info view-details" 
                                            data-bs-toggle="modal" data-bs-target="#subscriptionDetailsModal"
                                            data-subscription="{{ json_encode($subscription) }}"
                                            data-user="{{ json_encode($subscription->user) }}"
                                            data-business="{{ json_encode($subscription->user->business ?? null) }}">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted py-4">
                                <i class="fas fa-database fa-2x mb-2 d-block"></i>
                                {{ __('payment.no_subscriptions_found') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($subscriptions?->count() > 0)
            <div class="d-flex justify-content-between align-items-center mt-4">
                <div class="text-muted">
                    {{ __('payment.showing_x_of_y', ['count' => $subscriptions->count(), 'total' => $subscriptions->total()]) }}
                </div>
                <div>
                    {{ $subscriptions->appends(request()->except('page'))->links() }}
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Subscription Details Modal -->
<div class="modal fade" id="subscriptionDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-semibold">{{ __('payment.subscription_details') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="subscriptionDetailsContent">
                <!-- Content will be loaded via JavaScript -->
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('payment.close') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea, #764ba2) !important;
    }
    
    .card {
        transition: all 0.3s ease;
        border: none !important;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 .75rem 1.5rem rgba(0, 0, 0, .15) !important;
    }
    
    .table {
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .table th {
        font-weight: 600;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6c757d;
        border-top: none;
        background-color: #f8f9fc;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    .table td {
        vertical-align: middle;
        border-top: 1px solid #f3f4f6;
    }
    
    .table tbody tr {
        transition: all 0.2s ease;
    }
    
    .table tbody tr:hover {
        background-color: #f8f9fc;
        transform: scale(1.01);
    }
    
    .subscription-status {
        font-size: 0.75rem;
        padding: 0.5em 0.75em;
        border-radius: 1rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .subscription-status.active {
        background-color: #10b981;
        color: white;
    }
    
    .subscription-status.pending {
        background-color: #f59e0b;
        color: white;
    }
    
    .subscription-status.expired {
        background-color: #6b7280;
        color: white;
    }
    
    .subscription-status.cancelled {
        background-color: #ef4444;
        color: white;
    }
    
    .btn {
        transition: all 0.2s ease;
        border-radius: 0.5rem;
        font-weight: 500;
    }
    
    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
    }
    
    .btn-sm {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
    }
    
    .form-control, .form-select {
        border-radius: 0.5rem;
        border: 1px solid #e3e6f0;
        transition: all 0.2s ease;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #4e73df;
        box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.1);
    }
    
    .modal-content {
        border-radius: 1rem;
        border: none;
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.175);
    }
    
    .modal-header {
        border-bottom: 1px solid #f3f4f6;
        border-radius: 1rem 1rem 0 0;
        background-color: #f8f9fc;
    }
    
    .modal-footer {
        border-top: 1px solid #f3f4f6;
        border-radius: 0 0 1rem 1rem;
        background-color: #f8f9fc;
    }
    
    .alert {
        border-radius: 0.75rem;
        border: none;
    }
    
    .pagination {
        margin-bottom: 0;
    }
    
    .page-link {
        border-radius: 0.5rem;
        margin: 0 0.125rem;
        border: 1px solid #e3e6f0;
        color: #6c757d;
        transition: all 0.2s ease;
    }
    
    .page-link:hover {
        background-color: #4e73df;
        border-color: #4e73df;
        color: white;
        transform: translateY(-1px);
    }
    
    .page-item.active .page-link {
        background-color: #4e73df;
        border-color: #4e73df;
    }
    
    .dropdown-menu {
        border-radius: 0.75rem;
        border: none;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
    
    .dropdown-item {
        transition: all 0.2s ease;
        border-radius: 0.5rem;
        margin: 0.125rem;
    }
    
    .dropdown-item:hover {
        background-color: #4e73df;
        color: white;
        transform: translateX(5px);
    }
    
    .search-input {
        position: relative;
    }
    
    .search-input::before {
        content: '\f002';
        font-family: 'Font Awesome 5 Free';
        font-weight: 900;
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
        z-index: 5;
    }
    
    .search-input input {
        padding-left: 2.5rem;
    }
    
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }
    
    .loading-spinner {
        background-color: white;
        padding: 2rem;
        border-radius: 1rem;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        text-align: center;
    }
    
    .empty-state {
        padding: 3rem 1rem;
        text-align: center;
        color: #6c757d;
    }
    
    .empty-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.3;
    }
    
    .empty-state h5 {
        color: #495057;
        margin-bottom: 0.5rem;
    }
    
    .filter-badge {
        background-color: #4e73df;
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 1rem;
        font-size: 0.75rem;
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
        display: inline-flex;
        align-items: center;
    }
    
    .filter-badge .btn-close {
        margin-left: 0.5rem;
        font-size: 0.75rem;
        filter: invert(1);
    }
    
    .animate-fade-in {
        animation: fadeIn 0.5s ease-in;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .animate-slide-up {
        animation: slideUp 0.3s ease-out;
    }
    
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @media (max-width: 768px) {
        .card-body {
            padding: 1rem !important;
        }
        
        .table-responsive {
            font-size: 0.875rem;
        }
        
        .btn {
            font-size: 0.875rem;
            padding: 0.375rem 0.75rem;
        }
        
        .container-fluid {
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }
    }
    
    /* Custom scrollbar for table */
    .table-responsive::-webkit-scrollbar {
        height: 8px;
    }
    
    .table-responsive::-webkit-scrollbar-track {
        background: #f8f9fc;
        border-radius: 10px;
    }
    
    .table-responsive::-webkit-scrollbar-thumb {
        background: #4e73df;
        border-radius: 10px;
    }
    
    .table-responsive::-webkit-scrollbar-thumb:hover {
        background: #375a9d;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add fade-in animation to cards
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        setTimeout(() => {
            card.classList.add('animate-fade-in');
        }, index * 50);
    });
    
    // Add slide-up animation to table rows
    const tableRows = document.querySelectorAll('.table tbody tr');
    tableRows.forEach((row, index) => {
        setTimeout(() => {
            row.classList.add('animate-slide-up');
        }, index * 25);
    });
    
    // Auto-dismiss alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert.querySelector('.btn-close')) {
                alert.querySelector('.btn-close').click();
            }
        }, 5000);
    });
    
    // Add loading overlay
    const loadingOverlay = document.createElement('div');
    loadingOverlay.className = 'loading-overlay';
    loadingOverlay.innerHTML = `
        <div class="loading-spinner">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <h6 class="fw-medium mb-0">Processing request...</h6>
        </div>
    `;
    document.body.appendChild(loadingOverlay);
    
    // Global loading functions
    window.showLoading = function() {
        loadingOverlay.style.display = 'flex';
    };
    
    window.hideLoading = function() {
        loadingOverlay.style.display = 'none';
    };
    
    // Enhanced AJAX form handling
    document.querySelectorAll('form[data-ajax="true"]').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const url = this.action;
            const method = this.method || 'POST';
            
            showLoading();
            
            fetch(url, {
                method: method,
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                
                if (data.success) {
                    showToast('success', data.message || 'Operation completed successfully');
                    
                    // Close modal if exists
                    const modal = this.closest('.modal');
                    if (modal) {
                        bootstrap.Modal.getInstance(modal).hide();
                    }
                    
                    // Reload page or update content
                    if (data.reload) {
                        setTimeout(() => location.reload(), 1000);
                    }
                } else {
                    showToast('error', data.message || 'Operation failed');
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showToast('error', 'An error occurred. Please try again.');
            });
        });
    });
    
    // Toast notification function
    window.showToast = function(type, message) {
        const toastHtml = `
            <div class="toast align-items-center text-white bg-${type} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            document.body.appendChild(toastContainer);
        }
        
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        const toastElement = toastContainer.lastElementChild;
        const toast = new bootstrap.Toast(toastElement, { delay: 4000 });
        toast.show();
        
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    };
});
</script>
@endpush

@push('styles')
<style>
.subscription-status.active {
    background-color: #198754 !important;
}
.subscription-status.pending {
    background-color: #ffc107 !important;
    color: #000 !important;
}
.subscription-status.expired {
    background-color: #6c757d !important;
}
.subscription-status.canceled {
    background-color: #dc3545 !important;
}
.btn-loading .spinner-border-sm {
    width: 1rem;
    height: 1rem;
}
.ajax-form {
    display: inline-block;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {
    // Initialize AJAX forms
    document.querySelectorAll(".ajax-form").forEach(form => {
        form.addEventListener("submit", function (e) {
            e.preventDefault();
            
            const button = this.querySelector('button[type="submit"]');
            const btnText = button.querySelector('.btn-text');
            const btnLoading = button.querySelector('.btn-loading');
            
            // Show loading state
            btnText.classList.add('d-none');
            btnLoading.classList.remove('d-none');
            button.disabled = true;
            
            let url = this.action;
            let method = this.querySelector("input[name=_method]")?.value || this.method;
            let formData = new FormData(this);

            fetch(url, {
                method: method,
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                    "Accept": "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                },
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                // Show success message
                showAlert(data.success ? 'success' : 'danger', 
                         data.message || (data.success ? 
                             '{{ __("payment.operation_successful") }}' : 
                             '{{ __("payment.something_went_wrong") }}'));
                
                if (data.success) {
                    // Reload the page after a short delay to reflect changes
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                }
            })
            .catch(error => {
                console.error('AJAX Error:', error);
                showAlert('danger', '{{ __("payment.server_error_try_again") }}');
            })
            .finally(() => {
                // Restore button state
                btnText.classList.remove('d-none');
                btnLoading.classList.add('d-none');
                button.disabled = false;
            });
        });
    });

    // Filter functionality - server-side implementation
    const filterForm = document.getElementById('filterForm');
    const statusFilter = document.getElementById('statusFilter');
    const billingCycleFilter = document.getElementById('billingCycleFilter');
    const searchInput = document.getElementById('searchInput');
    const searchButton = document.getElementById('searchButton');
    const resetFilters = document.getElementById('resetFilters');

    // Auto-submit form when filters change
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            filterForm.submit();
        });
    }

    if (billingCycleFilter) {
        billingCycleFilter.addEventListener('change', function() {
            filterForm.submit();
        });
    }

    // Search functionality
    if (searchButton) {
        searchButton.addEventListener('click', function() {
            filterForm.submit();
        });
    }

    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                filterForm.submit();
            }
        });
    }

    // Reset filters
    if (resetFilters) {
        resetFilters.addEventListener('click', function() {
            // Clear all form inputs
            statusFilter.value = '';
            billingCycleFilter.value = '';
            searchInput.value = '';
            
            // Submit the form to reset everything
            filterForm.submit();
        });
    }

    // Subscription details modal
    const viewDetailsButtons = document.querySelectorAll('.view-details');
    const detailsContent = document.getElementById('subscriptionDetailsContent');

    viewDetailsButtons.forEach(button => {
        button.addEventListener('click', function() {
            const subscription = JSON.parse(this.getAttribute('data-subscription'));
            const user = JSON.parse(this.getAttribute('data-user'));
            const business = JSON.parse(this.getAttribute('data-business'));
            
            // Format dates
            const startDate = subscription.start_date ? new Date(subscription.start_date).toLocaleDateString() : 'N/A';
            const endDate = subscription.end_date ? new Date(subscription.end_date).toLocaleDateString() : 'N/A';
            const createdAt = subscription.created_at ? new Date(subscription.created_at).toLocaleDateString() : 'N/A';
            const updatedAt = subscription.updated_at ? new Date(subscription.updated_at).toLocaleDateString() : 'N/A';

            detailsContent.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="fw-semibold">{{ __('payment.subscription_info') }}</h6>
                        <p><strong class="text-muted">{{ __('payment.id') }}:</strong> <span class="fw-medium">${subscription.id}</span></p>
                        <p><strong class="text-muted">{{ __('payment.plan') }}:</strong> <span class="fw-medium">${subscription.plan_name}</span></p>
                        <p><strong class="text-muted">{{ __('payment.billing_cycle') }}:</strong> <span class="fw-medium text-capitalize">${subscription.billing_cycle}</span></p>
                        <p><strong class="text-muted">{{ __('payment.amount') }}:</strong> <span class="fw-medium">Ksh ${subscription.amount.toLocaleString()}</span></p>
                        <p><strong class="text-muted">{{ __('payment.status') }}:</strong> <span class="badge subscription-status ${subscription.status} text-capitalize">${subscription.status}</span></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-semibold">{{ __('payment.dates') }}</h6>
                        <p><strong class="text-muted">{{ __('payment.start_date') }}:</strong> <span class="fw-medium">${startDate}</span></p>
                        <p><strong class="text-muted">{{ __('payment.end_date') }}:</strong> <span class="fw-medium">${endDate}</span></p>
                        <p><strong class="text-muted">{{ __('payment.created_at') }}:</strong> <span class="fw-medium">${createdAt}</span></p>
                        <p><strong class="text-muted">{{ __('payment.updated_at') }}:</strong> <span class="fw-medium">${updatedAt}</span></p>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <h6 class="fw-semibold">{{ __('payment.user_info') }}</h6>
                        <p><strong class="text-muted">{{ __('payment.name') }}:</strong> <span class="fw-medium">${user.name || 'N/A'}</span></p>
                        <p><strong class="text-muted">{{ __('payment.email') }}:</strong> <span class="fw-medium">${user.email || 'N/A'}</span></p>
                        <p><strong class="text-muted">{{ __('payment.phone') }}:</strong> <span class="fw-medium">${user.phone || 'N/A'}</span></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-semibold">{{ __('payment.business_info') }}</h6>
                        <p><strong class="text-muted">{{ __('payment.business_name') }}:</strong> <span class="fw-medium">${business?.name || 'N/A'}</span></p>
                    </div>
                </div>
                ${subscription.mpesa_receipt ? `
                <div class="row mt-3">
                    <div class="col-12">
                        <h6 class="fw-semibold">{{ __('payment.payment_info') }}</h6>
                        <p><strong class="text-muted">{{ __('payment.mpesa_receipt') }}:</strong> <span class="fw-medium">${subscription.mpesa_receipt}</span></p>
                    </div>
                </div>
                ` : ''}
            `;
        });
    });

    // Show alert function
    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.getElementById('ajaxAlerts').appendChild(alertDiv);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
});
</script>
@endpush