@extends('layouts.app')

@section('content')
<div class="scene">
    <div class="scene__inner wrap wrap--panel">
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

            <nav class="panel-tabs" aria-label="ניווט ניהול">
                <a href="{{ route('admin.index') }}" @class(['is-active' => request()->routeIs('admin.index')])>סקירה</a>
                <a href="{{ route('admin.memorials.index') }}" @class(['is-active' => request()->routeIs('admin.memorials.*')])>עמודי הנצחה</a>
                <a href="{{ route('admin.users.index') }}" @class(['is-active' => request()->routeIs('admin.users.*')])>משתמשים</a>
                <a href="{{ route('admin.leads.index') }}" @class(['is-active' => request()->routeIs('admin.leads.*')])>פניות</a>
                <a href="{{ route('admin.settings.edit', 'general') }}" @class(['is-active' => request()->routeIs('admin.settings.*')])>הגדרות</a>
                @if (auth()->user()->primaryMemorial())
                    <a href="{{ route('dashboard.index') }}">האזור האישי שלי</a>
                @endif
            </nav>

            <div class="panel-body">
                <x-alerts />
                @yield('admin')
            </div>
        </div>
    </div>
</div>
@endsection
