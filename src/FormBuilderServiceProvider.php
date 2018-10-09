<?php

namespace Yashwantsb\Formbuilder;

use Illuminate\Support\ServiceProvider;

class FormBuilderServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        $this->loadViewsFrom(__DIR__.'/views','FormBuilder');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->mergeConfigFrom(
            __DIR__.'/config/formbuilder.php', 'FormBuilder'
        );
        $this->publishes([
            __DIR__.'/config/formbuilder.php' => config_path('formbuilder.php'),
        ]);
    }

    public function register()
    {

    }
}