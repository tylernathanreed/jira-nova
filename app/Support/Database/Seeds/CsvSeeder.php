<?php

namespace App\Support\Database\Seeds;

use League\Csv\Reader;
use League\Csv\Writer;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\ConnectionResolverInterface as Resolver;

class CsvSeeder extends Seeder
{
    //////////////////
    //* Attributes *//
    //////////////////
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model;

    /**
     * The absolute file path to the root seed data directory.
     *
     * @var string|null
     */
    public $root;

    /**
     * The name of the seed data file.
     *
     * @var string|null
     */
    public $filename;

    /**
     * The connection resolver instance.
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    public $resolver;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    public $filesystem;

    /**
     * The name of the database connection.
     *
     * @var string|null
     */
    public $connection;

    /**
     * Whether or not to include trashed data.
     *
     * @var boolean
     */
    public $withTrashed = true;

    /**
     * The attributes to match when creating or updating records.
     *
     * @var array
     */
    public $match = [];

    /**
     * The select columns to replace with other selections.
     *
     * @var array
     */
    public $replacements = [];

    /**
     * The inverse select columns to replace with other selections.
     *
     * @var array
     */
    public $inverseReplacements = [];

    /**
     * The columns required for seeding.
     *
     * @var array
     */
    public $required = [];

    /**
     * The columns to ignore for seeding.
     *
     * @var array
     */
    public $ignore = [];

    /**
     * The columns for ordering.
     *
     * @var array
     */
    public $orderings = [];

    /**
     * The seedable model relations keyed by the local column name.
     *
     * @var array
     */
    public $joinRelations = [];

    /**
     * The inverse seedable model relations.
     *
     * @var array
     */
    public $inverseJoinRelations = [];

    ///////////////////
    //* Constructor *//
    ///////////////////
    /**
     * Creates a new instance of this class.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @param  \Illuminate\Filesystem\Filesystem                 $filesystem
     *
     * @return $this
     */
    public function __construct(Resolver $resolver, Filesystem $filesystem)
    {
        $this->resolver = $resolver;
        $this->filesystem = $filesystem;
    }

