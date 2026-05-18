@extends('layouts.app')
@section('title', 'Edit SLA')

@section('content')
@include('superadmin::layouts.nav')

<section class="content-header">
    <h1>
        <i class="fa fa-pencil"></i> Edit Service Level Agreement
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ action([\Modules\Superadmin\Http\Controllers\SuperadminController::class, 'index']) }}"><i class="fa fa-dashboard"></i> Superadmin</a></li>
        <li><a href="{{ route('superadmin.sla.show') }}">SLA</a></li>
        <li class="active">Edit</li>
    </ol>
</section>

<section class="content">

    <form action="{{ route('superadmin.sla.update') }}" method="POST">
        @csrf

        {{-- SLA Content --}}
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">SLA Content <small>(Markdown)</small></h3>
                <div class="box-tools">
                    <button type="button" class="btn btn-xs btn-default" id="toggle-preview">
                        <i class="fa fa-eye"></i> Preview
                    </button>
                </div>
            </div>
            <div class="box-body">
                <div class="alert alert-info" style="font-size:12px;padding:8px 15px;">
                    <i class="fa fa-info-circle"></i>
                    Write in <strong>Markdown</strong>. Use <code>## Heading</code>, <code>| Table |</code>, <code>**bold**</code>.
                    The signature section (§13) below is managed separately.
                </div>
                <textarea name="sla_content" id="sla-content" class="form-control"
                          rows="35" style="font-family:monospace;font-size:13px;">{{ old('sla_content', $content) }}</textarea>
                @error('sla_content')
                    <span class="text-danger">{{ $message }}</span>
                @enderror

                {{-- Live preview pane --}}
                <div id="sla-preview" style="display:none;margin-top:20px;padding:20px;border:1px solid #ddd;border-radius:4px;background:#fff;"></div>
            </div>
        </div>

        {{-- Signature Section --}}
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-pencil-square-o"></i> §13 — Review &amp; Acceptance Signatures</h3>
            </div>
            <div class="box-body">
                <p class="text-muted">Fill in the signatory details. These appear on the printed SLA.</p>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="width:30%">Role</th>
                            <th style="width:40%">Full Name <small class="text-muted">(person signing)</small></th>
                            <th style="width:30%">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($defaultSigs as $i => $sig)
                        <tr>
                            <td>
                                <strong>{{ $sig['role'] }}</strong>
                                <input type="hidden" name="signatures[{{ $i }}][role]" value="{{ $sig['role'] }}">
                            </td>
                            <td>
                                <input type="text"
                                       name="signatures[{{ $i }}][name]"
                                       class="form-control"
                                       placeholder="Full name"
                                       value="{{ old("signatures.{$i}.name", $sig['name'] ?? '') }}">
                            </td>
                            <td>
                                <input type="date"
                                       name="signatures[{{ $i }}][date]"
                                       class="form-control"
                                       value="{{ old("signatures.{$i}.date", $sig['date'] ?? '') }}">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Actions --}}
        <div class="box box-solid">
            <div class="box-body">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fa fa-save"></i> Save SLA
                </button>
                <a href="{{ route('superadmin.sla.show') }}" class="btn btn-default btn-lg">
                    <i class="fa fa-times"></i> Cancel
                </a>
            </div>
        </div>

    </form>
</section>
@endsection

@section('javascript')
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
document.getElementById('toggle-preview').addEventListener('click', function () {
    var preview = document.getElementById('sla-preview');
    var content = document.getElementById('sla-content').value;
    if (preview.style.display === 'none') {
        preview.innerHTML = marked.parse(content);
        preview.style.display = 'block';
        this.innerHTML = '<i class="fa fa-code"></i> Hide Preview';
    } else {
        preview.style.display = 'none';
        this.innerHTML = '<i class="fa fa-eye"></i> Preview';
    }
});
</script>

<style>
    #sla-preview h1 { font-size:22px; border-bottom:2px solid #333; padding-bottom:6px; }
    #sla-preview h2 { font-size:17px; border-bottom:1px solid #ccc; padding-bottom:4px; margin-top:24px; }
    #sla-preview table { width:100%; border-collapse:collapse; margin:12px 0; }
    #sla-preview table th, #sla-preview table td { border:1px solid #ddd; padding:6px 10px; }
    #sla-preview table th { background:#f5f5f5; }
    #sla-preview code { background:#f5f5f5; padding:2px 5px; border-radius:3px; }
    #sla-preview pre  { background:#f5f5f5; padding:12px; border-radius:4px; }
</style>
@endsection
