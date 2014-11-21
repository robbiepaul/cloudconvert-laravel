<?php

namespace RobbieP\CloudConvertLaravel;


class Config {

    private $config;

    function __construct($config)
    {
        $this->config = $config;
    }

    public function get($key)
    {
        if(! is_array($this->config) ) return null;
        return array_get($this->config, $key, null);
    }

} 