<?php

namespace App\Nova\Actions;

use DateTime;
use Laravel\Nova\Nova;
use Laravel\Nova\Actions\Action;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Laravel\Nova\Actions\ActionEvent;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Actions\Transaction;
use Laravel\Nova\Actions\CallQueuedAction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Laravel\Nova\Http\Requests\ActionRequest;
use Illuminate\Database\Eloquent\Relations\Relation;

class ActionDispatcher
{
    /**
     * Dispatches ths specified action using the given collection.
     *
     * @param  \Laravel\Nova\Actions\Action       $action
     * @param  string                             $method
     * @param  \Illuminate\Support\Collection     $models
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     *
     * @return mixed
     */
    public static function dispatchForCollection(Action $action, $method, Collection $models, ActionFields $fields)
    {
    	// Stop if there aren't any models
        if($models->isEmpty()) {
            return;
        }

        // Queue the action if it should queue
        if($action instanceof ShouldQueue) {
            return static::queueForCollection($action, $method, $models, $fields);
        }

        // Dispatch the action within a transaction
        return Transaction::run(function ($batchId) use ($fields, $action, $method, $models) {

        	// If the action uses action events, create them
            if(!$action->withoutActionEvents) {
                static::createActionEventForCollection($action, $batchId, $models);
            }

            // Handle the action
            return $action->withBatchId($batchId)->{$method}($fields, $models);

        }, function ($batchId) {
            ActionEvent::markBatchAsFinished($batchId);
        });
    }

    /**
     * Dispatch the given action in the background.
     *
     * @param  \Laravel\Nova\Actions\Action       $action
     * @param  string                             $method
     * @param  \Illuminate\Support\Collection     $models
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     *
     * @return void
     */
    public static function queueForCollectionAction(Action $action, $method, Collection $models, ActionFields $fields)
    {
    	// Queue the action within a transaction
        return Transaction::run(function ($batchId) use ($action, $method, $models) {

        	// If the action uses action events, create them
            if(!$action->withoutActionEvents) {
                static::createActionEventForCollection($action, $batchId, $models, 'waiting');
            }

            // Queue the action
            Queue::connection(static::connection($action))->pushOn(
                static::queue($action),
                new CallQueuedAction(
                    $action, $method, $fields, $models, $batchId
                )
            );

        });
    }

    /**
     * Creates the action records for the given model collection.
     *
     * @param  \Laravel\Nova\Actions\Action    $action
     * @param  string                          $batchId
     * @param  \Illuminate\Support\Collection  $models
     * @param  string                          $status
     *
     * @return void
     */
    public static function createActionEventForCollection(Action $action, $batchId, Collection $models, $status = 'running')
    {
    	// Map the models into action events
        $models = $models->map(function($model) use ($action, $batchId, $models, $status) {

        	// Merge the default attributes with the actionable overrides
            return array_merge(static::defaultAttributes($action, $batchId, $models, $status), [
                'actionable_id' => static::actionableKey($action, $model),
                'target_id' => static::targetKey($action, $model),
                'model_id' => $model->getKey(),
			]);

        });

        // Bulk insert the models
        $models->chunk(50)->each(function ($models) {
            ActionEvent::insert($models->all());
        });

        // Prune the action events
        ActionEvent::prune($models);
    }

    /**
     * Get the default attributes for creating a new action event.
     *
     * @param  \Laravel\Nova\Actions\Action               $action
     * @param  string                                     $batchId
     * @param  \Illuminate\Support\Collection             $models
     * @param  string                                     $status
     *
     * @return array
     */
    public static function defaultAttributes(Action $action, $batchId, Collection $models, $status = 'running')
    {
        // Check if the action is a pivot action
        if(static::isPivotAction($action)) {

            // Determine the pivot class
            $pivotClass = static::pivotRelation($action)->getPivotClass();

            // Determine the pivot model type
            $modelType = collect(Relation::$morphMap)->filter(function($model, $alias) use ($pivotClass) {
                return $model === $pivotClass;
            })->keys()->first() ?? $pivotClass;

        }

        // Otherwise, use the model's morph class
        else {
            $modelType = $models->first()->getMorphClass();
        }

        // Determine the default model
        $model = $models->first();

        // Create a new action request
        $request = app()->make(ActionRequest::class);

        // Return the default attributes
        return [
            'batch_id' => $batchId,
            'user_id' => auth()->user()->getAuthIdentifier(),
            'name' => $action->name(),
            'actionable_type' => (static::actionableModel($action) ?? $model)->getMorphClass(),
            'target_type' => (static::model($action) ?? $model)->getMorphClass(),
            'model_type' => $modelType,
            'fields' => serialize($request->resolveFieldsForStorage()),
            'status' => $status,
            'exception' => '',
            'created_at' => new DateTime,
            'updated_at' => new DateTime,
        ];
    }


