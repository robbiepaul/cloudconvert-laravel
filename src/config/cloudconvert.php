<?php

return array(

	/*
	|--------------------------------------------------------------------------
	| CloudConvert Config
	|--------------------------------------------------------------------------
	|
	| CloudConvert is a file conversion service. Convert anything to anything
	| more than 100 different audio, video, document, ebook, archive, image,
	| spreadsheet and presentation formats supported.
	|
	*/

	/**
	 * API Key
	 * You can get it from: https://cloudconvert.org/user/profile
	 */

	'api_key' => '',
	's3' => [
		'accesskeyid' => '',
		'secretaccesskey' => '',
		'bucket' => '',
		'acl' => '',
		'region' => ''
	],
	'ftp' => [
		'host' => '',
		'user' => '',
		'password' => '',
		'port' => 21,
		'dir' => '',
	]

);