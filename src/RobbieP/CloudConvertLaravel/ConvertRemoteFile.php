<?php namespace RobbieP\CloudConvertLaravel;

use Illuminate\Filesystem\Filesystem;

class ConvertRemoteFile extends Convert implements ConvertInterface {

	function __construct($file, $converteroptions = null)
	{
		parent::__construct($file, $converteroptions);
		$this->setMethod(CloudConvert::INPUT_DOWNLOAD);
		$this->setFilesystem();
	}

	public function setFilesystem($fileSystem = null)
	{
		$this->fileSystem = (! is_null($fileSystem) ) ? $fileSystem : new Filesystem();
	}

	public function save()
	{
		if($this->validateSave()) {
			return $this->fileSystem->put($this->getFilepath(), $this->getData());
		}
		throw new \Exception('File not writable: '.$this->getFilepath());
	}

	protected function validateSave()
	{
		return $this->fileSystem->isWritable($this->getFilepath()) && $this->getData();
	}

	public function getConversionSettings()
	{
		$file = $this->getFile();
		$data = [
			'input' => CloudConvert::INPUT_DOWNLOAD,
		];

		if(is_array($file)) {
			$data['files'] = $this->getFile();
		} else {
			$data['filename'] = $this->getFilename();
			$data['file'] = $this->getFile();
		}
		if(!empty($this->output)) {
			$data['outputformat'] = $this->output->getFormat();
			$data['converteroptions'] =  $this->output->getConverterOptions();
			$data['preset'] = $this->output->getPreset();
			$data['output'] = $this->output->getStorage();
			$data['wait'] = $this->output->shouldWait();
		} else {
			$data['converteroptions'] =  $this->getConverterOptions();
			$data['wait'] = $this->shouldWait();
		}
		return $data;
	}



}



