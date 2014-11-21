<?php

namespace RobbieP\CloudConvertLaravel;


use Config;

class StorageS3 extends Storage implements StorageInterface {

	const INPUT_METHOD = 's3';

	public $accesskeyid;
	public $secretaccesskey;
	public $bucket;
	public $path;
	public $acl;
	public $region;

	function __construct() {
		$this->accesskeyid = Config::get('cloudconvert-laravel::s3.accesskeyid');
		$this->secretaccesskey = Config::get('cloudconvert-laravel::s3.secretaccesskey');
		$this->bucket =  Config::get('cloudconvert-laravel::s3.bucket');
	}
	public function validateCredentials()
	{
		if(empty($this->accesskeyid)) {
			throw new \Exception('Must provide the accesskeyid for your S3 account');
		}
		if(empty($this->secretaccesskey)) {
			throw new \Exception('Must provide the secretaccesskey for your S3 account');
		}
		if(empty($this->bucket)) {
			throw new \Exception('Must provide the bucket for your S3 account');
		}
	}



	/**
	 * @return mixed
	 */
	public function getPath()
	{
		return $this->path;
	}

	public function getMethod()
	{
		return self::INPUT_METHOD;
	}


}