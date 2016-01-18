<?php namespace RobbieP\CloudConvertLaravel\HttpClientAdapter;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class Guzzle6Adapter implements HttpClientInterface {

    private $client;
    private $response;

    /**
     * Uses Guzzle 6.*
     */
    function __construct()
    {
        $this->client = new Client();
    }

    /**
     * @param string $url
     * @param array $params
     * @param array|null $query
     * @return bool|mixed
     * @throws \Exception
     */
    public function post($url, $params = [], $query = null)
    {
        $body = is_array($query) && is_array($params)  ? array_merge($params, $query) : $params;
        
        $opts = [ 'json' =>  $body  ];

        if(isset($body['file']) && is_resource($body['file']))  {
            return $this->multipart($url, $body);
        }

        try {
            $this->response = $this->client->post($url,  $opts);
        } catch (\Exception $e) {
            throw $e;
        }

        return $this->returnJsonResponse();
    }

    /**
     * @param string $url
     * @param array $params
     * @param array $query
     * @return bool|mixed
     * @throws \Exception
     */
    public function get($url, $params = [], $query = [])
    {
        $query = array_merge($params, $query);
        $opts = [];
        if(!empty($params) && !empty($query)) {
            $opts['query'] = $query;
        }

        try {
            $this->response = $this->client->get($url, $opts);
        } catch (ClientException  $e) {
            throw $e;
        }

        return $this->returnJsonResponse();

    }

    /**
     * @param $url
     * @return mixed
     */
    public function delete($url)
    {
        $this->response = $this->client->delete($url);
        return $this->returnJsonResponse();
    }

    /**
     * @param $url
     * @param string $method
     * @param array $params
     * @param array $query
     * @return $this
     */
    public function request($url, $method = 'GET', $params = [], $query = null)
    {
        $this->response = $this->client->{$method}($url, $params);

        return $this;
    }

    /**
     * @return mixed
     */
    public function contents()
    {
        return $this->response->getBody()->getContents();
    }

    /**
     * @return mixed
     */
    protected function returnJsonResponse()
    {
        return json_decode($this->response->getBody()->__toString());
    }

    /**
     * @param $url
     * @param $body
     * @return mixed
     * @throws \Exception
     */
    public function multipart($url, $body)
    {
        $content = [];
        foreach($body as $name => $contents) {
            $content[] = ['name' => $name, 'contents' => $contents];
        }
        $opts = [ 'multipart' => $content ];
        try {
            $this->response = $this->client->post($url,  $opts);
        } catch (\Exception $e) {
            throw $e;
        }

        return $this->returnJsonResponse();
    }


}