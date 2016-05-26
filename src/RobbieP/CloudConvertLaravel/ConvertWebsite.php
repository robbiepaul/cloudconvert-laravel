<?php namespace RobbieP\CloudConvertLaravel;

use Illuminate\Support\Str;

class ConvertWebsite extends Convert implements ConvertInterface {

	function __construct($file, $converteroptions = null)
	{
		parent::__construct($file, $converteroptions);
		$this->setMethod(CloudConvert::INPUT_URL);
		$this->setFormat(CloudConvert::INPUT_WEBSITE);
	}


	public function save()
	{
		return false;
	}

	public function setFile($file)
	{
		$file = $this->fixMalformedURL($file);
		$this->validateURL($file);
		$this->file = $file;
		return $this;
	}


	public function getFilename()
	{
		return 'Screenshot_'.Str::slug(str_replace('http','',$this->file)) . '_'.date('dmYHs').'.website';
	}

	public function getConversionSettings()
	{
		return [
			'input' => CloudConvert::INPUT_URL,
			'outputformat' => $this->output->getFormat(),
			'filename' => $this->getFilename(),
			'file' => $this->getFile(),
			'converteroptions' =>  $this->output->getConverterOptions(),
			'preset' =>$this->output->getPreset(),
			'output' =>$this->output->getStorage(), 
			'wait' => $this->output->shouldWait()
		];

	}

	/**
	 * @param string $url
	 * @throws \Exception
	 */
	public function validateURL($url)
	{
		if (!filter_var($url, FILTER_VALIDATE_URL))
		{
			throw new \Exception('Not a valid URL. Must be fully qualified including protocol.');
		}
	}

	/**
	 * @param $file
	 * @return string
	 */
	private function fixMalformedURL($file)
	{
		if (!preg_match("/^(https?:\/\/)/i", $file)) {
			$file = "http://$file";
			return $file;
		}
		return $file;
	}
}