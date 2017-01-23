<?php namespace RobbieP\CloudConvertLaravel;

use Exception;
use InvalidArgumentException;

class Process {

    use HttpClient;

    const STEP_FINISHED = 'finished';
    const STEP_ERROR = 'error';
    const STEP_CONVERT = 'convert';
    const TIMEOUT = 120;

    public $id;
    public $host;
    public $step;
    public $starttime;
    public $endtime;
    public $message;
    public $url;
    public $info;
    protected $response;
    private $mode;
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
        $this->response = $this->http->{$method}($this->url . $endpoint, $params, $this->getQueryOptions());
        return $this->response ;
    }

    /**
     * @param Convert|array $input
     * @param Convert $output
     * @return mixed
     */
    public function convert($input, Convert $output)
    {
        $this->validateInputAndOutput($input, $output);
        
        $options = $this->getInputOptions($input, $output);

        $this->mergeOptions($options);

        $this->response = $this->process($this->options);

        return $this->response;
    }

    public function mode($mode, $input = null,  $output = null)
    {
        $this->mode = $mode;
        $options = $this->getInputOptions($input, $output);
        $this->mergeOptions($options);
        $this->response = $this->process($this->options);
        return $this->response;
    }

    public function getResponse()
    {
        return $this->response;
    }
    public function getInfo()
    {
        return $this->info;
    }

    protected function getInputOptions($input, $output)
    {
        if(is_array($input) && isset($input[0]) && $input[0] instanceof ConvertRemoteFile) {
            $primaryInput = $input[0];
            if(!is_null($output)) $primaryInput->prepareOutput($output);
            $options = $primaryInput->toArray();
            if(isset($options['file'])) {
                $obj = $this->createMultipleFileObject($primaryInput, 0);
                $options['files'] = [$obj->toJson()];
                unset($options['file']);
            } 
           
            foreach ($input as $key => $inputObj) {
                if($key > 0) {
                    if(!is_null($output)) $inputObj->prepareOutput($output);
                    $obj = $this->createMultipleFileObject($inputObj, $key);
                    $options['files'][] = $obj->toJson();
                }
            }
            $options['filename'] = $output ? $output->getFilename() : $primaryInput->getFilename();
            $options['wait'] = true;
            $options['save'] = true;
            return $options;
        }
        if(!is_null($output)) $input->prepareOutput($output);
        $options = $input->toArray();

        return $options;
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
        if(! $this->shouldWait() ) return false;
        $time = 0;
        while ($time++ <= $timeout) {
            sleep(1);
            $this->status();
            $this->checkErrors();
            if( $this->isFinished() ) return true;
        }
        throw new \Exception('Timeout');
    }

    public function shouldWait()
    {
        return $this->options['wait'] === true;
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
        return ( $this->step === self::STEP_FINISHED  && ((isset($this->output) && isset($this->output->url)) || $this->mode !== 'convert'));
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
     * @return $this
     * @internal param string $action
     */
    public function status()
    {
        $response = $this->http->get($this->url);

        $this->fill($response);
        return $this;
    }

    /**
     * @return Process
     */
    public function cancel()
    {
        $response = $this->http->delete($this->url);
        $this->fill($response);
        return $this;
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
     * @throws InvalidArgumentException
     */
    private function validateInputAndOutput($input, $output)
    {
        if(!$input instanceof Convert && !is_array($input)) {
            throw new \InvalidArgumentException('Input is not convertable');
        }
        if(!$output instanceof Convert) {
            throw new \InvalidArgumentException('Output is not convertable');
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

    /**
     * @param $inputObj
     * @param $key
     * @return ConvertMultiple
     */
    protected function createMultipleFileObject($inputObj, $key)
    {
        $obj = new ConvertMultiple;
        $obj->file = $inputObj->toArray()['file'];
        $obj->filename = isset($inputObj->toArray()['filename']) ? $inputObj->toArray()['filename'] : $key + 1;
        return $obj;
    }

    /**
     * @param $options
     */
    private function mergeOptions($options = [])
    {
        if(isset($this->options['callback']) || isset($options['callback'])) {
            $this->options['wait'] = false;
        }
        if(isset($this->mode) && !is_null($this->mode)) {
            $this->options['mode'] = $this->mode;
        }
        $this->options = array_merge($options, $this->options);
    }


}