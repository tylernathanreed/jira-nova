<?php

return [

    /**
     * Laravel Fields
     */
    'avatar'               => \Laravel\Nova\Fields\Avatar::class,
    'boolean'              => \Laravel\Nova\Fields\Boolean::class,
    'code'                 => \Laravel\Nova\Fields\Code::class,
    'country'              => \Laravel\Nova\Fields\Country::class,
    'currency'             => \Laravel\Nova\Fields\Currency::class,
    'dateField'            => \Laravel\Nova\Fields\Date::class, // "date" is a macro to impose a default format
    'dateTime'             => \Laravel\Nova\Fields\DateTime::class,
    'file'                 => \Laravel\Nova\Fields\File::class,
    'gravatar'             => \Laravel\Nova\Fields\Gravatar::class,
    'heading'              => \Laravel\Nova\Fields\Heading::class,
    'id'                   => \Laravel\Nova\Fields\ID::class,
    'image'                => \Laravel\Nova\Fields\Image::class,
    'markdown'             => \Laravel\Nova\Fields\Markdown::class,
    'number'               => \Laravel\Nova\Fields\Number::class,
    'password'             => \Laravel\Nova\Fields\Password::class,
    'passwordConfirmation' => \Laravel\Nova\Fields\PasswordConfirmation::class,
    'place'                => \Laravel\Nova\Fields\Place::class,
    'select'               => \Laravel\Nova\Fields\Select::class,
    'status'               => \Laravel\Nova\Fields\Status::class,
    'text'                 => \Laravel\Nova\Fields\Text::class,
    'textarea'             => \Laravel\Nova\Fields\Textarea::class,
    'timezone'             => \Laravel\Nova\Fields\Timezone::class,
    'trix'                 => \Laravel\Nova\Fields\Trix::class,

    /**
     * Laravel Relations
     */
    'belongsTo'           => \Laravel\Nova\Fields\BelongsTo::class,
    'belongsToMany'       => \Laravel\Nova\Fields\BelongsToMany::class,
    'hasMany'             => \Laravel\Nova\Fields\HasMany::class,
    'hasOne'              => \Laravel\Nova\Fields\HasOne::class,
    'morphedByMany'       => \Laravel\Nova\Fields\MorhpedByMany::class,
    'morphMany'           => \Laravel\Nova\Fields\MorphMany::class,
    'morphOne'            => \Laravel\Nova\Fields\MorphOne::class,
    'morphTo'             => \Laravel\Nova\Fields\MorphTo::class,
    'morphToActionTarget' => \Laravel\Nova\Fields\MorphToActionTarget::class,
    'morphToMany'         => \Laravel\Nova\Fields\MorphToMany::class,

    /**
     * Package Fields
     */
    'badge'       => \Timothyasp\Badge\Badge::class,
    'badgeUrl'    => \NovaComponents\BadgeUrl\BadgeUrl::class,
    'color'       => \Timothyasp\Color\Color::class,
    'multiselect' => \OptimistDigital\MultiselectField\Multiselect::class,
    'swatch'      => \NovaComponents\ColorSwatch\ColorSwatch::class,
    'textWrap'    => \Reedware\NovaTextWrapField\TextWrap::class,
    'timeField'   => \Laraning\NovaTimeField\TimeField::class,
    'url'         => \Inspheric\Fields\Url::class,
    'valueToggle' => \Reedware\NovaValueToggle\ValueToggle::class,

];