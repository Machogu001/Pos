@extends('errors::minimal')

@section('title', __('Access Denied'))
@section('code', '403')
@section('message')
	<div style="display:flex;align-items:center;justify-content:center;min-height:60vh;">
		<div style="background:#fff;padding:1.25rem 1.5rem;border-radius:12px;box-shadow:0 10px 25px rgba(0,0,0,0.08);max-width:520px;width:100%;text-align:center;border:1px solid #e5e7eb;">
			<div style="font-size:1.25rem;font-weight:600;color:#111827;margin-bottom:0.5rem;">{{ __('Access to this module is restricted') }}</div>
			<p style="color:#4b5563;margin-bottom:1rem;">{{ __('You don\'t have permission to access this feature. Please contact an administrator to update your role permissions.') }}</p>
			<button onclick="window.history.back()" style="background:#2563eb;color:#fff;padding:0.5rem 0.75rem;border-radius:8px;border:none;cursor:pointer;font-weight:600;">{{ __('OK') }}</button>
		</div>
	</div>
@endsection
