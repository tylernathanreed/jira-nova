@extends('layouts.app')

@section('content')

	<div class="container">
		<form id="form" method="POST">
			{{ csrf_field() }}

			<div class="form-group">
				<swimlane :issues='{{ json_encode(array_values($issues)) }}'/>
			</div>

			<div class="row justify-content-end">
				<div class="col-md-3 col-sm-6 col-xs-12">
					<button type="submit" class="btn btn-primary w-100">Save</button>
				</div>
			</div>
		</form>
	</div>

@endsection