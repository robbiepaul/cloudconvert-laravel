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
		$this->package('robbiep/cloudconvert-laravel');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerCloudConvertCommands();

		$this->app['cloudconvert'] = $this->app->share(function($app)
		{
			return new CloudConvert($app['config']->get('cloudconvert-laravel::config'));
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

	public function registerCloudConvertCommands()
	{
		$this->app['cloudconvert.convert'] = $this->app->share(function()
		{
			$cloudconvert = $this->app->make('RobbieP\CloudConvertLaravel\CloudConvert');

			return new Commands\Convert($cloudconvert);
		});
		$this->commands('cloudconvert.convert');

		$this->app['cloudconvert.types'] = $this->app->share(function ()
		{
			$cloudconvert = $this->app->make('RobbieP\CloudConvertLaravel\CloudConvert');

			return new Commands\ConversionTypes($cloudconvert);
		});
		$this->commands('cloudconvert.types');

		$this->app['cloudconvert.processes'] = $this->app->share(function ()
		{
			$cloudconvert = $this->app->make('RobbieP\CloudConvertLaravel\CloudConvert');

			return new Commands\Processes($cloudconvert);
		});
		$this->commands('cloudconvert.processes');

		$this->app['cloudconvert.website'] = $this->app->share(function ()
		{
			$cloudconvert = $this->app->make('RobbieP\CloudConvertLaravel\CloudConvert');

			return new Commands\Website($cloudconvert);
		});
		$this->commands('cloudconvert.website');
	}

}
