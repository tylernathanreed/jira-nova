<?php

namespace App\Nova\Resources;

use Laravel\Nova\Resource as NovaResource;
use Laravel\Nova\Http\Requests\NovaRequest;

abstract class Resource extends NovaResource
{
    /**
     * Indicates if the resoruce should be globally searchable.
     *
     * @var bool
     */
    public static $globallySearchable = false;

    /**
     * Indicates if the resource should be displayed in the sidebar.
     *
     * @var bool
     */
    public static $displayInNavigation = false;

    /**
     * The default ordering to use when listing this resource.
     *
     * @var array
     */
    public static $defaultOrderings = [];

    /**
     * Build an "index" query for the given resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder    $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function indexQuery(NovaRequest $request, $query)
    {
        return $query;
    }

    /**
     * Build a Scout search query for the given resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Laravel\Scout\Builder                   $query
     *
     * @return \Laravel\Scout\Builder
     */
    public static function scoutQuery(NovaRequest $request, $query)
    {
        return $query;
    }

    /**
     * Build a "detail" query for the given resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder    $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function detailQuery(NovaRequest $request, $query)
    {
        return parent::detailQuery($request, $query);
    }

    /**
     * Build a "relatable" query for the given resource.
     *
     * This query determines which instances of the model may be attached to other resources.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder    $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function relatableQuery(NovaRequest $request, $query)
    {
        return parent::relatableQuery($request, $query);
    }

    /**
     * Perform any final formatting of the given validation rules.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  array  $rules
     *
     * @return array
     */
    protected static function formatRules(NovaRequest $request, array $rules)
    {
        // Initialize the replacements
        $replacements = [
            '{{resourceId}}' => str_replace(['\'', '"', ',', '\\'], '', $request->resourceId ?: 'NULL')
        ];

        // Initialize the replacement values
        $values = [];

        // Add the request parameters as replacement values
        $values['request'] = $request->all();

        // Convert the replacement values into replacement rules
        $replacements = ($replacer = function($replacements, $values, $prefix = null) use (&$replacer) {

            foreach($values as $key => $value) {

                if(is_array($value)) {
                    return $replacer($replacements, $value, !is_null($prefix) ? "{$prefix}.{$key}" : $key);
                } else if(!is_null($prefix)) {
                    $replacements["{{{$prefix}.{$key}}}"] = str_replace(['\'', '"', ',', '\\'], '', $value);
                } else {
                    $replacements["{{{$key}}}"] = str_replace(['\'', '"', ',', '\\'], '', $value);
                }

            }

            return $replacements;

        })($replacements, $values);

        // Remove all empty replacement values
        $replacements = array_filter($replacements);

        // If no replacements were found, just return the rules as-is
        if(empty($replacements)) {
            return $rules;
        }

        // Replace the rules
        return collect($rules)->map(function ($rules) use ($replacements) {
            return collect($rules)->map(function ($rule) use ($replacements) {
                return is_string($rule)
                            ? str_replace(array_keys($replacements), array_values($replacements), $rule)
                            : $rule;
            })->all();
        })->all();
    }

    /**
     * Apply any applicable orderings to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array                                  $orderings
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected static function applyOrderings($query, array $orderings)
    {
        if(empty($orderings)) {
            $orderings = static::$defaultOrderings;
        }

        return parent::applyOrderings($query, $orderings);
    }
}
