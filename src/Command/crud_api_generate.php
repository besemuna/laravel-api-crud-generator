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
    protected $modelName;

    /** @var string  */
    protected $schemaCreate;

    /** @var string  */
    protected $addValidation;

    /** @var string  */
    protected $updateValidation;

    /** @var string  */
    protected $relationships;

    /** @var string  */
    protected $softDeletes = false;


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
        $this->modelName = $name;

        # get and process fields and foreign keys
        $fields = $this->processFieldsFromFile($this->option('fields'));

        # process soft deletes
        if ($this->option('soft_deletes')) {
            $this->softDeletes = true;
        }

        # set all info needed

        # build model
        $this->buildModel();

        # build controller
        $this->buildController();

        # build migration file
        $this->buildMigration();

        # add route file
        $this->appendRoute();
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

            # process fields
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

            # process relationships
            if (isset($json["relationships"])) {
                $this->setRelationshipValues($json["relationships"]);
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
        $schemaCreate = "";

        foreach($migrationArray as $key) {
            $schemaCreate .= '$table' ."->{$key["type"]}('{$key['name']}')";

            if (isset($key["modifier"])) {
                $schemaCreate .= "->{$key["modifier"]}();\n";
            }else {
                $schemaCreate .= "; \n";
            }
        }

        # softdeletes
        if ($this->option('soft_deletes')) {
            $schemaCreate .= '$table->softDeletes();';
        }

        # replace with double quotes and remove last comma
        $schemaCreate = str_replace("'", '"', $schemaCreate);

        $this->schemaCreate = $schemaCreate;
        return true;
    }

    /**
     * Process and sets all values needed for final insertion of relationships
     * @param array $migrationArray Array of all info needed to set required values
     * @return bool
     */
    public function setRelationshipValues($relationships) {
        $rels = "";
        foreach ($relationships as $relationship) {
            $template = '
public function {{name}}() {
    return $this->{{type}}("{{class}}");
}
            ';

            $rels .= str_replace(
                [
                    "{{name}}",
                    "{{type}}",
                    "{{class}}"
                ],
                [
                  $relationship["name"],
                  $relationship["type"],
                  $relationship["class"]
                ],
                $template
            );
            $rels .= "\n";

        }

        $this->relationships = $rels;


    }

    /**
     * Gets a stub
     * @param string $stub name of the stub you want to retrieve
     * @return string
     */
    public function getStub($stub) {
        $stubb = File::get(base_path("packages/besemuna/LaravelApiCrudGenerator/src/stubs/$stub.stub"));
        return $stubb;
    }

    /**
     * Builds a model
     * @return bool
     */
    public function buildModel() {
        $stub = $this->getStub("model");

        $replaceSearch = [
            "{{useSoftDeletes}}",
            "{{softDeletes}}",
            "{{relationships}}",
            "{{modelNameSingularUpperFirst}}"
        ];

        $replaceWith = [
            "",
            "",
            $this->relationships,
            str_singular(ucfirst($this->modelName)),
        ];

        if ($this->softDeletes) {
            $replaceWith[0] = "use Illuminate\Database\Eloquent\SoftDeletes;";
            $replaceWith[1] = "use SoftDeletes;";
        }

        $migration = str_replace($replaceSearch, $replaceWith, $stub);

        File::put(app_path("/". str_singular(ucfirst($this->modelName)). ".php"), $migration);
        return true;
    }

    /**
     * Builds a controller
     * @return bool
     */
    public function buildController() {
        $stub = $this->getStub("controller");

        $replaceSearch = [
            "{{modelNameSingularUpperFirst}}",
            "{{modelNamePluralLowerAll}}",
            "{{modelNameSingularLowerAll}}",
            "{{validationRules}}"
        ];

        $replaceWith = [
            str_singular(ucfirst($this->modelName)),
            str_plural(strtolower($this->modelName)),
            str_singular(strtolower($this->modelName)),
            ""
        ];

        if ($this->addValidation !== null) {
            $replaceWith[3] = $this->addValidation;
        }

        $controller = str_replace($replaceSearch, $replaceWith, $stub);
        File::put(base_path("app/Http/Controllers/". str_singular(ucfirst($this->modelName)). "Controller.php"), $controller);
        return true;
    }

    /**
     * Builds a migration file
     * @return bool
     */
    public function buildMigration() {
        $stub = $this->getStub("migration");

        $replaceSearch = [
            "{{modelNamePluralFirstUpper}}",
            "{{modelNamePluralLowerAll}}",
            "{{SchemaCreate}}",
        ];

        $replaceWith = [
            str_plural(ucfirst($this->modelName)),
            str_plural(strtolower($this->modelName)),
            $this->schemaCreate
        ];



        $migration = str_replace($replaceSearch, $replaceWith, $stub);
        $fileName = date("Y_m_d_U") . "_create_" . str_plural(strtolower($this->modelName)) . "_table";

        File::put(base_path("database/migrations/$fileName.php"), $migration);
        return true;
    }

    /**
     * Append route file
     * @return bool
     */
    public function appendRoute() {
        $routes = "Route::resource('{{modelNameSingularLowerAll}}', '{{ControllerName}}');";
        $routes = str_replace(
            [
                "{{modelNameSingularLowerAll}}",
                "{{ControllerName}}"
            ],
            [
                str_singular(strtolower($this->modelName)),
                str_singular(ucfirst($this->modelName)) . "Controller"

            ],
            $routes
        );
        $routes = str_replace("'", '"', $routes);
        File::append(base_path("routes/api.php"), "\n$routes");
        return true;
    }
}
