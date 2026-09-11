@props(['religion' => 'jewish'])

@php($value = $religion instanceof \App\Enums\Religion ? $religion->value : $religion)

@switch($value)
    @case('jewish')
        {{-- The Endless star mark. --}}
        <img src="{{ asset('images/brand/star-of-david.svg') }}" alt="" {{ $attributes }}>
        @break

    @case('christian')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" {{ $attributes }}>
            <path d="M12 2v20M6 8h12"/>
        </svg>
        @break

    @case('muslim')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round" {{ $attributes }}>
            <path d="M16.5 3.6a9 9 0 1 0 3.4 14.7A7.2 7.2 0 0 1 16.5 3.6z"/>
        </svg>
        @break

    @case('druze')
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" {{ $attributes }}>
            <path d="M12 2.5l9.5 5.5v8L12 21.5 2.5 16V8z"/>
        </svg>
        @break

    @case('none')
        @break

    @default
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" {{ $attributes }}>
            <circle cx="12" cy="12" r="8"/>
        </svg>
@endswitch
