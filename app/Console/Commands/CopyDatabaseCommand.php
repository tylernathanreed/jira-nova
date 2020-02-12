<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\ConnectionResolverInterface as Resolver;

class CopyDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @option  {string}  "source"       The source connection name.
     * @option  {string}  "destination"  The destination connection name.
     *
     * @var string
     */
    protected $signature = 'db:copy {source} {destination}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Copies the data from one database to another';

    /**
     * Execute the console command.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     *
     * @return mixed
     */
    public function handle(Resolver $resolver)
    {
        // Determine the tables to copy
        $tables = $this->getApplicableTables($resolver);

        // Wrap everything within a transaction
        $this->getDestinationConnection($resolver)->transaction(function() use ($resolver, $tables) {

            // Disable foreign keys for on the destination
            $this->getDestinationConnection($resolver)->getSchemaBuilder()->disableForeignKeyConstraints();

            // Copy the data from each table
            foreach($tables as $table) {
                $this->copyTableData($resolver, $table);
            }

            // Enable foreign keys for on the destination
            $this->getDestinationConnection($resolver)->getSchemaBuilder()->enableForeignKeyConstraints();

        });
    }

    /**
     * Copies the data from the specified table.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @param  string                                            $table
     *
     * @return void
     */
    public function copyTableData(Resolver $resolver, string $table)
    {
        // Start the broadcast
        $this->comment("Copying [{$table}] table...");

        // Extract the records as a generator
        $records = $this->getSourceConnection($resolver)->table($table)->cursor();

        // Insert each record into the destination table
        foreach($records as $record) {
            $this->getDestinationConnection($resolver)->table($table)->insert((array) $record);
        }

        // End the broadcast
        $this->info("Copied [{$table}] table.");
    }

    /**
     * Returns the applicable tables to copy.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     *
     * @return array
     */
    public function getApplicableTables(Resolver $resolver)
    {
        return array_values(array_intersect(
            $this->getApplicableSourceTables($resolver),
            $this->getApplicableDestinationTables($resolver)
        ));
    }

    /**
     * Returns the applicable tables from the source connection.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     *
     * @return array
     */
    public function getApplicableSourceTables(Resolver $resolver)
    {
        return $this->getApplicableTablesFromConnection($resolver, $this->argument('source'));
    }

    /**
     * Returns the applicable tables from the destination connection.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     *
     * @return array
     */
    public function getApplicableDestinationTables(Resolver $resolver)
    {
        return $this->getApplicableTablesFromConnection($resolver, $this->argument('destination'));
    }

    /**
     * Returns the applicable tables from the specified connection name.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @param  string                                            $name
     *
     * @return array
     */
    public function getApplicableTablesFromConnection(Resolver $resolver, string $name)
    {
        return array_diff($this->getAllTablesFromConnection($resolver, $name), $this->getUnapplicableTables());
    }

    /**
     * Returns all of the tables from the specified connection name.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @param  string                                            $name
     *
     * @return array
     */
    public function getAllTablesFromConnection(Resolver $resolver, string $name)
    {
        return array_map(function($table) {
            return $table->getName();
        }, $resolver->connection($name)->getDoctrineSchemaManager()->listTables());
    }

    /**
     * Returns the tables that can't or shouldn't be copied.
     *
     * @return array
     */
    public function getUnapplicableTables()
    {
        return [
            'jobs',
            'migrations',
            'telescope_entries',
            'telescope_entries_tags',
            'telescope_monitoring'
        ];
    }

    /**
     * Creates and returns a connection to the source database.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     *
     * @return \Illuminate\Database\ConnectionInterface
     */
    public function getSourceConnection(Resolver $resolver)
    {
        return $resolver->connection($this->argument('source'));
    }

    /**
     * Creates and returns a connection to the destination database.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     *
     * @return \Illuminate\Database\ConnectionInterface
     */
    public function getDestinationConnection(Resolver $resolver)
    {
        return $resolver->connection($this->argument('destination'));
    }
}
