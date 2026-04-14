<section class="content-header">
    <div class="row">
        <div class="col-sm-8">
            <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black mb-1">{{ $title ?? '' }}</h1>
            @if(!empty($subtitle))
                <p class="text-muted mb-0">{{ $subtitle }}</p>
            @endif
        </div>
        <div class="col-sm-4 text-right no-print" style="margin-top: 8px;">
            {!! $actions ?? '' !!}
        </div>
    </div>
</section>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    if (typeof window.hrmAlert !== 'function') {
        window.hrmAlert = function(message, type){
            var t = type || 'error';
            if (typeof window.showToast === 'function') {
                window.showToast(t, message);
                return;
            }
            if (window.toastr && typeof window.toastr[t] === 'function') {
                window.toastr[t](message);
                return;
            }
            var toast = document.createElement('div');
            toast.className = 'alert alert-' + (t === 'error' ? 'danger' : t);
            toast.style.position = 'fixed';
            toast.style.top = '20px';
            toast.style.right = '20px';
            toast.style.zIndex = '9999';
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(function(){ if (toast.parentNode) toast.parentNode.removeChild(toast); }, 3000);
        };
    }

    if (typeof window.hrmConfirm !== 'function') {
        window.hrmConfirm = function(message, opts){
            var options = opts || {};
            var text = message || options.text || 'Are you sure?';
            var danger = typeof options.danger === 'boolean'
                ? options.danger
                : /delete|remove|permanent|cannot be undone/i.test(String(text));
            var confirmText = options.confirmButtonText || (danger ? 'Delete' : 'Confirm');
            var cancelText = options.cancelButtonText || 'Cancel';
            var title = options.title || (danger ? 'Confirm Action' : 'Please Confirm');
            if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
                return Swal.fire({
                    title: title,
                    text: text,
                    icon: options.icon || 'warning',
                    showCancelButton: true,
                    confirmButtonText: confirmText,
                    cancelButtonText: cancelText,
                    confirmButtonColor: danger ? '#d33' : '#3085d6'
                }).then(function(result){
                    return !!(result && result.isConfirmed);
                });
            }

            return new Promise(function(resolve){
                var existing = document.getElementById('hrmConfirmFallback');
                if (existing) existing.remove();

                var overlay = document.createElement('div');
                overlay.id = 'hrmConfirmFallback';
                overlay.style.position = 'fixed';
                overlay.style.top = '0';
                overlay.style.left = '0';
                overlay.style.width = '100%';
                overlay.style.height = '100%';
                overlay.style.background = 'rgba(0,0,0,0.35)';
                overlay.style.zIndex = '9998';

                var box = document.createElement('div');
                box.style.position = 'fixed';
                box.style.top = '50%';
                box.style.left = '50%';
                box.style.transform = 'translate(-50%, -50%)';
                box.style.width = '360px';
                box.style.maxWidth = 'calc(100vw - 30px)';
                box.style.background = '#fff';
                box.style.borderRadius = '10px';
                box.style.padding = '14px';
                box.style.boxShadow = '0 12px 30px rgba(0,0,0,0.15)';
                var confirmClass = danger ? 'btn btn-danger btn-sm' : 'btn btn-primary btn-sm';
                box.innerHTML = '' +
                    '<div style="font-weight:600; margin-bottom:8px;">' + title + '</div>' +
                    '<div style="font-size:13px; color:#6b7280; margin-bottom:12px;">' + text + '</div>' +
                    '<div style="display:flex; justify-content:flex-end; gap:8px;">' +
                        '<button type="button" class="btn btn-default btn-sm" id="hrmConfirmNo">' + cancelText + '</button>' +
                        '<button type="button" class="' + confirmClass + '" id="hrmConfirmYes">' + confirmText + '</button>' +
                    '</div>';

                overlay.appendChild(box);
                document.body.appendChild(overlay);

                function done(val){
                    var el = document.getElementById('hrmConfirmFallback');
                    if (el) el.remove();
                    resolve(!!val);
                }

                overlay.addEventListener('click', function(e){ if (e.target === overlay) done(false); });
                document.getElementById('hrmConfirmNo').addEventListener('click', function(){ done(false); });
                document.getElementById('hrmConfirmYes').addEventListener('click', function(){ done(true); });
            });
        };
    }

    document.addEventListener('submit', function(e){
        var form = e.target;
        if (!form || !form.matches || !form.matches('form[data-hrm-confirm]')) return;
        if (form.getAttribute('data-hrm-confirmed') === '1') {
            form.removeAttribute('data-hrm-confirmed');
            return;
        }
        e.preventDefault();
        var msg = form.getAttribute('data-hrm-confirm') || 'Are you sure?';
        var isDanger = form.getAttribute('data-hrm-danger') === '1' || /delete|remove|permanent|cannot be undone/i.test(msg);
        window.hrmConfirm(msg, {
            title: form.getAttribute('data-hrm-confirm-title') || 'Please Confirm',
            confirmButtonText: form.getAttribute('data-hrm-confirm-yes') || (isDanger ? 'Delete' : 'Confirm'),
            cancelButtonText: form.getAttribute('data-hrm-confirm-no') || 'Cancel',
            danger: isDanger
        }).then(function(confirmed){
            if (confirmed) {
                form.setAttribute('data-hrm-confirmed', '1');
                form.submit();
            }
        });
    }, true);

    document.addEventListener('click', function(e){
        var btn = e.target && e.target.closest ? e.target.closest('[data-hrm-confirm-submit]') : null;
        if (!btn) return;
        e.preventDefault();
        var form = btn.closest('form');
        if (!form) return;
        var msg = btn.getAttribute('data-hrm-confirm') || form.getAttribute('data-hrm-confirm') || 'Are you sure?';
        var isDanger = btn.getAttribute('data-hrm-danger') === '1' || form.getAttribute('data-hrm-danger') === '1' || /delete|remove|permanent|cannot be undone/i.test(msg);
        window.hrmConfirm(msg, {
            title: btn.getAttribute('data-hrm-confirm-title') || 'Please Confirm',
            confirmButtonText: btn.getAttribute('data-hrm-confirm-yes') || (isDanger ? 'Delete' : 'Confirm'),
            cancelButtonText: btn.getAttribute('data-hrm-confirm-no') || 'Cancel',
            danger: isDanger
        }).then(function(confirmed){
            if (confirmed) form.submit();
        });
    });
});
</script>
@endpush