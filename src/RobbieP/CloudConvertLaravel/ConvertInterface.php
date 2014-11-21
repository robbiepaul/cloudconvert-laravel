<?php 

namespace RobbieP\CloudConvertLaravel;

interface ConvertInterface {

	public function getPath ();

	public function getFilename ();

	public function getMethod ();

	public function getFormat ();

	public function save ();

	public function getConversionSettings ($output);
}