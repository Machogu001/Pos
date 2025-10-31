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
                    <tbody>
                        @forelse($departments as $dept)
                            <tr data-id="{{ $dept->id }}">
                                <td>{{ $dept->id }}</td>
                                <td>{{ $dept->department ?? $dept->name ?? '-' }}</td>
                                <td>{{ $dept->company_name ?? ($dept->company->name ?? '-') }}</td>
                                <td class="head-cell">
                                    @php
                                        $emp = $dept->employee ?? null;
                                        $empName = $dept->employee_head ?? ($emp->username ?? (isset($emp->firstname) ? trim($emp->firstname . ' ' . ($emp->lastname ?? '')) : null));
                                    @endphp
                                    {{ $empName ?? '-' }}
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('hrm.departments.edit', $dept->id) }}" class="btn btn-sm btn-secondary">Edit</a>
                                    <button type="button" class="btn btn-sm btn-info set-head-btn" data-id="{{ $dept->id }}" data-current="{{ $dept->department_head ?? '' }}">Set Head</button>
                                    @if($dept->department_head)
                                        <button type="button" class="btn btn-sm btn-warning remove-head-btn" data-id="{{ $dept->id }}">Remove Head</button>
                                    @endif
                                    <form action="{{ route('hrm.departments.destroy', $dept->id) }}" method="POST" style="display:inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</button>
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
</div>
@endsection

            @push('scripts')
            <script>
            document.addEventListener('DOMContentLoaded', function(){
                    var csrf = '{{ csrf_token() }}';

                    // Create modal markup
                    var modalHtml = `
                    <div class="modal fade" id="setHeadModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Set Department Head</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
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
                    var bsModal = setHeadModalEl ? new bootstrap.Modal(setHeadModalEl) : null;

                    // Open modal when Set Head clicked
                    document.querySelectorAll('.set-head-btn').forEach(function(btn){
                            btn.addEventListener('click', function(){
                                    var id = this.getAttribute('data-id');
                                    var current = this.getAttribute('data-current') || '';
                                    document.getElementById('modal_department_id').value = id;
                                    var sel = document.getElementById('modal_department_head');
                                    if(sel) sel.value = current;
                                    if(bsModal) bsModal.show();
                            });
                    });

                    // Save head via AJAX
                    document.getElementById('modalSaveHead').addEventListener('click', function(){
                            var id = document.getElementById('modal_department_id').value;
                            var head = document.getElementById('modal_department_head').value;
                            fetch('/hrm/departments/' + id + '/head', {
                                    method: 'POST',
                                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                                    body: new URLSearchParams({ department_head: head })
                            }).then(function(r){ return r.json(); }).then(function(data){
                                    if(data && data.success){
                                            var row = document.querySelector('tr[data-id="'+id+'"]');
                                            if(row){
                                                    var cell = row.querySelector('.head-cell');
                                                    cell.textContent = data.employee_name || '-';
                                                    // show remove button if not present
                                                    if(data.department_head){
                                                            if(!row.querySelector('.remove-head-btn')){
                                                                    var btn = document.createElement('button'); btn.type='button'; btn.className='btn btn-sm btn-warning remove-head-btn'; btn.setAttribute('data-id', id); btn.textContent='Remove Head';
                                                                    row.querySelector('.text-end').insertBefore(btn, row.querySelector('.text-end').children[ row.querySelector('.text-end').children.length - 1 ]);
                                                                    btn.addEventListener('click', removeHeadHandler);
                                                            }
                                                    }
                                            }
                                            if(bsModal) bsModal.hide();
                                    } else {
                                            alert('Failed to set department head');
                                    }
                            }).catch(function(){ alert('Request failed'); });
                    });

                    // Remove head handler
                    function removeHeadHandler(e){
                            var id = this.getAttribute('data-id');
                            if(!confirm('Remove department head?')) return;
                            fetch('/hrm/departments/' + id + '/head', {
                                    method: 'DELETE',
                                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
                            }).then(function(r){ return r.json(); }).then(function(data){
                                    if(data && data.success){
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
