<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class FocusGroup extends Model
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
    protected $table = 'focus_groups';

    /**
     * The attributes that should be casted.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'color' => 'json',
        'criteria' => 'json'
    ];

    ////////////
    //* Nova *//
    ////////////
    /**
     * Returns the Nova data for this schedule.
     *
     * @return array
     */
    public function toNovaData()
    {
        return [
            'blocks_other_focuses' => $this->blocks_other_focuses,
            'color' => $this->color,
            'priority' => $this->priority
        ];
    }

    /////////////////
    //* Relations *//
    /////////////////
    /**
     * Returns the allocations associated to this focus group.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function allocations()
    {
        return $this->hasMany(ScheduleFocusAllocation::class, 'focus_group_id');
    }
}
