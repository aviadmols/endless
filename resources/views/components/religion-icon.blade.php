@props(['religion' => 'jewish'])

@php($value = $religion instanceof \App\Enums\Religion ? $religion->value : $religion)

{{-- The reference's line marks, re-drawn inline so they take `currentColor`. --}}
@switch($value)
    @case('jewish')
        <svg viewBox="0 0 342.6 387.94" fill="none" stroke="currentColor" stroke-width="18.52" stroke-miterlimit="10" {{ $attributes }}>
            <polygon points="16.32 277.31 326.28 277.31 171.3 18.05 16.32 277.31"/>
            <polygon points="171.3 369.9 326.28 110.64 16.32 110.64 171.3 369.9"/>
        </svg>
        @break

    @case('christian')
        <svg viewBox="0 0 296.3 407.41" fill="none" stroke="currentColor" stroke-width="18.52" stroke-miterlimit="10" {{ $attributes }}>
            <line x1="148.15" x2="148.15" y2="407.41"/>
            <line x1="296.3" y1="148.15" y2="148.15"/>
        </svg>
        @break

    @case('muslim')
        <svg viewBox="0 0 357.48 370.37" fill="none" stroke="currentColor" stroke-width="18.52" stroke-miterlimit="10" {{ $attributes }}>
            <polygon points="311.81 200.38 351.85 169.75 302.33 169.75 287.04 120.37 271.75 169.75 222.22 169.75 262.26 200.38 246.91 250 287.04 219.31 327.16 250 311.81 200.38"/>
            <path d="M129.63,185.19c0-79.96,59.66-145.83,136.87-155.92-24.33-12.72-51.96-20.01-81.31-20.01C88.02,9.26,9.26,88.02,9.26,185.19s78.76,175.93,175.93,175.93c29.36,0,56.98-7.29,81.31-20.01-77.21-10.09-136.87-75.96-136.87-155.92Z"/>
        </svg>
        @break

    @case('other')
        {{-- "Unaffiliated" on the reference: five figures, no creed. --}}
        <svg viewBox="0 0 351.85 342.59" fill="none" stroke="currentColor" stroke-width="18.52" stroke-miterlimit="10" {{ $attributes }}>
            <circle cx="175.94" cy="55.56" r="46.3"/>
            <circle cx="296.3" cy="148.15" r="46.3"/>
            <circle cx="55.56" cy="148.15" r="46.3"/>
            <circle cx="101.85" cy="287.04" r="46.3"/>
            <circle cx="250" cy="287.04" r="46.3"/>
        </svg>
        @break

    @case('druze')
        {{-- The reference has no Druze mark; this one is drawn to the same weight. --}}
        <svg viewBox="0 0 342.6 342.6" fill="none" stroke="currentColor" stroke-width="18.52" stroke-miterlimit="10" {{ $attributes }}>
            <polygon points="171.3 18.05 302.6 93.83 302.6 245.37 171.3 321.15 40 245.37 40 93.83 171.3 18.05"/>
        </svg>
        @break

    @case('none')
        @break

    @default
        <svg viewBox="0 0 342.6 342.6" fill="none" stroke="currentColor" stroke-width="18.52" stroke-miterlimit="10" {{ $attributes }}>
            <circle cx="171.3" cy="171.3" r="153.25"/>
        </svg>
@endswitch
