<?php

class FunctionalTest extends BaseTest {

	protected $fileSystem;
	protected $response;

	protected function setUp()
	{
		$this->fileSystem = Mockery::mock('\Illuminate\Filesystem\Filesystem');
		$this->response = Mockery::mock('\GuzzleHttp\Message\Response', ['__construct' => null]);

	}

	public function testCloudConvertApiKey()
	{
		$this->assertFalse($this->cloudConvert->hasApiKey());
		$this->cloudConvert->setApiKey('API_KEY');
		$this->assertTrue($this->cloudConvert->hasApiKey());
		$this->assertSame($this->cloudConvert->getApiKey(), 'API_KEY');
	}

	public function testInputFilepath()
	{
		$file_path = $this->mockInputFilepath();
		$this->assertSame($this->cloudConvert->getInput()->getFormat(), 'jpg');
		$this->assertSame($this->cloudConvert->getInput()->getMethod(),'upload');
	}

	public function testMakeWithOneInput()
	{
		$this->cloudConvert->setApiKey('API_KEY');
		$this->fileSystem->shouldReceive('isFile')->once()->andReturn(true);
		$this->cloudConvert->setFilesystem($this->fileSystem);
		$this->response->url = 'http://process-url';
		$client = $this->mockClient();
		$client->shouldReceive('post')->andReturn($this->response);
		$this->cloudConvert->setClient($client);
		$this->cloudConvert->make('/a/path/to/image.jpg', 'png');
		$this->assertSame($this->cloudConvert->getInputFormat(), 'jpg');
		$this->assertSame($this->cloudConvert->getOutputFormat(), 'png');
		$this->assertSame($this->cloudConvert->getInput()->getMethod(),'upload');
		$this->assertInstanceOf('\RobbieP\CloudConvertLaravel\Process',$this->cloudConvert->getProcess());
	}

	public function testMakeWithTwoInputs()
	{
		$this->cloudConvert->setApiKey('API_KEY');
		$this->fileSystem->shouldReceive('isFile')->once()->andReturn(true);
		$this->cloudConvert->setFilesystem($this->fileSystem);
		$this->response->url = 'http://process-url';
		$client = $this->mockClient();
		$client->shouldReceive('post')->andReturn($this->response);
		$this->cloudConvert->setClient($client);
		$this->cloudConvert->make('/a/path/to/image.gif', 'gif', 'bmp');
		$this->assertSame($this->cloudConvert->getInputFormat(), 'gif');
		$this->assertSame($this->cloudConvert->getOutputFormat(), 'bmp');
		$this->assertSame($this->cloudConvert->getInput()->getMethod(),'upload');
	}

	public function testUsingFileMethod()
	{
		$this->cloudConvert->setApiKey('API_KEY');
		$this->fileSystem->shouldReceive('isFile')->once()->andReturn(true);
		$this->cloudConvert->setFilesystem($this->fileSystem);
		$this->cloudConvert->file('/a/path/to/image.gif');
		$this->assertSame($this->cloudConvert->getInputFormat(), 'gif');
		$this->assertSame($this->cloudConvert->getInput()->getMethod(),'upload');
	}

	public function testUsingFileUploadMethod()
	{
		$this->cloudConvert->setApiKey('API_KEY');
		$uploaded_file = Mockery::mock('\Symfony\Component\HttpFoundation\File\UploadedFile',
			[
				'getClientOriginalName'      => 'image-1.jpg',
				'getFilename'                => '/tmp/image-1.jpg',
				'getClientOriginalExtension' => 'jpg',
				'getPathname'                => '/tmp/image-1.jpg'
			]
		);
		$this->cloudConvert->file($uploaded_file);
		$this->assertSame($this->cloudConvert->getInputFormat(), 'jpg');
		$this->assertSame($this->cloudConvert->getInput()->getMethod(),'upload');
	}

	public function testOutputUsingFileMethod()
	{

		$this->cloudConvert->setApiKey('API_KEY');
		$this->fileSystem->shouldReceive('isFile')->once()->andReturn(true);
		$this->cloudConvert->setFilesystem($this->fileSystem);
		$process = $this->cloudConvert->file('stubs/tv.jpg');
		$this->assertSame($this->cloudConvert->getInputFormat(), 'jpg');
		$this->assertSame($this->cloudConvert->getInput()->getMethod(),'upload');

	}

	public function testWebsiteInput()
	{
		$this->cloudConvert->setApiKey('API_KEY');
		$process = $this->cloudConvert->website('http://www.google.co.uk');
		$this->assertSame($this->cloudConvert->getInputFormat(), 'website');
		$this->assertSame($this->cloudConvert->getInput()->getMethod(),'url');

	}






}