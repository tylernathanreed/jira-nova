<?php

namespace App\Policies;

use Nova;
use Illuminate\Auth\Access\HandlesAuthorization;
use Laravel\Nova\Http\Requests\InteractsWithResources;

abstract class Policy
{
	use HandlesAuthorization, InteractsWithResources;

    /**
     * Returns the class name of the resource being requested.
     *
     * @return string|null
     */
    public function resource()
    {
        return Nova::resourceForKey(request()->route('resource'));
    }

    /**
     * Returns the model specified by the current request.
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function modelViaResourceId()
    {
        return $this->findModelQuery(request()->route('resourceId'))->first();
    }

    /**
     * Returns the specified model from the current request.
     *
     * @param  string  $model
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function getModelFromRequest($model)
    {
        // Determine the resource from the model
        $resource = Nova::resourceForModel($model);

        // Make sure the request is for the specified model
        if($this->resource() != $resource) {
            return null;
        }

        // Return the model from the request
        return $this->modelViaResourceId();
    }
}