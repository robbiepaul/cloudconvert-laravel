<?php namespace RobbieP\CloudConvertLaravel;


interface StorageInterface {

	/**
	 * @param $options
	 * @return void
     */
	public function setOptions ( $options );

	/**
	 * @return mixed
     */
	public function options ();

	/**
	 * @return mixed
     */
	public function validateCredentials ();

	/**
	 * @return mixed
     */
	public function getPath ();


	public function setPath ($path);

	/**
	 * @return string
     */
	public function getMethod ();


	public function setFormat ($format);




}