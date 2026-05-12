@extends('layouts.app')

@section('title', __('ui.departments'))

@section('content')
@include('hrm::partials.hrm_page_header', [
    'title' => __('ui.departments'),
    'subtitle' => __('ui.organize_employees_by_department_and_assign_the_right_department_head'),
    'actions' => '<a href="'.route('hrm.departments.create').'" class="btn btn-primary"><i class="fa fa-plus"></i> '. __('ui.create_department') .'</a>'
])

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ __('ui.department_directory') }}</h3>
        </div>
        <div class="box-body no-padding">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('ui.department') }}</th>
                            <th>{{ __('ui.company') }}</th>
                            <th>{{ __('ui.head') }}</th>
                            <th class="text-end">{{ __('ui.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($departments as $d)
                            @php
                                $head = null;
                                if(isset($employees) && $employees instanceof \Illuminate\Support\Collection){
                                    $head = $employees->firstWhere('id', $d->department_head);
                                }
                                $headLabel = '-';
                                if($head){
                                    $headLabel = $head->username ?? trim((($head->firstname ?? '') . ' ' . ($head->lastname ?? '')));
                                } elseif(!empty($d->employee_head)) {
                                    $headLabel = $d->employee_head;
                                }
                            @endphp
                            <tr data-id="{{ $d->id }}">
                                <td><strong>{{ $d->department }}</strong></td>
                                <td>{{ $d->company_name ?? '-' }}</td>
                                <td class="head-cell">{{ $headLabel }}</td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-primary set-head-btn" data-id="{{ $d->id }}" data-current="{{ $d->department_head ?? '' }}">{{ __('ui.set_head') }}</button>
                                    @if($d->department_head)
                                        <button type="button" class="btn btn-sm btn-warning remove-head-btn" data-id="{{ $d->id }}">{{ __('ui.remove_head') }}</button>
                                    @endif
                                    <a href="{{ route('hrm.departments.edit', $d->id) }}" class="btn btn-sm btn-default">{{ __('ui.edit') }}</a>
                                    <form action="{{ route('hrm.departments.destroy', $d->id) }}" method="POST" style="display:inline-block" data-hrm-confirm="{{ __('ui.delete_this_department') }}" data-hrm-confirm-title="{{ __('ui.delete_department') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger">{{ __('ui.delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">{{ __('ui.no_departments_found') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if(isset($totalRows) && $totalRows > (int)($perPage ?? 0) && isset($paginator))
        <div class="text-right">
            {{ $paginator->appends(request()->query())->links() }}
        </div>
    @endif
</section>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    var csrf = '{{ csrf_token() }}';
    var baseUrl = '{{ url("") }}';

    function showToast(message, type){
        type = type || 'success';
        var toast = document.createElement('div');
        toast.className = 'alert alert-' + (type === 'error' ? 'danger' : type) + ' position-fixed';
        toast.style.right = '20px'; toast.style.top = '20px'; toast.style.zIndex = 2000;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(function(){ toast.style.opacity = '0'; setTimeout(function(){ toast.remove(); }, 300); }, 3000);
    }

    // Inject modal markup for setting department head
    var modalHtml = `
                <div class="modal fade" id="setHeadModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('ui.set_department_head') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div id="modalError" class="alert alert-danger d-none"></div>
                    <form id="setHeadForm">
                        <input type="hidden" name="department_id" id="modal_department_id" />
                        <div class="form-group">
                            <label for="modal_department_head" class="form-label">{{ __('ui.select_employee_2') }}</label>
                            <select id="modal_department_head" name="department_head" class="form-control">
                                <option value="">{{ __('ui.none') }}</option>
                                @foreach($employees as $e)
                                    @php $empLabel = $e->username ?? trim((($e->firstname ?? '') . ' ' . ($e->lastname ?? ''))); @endphp
                                    <option value="{{ $e->id }}">{{ $empLabel ?: __('ui.employee_2') . $e->id }}</option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('ui.cancel') }}</button>
                    <button type="button" id="modalSaveHead" class="btn btn-primary">{{ __('ui.save') }}</button>
                </div>
            </div>
        </div>
    </div>`;

    document.body.insertAdjacentHTML('beforeend', modalHtml);
    var setHeadModalEl = document.getElementById('setHeadModal');
    var bsModal = (typeof bootstrap !== 'undefined' && setHeadModalEl) ? new bootstrap.Modal(setHeadModalEl) : null;
    var employeeOptions = @json(($employees ?? collect())->map(function ($e) {
        $empLabel = $e->username ?? trim((($e->firstname ?? '') . ' ' . ($e->lastname ?? '')));
        return ['id' => (string) $e->id, 'label' => ($empLabel ?: (__('ui.employee_2') . $e->id))];
    })->values());

    function openSetHeadToastInput(id, current){
        if(typeof Swal !== 'undefined' && typeof Swal.fire === 'function'){
            var inputOptions = { '': "{{ __('ui.none') }}" };
            employeeOptions.forEach(function(item){ inputOptions[item.id] = item.label; });
            Swal.fire({
                title: "{{ __('ui.set_department_head') }}",
                text: "{{ __('ui.select_employee_to_set_as_head') }}",
                input: 'select',
                inputOptions: inputOptions,
                inputValue: current || '',
                showCancelButton: true,
                confirmButtonText: "{{ __('ui.save') }}",
                cancelButtonText: "{{ __('ui.cancel') }}",
                preConfirm: function(value){
                    return sendSetHead(id, value || '').then(function(data){
                        if(data && data.success){ return data; }
                        var msg = (data && data.message) ? data.message : "{{ __('ui.failed_to_set_department_head') }}";
                        if(typeof Swal.showValidationMessage === 'function'){
                            Swal.showValidationMessage(msg);
                        }
                        return false;
                    }).catch(function(){
                        if(typeof Swal.showValidationMessage === 'function'){
                            Swal.showValidationMessage("{{ __('ui.request_failed') }}");
                        }
                        return false;
                    });
                }
            }).then(function(result){
                if(result && result.isConfirmed && result.value){
                    updateHeadRow(id, result.value);
                    showToast("{{ __('ui.department_head_updated') }}", 'success');
                }
            });
            return;
        }

        var existing = document.getElementById('setHeadToastPrompt');
        if(existing){ existing.remove(); }

        var wrapper = document.createElement('div');
        wrapper.id = 'setHeadToastPrompt';
        wrapper.style.position = 'fixed';
        wrapper.style.top = '50%';
        wrapper.style.left = '50%';
        wrapper.style.transform = 'translate(-50%, -50%)';
        wrapper.style.width = '340px';
        wrapper.style.maxWidth = 'calc(100vw - 30px)';
        wrapper.style.background = '#ffffff';
        wrapper.style.border = '1px solid #e5e7eb';
        wrapper.style.borderRadius = '10px';
        wrapper.style.boxShadow = '0 12px 30px rgba(0,0,0,0.15)';
        wrapper.style.padding = '14px';
        wrapper.style.zIndex = '3000';

        wrapper.innerHTML = '' +
            '<div style="font-weight:600; margin-bottom:8px;">{{ __('ui.set_department_head') }}</div>' +
            '<div style="font-size:12px; color:#6b7280; margin-bottom:8px;">{{ __('ui.select_employee_or_none_to_clear') }}</div>' +
            '<select id="setHeadToastInput" class="form-control" style="margin-bottom:10px;"><option value="">{{ __('ui.none') }}</option></select>' +
            '<div id="setHeadToastError" style="display:none; color:#dc2626; font-size:12px; margin-bottom:8px;"></div>' +
            '<div style="display:flex; justify-content:flex-end; gap:8px;">' +
                '<button type="button" id="setHeadToastCancel" class="btn btn-default btn-sm">{{ __('ui.cancel') }}</button>' +
                '<button type="button" id="setHeadToastSave" class="btn btn-primary btn-sm">{{ __('ui.save') }}</button>' +
            '</div>';

        document.body.appendChild(wrapper);
        var input = document.getElementById('setHeadToastInput');
        if(input){
            employeeOptions.forEach(function(item){
                var opt = document.createElement('option');
                opt.value = item.id;
                opt.textContent = item.label;
                input.appendChild(opt);
            });
            input.value = current || '';
            input.focus();
        }

        function closePrompt(){
            var node = document.getElementById('setHeadToastPrompt');
            if(node){ node.remove(); }
        }

        document.getElementById('setHeadToastCancel').addEventListener('click', closePrompt);
        document.getElementById('setHeadToastSave').addEventListener('click', function(){
            var head = (document.getElementById('setHeadToastInput').value || '').trim();
            var err = document.getElementById('setHeadToastError');
            if(err){ err.style.display = 'none'; err.textContent = ''; }

            sendSetHead(id, head).then(function(data){
                if(data && data.success){
                    updateHeadRow(id, data);
                    closePrompt();
                    showToast("{{ __('ui.department_head_updated') }}", 'success');
                } else {
                    var msg = (data && data.message) ? data.message : "{{ __('ui.failed_to_set_department_head') }}";
                    if(err){ err.textContent = msg; err.style.display = 'block'; }
                    showToast(msg, 'error');
                }
            }).catch(function(){
                if(err){ err.textContent = "{{ __('ui.request_failed') }}"; err.style.display = 'block'; }
                showToast("{{ __('ui.request_failed') }}", 'error');
            });
        });
    }

    function updateHeadRow(id, data){
        var row = document.querySelector('tr[data-id="'+id+'"]');
        if(!row){ return; }

        var cell = row.querySelector('.head-cell');
        if(cell){ cell.textContent = data.employee_name || '-'; }

        var existingRemoveBtn = row.querySelector('.remove-head-btn');
        if(data.department_head){
            if(!existingRemoveBtn){
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-sm btn-warning remove-head-btn';
                btn.setAttribute('data-id', id);
                btn.textContent = "{{ __('ui.remove_head') }}";
                var end = row.querySelector('.text-end');
                var form = end ? end.querySelector('form') : null;
                if(end){
                    if(form) end.insertBefore(btn, form);
                    else end.appendChild(btn);
                }
            }
        } else if(existingRemoveBtn){
            existingRemoveBtn.remove();
        }
    }

    function openSetHead(id, current){
        var md = document.getElementById('modal_department_id'); if(md) md.value = id;
        var sel = document.getElementById('modal_department_head'); if(sel) sel.value = current || '';
        var err = document.getElementById('modalError'); if(err) err.classList.add('d-none');
        if(bsModal){
            bsModal.show();
            return;
        }

        if(typeof window.jQuery !== 'undefined' && setHeadModalEl && typeof window.jQuery.fn.modal === 'function'){
            window.jQuery(setHeadModalEl).modal('show');
            return;
        }

        openSetHeadToastInput(id, current);
    }

    function sendSetHead(id, head){
        var url = baseUrl + '/hrm/departments/' + id + '/head';
        return fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: new URLSearchParams({ department_head: head })
        }).then(function(r){ return r.json(); });
    }

    document.addEventListener('click', function(e){
        var t = e.target;
        if(t && t.classList.contains('set-head-btn')){
            openSetHead(t.getAttribute('data-id'), t.getAttribute('data-current'));
        }
    });

    document.getElementById('modalSaveHead').addEventListener('click', function(){
        var id = document.getElementById('modal_department_id').value;
        var head = document.getElementById('modal_department_head').value;
        var err = document.getElementById('modalError'); if(err) err.classList.add('d-none');
        sendSetHead(id, head).then(function(data){
            if(data && data.success){
                updateHeadRow(id, data);
                if(bsModal) {
                    bsModal.hide();
                } else if(typeof window.jQuery !== 'undefined' && setHeadModalEl && typeof window.jQuery.fn.modal === 'function') {
                    window.jQuery(setHeadModalEl).modal('hide');
                }
                showToast("{{ __('ui.department_head_updated') }}", 'success');
            } else {
                var msg = (data && data.message) ? data.message : "{{ __('ui.failed_to_set_department_head') }}";
                if(err){ err.textContent = msg; err.classList.remove('d-none'); }
                showToast(msg, 'error');
            }
        }).catch(function(){ if(err){ err.textContent = "{{ __('ui.request_failed') }}"; err.classList.remove('d-none'); } showToast("{{ __('ui.request_failed') }}", 'error'); });
    });

    function askRemoveHeadConfirm(){
        if(typeof Swal !== 'undefined' && typeof Swal.fire === 'function'){
            return Swal.fire({
                title: "{{ __('ui.remove_department_head') }}",
                text: "{{ __('ui.this_will_clear_the_current_department_head') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: "{{ __('ui.yes_remove') }}",
                cancelButtonText: "{{ __('ui.cancel') }}"
            }).then(function(result){
                return !!(result && result.isConfirmed);
            });
        }

        return new Promise(function(resolve){
            var existing = document.getElementById('removeHeadToastConfirm');
            if(existing){ existing.remove(); }

            var wrapper = document.createElement('div');
            wrapper.id = 'removeHeadToastConfirm';
            wrapper.style.position = 'fixed';
            wrapper.style.top = '50%';
            wrapper.style.left = '50%';
            wrapper.style.transform = 'translate(-50%, -50%)';
            wrapper.style.width = '340px';
            wrapper.style.maxWidth = 'calc(100vw - 30px)';
            wrapper.style.background = '#ffffff';
            wrapper.style.border = '1px solid #e5e7eb';
            wrapper.style.borderRadius = '10px';
            wrapper.style.boxShadow = '0 12px 30px rgba(0,0,0,0.15)';
            wrapper.style.padding = '14px';
            wrapper.style.zIndex = '3000';

            wrapper.innerHTML = '' +
                '<div style="font-weight:600; margin-bottom:8px;">{{ __('ui.remove_department_head') }}</div>' +
                '<div style="font-size:12px; color:#6b7280; margin-bottom:10px;">{{ __('ui.this_will_clear_the_current_department_head') }}</div>' +
                '<div style="display:flex; justify-content:flex-end; gap:8px;">' +
                    '<button type="button" id="removeHeadToastCancel" class="btn btn-default btn-sm">{{ __('ui.cancel') }}</button>' +
                    '<button type="button" id="removeHeadToastYes" class="btn btn-danger btn-sm">{{ __('ui.yes_remove') }}</button>' +
                '</div>';

            document.body.appendChild(wrapper);

            function closeAndResolve(val){
                var node = document.getElementById('removeHeadToastConfirm');
                if(node){ node.remove(); }
                resolve(!!val);
            }

            document.getElementById('removeHeadToastCancel').addEventListener('click', function(){ closeAndResolve(false); });
            document.getElementById('removeHeadToastYes').addEventListener('click', function(){ closeAndResolve(true); });
        });
    }

    function removeHeadHandler(e){
        var id = this.getAttribute('data-id');
        askRemoveHeadConfirm().then(function(confirmed){
            if(!confirmed){ return; }
            var url = baseUrl + '/hrm/departments/' + id + '/head';
            fetch(url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } }).then(function(r){ return r.json(); }).then(function(data){
                if(data && data.success){
                    var row = document.querySelector('tr[data-id="'+id+'"]'); if(row){ row.querySelector('.head-cell').textContent = '-'; var btn = row.querySelector('.remove-head-btn'); if(btn) btn.remove(); }
                    showToast("{{ __('ui.department_head_removed') }}", 'success');
                } else { showToast("{{ __('ui.failed_to_remove_head') }}", 'error'); }
            }).catch(function(){ showToast("{{ __('ui.request_failed') }}", 'error'); });
        });
    }

    document.addEventListener('click', function(e){ var t = e.target; if(t && t.classList.contains('remove-head-btn')){ removeHeadHandler.call(t, e); } });

});
</script>
@endpush

@endsection

