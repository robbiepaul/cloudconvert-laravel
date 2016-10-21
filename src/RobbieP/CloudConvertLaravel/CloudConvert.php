<?php namespace RobbieP\CloudConvertLaravel;

/*
|--------------------------------------------------------------------------
| CloudConvert Laravel API
|--------------------------------------------------------------------------
|
| CloudConvert is a file conversion service. Convert anything to anything
| more than 100 different audio, video, document, ebook, archive, image,
| spreadsheet and presentation formats supported.
|
*/

use Exception;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;


class CloudConvert
{
    use HttpClient;

    const INPUT_DOWNLOAD    = 'download';
    const INPUT_UPLOAD      = 'upload';
    const INPUT_WEBSITE     = 'website';
    const INPUT_URL         = 'url';

    const MODE_CONVERT      = 'convert';
    const MODE_INFO         = 'info';
    const MODE_COMBINE      = 'combine';
    const MODE_COMPRESS     = 'compress';
    const MODE_UNLOCK       = 'compress';
    const MODE_PROTECT      = 'protect';
    const MODE_ARCHIVE      = 'archive';
    const MODE_EXTRACT      = 'extract';

    protected $fileSystem;

    /**
     * @var null
     */
    private $api_key;
    private $api_url = 'https://api.cloudconvert.com';
    private $process;
    private $input;
    private $inputs;
    private $output;
    private $resource;
    private $input_method;
    private $input_format;
    private $output_format;
    private $processes;
    private $options = [];
    private $preset = null;
    private $converteroptions;
    private $mode;

    /**
     * Available storage options, must be configured.
     * More soon: 'dropbox','googledrive'
     * @var array
     */
    private $storage_options = ['s3', 'ftp'];

    /**
     * Configuration options
     * @var Config
     */
    private $config;


    /**
     * @param $config
     * @internal param null $api_key
     */
    function __construct($config = null)
    {
        $this->setConfig($config);
        $this->setClient();
        $this->setFilesystem();
    }

    /**
     * @param array $config
     * @return CloudConvert
     */
    public function newInstance($config = [])
    {
        $new_config = array_merge($this->config->toArray(), $config);
        return new static($new_config);
    }

    /**
     * @param $resource
     * @param null $input
     * @param null $output
     * @return $this
     * @throws Exception
     */
    public function make($resource, $input = null, $output = null)
    {
        $this->assignInputAndOutputVars($input, $output);
        $this->init($resource);
        $this->startProcess();
        return $this;
    }

    /**
     * @param Filesystem $fileSystem
     */
    public function setFilesystem($fileSystem = null)
    {
        $this->fileSystem = (!is_null($fileSystem)) ? $fileSystem : new Filesystem();
    }

    /**
     * @param $file
     * @return $this
     * @throws Exception
     */
    public function file($file)
    {
        $this->init($file);
        return $this;
    }

    /**
     * @param $array
     * @return $this
     * @throws Exception
     */
    public function merge(array $array)
    {
        foreach ($array as $file) {
            $this->addMultipleInput($file);
        }
        return $this;
    }

    /**
     * @param $type
     * @param bool $chain
     * @return $this|CloudConvert
     */
    public function to($type, $chain = false)
    {
        if(is_array($type)) {
            foreach($type as $ext) {
                $this->to($ext, true);
            }
            $this->reset();
            return $this;
        }
        $this->convert($type);
        $this->save();
        if(!$chain) $this->reset();
        return $this;
    }

    /**
     * @param null $type
     * @return $this|CloudConvert
     * @throws Exception
     */
    public function convert($type = null)
    {
        $this->validateConversion();
        $this->initOutput($type);
        $this->startProcess();

        if(isset($this->options['callback'])) {
            $this->convertFile();
        }

        return $this;
    }

    /**
     * @param null $mode
     * @return $this|CloudConvert
     * @throws Exception
     * @internal param null $type
     */
    public function mode($mode = null, $wait = true)
    {
        $this->mode = $mode;
        $this->startProcess();
        $this->wait($wait);
        $this->getProcess()->mode($mode, $this->getInput(), $this->getOutput());

        if ($this->getProcess()->waitForConversion()) {
            return $this;
        }
        return $this;
    }

    public function response()
    {
        return !empty($this->process) ? $this->getProcess()->getResponse() : null;
    }

    public function getInfo()
    {
        return !empty($this->process) ? $this->getProcess()->getInfo() : null;
    }

    public function info()
    {
        return $this->mode('info');
    }

