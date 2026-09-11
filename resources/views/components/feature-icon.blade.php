@props(['icon' => 'dove'])

@switch($icon)
    @case('heart')
        <svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M16 27S4 19.5 4 12.2A6.2 6.2 0 0 1 16 9.4 6.2 6.2 0 0 1 28 12.2C28 19.5 16 27 16 27z"/>
            <path d="M16 9.4V27"/>
        </svg>
        @break

    @case('cloud')
        <svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9.5 24a5.5 5.5 0 0 1-.3-11A8 8 0 0 1 24 13.6a5.2 5.2 0 0 1-.7 10.4z"/>
        </svg>
        @break

    @case('hand')
        <svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 18V7.5a1.8 1.8 0 0 1 3.6 0V16"/>
            <path d="M15.6 16V6a1.8 1.8 0 0 1 3.6 0v10"/>
            <path d="M19.2 16.5V9.5a1.8 1.8 0 0 1 3.6 0V20c0 4.4-3 7.5-7.2 7.5S8 24.4 8 20v-4a1.8 1.8 0 0 1 3.6 0"/>
            <path d="M25 5l2-2M27 9h3M23 3l.6-2"/>
        </svg>
        @break

    @case('album')
        <svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="5" y="6" width="22" height="20" rx="2.5"/>
            <circle cx="11.5" cy="12.5" r="2"/>
            <path d="M5 21l6-6 6 6 4-4 6 6"/>
        </svg>
        @break

    @case('shield')
        <svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M16 3l11 4v8.5C27 22.4 22.2 27.4 16 29 9.8 27.4 5 22.4 5 15.5V7z"/>
            <path d="M11.5 16.2l3.2 3.3 6-6.4"/>
        </svg>
        @break

    @default
        {{-- dove --}}
        <svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M27 6.5c-2.6 0-4.6 1.3-6.4 3.3-2 2.2-3.6 3.4-6.1 3.4-3.3 0-5.5 2-5.5 4.8 0 1.6.7 2.9 1.9 3.8-1.6.6-3 .6-4.4.2 1.2 3.4 4.4 5.6 8.2 5.6 5.6 0 9.6-4.3 9.6-10 0-1 .2-1.9.7-2.7l2-3.2z"/>
            <path d="M20.4 11.3c1.3.6 2.4 1.7 3 3.1"/>
        </svg>
@endswitch
