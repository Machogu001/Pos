@extends('layouts.app')

@section('title', __('payment.admin_dashboard'))

@section('content')
<div class="container-fluid py-4">

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 rounded-3 bg-gradient-primary text-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-1 fw-normal">{{ __('payment.total_businesses') }}</h6>
                            <h2 class="mb-0 fw-bold">{{ $totalUsers ?? 0 }}</h2>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-building fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 rounded-3 bg-gradient-success text-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-1 fw-normal">{{ __('payment.active') }}</h6>
                            <h2 class="mb-0 fw-bold">{{ $activeUsers ?? 0 }}</h2>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 rounded-3 bg-gradient-warning text-dark">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-1 fw-normal">{{ __('payment.inactive') }}</h6>
                            <h2 class="mb-0 fw-bold">{{ $inactiveUsers ?? 0 }}</h2>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-pause-circle fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 rounded-3 bg-gradient-danger text-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-1 fw-normal">{{ __('payment.terminated') }}</h6>
                            <h2 class="mb-0 fw-bold">{{ $terminatedUsers ?? 0 }}</h2>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="fas fa-times-circle fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Users Management -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-users me-2 text-primary"></i>
                        {{ __('payment.user_management') }}
                    </h5>
                    <a href="{{ route('admin.users') }}" class="btn btn-sm btn-outline-primary">
                        {{ __('payment.view_all') }} <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">{{ __('payment.business') }}</th>
                                    <th>{{ __('payment.business_status') }}</th>
                                    <th>{{ __('payment.user') }}</th>
                                    <th>{{ __('payment.phone') }}</th> <!-- Phone from M-Pesa payments -->
                                    <th>{{ __('payment.user_status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($recentUsers ?? collect())->take($settings->recent_limit ?? 5) as $user)
                                    <tr data-has-phone="{{ !empty($user->phone) ? 'true' : 'false' }}">
                                        <!-- Business -->
                                        <td class="ps-3">
                                            <span class="fw-medium">{{ optional($user->business)->name ?? 'N/A' }}</span>
                                        </td>

                                        <!-- Business Status -->
                                        <td>
                                            @if($user->business)
                                            <form action="{{ route('admin.business.update-status', $user->business) }}" 
                                                  method="POST" class="ajax-form d-flex align-items-center">
                                                @csrf
                                                @method('PATCH')
                                                <select name="is_active" class="form-select form-select-sm me-2">
                                                    <option value="1" {{ $user->business->is_active ? 'selected' : '' }}>{{ __('payment.active') }}</option>
                                                    <option value="0" {{ !$user->business->is_active ? 'selected' : '' }}>{{ __('payment.inactive') }}</option>
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <span class="btn-text">{{ __('payment.apply') }}</span>
                                                    <span class="btn-loading d-none">
                                                        <span class="spinner-border spinner-border-sm" role="status"></span>
                                                    </span>
                                                </button>
                                            </form>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                            
                                        <!-- User -->
                                        <td>
                                            <span class="fw-medium">{{ $user->username ?? $user->name ?? 'N/A' }}</span>
                                        </td>

                                        <!-- Phone Number from M-Pesa Payment -->
                                        <td>
                                            <span class="fw-medium">{{ $user->phone ?? 'N/A' }}</span>
                                        </td>

                                        <!-- User Status -->
                                        <td>
                                            <form action="{{ route('admin.users.update-status', $user) }}" 
                                                  method="POST" class="ajax-form user-status-form d-flex align-items-center">
                                                @csrf
                                                @method('PATCH')
                                                <select name="status" class="form-select form-select-sm me-2">
                                                    <option value="active" {{ $user->status === 'active' ? 'selected' : '' }}>{{ __('payment.active') }}</option>
                                                    <option value="inactive" {{ $user->status === 'inactive' ? 'selected' : '' }}>{{ __('payment.inactive') }}</option>
                                                    <option value="terminated" {{ $user->status === 'terminated' ? 'selected' : '' }}>{{ __('payment.terminated') }}</option>
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-primary">
                                                    <span class="btn-text">{{ __('payment.apply') }}</span>
                                                    <span class="btn-loading d-none">
                                                        <span class="spinner-border spinner-border-sm" role="status"></span>
                                                    </span>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            <i class="fas fa-user-slash fa-2x mb-2 d-block"></i>
                                            {{ __('payment.no_users_found') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Subscriptions -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-receipt me-2 text-success"></i>
                        {{ __('payment.recent_subscriptions') }}
                    </h5>
                    <a href="{{ route('admin.subscriptions') }}" class="btn btn-sm btn-outline-primary">
                        {{ __('payment.view_all') }} <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">{{ __('payment.user') }}</th>
                                    <th>{{ __('payment.business') }}</th>
                                    <th>{{ __('payment.plan') }}</th>
                                    <th>{{ __('payment.amount') }}</th>
                                    <th>{{ __('payment.status') }}</th>
                                    <th class="pe-3">{{ __('payment.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($recentSubscriptions ?? collect())->take($settings->recent_limit ?? 5) as $subscription)
                                    <tr>
                                        <td class="ps-3">
                                            <span class="fw-medium">{{ $subscription->user->username ?? ($subscription->user->name ?? 'N/A') }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-medium">{{ optional($subscription->user->business)->name ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-medium">{{ $subscription->plan_name }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-medium">Ksh {{ number_format($subscription->amount, 2) }}</span>
                                        </td>
                                        <td>
                                            <span class="badge 
                                                {{ $subscription->status === 'active' ? 'bg-success' : 
                                                   ($subscription->status === 'pending' ? 'bg-warning text-dark' : 
                                                   ($subscription->status === 'expired' ? 'bg-secondary' : 'bg-danger')) }} text-capitalize">
                                                {{ $subscription->status }}
                                            </span>
                                        </td>
                                        <td class="pe-3">
                                            <form action="{{ route('admin.subscriptions.update-status', $subscription) }}" 
                                                  method="POST" class="ajax-form d-flex align-items-center">
                                                @csrf
                                                @method('PATCH')
                                                <select name="status" class="form-select form-select-sm me-2">
                                                    <option value="active" {{ $subscription->status === 'active' ? 'selected' : '' }}>{{ __('payment.active') }}</option>
                                                    <option value="pending" {{ $subscription->status === 'pending' ? 'selected' : '' }}>{{ __('payment.pending') }}</option>
                                                    <option value="expired" {{ $subscription->status === 'expired' ? 'selected' : '' }}>{{ __('payment.expired') }}</option>
                                                    <option value="canceled" {{ $subscription->status === 'canceled' ? 'selected' : '' }}>{{ __('payment.canceled') }}</option>
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-primary">
                                                    <span class="btn-text">{{ __('payment.apply') }}</span>
                                                    <span class="btn-loading d-none">
                                                        <span class="spinner-border spinner-border-sm" role="status"></span>
                                                    </span>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="fas fa-file-invoice-dollar fa-2x mb-2 d-block"></i>
                                            {{ __('payment.no_subscriptions_found') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Subscription Settings -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm border-0 rounded-3 border-start border-4 border-info">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-cog me-2 text-info"></i>
                        {{ __('payment.subscription_settings') }}
                    </h5>
                </div>
                <div class="card-body">
                    <form id="settingsForm" action="javascript:void(0);" method="POST" class="settings-form" 
                          onsubmit="
                            event.preventDefault();
                            console.log('🚀 Settings form submitted inline');
                            
                            const form = this;
                            const formAction = '{{ route('admin.settings.update') }}';
                            const formData = new FormData(form);
                            const csrfToken = document.querySelector('meta[name=&quot;csrf-token&quot;]')?.content || '{{ csrf_token() }}';
                            
                            // Show loading state
                            const submitBtn = form.querySelector('button[type=&quot;submit&quot;]');
                            const btnText = submitBtn.querySelector('.btn-text');
                            const btnLoading = submitBtn.querySelector('.btn-loading');
                            
                            if (btnText && btnLoading) {
                                btnText.classList.add('d-none');
                                btnLoading.classList.remove('d-none');
                            }
                            submitBtn.disabled = true;
                            
                            fetch(formAction, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                console.log('✅ Settings response:', data);
                                if (data.success) {
                                    // Create floating toast notification
                                    const createToast = (type, message) => {
                                        const toast = document.createElement('div');
                                        toast.className = 'floating-toast floating-toast-' + type;
                                        toast.innerHTML = `
                                            <div class='toast-content'>
                                                <i class='fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2'></i>
                                                <span>${message}</span>
                                                <button type='button' class='toast-close' onclick='this.parentElement.parentElement.remove()'>
                                                    <i class='fas fa-times'></i>
                                                </button>
                                            </div>
                                        `;
                                        
                                        // Add styles if not already present
                                        if (!document.getElementById('floating-toast-styles')) {
                                            const style = document.createElement('style');
                                            style.id = 'floating-toast-styles';
                                            style.textContent = `
                                                .floating-toast {
                                                    position: fixed;
                                                    top: 1rem;
                                                    right: 1rem;
                                                    z-index: 9999;
                                                    min-width: 300px;
                                                    max-width: 500px;
                                                    border-radius: 0.75rem;
                                                    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
                                                    transform: translateX(100%);
                                                    transition: all 0.3s ease;
                                                    opacity: 0;
                                                }
                                                .floating-toast.show {
                                                    transform: translateX(0);
                                                    opacity: 1;
                                                }
                                                .floating-toast-success {
                                                    background-color: #10b981;
                                                    color: white;
                                                }
                                                .floating-toast-error {
                                                    background-color: #ef4444;
                                                    color: white;
                                                }
                                                .toast-content {
                                                    padding: 1rem 1.25rem;
                                                    display: flex;
                                                    align-items: center;
                                                    font-weight: 500;
                                                    font-size: 0.95rem;
                                                }
                                                .toast-close {
                                                    background: none;
                                                    border: none;
                                                    color: inherit;
                                                    margin-left: auto;
                                                    padding: 0.25rem;
                                                    cursor: pointer;
                                                    opacity: 0.7;
                                                    transition: opacity 0.2s ease;
                                                }
                                                .toast-close:hover {
                                                    opacity: 1;
                                                }
                                            `;
                                            document.head.appendChild(style);
                                        }
                                        
                                        document.body.appendChild(toast);
                                        setTimeout(() => toast.classList.add('show'), 10);
                                        setTimeout(() => {
                                            toast.classList.remove('show');
                                            setTimeout(() => toast.remove(), 300);
                                        }, 4000);
                                    };
                                    
                                    createToast('success', data.message || 'Settings updated successfully!');
                                    
                                    // Play success audio
                                    try {
                                        const successAudio = document.getElementById('success-audio');
                                        if (successAudio) {
                                            successAudio.play().catch(error => {
                                                console.log('🔇 Audio playback may be blocked by browser policy');
                                            });
                                        }
                                    } catch (error) {
                                        console.log('🔇 Audio not available');
                                    }
                                } else {
                                    createToast('error', data.message || 'Failed to update settings');
                                }
                            })
                            .catch(error => {
                                console.error('❌ Error:', error);
                                // Create error toast for network errors too
                                const createToast = (type, message) => {
                                    const toast = document.createElement('div');
                                    toast.style.cssText = 'position:fixed;top:1rem;right:1rem;z-index:9999;background:#ef4444;color:white;padding:1rem;border-radius:0.5rem;box-shadow:0 4px 6px rgba(0,0,0,0.1);';
                                    toast.innerHTML = '<i class=&quot;fas fa-exclamation-triangle me-2&quot;></i>' + message;
                                    document.body.appendChild(toast);
                                    setTimeout(() => toast.remove(), 4000);
                                };
                                createToast('error', 'An error occurred while updating settings');
                            })
                            .finally(() => {
                                if (btnText && btnLoading) {
                                    btnText.classList.remove('d-none');
                                    btnLoading.classList.add('d-none');
                                }
                                submitBtn.disabled = false;
                            });
                            
                            return false;
                          ">
                        @csrf
                        <input type="hidden" name="form_action" value="{{ route('admin.settings.update') }}">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-medium">{{ __('payment.monthly_price') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">Ksh</span>
                                    <input type="number" step="0.01" class="form-control" name="monthly_price"
                                        value="{{ $settings?->monthly_price ?? 0 }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">{{ __('payment.quarterly_price') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">Ksh</span>
                                    <input type="number" step="0.01" class="form-control" name="quarterly_price"
                                        value="{{ $settings?->quarterly_price ?? 0 }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">{{ __('payment.yearly_price') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">Ksh</span>
                                    <input type="number" step="0.01" class="form-control" name="yearly_price"
                                        value="{{ $settings?->yearly_price ?? 0 }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">{{ __('payment.registration_price') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">Ksh</span>
                                    <input type="number" step="0.01" min="0" max="10000" class="form-control" name="registration_price"
                                        value="{{ $settings?->registration_price ?? 5 }}" aria-describedby="registrationPriceHelp">
                                </div>
                                <small id="registrationPriceHelp" class="text-muted">Price charged during business registration. Set to 0 to make registration free. Max Ksh 10,000.</small>
                                <div class="mt-2">
                                    <button id="previewRegistrationEmailBtn" type="button" class="btn btn-sm btn-outline-secondary">Preview Registration Email</button>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-check form-switch mt-3">
                                    <input class="form-check-input" type="checkbox" name="auto_renewal"
                                        id="auto_renewal" value="1" {{ ($settings?->auto_renewal ?? false) ? 'checked' : '' }}>
                                    <label for="auto_renewal" class="form-check-label fw-medium">{{ __('payment.enable_auto_renewal') }}</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">{{ __('payment.grace_period_days') }}</label>
                                <input type="number" class="form-control" name="grace_period_days"
                                    value="{{ $settings?->grace_period_days ?? 7 }}" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">{{ __('payment.recent_records_limit') }}</label>
                                <input type="number" min="1" max="100" class="form-control" name="recent_limit"
                                    value="{{ $settings?->recent_limit ?? 5 }}" required>
                                <small class="text-muted">{{ __('payment.controls_how_many_recent') }}</small>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary px-4">
                            <span class="btn-text">{{ __('payment.update_settings') }}</span>
                            <span class="btn-loading d-none">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                                <span class="ms-1">{{ __('payment.updating') }}</span>
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Manual Subscription Management -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm border-0 rounded-3 border-start border-4 border-success">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-hand-holding-usd me-2 text-success"></i>
                        {{ __('payment.manual_subscription_management') }}
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.subscriptions.manual') }}" method="POST" class="ajax-form">
                        @csrf
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-medium">{{ __('payment.select_user') }}</label>
                                <select class="form-select" name="user_id" required>
                                    <option value="">{{ __('payment.select_user') }}</option>
                                    @foreach($users ?? [] as $user)
                                    <option value="{{ $user->id }}">
                                        {{ optional($user->business)->name ?? $user->name }} ({{ $user->email }})
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">{{ __('payment.billing_cycle') }}</label>
                                <select class="form-select" name="billing_cycle" required>
                                    <option value="monthly">{{ __('payment.monthly') }}</option>
                                    <option value="quarterly">{{ __('payment.quarterly') }}</option>
                                    <option value="yearly">{{ __('payment.yearly') }}</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">{{ __('payment.custom_amount') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">Ksh</span>
                                    <input type="number" step="0.01" class="form-control" name="custom_amount">
                                </div>
                                <small class="text-muted">{{ __('payment.leave_empty_default') }}</small>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success px-4">
                            <span class="btn-text">{{ __('payment.create_manual_subscription') }}</span>
                            <span class="btn-loading d-none">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                                <span class="ms-1">{{ __('payment.creating') }}</span>
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
    .bg-gradient-primary {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%) !important;
    }
    .bg-gradient-success {
        background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%) !important;
    }
    .bg-gradient-warning {
        background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%) !important;
    }
    .bg-gradient-danger {
        background: linear-gradient(135deg, #e74a3b 0%, #be2617 100%) !important;
    }
    .btn-loading .spinner-border-sm {
        width: 1rem;
        height: 1rem;
    }
    .toast-success {
        background-color: #28a745 !important;
    }
    .toast-error {
        background-color: #dc3545 !important;
    }
    .ajax-form {
        position: relative;
    }
    .form-disabled-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(255, 255, 255, 0.7);
        display: none;
        z-index: 100;
        border-radius: 0.375rem;
    }
    .ajax-form.loading .form-disabled-overlay {
        display: block;
    }
    .status-update-feedback {
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }
    .table th {
        font-weight: 600;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6c757d;
    }
    
    /* Custom Toast Styling */
    .swal2-toast-custom {
        border-radius: 0.75rem !important;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        border: none !important;
        font-size: 0.95rem !important;
        font-weight: 500 !important;
        z-index: 9999 !important;
    }
    
    .swal2-toast-custom .swal2-title {
        font-size: 0.95rem !important;
        font-weight: 500 !important;
        margin: 0 !important;
    }
    
    .swal2-toast-custom .swal2-timer-progress-bar {
        background-color: rgba(255, 255, 255, 0.3) !important;
        height: 3px !important;
    }
    
    .swal2-toast-custom .swal2-icon {
        margin: 0 0.75rem 0 0 !important;
        width: 1.5rem !important;
        height: 1.5rem !important;
        font-size: 1rem !important;
    }
    
    .swal2-container {
        z-index: 9999 !important;
        padding: 1rem !important;
    }
    
    /* Native Toast Fallback Styles */
    .native-toast {
        position: fixed;
        top: 1rem;
        right: 1rem;
        z-index: 9999;
        min-width: 300px;
        max-width: 500px;
        border-radius: 0.75rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        transform: translateX(100%);
        transition: all 0.3s ease;
        opacity: 0;
    }
    
    .native-toast.show {
        transform: translateX(0);
        opacity: 1;
    }
    
    .native-toast-success {
        background-color: #10b981;
        color: white;
    }
    
    .native-toast-error {
        background-color: #ef4444;
        color: white;
    }
    
    .native-toast-content {
        padding: 1rem 1.25rem;
        display: flex;
        align-items: center;
        font-weight: 500;
        font-size: 0.95rem;
    }
    
    .native-toast-close {
        background: none;
        border: none;
        color: inherit;
        margin-left: auto;
        padding: 0.25rem;
        cursor: pointer;
        opacity: 0.7;
        transition: opacity 0.2s ease;
    }
    
    .native-toast-close:hover {
        opacity: 1;
    }
    
    @media (max-width: 768px) {
        .native-toast {
            left: 1rem;
            right: 1rem;
            min-width: auto;
            max-width: none;
        }
    }
</style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {

            // Add phone number validation before activation
            document.querySelectorAll('.user-status-form select[name="status"]').forEach(select => {
                // Store initial value
                select.setAttribute('data-previous', select.value);
                
                select.addEventListener('change', function() {
                    if (this.value === 'active') {
                        const row = this.closest('tr');
                        const hasPhone = row.dataset.hasPhone === 'true';
                        const phoneCell = row.querySelector('td:nth-child(4)'); // 4th column is phone
                        
                        if (!hasPhone) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Missing Phone Number',
                                html: `This user does not have a phone number for payment processing.<br>
                                      Phone: <strong>${phoneCell.textContent.trim()}</strong><br><br>
                                      Please add a phone number before activating this user.`,
                                confirmButtonText: 'OK'
                            });
                            
                            // Reset to previous value
                            const previousStatus = this.getAttribute('data-previous') || 'inactive';
                            this.value = previousStatus;
                        } else {
                            // Store current value as previous for next time
                            this.setAttribute('data-previous', this.value);
                        }
                    } else {
                        // Store current value as previous for next time
                        this.setAttribute('data-previous', this.value);
                    }
                });
            });

            // Initialize all AJAX forms (except settings form which has its own handler)
            document.querySelectorAll(".ajax-form").forEach(form => {
                // Skip settings form as it has its own dedicated handler
                if (form.id === 'settingsForm') {
                    console.log('⏭️ Skipping settings form (has dedicated handler)');
                    return;
                }
                
                console.log('Initializing AJAX form:', form.id || form.action);
                
                // Add a disabled overlay for better UX
                const overlay = document.createElement('div');
                overlay.className = 'form-disabled-overlay';
                form.appendChild(overlay);
                
                form.addEventListener("submit", function (e) {
                    // Always prevent default form submission for AJAX handling
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Check if this is a user status form with active status and no phone
                    if (form.classList.contains('user-status-form')) {
                        const statusSelect = form.querySelector('select[name="status"]');
                        const row = form.closest('tr');
                        const hasPhone = row.dataset.hasPhone === 'true';
                        
                        if (statusSelect && statusSelect.value === 'active' && !hasPhone) {
                            const phoneCell = row.querySelector('td:nth-child(4)');
                            
                            Swal.fire({
                                icon: 'warning',
                                title: 'Missing Phone Number',
                                html: `This user does not have a phone number for payment processing.<br>
                                      Phone: <strong>${phoneCell.textContent.trim()}</strong><br><br>
                                      Please add a phone number before activating this user.`,
                                confirmButtonText: 'OK'
                            });
                            
                            // Reset to previous value
                            const previousStatus = statusSelect.getAttribute('data-previous') || 'inactive';
                            statusSelect.value = previousStatus;
                            return false;
                        }
                    }
                    
                    // Show loading state
                    setFormLoadingState(this, true);
                    
                    let url = this.action;
                    let method = this.querySelector("input[name=_method]")?.value || this.method;
                    let formData = new FormData(this);

                    // Get CSRF token from meta tag or fallback
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || 
                                    document.querySelector('input[name="_token"]')?.value || 
                                    '{{ csrf_token() }}';
                    
                    console.log('Submitting AJAX form:', url, method);
                    
                    fetch(url, {
                        method: method,
                        headers: {
                            "X-CSRF-TOKEN": csrfToken,
                            "Accept": "application/json",
                            "X-Requested-With": "XMLHttpRequest"
                        },
                        body: formData
                    })
                    .then(response => {
                        console.log('Response status:', response.status);
                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Response data:', data);
                        // Handle settings form specifically
                        if (data.success && url.includes('settings.update')) {
                            // Update the recent limit in all tables without reloading
                            if (data.settings) {
                                const recentLimit = data.settings.recent_limit || 5;
                                document.querySelectorAll('table tbody tr:not(.no-entries)').forEach((row, index) => {
                                    if (index >= recentLimit) {
                                        row.style.display = 'none';
                                    } else {
                                        row.style.display = '';
                                    }
                                });
                            }
                            
                            // Show specific success message for settings update
                            showToast('success', '{{ __("payment.settings_updated_successfully") }}');
                            return; // Skip the generic success message
                        }
                        
                        // For status update forms, update the badge if it exists
                        if (data.success && url.includes('update-status')) {
                            const statusCell = form.closest('td');
                            const statusBadge = statusCell.querySelector('.badge');
                            
                            if (statusBadge && data.new_status) {
                                // Update badge class and text
                                const statusClass = getStatusBadgeClass(data.new_status);
                                statusBadge.className = 'badge ' + statusClass + ' text-capitalize';
                                statusBadge.textContent = data.new_status;
                            }
                        }
                        
                        // Show generic success/error message for other forms
                        showToast(
                            data.success ? 'success' : 'error',
                            data.message || (data.success ? 
                                '{{ __("payment.updated_successfully") }}' : 
                                '{{ __("payment.something_went_wrong") }}')
                        );
                    })
                    .catch(error => {
                        console.error('AJAX Error:', error);
                        showToast(
                            'error', 
                            '{{ __("payment.server_error_try_again") }}'
                        );
                    })
                    .finally(() => {
                        // Restore form state
                        setFormLoadingState(this, false);
                    });
                });
            });
            
            // Additional specific handler for settings form (backup)
            const settingsForm = document.getElementById('settingsForm');
            if (settingsForm) {
                console.log('Found settings form, adding backup handler');
                // Remove any existing event listeners to avoid conflicts
                settingsForm.removeEventListener('submit', settingsForm._customHandler);
                
                settingsForm._customHandler = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Settings form submitted via backup handler');
                    
                    const formData = new FormData(this);
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
                    
                    // Show loading state
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const btnText = submitBtn.querySelector('.btn-text');
                    const btnLoading = submitBtn.querySelector('.btn-loading');
                    
                    if (btnText && btnLoading) {
                        btnText.classList.add('d-none');
                        btnLoading.classList.remove('d-none');
                    }
                    submitBtn.disabled = true;
                    
                    fetch(this.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    })
                    .then(response => {
                        console.log('Response status:', response.status);
                        return response.json();
                    })
                    .then(data => {
                        console.log('Settings update response:', data);
                        if (data.success) {
                            showToast('success', data.message || 'Settings updated successfully!');
                        } else {
                            showToast('error', data.message || 'Failed to update settings');
                        }
                    })
                    .catch(error => {
                        console.error('Settings update error:', error);
                        showToast('error', 'An error occurred while updating settings');
                    })
                    .finally(() => {
                        // Restore button state
                        if (btnText && btnLoading) {
                            btnText.classList.remove('d-none');
                            btnLoading.classList.add('d-none');
                        }
                        submitBtn.disabled = false;
                    });
                };
                
                settingsForm.addEventListener('submit', settingsForm._customHandler);
            }
            
            // Manual payment check handler
            if (document.getElementById('manualCheckStatus')) {
                document.getElementById('manualCheckStatus').addEventListener('click', async function() {
                    const responseDiv = document.getElementById('stkResponse') || document.createElement('div');
                    responseDiv.innerHTML = '<div class="alert alert-info">{{ __("payment.checking_payment") }}</div>';
                    
                    try {
                        const checkoutRequestId = '{{ session('checkout_request_id') }}';
                        const res = await fetch("{{ route('subscription.manualStatusCheck') }}", {
                            method: 'POST',
                            headers: { 
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ 
                                checkout_request_id: checkoutRequestId
                            })
                        });
                        
                        if (!res.ok) {
                            throw new Error(`HTTP error! Status: ${res.status}`);
                        }
                        
                        const data = await res.json();
                        
                        if (data.transaction_status === 'success') {
                            responseDiv.innerHTML = '<div class="alert alert-success">{{ __("payment.payment_confirmed_reload") }}</div>';
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            responseDiv.innerHTML = `<div class="alert alert-warning">{{ __("payment.payment_status") }}: ${data.transaction_status}</div>`;
                        }
                    } catch(err) {
                        console.error('Payment check error:', err);
                        responseDiv.innerHTML = '<div class="alert alert-danger">{{ __("payment.payment_status_unknown") }}</div>';
                    }
                });
            }
        });
        
        /**
         * Get appropriate badge class for status
         */
        function getStatusBadgeClass(status) {
            switch(status) {
                case 'active': return 'bg-success';
                case 'pending': return 'bg-warning text-dark';
                case 'expired': return 'bg-secondary';
                case 'canceled': return 'bg-danger';
                default: return 'bg-secondary';
            }
        }

            // Preview registration email handler
            const previewBtn = document.getElementById('previewRegistrationEmailBtn');
            if (previewBtn) {
                previewBtn.addEventListener('click', async function () {
                    try {
                        const res = await fetch('{{ route('admin.settings.previewRegistrationEmail') }}', {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        if (!res.ok) throw new Error('Failed to load preview');
                        const html = await res.text();

                        // Show modal with preview
                        const modalDiv = document.createElement('div');
                        modalDiv.className = 'modal fade';
                        modalDiv.style.display = 'block';
                        modalDiv.innerHTML = `
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Registration Email Preview</h5>
                                        <button type="button" class="btn-close" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">${html}</div>
                                </div>
                            </div>`;

                        document.body.appendChild(modalDiv);
                        // close handler
                        modalDiv.querySelector('.btn-close').addEventListener('click', () => modalDiv.remove());
                    } catch (err) {
                        console.error(err);
                        showToast('error', 'Unable to load preview');
                    }
                });
            }
        
        /**
         * Set loading state for form elements
         */
        function setFormLoadingState(form, isLoading) {
            // Toggle form overlay
            const overlay = form.querySelector('.form-disabled-overlay');
            if (overlay) {
                overlay.style.display = isLoading ? 'block' : 'none';
            }
            
            // Toggle form class
            if (isLoading) {
                form.classList.add('loading');
            } else {
                form.classList.remove('loading');
            }
            
            // Toggle buttons state
            const buttons = form.querySelectorAll('button');
            buttons.forEach(button => {
                button.disabled = isLoading;
                
                const btnText = button.querySelector('.btn-text');
                const btnLoading = button.querySelector('.btn-loading');
                
                if (btnText && btnLoading) {
                    if (isLoading) {
                        btnText.classList.add('d-none');
                        btnLoading.classList.remove('d-none');
                    } else {
                        btnText.classList.remove('d-none');
                        btnLoading.classList.add('d-none');
                    }
                }
            });
            
            // Toggle inputs state
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.disabled = isLoading;
            });
        }
        
        /**
         * Show toast notification
         */
        function showToast(icon, title) {
            try {
                // Try using SweetAlert2 first
                if (typeof Swal !== 'undefined') {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 4000,
                        timerProgressBar: true,
                        customClass: {
                            popup: 'swal2-toast-custom'
                        },
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', Swal.stopTimer);
                            toast.addEventListener('mouseleave', Swal.resumeTimer);
                        }
                    });
                    
                    Toast.fire({
                        icon: icon,
                        title: title,
                        background: icon === 'success' ? '#10b981' : '#ef4444',
                        color: '#ffffff',
                        iconColor: '#ffffff'
                    });
                } else {
                    // Fallback to native toast
                    showNativeToast(icon, title);
                }
            } catch (error) {
                console.error('Toast error:', error);
                // Fallback to native toast
                showNativeToast(icon, title);
            }
        }
        
        /**
         * Native toast fallback
         */
        function showNativeToast(type, message) {
            // Remove any existing toasts
            const existingToasts = document.querySelectorAll('.native-toast');
            existingToasts.forEach(toast => toast.remove());
            
            const toast = document.createElement('div');
            toast.className = `native-toast native-toast-${type}`;
            toast.innerHTML = `
                <div class="native-toast-content">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                    <span>${message}</span>
                    <button type="button" class="native-toast-close" onclick="this.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            // Show with animation
            setTimeout(() => toast.classList.add('show'), 10);
            
            // Auto-remove after 4 seconds
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }
    </script>
@endpush