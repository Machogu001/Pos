@extends('layouts.app')

@section('title', 'Leaves')

@section('content')
@include('hrm::partials.hrm_page_header', [
	'title' => 'Leaves',
	'subtitle' => 'Review leave applications, approvals, and employee time off at a glance.',
	'actions' => '<a href="'.route('hrm.leaves.create').'" class="btn btn-primary"><i class="fa fa-plus"></i> Create Leave</a>'
])

<section class="content">
	<div class="box box-primary">
		<div class="box-header with-border">
			<h2 class="box-title h3">Leave Register</h2>
			<div class="box-tools pull-right">
				<span class="label label-info">Total leaves: {{ $totalRows ?? 0 }}</span>
			</div>
		</div>
		<div class="box-body">
			<div class="row mb-3">
				<div class="col-md-3">
					<label for="company_filter" class="form-label">Company</label>
					<select id="company_filter" class="form-control" onchange="applyFilters()">
						<option value="">All companies</option>
						@if(isset($companies))
							@foreach($companies as $c)
								<option value="{{ $c->id }}">{{ $c->name }}</option>
							@endforeach
						@endif
					</select>
				</div>
				<div class="col-md-4">
					<label for="search" class="form-label">Search</label>
					<input id="search" class="form-control" placeholder="Search by employee, leave type, company or department" oninput="applyFilters()" />
				</div>
				<div class="col-md-5 d-flex align-items-end justify-content-end">
					<small class="text-muted">Classic list view with live filtering.</small>
				</div>
			</div>

			<div class="table-responsive">
				<table class="table table-hover table-striped align-middle">
					<thead class="table-light">
						<tr>
							<th>Employee</th>
							<th>Leave Type</th>
							<th>Start Date</th>
							<th>End Date</th>
							<th>Days</th>
							<th>Status</th>
							<th class="text-end">Actions</th>
						</tr>
					</thead>
					<tbody id="leaves_table_body">
						@forelse($leaves as $l)
							<tr>
								<td><strong>{{ $l['employee_name'] ?? '-' }}</strong></td>
								<td>{{ $l['leave_type_title'] ?? '-' }}</td>
								<td>{{ $l['start_date'] ?? '-' }}</td>
								<td>{{ $l['end_date'] ?? '-' }}</td>
								<td>{{ $l['days'] ?? 0 }}</td>
								<td>
									<span class="label label-default">{{ ucfirst($l['status'] ?? '-') }}</span>
								</td>
								<td class="text-end">
									<a href="{{ route('hrm.leaves.edit', $l['id']) }}" class="btn btn-sm btn-default">Edit</a>
									<button class="btn btn-sm btn-danger" onclick="deleteLeave({{ $l['id'] }})">Delete</button>
								</td>
							</tr>
						@empty
							<tr>
								<td colspan="7" class="text-center text-muted">No leaves found.</td>
							</tr>
						@endforelse
					</tbody>
				</table>
			</div>

			<div class="row" style="margin-top:15px;">
				<div class="col-md-6">
					<label for="leaves_per_page" class="me-2">Per page:</label>
					<select id="leaves_per_page" class="form-control d-inline-block" style="width:120px" onchange="changePerPage()" aria-label="Leaves per page">
						<option value="10">10</option>
						<option value="25">25</option>
						<option value="50">50</option>
						<option value="100">100</option>
						<option value="-1">All</option>
					</select>
				</div>
				<div class="col-md-6 text-right">
					@if(isset($paginator))
						{{ $paginator->appends(request()->query())->links() }}
					@endif
				</div>
			</div>
		</div>
	</div>
</section>

@push('scripts')
<script>
	let hrmLeaveTimer = null;

	function applyFilters() {
		const company = document.getElementById('company_filter')?.value;
		const search = document.getElementById('search')?.value;
		const params = new URLSearchParams(window.location.search);
		if (company) params.set('company_id', company); else params.delete('company_id');
		if (search) params.set('search', search); else params.delete('search');
		params.delete('page');

		if (hrmLeaveTimer) clearTimeout(hrmLeaveTimer);
		hrmLeaveTimer = setTimeout(() => {
			fetch(window.location.pathname + '?' + params.toString(), { headers: { 'Accept': 'application/json' } })
				.then(resp => {
					const ct = resp.headers.get('content-type') || '';
					if (ct.indexOf('application/json') === -1) { window.location.search = params.toString(); return null; }
					return resp.json();
				})
				.then(json => { if (!json) return; updateTable(json); })
				.catch(() => { window.location.search = params.toString(); });
		}, 300);
	}

	function changePerPage() {
		const per = document.getElementById('leaves_per_page').value;
		const params = new URLSearchParams(window.location.search);
		if (per) params.set('limit', per); else params.delete('limit');
		params.delete('page');
		window.location.search = params.toString();
	}

	function updateTable(json) {
		if (!json) return;
		const tbody = document.getElementById('leaves_table_body');
		tbody.innerHTML = '';
		if (!json.leaves || !json.leaves.length) {
			tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No leaves found.</td></tr>';
			return;
		}
		json.leaves.forEach(l => {
			const tr = document.createElement('tr');
			tr.innerHTML = `
				<td>${l.employee_name || '-'}</td>
				<td>${l.leave_type_title || '-'}</td>
				<td>${l.start_date || '-'}</td>
				<td>${l.end_date || '-'}</td>
				<td>${l.days || 0}</td>
				<td>${(l.status || '-')}</td>
				<td class="text-end">
					<a href="${window.location.pathname}/${l.id}/edit" class="btn btn-sm btn-outline-secondary">Edit</a>
					<button class="btn btn-sm btn-outline-danger" onclick="deleteLeave(${l.id})">Delete</button>
				</td>
			`;
			tbody.appendChild(tr);
		});
		// Update total display
		document.querySelectorAll('.text-muted').forEach(el => {
			if (el.textContent.trim().startsWith('Total leaves:')) {
				el.textContent = 'Total leaves: ' + (json.totalRows ?? 0);
			}
		});
	}

	function deleteLeave(id) {
		window.hrmConfirm('Delete this leave?', { title: 'Delete Leave', confirmButtonText: 'Delete' }).then(confirmed => {
			if (!confirmed) return;
			fetch(`${window.location.pathname}/${id}`, {
				method: 'POST',
				headers: {
					'X-CSRF-TOKEN': '{{ csrf_token() }}',
					'Accept': 'application/json'
				},
				body: new URLSearchParams({ _method: 'DELETE' })
			})
			.then(r => r.json())
			.then(json => {
				if (json && json.success) {
					if (window.toastr) { toastr.success('Deleted successfully'); }
					if (window.playSuccess) { window.playSuccess(); }
					applyFilters();
				} else {
					if (window.toastr) { toastr.error('Delete failed'); }
					if (window.playError) { window.playError(); }
				}
			})
			.catch(() => { if (window.toastr) { toastr.error('Delete failed'); } if (window.playError) { window.playError(); } });
		});
	}

	// Preselect per-page from query string and filters
	(function() {
		const params = new URLSearchParams(window.location.search);
		const per = params.get('limit');
		const company = params.get('company_id');
		const search = params.get('search');
		if (per) document.getElementById('leaves_per_page').value = per;
		if (company) document.getElementById('company_filter').value = company;
		if (search) document.getElementById('search').value = search;
	})();
</script>
@endpush

@endsection
