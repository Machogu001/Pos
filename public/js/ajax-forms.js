document.addEventListener('DOMContentLoaded', function () {
    // Centralized AJAX form handler for elements with class .ajax-form
    document.querySelectorAll('.ajax-form').forEach(form => {
        // Avoid attaching twice
        if (form._ajaxHandlerAttached) return;
        form._ajaxHandlerAttached = true;

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const button = this.querySelector('button[type="submit"]');
            const btnText = button ? button.querySelector('.btn-text') : null;
            const btnLoading = button ? button.querySelector('.btn-loading') : null;

            // Determine intended method from hidden _method or form.method
            let intendedMethod = (this.querySelector("input[name=_method]")?.value || this.method || 'POST').toString();

            // Collect FormData BEFORE disabling inputs — disabled elements are not included in FormData
            let formData = new FormData(this);

            // If intended is PATCH/PUT/DELETE and the payload is multipart, send as POST and keep _method
            let fetchMethod = ['PATCH', 'PUT', 'DELETE'].includes(intendedMethod.toUpperCase()) ? 'POST' : intendedMethod;

            // Show loading state
            if (btnText && btnLoading) {
                btnText.classList.add('d-none');
                btnLoading.classList.remove('d-none');
            }
            if (button) button.disabled = true;

            // Add form disabled overlay if present via class
            if (this.classList && !this.classList.contains('loading')) this.classList.add('loading');

            // CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

            fetch(this.action, {
                method: fetchMethod,
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(async response => {
                let payload = null;
                try { payload = await response.json(); } catch (e) { payload = null; }

                if (!response.ok) {
                    const err = new Error(payload?.message || `HTTP ${response.status}`);
                    err.payload = payload;
                    throw err;
                }
                return payload;
            })
            .then(data => {
                // Update badges or UI inline if server returns state
                try {
                    if (data && data.new_status) {
                        const row = form.closest('tr');
                        if (row) {
                            const badge = row.querySelector('.badge');
                            if (badge) {
                                // If a helper getStatusBadgeClass exists, use it
                                const cls = (typeof getStatusBadgeClass === 'function') ? getStatusBadgeClass(data.new_status) : 'bg-secondary';
                                badge.className = 'badge ' + cls + ' text-capitalize';
                                badge.textContent = data.new_status;
                            }
                        }
                    }

                    if (data && data.new_is_active !== undefined) {
                        const row = form.closest('tr');
                        if (row) {
                            const badge = row.querySelector('.badge');
                            if (badge) {
                                badge.className = 'badge ' + (data.new_is_active ? 'bg-success' : 'bg-secondary');
                                badge.textContent = data.new_is_active ? 'Active' : 'Inactive';
                            }
                        }
                    }
                } catch (e) { console.warn('UI update after AJAX: ', e); }

                // Show success toast
                if (window.showToast) {
                    showToast('success', data.message || 'Applied successfully');
                } else {
                    // Fallback: simple alert in top-right
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-success position-fixed top-0 end-0 m-3';
                    alertDiv.innerText = data.message || 'Applied successfully';
                    document.body.appendChild(alertDiv);
                    setTimeout(() => alertDiv.remove(), 4000);
                }

                // If server asked to reload
                if (data && data.reload) {
                    setTimeout(() => window.location.reload(), 1200);
                }
            })
            .catch(error => {
                console.error('AJAX Error:', error);
                let msg = 'An error occurred. Please try again.';
                try {
                    if (error && error.payload && error.payload.message) msg = error.payload.message;
                    else if (error && error.message) msg = error.message;
                    if (error && error.payload && error.payload.errors) {
                        const errs = Object.values(error.payload.errors).flat().map(i => Array.isArray(i) ? i.join(' ') : i).join(' ');
                        if (errs) msg = errs;
                    }
                } catch (e) {}

                if (window.showToast) showToast('error', msg);
                else {
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger position-fixed top-0 end-0 m-3';
                    alertDiv.innerText = msg;
                    document.body.appendChild(alertDiv);
                    setTimeout(() => alertDiv.remove(), 4000);
                }
            })
            .finally(() => {
                // Restore button state
                if (btnText && btnLoading) {
                    btnText.classList.remove('d-none');
                    btnLoading.classList.add('d-none');
                }
                if (button) button.disabled = false;
                if (this.classList && this.classList.contains('loading')) this.classList.remove('loading');
            });
        });
    });
});