    ///////////////
    //* Seeding *//
    ///////////////
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->seedUsingCsv();
    }

    /**
     * Seeds the database using the associated csv.
     *
     * @return void
     */
    public function seedUsingCsv()
    {
        // Create a new reader
        $reader = $this->newCsvReader();

        // Determine the headers
        $headers = $reader->getHeader();

        // Determine the records
        $records = [];

        foreach($reader as $record) {
            $records[] = $record;
        }

        // Convert the records into update results
        $results = $this->newUpdateQuery($headers, $records)->get();

        // Create a new model instance for reference
        $model = static::newModel();

        // Perform the inserts and updates without mass assignment protection
        $model::unguarded(function() use ($results, $model) {

            // Iterate through each result
            foreach($results as $result) {

                // Cast the result to an array
                $result = $result->getAttributes();

                // Extract the matching data
                $match = Arr::only($result, $this->match);

                // If result has a trashed entry, convert it to a timestamp
                if(isset($result['trashed'])) {

                    // Provide the timestamp
                    $result[$model->getDeletedAtColumn()] = $result['trashed'] ? $model->freshTimestamp() : null;

                    // Remove the trashed flag
                    unset($result['trashed']);

                }

                // Create or update the model
                $instance = $model->updateOrCreate($match, $result);

            }

        });
    }

    /**
     * Creates and returns a new update query.
     *
     * @param  array  $headers
     * @param  array  $records
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function newUpdateQuery($headers, $records)
    {
        // Create a new query
        $query = $this->newQueryFromRecords($records);

        // Join into the specified relations
        foreach($this->inverseJoinRelations as $joinRelation) {
            $query->leftJoinRelation($joinRelation);
        }

        // Select each header
        foreach($headers as $header) {

            // If the header has a replacement, swap it out
            if(isset($this->inverseReplacements[$header])) {
                $header = $this->inverseReplacements[$header];
            }

            $query->addSelect($header);

        }

        // Return the query
        return $query;
    }

    /**
     * Creates and returns a new query using the specified records.
     *
     * @param  array  $records
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newQueryFromRecords($records)
    {
        // Create a new query
        $query = $this->newQueryWithoutScopes();

        // Convert the records into a subquery
        $subquery = array_reduce($records, function($subquery, $record) {

            // Create a new query
            $query = $this->resolver->query();

            // Select each key/value pair
            foreach($record as $key => $value) {
                $query->addSelect("{$value} as {$key}");
            }

            // If a subquery doesn't exist, use the query
            if(is_null($subquery)) {
                return $query;
            }

            // Otherwise, union the query
            $subquery->unionAll($query);

            // Return the subquery
            return $subquery;

        }, null);

        // Select from the subquery
        $query->fromSub($subquery, 'records');

        $query->getModel()->setTable('records');

        // Return the query
        return $query;
    }

    /**
     * Creates and returns a new csv reader.
     *
     * @return League\Csv\Reader
     */
    public function newCsvReader()
    {
        return Reader::createFromPath($this->getFilePath(), 'r')->setHeaderOffset(0);
    }

    //////////////////
    //* Generating *//
    //////////////////
    /**
	 * Run the database seed generator.
	 *
	 * @return void
     */
    public function generate()
    {
        $this->generateUsingCsv();
    }

    /**
     * Run the database csv seed generator.
     *
     * @return void
     */
    public function generateUsingCsv()
    {
        // Create a new writer
        $writer = $this->newCsvWriter();

        // Create a new select query
        $query = $this->newSelectQuery();

        // Determine the headers from the query
        $headers = $this->getHeadersFromQuery($query);

        // Write the headers to the csv
        $writer->insertOne($headers);

        // Write the data to the csv
        $query->chunk(100, function($chunk) use ($writer) {
            $writer->insertAll($chunk->map(function($r) { return (array) $r; })->all());
        });
    }

    /**
     * Creates and returns a new select query for writing.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function newSelectQuery()
    {
        // Create a new query
        $query = $this->newBaseQuery();

        // Create a new model instance
        $model = static::newModel();

        // Determine whether or not the model soft deletes
        $softDeletes = method_exists($model, 'bootSoftDeletes');

        // Select the query columns
        $query->select($this->getTableSelectColumns($model, $softDeletes));

        // Check if the model soft deletes
        if($softDeletes) {

            // If we're not meant to include trashed data, filter it out
            if(!$this->withTrashed) {
                $query->withoutTrashed();
            }

            // Otherwise, include trashed data
            else {

                // Include trashed data
                $query->withTrashed();

                // Select whether or not the model is trashed
                $query->addSelect([
                    new Expression('case when ' . $model->getQualifiedDeletedAtColumn() . ' is null then 0 else 1 end as trashed')
                ]);

            }

        }

        // Order by the specified columns
        foreach($this->orderings as $column => $direction) {
            $query->orderBy($column, $direction);
        }

        // To ensure a proper ordering, also order by id
        if(!isset($this->orderings[$model->getKeyName()])) {
            $query->orderBy($model->getQualifiedKeyName(), 'asc');
        }

        // Return the query
        return $query->toBase();
    }

    /**
     * Creates and returns a new query for seeding and generating.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newBaseQuery()
    {
        // Create a new query
        $query = $this->newQuery();

        // Create a new model instance
        $model = static::newModel();

        // Join into the specified relations
        foreach($this->joinRelations as $joinRelation) {
            $query->leftJoinRelation($joinRelation);
        }

        // Make sure the required columns are present
        foreach($this->required as $required) {
            $query->whereNotNull($required);
        }

        // Return the query
        return $query;
    }

    /**
     * Returns the columns to select on the model's table.
     *
     * @param  mixed    $model
     * @param  boolean  $softDeletes
     *
     * @return array
     */
    public function getTableSelectColumns($model, $softDeletes)
    {
        // Determine the table column listing
        $listing = $this->getSchema()->getColumnListing($model->getTable());

        // Convert the listing to an associative array
        $listing = array_combine($listing, $listing);

        // If the model is incrementing, remove the primary key
        if($model->incrementing) {
            $listing = array_except($listing, [$model->getKeyName()]);
        }

        // If the model uses timestamps, remove them
        if($model->timestamps) {
            $listing = array_except($listing, [$model->getCreatedAtColumn(), $model->getUpdatedAtColumn()]);
        }

        // If the model uses soft deletes, remove the timestamp
        if($softDeletes) {
            $listing = array_except($listing, [$model->getDeletedAtColumn()]);
        }

        // Remove any columns that should be explicitly omitted
        if(!empty($this->ignore)) {
            $listing = array_except($listing, $this->ignore);
        }

        // Qualify each column
        $listing = array_combine($listing, array_map(function($column) use ($model) {
            return $model->qualifyColumn($column);
        }, $listing));

        // Apply the replacements
        foreach($this->replacements as $before => $after) {

            // Only apply the replacement if the column is listed
            if(isset($listing[$before])) {
                $listing[$before] = $after;
            }

        }

        // Return the columns
        return array_values($listing);
    }

    /**
     * Returns the headers from the specified query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     *
     * @return array
     */
    public function getHeadersFromQuery($query)
    {
        // Determine the columns being selected
        $columns = $query->columns;

        // Determine the aliases from the columns
        return array_map(function($column) {

            // If the column is an expression, cast it to a string
            if($column instanceof Expression) {
                $column = $column->getValue();
            }

            // If the column has an alias, use it
            if(is_string($column) && ($aliasPosition = strripos($column, ' as ')) !== false) {
                return substr($column, $aliasPosition + strlen(' as '));
            }

            // If the column is qualified, unqualify it
            if(is_string($column) && ($qualifiedPosition = strripos($column, '.')) !== false) {
                return substr($column, $qualifiedPosition + strlen('.'));
            }

            // Return the column as-is
            return $column;

        }, $columns);
    }

    /**
     * Creates and returns a new csv writer.
     *
     * @return League\Csv\Writer
     */
    public function newCsvWriter()
    {
        return Writer::createFromPath($this->getFilePath(), 'w+');
    }

    //////////////
    //* Shared *//
    //////////////
    /**
     * Creates and returns a new query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newQuery()
    {
        return (static::newModel())->newQuery();
    }

    /**
     * Creates and returns a new query without global scopes.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newQueryWithoutScopes()
    {
        return (static::newModel())->newQueryWithoutScopes();
    }

    ///////////////
    //* Options *//
    ///////////////
    /**
     * Creates and returns a fresh instance of the model represented by the resource.
     *
     * @return mixed
     */
    public static function newModel()
    {
        // Determine the model class
        $model = static::$model;

        // Create and return a new model
        return new $model;
    }

    /**
     * Returns the schema builder.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    public function getSchema()
    {
        return $this->resolver->connection($this->connection)->getSchemaBuilder();
    }

    /**
     * Returns the fo;e path to the seed data file.
     *
     * @return string
     */
    public function getFilePath()
    {
        return rtrim($this->getRootPath(), '\\/') . DIRECTORY_SEPARATOR . $this->getFilename();
    }

    /**
     * Returns the path to the seed data file directory.
     *
     * @return string
     */
    public function getRootPath()
    {
        return $this->root ?: static::getDefaultRootPath();
    }

    /**
     * Returns the default path to the seed data file directory.
     *
     * @return string
     */
    public static function getDefaultRootPath()
    {
        return database_path() . DIRECTORY_SEPARATOR . 'seeds/data';
    }

    /**
     * Returns the filename of the seed data file.
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->filename ?: static::getDefaultFilename();
    }

    /**
     * Returns the default filename of the seed data file.
     *
     * @return string
     */
    public static function getDefaultFilename()
    {
        return static::newModel()->getTable() . '.csv';
    }
}
