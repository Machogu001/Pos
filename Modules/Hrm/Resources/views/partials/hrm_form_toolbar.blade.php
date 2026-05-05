<div class="d-flex justify-content-end mb-3">
    @if(!empty($cancelUrl))
        <a href="{{ $cancelUrl }}" class="btn btn-secondary mr-2">{{ $cancelLabel ?? 'Cancel' }}</a>
    @else
        <a href="{{ url()->previous() }}" class="btn btn-secondary mr-2">{{ $cancelLabel ?? 'Cancel' }}</a>
    @endif

    {{-- Save button: when included inside a <form> this will submit it. --}}
    <button type="submit" class="btn btn-primary">{{ $saveLabel ?? 'Save' }}</button>
</div>
