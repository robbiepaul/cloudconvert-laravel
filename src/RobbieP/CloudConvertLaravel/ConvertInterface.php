<?php namespace RobbieP\CloudConvertLaravel;

interface ConvertInterface {

	/**
	 * @return string
	 */
	public function getPath ();

	public function getFilename ();

	public function getMethod ();

	public function getFormat ();

	public function shouldWait ();

	public function save ();

	public function toArray ();

	public function getConversionSettings ();

}