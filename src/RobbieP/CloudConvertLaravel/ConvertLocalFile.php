<?php namespace RobbieP\CloudConvertLaravel;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ConvertLocalFile extends Convert implements ConvertInterface {

	private $uploadedFile;
	protected $wait = true;

	function __construct($file, $converteroptions = null)
	{
		parent::__construct($file, $converteroptions);
		$this->setMethod(CloudConvert::INPUT_UPLOAD);
		$this->setFilesystem();
		if($file instanceof UploadedFile) {
			$this->uploadedFile = $file;
			$this->setFile($file->getPathname());
			$this->setFormat($file->getClientOriginalExtension());
		} else {

			$this->setFile($file);

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

	public function getConversionSettings()
	{
		$settings = [
			'input' => CloudConvert::INPUT_UPLOAD,
			'outputformat' => $this->output->getFormat(),
			'file' => $this->getInputFile(),
			'filename' => $this->getInputFilename(),
			'converteroptions' => $this->output->getConverterOptions(),
			'preset' => $this->output->getPreset(),
			'output' => $this->output->getStorage(),
			'wait' => $this->output->shouldWait()
		];

		if ($this->uploadedFile !== null) {
			$settings['filename'] = $this->uploadedFile->getClientOriginalName();
		}

		return $settings;
	}

	public function getInputFile()
	{
		return @fopen($this->getFilepath(), 'r');
	}


	public function getInputFilename()
	{
		return basename($this->getFilepath());
	}

	/**
	 * @param string $file_path
	 * @return bool
	 */
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