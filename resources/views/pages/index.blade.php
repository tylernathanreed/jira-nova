@extends('layouts.app')

@section('content')

	<div class="container">
		<swimlane :issues='{{ json_encode(array_values($issues)) }}'/>
	</div>

@endsection