<?php

namespace App\Models;

use App\Events\CacheStatusUpdate;

class TimeOff extends Model
{
    /////////////////
    //* Constants *//
    /////////////////
    /**
     * The type constants.
     *
     * @var string
     */
    const TYPE_FULL = 'full';
    const TYPE_HALF = 'half';
    const TYPE_CUSTOM = 'custom';

    /**
     * The value constants.
     *
     * @var float|null
     */
    const VALUE_FULL = 1;
    const VALUE_HALF = 0.5;
    const VALUE_CUSTOM = null;

    //////////////////
    //* Attributes *//
    //////////////////
    /**
     * The table associated to this model.
     *
     * @var string
     */
    protected $table = 'time_off';

    /**
     * The attributes that should be casted to dates.
     *
     * @var array
     */
    protected $dates = [
        'date'
    ];

    /**
     * Overrides the type accessor to use a value based off of the percent.
     *
     * @return string
     */
    public function getTypeFromPercent($percent)
    {
        switch($percent) {

            // Full
            case self::VALUE_FULL:
                return self::TYPE_FULL;

            // Half
            case self::VALUE_HALF:
                return self::TYPE_HALF;

            // Custom (or other)
            case self::VALUE_CUSTOM: default:
                return self::TYPE_CUSTOM;

        }
    }

    /**
     * Overrides the type mutator to set the percent.
     *
     * @param  string  $type
     *
     * @return $this
     */
    public function setPercentFromType($type)
    {
        switch($type) {

            // Full
            case self::TYPE_FULL:
                return $this->setAttribute('percent', self::VALUE_FULL);

            // Half
            case self::TYPE_HALF:
                return $this->setAttribute('percent', self::VALUE_HALF);

            // Custom (or other)
            case self::TYPE_CUSTOM: default:
                return $this;

        }
    }

    /////////////////
    //* Relations *//
    /////////////////
    /**
     * Returns the user requesting the time off.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
