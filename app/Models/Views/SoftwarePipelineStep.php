<?php

namespace App\Models\Views;

use App\Models\SoftwareBranchTier;
use App\Models\SoftwareApplication;

class SoftwarePipelineStep extends View
{
    //////////////////
    //* Attributes *//
    //////////////////
    /**
     * The table associated to this model.
     *
     * @var string
     */
    protected $table = 'vw_software_pipeline_steps';

    /////////////////
    //* Relations *//
    /////////////////
    /**
     * Returns the application that this step references.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function application()
    {
        return $this->belongsTo(SoftwareApplication::class, 'application_id');
    }

    /**
     * Returns the branch tier that this step references.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function branchTier()
    {
        return $this->belongsTo(SoftwareBranchTier::class, 'branch_tier_id');
    }
}
