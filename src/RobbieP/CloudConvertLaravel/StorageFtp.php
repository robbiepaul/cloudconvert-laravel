<?php namespace RobbieP\CloudConvertLaravel;


class StorageFTP extends Storage implements  StorageInterface {

	const INPUT_METHOD = 'ftp';

	public $host;
	public $port = 21;
	public $user;
	public $password;
	public $path;
	private $config;

	/**
	 * @param Config $config
	 */
	function __construct($config) {
		$this->config = $config;
		if(is_object($config)) {
			$this->host = $this->config->get('ftp.host');
			$this->user = $this->config->get('ftp.user');
			$this->password = $this->config->get('ftp.password');
		}
	}

	public function validateCredentials()
	{
		if(empty($this->host)) {
			throw new \Exception('Must provide the host for your FTP account');
		}
		if(empty($this->user)) {
			throw new \Exception('Must provide the user for your FTP account');
		}
		if(empty($this->password)) {
			throw new \Exception('Must provide the password for your FTP account');
		}
	}

	public function getPath()
	{
		return $this->path;
	}

	public function getMethod()
	{
		return self::INPUT_METHOD;
	}

}