<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class SoftwareBranch extends Model
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
    protected $table = 'software_branches';

    /////////////////
    //* Relations *//
    /////////////////
    /**
     * Returns the project that this branch belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Returns the application that this branch belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function application()
    {
        return $this->belongsTo(SoftwareApplication::class, 'application_id');
    }

    /**
     * Returns the tier that this branch belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function tier()
    {
        return $this->belongsTo(SoftwareBranchTier::class, 'branch_tier_id');
    }

    /**
     * Returns the target version that this branch belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function targetVersion()
    {
        return $this->belongsTo(Version::class, 'target_version_id');
    }

    /**
     * Returns the pipeline steps that use this branch.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function pipelineSteps()
    {
        return $this->hasMany(Views\SoftwarePipelineStep::class, 'branch_id');
    }

    /**
     * Returns the pipeline summary that use this branch.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function pipelineSummary()
    {
        return $this->hasMany(Views\SoftwarePipelineSummary::class, 'branch_id');
    }
}
