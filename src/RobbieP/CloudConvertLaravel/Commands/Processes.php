<?php namespace RobbieP\CloudConvertLaravel\Commands;

use Illuminate\Console\Command;
use RobbieP\CloudConvertLaravel\CloudConvert;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class Processes extends Command {

	use CloudConvertCommandTrait;

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'cloudconvert:processes';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Show all your CloudConvert processes';


	/**
	 * Create a new command instance.
	 *
	 * @param CloudConvert $cloudConvert
	 * @return \RobbieP\CloudConvertLaravel\Commands\Processes
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
	 * @return null|boolean
	 */
	public function fire()
	{
		$this->checkAPI();
		$delete = $this->option('delete');
		$processid = $this->getProcessId();
		$processes = $this->cloudConvert->processes();
		if($delete === true) {
			return $this->deleteProcessByID($processid);
		}
		if( !empty($processid)) {
			return $this->statusProcessByID($processid);
		}
		$headers = ['ID', 'Host', 'Step', 'Time', 'Duration (sec)'];
		$rows = [];
		foreach ($processes as $k => $process) {
			$rows[] = [$process->id, $process->host, $this->stepFormat($process->step), $process->starttime, strtotime($process->endtime) - strtotime($process->starttime)];
		}
		$this->table($headers, $rows);

		return true;
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			['processid', InputArgument::OPTIONAL, 'Get the process status by ID'],
		);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return [
			array('delete', 'd', InputOption::VALUE_NONE, 'Delete a process by ID', null)
		];
	}

	/**
	 * @param $step
	 * @return string
     */
	private function stepFormat($step)
	{
		if($step !== 'finished') {
			return "<error>$step</error>";
		}
		return $step;
	}

	/**
	 * @param $delete
	 */
	private function deleteProcessByID($delete)
	{
		$this->validateProcessRequest($delete);
		$result = $this->cloudConvert->deleteProcess($delete);
		$this->info($result->message);
		return;
	}

	/**
	 * @param $status
	 * @throws \Exception
     */
	private function statusProcessByID($status)
	{
		$this->validateProcessRequest($status);
		$result = $this->cloudConvert->getProcess($status);
		$headers = ['Input', 'Output', 'Options', 'Download link'];
		$options = array_filter((array)$result->converter->options, 'strlen');
		$rows = [
			[$result->input->filename,  $result->output->filename, http_build_query($options, '', "\n"), (isset($result->output->url) ? $result->output->url : $this->stepFormat($result->step)) ]
		];
		$this->table($headers, $rows);
		return;
	}

	/**
	 * @return array|string
	 */
	private function getProcessId()
	{
		$processid = $this->argument('processid');

		return $processid;
	}

	/**
	 * @param $delete
	 * @throws \Exception
	 */
	private function validateProcessRequest($delete)
	{
		if (empty($delete)) {
			throw new \Exception('No process ID provided');
		}
	}

}
