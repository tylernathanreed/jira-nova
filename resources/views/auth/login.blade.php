@extends('layouts.app', [
    'body' => [
        'class' => 'bg-40 text-black h-full'
    ]
])

@push('styles')
    <link rel="stylesheet" href="{{ mix('app.css', 'vendor/nova') }}">
@endpush

@section('app')

    <div class="px-view py-view mx-auto">
        <div class="mx-auto py-8 max-w-sm text-center text-90 flex items-center justify-center">
            <i class="fab fa-jira fa-fw fa-2x pr-4"></i> <span class="text-2xl"><b>Jira</b> Issue Management</span>
        </div>

        <form
            class="bg-white shadow rounded-lg p-8 max-w-login mx-auto"
            method="POST"
            action="{{ route('login') }}"
        >
            {{ csrf_field() }}

            <h2 class="text-2xl text-center font-normal mb-6 text-90">
                {{ __('Welcome Back!') }}
            </h2>

            <svg class="block mx-auto mb-6" xmlns="http://www.w3.org/2000/svg" width="100" height="2" viewBox="0 0 100 2">
                <g id="Page-1" fill="none" fill-rule="evenodd">
                    <g id="08-login" fill="#D8E3EC" transform="translate(-650 -371)">
                        <path id="Rectangle-15" d="M650 371h100v2H650z"/>
                    </g>
                </g>
            </svg>

            @if($errors->any())
                <p class="text-center font-semibold text-danger my-3">
                    @if($errors->has('email_address'))
                        {{ $errors->first('email_address') }}
                    @else
                        {{ $errors->first('password') }}
                    @endif
                </p>
            @endif

            <div class="mb-6 {{ $errors->has('email') ? ' has-error' : '' }}">
                <label class="block font-bold mb-2" for="email_address">{{ __('Email Address') }}</label>
                <input class="form-control form-input form-input-bordered w-full" id="email_address" type="email" name="email_address" value="{{ old('email') }}" required autofocus>
            </div>

            <div class="mb-6 {{ $errors->has('password') ? ' has-error' : '' }}">
                <label class="block font-bold mb-2" for="password">{{ __('Password') }}</label>
                <input class="form-control form-input form-input-bordered w-full" id="password" type="password" name="password" required>
            </div>

            <button class="w-full btn btn-default btn-primary hover:bg-primary-dark py-0" type="submit">
                {{ __('Login') }}
            </button>
        </form>
    </div>

@endsection