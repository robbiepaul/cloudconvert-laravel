<?php

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

namespace RobbieP\CloudConvertLaravel;

use Config;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;


class CloudConvert
{
    use HttpClient;

    const INPUT_DOWNLOAD = 'download';
    const INPUT_UPLOAD = 'upload';
    const INPUT_WEBSITE = 'website';
    const INPUT_URL = 'url';
    protected $fileSystem;

    /**
     * @var null
     */
    private $api_key;
    private $api_url = 'https://api.cloudconvert.org';
    private $process;
    private $input;
    private $output;
    private $resource;
    private $input_method;
    private $input_format;
    private $output_format;
    private $processes = [];
    private $options = [];
    private $preset = null;
    private $converteroptions;

    /**
     * Available storage options, must be configured.
     * More soon: 'dropbox','googledrive'
     * @var array
     */
    private $storage_options = ['s3', 'ftp'];


    /**
     * @param null $api_key
     */
    function __construct($api_key = null)
    {
        $this->api_key = $api_key ?: Config::get('cloudconvert-laravel::api_key');
        $this->setClient();
        $this->setFilesystem();
    }

    /**
     * @param $resource
     * @param null $input
     * @param null $output
     * @return $this
     * @throws \Exception
     */
    public function make($resource, $input = null, $output = null)
    {
        $this->assignInputAndOutputVars($input, $output);
        $this->init($resource);
        $this->startProcess();
        return $this;
    }

    public function setFilesystem($fileSystem = null)
    {
        $this->fileSystem = (!is_null($fileSystem)) ? $fileSystem : new Filesystem();

    }

    /**
     * @param $file
     * @return $this
     * @throws \Exception
     */
    public function file($file)
    {
        $this->init($file);
        return $this;
    }

    /**
     * @param $type
     * @return $this|CloudConvert
     */
    public function to($type)
    {
        $this->convert($type);
        $this->save();
        return $this;
    }

    /**
     * @param null $type
     * @return $this|CloudConvert
     * @throws \Exception
     */
    public function convert($type = null)
    {
        $this->validateConversion($type);
        if ($type instanceof Storage) {
            $this->setOutput(new ConvertStorage($type));
        } else {
            $this->setOutput(new ConvertLocalFile($type));
        }

        $this->startProcess();

        return $this;
    }

    /**
     * @throws \Exception
     * @internal param null $output
     * @internal param null $path
     * @return $this|CloudConvert
     */
    public function save()
    {
        if ($this->process->isFinished()) {
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
        $this->process->convert($this->getInput(), $this->getOutput());
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
     * @throws \Exception
     */
    public function conversionTypes($group = null)
    {
        $results = $this->http->get($this->api_url . "/conversiontypes?inputformat={$this->getInputFormat()}&outputformat={$this->getOutputFormat()}");
        $this->types = new Collection($results);
        if ($this->types->isEmpty()) {
            throw new \Exception('No conversion types found');
        }
        if (!is_null($group)) {
            $this->types = $this->filterTypesByGroup($this->types, $group);
        }
        return $this->types;
    }

    /**
     * @return Collection
     * @throws \Exception
     */
    public function processes()
    {
        $this->checkAPIkey();
        $results = $this->start("/processes?apikey={$this->api_key}");
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
     * @throws \Exception
     * @internal param $input
     * @internal param $output
     * @return Process
     */
    public function startProcess()
    {
        $this->checkAPIkey();
        $this->validateFormats();
        $response = $this->start('/process', [
            'inputformat' => $this->getInputFormat(),
            'outputformat' => $this->getOutputFormat(),
            'apikey' => $this->api_key
        ]);

        if (!empty($this->options['callback'])) {
            return true;
        }
        return $this->setProcess(new Process($response, $this->getInputFormat(), $this->getOutputFormat()));
    }

    /**
     * @param $process
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
        return $this->http->post($this->api_url . $endpoint, $params, $this->getQueryOptions());
    }

    /**
     * @param $resource
     * @return mixed
     * @throws \Exception
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
                throw new \Exception("File input is not readable");
        }
    }

    /**
     * @return mixed
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
     * @return mixed
     */
    public function initFromLocalFile()
    {
        return $this->input = new ConvertLocalFile($this->resource);
    }

    /**
     * @return mixed
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
     * @throws \Exception
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
     * @throws \Exception
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
        $data = $this->process->download();
        $this->getOutput()->setData($data);
        $this->getOutput()->save();
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
     * @throws \Exception
     */
    public function downloadURL()
    {
        if (isset($this->process->output->url)) return $this->process->output->url;
        throw new \Exception('Download URL not ready yet.');
    }

    /**
     * @param $data
     * @return $this
     */
    private function forceDownload($data)
    {
        $filename = (strstr($this->input, '.' . $this->input_format)) ? str_replace('.' . $this->input_format, '.' . $this->output_format, basename($this->input)) : "file.{$this->output_format}";
        return \Illuminate\Support\Facades\Response::make($data, 200)
            ->header('Content-Type', 'application/octet-stream')
            ->header('Content-Transfer-Encoding', 'Binary')
            ->header('Content-disposition', "attachment; filename={$filename}");
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
	 * @param $types
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
     * @throws \Exception
     */
    private function validateFormats()
    {
        if (!$this->getInputFormat()) {
            throw new \Exception('No input format provided');
        }
        if (!$this->getOutputFormat()) {
            throw new \Exception('No output format provided');
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
     * @param $type
     * @throws \Exception
     */
    private function validateConversion($type)
    {
        if (!$this->input) {
            throw new \Exception('Please set the file before converting');
        }
    }

    /**
     * @return array
     */
    private function getQueryOptions()
    {
        return $this->options;
    }

    /**
     * @param $name
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
     * @throws \Exception
     * @internal param $path
     * @return $this|CloudConvert
     */
    public function convertFileAndSaveTo()
    {
        $this->convertFile();
        if ($this->process->waitForConversion()) {
            return $this->downloadConvertedFile();
        }
        throw new \Exception('Problem saving file');
    }


    /**
     * @param $url
     * @throws \Exception
     */
    public function validateURL($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \Exception('Not a valid URL. Must be fully qualified including protocol.');
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
        $instance = new StorageS3();
		return $this->returnInstanceWithOptions($path, $options, $instance);
    }

    /**
     * @param $path
     * @param array $options
     * @return StorageFtp
     */
    public function FTP($path, $options = [])
    {
        $instance = new StorageFtp();
		return $this->returnInstanceWithOptions($path, $options, $instance);
    }

    /**
     * @param $provider
     * @throws \Exception
     */
    public function validateProvider($provider)
    {
        if (!in_array($provider, $this->storage_options)) {
            throw new \Exception ($provider . ' is not supported. Please choose from: ' . implode(', ', $this->storage_options));
        }
    }

    /**
     * @param $provider
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
     * @param $action
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
            return $item->id = $id;
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
        return $this->input;
    }


    /**
     * @return mixed
     */
    public function getInputFormat()
    {
        return isset($this->input) ? $this->getInput()->getFormat() : $this->input_format;
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
     * Prepare the running process for the upload/download
     * Sets any outstanding options or preset
     */
    private function prepareProcessForConversion()
    {
        $this->getOutput()->setConverterOptions($this->converteroptions);
        $this->getOutput()->setPreset($this->preset);
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
		$instance->setOptions(array_merge(['path' => $path], $options));
		return $instance;
	}


}