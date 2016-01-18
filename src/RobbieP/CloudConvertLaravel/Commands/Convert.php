<?php namespace RobbieP\CloudConvertLaravel\Commands;

use Illuminate\Console\Command;
use RobbieP\CloudConvertLaravel\CloudConvert;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class Convert extends Command {

	use CloudConvertCommandTrait;


	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'cloudconvert:convert';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Convert one file to another using the CloudConvert API';

	/**
	 * Create a new command instance.
	 *
	 * @param CloudConvert $cloudConvert
	 * @return \RobbieP\CloudConvertLaravel\Commands\Convert
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
		$inputfile = $this->argument('inputfile');
		$outputfile = $this->argument('outputfile');
		$path = $this->option('path');
		$storage = $this->option('storage');
		$options = $this->option('option');
		$queue = $this->option('background');
		$this->checkAPI();
		$this->comment('Conversion process starting...');

		$out = (!empty($storage)) ? $this->cloudConvert->getStorageInstance(strtolower($storage), ['path' => $this->getOutputPath($outputfile, $path)]) : $this->getOutputPath($outputfile, $path);

		$in = (empty($path)) ? $inputfile : $path.DIRECTORY_SEPARATOR.$inputfile;

		$process = $this->cloudConvert->file($in);
		if(!empty($options)) {
			$o = $this->parseOptions($options);
			$process->withOptions($o);
		}
		if(empty($queue)) {
			$process->to($out);
			$this->info('Successfully converted '.basename($inputfile).' to '.basename($outputfile));
		} else {
			$process->queue('to', $outputfile);
			$this->info('Added to your queue: '.basename($inputfile).' to '.basename($outputfile));
		}

	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return [
			['inputfile', InputArgument::REQUIRED, 'The input file'],
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
			['path', 'p', InputOption::VALUE_OPTIONAL, 'Path to the files if not provided.', null],
			['storage', 's', InputOption::VALUE_OPTIONAL, 'Choose the storage provider. Either FTP or S3', null],
			['background', 'b', InputOption::VALUE_OPTIONAL, 'Run the conversion in the background. (Must have a queue system running ie. Beanstalkd)', null],
			['option', 'o', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Converter options (eg. --option=quality:90)', []],
		];
	}


}
