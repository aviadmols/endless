@props(['margin' => true])

@if (session('status'))
    <div class="alert alert--success" role="status" @style(['margin-block-end: 20px' => $margin])>{{ session('status') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert--error" role="alert" @style(['margin-block-end: 20px' => $margin])>
        @if ($errors->count() === 1)
            {{ $errors->first() }}
        @else
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
    </div>
@endif
