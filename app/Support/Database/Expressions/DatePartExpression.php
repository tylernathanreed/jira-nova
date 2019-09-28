<?php

namespace App\Support\Database\Expression;

use InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;

class DatePartExpression extends Expression
{
    /**
     * Create a new raw query expression.
     *
     * @param  mixed  $value
     *
     * @return void
     */
    public function __construct(Builder $query, $column, $unit, $timezone)
    {
        $this->value = static::make($query, $column, $unit, $timezone)->getValue();
    }

    /**
     * Creates and returns a new date part expression instance.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string                                 $column
     * @param  string                                 $unit
     * @param  string                                 $timezone
     *
     * @return \Laravel\Nova\Metrics\TrendDateExpression
     */
    public static function make(Builder $query, $column, $unit, $timezone)
    {
        switch($driver = $query->getConnection()->getDriverName()) {

            case 'sqlite':
                return new DatePartSqliteDriverExpression($query, $column, $unit, $timezone);

            case 'mysql':
            case 'mariadb':
                return new DatePartMySqlDriverExpression($query, $column, $unit, $timezone);

            case 'pgsql':
                return new DatePartPostgresDriverExpression($query, $column, $unit, $timezone);

            case 'sqlsrv':
                return new DatePartSqlSrvDriverExpression($query, $column, $unit, $timezone);

            default:
                throw new InvalidArgumentException("Date part expressions are not supported for [{$driver}] database drivers.");
        }
    }
}
