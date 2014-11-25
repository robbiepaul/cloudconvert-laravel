<?php

namespace RobbieP\CloudConvertLaravel;


use OAuth\Common\Exception\Exception;
use Symfony\Component\Process\Exception\InvalidArgumentException;

class Process {

    use HttpClient;

    const STEP_FINISHED = 'finished';
    const STEP_ERROR = 'error';
    const STEP_CONVERT = 'convert';
    const TIMEOUT = 120;

    private $id;
    private $host;
    private $step;
    private $starttime;
    private $endtime;
    private $message;
    private $url;
    private $output;
    private $output_format;
    private $input_format;
    private $options;

    /**
     * @param mixed $data
     * @param null $input
     * @param null $output
     */
    function __construct($data, $input = null, $output = null)
    {
        $this->setClient();
        $this->input_format = $input;
        $this->output_format = $output;
        if(is_object($data) && !empty($data)) {
            $this->fill($data);
        }
        if(is_string($data) && strstr($data, '//')) {
            $this->url = $this->fixURL($data);
            $this->status();
        }
    }


    /**
     * @param array $params
     * @param string $endpoint
     * @param string $method
     * @return mixed
     * @throws \Exception
     */
    protected function process($params = [], $endpoint = '', $method = 'post')
    {
        $this->checkURLisOK();
        $response = $this->http->{$method}($this->url . $endpoint, $params, $this->getQueryOptions());
        return $response;
    }

    /**
     * @param Convert $input
     * @param Convert $output
     * @return mixed
     */
    public function convert(Convert $input, Convert $output)
    {
        $this->validateInputAndOutput($input, $output);
        $input->prepareOutput($output);

        $response = $this->process($input->toArray());

        return $response;
    }

    /**
     * @return string
     */
    public function downloadURL()
    {
        return !empty($this->output) ? $this->output->url :  '';
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function download()
    {
        $this->checkFileIsReadyToDownload();
        $this->output->url = $this->fixURL($this->output->url);
        $data = $this->fetchFiles();
        return $data;
    }

    /**
     * @return array
     */
    private function fetchFiles()
    {

        if(empty($this->output->files))
        {
            return $this->http->request($this->output->url, 'get')->contents();
        }
        $data = [];
        foreach($this->output->files as $k => $file) {
            $data[$k]['filename'] = $file;
            $data[$k]['data'] =  $this->http->request($this->output->url.'/'.$file, 'get')->contents();
        }
        return $data;
    }

    /**
     * @throws \Exception
     */
    private function checkFileIsReadyToDownload()
    {
        if (!isset($this->output->url)) {
            throw new \Exception('Not ready to download');
        }
    }

    /**
     * @param $url
     * @return string
     */
    private function fixURL($url)
    {
        if (strpos($url, 'http') === false)
            $url = "https:" .$url;
        return $url;
    }

    /**
     * Blocks until the conversion is finished
     * @param int $timeout
     * @return bool
     * @throws \Exception
     */
    public function waitForConversion($timeout = self::TIMEOUT) {
        $time = 0;
        while ($time++ <= $timeout) {
            sleep(1);
            $this->status();
            $this->checkErrors();
            if( $this->isFinished() ) return true;
        }
        throw new \Exception('Timeout');
    }

    /**
     * @return mixed
     */
    public function getStep()
    {
        return $this->step;
    }

    /**
     * @return bool
     */
    public function isFinished()
    {
        return ( $this->step === self::STEP_FINISHED  && isset($this->output) && isset($this->output->url) );
    }

    /**
     * @throws \Exception
     */
    public function checkErrors()
    {
        if( $this->step === self::STEP_ERROR ) {
            throw new \Exception( $this->message );
        }
    }

    /**
     * @param string $action
     * @return $this
     */
    public function status($action = '')
    {
        $response = $this->process([], $action, 'get');
        $this->fill($response);
        return $this;
    }

    /**
     * @return Process
     */
    public function cancel()
    {
        return $this->status('cancel');
    }

    /**
     * @param array|object $data
     */
    private function fill($data = [])
    {
        foreach($data as $key => $value) {
            $this->{$key} = $value;
        }
        $this->url = $this->fixURL($this->url);
    }

    /**
     * @return mixed
     */
    public function delete()
    {
        return $this->http->delete($this->url);
    }

    /**
     * @throws \Exception
     */
    private function checkURLisOK()
    {
        if (empty($this->url))
            throw new \Exception("No process URL found! (Conversion not started)");
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return mixed
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @param Convert $input
     * @param Convert $output
     */
    private function validateInputAndOutput($input, $output)
    {
        if(!$input instanceof Convert) {
            throw new InvalidArgumentException('Input is not convertable');
        }
        if(!$output instanceof Convert) {
            throw new InvalidArgumentException('Output is not convertable');
        }
    }

    /**
     * @return mixed
     */
    public function getQueryOptions()
    {
        return $this->options;
    }

    /**
     * @param mixed $options
     */
    public function setQueryOptions($options)
    {
        $this->options = $options;
    }

    public function compareOutput(Convert $output)
    {
        if($this->isFinished() &&   $this->output->ext == $output->getFormat()) {
            return true;
        }
        throw new \Exception('Output format provided does not match the format converted');
    }


}