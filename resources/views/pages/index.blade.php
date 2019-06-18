@extends('layouts.app')

@section('content')

	<div class="container">
		<div class="swimlane">
			<div class="swimline-header">
			</div>

			<ul class="swimlane-content ui-sortable">
				@foreach($issues as $issue)
					<li class="swimlane-issue-wrapper">
						<div class="swimlane-issue" data-issue="{{ $issue['key'] }}">
							<div class="swimlane-issue-field" data-field="type">
								<img class="icon" src="{{ $issue['type_icon_url'] }}"/>
							</div>

							<div class="swimlane-issue-field" data-field="priority">
								<img class="icon" src="{{ $issue['priority_icon_url'] }}"/>
							</div>

							<div class="swimlane-issue-field" data-field="key">
								{{ $issue['key'] }}
							</div>

							<div class="swimlane-issue-field" data-field="summary" style="flex: 1; color: #777">
								{{ $issue['summary'] }}
							</div>

							<div class="swimlane-issue-field issue-status-{{ $issue['status_color'] }}" data-field="status" style="min-width: 90px; text-align: center">
								{{ $issue['status'] }}
							</div>

							<div class="swimlane-issue-field" data-field="issue-category" style="min-width: 60px; text-align: center">
								{{ $issue['issue_category'] }}
							</div>

							<div class="swimlane-issue-field" data-field="estimated-completion-date" style="min-width: 80px; text-align: center">
								{{ !is_null($date = $issue['old_estimated_completion_date']) ? \Carbon\Carbon::parse($date)->format('n/d/Y') : null }}
							</div>

							<div class="swimlane-issue-field" data-field="due-date" style="min-width: 80px; text-align: center">
								{{ !is_null($date = $issue['due_date']) ? \Carbon\Carbon::parse($date)->format('n/d/Y') : null }}
							</div>

							<div class="swimlane-issue-field" data-field="time-estimate" style="min-width: 40px; text-align: right">
								{{ number_format($issue['time_estimate'] / 3600, 2) }}
							</div>
						</div>
					</li>
				@endforeach
			</ul>
		</div>
	</div>

@endsection

@push('styles')

	<style>
		.swimlane-content {
			list-style-type: none;
			margin: 0;
			padding: 0 5px;
			border: 1px solid #ddd;
			border-radius: 3px;
			background-color: #f4f4f4;
			width: 100%;
		}

		.swimlane-issue-wrapper {
			min-height: 2rem;
			margin: 5px 0;
			padding: 5px;
			font-size: 12px;
			font-family: 'Segoe UI';
			line-height: 1rem;
			background: #fff;
			border: 1px solid #ddd;
			border-radius: 3px;
			box-shadow: 0 1px 2px 0 rgba(9, 30, 66, 0.25);
			color: #333;
			cursor: move;
			user-select: none;
		}

		.swimlane-issue {
			display: flex;
			align-items: center;
			margin: 0 -3px;
		}

		.swimlane-issue-field {
			padding: 0 3px;
		}

		.swimlane-placeholder {
			height: 2rem;
			border: 1px dashed #ccc;
			border-radius: 3px;
			background-color: rgba(0, 0, 0, 0.05);
		}

		.issue-status-blue-gray {
			display: inline-block;
			padding: 1px 4px;
			font-size: 10px;
			font-weight: bold;
			border-width: 1px;
			border-style: solid;
			border-radius: 3px;

			background: #fff;
			color: #43526e;
			border-color: #c1c7d0;
		}

		.issue-status-yellow {
			display: inline-block;
			padding: 1px 4px;
			font-size: 10px;
			font-weight: bold;
			border-width: 1px;
			border-style: solid;
			border-radius: 3px;

			background: #fff;
			color: #0052cc;
			border-color: #b3d4ff;
		}

		img.icon {
			width: 16px;
			height: 16px;
		}
	</style>

@endpush