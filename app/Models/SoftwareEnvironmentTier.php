<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class SoftwareEnvironmentTier extends Model
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
    protected $table = 'software_environment_tiers';

    /////////////////
    //* Relations *//
    /////////////////
    /**
     * Returns the environments that belong to this tier.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function environments()
    {
        return $this->hasMany(SoftwareEnvironment::class, 'environment_tier_id');
    }
}
