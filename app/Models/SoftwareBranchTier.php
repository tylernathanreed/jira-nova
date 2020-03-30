<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class SoftwareBranchTier extends Model
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
    protected $table = 'software_branch_tiers';

    /////////////////
    //* Relations *//
    /////////////////
    /**
     * Returns the branches that belong to this tier.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function branches()
    {
        return $this->hasMany(SoftwareBranch::class, 'branch_tier_id');
    }
}
