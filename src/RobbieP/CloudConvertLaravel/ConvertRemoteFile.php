<?php

namespace RobbieP\CloudConvertLaravel;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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

	public function getConversionSettings($output)
	{
		$output->filenameCheck($this);
		return [
			'input' => CloudConvert::INPUT_DOWNLOAD,
			'outputformat' => $output->getFormat(),
			'filename' => $this->getFilename(),
			'link' => $this->getFile(),
			'converteroptions' =>  $output->getConverterOptions(),
			'preset' =>$output->getPreset(),
			'output' =>$output->getStorage()
		];
	}
}