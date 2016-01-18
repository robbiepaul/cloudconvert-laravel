<?php namespace RobbieP\CloudConvertLaravel;

use GuzzleHttp\Client;
use RobbieP\CloudConvertLaravel\HttpClientAdapter\Guzzle5Adapter;
use RobbieP\CloudConvertLaravel\HttpClientAdapter\Guzzle6Adapter;

trait HttpClient
{

    public $http;

	/**
     * @param HttpClientAdapter\HttpClientInterface $adapter
     */
    public function setClient($adapter = null)
    {
        if(! is_null($adapter)) {
            $this->http = $adapter;
        } else {
            $this->setGuzzleAdapter();
        }
    }

    public function setGuzzleAdapter()
    {
        switch (true) {
            case ( version_compare(Client::VERSION, '6.0.0', '<') ):
                $this->http = new Guzzle5Adapter;
                break;
            case ( version_compare(Client::VERSION, '6.0.0', '>=') ):
                $this->http = new Guzzle6Adapter;
                break;
        }
    }

}