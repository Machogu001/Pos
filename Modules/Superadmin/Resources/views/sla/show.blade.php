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
    @include('layouts.partials.error')

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
            <button onclick="printSLA()" class="btn btn-success">
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
            <div class="signature-section">
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
            </div>{{-- /.signature-section --}}
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

function printSLA() {
    var content = document.getElementById('sla-document').innerHTML;
    var win = window.open('', '_blank', 'width=900,height=700');
    win.document.write('<!DOCTYPE html><html><head><meta charset="utf-8">' +
        '<title>Service Level Agreement — BreMac POS/ERP</title>' +
        '<style>' +
        '  @page { margin:2cm; size:A4 portrait; }' +
        '  * { box-sizing:border-box; }' +
        '  body { font-family:Georgia,"Times New Roman",serif; font-size:11pt; color:#000; margin:0; padding:0; }' +
        '  h1 { font-size:20pt; border-bottom:2pt solid #000; padding-bottom:6pt; page-break-after:avoid; }' +
        '  h2 { font-size:14pt; margin-top:18pt; border-bottom:1pt solid #999; padding-bottom:3pt; page-break-after:avoid; }' +
        '  h3 { font-size:12pt; margin-top:12pt; page-break-after:avoid; }' +
        '  h1+*, h2+*, h3+* { page-break-before:avoid; }' +
        '  p, li { orphans:3; widows:3; line-height:1.6; }' +
        '  table { width:100%; border-collapse:collapse; margin:10pt 0; }' +
        '  thead { display:table-header-group; }' +
        '  tr { page-break-inside:avoid; }' +
        '  td, th { padding:5pt 7pt; border:1pt solid #999; font-size:10pt; }' +
        '  th { background:#f0f0f0; font-weight:bold; }' +
        '  code { background:#f5f5f5; padding:1pt 4pt; font-size:9pt; }' +
        '  pre  { background:#f5f5f5; padding:8pt; font-size:9pt; overflow:visible; white-space:pre-wrap; }' +
        '  blockquote { border-left:3pt solid #ccc; padding-left:10pt; color:#444; margin:8pt 0; }' +
        '  hr { border:none; border-top:1pt solid #ccc; margin:14pt 0; }' +
        '  .signature-section { page-break-inside:avoid; }' +
        '  .box-body { padding:0; }' +
        '</style>' +
        '</head><body>' + content + '</body></html>');
    win.document.close();
    win.focus();
    // Wait for content to render before printing
    win.onload = function() { win.print(); };
    setTimeout(function() { if (!win.closed) win.print(); }, 800);
}
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

    @media print { body { display:none !important; } }
</style>
@endsection
