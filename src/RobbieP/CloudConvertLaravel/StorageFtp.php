<?php


namespace RobbieP\CloudConvertLaravel;


use Config;

class StorageFTP extends Storage implements  StorageInterface {

	const INPUT_METHOD = 'ftp';

	public $host;
	public $port = 21;
	public $user;
	public $password;
	public $path;

	function __construct() {
		$this->host = Config::get('cloudconvert-laravel::ftp.host');
		$this->user =  Config::get('cloudconvert-laravel::ftp.user');;
		$this->password =  Config::get('cloudconvert-laravel::ftp.password');
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