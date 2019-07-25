<?php

namespace App\Nova\Actions;

use App\Models\Issue;
use Illuminate\Support\Collection;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Actions\ActionMethod;
use Laravel\Nova\Http\Requests\ActionRequest;
use Laravel\Nova\Exceptions\MissingActionHandlerException;

class SaveSwimlaneChanges extends Action
{
    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection     $issues
     *
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $issues)
    {
        // Determine the old and new orders
        $oldOrder = $issues->sortBy('original.order')->pluck('key')->toArray();
        $newOrder = $issues->sortBy('order')->pluck('key')->toArray();

        // Determine the subtasks
        $subtasks = $issues->where('is_subtask', '=', 1)->pluck('key')->toArray();

        // Determine the issues with new estimates
        $estimates = $issues->filter(function($issue) {
            return $issue['est'] != $issue['original']['est'];
        })->pluck('est', 'key');

        // Perform the ranking operations to sort the old list into the new list
        Issue::updateOrderByRank($oldOrder, $newOrder, $subtasks);

        // Update the estimated completion dates
        Issue::updateEstimates($estimates);
    }

    /**
     * Execute the action for the given request.
     *
     * @param  \Laravel\Nova\Http\Requests\ActionRequest  $request
     *
     * @return mixed
     *
     * @throws \Laravel\Nova\Exceptions\MissingActionHandlerExceptionMissingActionHandlerException
     */
    public function handleRequest(ActionRequest $request)
    {
        // Determine the action method
        $method = ActionMethod::determine($this, $request->targetModel());

        // Make sure the method exists
        if(!method_exists($this, $method)) {
            throw MissingActionHandlerException::make($this, $method);
        }

        // Resolve the action fields
        $fields = $request->resolveFields();

        // Determine the resource data
        $resources = collect(json_decode($request->resourceData, true));

        // Handle the request
        $results = [
            $this->handle($fields, $resources)
        ];

        // Return the results
        return $this->handleResult($fields, $results);
    }

    /**
     * Returns the fields available on the action.
     *
     * @return array
     */
    public function fields()
    {
        return [];
    }
}
