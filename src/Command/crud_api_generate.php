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
    protected $signature = 'laravel_crud:generate';

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
