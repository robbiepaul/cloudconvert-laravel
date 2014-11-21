<?php

namespace RobbieP\CloudConvertLaravel;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ConvertLocalFile extends Convert implements ConvertInterface {

	function __construct($file, $converteroptions = null)
	{
		parent::__construct($file, $converteroptions);
		$this->setMethod(CloudConvert::INPUT_UPLOAD);
		$this->setFilesystem();
		if($file instanceof UploadedFile) {
			$this->setFile($file->getFilename());
		}
	}

	public function setFilesystem($fileSystem = null)
	{
		$this->fileSystem = (! is_null($fileSystem) ) ? $fileSystem : new Filesystem();
	}

	public function save()
	{
		if($this->validateSave()) {
			return $this->saveFile($this->getFilepath(), $this->getData());
		}
		throw new \Exception('File not writable or no data available: '.$this->getFilepath());
	}

	/**
	 * @return bool
     */
	protected function validateSave()
	{
		return $this->fileSystem->isWritable($this->getPath()) && $this->getData();
	}

	public function getConversionSettings($output)
	{
		$output->filenameCheck($this);
		return [
			'input' => CloudConvert::INPUT_UPLOAD,
			'outputformat' => $output->getFormat(),
			'file' => @fopen($this->getFilepath(), 'r'),
			'converteroptions' => $output->getConverterOptions(),
			'preset' => $output->getPreset(),
			'output' => $output->getStorage()
		];
	}

	private function saveFile($file_path, $data)
	{
		if(is_array($data)) {
			foreach($data as $k => $file) {
				$this->saveFile($this->getPath().'/'.$file['filename'],$file['data']);
			}
			return true;
		}
		return $this->fileSystem->put($file_path, $data);
	}
}