<?php

namespace Besemuna\LaravelApiCrudGenerator\Command;

use Illuminate\Console\Command;
use File;

class GenerateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravel_crud:generate
                            {name : The name of the crud}
                            {--fields= : Field names from a json file .}
                            {--soft_deletes= : Allow for soft deletes .}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command generate crud endpoints on the fly!';

    /** @var string  */
    protected $schemaCreate;

    /** @var string  */
    protected $addValidation;

    /** @var string  */
    protected $updateValidation;


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        # get name, model name, migration name, table name
        $name = $this->argument('name');
        $modelName = str_singular(ucfirst($name));
        $migrationName = str_plural(snake_case($name));
        $tableName = $migrationName;

        # get and process fields and foreign keys
        $fields = $this->processFieldsFromFile($this->option('fields'));


        # set all info needed

        # build model

        # build controller

        # build migration file

        # add route file
    }

    /**
     * Process fields from a file in a json format
     * @param string $fileName Path of the file
     * @return string
     */
    public function processFieldsFromFile($file) {
        try {
            $fieldsFile = File::get($file);
            $json = json_decode($fieldsFile,true);

            # prepare info for migration and validation
            $validationArray = array();
            $migrationArray = array();

            foreach($json['fields'] as $key) {
                # migration array
                $array = [
                    "name" => $key['name'],
                    "type" => $key['type'],
                    "modifier" => $key['modifier']
                ];

                if (isset($key['foreign_key'])) {
                    $array['foreign_key'] = $key["foreign_key"];
                }

                array_push($migrationArray, $array);

                # validation array
                if (isset($key['validation'])) {
                    $validationArray[$key["name"]] = $key["validation"];
                }
            }

            $this->setValidationValues($validationArray);
            $this->setMigrationValues($migrationArray);

        }catch(\Illuminate\Contracts\Filesystem\FileNotFoundException $e) {
            echo 'file does not exist';
        }
    }

    /**
     * Process and sets all values needed for final insertion
     * @param array $validationArray Array of all info needed to set required values
     * @return bool True if successful and false if there are errors
     */
    public function setValidationValues($validationArray) {
        $addValidation = "";
        foreach($validationArray as $key => $value) {
            $addValidation .= "'{$key}' => '{$value}',\n";
        }

        # replace with double quotes and remove last comma
        $addValidation = str_replace("'", '"', $addValidation);
        $addValidation = rtrim($addValidation, ",");

        $this->addValidation = $addValidation;

    }

    /**
     * Process and sets all values needed for final insertion
     * @param array $migrationArray Array of all info needed to set required values
     * @return bool
     */
    public function setMigrationValues($migrationArray) {

    }
}
