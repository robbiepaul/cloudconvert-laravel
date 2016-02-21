<?php


abstract class BaseTest extends PHPUnit_Framework_TestCase {


	public static function bootstrapLaravel()
	{

	}

	protected  $cloudConvert;
	protected  $client;
	protected  $process_client;
	protected  $config;


	function __construct ()
	{
		date_default_timezone_set('Europe/London');
		self::bootstrapLaravel();
		parent::__construct();
		$this->config = Mockery::mock('\RobbieP\CloudConvertLaravel\Config')->shouldReceive('get')->andReturn('VALUE');
		$this->cloudConvert = new \RobbieP\CloudConvertLaravel\CloudConvert();
		$client = $this->mockClient();
		$this->cloudConvert->setClient($client);
	}

	public function mockInputUpload()
	{
		$uploaded_file = Mockery::mock(
			'\Symfony\Component\HttpFoundation\File\UploadedFile',
			[
				'getClientOriginalName'      => 'image-1.jpg',
				'getFilename'                => '/tmp/image-1.jpg',
				'getClientOriginalExtension' => 'jpg',
				'getPathname'                => '/tmp/image-1.jpg'
			]
		);
		$this->cloudConvert->file($uploaded_file);
	}

	/**
	 * @return string
	 */
	public function mockInputUrl()
	{
		$url = 'http://mirrors.creativecommons.org/presskit/logos/cc.logo.large.png';
		$this->cloudConvert->file($url);

		return $url;
	}

	/**
	 * @return string
     */
	public function mockInputWebsite()
	{
		$url = 'http://www.google.co.uk';
		$this->cloudConvert->website($url);

		return $url;
	}

	/**
	 * @return string
	 */
	public function mockInputFilepath()
	{
		$file_path = __DIR__ . '/stubs/tc.jpg';
		$this->cloudConvert->file($file_path);

		return $file_path;
	}

	public function mockInputStorageS3()
	{
		$storage = Mockery::mock(
			'\RobbieP\CloudConvertLaravel\StorageS3',
			[
				'getMethod' => 's3',
				'getPath'   => '/some/path/on/bucket/image-1.jpg'
			]
		);
		$this->cloudConvert->file($storage);
	}

	public function mockInputStorageFTP()
	{
		$storage = Mockery::mock(
			'\RobbieP\CloudConvertLaravel\StorageFtp',
			[
				'getMethod' => 'ftp',
				'getPath'   => '/some/path/on/ftp/test.image.png'
			]
		);
		$this->cloudConvert->file($storage);
	}

	public function mockClient()
	{
		$this->client = Mockery::mock(
			'\RobbieP\CloudConvertLaravel\HttpClientInterface'
		);
		return $this->client;
	}

	public function mockProcessClient()
	{
		$this->process_client = Mockery::mock(
			'\RobbieP\CloudConvertLaravel\HttpClientInterface'
		);
		return $this->process_client;
	}


}