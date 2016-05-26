<?php namespace RobbieP\CloudConvertLaravel\HttpClientAdapter;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Stream;

class Guzzle6Adapter implements HttpClientInterface {

    protected $multipartContent = [];
    private $client;
    private $response;
    protected $outputArray = [];

    /**
     * Uses Guzzle 6.*
     */
    public function __construct()
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
     * Return JSON encoded response
     *
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
        foreach($body as $name => $contents) {
            $this->getMultipartContent($name, $contents);
        }
        $opts = [ 'multipart' => $this->multipartContent ];
        try {
            $this->response = $this->client->post($url,  $opts);
        } catch (\Exception $e) {
            throw $e;
        }

        return $this->returnJsonResponse();
    }

    /**
     * @param $name
     * @param $contents
     * @return array
     */
    public function getMultipartContent($name, $contents)
    {
        if(! is_array($contents)) {
            $this->addToMultiPart(['name' => $name, 'contents' => $this->castContents($contents)]);
        } else {
            $multipartContent = $this->flattenArray($name, $contents);
            foreach($multipartContent as $contentArray) {
                $this->addToMultiPart($contentArray);
            }
        }
        return $this->multipartContent;
    }

    /**
     * @param $name
     * @param $contents
     * @return array
     */
    private function flattenArray($name, $contents)
    {
        foreach ($contents as $key => $value)
        {
            $new_name = $name.'[' . $key . ']';
            if(is_array($value)) $this->flattenArray($new_name, $value);
            else $this->outputArray[] = ['name' => $new_name, 'contents' => $this->castContents($value)];
        }
        return $this->outputArray;
    }

    /**
     * Add single contentArray to final multipartContent array
     * @param $contentArray
     */
    private function addToMultiPart(array $contentArray)
    {
        if(! in_array($contentArray, $this->multipartContent)) {
            $this->multipartContent[] = $contentArray;
        }
    }

    /**
     * @param $contents
     * @return string
     */
    protected function castContents($contents)
    {
        if (is_numeric($contents)) return (string) $contents;
        if (is_bool($contents)) return $contents ? 'true' : 'false';
        if (is_string($contents)) return utf8_encode($contents);
        return $contents;
    }

}
