@extends('layouts.app')

@section('title', __('payment.admin_dashboard'))

@section('content')
<div class="container-fluid px-4 py-4">

    <!-- Header Banner -->
    <div class="bg-gradient-primary text-white py-4 mb-4 rounded-3">
        <div class="container-fluid px-0">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="h3 mb-1 fw-bold">
                        <i class="fas fa-tachometer-alt me-2"></i> {{ __('payment.admin_dashboard') }}
                    </h1>
                    <p class="mb-0 opacity-75">Overview of users, subscriptions and recent activity</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="{{ route('admin.subscriptions') }}" class="btn btn-light btn-sm shadow-sm">
                        <i class="fas fa-receipt me-1"></i> {{ __('payment.view_subscriptions') }}
                    </a>
                    <a href="{{ route('admin.users') }}" class="btn btn-light btn-sm shadow-sm ms-2">
                        <i class="fas fa-users me-1"></i> {{ __('payment.view_users') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 rounded-3 bg-gradient-primary text-white stats-card">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-1 fw-normal">{{ __('payment.total_businesses') }}</h6>
                            <h2 class="mb-0 fw-bold">{{ $totalBusinesses ?? $totalUsers ?? 0 }}</h2>
                        </div>
                        <div class="flex-shrink-0 text-end ms-3">
                            <i class="fas fa-building fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 rounded-3 bg-gradient-success text-white stats-card">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-1 fw-normal">{{ __('payment.active') }}</h6>
                            <h2 class="mb-0 fw-bold">{{ $activeUsers ?? 0 }}</h2>
                        </div>
                        <div class="flex-shrink-0 text-end ms-3">
                            <i class="fas fa-check-circle fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 rounded-3 bg-gradient-warning text-dark stats-card">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-1 fw-normal">{{ __('payment.inactive') }}</h6>
                            <h2 class="mb-0 fw-bold">{{ $inactiveUsers ?? 0 }}</h2>
                        </div>
                        <div class="flex-shrink-0 text-end ms-3">
                            <i class="fas fa-pause-circle fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 rounded-3 bg-gradient-danger text-white stats-card">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-1 fw-normal">{{ __('payment.terminated') }}</h6>
                            <h2 class="mb-0 fw-bold">{{ $terminatedUsers ?? 0 }}</h2>
                        </div>
                        <div class="flex-shrink-0 text-end ms-3">
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
              <form id="settingsForm" action="{{ route('admin.settings.update') }}" method="POST" class="settings-form" 
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

                        <h5 class="mt-3">Payroll Defaults</h5>
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label fw-medium">NSSF percent (fraction)</label>
                                <input type="number" step="0.00001" class="form-control" name="payroll_nssf_percent" value="{{ $settings?->payroll_nssf_percent ?? 0.0048 }}">
                                <small class="text-muted">e.g. 0.0048 for 0.48%</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-medium">SHIF percent (fraction)</label>
                                <input type="number" step="0.00001" class="form-control" name="payroll_shif_percent" value="{{ $settings?->payroll_shif_percent ?? 0.0275 }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-medium">Housing percent (fraction)</label>
                                <input type="number" step="0.00001" class="form-control" name="payroll_housing_percent" value="{{ $settings?->payroll_housing_percent ?? 0.015 }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-medium">Tax percent (fraction)</label>
                                <input type="number" step="0.00001" class="form-control" name="payroll_tax_percent" value="{{ $settings?->payroll_tax_percent ?? 0.245 }}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Personal relief (amount)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">Ksh</span>
                                    <input type="number" step="0.01" class="form-control" name="payroll_personal_relief" value="{{ $settings?->payroll_personal_relief ?? 2400 }}">
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <label class="form-label fw-medium">Payroll tax bands</label>
                                <div id="bandEditor" class="mb-2">
                                    <div class="card border-1 shadow-sm">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <div>
                                                    <strong>Payroll Tax Bands</strong>
                                                    <div class="small text-muted">Define progressive bands. Leave Upper empty for the final open band.</div>
                                                </div>
                                                <div class="btn-group">
                                                    <button type="button" id="addBand" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-plus me-1"></i> Add band
                                                    </button>
                                                    <button type="button" id="loadExampleBands" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-list me-1"></i> Load example
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="table-responsive">
                                                <table class="table table-sm table-borderless align-middle" id="bandsTable">
                                                    <thead>
                                                        <tr class="text-muted small">
                                                            <th style="width:55%">Upper (Ksh — leave empty for last band)</th>
                                                            <th style="width:30%">Rate (fraction, e.g. 0.1)</th>
                                                            <th style="width:15%" class="text-end">Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody></tbody>
                                                </table>
                                            </div>

                                            <div class="mt-2 d-flex justify-content-between align-items-center">
                                                <small class="text-muted">Tip: specify bands in increasing order; final band upper should be empty.</small>
                                                <div id="bandsError" class="text-danger small" style="display:none;"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="payroll_tax_bands" id="payroll_tax_bands" value="{{ $settings?->payroll_tax_bands ?? '' }}">
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
    /* Reuse the polished subscriptions styles so dashboard fits consistently */
    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea, #764ba2) !important;
    }

    .card {
        transition: all 0.3s ease;
        border: none !important;
        border-radius: 0.5rem;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 .75rem 1.5rem rgba(0, 0, 0, .08) !important;
    }

    .card-body { padding: 1rem; }

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

    .table td { vertical-align: middle; border-top: 1px solid #f3f4f6; }

    .table tbody tr { transition: all 0.2s ease; }
    .table tbody tr:hover { background-color: #f8f9fc; transform: scale(1.01); }

    .btn { transition: all 0.2s ease; border-radius: 0.5rem; font-weight: 500; }
    .btn:hover { transform: translateY(-1px); box-shadow: 0 0.25rem 0.5rem rgba(0,0,0,0.08); }

    .btn-sm { padding: 0.375rem 0.75rem; font-size: 0.875rem; }

    .form-control, .form-select { border-radius: 0.5rem; border: 1px solid #e3e6f0; transition: all 0.2s ease; }
    .form-control:focus, .form-select:focus { border-color: #4e73df; box-shadow: 0 0 0 0.15rem rgba(78,115,223,0.08); }

    .modal-content { border-radius: 1rem; border: none; box-shadow: 0 1rem 3rem rgba(0,0,0,0.14); }
    .modal-header { border-bottom: 1px solid #f3f4f6; border-radius: 1rem 1rem 0 0; background-color: #f8f9fc; }
    .modal-footer { border-top: 1px solid #f3f4f6; border-radius: 0 0 1rem 1rem; background-color: #f8f9fc; }

    .alert { border-radius: 0.75rem; border: none; }

    @media (max-width: 768px) {
        .card-body { padding: 1rem !important; }
        .table-responsive { font-size: 0.9rem; }
        .btn { font-size: 0.9rem; padding: 0.375rem 0.75rem; }
        .container-fluid { padding-left: 1rem !important; padding-right: 1rem !important; }
    }

    /* Small helper: ensure the stats cards align with subscriptions sizing */
    .card .card-body .h2, .card .card-body h2 { font-size: 1.6rem; }

    /* Keep ajax form overlay */
    .ajax-form { position: relative; }
    .form-disabled-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(255, 255, 255, 0.7); display: none; z-index: 100; border-radius: 0.375rem; }
    .ajax-form.loading .form-disabled-overlay { display: block; }

    /* Dashboard-specific tweaks */
    .stats-card { min-height: 90px; display: flex; align-items: center; }
    .stats-card h6 { font-size: 0.85rem; margin-bottom: 0.25rem; }
    .stats-card h2 { font-size: 1.6rem; margin: 0; }

    /* Cap tables inside dashboard cards so very long lists don't push the page too far */
    .card .table-responsive { max-height: 260px; overflow: auto; }
    .table td, .table th { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    /* Slight separation between sidebar and main on wide screens */
    @media (min-width: 992px) {
        body:not(.sidebar-collapse) main { padding-left: 0.5rem; }
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

            // AJAX forms are handled centrally in /public/js/ajax-forms.js
            console.log('Using centralized AJAX form handler for .ajax-form');
            
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
                    // Prefer explicit hidden form_action field when present to avoid action="javascript:void(0)"
                    const formAction = this.querySelector('input[name="form_action"]')?.value || this.action;
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

                    fetch(formAction, {
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

                // Bands editor wiring
                (function(){
                    function q(sel){ return document.querySelector(sel); }
                    var tableBody = q('#bandsTable tbody');
                    var addBtn = q('#addBand');
                    var hidden = q('#payroll_tax_bands');
                    var errorDiv = q('#bandsError');

                    function makeRow(upper, rate){
                        var tr = document.createElement('tr');
                        var u = document.createElement('td');
                        var ui = document.createElement('input'); ui.type='number'; ui.step='0.01'; ui.className='form-control form-control-sm';
                        if (upper !== null && upper !== undefined) ui.value = upper;
                        ui.placeholder = '';
                        u.appendChild(ui);

                        var r = document.createElement('td');
                        var ri = document.createElement('input'); ri.type='number'; ri.step='0.0001'; ri.className='form-control form-control-sm';
                        if (rate !== null && rate !== undefined) ri.value = rate;
                        r.appendChild(ri);

                        var a = document.createElement('td'); a.className='text-center';
                        var rem = document.createElement('button'); rem.type='button'; rem.className='btn btn-sm btn-outline-danger'; rem.innerText='Remove';
                        rem.addEventListener('click', function(){ tr.remove(); });
                        a.appendChild(rem);

                        tr.appendChild(u); tr.appendChild(r); tr.appendChild(a);
                        tableBody.appendChild(tr);
                        return tr;
                    }

                    function loadInitial(){
                        tableBody.innerHTML = '';
                        var raw = hidden.value || '';
                        if (!raw.trim()){ // add default empty row
                            makeRow('', ''); return;
                        }
                        try {
                            var arr = JSON.parse(raw);
                            if (!Array.isArray(arr)) throw new Error('Not array');
                            arr.forEach(function(b){ makeRow(b.upper === null ? '' : b.upper, b.rate); });
                        } catch(e){
                            // fallback: show one empty row and display error
                            makeRow('', '');
                            errorDiv.style.display = 'block';
                            errorDiv.innerText = 'Saved bands JSON is invalid — editing will overwrite it. Please fix after saving.';
                        }
                    }

                    addBtn.addEventListener('click', function(){ makeRow('', ''); });

                    function collectBands(){
                        var bands = [];
                        var rows = tableBody.querySelectorAll('tr');
                        for(var i=0;i<rows.length;i++){
                            var up = rows[i].querySelector('td:nth-child(1) input').value;
                            var rt = rows[i].querySelector('td:nth-child(2) input').value;
                            var upper = (up === undefined || up === null || up === '') ? null : parseFloat(up);
                            var rate = (rt === undefined || rt === null || rt === '') ? NaN : parseFloat(rt);
                            if (isNaN(rate)) { throw new Error('Rate must be a number on row '+(i+1)); }
                            bands.push({ upper: upper, rate: rate });
                        }
                        return bands;
                    }

                    // hook into main settings submit to validate and serialize
                    var settingsFormEl = q('#settingsForm');
                    if (settingsFormEl){
                        settingsFormEl.addEventListener('submit', function(ev){
                            try {
                                errorDiv.style.display = 'none'; errorDiv.innerText = '';
                                var bands = collectBands();
                                hidden.value = JSON.stringify(bands);
                                return true;
                            } catch(err){
                                ev.preventDefault(); ev.stopPropagation();
                                errorDiv.style.display = 'block'; errorDiv.innerText = err.message || 'Invalid bands';
                                return false;
                            }
                        });
                    }

                    loadInitial();
                })();
                
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