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
        'color' => 'json',
        'criteria' => 'json'
    ];
}