    /**
     * @param $array
     * @return $this
     * @throws Exception
     */
    public function combine(array $array)
    {
        foreach ($array as $file) {
            $this->addMultipleInput($file);
        }
        $this->mode('combine');

        return $this;
    }

    /**
     * @throws Exception
     * @internal param null $output
     * @internal param null $path
     * @return $this|CloudConvert
     */
    public function save($output = null)
    {
        $this->checkOutput($output);
        // if !output and $output not null - create new output object
        // check output given is same as output to be downloaded

        if ($this->getProcess()->isFinished()) {
            return $this->downloadConvertedFile();
        }

        return $this->convertFileAndSaveTo();
    }


    /**
     * @return mixed
     */
    public function convertFile()
    {
        $this->prepareProcessForConversion();
        $this->getProcess()->convert($this->getInput(), $this->getOutput());
    }

    /**
     * @param $type
     * @return $this
     */
    public function input($type)
    {
        $this->input_format = $this->filterType($type);
        return $this;
    }

    /**
     * @param $type
     * @return $this
     */
    public function output($type)
    {
        $this->output_format = $this->filterType($type);
        return $this;
    }

    /**
     * @param null $group
     * @return Collection
     * @throws Exception
     */
    public function conversionTypes($group = null)
    {
        $results = $this->http->get($this->api_url . "/conversiontypes?inputformat={$this->getInputFormat()}&outputformat={$this->getOutputFormat()}");
        $types = new Collection($results);
        if ($types->isEmpty()) {
            throw new Exception('No conversion types found');
        }
        if (!is_null($group)) {
            $types = $this->filterTypesByGroup($types, $group);
        }
        return $types;
    }

    /**
     * @return Collection
     * @throws Exception
     */
    public function processes()
    {
        $this->checkAPIkey();
        $results = $this->start("/processes?apikey={$this->getApiKey()}");
        return $this->processes = new Collection($results);
    }


