<link href="{{ asset('css/tailwind/app.css?v='.$asset_v) }}" rel="stylesheet">

<link rel="stylesheet" href="{{ asset('css/vendor.css?v='.$asset_v) }}">

@if( in_array(session()->get('user.language', config('app.locale')), config('constants.langs_rtl')) )
	<link rel="stylesheet" href="{{ asset('css/rtl.css?v='.$asset_v) }}">
@endif

@yield('css')

@yield('styles')

<!-- app css -->
<link rel="stylesheet" href="{{ asset('css/app.css?v='.$asset_v) }}">

@if(isset($pos_layout) && $pos_layout)
	<style type="text/css">
		.content{
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
	  background-image: url("{{asset('/img/pattern-directionicon-arrow.png')}}");
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

	.sidebar-child-link {
		display: flex;
		width: 100%;
		position: relative;
		z-index: 2;
		pointer-events: auto;
	}

	/*
	 * Print fix: prevent a blank leading page when printing receipts/reports.
	 * The app shell uses full-height flex containers (h-screen/h-full + overflow hidden),
	 * which can force the printable section onto the next page.
	 */
	@media print {
		@page {
			size: A4 portrait;
			margin: 10mm;
		}

		html,
		body {
			width: auto !important;
			height: auto !important;
			margin: 0 !important;
			padding: 0 !important;
		}

		.tw-h-screen,
		.tw-h-full,
		#scrollable-container {
			height: auto !important;
			min-height: 0 !important;
			max-height: none !important;
		}

		.tw-overflow-hidden,
		.tw-overflow-y-auto,
		#scrollable-container {
			overflow: visible !important;
		}

		#receipt_section.print_section {
			display: block !important;
			page-break-before: auto !important;
			break-before: auto !important;
			width: 100% !important;
			max-width: 100% !important;
			margin: 0 auto !important;
			padding: 0 !important;
			box-sizing: border-box !important;
			overflow: visible !important;
		}

		#receipt_section.print_section .row,
		#print_div .row {
			margin-left: 0 !important;
			margin-right: 0 !important;
		}

		#receipt_section.print_section .invoice-info::before,
		#receipt_section.print_section .invoice-info::after,
		#print_div .invoice-info::before,
		#print_div .invoice-info::after {
			content: " ";
			display: table;
		}

		#receipt_section.print_section .invoice-info::after,
		#print_div .invoice-info::after {
			clear: both;
		}

		#receipt_section.print_section [class*="col-"],
		#print_div [class*="col-"] {
			padding-left: 4px !important;
			padding-right: 4px !important;
			min-width: 0 !important;
		}

		#receipt_section.print_section .invoice-col.width-50,
		#receipt_section.print_section .width-50.f-left,
		#receipt_section.print_section .width-50.f-right,
		#print_div .invoice-col.width-50,
		#print_div .width-50.f-left,
		#print_div .width-50.f-right {
			float: left !important;
			width: 50% !important;
			max-width: 50% !important;
		}

		#receipt_section.print_section .width-100,
		#print_div .width-100 {
			width: 100% !important;
			max-width: 100% !important;
		}

		#receipt_section.print_section table,
		#print_div table {
			width: 100% !important;
			max-width: 100% !important;
			table-layout: fixed;
			border-collapse: collapse;
		}

		#receipt_section.print_section th,
		#receipt_section.print_section td,
		#print_div th,
		#print_div td {
			white-space: normal !important;
			word-break: break-word !important;
			overflow-wrap: anywhere !important;
			font-size: 11px !important;
			padding: 4px !important;
		}

		#receipt_section.print_section .font-30,
		#print_div .font-30 {
			font-size: 20px !important;
		}

		#receipt_section.print_section .font-23,
		#print_div .font-23 {
			font-size: 15px !important;
		}

		#receipt_section.print_section .pull-left,
		#print_div .pull-left {
			float: left !important;
			max-width: 48% !important;
		}

		#receipt_section.print_section img,
		#print_div img {
			max-width: 100% !important;
			height: auto !important;
		}
	}
</style>
@if(!empty($__system_settings['additional_css']))
    {!! $__system_settings['additional_css'] !!}
@endif


<!-- Ensure main content shifts when the admin sidebar is present so it does not sit underneath it
<style>
	/* The sidebar width (Tailwind tw-w-64 = 16rem). We keep it for reference in case
	   you want to use the full offset later, but default to a small visual gap so
	   the main content doesn't shift too far. */
	:root { --admin-sidebar-width: 16rem; --admin-sidebar-offset: 3.5rem; }

	/* Desktop and larger: apply a small left gap between sidebar and main content
	   instead of shifting the main area by the full sidebar width. This keeps the
	   layout centered while avoiding overlap. */
	@media (min-width: 992px) {
		/* Small visual separation when sidebar is visible */
		body:not(.sidebar-collapse) main {
			margin-left: var(--admin-sidebar-offset);
		}

		/* If the sidebar becomes absolutely positioned (small-view active), keep main
		   full-width and let the sidebar overlay without hiding content */
		.side-bar.small-view-side-active + main,
		.side-bar.small-view-side-active ~ main {
			margin-left: 0;
		}
	}

	/* When sidebar is collapsed, remove margin so content uses full width */
	body.sidebar-collapse main {
		margin-left: 0 !important;
	}

	/* Small screens: sidebar overlays content; remove offset */
	@media (max-width: 991px) {
		main { margin-left: 0 !important; }
	}
</style>
 -->
