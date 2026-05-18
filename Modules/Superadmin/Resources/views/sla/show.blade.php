@extends('layouts.app')
@section('title', 'Service Level Agreement')

@section('content')
@include('superadmin::layouts.nav')

<section class="content-header no-print">
    <h1>
        <i class="fa fa-file-text-o"></i> Service Level Agreement
        <small>BreMac POS/ERP System</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ action([\Modules\Superadmin\Http\Controllers\SuperadminController::class, 'index']) }}"><i class="fa fa-dashboard"></i> Superadmin</a></li>
        <li class="active">SLA</li>
    </ol>
</section>

<section class="content">
    @include('layouts.partials.error_modal')

    @if(session('status'))
        @php $status = session('status'); @endphp
        <div class="alert alert-{{ $status['success'] ? 'success' : 'danger' }} alert-dismissible no-print">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ $status['msg'] }}
        </div>
    @endif

    {{-- Action bar --}}
    <div class="row no-print" style="margin-bottom:15px;">
        <div class="col-md-12">
            <a href="{{ route('superadmin.sla.edit') }}" class="btn btn-primary">
                <i class="fa fa-pencil"></i> Edit SLA
            </a>
            <button onclick="window.print()" class="btn btn-success">
                <i class="fa fa-print"></i> Print / Save as PDF
            </button>
            <a href="{{ url('SLA.pdf') }}" target="_blank" class="btn btn-default">
                <i class="fa fa-file-pdf-o"></i> Download PDF
            </a>
        </div>
    </div>

    {{-- SLA document --}}
    <div class="box box-solid" id="sla-document">
        <div class="box-body" style="padding:30px 40px;">

            {{-- Rendered markdown content --}}
            <div id="sla-body"></div>

            {{-- Signature table (always rendered from DB/form fields) --}}
            <hr>
            <h2>13. Review &amp; Acceptance</h2>
            <table class="table table-bordered" style="margin-top:15px;">
                <thead>
                    <tr>
                        <th style="width:30%">Role</th>
                        <th style="width:35%">Name &amp; Signature</th>
                        <th style="width:20%">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($defaultSigs as $sig)
                    <tr>
                        <td>{{ $sig['role'] }}</td>
                        <td style="min-height:50px;">
                            @if(!empty($sig['name']))
                                <strong>{{ $sig['name'] }}</strong>
                            @else
                                &nbsp;
                            @endif
                        </td>
                        <td>
                            @if(!empty($sig['date']))
                                {{ $sig['date'] }}
                            @else
                                &nbsp;
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <p class="text-muted" style="margin-top:20px;font-size:12px;">
                <em>This document is version-controlled in the system repository at <code>SLA.md</code>.</em>
            </p>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
(function () {
    // Strip the §13 signature section from content — we render it separately above
    var raw = @json($content);
    // Remove everything from "## 13." or "## 13 " onward
    var stripped = raw.replace(/^#{1,3}\s+13[\.\s][\s\S]*$/m, '').trim();
    document.getElementById('sla-body').innerHTML = marked.parse(stripped);
})();
</script>

<style>
    #sla-document table { width:100%; }
    #sla-document h1 { font-size:24px; border-bottom:2px solid #333; padding-bottom:8px; }
    #sla-document h2 { font-size:18px; margin-top:28px; border-bottom:1px solid #ccc; padding-bottom:4px; }
    #sla-document h3 { font-size:15px; margin-top:18px; }
    #sla-document table th { background:#f5f5f5; }
    #sla-document code { background:#f5f5f5; padding:2px 5px; border-radius:3px; }
    #sla-document pre  { background:#f5f5f5; padding:12px; border-radius:4px; }
    #sla-document blockquote { border-left:4px solid #ddd; padding-left:12px; color:#666; }

    @media print {
        .no-print, .main-sidebar, .main-header, .content-header { display:none !important; }
        .content-wrapper { margin-left:0 !important; }
        #sla-document { box-shadow:none !important; border:none !important; }
        #sla-document .box-body { padding:0 !important; }
        body { font-size:11pt; }
        h1 { font-size:20pt !important; }
        h2 { font-size:14pt !important; page-break-after:avoid; }
        table { page-break-inside:avoid; }
        @page { margin: 2cm; }
    }
</style>
@endsection
