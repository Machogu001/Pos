<link href="{{ asset('css/tailwind/app.css?v=' . $asset_v) }}" rel="stylesheet">

<link rel="stylesheet" href="{{ asset('css/vendor.css?v=' . $asset_v) }}">

@if (in_array(session()->get('user.language', config('app.locale')), config('constants.langs_rtl')))
    <link rel="stylesheet" href="{{ asset('css/rtl.css?v=' . $asset_v) }}">
@endif

@yield('css')

<!-- app css -->
<link rel="stylesheet" href="{{ asset('css/app.css?v=' . $asset_v) }}">

{{-- vue select style --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/vue-select/3.10.0/vue-select.min.css"
    integrity="sha512-p9Rnc/ifofEKT1/zS29PIGEYYzefru0lLszFCpKJUvlEEaR15qXkE220qFZcQ77useHPZ+++e7ufPqrb1Cbr7Q==" crossorigin="anonymous"
    referrerpolicy="no-referrer" />

@php
    $accounting_theme_asset = Module::asset('accounting:css/theme.custom.css');
    $accounting_theme_version = file_exists(public_path('modules/accounting/css/theme.custom.css'))
        ? filemtime(public_path('modules/accounting/css/theme.custom.css'))
        : $asset_v;
@endphp

<link rel="stylesheet" href="{{ $accounting_theme_asset . '?v=' . $accounting_theme_version }}">

<link rel="stylesheet" href="{{ Module::asset('accounting:css/plugins/bootstrap.custom.css') }}">

{{-- Accounting layout overrides — inlined to bypass any CDN/browser cache --}}
<style>
/* ── Accounting module-level toolbar ── */
.accounting-toolbar{margin:15px;border:1px solid #dfe6ef;border-radius:12px;box-shadow:0 8px 24px rgba(15,23,42,.06);}
.accounting-toolbar .container-fluid{padding:6px 10px;}
.accounting-toolbar .navbar-brand{font-weight:700;color:#17324d!important;}
.accounting-toolbar .navbar-nav{display:flex!important;flex-wrap:wrap;gap:4px;float:none!important;padding:6px 0;}
.accounting-toolbar .navbar-nav>li{float:none!important;}
.accounting-toolbar .navbar-nav>li>a{border-radius:999px;color:#35506b!important;font-weight:600;padding:7px 14px!important;line-height:1.4;}
.accounting-toolbar .navbar-nav>li.active>a,
.accounting-toolbar .navbar-nav>li>a:hover,
.accounting-toolbar .navbar-nav>li>a:focus{background:#eaf3ff!important;color:#0f4c81!important;}
/* ── Stat cards ── */
.accounting-stat-card{border-radius:16px;overflow:hidden;min-height:155px;box-shadow:0 10px 24px rgba(15,23,42,.12);}
.accounting-stat-card .inner{padding:22px 20px;}
.accounting-stat-card .inner h4{font-size:28px;margin-bottom:8px;}
.accounting-stat-card .inner p{font-size:15px;line-height:1.4;max-width:75%;}
.accounting-stat-card .icon{right:16px;top:18px;font-size:56px;opacity:.2;}
.accounting-stat-card .small-box-footer{padding:10px 16px;font-weight:600;}
/* ── Content panels ── */
.accounting-dashboard-page,.accounting-page{padding-top:4px;}
.accounting-dashboard-shell{padding:0 15px 15px;}
.accounting-dashboard-stats,.accounting-dashboard-panels{margin-bottom:10px;}
.accounting-dashboard-page .box,.accounting-page .box{border-radius:14px;overflow:hidden;box-shadow:0 8px 20px rgba(15,23,42,.06);}
.accounting-dashboard-page .box-header,.accounting-page .box-header{padding:14px 18px;}
.accounting-dashboard-page .box-body,.accounting-page .box-body{padding:18px;}
.accounting-page .table-responsive{overflow-x:auto;}
.accounting-page table th{white-space:nowrap;}
.accounting-page .pagination{margin:0;}
/* ── Mobile ── */
@media(max-width:767px){
  .accounting-toolbar{margin:8px;}
  .accounting-toolbar .navbar-nav{display:block!important;}
  .accounting-toolbar .navbar-nav>li>a{border-radius:8px;margin-bottom:4px;}
  .accounting-stat-card{min-height:130px;}
  .accounting-stat-card .inner p{max-width:100%;}
}
</style>

@if (isset($pos_layout) && $pos_layout)
    <style type="text/css">
        .content {
            padding-bottom: 0px !important;
        }

    </style>
@endif
<style type="text/css">
    /*
 * Pattern lock css
 * Pattern direction
 * http://ignitersworld.com/lab/patternLock.html
 */
    .patt-wrap {
        z-index: 10;
    }

    .patt-circ.hovered {
        background-color: #cde2f2;
        border: none;
    }

    .patt-circ.hovered .patt-dots {
        display: none;
    }

    .patt-circ.dir {
        background-image: url("{{ asset('/img/pattern-directionicon-arrow.png') }}");
        background-position: center;
        background-repeat: no-repeat;
    }

    .patt-circ.e {
        -webkit-transform: rotate(0);
        transform: rotate(0);
    }

    .patt-circ.s-e {
        -webkit-transform: rotate(45deg);
        transform: rotate(45deg);
    }

    .patt-circ.s {
        -webkit-transform: rotate(90deg);
        transform: rotate(90deg);
    }

    .patt-circ.s-w {
        -webkit-transform: rotate(135deg);
        transform: rotate(135deg);
    }

    .patt-circ.w {
        -webkit-transform: rotate(180deg);
        transform: rotate(180deg);
    }

    .patt-circ.n-w {
        -webkit-transform: rotate(225deg);
        transform: rotate(225deg);
    }

    .patt-circ.n {
        -webkit-transform: rotate(270deg);
        transform: rotate(270deg);
    }

    .patt-circ.n-e {
        -webkit-transform: rotate(315deg);
        transform: rotate(315deg);
    }

</style>
@if (!empty($__system_settings['additional_css']))
    {!! $__system_settings['additional_css'] !!}
@endif
