<?php namespace RobbieP\CloudConvertLaravel;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\AliasLoader;

class CloudConvertLaravelServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
        $this->publishes([
            __DIR__.'/../../config/cloudconvert.php' => config_path('cloudconvert.php'),
        ]);
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->singleton('cloudconvert', function($app)
		{
			return new CloudConvert(config('cloudconvert'));
		});

		$this->app->booting(function()
		{
			$loader = AliasLoader::getInstance();
			$loader->alias('CloudConvert', 'RobbieP\CloudConvertLaravel\Facades\CloudConvert');
		});

	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return string[]
	 */
	public function provides()
	{
		return ['cloudconvert'];
	}

	public function getConfig($key)
	{
		return $this->app['config']["robbiep/cloudconvert-laravel::$key"];
	}



}
