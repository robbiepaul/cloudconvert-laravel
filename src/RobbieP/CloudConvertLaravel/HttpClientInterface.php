<?php
/**
 * Created by PhpStorm.
 * User: robbie
 * Date: 16/11/14
 * Time: 19:53
 */

namespace RobbieP\CloudConvertLaravel;


interface HttpClientInterface {
    /**
     * GET REQUEST
     * @param $url
     * @param array $params
     * @return mixed
     */
    public function get ( $url, $params = [] );

    /**
     * POST REQUEST
     * @param $url
     * @param array $params
     * @return mixed
     */
    public function post ( $url, $params = [] );

    /**
     * DELETE REQUEST
     * @param $url
     * @return mixed
     */
    public function delete ( $url );

    /**
     * HTTP REQUEST
     * @param $url
     * @param $method get, post, delete, put
     * @param array $params
     * @return mixed
     */
    public function request ( $url, $method, $params = [] );

    /**
     * GET CONTENTS OF REQUEST
     * Must be called after request()
     * @return mixed
     */
    public function contents ();

} 