<?php

namespace RobbieP\CloudConvertLaravel;

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

	public function getConversionSettings()
	{
		return [
			'input' => $this->getFile()->options(),
			'outputformat' =>  $this->output->getFormat(),
			'file' => $this->getFile()->getPath(),
			'converteroptions' =>  $this->output->getConverterOptions(),
			'preset' =>$this->output->getPreset(),
			'output' => $this->output->getStorage()
		];
	}
}