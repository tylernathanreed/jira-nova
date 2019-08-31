<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Slideshow extends Model
{
    use SoftDeletes;

    /////////////////
    //* Relations *//
    /////////////////
    /**
     * Returns the pages that belong to this slideshow.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pages()
    {
        return $this->hasMany(SlideshowPage::class, 'slideshow_id');
    }
}
