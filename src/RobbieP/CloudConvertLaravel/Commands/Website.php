<?php namespace RobbieP\CloudConvertLaravel\Commands;

use Illuminate\Console\Command;
use RobbieP\CloudConvertLaravel\CloudConvert;
use RobbieP\CloudConvertLaravel\Commands\CloudConvertCommandTrait;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class Website extends Command {

	use CloudConvertCommandTrait;
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'cloudconvert:website';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Take a screenshot of any website';


	/**
	 * Create a new command instance.
	 *
	 * @param CloudConvert $cloudConvert
	 * @return \RobbieP\CloudConvertLaravel\Commands\Website
	 */
	public function __construct(CloudConvert $cloudConvert)
	{
		$this->cloudConvert = $cloudConvert;
		$this->cloudConvert->setConfig(\Config::get('cloudconvert-laravel::config'));
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		$url = $this->argument('url');
		$outputfile = $this->argument('outputfile');
		$path = $this->option('path');
		$storage = $this->option('storage');
		$options = $this->option('option');
		$this->checkAPI();
		$this->comment('Process starting...');

		$out = (!empty($storage)) ? $this->cloudConvert->getStorageInstance(strtolower($storage), ['path' => $this->getOutputPath($outputfile, $path)]) : $this->getOutputPath($outputfile, $path);


		$process = $this->cloudConvert->website($url);

		if(!empty($options)) {
			$o = $this->parseOptions($options);
			$process->withOptions($o);
		}
		$process->to($out);
		$this->info("Successfully captured <comment>$url</comment> to ".basename($outputfile));
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return [
			['url', InputArgument::REQUIRED, 'The url you want to take a screen shot of'],
			['outputfile', InputArgument::REQUIRED, 'The output file'],
		];
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return [
			['path', 'p', InputOption::VALUE_OPTIONAL, 'Path to the out file (optional)', null],
			['storage', 's', InputOption::VALUE_OPTIONAL, 'Choose the storage provider. Either FTP or S3', null],
			['option', 'o', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Converter options (eg. --option=quality:90)', []],
		];
	}


}
