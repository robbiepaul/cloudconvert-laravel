<?php
/**
 * Created by PhpStorm.
 * User: robbie
 * Date: 18/11/14
 * Time: 20:42
 */

namespace RobbieP\CloudConvertLaravel\Commands;


trait CloudConvertCommandTrait {

    public function checkAPI()
    {
        if(!$this->cloudConvert->hasApiKey()) {
            $api_key = $this->ask('What is your API key for CloudConvert?');
            $this->cloudConvert->setApiKey($api_key);
        }
    }

} 