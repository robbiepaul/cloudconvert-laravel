<?php

namespace RobbieP\CloudConvertLaravel;

use GuzzleHttp\Client;

class HttpClientAdapter implements HttpClientInterface {

    private $client;
    private $request;
    private $response;

    /**
     *
     */
    function __construct()
    {
        $this->client = new Client();
    }

    /**
     * @param $url
     * @param array $params
     * @param array|null $query
     * @return bool|mixed
     */
    public function post($url, $params = [], $query = null)
    {
        $body = is_array($query) && is_array($params)  ? array_merge($params, $query) : $params;
        
        $opts = [ 'json' =>  $body  ];
        //if(isset($opts['json']) && isset($opts['json']['file'])) dd($opts);
        try {
            $this->response = $this->client->post($url,  $opts);
        } catch (\Exception $e) {
            dd(['error msg' => $e->getMessage(), 'params' => $body]);
        }
        return $this->response->json(['object' => true]);
    }

    /**
     * @param $url
     * @param array $params
     * @param array $query
     * @return bool|mixed
     */
    public function get($url, $params = [], $query = null)
    {
        $opts = [ 'body' => $params ];
        if(!empty($query)) {
            $opts['query'] = $query;
        }
        $this->response = $this->client->get($url, $opts);
        return $this->response->json(['object' => true]);
    }

    /**
     * @param $url
     * @return mixed
     */
    public function delete($url)
    {
        $this->response = $this->client->delete($url);
        return $this->response->json(['object' => true]);
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
        $this->request = $this->client->createRequest($method, $url);
        $this->response = $this->client->send($this->request);
        return $this;
    }


    /**
     * @return mixed
     */
    public function contents()
    {
        return $this->response->getBody()->getContents();
    }




} 