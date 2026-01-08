@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Departments</h2>
        <a href="{{ route('hrm.departments.create') }}" class="btn btn-primary">Create Department</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <p class="text-muted">Manage departments and assign department heads.</p>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Department</th>
                            <th>Company</th>
                            <th>Head</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($departments as $d)
                            @php
                                $head = null;
                                if(isset($employees) && $employees instanceof \Illuminate\Support\Collection){
                                    $head = $employees->firstWhere('id', $d->department_head);
                                }
                                if(!$head && isset($d->employee_head)){
                                    $head = $d->employee_head;
                                }
                                $headLabel = '-';
                                if($head){
                                    $headLabel = $head->username ?? trim((($head->firstname ?? '') . ' ' . ($head->lastname ?? '')));
                                }
                            @endphp
                            <tr data-id="{{ $d->id }}">
                                <td>{{ $d->id }}</td>
                                <td>{{ $d->department }}</td>
                                <td>{{ optional($d->business)->name ?? ($d->business_id ?? '-') }}</td>
                                <td class="head-cell">{{ $headLabel }}</td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-primary set-head-btn" data-id="{{ $d->id }}" data-current="{{ $d->department_head ?? '' }}">Set Head</button>
                                    @if($d->department_head)
                                        <button type="button" class="btn btn-sm btn-warning remove-head-btn" data-id="{{ $d->id }}">Remove Head</button>
                                    @endif
                                    <a href="{{ route('hrm.departments.edit', $d->id) }}" class="btn btn-sm btn-secondary">Edit</a>
                                    <form action="{{ route('hrm.departments.destroy', $d->id) }}" method="POST" style="display:inline-block" onsubmit="return confirm('Delete this department?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">No departments found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if(isset($totalRows) && $totalRows > (int)($perPage ?? 0) && isset($paginator))
        <div class="mt-3">
            {{ $paginator->appends(request()->query())->links() }}
        </div>
    @endif
</div>

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
                    <h5 class="modal-title">Set Department Head</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="modalError" class="alert alert-danger d-none"></div>
                    <form id="setHeadForm">
                        <input type="hidden" name="department_id" id="modal_department_id" />
                        <div class="mb-3">
                            <label for="modal_department_head" class="form-label">Select Employee</label>
                            <select id="modal_department_head" name="department_head" class="form-control">
                                <option value="">-- None --</option>
                                @foreach($employees as $e)
                                    @php $empLabel = $e->username ?? trim((($e->firstname ?? '') . ' ' . ($e->lastname ?? ''))); @endphp
                                    <option value="{{ $e->id }}">{{ $empLabel ?: 'Employee #'.$e->id }}</option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="modalSaveHead" class="btn btn-primary">Save</button>
                </div>
            </div>
        </div>
    </div>`;

    document.body.insertAdjacentHTML('beforeend', modalHtml);
    var setHeadModalEl = document.getElementById('setHeadModal');
    var bsModal = (typeof bootstrap !== 'undefined' && setHeadModalEl) ? new bootstrap.Modal(setHeadModalEl) : null;

    function openSetHead(id, current){
        var md = document.getElementById('modal_department_id'); if(md) md.value = id;
        var sel = document.getElementById('modal_department_head'); if(sel) sel.value = current || '';
        var err = document.getElementById('modalError'); if(err) err.classList.add('d-none');
        if(bsModal){ bsModal.show(); } else { var val = prompt('Enter employee id to set as head (or leave empty to clear):', current || ''); if(val !== null){ sendSetHead(id, val); } }
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
                var row = document.querySelector('tr[data-id="'+id+'"]');
                if(row){
                    var cell = row.querySelector('.head-cell');
                    cell.textContent = data.employee_name || '-';
                    // add remove button if needed
                    if(data.department_head){
                        if(!row.querySelector('.remove-head-btn')){
                            var btn = document.createElement('button'); btn.type='button'; btn.className='btn btn-sm btn-warning remove-head-btn'; btn.setAttribute('data-id', id); btn.textContent='Remove Head';
                            var end = row.querySelector('.text-end');
                            // insert before the delete form if present
                            var form = end.querySelector('form');
                            if(form) end.insertBefore(btn, form); else end.appendChild(btn);
                            btn.addEventListener('click', removeHeadHandler);
                        }
                    }
                }
                if(bsModal) bsModal.hide();
                showToast('Department head updated', 'success');
            } else {
                var msg = (data && data.message) ? data.message : 'Failed to set department head';
                if(err){ err.textContent = msg; err.classList.remove('d-none'); }
                showToast(msg, 'error');
            }
        }).catch(function(){ if(err){ err.textContent = 'Request failed'; err.classList.remove('d-none'); } showToast('Request failed', 'error'); });
    });

    function removeHeadHandler(e){
        var id = this.getAttribute('data-id');
        if(!confirm('Remove department head?')) return;
        var url = baseUrl + '/hrm/departments/' + id + '/head';
        fetch(url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } }).then(function(r){ return r.json(); }).then(function(data){
            if(data && data.success){
                var row = document.querySelector('tr[data-id="'+id+'"]'); if(row){ row.querySelector('.head-cell').textContent = '-'; var btn = row.querySelector('.remove-head-btn'); if(btn) btn.remove(); }
                showToast('Department head removed', 'success');
            } else { showToast('Failed to remove head', 'error'); }
        }).catch(function(){ showToast('Request failed', 'error'); });
    }

    document.addEventListener('click', function(e){ var t = e.target; if(t && t.classList.contains('remove-head-btn')){ removeHeadHandler.call(t, e); } });

});
</script>
@endpush

@endsection

