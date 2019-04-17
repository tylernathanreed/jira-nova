<?php

namespace App\Nova\Actions\Concerns;

use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Actions\ActionMethod;
use Laravel\Nova\Http\Requests\ActionRequest;
use Laravel\Nova\Actions\ActionModelCollection;
use Laravel\Nova\Exceptions\MissingActionHandlerException;

trait ManuallyCallable
{
    /**
     * Calls this action.
     *
     * @param  array  $fields
     * @param  mixed  $models
     *
     * @return mixed
     *
     * @throws \Laravel\Nova\Exceptions\MissingActionHandlerException
     */
    public function handleCollection($fields = [], $models = [])
    {
    	// Determine the method to invoke
    	$method = ActionMethod::determine($this, $request->targetModel());

    	// Make sure the method exists
        if(!method_exists($this, $method)) {
            throw MissingActionHandlerException::make($this, $method);
        }

        // Initialize the executed flag
        $wasExecuted = false;

        // Resolve the fields
        $fields = $this->resolveFieldValues($fields);

        // Check if the model list is not a collection
        if(!$models instanceof ActionModelCollection) {

        	// Convert the models to an action model collection
        	$models = ActionModelCollection::make($models);

        }

        // Create a new action request
        $request = app()->make(ActionRequest::class);

        // Chunk the models
        $models->chunk(static::$chunkCount)->map(function($models) use ($fields, $request, $method, &$wasExecuted) {

        	// Filter the models for execution
            $models = $models->filterForExecution($request);

            // If there's at least one model, mark this action as executed
            if(count($models) > 0) {
                $wasExecuted = true;
            }

            // Call this action for the specified models
            return ActionDispatcher::dispatchForCollection($this, $method, $models, $fields);

        });

        // Let the user know if nothing was executed
        if(!$wasExecuted) {
            return static::danger(__('Sorry! You are not authorized to perform this action.'));
        }

        // Handle the result
        return $this->handleResult($fields, $results);
    }

    /**
	 * Resolves the action fields from the specified field values.
	 *
	 * @param  array  $values
	 *
	 * @return \Laravel\Nova\Fields\ActionFields
	 */
    public function resolveFieldValues($values = [])
    {
        // Map the provided values into the fields
        $results = collect($this->fields())->mapWithKeys(function($field) use ($values) {
            return [$field->attribute => $values[$field->attribute] ?? null];
        });

        // Create and return a new set of action fields
        return new ActionFields(collect($results), $results->filter(function($field) {
            return is_callable($field);
        }));
    }
}