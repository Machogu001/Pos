@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Departments</h2>
        <a href="{{ route('hrm.departments.create') }}" class="btn btn-primary">Create Department</a>
    </div>

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
                                @push('scripts')
                                <script>
                                document.addEventListener('DOMContentLoaded', function(){
                                                var csrf = '{{ csrf_token() }}';
                                                var baseUrl = '{{ url("") }}';

                                                // helper: simple toast
                                                function showToast(message, type){
                                                        type = type || 'success';
                                                        var toast = document.createElement('div');
                                                        toast.className = 'alert alert-' + (type === 'error' ? 'danger' : type) + ' position-fixed';
                                                        toast.style.right = '20px'; toast.style.top = '20px'; toast.style.zIndex = 2000;
                                                        toast.textContent = message;
                                                        document.body.appendChild(toast);
                                                        setTimeout(function(){ toast.style.opacity = '0'; setTimeout(function(){ toast.remove(); }, 300); }, 3000);
                                                }

                                                // Create modal markup (with inline error area)
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

                                                // attach Set Head handlers
                                                function openSetHead(id, current){
                                                        document.getElementById('modal_department_id').value = id;
                                                        var sel = document.getElementById('modal_department_head'); if(sel) sel.value = current || '';
                                                        var err = document.getElementById('modalError'); if(err) err.classList.add('d-none');
                                                        if(bsModal){ bsModal.show(); } else { /* fallback: prompt for id */
                                                                var val = prompt('Enter employee id to set as head (or leave empty to clear):', current || '');
                                                                if(val !== null){ sendSetHead(id, val); }
                                                        }
                                                }

                                                document.querySelectorAll('.set-head-btn').forEach(function(btn){ btn.addEventListener('click', function(){ openSetHead(this.getAttribute('data-id'), this.getAttribute('data-current') || ''); }); });

                                                // send set head request
                                                function sendSetHead(id, head){
                                                        var url = baseUrl + '/hrm/departments/' + id + '/head';
                                                        return fetch(url, {
                                                                method: 'POST',
                                                                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                                                                body: new URLSearchParams({ department_head: head })
                                                        }).then(function(r){ return r.json(); });
                                                }

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
                                                                                // show remove button
                                                                                if(data.department_head){
                                                                                        if(!row.querySelector('.remove-head-btn')){
                                                                                                var btn = document.createElement('button'); btn.type='button'; btn.className='btn btn-sm btn-warning remove-head-btn'; btn.setAttribute('data-id', id); btn.textContent='Remove Head';
                                                                                                // insert before last form/button
                                                                                                var end = row.querySelector('.text-end'); end.insertBefore(btn, end.querySelector('form'));
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
                                                        }).catch(function(e){
                                                                if(err){ err.textContent = 'Request failed'; err.classList.remove('d-none'); }
                                                                showToast('Request failed', 'error');
                                                        });
                                                });

                                                // Remove head handler
                                                function removeHeadHandler(e){
                                                        var id = this.getAttribute('data-id');
                                                        if(!confirm('Remove department head?')) return;
                                                        var url = baseUrl + '/hrm/departments/' + id + '/head';
                                                        fetch(url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } }).then(function(r){ return r.json(); }).then(function(data){
                                                                if(data && data.success){
                                                                        var row = document.querySelector('tr[data-id="'+id+'"]');
                                                                        if(row){ row.querySelector('.head-cell').textContent = '-'; var btn = row.querySelector('.remove-head-btn'); if(btn) btn.remove(); }
                                                                        showToast('Department head removed', 'success');
                                                                } else { showToast('Failed to remove head', 'error'); }
                                                        }).catch(function(){ showToast('Request failed', 'error'); });
                                                }

                                                document.querySelectorAll('.remove-head-btn').forEach(function(b){ b.addEventListener('click', removeHeadHandler); });

                                });
                                </script>
                                @endpush
                                            var row = document.querySelector('tr[data-id="'+id+'"]');
                                            if(row){
                                                    var cell = row.querySelector('.head-cell');
                                                    cell.textContent = '-';
                                                    var btn = row.querySelector('.remove-head-btn'); if(btn) btn.remove();
                                            }
                                    } else {
                                            alert('Failed to remove head');
                                    }
                            }).catch(function(){ alert('Request failed'); });
                    }

                    document.querySelectorAll('.remove-head-btn').forEach(function(b){ b.addEventListener('click', removeHeadHandler); });

            });
            </script>
            @endpush
