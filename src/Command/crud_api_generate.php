<?php

namespace Besemuna\LaravelApiCrudGenerator\Command;

use Illuminate\Console\Command;

class GenerateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravel_crud:generate
                            {name : The name of the crud}
                            {--fields= : Field names for controller and migration.}
                            {--fields_from_file= : Field names from a json file .}
                            {--foreign_key= : Foreign keys for migration.}
                            {--foreign_key_from_file= : Foreign keys from a json file .}
                            {--soft_deletes= : Allow for soft deletes .}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command generate crud endpoints on the fly!';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
    }
}
