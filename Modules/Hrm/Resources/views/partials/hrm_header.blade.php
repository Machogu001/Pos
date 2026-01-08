<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">{{ $title ?? '' }}</h2>
        @if(!empty($subtitle))
            <div class="text-muted small">{{ $subtitle }}</div>
        @endif
    </div>
    <div>
        {{-- actions may contain raw HTML for buttons/links --}}
        {!! $actions ?? '' !!}
    </div>
</div>
