<?php

namespace App\Nova\Actions\Concerns;

use Laravel\Nova\Actions\Action;
use Illuminate\Support\Collection;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Actions\ActionMethod;
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
    public function call($fields = [], $models = [])
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

        // Convert the models to a collection
        if(!$models instanceof Collection) {
        	$models = new Collection($models);
        }

        // Chunk the models
        $models->chunk(static::$chunkCount)->map(function($models) use ($fields, $method, &$wasExecuted) {

	    	/**
	    	 * @todo
	    	 */

        	// Filter the models for execution
            $models = $models->filterForExecution($request);

            // If there's at least one model, mark this action as executed
            if(count($models) > 0) {
                $wasExecuted = true;
            }

            // Call this action for the specified models
            return static::runForModels($this, $method, $models, $fields);

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

    /**
     * Runs ths specified action using the given models.
     *
     * @param  \Laravel\Nova\Actions\Action       $action
     * @param  string                             $method
     * @param  \Illuminate\Support\Collection     $models
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     *
     * @return mixed
     */
    public static function runForModels(Action $action, $method, Collection $models, ActionFields $fields)
    {
    	/**
    	 * @todo
    	 */

        if ($models->isEmpty()) {
            return;
        }

        if ($action instanceof ShouldQueue) {
            return static::queueForModels($request, $action, $method, $models);
        }

        return Transaction::run(function ($batchId) use ($fields, $request, $action, $method, $models) {
            if (! $action->withoutActionEvents) {
                ActionEvent::createForModels($request, $action, $batchId, $models);
            }

            return $action->withBatchId($batchId)->{$method}($fields, $models);
        }, function ($batchId) {
            ActionEvent::markBatchAsFinished($batchId);
        });
    }
}