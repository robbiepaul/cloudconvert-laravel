<?php

class ConvertTest extends BaseTest {

	public function testConvertLocalFileWorks()
	{
		$convertLocalFile = new \RobbieP\CloudConvertLaravel\ConvertLocalFile('/example/path/to/a-image.four_4.jpeg');
		$this->assertSame('jpeg', $convertLocalFile->getFormat());
		$this->assertSame('a-image.four_4.jpeg', $convertLocalFile->getFilename());
		$this->assertSame('/example/path/to', $convertLocalFile->getPath());
		$this->assertSame('/example/path/to'.'/'.'a-image.four_4.jpeg', $convertLocalFile->getFilepath());
	}

	public function testConvertUploadedFileWorks()
	{
		$uploaded_file = Mockery::mock(
			'\Symfony\Component\HttpFoundation\File\UploadedFile',
			[
				'getClientOriginalName'      => 'image-1.jpg',
				'getFilename'                => '/tmp/image-1.jpg',
				'getClientOriginalExtension' => 'jpg',
			]
		);
		$convertLocalFile = new \RobbieP\CloudConvertLaravel\ConvertLocalFile($uploaded_file);
		$this->assertSame('jpg', $convertLocalFile->getFormat());
		$this->assertSame('image-1.jpg', $convertLocalFile->getFilename());
		$this->assertSame('/tmp', $convertLocalFile->getPath());
		$this->assertSame('/tmp'.'/'.'image-1.jpg', $convertLocalFile->getFilepath());

	}

	public function testConvertLocalFileSaves()
	{
		$convertLocalFile = new \RobbieP\CloudConvertLaravel\ConvertLocalFile('/example/path/to/a-image.four_4.jpeg');

		$fileSystem = Mockery::mock(
			'\Illuminate\Filesystem\Filesystem',
			[
				'isWritable'      		     => true,
				'put'               	 	 => true
			]
		);
		$convertLocalFile->setFilesystem($fileSystem);
		$convertLocalFile->setData('BLOB');
		$this->assertTrue($convertLocalFile->save());
	}

	/**
	 * @expectedException Exception
	 */
	public function testExceptionIfFilenotwritable()
	{
		$convertLocalFile = new \RobbieP\CloudConvertLaravel\ConvertLocalFile('/example/path/to/a-image.four_4.jpeg');

		$fileSystem = Mockery::mock(
			'\Illuminate\Filesystem\Filesystem',
			[
				'isWritable'      		     => false,
				'put'               	 	 => true
			]
		);
		$convertLocalFile->setFilesystem($fileSystem);
		$convertLocalFile->setData('BLOB');
		$this->assertTrue($convertLocalFile->save());
	}

	/**
	 * @expectedException Exception
	 */
	public function testExceptionIfNotDataHasBeenSet()
	{
		$convertLocalFile = new \RobbieP\CloudConvertLaravel\ConvertLocalFile('/example/path/to/a-image.four_4.jpeg');

		$fileSystem = Mockery::mock(
			'\Illuminate\Filesystem\Filesystem',
			[
				'isWritable'      		     => true,
				'put'               	 	 => true
			]
		);
		$convertLocalFile->setFilesystem($fileSystem);
		$this->assertTrue($convertLocalFile->save());
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage Invalid format
	 */
	public function testConvertLocalFileThrowsExceptionIfOnlyPathGiven()
	{
		$convertLocalFile = new \RobbieP\CloudConvertLaravel\ConvertLocalFile('/example/path/');
		$convertLocalFile->getFormat();
	}

	public function testConvertLocalFileSetFormatWorksOnPath()
	{
		$convertLocalFile = new \RobbieP\CloudConvertLaravel\ConvertLocalFile('/example/path/');
		$convertLocalFile->setFormat('png');
		$this->assertSame('/example/path', $convertLocalFile->getPath());
		$this->assertSame('png', $convertLocalFile->getFormat());
	}

	public function testConvertLocalFileSetFilenameReturnsCorrectFormat()
	{
		$convertLocalFile = new \RobbieP\CloudConvertLaravel\ConvertLocalFile('/example/path/');
		$convertLocalFile->setFilename('test.mov');
		$this->assertSame('mov', $convertLocalFile->getFormat());
	}

	public function testCorrectFilenameIfExtensionGiven()
	{
		$convertLocalFile = new \RobbieP\CloudConvertLaravel\ConvertLocalFile('/example/path/');
		$convertLocalFile->setFilename('a-nice-pdf-file.pdf', 'jpg');
		$this->assertSame('a-nice-pdf-file.jpg', $convertLocalFile->getFilename());
		$this->assertSame('jpg', $convertLocalFile->getFormat());
	}

	public function testWebsiteInput()
	{
		$convertWebsite = new \RobbieP\CloudConvertLaravel\ConvertWebsite('google.co.uk');
		$this->assertSame('website', $convertWebsite->getFormat());
		$this->assertContains('googlecouk', $convertWebsite->getFilename());
		$this->assertSame('url', $convertWebsite->getMethod());
	}

	public function testRemoteFileInput()
	{
		$convertWebsite = new \RobbieP\CloudConvertLaravel\ConvertRemoteFile('http://mirrors.creativecommons.org/presskit/icons/cc.large.png');
		$this->assertSame('png', $convertWebsite->getFormat());
		$this->assertSame('cc.large.png', $convertWebsite->getFilename());
		$this->assertSame('download', $convertWebsite->getMethod());
	}

	public function testOnlyOutputFormatGiven()
	{
		$inputConvertLocalFile = new \RobbieP\CloudConvertLaravel\ConvertLocalFile('/a/local/path/test.image.jpg');
		$outputConvertLocalFile = new \RobbieP\CloudConvertLaravel\ConvertLocalFile('png');

		$this->assertSame('png', $outputConvertLocalFile->getFormat());
		$this->assertSame('jpg', $inputConvertLocalFile->getFormat());

		$this->assertSame('test.image.jpg', $inputConvertLocalFile->getFilename());
		$this->assertEquals('', $outputConvertLocalFile->getFilename());

		$this->assertSame('.', $outputConvertLocalFile->getPath());
		$this->assertSame('/a/local/path', $inputConvertLocalFile->getPath());

		$outputConvertLocalFile->filenameCheck($inputConvertLocalFile);

		$this->assertSame('test.image.png', $outputConvertLocalFile->getFilename());
		$this->assertSame($inputConvertLocalFile->getPath(), $outputConvertLocalFile->getPath());

	}


	protected function tearDown()
	{
		$convertLocalFile = null;
	}






}