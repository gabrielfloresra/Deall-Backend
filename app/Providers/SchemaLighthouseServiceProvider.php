<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Nuwave\Lighthouse\Schema\Source\SchemaSourceProvider;
use Nuwave\Lighthouse\Schema\Source\SchemaStitcher;

class SchemaLighthouseServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton( SchemaSourceProvider::class, function (): SchemaStitcher {
            $publicSchema = new SchemaStitcher(
                config( 'lighthouse.schema.public', '' )
            );
            if( !isset( $_GET['key'] ) ){
                return $publicSchema;
            }
            
            $key = $_GET['key'];
            $validRutes =['admin','company','client'];
            if( !in_array($key, $validRutes) ){
                return $publicSchema;
            }
            
            return new SchemaStitcher(
                config( 'lighthouse.schema.'.$key, '' )
            );
        });
    }

}