    /**
     * @param $id
     * @return mixed
     */
    public function deleteProcess($id)
    {
        $process = $this->getProcessById($id);
        if ($process->url && $process->url = $this->fixURL($process->url)) {
            return $this->http->delete($process->url);
        }
        return false;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getProcess($id = null)
    {
        if (!is_null($id)) {
            $process = $this->getProcessById($id);
            if ($process->url && $process->url = $this->fixURL($process->url)) {
                return $this->http->get($process->url);
            }
        }
        return $this->process;
    }

    /**
     * @throws Exception
     * @internal param $input
     * @internal param $output
     * @return CloudConvert
     */
    public function startProcess()
    {
        $this->checkAPIkey();
        $this->validateFormats();
        $data = [
            'inputformat' => $this->getInputFormat(),
            'apikey' => $this->getApiKey()
        ];
        if($this->getOutputFormat()) $data['outputformat'] = $this->getOutputFormat();
        $response = $this->start('/process', $data);

        $this->setProcess(new Process($response, $this->getInputFormat(), $this->getOutputFormat()));

        return $this;
    }

    /**
     * @param Process $process
     * @return mixed
     */
    public function setProcess($process)
    {
        return $this->process = $process;
    }

    /**
     * Should be used in your callback URL script
     * @param $url
     * @return $this
     */
    public function useProcess($url)
    {
        $this->setProcess(new Process($url));

        return $this;
    }

    /**
     * @param string $endpoint
     * @param array $params
     * @return mixed
     */
    protected function start($endpoint = '/', $params = [])
    {
        return $this->http->post($this->api_url . $endpoint, $params);
    }

    /**
     * @param $resource
     * @return mixed
     * @throws Exception
     */
    public function init($resource = null)
    {
        if (empty($this->resource) && !empty($resource))
            $this->resource = $resource;

        switch (true) {
            case $this->isUrl():
                return $this->initFromUrl();
            case $this->isFilePath():
            case $this->isSymfonyUpload():
                return $this->initFromLocalFile();
            case $this->isRemoteStorage():
                return $this->initFromRemoteStorage();
            default:
                throw new Exception("File input is not readable");
        }
    }

    /**
     * @return ConvertRemoteFile
     */
    public function initFromUrl()
    {
        return $this->input = new ConvertRemoteFile($this->resource);
    }

    /**
     * @param $url
     * @return ConvertWebsite
     */
    public function initFromScreenshot($url)
    {
        return $this->input = new ConvertWebsite($url);
    }

    /**
     * @return ConvertLocalFile
     */
    public function initFromLocalFile()
    {
        return $this->input = new ConvertLocalFile($this->resource);
    }

    /**
     * @return ConvertStorage
     */
    private function initFromRemoteStorage()
    {
        return $this->input = new ConvertStorage($this->resource);
    }

    /**
     * @return bool
     */
    public function isSymfonyUpload()
    {
        return is_a($this->resource, 'Symfony\Component\HttpFoundation\File\UploadedFile');
    }

    /**
     * @return bool
     */
    public function isRemoteStorage()
    {
        return is_a($this->resource, 'RobbieP\CloudConvertLaravel\Storage');
    }

    /**
     * @return bool
     */
    public function isFilePath()
    {
        if (is_string($this->resource)) {
            return !empty($this->fileSystem) && $this->fileSystem->isFile($this->resource);
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isUrl()
    {
        return (bool)filter_var($this->resource, FILTER_VALIDATE_URL);
    }


    /**
     * Provide a callback URL for the API to call when its
     * finished processing.
     * In the callback URL script 'useProcess($_REQUEST['url'])'
     * @param $url
     * @return $this
     * @throws Exception
     */
    public function callback($url)
    {
        $this->validateURL($url);
        $this->setOption('callback', $url);
        return $this;
    }

    /**
     * Gets the process ready to take a screenshot of a website
     * @param $url
     * @return $this
     * @throws Exception
     */
    public function website($url)
    {
        $this->initFromScreenshot($url);

        return $this;
    }

    /**
     * @internal param string $path
     * @return $this|CloudConvert
     */
    private function downloadConvertedFile()
    {
        $data = $this->getProcess()->download();
        if($this->hasOutput()) {
            $this->getOutput()->setData($data);
            $this->getOutput()->save();
        }
        return $this;
    }

    /**
     * @return $this|CloudConvert
     */
    public function download()
    {
        return $this->downloadConvertedFile();
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function downloadURL()
    {
        if ( $this->getProcess()->isFinished() ) return $this->getProcess()->downloadURL();
        throw new Exception('Download URL not ready yet.');
    }

    /**
     * @param Config $config
     */
    public function setConfig($config = null)
    {
        if(is_array($config))
            $this->config = new Config($config);

        if(is_object($config))
            $this->config = $config;

        $this->api_key = is_string($config) ? $config : (is_object($this->config) ? $this->config->get('api_key') : null  );
    }

	/**
	 *
     */
	private function checkAPIkey()
    {
        if (!$this->hasApiKey()) {
            throw new \InvalidArgumentException('No API key provided.');
        }
    }

	/**
	 * @param Collection $types
	 * @param $group
	 * @return mixed
     */
	private function filterTypesByGroup($types, $group)
    {
        return $types->filter(function ($type) use ($group) {
            return $type->group === $group;
        });
    }

	/**
	 * @param $type
	 * @return mixed
     */
	private function filterType($type)
    {
		$a = explode('.', $type);
        return end($a);
    }

    /**
     * @throws Exception
     */
    private function validateFormats()
    {
        if(!is_null($this->mode)) return true;
        if (!$this->getInputFormat() || !$this->getOutputFormat()) {
            throw new Exception('Invalid formats provided');
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return ($this->process->getStep()) ? 'Process is: ' . $this->process->getStep() : 'Process has not started yet';
    }

    /**
     * @throws Exception
     */
    private function validateConversion()
    {
        if (!$this->input) {
            throw new Exception('Please set the file before converting');
        }
    }
    
    /**
     * Wait till convertion is finished
     * 
     * @return \RobbieP\CloudConvertLaravel\CloudConvert
     */
    public function wait($value = true)
    {
        $this->setOption('wait', $value);
        return $this;
    }

    /**
     * @param string $name
     * @param $value
     */
    private function setOption($name, $value)
    {
        $this->options[$name] = $value;
    }

    /**
     * @param $path
     * @return bool
     */
    public function isFolderWritable($path)
    {
        return is_string($path) && is_writable(dirname($path));
    }

    /**
     * @throws Exception
     * @internal param $path
     * @return $this|CloudConvert
     */
    public function convertFileAndSaveTo()
    {
        $this->convertFile();

        if ($this->getProcess()->waitForConversion()) {
            return $this->downloadConvertedFile();
        }
        else if(! $this->getProcess()->shouldWait() ) {
            return $this;
        }
        throw new Exception('Problem saving file');
    }


    /**
     * @param $url
     * @throws Exception
     */
    public function validateURL($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception('Not a valid URL. Must be fully qualified including protocol.');
        }
    }


    /**
     * @param $input
     * @param $output
     */
    public function assignInputAndOutputVars($input, $output)
    {
        if (is_null($output) && !is_null($input)) {
            $this->output_format = $input;
        } else if (!is_null($input) && !is_null($output)) {
            $this->input_format = $input;
            $this->output_format = $output;
        }
    }

    /**
     * @param $path
     * @param array $options
     * @return StorageS3
     */
    public function S3($path, $options = [])
    {
        $instance = new StorageS3($this->config);
		return $this->returnInstanceWithOptions($path, $options, $instance);
    }

    /**
     * @param $path
     * @param array $options
     * @return StorageFtp
     */
    public function FTP($path, $options = [])
    {
        $instance = new StorageFtp($this->config);
		return $this->returnInstanceWithOptions($path, $options, $instance);
    }

    /**
     * @param $provider
     * @throws Exception
     */
    public function validateProvider($provider)
    {
        if (!in_array($provider, $this->storage_options)) {
            throw new Exception ($provider . ' is not supported. Please choose from: ' . implode(', ', $this->storage_options));
        }
    }

    /**
     * @param string $provider
     * @param $options
     * @return Storage
     */
    public function getStorageInstance($provider, $options)
    {
        if ($provider instanceof Storage) {
            return $provider;
        }

        $class = "RobbieP\\CloudConvertLaravel\\Storage" . ucfirst($provider);
        $storage = new $class;
        $storage->setOptions($options);

        return $storage;

    }

    /**
     * @param string $action
     * @param $options
     * @return bool
     */
    public function queue($action, $options)
    {
        $data = get_object_vars($this);
        \Queue::push('RobbieP\\CloudConvertLaravel\\Queued', compact('action', 'options', 'data'));
        return true;
    }

    /**
     * @param array $data
     */
    public function fill($data = [])
    {
        foreach ($data as $key => $value) {
            if (!in_array($key, ['fileSystem', 'http'])) $this->{$key} = $value;
        }
    }

    /**
     * @param null $api_key
     */
    public function setApiKey($api_key)
    {
        $this->api_key = $api_key;
    }

    /**
     * @return bool
     */
    public function hasApiKey()
    {
        return !empty($this->api_key);
    }

    /**
     * @param $id
     */
    private function getProcessById($id)
    {
        $process = $this->processes->filter(function ($item) use ($id) {
            return $item->id === $id;
        })->first();
        return $process;
    }

    /**
     * @param $url
     * @return string
     */
    private function fixURL($url)
    {
        if (strpos($url, 'http') === false)
            $url = "https:" . $url;

        return $url;
    }

    /**
     * @return null
     */
    public function getApiKey()
    {
        return $this->api_key;
    }

    /**
     * @return string
     */
    public function getInputMethod()
    {
        return $this->input_method;
    }

    /**
     * @return mixed
     */
    public function getInput()
    {
        if(!empty($this->inputs)) return $this->inputs;
        return $this->input;
    }


    /**
     * @return mixed
     */
    public function getInputFormat()
    {
        $inputObj = (!empty($this->inputs)) ? $this->inputs[0] : $this->input;
        return isset($this->input) ? $inputObj->getFormat() : $this->input_format;
    }

    /**
     * @return mixed
     */
    public function getOutputFormat()
    {
        return isset($this->output) ? $this->getOutput()->getFormat() : $this->output_format;
    }

    /**
     * @param array $options
     */
    public function setConverterOptions($options = [])
    {
        foreach ($options as $key => $value) {
            $this->setConverterOption($key, $value);
        }
    }

    /**
     * @param $name
     * @param $value
     */
    private function setConverterOption($name, $value)
    {
        $this->converteroptions[$name] = $value;
    }

	/**
	 * @param $preset
	 * @return $this
     */
	public function withPreset($preset)
    {
        $this->preset = $preset;
        return $this;
    }

    /**
     * Set a converter option
     * @param $key
     * @param $value
     * @return $this
     */
    public function withOption($key, $value)
    {
        $this->setConverterOption($key, $value);
        return $this;
    }

    /**
     * Set an array of converter options
     * @param array $options
     * @return $this
     */
    public function withOptions(array $options)
    {
        $this->setConverterOptions($options);
        return $this;
    }

    /**
     * Set the quality, usually used with JPG images
     * Value can be 1-100
     * @param $num
     * @return $this
     */
    public function quality($num)
    {
        $this->setConverterOption('quality', $num);
        return $this;
    }

    /**
     * Set the DPI of the image
     * Print DPI: 300
     * Web DPI: ~72
     * @param $num
     * @return $this
     */
    public function dpi($num)
    {
        $this->setConverterOption('density', $num);
        return $this;
    }

    /**
     * Set the video codec
     * Values can be:
     *        - H264
     *        - MPEG2VIDEO
     *        - SORENSON
     *        - THEORA
     *        - VP8
     *        - RV20
     *        - MPEG4
     *        - WMV2
     *          more...
     * @param $codec
     * @return $this
     */
    public function videoCodec($codec)
    {
        $this->setConverterOption('video_codec', $codec);
        return $this;
    }

    /**
     * Set the audio codec
     * Values can be:
     *        - AAC
     *        - AC3
     *        - OGG
     *        - MP3
     *        - WMAV2
     *          more...
     * @param $codec
     * @return $this
     */
    public function audioCodec($codec)
    {
        $this->setConverterOption('audio_codec', $codec);
        return $this;
    }

    /**
     * Audio bitrate
     * 192, 128, 96, 48
     * @param $bitrate
     * @return $this
     */
    public function audioBitrate($bitrate)
    {
        $this->setConverterOption('audio_bitrate', $bitrate);
        return $this;
    }

    /**
     * Trim start
     * @param $time
     * @return $this
     */
    public function trimFrom($time)
    {
        $this->setConverterOption('trim_from', $time);
        return $this;
    }

    /**
     * Trim end
     * @param $time
     * @return $this
     */
    public function trimTo($time)
    {
        $this->setConverterOption('trim_to', $time);
        return $this;
    }

    /**
     * Video faststart for streaming MP4s
     * @param $is_true
     * @return $this
     */
    public function faststart($is_true)
    {
        $this->setConverterOption('faststart', $is_true);
        return $this;
    }

    /**
     * Custom command line options for ffmpeg and imagemagick
     * Use placeholders {INPUTFILE} and {OUTPUTFILE}
     * Will override all other converter options
     * @param $command
     * @return $this
     */
    public function command($command)
    {
        $this->setConverterOption('command', $command);
        return $this;
    }

    /**
     * Page range selected for output
     * Used with documents
     * @param $from
     * @param $to
     * @return $this
     */
    public function pageRange($from, $to)
    {
        $this->setConverterOption('page_range', "$from-$to");
        return $this;
    }

    /**
     * Dynamic PDF creation using DOCX/PPTX templates
     * See: https://cloudconvert.com/blog/dynamic-pdf-creation-using-docx-templates/
     * @param $variables
     * @return $this
     */
    public function templating($variables)
    {
        $this->setConverterOption('templating', $variables);
        return $this;
    }

    /**
     * Prepare the running process for the upload/download
     * Sets any outstanding options or preset
     */
    private function prepareProcessForConversion()
    {
        $this->getOutput()->setConverterOptions($this->converteroptions);
        $this->getOutput()->setPreset($this->preset);
        $this->getProcess()->setQueryOptions($this->options);
    }

    /**
     * @return mixed
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @param mixed $output
     */
    public function setOutput($output)
    {
        $this->output = $output;
    }

	/**
	 * @param $path
	 * @param $options
	 * @param $instance
	 * @return mixed
	 */
	private function returnInstanceWithOptions($path, $options, $instance)
	{
        if($this->isFormat($path)){
            $instance->setFormat($path);
        } else {
            $instance->setPath($path);
        }
		$instance->setOptions($options);
		return $instance;
	}

    /**
     * @return bool
     */
    private function hasOutput()
    {
        return !!$this->getOutput();
    }

    /**
     * @param null $output
     * @throws Exception
     */
    private function checkOutput($output = null)
    {
        if(! $this->hasOutput() && is_null($output) )
            throw new Exception('Please provide the output path');

        if(! $this->hasOutput() && ! is_null($output) ) {
            $this->initOutput($output);
            $this->getProcess()->compareOutput($this->getOutput());
        }
    }

    /**
     * @param $type
     */
    private function initOutput($type)
    {
        if ($type instanceof Storage) {
            $this->setOutput(new ConvertStorage($type));
        } else {
            $this->setOutput(new ConvertLocalFile($type));
        }
    }

    public function reset()
    {
        if( $this->getProcess() && $this->getProcess()->isFinished() ) {
            $this->resource = null;
            $this->input = null;
            $this->output = null;
            $this->process = null;
            $this->preset = null;
            $this->input_method = null;
            $this->input_format = null;
            $this->output_format = null;
        }
    }

    /**
     * @param $file
     * @throws Exception
     */
    protected function addMultipleInput($file)
    {
        $input = $this->init($file);
        if (!$input instanceof ConvertRemoteFile) {
            throw new \InvalidArgumentException('Merged files must be remote files at this time');
        }
        $this->inputs[] = $input;
        $this->resource = null;
    }

    private function isFormat($path)
    {
        return ctype_alnum($path);
    }


}
