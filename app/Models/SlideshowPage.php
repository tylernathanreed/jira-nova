<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class SlideshowPage extends Model
{
    use SoftDeletes;

    /////////////////
    //* Relations *//
    /////////////////
    /**
     * Returns the slideshow that this page belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function slideshow()
    {
        return $this->belongsTo(Slideshow::class, 'slideshow_id');
    }

    //////////////////////
    //* Seed Relations *//
    //////////////////////
    /**
     * Returns the slideshow that this page belongs to using seed data.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function slideshowFromSeed()
    {
        return $this->belongsTo(Slideshow::class, 'slideshow_system_name', 'system_name');
    }
}
