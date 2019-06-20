<?php

namespace Besemuna\LaravelApiCrudGenerator;

use Illuminate\Support\ServiceProvider;

class LaravelApiCrudGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->make('Besemuna\LaravelApiCrudGenerator\TestClass');
        $this->commands(
            'Besemuna\LaravelApiCrudGenerator\Command\GenerateCommand'
        );


    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
