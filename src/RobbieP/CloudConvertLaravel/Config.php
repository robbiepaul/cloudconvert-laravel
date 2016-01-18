<?php namespace RobbieP\CloudConvertLaravel;


class Config {

    private $config;

    /**
     * @param $config
     */
    function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function get($key)
    {
        if(! is_array($this->config) ) return null;
        return array_get($this->config, $key, null);
    }

    public function toArray()
    {
        if(! is_array($this->config) ) return [];
        return $this->config;
    }

} 