    /**
     * Extract the queue connection for the action.
     *
     * @param  \Laravel\Nova\Actions\Action  $action
     *
     * @return string|null
     */
    protected static function connection($action)
    {
        return property_exists($action, 'connection') ? $action->connection : null;
    }

    /**
     * Extract the queue name for the action.
     *
     * @param  \Laravel\Nova\Actions\Action  $action
     *
     * @return string|null
     */
    protected static function queue($action)
    {
        return property_exists($action, 'queue') ? $action->queue : null;
    }

    /**
     * Returns whether or not the specified action is a pivot action.
     *
     * @return boolean
     */
    public static function isPivotAction($action)
    {
        return property_exists($action, 'pivotAction') ? $action->pivotAction : false;
    }

    /**
     * Returns the key of model that lists the action on its dashboard.
     *
     * @param  \Laravel\Nova\Actions\Action         $action
     * @param  \Illuminate\Database\Eloquent\Model  $model
     *
     * @return mixed
     */
    public static function actionableKey($action, $model)
    {
        return static::isPivotAction($action)
	        ? $model->{static::pivotRelation($action)->getForeignPivotKeyName()}
	        : $model->getKey();
    }

    /**
     * Returns the model instance that lists the action on its dashboard.
     *
     * @param  \Laravel\Nova\Actions\Action  $action
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public static function actionableModel($action)
    {
        return static::isPivotAction($action)
                        ? static::newViaResource($action)->model()
                        : static::model($action);
    }

    /**
     * Returns the class name of the resource being requested.
     *
     * @param  \Laravel\Nova\Actions\Action  $action
     *
     * @return mixed
     */
    public static function resource($action)
    {
        return tap(Nova::resourceForKey(static::resourceKey($action)), function($resource) {
            abort_if(is_null($resource), 404);
        });
    }

    /**
     * Returns the resource key for the specified action.
     *
     * @param  \Laravel\Nova\Actions\Action  $action
     *
     * @return string|null
     */
    public static function resourceKey($action)
    {
        return property_exists($action, 'resourceKey') ? $action->resourceKey : null;
    }

    /**
     * Returns a new instance of the underlying model for the specified action.
     *
     * @param  \Laravel\Nova\Actions\Action  $action
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public static function model($action)
    {
        if(is_null(static::resourceKey($action))) {
            return null;
        }

        $resource = static::resource($action);

        return $resource::newModel();
    }

    /**
     * Returns the key of model that is the target of the action.
     *
     * @param  \Laravel\Nova\Actions\Action         $action
     * @param  \Illuminate\Database\Eloquent\Model  $model
     *
     * @return mixed
     */
    public static function targetKey($action, $model)
    {
        return static::isPivotAction($action)
            ? $model->{static::pivotRelation($action)->getRelatedPivotKeyName()}
            : $model->getKey();
    }

    /**
     * Returns the many-to-many relationship for a pivot action.
     *
     * @param  \Laravel\Nova\Actions\Action  $action
     *
     * @return \Illuminate\Database\Eloquent\Relations\Relation|null
     */
    public static function pivotRelation($action)
    {
        if(static::isPivotAction($action)) {
            return static::newViaResource($action)->model()->{static::viaRelationship($action)}();
        }
    }

    /**
     * Returns a new instance of hte "via" resource being requested.
     *
     * @param  \Laravel\Nova\Actions\Action  $action
     *
     * @return \Laravel\Nova\Resource|null
     */
    public static function newViaResource($action)
    {
        $resource = static::viaResourceClass($action);

        if(is_null($resource)) {
            return null;
        }

        return new $resource($resource::newModel());
    }

    /**
     * Returns the class name of the "via" resource being requested.
     *
     * @param  \Laravel\Nova\Actions\Action  $action
     *
     * @return string
     */
    public static function viaResourceClass($action)
    {
        return Nova::resourceForKey(static::viaResource($action));
    }

    /**
     * Returns whether or not the request is via a relationship.
     *
     * @param  \Laravel\Nova\Actions\Action  $action
     *
     * @return boolean
     */
    public static function isViaRelationship($action)
    {
        return static::viaResource($action) && static::viaResourceId($action) && static::viaRelationship($action);
    }

    /**
     * Returns the key name of the "via" resource being requested.
     *
     * @param  \Laravel\Nova\Actions\Action  $action
     *
     * @return string
     */
    public static function viaResource($action)
    {
        return property_exists($action, 'viaResource') ? $action->viaResource : null;
    }

    /**
     * Returns the id of the "via" resource being requested.
     *
     * @param  \Laravel\Nova\Actions\Action  $action
     *
     * @return string
     */
    public static function viaResourceId($action)
    {
        return property_exists($action, 'viaResourceId') ? $action->viaResourceId : null;
    }

    /**
     * Returns the relationship to the "via" resource being requested.
     *
     * @param  \Laravel\Nova\Actions\Action  $action
     *
     * @return string
     */
    public static function viaRelationship($action)
    {
        return property_exists($action, 'viaRelationship') ? $action->viaRelationship : null;
    }
}