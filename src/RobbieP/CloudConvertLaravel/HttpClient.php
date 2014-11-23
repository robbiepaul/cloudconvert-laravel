<?php

namespace RobbieP\CloudConvertLaravel;

trait HttpClient
{

    public $http;

	/**
     * @param HttpClientInterface $adapter
     */
    public function setClient($adapter = null)
    {
        $this->http = (!is_null($adapter)) ? $adapter : new HttpClientAdapter;
    }

}