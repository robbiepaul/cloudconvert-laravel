<?php namespace RobbieP\CloudConvertLaravel;

use Illuminate\Filesystem\Filesystem;

class Queued {

	/**
	 * TODO: Tidy this up / Refactor
	 * @param $job
	 * @param $array
     */
	public function fire($job, $array)
	{
		$action = $array['action'];
		$options = $array['options'];
		$data = $array['data'];
		$process = new CloudConvert($data['api_key']);
		$fileSystem = new Filesystem();
		$process->setFilesystem($fileSystem);
		$process->fill($data);
		$process->init();
		$process->setClient();

		$process->{$action}($options);
		$job->delete();

	}

}