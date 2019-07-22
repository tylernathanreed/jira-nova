@extends('layouts.app')

@section('app')

	@include('partials.navbar')

	<main class="py-4">
	    @yield('content')
	</main>

@endsection