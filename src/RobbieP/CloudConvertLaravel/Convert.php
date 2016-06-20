<?php namespace RobbieP\CloudConvertLaravel;


abstract class Convert {

	protected $path;
	protected $file;
	protected $filename;
	protected $properties;
	protected $format;
	protected $method;
	protected $type;
	protected $fileSystem;
	protected $data;
	protected $converteroptions;
	protected $preset;
	protected $seperator;
	protected $output;
	protected $wait = true;

	/**
	 * @param $file
	 * @param null $converteroptions
	 * @internal param $properties
	 */
	function __construct($file, $converteroptions = null)
	{
		if( ! is_null($file) ) $this->setFile($file);
		if( ! is_null($converteroptions) ) $this->setConverterOptions($converteroptions);
		$this->seperator = '/'; //DIRECTORY_SEPARATOR
	}

	/**
	 * @return mixed
	 */
	public function getFile()
	{
		return $this->file;
	}

	/**
	 * @return string
     */
	public function getFilepath()
	{
		return (isset($this->path) && !empty($this->path)) ? $this->path . $this->seperator . $this->filename : $this->getFilename();
	}

	/**
	 * @param mixed $file
	 * @return $this
	 */
	public function setFile($file)
	{
		if($this->isPath($file)) {
			$this->setPath($file);
			return $this;
		}

		if($this->isFormat($file)) {
			$this->setFormat($file);
			return $this;
		}
		$this->file = $file;
		if(!$this->isURL($file)) {
			$this->setPath(dirname($file));
		} else {
			$this->setPath('./');
		}
		$this->setFilename(basename($file));

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getConverterOptions()
	{
		return !empty( $this->converteroptions ) ? $this->converteroptions : null;
	}

	/**
	 * @return null
     */
	public function getPreset()
	{
		return !empty( $this->preset ) ? $this->preset : null;
	}


	/**
	 * @param mixed $properties
	 */
	public function setConverterOptions($properties = [])
	{
		if(!empty($properties))
		{
			foreach ($properties as $key => $name)
			{
				$this->addConverterOptions($key, $name);
			}
		}
	}

	/**
	 * @param $key
	 * @param $name
     */
	public function addConverterOptions($key, $name)
	{
		$this->converteroptions[$key] = $name;
	}

	/**
	 * @return string
	 * @throws \Exception
     */
	public function getExtension()
	{
		if( $filepath = $this->getFilepath() ) {
			return $this->parseExtension($filepath);
		}
		throw new \Exception( 'Unknown file path' );
	}

	/**
	 * @return string
	 * @throws \Exception
     */
	public function getFormat()
	{
		if( is_null($this->format) ) {
			$this->format = $this->getExtension();
			if(empty($this->format) && $this instanceof ConvertStorage && $this->getFile() && !empty($this->getFile()->getOutputFormat())) {
				$this->format = $this->getFile()->getOutputFormat();
			}
		}
		$this->validateFormat($this->format);
		return $this->format;
	}

	/**
	 * @param mixed $format
	 */
	public function setFormat($format)
	{
		$this->validateFormat($format);
		$format = $this->stripQueryString($format);
		$this->format = $format;
	}

	/**
	 * @param mixed $path
	 */
	public function setPath($path)
	{
		$this->path = str_replace('http:', '', rtrim($path, '/'));
	}

	/**
	 * @return string
	 */
	public function getPath()
	{
		return empty($this->path) ? '.' : $this->path;
	}

	/**
	 * @return mixed
	 */
	public function getFilename()
	{
		return $this->filename;
	}

	/**
	 * @param mixed $filename
	 * @param null|string $ext
	 */
	public function setFilename($filename, $ext = '')
	{
		$filename = $this->stripQueryString($filename);
		$this->filename = empty($ext) ? $filename : preg_replace("/{$this->parseExtension($filename)}$/", $ext, $filename);
	}

	/**
	 * @return mixed
	 */
	public function getMethod()
	{
		return $this->method;
	}

	/**
	 * @param mixed $method
	 */
	public function setMethod($method)
	{
		$this->method = $method;
	}

	/**
	 * @return mixed
	 */
	public function getData()
	{
		return $this->data;
	}

	public function getStorage()
	{
		return null;
	}

	/**
	 * @param mixed $data
	 */
	public function setData($data)
	{
		$this->data = $data;
	}

	/**
	 * @param mixed $preset
	 */
	public function setPreset($preset)
	{
		$this->preset = $preset;
	}

	/**
	 * @param Convert $input
     */
	public function filenameCheck(Convert $input)
	{
		if (empty($this->filename) && empty($this->path)) {
			$this->setPath($input->getPath());
			$this->setFilename($input->getFilename(), $this->getFormat());
		}
	}

	/**
	 * @param $file
	 * @return bool
     */
	protected function isPath($file)
	{
		return $this->parseExtension($file) === '' && strstr($file, '/') && is_file($file) === false;
	}

	/**
	 * @param string $file_path
	 * @return string
     */
	protected function parseExtension($file_path = '')
	{
		$ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
		$ext = preg_replace("/(\?.*)/i", '', $ext);
		return $ext;
	}

	/**
	 * @param $format
	 * @throws \Exception
     */
	protected function validateFormat($format)
	{
		if( empty($format) || ! $this->isFormat($format) ) {
			throw new \Exception('Invalid format');
		}
	}

	/**
	 * @param $format
	 * @return bool
     */
	protected  function isFormat($format)
	{
		if($format instanceof Storage) return false;
		$format = $this->stripQueryString($format);
		return ctype_alnum($format);
	}

	/**
	 * @param Convert $output
     */
	public function prepareOutput(Convert $output)
	{
		$output->filenameCheck($this);
		$this->output = $output;
	}

	/**
	 * @return mixed
     */
	public function toArray()
	{
		return $this->getConversionSettings();
	}

	/**
	 * @param $format
	 * @return mixed
	 */
	protected function stripQueryString($format)
	{
		$format = preg_replace("/(\?.*)/i", '', $format);
		return $format;
	}

	private function isURL($url)
	{
		return filter_var($url, FILTER_VALIDATE_URL);
	}

	public function shouldWait()
	{
		return $this->wait;
	}

}