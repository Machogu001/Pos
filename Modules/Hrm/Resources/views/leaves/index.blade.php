@extends('layouts.app')

@section('content')
<div class="container py-4">
	<div class="card shadow-sm">
		<div class="card-header d-flex justify-content-between align-items-center">
			<h4 class="mb-0">Leaves</h4>
			<div>
				<a href="{{ route('hrm.leaves.create') }}" class="btn btn-primary">Create Leave</a>
			</div>
		</div>
		<div class="card-body">
			<div class="row mb-3">
				<div class="col-md-3">
					<label for="company_filter" class="form-label">Company</label>
					<select id="company_filter" class="form-select" onchange="applyFilters()">
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
					<small class="text-muted">Total leaves: {{ $totalRows ?? 0 }}</small>
				</div>
			</div>

			<div class="table-responsive">
				<table class="table table-hover align-middle">
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
								<td>{{ $l['employee_name'] ?? '-' }}</td>
								<td>{{ $l['leave_type_title'] ?? '-' }}</td>
								<td>{{ $l['start_date'] ?? '-' }}</td>
								<td>{{ $l['end_date'] ?? '-' }}</td>
								<td>{{ $l['days'] ?? 0 }}</td>
								<td>{{ ucfirst($l['status'] ?? '-') }}</td>
								<td class="text-end">
									<a href="{{ route('hrm.leaves.edit', $l['id']) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
									<button class="btn btn-sm btn-outline-danger" onclick="deleteLeave({{ $l['id'] }})">Delete</button>
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

			<div class="d-flex justify-content-between align-items-center mt-3">
				<div>
					<label class="me-2">Per page:</label>
					<select id="per_page" class="form-select d-inline-block" style="width:120px" onchange="changePerPage()">
						<option value="10">10</option>
						<option value="25">25</option>
						<option value="50">50</option>
						<option value="100">100</option>
						<option value="-1">All</option>
					</select>
				</div>

				<div>
					@if(isset($paginator))
						{{ $paginator->appends(request()->query())->links() }}
					@endif
				</div>
			</div>
		</div>
	</div>
</div>

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
		const per = document.getElementById('per_page').value;
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
		if (!confirm('Delete this leave?')) return;
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
	}

	// Preselect per-page from query string and filters
	(function() {
		const params = new URLSearchParams(window.location.search);
		const per = params.get('limit');
		const company = params.get('company_id');
		const search = params.get('search');
		if (per) document.getElementById('per_page').value = per;
		if (company) document.getElementById('company_filter').value = company;
		if (search) document.getElementById('search').value = search;
	})();
</script>
@endpush

@endsection
