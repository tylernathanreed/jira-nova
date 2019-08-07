<?php

namespace App\Support\Database\Seeds;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Database\ConnectionResolverInterface as Resolver;

class DatabaseSeedGenerateCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'db:seed:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates seed data from the database';

    /**
     * The connection resolver instance.
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $filesystem;

    /**
     * Create a new database seed command instance.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     * @param  \Illuminate\Filesystem\Filesystem                 $filesystem
     *
     * @return $this
     */
    public function __construct(Resolver $resolver, Filesystem $filesystem)
    {
        parent::__construct();

        $this->resolver = $resolver;
        $this->filesystem = $filesystem;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // Confirm to proceed
        if(!$this->confirmToProceed()) {
            return;
        }

        $this->resolver->setDefaultConnection($this->getDatabase());

        $this->getSeedGenerator()->generate();

        $this->info('Seed data generated successfully.');
    }

    /**
     * Returns the seed generator instance from the container.
     *
     * @return \Illuminate\Database\Seeder
     */
    protected function getSeedGenerator()
    {
        $class = $this->laravel->make($this->input->getOption('class'), ['resolver' => $this->resolver]);

        return $class->setContainer($this->laravel)->setCommand($this);
    }

    /**
     * Returns the name of the database connection to use.
     *
     * @return string
     */
    protected function getDatabase()
    {
        $database = $this->input->getOption('database');

        return $database ?: $this->laravel['config']['database.default'];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['class', null, InputOption::VALUE_REQUIRED, 'The class name of the seed generator'],

            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to seed'],

            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production'],
        ];
    }
}