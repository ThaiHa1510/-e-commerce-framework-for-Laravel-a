<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ShopProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        
        $this->app->singleton('app.context', function($app) {
			return new \App\Services\Context($app['config']);
		});
        $this->app->singleton('shop', function($app) {
            
			return new \App\Services\Shop($app['app.context']);
        });
        

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
