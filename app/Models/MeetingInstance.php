<?php

namespace App\Models;

class MeetingInstance extends Model
{
    //////////////////
    //* Attributes *//
    //////////////////
    /**
     * The table associated to this model.
     *
     * @var string
     */
    protected $table = 'meeting_instances';

    /**
     * The attributes that should be casted to dates.
     *
     * @var array
     */
    protected $dates = [
        'effective_date',
        'starts_at',
        'ends_at'
    ];

    ////////////
    //* Boot *//
    ////////////
    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        // Call the parent method
        parent::boot();

        // When the model is being saved...
        static::saving(function($model) {

            // If the model is either new, the effective date is being changed, or the "start_time" attribute exists...
            if(!$model->exists || $model->isDirty('effective_date') || !is_null($time = $model->start_time)) {

                // Update the start time
                $model->starts_at = $model->getCalculatedStartDateTime();

                // Remove the "start_time" attribute
                unset($model->start_time);

            }

            // If the model is either new, the effective date is being changed, or the "end_time" attribute exists...
            if(!$model->exists || $model->isDirty('effective_date') || !is_null($time = $model->end_time)) {

                // Update the end time
                $model->ends_at = $model->getCalculatedEndDateTime();

                // Remove the "end_time" attribute
                unset($model->end_time);

            }

            // If the model is either new, or the "starts_at" and/or "ends_at" attribute is being changed...
            if(!$model->exists || $model->isDirty('starts_at') || $model->isDirty('ends_at')) {

                // Update the length
                $model->length_in_seconds = $model->ends_at->diffInSeconds($model->starts_at);

            }

        });
    }

    /////////////////
    //* Accessors *//
    /////////////////
    /**
     * Returns the calculated start date time.
     *
     * @return \Carbon\Carbon
     */
    public function getCalculatedStartDateTime()
    {
        return $this->getCalculatedReferenceDateTime('start');
    }

    /**
     * Returns the calculated end date time.
     *
     * @return \Carbon\Carbon
     */
    public function getCalculatedEndDateTime()
    {
        return $this->getCalculatedReferenceDateTime('end');
    }

    /**
     * Returns the calculated reference date time.
     *
     * @param  string  $reference
     *
     * @return \Carbon\Carbon
     */
    public function getCalculatedReferenceDateTime($reference)
    {
        // Determine the effective date
        $date = carbon($this->effective_date)->toDateString();

        // Determine the reference time
        $time = $this->attributes["{$reference}_time"] ?: carbon()->toTimeString();

        // Concatenate the date and time
        return carbon("{$date} {$time}");
    }

    /**
     * Returns the length of the meeting, in hours.
     *
     * @return float
     */
    public function getLength()
    {
        return $this->ends_at->floatDiffInHours($this->starts_at);
    }

    ///////////////////////
    //* Magic Accessors *//
    ///////////////////////
    /**
     * Creates the {@see $this->start_time} attribute for the Nova framework.
     *
     * @return \Carbon\Carbon|null
     */
    public function getStartTimeAttribute()
    {
        return !is_null($this->starts_at) ? carbon($this->starts_at)->format('H:i:s') : null;
    }

    /**
     * Creates the {@see $this->end_time} attribute for the Nova framework.
     *
     * @return \Carbon\Carbon|null
     */
    public function getEndTimeAttribute()
    {
        return !is_null($this->ends_at) ? carbon($this->ends_at)->format('H:i:s') : null;
    }

    //////////////////////
    //* Magic Mutators *//
    //////////////////////
    /**
     * Creates the {@see $this->participants} mutator for the participants relation and Nova framework.
     *
     * @param  string|array  $participants
     *
     * @return $this
     */
    public function setParticipantsAttribute($participants)
    {
        if(is_string($participants)) {
            $participants = json_decode($participants);
        }

        return $this->participants()->sync($participants);
    }

    /////////////////
    //* Relations *//
    /////////////////
    /**
     * Returns the participants of this meeting.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function participants()
    {
        return $this->belongsToMany(User::class, 'meeting_participants', 'meeting_instance_id', 'user_id')->withTimestamps();
    }
}
