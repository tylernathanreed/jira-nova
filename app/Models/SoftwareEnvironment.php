<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class SoftwareEnvironment extends Model
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
    protected $table = 'software_environments';

    /////////////////
    //* Relations *//
    /////////////////
    /**
     * Returns the branch that this environment is currently using.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function branch()
    {
        return $this->belongsTo(SoftwareBranch::class, 'branch_id');
    }

    /**
     * Returns the brand that this environment is currently using.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function brand()
    {
        return $this->belongsTo(SoftwareBrand::class, 'brand_id');
    }

    /**
     * Returns the tier that this environment is classified as.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function tier()
    {
        return $this->belongsTo(SoftwareEnvironmentTier::class, 'environment_tier_id');
    }
}
