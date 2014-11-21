<?php

namespace RobbieP\CloudConvertLaravel;


abstract class Storage {

	/**
	 * @return mixed
     */
	public function __toString()
	{
		return $this->getMethod();
	}

	/**
	 * @return array
     */
	public function options()
	{
		$this->validateCredentials();
		return [static::INPUT_METHOD =>  get_object_vars($this)];
	}

	/**
	 * @param $options
     */
	public function setOptions($options)
	{
		if(!empty($options)) {
			foreach($options as $k => $option) {
				$this->{$k} = $option;
			}
		}
	}

}