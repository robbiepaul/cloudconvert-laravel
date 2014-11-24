<?php

namespace RobbieP\CloudConvertLaravel;

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
		return [
			'input' => CloudConvert::INPUT_DOWNLOAD,
			'outputformat' => $this->output->getFormat(),
			'filename' => $this->getFilename(),
			'file' => $this->getFile(),
			'converteroptions' =>  $this->output->getConverterOptions(),
			'preset' =>$this->output->getPreset(),
			'output' =>$this->output->getStorage()
		];
	}






}



