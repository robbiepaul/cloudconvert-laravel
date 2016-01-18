<?php namespace RobbieP\CloudConvertLaravel\Facades;

use Illuminate\Support\Facades\Facade;

class CloudConvert extends Facade  {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'cloudconvert'; }

}