<?php namespace RobbieP\CloudConvertLaravel;


abstract class Storage {

	protected $outputformat;
	public $path;

	/**
	 * @return mixed
	 */
	public function __toString()
	{
		return $this->getMethod();
	}

	/**
	 * @return array
	 */
	public function options()
	{
		$this->validateCredentials();
		return [static::INPUT_METHOD => Helpers::getPublicObjectVars($this)];
	}

	/**
	 * @param $options
	 */
	public function setOptions($options)
	{
		if(!empty($options)) {
			foreach($options as $k => $option) {
				$this->{$k} = $option;
			}
		}
	}

	public function setFormat($format)
	{
		$this->outputformat = $format;
	}

	public function getOutputFormat()
	{
		return $this->outputformat;
	}

	public function setPath($path)
	{
		$this->path = $path;
	}

}
