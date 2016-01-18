<?php namespace RobbieP\CloudConvertLaravel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use RobbieP\CloudConvertLaravel\CloudConvert;
use Symfony\Component\Console\Input\InputOption;


class ConversionTypes extends Command
{

    use CloudConvertCommandTrait;
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'cloudconvert:types';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show all available conversion types';


    /**
     * Create a new command instance.
     *
     * @param CloudConvert $cloudConvert
     * @return \RobbieP\CloudConvertLaravel\Commands\ConversionTypes
     */
    public function __construct(CloudConvert $cloudConvert)
    {
        $this->cloudConvert = $cloudConvert;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $input = $this->option('inputformat');
        $output = $this->option('outputformat');
        $group = $this->option('group');
        $types = $this->cloudConvert->input($input)->output($output)->conversionTypes($group);
        $headers = [];
        $rows = [];
        if (empty($output) || empty($input)) {
            list($headers, $rows) = $this->showAllTypesTable($types);
        } else if (!empty($output) && !empty($input)) {
            $this->line("**************************************************************");
            $this->comment("These are all the conversion options for converting <error>{$input}</error> to <error>{$output}</error> ");
            $this->line("**************************************************************");
            list($headers, $rows) = $this->showAllOptionsTable($types);
        }

        $this->table($headers, $rows);
        $this->info('To see all available options, provide both --inputformat and --outputformat');
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array();
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('inputformat', 'i', InputOption::VALUE_OPTIONAL, 'Search by input format', null),
            array('outputformat', 'o', InputOption::VALUE_OPTIONAL, 'Search by output format', null),
            array('group', 'g', InputOption::VALUE_OPTIONAL, 'Search by file group', null),
        );
    }

    /**
     * @param Collection $types
     * @return array
     */
    private function showAllTypesTable($types)
    {
        $headers = ['Input', 'Output', 'Default converter options', 'Group', 'Converter'];
        $rows = [];
        foreach ($types as $k => $type) {
            $options = array_filter((array)$type->converteroptions, 'strlen');
            $rows[] = [$type->inputformat, $type->outputformat, http_build_query($options, '', ', '), $type->group, $type->converter];
        }
        return array($headers, $rows);
    }

    /**
     * @param Collection $types
     * @return array
     */
    private function showAllOptionsTable($types)
    {
        $headers = ['Option', 'Default value'];
        $rows = [];
        foreach ($types as $k => $type) {
            $options = (array)$type->converteroptions;
            foreach ($options as $opt => $default) {
                $rows[] = [$opt, $default];
            }
        }
        return array($headers, $rows);
    }

}
