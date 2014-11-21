<?php


namespace RobbieP\CloudConvertLaravel;


interface StorageInterface {

	/**
	 * @param $options
	 * @return mixed
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

	/**
	 * @return mixed
     */
	public function getMethod ();


}