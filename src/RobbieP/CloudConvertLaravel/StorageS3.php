<?php namespace RobbieP\CloudConvertLaravel;

class StorageS3 extends Storage implements StorageInterface {

	const INPUT_METHOD = 's3';

	public $accesskeyid;
	public $secretaccesskey;
	public $bucket;
	public $path;
	public $acl;
	public $region;
	private $config;
	protected $outputformat;

	/**
	 * @param Config $config
	 */
	function __construct($config) {
		$this->config = $config;
		if(is_object($config)) {
			$this->accesskeyid = $this->config->get('s3.accesskeyid');
			$this->secretaccesskey = $this->config->get('s3.secretaccesskey');
			$this->bucket = $this->config->get('s3.bucket');
			$this->acl = $this->config->get('s3.acl');
			$this->region = $this->config->get('s3.region');
		}
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