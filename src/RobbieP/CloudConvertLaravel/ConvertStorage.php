<?php

namespace RobbieP\CloudConvertLaravel;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ConvertStorage extends Convert implements ConvertInterface {

	function __construct($file, $converteroptions = null)
	{
		parent::__construct($file, $converteroptions);
		$this->setMethod($file->getMethod());
		$this->setFile($file->getPath());
		$this->file = $file;
	}

	public function save()
	{
		return  false;
	}

	public function getMethod()
	{
		return $this->getFile()->getMethod();
	}

	public function getStorage()
	{
		return $this->getFile()->options();
	}

	public function getConversionSettings($output)
	{
		$output->filenameCheck($this);
		return [
			'input' => $this->getFile()->options(),
			'outputformat' =>  $output->getFormat(),
			'file' => $this->getFile()->getPath(),
			'converteroptions' =>  $output->getConverterOptions(),
			'preset' =>$output->getPreset(),
			'output' => $output->getStorage()
		];
	}
}