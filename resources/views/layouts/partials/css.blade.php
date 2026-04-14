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
