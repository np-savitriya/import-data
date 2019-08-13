<?php
/**
 * Created by PhpStorm.
 * User: ravin
 * Date: 4/3/19
 * Time: 12:05 PM
 */

namespace Import\ImportData;

use Illuminate\Support\ServiceProvider;

class ImportDataServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Import::class, function () {
            return new ImportData();
        });

        $this->app->alias(ImportDataError::class, 'import_data');
    }

    public function boot() {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadRoutesFrom(__DIR__.'/routes.php');
    }
}
