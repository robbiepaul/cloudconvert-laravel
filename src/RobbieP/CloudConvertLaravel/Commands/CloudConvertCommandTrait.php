<?php
/**
 * Created by PhpStorm.
 * User: robbie
 * Date: 18/11/14
 * Time: 20:42
 */

namespace RobbieP\CloudConvertLaravel\Commands;


trait CloudConvertCommandTrait {

    private $cloudConvert;

    public function checkAPI()
    {
        if(!$this->cloudConvert->hasApiKey()) {
            $api_key = $this->ask('What is your API key for CloudConvert?');
            $this->cloudConvert->setApiKey($api_key);
        }
    }

    private function parseOptions($options = [])
    {
        $o = [];
        foreach($options as $key => $val ){
            list($k,$v) = explode(':',$val);
            $o[$k] = $v;
        }
        return $o;
    }

    /**
     * @param $outputfile
     * @param $path
     * @return string
     */
    private function getOutputPath($outputfile, $path)
    {
        if(strstr($outputfile, DIRECTORY_SEPARATOR)) {
            return $outputfile;
        }
        return (empty($path)) ? $outputfile : $path.DIRECTORY_SEPARATOR.$outputfile;
    }


} 