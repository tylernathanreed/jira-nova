<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class SoftwareBrand extends Model
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
    protected $table = 'software_brands';

    /////////////////
    //* Relations *//
    /////////////////
    /**
     * Returns the branches that belong to this application.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function environments()
    {
        return $this->hasMany(SoftwareEnvironment::class, 'brand_id');
    }
}
