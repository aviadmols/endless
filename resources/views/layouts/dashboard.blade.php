@extends('layouts.app')

@section('content')
<div class="scene">
    <div class="scene__inner wrap wrap--panel">

        @php($memorial = $memorial ?? auth()->user()?->primaryMemorial())

        <div class="paper-card">
            <div class="panel-head">
                <div>
                    <h1>@yield('page-title')</h1>
                    @hasSection('page-sub')
                        <p class="sub">@yield('page-sub')</p>
                    @endif
                </div>
                @yield('page-actions')
            </div>

            @if ($memorial)
                <nav class="panel-tabs" aria-label="ניווט האזור האישי">
                    <a href="{{ route('dashboard.index') }}" @class(['is-active' => request()->routeIs('dashboard.index')])>סקירה</a>
                    <a href="{{ route('dashboard.memorial.edit') }}" @class(['is-active' => request()->routeIs('dashboard.memorial.*')])>עריכת העמוד</a>
                    <a href="{{ route('dashboard.memories.index') }}" @class(['is-active' => request()->routeIs('dashboard.memories.*')])>
                        זיכרונות
                        @php($pending = $memorial->pendingMemories()->count())
                        @if ($pending)<span class="count">{{ $pending }}</span>@endif
                    </a>
                    <a href="{{ route('dashboard.share') }}" @class(['is-active' => request()->routeIs('dashboard.share')])>שיתוף</a>
                    <a href="{{ route('dashboard.account.edit') }}" @class(['is-active' => request()->routeIs('dashboard.account.*')])>החשבון שלי</a>
                    <a href="{{ route('memorials.show', $memorial) }}" target="_blank" rel="noopener">צפייה בעמוד ↗</a>
                </nav>
            @endif

            <div class="panel-body">
                <x-alerts />
                @yield('dashboard')
            </div>
        </div>
    </div>
</div>
@endsection
