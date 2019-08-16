@extends('nova::auth.layout')

@section('content')

    <div class="mx-auto py-8 max-w-sm text-center text-90 flex items-center justify-center">
        @include('nova::partials.logo')
    </div>

<form
    class="bg-white shadow rounded-lg p-8 max-w-login mx-auto"
    method="POST"
    action="{{ route('nova.login') }}"
>
    {{ csrf_field() }}

    @component('nova::auth.partials.heading')
        {{ __('Welcome Back!') }}
    @endcomponent

    @if($errors->any())
        <p class="text-center font-semibold text-danger my-3">
            @if ($errors->has('email_address'))
                {{ $errors->first('email_address') }}
            @else
                {{ $errors->first('password') }}
            @endif
        </p>
    @endif

    <div class="mb-6 {{ $errors->has('email') ? ' has-error' : '' }}">
        <label class="block font-bold mb-2" for="email_address">{{ __('Email Address') }}</label>
        <input class="form-control form-input form-input-bordered w-full" id="email" type="email" name="email_address" value="{{ old('email') }}" required autofocus>
    </div>

    <div class="mb-6 {{ $errors->has('password') ? ' has-error' : '' }}">
        <label class="block font-bold mb-2 flex" for="password">
            <span class="flex-1">
                {{ __('Password') }}
            </span>
            <a href="https://confluence.atlassian.com/cloud/api-tokens-938839638.html" target="_blank" class="no-underline">
                Need an API token?
            </a>
        </label>
        <input class="form-control form-input form-input-bordered w-full" id="password" type="password" name="password" required>
    </div>



    <button class="w-full btn btn-default btn-primary hover:bg-primary-dark" type="submit">
        {{ __('Login') }}
    </button>
</form>
@endsection
