<?php namespace RobbieP\CloudConvertLaravel\HttpClientAdapter;

interface HttpClientInterface {
    /**
     * GET REQUEST
     * @param string $url
     * @param array $params
     * @return mixed
     */
    public function get ( $url, $params = [] );

    /**
     * POST REQUEST
     * @param string $url
     * @param array $params
     * @return mixed
     */
    public function post ( $url, $params = [] );

    /**
     * DELETE REQUEST
     * @param string $url
     * @return mixed
     */
    public function delete ( $url );

    /**
     * HTTP REQUEST
     * @param $url
     * @param string $method get, post, delete, put
     * @param array $params
     * @return Guzzle5Adapter|Guzzle6Adapter
     */
    public function request ( $url, $method, $params = [] );

    /**
     * GET CONTENTS OF REQUEST
     * Must be called after request()
     * @return mixed
     */
    public function contents ();

} 