<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class SoftwareApplication extends Model
{
    use SoftDeletes;

    //////////////////
    //* Attributes *//
    //////////////////
    /**
     * The table associated to this model.
     *
     * @var string
     */
    protected $table = 'software_applications';

    /////////////////
    //* Relations *//
    /////////////////
    /**
     * Returns the branches that belong to this application.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function branches()
    {
        return $this->hasMany(SoftwareBranch::class, 'application_id');
    }
}
