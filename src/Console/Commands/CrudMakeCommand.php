<?php

namespace SantosAlan\LaravelCrud\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Pluralizer;
use Illuminate\Support\Str;

class CrudMakeCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:crud';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:crud
                            {--t|table= : [all | table number] }
                            {--p|path-models=App\\Models : Namespace to Models (Directories will be created) }
                            {--r|routes=Y : [Y | N] }
                            {--a|api-client=N : [Y | N] (Api client to System Core generated with santosalan/lumen-crud)}
                            {--w|web-service=N : [Y | N] (REST Web Service)}
                            {--b|base-model=N : [Y | N] }
                            {--T|theme=1 : [1 = AdminLTE | 2 = Porto Admin] (Put the theme files in an exclusive folder inside public / vendor... If the theme is not free, an authorized copy of the theme is required... We will not deliver copies of themes that are not free. Any unauthorized copy is your complete responsibility.)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a CRUD, Web Service or API Client to SantosAlan/Lumen-CRUD';

    /**
     * The path of Models
     *
     * @var string
     */
    private $pathModels = 'App\\Models\\';

    /**
     * [$routes description]
     * @var boolean
     */
    private $routes = true;

    /**
     * [$apiLumen description]
     * @var boolean
     */
    private $apiLumen = false;

    /**
     * [$webService description]
     * @var boolean
     */
    private $webService = false;

    /**
     * [$theme 1=AdminLTE | 2=Porto Admin]
     * @var integer
     */
    private $theme = 1;

    /**
     * [$tables description]
     *
     * @var [type]
     */
    private $tables = [];

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
     * [processOptionRoutes description]
     * @return [type] [description]
     */
    public function processOptionRoutes()
    {
        $this->alert('ROUTES PROCESS');

        // Verify option ROUTES
        if (in_array(strtoupper(trim($this->option('routes'))), ['N','NO','FALSE'])) {
            $this->routes = false;
        }

    }

    /**
     * [processRoutes description]
     * @return [type] [description]
     */
    public function processRoutes()
    {
        if ($this->routes) {

            $template = $this->getTemplate('routes');
            $routes = '';


            if (trim($this->option('table')) === 'all') {

                foreach ($this->tables as $table) {
                    $m = [
                        'plural_uc' => ucwords($table->plural),
                        'plural' => $table->plural,
                        'kebab_plural' => Str::kebab($table->plural),
                    ];

                    $temp = $template;

                    foreach ($this->marks()['routes'] as $mark){
                        $temp = str_replace('{{{' . $mark . '}}}', trim($m[$mark]), $temp);
                    }

                    $routes .= $temp;
                }


            } elseif (trim($this->option('table')) !== '') {

                $tableKey = $this->option('table');

                $table = $this->tables[$tableKey];

                $m = [
                    'plural_uc' => ucwords($table->plural),
                    'plural' => $table->plural,
                    'kebab_plural' => Str::kebab($table->plural),
                ];

                $temp = $template;

                foreach ($this->marks()['routes'] as $mark){
                    $temp = str_replace('{{{' . $mark . '}}}', trim($m[$mark]), $temp);
                }

                $routes = $temp;

            }

            $fileRoutes = $this->webService 
                            ? fopen(base_path() . '/routes/api.php', 'a+') 
                            : fopen(base_path() . '/routes/web.php', 'a+');

            fwrite($fileRoutes, $routes);
            fclose($fileRoutes);
        }
    }

    /**
     * [processOptionPathModels description]
     * @return [type] [description]
     */
    public function processOptionPathModels()
    {
        $this->alert('PATH MODELS PROCESS');

        // Verify option TABLE
        if (trim($this->option('path-models')) !== '') {
            $this->pathModels = Str::finish($this->option('path-models'), '\\');
        }

    }

    /**
     * [verifyOptionTable description]
     *
     * @return [type] [description]
     */
    public function processOptionTable()
    {
        $this->alert('TABLE PROCESS');

        // Tables
        $tables = DB::select('SHOW TABLES');

        $prefix = env('DB_PREFIX', '') !== '' 
                    ? env('DB_PREFIX') 
                    : ( env('DB_TABLE_PREFIX', '') !== '' 
                        ? env('DB_TABLE_PREFIX') 
                        : env('DB_PREFIX_TABLE', '') );
        
        foreach ($tables as $t) {
            $tbName = substr($t->{'Tables_in_' . env('DB_DATABASE')}, strlen($prefix));
            if (in_array($tbName, ['migrations', 'password_resets', 'failed_jobs', 'password_reset_tokens', 'personal_access_tokens'])) {
                continue;
            }

            $prepareName = function ($name, $format) {
                $name = explode('_', $name);
                $name[count($name) - 1] = Pluralizer::{$format}(end($name));

                return implode('_', $name);
            };

            // Make the table object
            $objTab = new \stdClass();
            $objTab->originalName = $t->{'Tables_in_' . env('DB_DATABASE')};
            $objTab->name = $tbName;
            $objTab->relationTable = false;
            $objTab->singular = Str::camel($prepareName($objTab->name, 'singular'));
            $objTab->plural = Str::camel($prepareName($objTab->name, 'plural'));
            $objTab->snakeSingular = Str::snake($objTab->singular);
            $objTab->snakePlural = Str::snake($objTab->plural);
            $objTab->fieldDisplay = false;
            $objTab->fk = $objTab->snakeSingular . '_id';
            $objTab->fields = [];
            $objTab->belongsTo = [];
            $objTab->hasMany = [];
            $objTab->hasOne = [];
            $objTab->belongsToMany = [];
            $objTab->marks = [];
            $objTab->arqs = [];


            array_push($this->tables, $objTab);
        }

        // dd($this->tables);

        // Register belongsToMany
        foreach ($this->tables as $table) {
            $tabs = explode('_', $table->name);

            if (count($tabs) === 2) {
                $tab1 = Pluralizer::plural($tabs[0]);
                $tab2 = Pluralizer::plural($tabs[1]);
                $rel1 = false;
                $rel2 = false;

                foreach ($this->tables as $t) {
                    if ($t->name == $tab1) {
                        $rel1 = true;
                    }

                    if ($t->name == $tab2) {
                        $rel2 = true;
                    }
                }

                if ($rel1 && $rel2) {
                    foreach ($this->tables as $t) {
                        if ($t->name == $tab1) {
                            $t->belongsToMany[$table->name] = $tab2;
                        }

                        if ($t->name == $tab2) {
                            $t->belongsToMany[$table->name] = $tab1;
                        }
                    }

                    $table->relationTable = true;
                }


            }

        }

        // dd($this->tables);


        // Verify option TABLE
        if (trim($this->option('table')) === '') {

            $this->alert('TABLES');
            foreach ($this->tables as $tableKey => $table) {
                $this->info($tableKey . '->' . $table->name);
            }
            die;

        } else {
            foreach ($this->tables as $tableKey => $table) {
                $this->readTable($tableKey);
            }
        }

        foreach ($this->tables as $table) {
            // Register hasMany and hasOne
            foreach ($this->tables as $t) {
                if ($table->name === $t->name || $t->relationTable === true) {
                    continue;
                }

                if (in_array($table->name, $t->belongsTo)) {
                    foreach ($t->fields as $f) {
                        if ($f->name === $table->fk) {
                            array_push($table->{$f->unique ? 'hasOne' : 'hasMany'}, $t->name);
                        }
                    }
                }
            }


            // Process Marks
            $table->marks = $this->processMarks($table);
        }



        // DUMPS
        // if (trim($this->option('table')) === 'all') {
        //     dd($this->tables);

        // } elseif (trim($this->option('table')) !== '') {
        //     $tableKey = $this->option('table');
        //     dd($this->tables[$tableKey]);
        // }


    }

    /**
     * [processOptionApiClient description]
     * @return [type] [description]
     */
    public function processOptionApiClient()
    {
        $this->alert('API CLIENT PROCESS');

        // Verify option API CLIENT
        if (!in_array(strtoupper(trim($this->option('api-client'))), ['N','NO','FALSE'])) {
            $this->apiLumen = true;
        }

    }

    /**
     * [processOptionWebService description]
     * @return [type] [description]
     */
    public function processOptionWebService()
    {
        $this->alert('WEB SERVICE PROCESS');

        // Verify option WEB SERVICE
        if (!in_array(strtoupper(trim($this->option('web-service'))), ['N','NO','FALSE'])) {
            $this->webService = true;
        }

    }

    /**
     * [processOptionTheme description]
     * @return [type] [description]
     */
    public function processOptionTheme()
    {
        $this->alert('THEME PROCESS');

        // Verify option THEME
        if (in_array(trim($this->option('theme')), [1, 2])) {
            $this->theme = $this->option('theme');
        }

    }

    /**
     * [themeDir description]
     * @return string
     */
    private function themeDir()
    {
        $dir = 'adminlte';

        switch($this->theme) {
            case 1: $dir = 'adminlte'; break;
            case 2: $dir = 'porto-admin'; break;
            default: $dir = 'adminlte';
        }

        // dump($this->theme);
        // dump($dir);

        return $dir;
    }

    /**
     * [readTable description]
     *
     * @return [type] [description]
     */
    public function readTable(int $tableKey)
    {
        $table = $this->tables[$tableKey];

        // process table
        $this->warn('TABLE ' . $table->name . ':');

        $fields = DB::select('DESC ' . $table->originalName);

        //dump($fields);
        foreach ($fields as $f) {
            $objField = $this->readField($f, $table);

            array_push($table->fields, $objField);

            // Register BelongsTo
            if ($objField->fk) {
                array_push($table->belongsTo, $objField->fk);
            }
        }


    }


    /**
     * [readField description]
     *
     * @return [type] [description]
     */
    public function readField($field, $table)
    {
        $objField = new \stdClass();

        //$this->alert($field->Field);
        preg_match('/[a-zA-Z]+/', $field->Type, $type);
        preg_match('/[0-9]+/', $field->Type, $size);
        preg_match('/([a-zA-Z_0-9]+)_id/', $field->Field, $fk2);

        $types = null;
        if ($type[0] === 'enum') {
            // dump($field->Type);
            preg_match('/\([\'0-9,a-zA-Z]+\)/', $field->Type, $types);
            $types = str_replace(['(', "'",')'], '', $types[0]);
        }

        $objField->name = $field->Field;
        $objField->type = $type[0];
        $objField->inTypes = $types;
        $objField->size = isset($size[0]) ? $size[0] : null;
        $objField->unsigned = strpos($field->Type, 'unsigned') !== false ? true : false;
        $objField->required = $field->Null === 'NO' ? true : false;
        $objField->pk = $field->Key === 'PRI' ? true : false;
        $objField->fk = empty($fk) ? (empty($fk2) ? false : Pluralizer::plural($fk2[1])) : Pluralizer::plural($fk[1]);
        $objField->display = false;
        $objField->unique = $field->Key === 'UNI' ? true : false;
        $objField->default = $field->Default;
        $objField->autoIncrement = strpos($field->Extra, 'auto_increment') !== false ? true : false;
        $objField->validator = $this->generateValidator($objField, $table);
        $objField->filter_set = $this->generateFilterSet($objField);
        $objField->filter = $this->generateFilter($objField);


        $displays = [
                        'name',
                        $table->snakeSingular . '_name',
                        'name_' . $table->snakeSingular,
                        'title',
                        $table->snakeSingular . '_title',
                        'title_' . $table->snakeSingular,
                        'username',
                        'user',
                        'login',
                        'email'
                    ];
        if (!$table->fieldDisplay && in_array($objField->name, $displays)) {
            $table->fieldDisplay = true;
            $objField->display = true;
        }


        return $objField;
    }

    /**
     * [generateValidator description]
     * @return [type] [description]
     */
    public function generateValidator($objField, $table)
    {

        // Get Field Type
        $funcType = function () use ($objField) {

            switch ($objField->type) {
                case 'int':
                    $type = 'integer';
                    break;

                case 'char':
                case 'varchar':
                case 'text':
                case 'enum':
                    $type = 'string';
                    break;

                default:
                    $type = $objField->type;
            }

            return $type;
        };

        // Get PK name
        foreach ($table->fields as $f) {
            if ($f->pk) {
                $pk = $f->name;
            }
        }

        $validator = $funcType();
        $validator .= $objField->type === 'enum' ? '|in:' . $objField->inTypes : '';
        $validator .= $objField->size && in_array($objField->type, ['char','varchar','text'])
                        ? '|max:' . $objField->size
                        : '';
        $validator .= strpos($objField->name, 'email') !== false ? '|email' : '';
        $validator .= $objField->unique ? '|unique:' . $table->name . ',' . $pk : '';
        $validator .= $objField->required ? '|required' : '';

        return $validator;

    }

    /**
     * [generateFilterSet description]
     * @return [type] [description]
     */
    public function generateFilterSet($objField)
    {

        // Get Field Type
        $funcType = function () use ($objField) {

            switch ($objField->type) {
                case 'int':
                    $type = 'integer';
                    break;

                case 'char':
                case 'varchar':
                case 'text':
                case 'enum':
                    $type = 'string';
                    break;

                default:
                    $type = $objField->type;
            }

            return $type;
        };

        // $this->info($objField->name . ' -> ' . $funcType());

        switch ($funcType()) {
            case 'date':
            case 'datetime':
            case 'time':
            case 'timestamp':
            case 'integer':
                    $filter = "'" . $objField->name . "' => isset(\$r['" . $objField->name . "']) ? \$r['" . $objField->name . "'] : null,
                '" . $objField->name . "-options' => isset(\$r['" . $objField->name . "-options']) ? \$r['" . $objField->name . "-options'] : null,
                '" . $objField->name . "-1' => isset(\$r['" . $objField->name . "-1'])
                                        ? \$r['" . $objField->name . "-1']
                                        : (isset(\$r['" . $objField->name . "-2'])
                                                ? \$r['" . $objField->name . "-2']
                                                : null),
                '" . $objField->name . "-2' => isset(\$r['" . $objField->name . "-2'])
                                        ? \$r['" . $objField->name . "-2']
                                        : (isset(\$r['" . $objField->name . "-1'])
                                                ? \$r['" . $objField->name . "-1']
                                                : null)";
                    break;


            default:
                    $filter = "'" . $objField->name . "' => isset(\$r['" . $objField->name . "']) ? \$r['" . $objField->name . "'] : null";
                    break;
        }

        return $filter;

    }

    /**
     * [generateFilter description]
     * @return [type] [description]
     */
    public function generateFilter($objField)
    {

        // Get Field Type
        $funcType = function () use ($objField) {

            switch ($objField->type) {
                case 'int':
                    $type = 'integer';
                    break;

                case 'char':
                case 'varchar':
                case 'text':
                case 'enum':
                    $type = 'string';
                    break;

                default:
                    $type = $objField->type;
            }

            return $type;
        };

        $filter = "isset(\$filter['" . $objField->name . "'])";
        switch ($funcType()) {
            case 'date':
            case 'datetime':
            case 'time':
            case 'timestamp':
            case 'integer':
                    if (!$objField->fk) {
                        $filter = "isset(\$filter['" . $objField->name . "'])
                                        ? [\$filter['" . $objField->name . "-options'], \$filter['" . $objField->name . "']]
                                        : (isset(\$filter['" . $objField->name . "-1']) && isset(\$filter['" . $objField->name . "-2'])
                                                ? [\$filter['" . $objField->name . "-options'], [\$filter['" . $objField->name . "-1'], \$filter['" . $objField->name . "-2']]]
                                                : null)";
                    } else {
                        $filter .= " ? \$filter['" . $objField->name . "'] : null";
                    }
                    break;

            case 'string':
                    $filter .= " ? '%' . \$filter['" . $objField->name . "'] . '%' : null";
                    break;

            default:
                    $filter .= " ? \$filter['" . $objField->name . "'] : null";
                    break;
        }

        return $filter;

    }

    /**
     * getTemplate
     *
     * @param  [type] $type [description]
     * @return string      [description]
     */
    public function getTemplate($type)
    {
        if ($this->apiLumen) {
            $template = file_get_contents(__DIR__ . '/stubs/api/' . $type . '.stub');
        } elseif ($this->webService) {
            $template = file_get_contents(__DIR__ . '/stubs/web-service/' . $type . '.stub');
        } else {
            switch($type) {
                case 'index.blade':
                case 'form.blade':
                case 'show.blade':
                    $template = file_get_contents(__DIR__ . '/stubs/' . $this->themeDir() . '/' . $type . '.stub');
                    break;

                default:
                    $template = file_get_contents(__DIR__ . '/stubs/' . $type . '.stub');
            }
        }

        if ($template === false) {
            $this->error('CRUD Template [' . $type  . '] not found.');
            die;
        }

       return $template;
    }

    public function getPkDisplay($objTable)
    {
        $pk = null;
        $display = null;

        // Find PK
        foreach ($objTable->fields as $f) {
            if (!$f->pk) {
                continue;
            }

            $pk = $f->name;
        }

        // Find Display
        foreach ($objTable->fields as $f) {
            if (!$f->display) {
                continue;
            }

            $display = $f->name;
        }

        // Verify PK or DISPLAY nulls
        $pk = $pk === null ? ($display === null ? $objTable->fields[0]->name : $display) : $pk;
        $display = $display === null ? $pk : $display;

        return [$pk, $display];
    }

    /**
     * [processMarks description]
     * @param  [type] $objTable [description]
     * @return [type]           [description]
     */
    public function processMarks($objTable)
    {

        // USES
        $prepareUses = function () use ($objTable) {

            $prepareName = function ($name, $format) {
                $name = explode('_', $name);
                $name[count($name) - 1] = Pluralizer::{$format}(end($name));

                return implode('_', $name);
            };

            $uses = 'use ' . $this->pathModels . ucwords($objTable->singular) . ";\n";
            foreach ($objTable->belongsTo as $b) {
                $uses .= 'use ' . $this->pathModels . Str::studly($prepareName($b, 'singular')) . ";\n";
            }

            return $uses;
        };

        // VALIDATORS
        $prepareValidators = function () use ($objTable) {
            $validators = '';
            foreach ($objTable->fields as $f) {
                if (in_array($f->name, ['id', 'created_at', 'updated_at', 'deleted_at', 'remember_token'])){
                    continue;
                }

                if (empty($validators)) {
                    $validators = "'".$f->name."' => '" . $f->validator . "',\n";
                } else {
                    $validators .= "                '".$f->name."' => '" . $f->validator . "',\n";
                }
            }

            return $validators;
        };

        // FILTERS SET
        $prepareFiltersSet = function () use ($objTable) {
            $filters = '';
            foreach ($objTable->fields as $f) {
                if (in_array($f->name, ['id', 'password', 'token', 'token_request', 'token_response', 'token_encrypt', 'remember_token'])){
                    continue;
                }

                if (empty($filters)) {
                    $filters = $f->filter_set . ",\n";
                } else {
                    $filters .= "                " . $f->filter_set . ",\n";
                }
            }

            return $filters;
        };

        // FILTERS
        $prepareFilters = function () use ($objTable) {
            $filters = '';
            foreach ($objTable->fields as $f) {
                if (in_array($f->name, ['id', 'password', 'token', 'token_request', 'token_response', 'token_encrypt', 'remember_token'])){
                    continue;
                }

                if (empty($filters)) {
                    $filters = "'" . $f->name . "' => " . $f->filter . ",\n";
                } else {
                    $filters .= "            '" . $f->name . "' => " . $f->filter . ",\n";
                }
            }

            return $filters;
        };

        // PLUCKS
        $preparePlucks = function () use ($objTable) {
            $plucks = '';
            foreach ($objTable->belongsTo as $b) {
                foreach ($this->tables as $t) {
                    if ($b !== $t->name) {
                        continue;
                    }

                    list($pk, $display) = $this->getPkDisplay($t);

                    if (empty($plucks)) {
                        $plucks = "'" . $t->plural . "' => "
                                . ucwords($t->singular) . "::pluck('" . $display . "', '" . $pk . "')"
                                . ",\n";
                    } else {
                        $plucks .= "                '" . $t->plural . "' => "
                                . ucwords($t->singular) . "::pluck('" . $display . "', '" . $pk . "')"
                                . ",\n";
                    }
                }
            }

            return $plucks;
        };

        // PLUCKS
        // $preparePlucks = function () use ($objTable) {
        //     $plucks = '';
        //     foreach ($objTable->belongsTo as $b) {
        //         foreach ($this->tables as $t) {
        //             if ($b !== $t->name) {
        //                 continue;
        //             }

        //             list($pk, $display) = $this->getPkDisplay($t);

        //             if (empty($plucks)) {
        //                 $plucks = '$' . $t->plural . ' = '
        //                         . ucwords($t->singular) . "::pluck('" . $display . "', '" . $pk . "')"
        //                         . ";\n";
        //             } else {
        //                 $plucks .= '        $' . $t->plural . ' = '
        //                         . ucwords($t->singular) . "::pluck('" . $display . "', '" . $pk . "')"
        //                         . ";\n";
        //             }
        //         }
        //     }

        //     return $plucks;
        // };


        // COMPACTS
        $prepareCompacts = function () use ($objTable) {
            $compacts = '';
            foreach ($objTable->belongsTo as $b) {
                $compacts .= ", '" . $b . "'";
            }

            return $compacts;
        };
        $compacts = $prepareCompacts();
        $compacts_c = empty(trim(substr($compacts, 1))) ? '' : ', compact(' . substr($compacts, 2) . ')';

        // PRIMARY KEY
        $preparePrimaryKey = function () use ($objTable) {
            $pk = 'id';
            $incrementing = true;

            foreach ($objTable->fields as $f) {
                if ($f->pk) {
                    $pk = $f->name;
                    $incrementing = $f->autoIncrement ? 'true' : 'false';
                    break;
                }
            }

            return [$pk, $incrementing];
        };
        list($primary, $incrementing) = $preparePrimaryKey();

        $prepareSoftDeletes = function () use ($objTable) {
            $softDeletes = [null, null];

            foreach ($objTable->fields as $f) {
                if (strtolower($f->name) === 'deleted_at') {
                    $softDeletes = [
                        'use Illuminate\Database\Eloquent\SoftDeletes;',
                        'use SoftDeletes;',
                    ];
                    break;
                }
            }

            return $softDeletes;
        };
        list($useSoftDeletes, $traitSoftDeletes) = $prepareSoftDeletes();

        // FILLABLES
        $prepareFillable = function () use ($objTable) {
            $fillable = null;

            foreach ($objTable->fields as $f) {
                if (!$this->apiLumen && in_array(strtolower($f->name), ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                    continue;
                }

                $fillable .= "'" . $f->name . "', ";
            }

            return $fillable;
        };

        // WITH
        $prepareWith = function () use ($objTable) {
            $with = null;

            foreach ($objTable->belongsTo as $b) {
                $with .= "'" . Pluralizer::singular($b) . "', ";
            }

            return $with;
        };

        // DATES
        $prepareDates = function () use ($objTable) {
            $dates = null;

            foreach ($objTable->fields as $f) {
                if (in_array($f->name, ['created_at', 'updated_at'])) {
                    continue;
                }

                $dates .= in_array(strtolower($f->type), ['date', 'datetime', 'timestamp'])
                            ? "'" . $f->name . "', "
                            : '';
            }

            return $dates;
        };

        // SUBTEMPLATES
        $prepareSubTemplates = function ($type) use ($objTable) {
            $subTemp = null;

            $prepPrimary = function ($table) {
                foreach ($table->fields as $f){
                    if ($f->pk) {
                        return $f->name;
                    }
                }

                return 'id';
            };

            $attr = [];
            switch ($type) {
                case 'belongs':
                    $attr = $objTable->belongsTo;
                    break;

                case 'many':
                    $attr = $objTable->hasMany;
                    break;

                case 'one':
                    $attr = $objTable->hasOne;
                    break;

                case 'belongsMany':
                case 'syncRelationships':
                    $attr = $objTable->belongsToMany;
                    break;

                case 'relationships':
                    $attr = array_merge($objTable->hasMany, $objTable->belongsToMany);
            }


            foreach ($attr as $key => $item) {
                foreach ($this->tables as $t) {
                    if ($t->name !== $item) {
                        continue;
                    }

                    $m = [
                        'plural' => $t->plural,
                        'plural_uc' => ucwords($t->plural),
                        'kebab_plural' => Str::kebab($t->plural),
                        'singular_uc' => ucwords($t->singular),
                        'singular' => $t->singular,
                        'use_model' => $this->pathModels . ucwords($t->singular),
                        'primary_model' => $prepPrimary($t),
                        'fk_model' => $objTable->fk,
                        'relation_table' => $key,
                    ];

                    // dump($objTable->name);
                    // dump($m);

                    $temp = $this->getTemplate($type);

                    foreach ($this->marks()[$type] as $mark){
                        $temp = str_replace('{{{' . $mark . '}}}', trim($m[$mark]), $temp);
                    }

                    $subTemp .= $temp;
                }

            }

            return $subTemp;
        };

        $prepareTitle = function ($field) {
            if (in_array($field, ['name', 'title', 'user', 'username', 'login', 'email', 'created_at', 'updated_at', 'deleted_at'])) {
                $title = "{{ trans('laravel-crud::view." . str_replace('_', '-', $field) . "') }}";
            } else {
                $title = Str::title(str_replace('_', ' ', $field));
            }

            return $title;
        };

        // FILTERS FIELDS
        $prepareFiltersFields = function () use ($objTable, $prepareTitle) {
            $fields = null;

            foreach ($objTable->fields as $field) {
                if (! in_array($field->name, ['id', 'password', 'token', 'token_request', 'token_response', 'token_encrypt', 'remember_token'])) {

                    if ($field->fk) {
                        foreach ($this->tables as $t) {
                            if ($t->name === $field->fk) {
                                $fields .= '
                <div class="col-xs-12 col-12 col-sm-6 col-md-4">
                    <div class="form-group mb-3">
                        <label for="' . $field->name . '" class="control-label">
                            ' . $prepareTitle($t->snakeSingular) . '
                        </label>
                        {{ Form::select("' . $field->name . '", $plucks["' . $t->plural . '"], @$filter["' . $field->name .'"], ["class" => "form-control", "placeholder" => ""]) }}
                    </div>
                </div>' . "\n";

                            }
                        }
                    } elseif (in_array($field->type, ['date', 'datetime', 'time', 'timestamp'])) {
                        $fields .= '
                <div class="col-xs-12 col-12 col-sm-6 col-md-4">
                    <div class="form-group mb-3">
                        <label for="' . $field->name . '" class="control-label">
                            ' . $prepareTitle($field->name) . '
                            <select name="' . $field->name . '-options" id="' . $field->name . '-options" class="pull-right select-options" data-field="' . $field->name . '">
                                <option value="=" {{ @$filter["' . $field->name . '-options"] == "=" ? "selected" : "" }}>{{ trans("laravel-crud::view.equal") }}</option>
                                <option value="<" {{ @$filter["' . $field->name . '-options"] == "<" ? "selected" : "" }}>{{ trans("laravel-crud::view.less-than") }}</option>
                                <option value="<=" {{ @$filter["' . $field->name . '-options"] == "<=" ? "selected" : "" }}>{{ trans("laravel-crud::view.less-equal") }}</option>
                                <option value=">" {{ @$filter["' . $field->name . '-options"] == ">" ? "selected" : "" }}>{{ trans("laravel-crud::view.greater-than") }}</option>
                                <option value=">=" {{ @$filter["' . $field->name . '-options"] == ">=" ? "selected" : "" }}>{{ trans("laravel-crud::view.greater-equal") }}</option>
                                <option value="between" {{ @$filter["' . $field->name . '-options"] == "between" ? "selected" : "" }}>{{ trans("laravel-crud::view.between-values") }}</option>
                            </select>
                        </label>
                        <div id="' . $field->name . '-options-1">
                            {{ Form::'. ($field->type == 'time' ? 'time' : 'date') . '("' . $field->name . '", @$filter["' . $field->name .'"], ["class" => "form-control", "id" => "' . $field->name . '", "placeholder" => "' . Str::title(str_replace('_', ' ', $field->name)) . '"' . ( $field->size ? ', "maxlength" => "' . $field->size . '"' : '' ) . ']) }}
                        </div>
                        <div id="' . $field->name . '-options-2" style="display:none;">
                            <div class="row">
                                <div class="col-xs-6">
                                    {{ Form::'. ($field->type == 'time' ? 'time' : 'date') . '("' . $field->name . '-1", @$filter["' . $field->name .'-1"], ["class" => "form-control", "id" => "' . $field->name . '-1", "placeholder" => trans("laravel-crud::view.value-1")' . ( $field->size ? ', "maxlength" => "' . $field->size . '"' : '' ) . ', "disabled"]) }}
                                </div>
                                <div class="col-xs-6">
                                    {{ Form::'. ($field->type == 'time' ? 'time' : 'date') . '("' . $field->name . '-2", @$filter["' . $field->name .'-2"], ["class" => "form-control", "id" => "' . $field->name . '-2", "placeholder" => trans("laravel-crud::view.value-2")' . ( $field->size ? ', "maxlength" => "' . $field->size . '"' : '' ) . ', "disabled"]) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>' . "\n";

                    } elseif ($field->type === 'int') {
                        $fields .= '
                <div class="col-xs-12 col-12 col-sm-6 col-md-4">
                    <div class="form-group mb-3">
                        <label for="' . $field->name . '" class="control-label">
                            ' . $prepareTitle($field->name) . '
                            <select name="' . $field->name . '-options" id="' . $field->name . '-options" class="pull-right select-options" data-field="' . $field->name . '">
                                <option value="=" {{ @$filter["' . $field->name . '-options"] == "=" ? "selected" : "" }}>{{ trans("laravel-crud::view.equal") }}</option>
                                <option value="<" {{ @$filter["' . $field->name . '-options"] == "<" ? "selected" : "" }}>{{ trans("laravel-crud::view.less-than") }}</option>
                                <option value="<=" {{ @$filter["' . $field->name . '-options"] == "<=" ? "selected" : "" }}>{{ trans("laravel-crud::view.less-equal") }}</option>
                                <option value=">" {{ @$filter["' . $field->name . '-options"] == ">" ? "selected" : "" }}>{{ trans("laravel-crud::view.greater-than") }}</option>
                                <option value=">=" {{ @$filter["' . $field->name . '-options"] == ">=" ? "selected" : "" }}>{{ trans("laravel-crud::view.greater-equal") }}</option>
                                <option value="between" {{ @$filter["' . $field->name . '-options"] == "between" ? "selected" : "" }}>{{ trans("laravel-crud::view.between-values") }}</option>
                            </select>
                        </label>
                        <div id="' . $field->name . '-options-1">
                            {{ Form::number("' . $field->name . '", @$filter["' . $field->name .'"], ["class" => "form-control", "id" => "' . $field->name . '", "placeholder" => "' . Str::title(str_replace('_', ' ', $field->name)) . '"' . ( $field->size ? ', "maxlength" => "' . $field->size . '"' : '' ) . ']) }}
                        </div>
                        <div id="' . $field->name . '-options-2" style="display:none;">
                            <div class="row">
                                <div class="col-xs-6">
                                    {{ Form::number("' . $field->name . '-1", @$filter["' . $field->name .'-1"], ["class" => "form-control", "id" => "' . $field->name . '-1", "placeholder" => trans("laravel-crud::view.value-1")' . ( $field->size ? ', "maxlength" => "' . $field->size . '"' : '' ) . ', "disabled"]) }}
                                </div>
                                <div class="col-xs-6">
                                    {{ Form::number("' . $field->name . '-2", @$filter["' . $field->name .'-2"], ["class" => "form-control", "id" => "' . $field->name . '-2", "placeholder" => trans("laravel-crud::view.value-2")' . ( $field->size ? ', "maxlength" => "' . $field->size . '"' : '' ) . ', "disabled"]) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>' . "\n";

                    } elseif ($field->name === 'email') {
                        $fields .= '
                <div class="col-xs-12 col-12 col-sm-6 col-md-4">
                    <div class="form-group mb-3">
                        <label for="' . $field->name . '" class="control-label">
                            ' . $prepareTitle($field->name) . '
                        </label>
                        {{ Form::email("' . $field->name . '", @$filter["' . $field->name .'"], ["class" => "form-control", "placeholder" => trans(\'laravel-crud::view.email\')' . ( $field->size ? ', "maxlength" => "' . $field->size . '"' : '' ) . ']) }}
                    </div>
                </div>' . "\n";

                    } elseif (in_array($field->name, ['name', 'title', 'user', 'username', 'login'])) {
                        $fields .= '
                <div class="col-xs-12 col-12 col-sm-6 col-md-4">
                    <div class="form-group mb-3">
                        <label for="' . $field->name . '" class="control-label">
                            ' . $prepareTitle($field->name) . '
                        </label>
                        {{ Form::text("' . $field->name . '", @$filter["' . $field->name .'"], ["class" => "form-control", "placeholder" => trans(\'laravel-crud::view.' . $field->name . '\')' . ( $field->size ? ', "maxlength" => "' . $field->size . '"' : '' ) . ']) }}
                    </div>
                </div>' . "\n";
                    } else {
                        $fields .= '
                <div class="col-xs-12 col-12 col-sm-6 col-md-4">
                    <div class="form-group mb-3">
                        <label for="' . $field->name . '" class="control-label">
                            ' . $prepareTitle($field->name) . '
                        </label>
                        {{ Form::text("' . $field->name . '", @$filter["' . $field->name .'"], ["class" => "form-control", "placeholder" => "' . Str::title(str_replace('_', ' ', $field->name)) . '"' . ( $field->size ? ', "maxlength" => "' . $field->size . '"' : '' ) . ']) }}
                    </div>
                </div>' . "\n";
                    }
                }
            }

            return $fields;
        };

        // TITLE FIELDS
        $prepareTitleFields = function () use ($objTable) {
            $fields = null;

            foreach ($objTable->fields as $field) {
                if ($field->name != 'password') {

                    if ($field->fk) {
                        foreach ($this->tables as $t) {
                            if ($t->name === $field->fk) {
                                $fields .= '
                            <th>' . Str::title(str_replace('_',' ',$t->snakeSingular)) . '</th>';
                            }
                        }
                    } elseif (in_array($field->name, ['name', 'title', 'user', 'username', 'login', 'email', 'created_at', 'updated_at', 'deleted_at'])) {
                        $fields .= '
                            <th>{{ trans(\'laravel-crud::view.' . str_replace('_', '-', $field->name) . '\') }}</th>';
                    } else {
                        $fields .= '
                            <th>' . Str::title(str_replace('_', ' ', $field->name)) . '</th>';
                    }
                }
            }

            return $fields;
        };

        // VALUE FIELDS
        $prepareValueFields = function () use ($objTable) {
            $fields = null;

            foreach ($objTable->fields as $field) {
                if ($field->name != 'password') {

                    if ($field->fk) {
                        foreach ($this->tables as $t) {
                            if ($t->name === $field->fk) {
                                $tmpFields = null;

                                foreach ($t->fields as $f) {
                                    if ($f->display) {
                                        $tmpFields = "
                                <td>
                                    @if ($" . $objTable->singular . "->" . $t->singular . ")
                                        {{ link_to_action('" . ucwords($t->plural) . "Controller@show', $" . $objTable->singular . "->" . $t->singular . '->' . $f->name . ", [$" . $objTable->singular . "->" . $field->name . "], ['class' => 'text-primary']) }}
                                    @endif
                                </td>";
                                        break;
                                    }
                                }

                                if ($tmpFields) {
                                    $fields .= $tmpFields;
                                } else {
                                    $fields .= "
                                <td>
                                    @if ($" . $objTable->singular . "->" . $t->singular .")
                                        {{ link_to_action('" . ucwords($t->plural) . "Controller@show', $" . $objTable->singular . "->" . $field->name . ", [$" . $objTable->singular . "->" . $field->name . "], ['class' => 'text-primary']) }}
                                    @endif
                                </td>";
                                }

                                break;
                            }
                        }
                    } elseif ($field->type === 'date') {
                        $fields .= '
                                <td>{{ blank($' . $objTable->singular . '->' . $field->name . ') ? null : @$' . $objTable->singular . '->' . $field->name . "->format('d/m/Y') }}</td>";
                    } elseif (in_array($field->type, ['datetime', 'timestamp'])) {
                        $fields .= '
                                <td>{{ blank($' . $objTable->singular . '->' . $field->name . ') ? null : @$' . $objTable->singular . '->' . $field->name . "->format('d/m/Y H:i:s') }}</td>";
                    } else {
                        $fields .= '
                                <td>{{ $' . $objTable->singular . '->' . $field->name . ' }}</td>';
                    }
                }
            }

            return $fields;
        };


        // FORM FIELDS
        $prepareFormFields = function () use ($objTable) {
            $fields = null;

            foreach ($objTable->fields as $field) {
                if (! in_array($field->name, ['id',  'updated_at', 'created_at', 'deleted_at', 'remember_token'])) {

                    if ($field->fk) {
                        foreach ($this->tables as $t) {
                            if ($t->name === $field->fk) {
                                $fields .= '
                    <div class="col-xs-12 col-12 mb-3">
                        {{ Form::label("' . $field->name . '", "' . Str::title(str_replace('_',' ',$t->singular)) . '", ["class" => "control-label"]) }}
                        {{ Form::select("' . $field->name . '", $plucks["' . $t->plural . '"], @$' . $objTable->singular . '->' . $field->name .', ["class" => "form-control", "placeholder" => ""' . ( $field->required ? ', "required"' : '' ) . ']) }}
                    </div>' . "\n";

                            }
                        }
                    } elseif ($field->type === 'date') {
                        $fields .= '
                    <div class="col-xs-12 col-12 mb-3">
                        {{ Form::label("' . $field->name . '", "' . Str::title(str_replace('_', ' ', $field->name)) . '", ["class" => "control-label"]) }}
                        {{ Form::date("' . $field->name . '", @$' . $objTable->singular . '->' . $field->name .', ["class" => "form-control", "placeholder" => "' . Str::title(str_replace('_', ' ', $field->name)) . '"' . ( $field->size ? ', "maxlength" => "' . $field->size . '"' : '' ) . ( $field->required ? ', "required"' : '' ) . ']) }}
                    </div>' . "\n";

                    } elseif (in_array($field->type, ['datetime', 'timestamp'])) {
                        $fields .= '
                    <div class="col-xs-12 col-12 mb-3">
                        {{ Form::label("' . $field->name . '", "' . Str::title(str_replace('_', ' ', $field->name)) . '", ["class" => "control-label"]) }}
                        {{ Form::datetime("' . $field->name . '", @$' . $objTable->singular . '->' . $field->name .', ["class" => "form-control", "placeholder" => "' . Str::title(str_replace('_', ' ', $field->name)) . '"' . ( $field->size ? ', "maxlength" => "' . $field->size . '"' : '' ) . ( $field->required ? ', "required"' : '' ) . ']) }}
                    </div>' . "\n";

                    } elseif ($field->type === 'int') {
                        $fields .= '
                    <div class="col-xs-12 col-12 mb-3">
                        {{ Form::label("' . $field->name . '", "' . Str::title(str_replace('_', ' ', $field->name)) . '", ["class" => "control-label"]) }}
                        {{ Form::number("' . $field->name . '", @$' . $objTable->singular . '->' . $field->name .', ["class" => "form-control", "placeholder" => "' . Str::title(str_replace('_', ' ', $field->name)) . '"' . ( $field->size ? ', "maxlength" => "' . $field->size . '"' : '' ) . ( $field->required ? ', "required"' : '' ) . ']) }}
                    </div>' . "\n";

                    } elseif ($field->name === 'email') {
                        $fields .= '
                    <div class="col-xs-12 col-12 mb-3">
                        {{ Form::label("' . $field->name . '", trans(\'laravel-crud::view.email\'), ["class" => "control-label"]) }}
                        {{ Form::email("' . $field->name . '", @$' . $objTable->singular . '->' . $field->name .', ["class" => "form-control", "placeholder" => trans(\'laravel-crud::view.email\')' . ( $field->size ? ', "maxlength" => "' . $field->size . '"' : '' ) . ( $field->required ? ', "required"' : '' ) . ']) }}
                    </div>' . "\n";

                    } elseif ($field->name === 'password') {

                        $fields .= '
                    @if (Request::is(\'*/create\'))
                    <div class="col-xs-12 col-12 mb-3">
                        {{ Form::label("' . $field->name . '", trans(\'laravel-crud::view.password\'), ["class" => "control-label"]) }}
                        {{ Form::password("' . $field->name . '", ["class" => "form-control", "placeholder" => trans(\'laravel-crud::view.password\')' . ( $field->size ? ', "maxlength" => "' . $field->size . '"' : '' ) . ( $field->required ? ', "required"' : '' ) . ']) }}
                    </div>
                    @endif' . "\n";

                    } elseif (in_array($field->name, ['name', 'title', 'user', 'username', 'login'])) {
                        $fields .= '
                    <div class="col-xs-12 col-12 mb-3">
                        {{ Form::label("' . $field->name . '", trans(\'laravel-crud::view.' . $field->name . '\'), ["class" => "control-label"]) }}
                        {{ Form::text("' . $field->name . '", @$' . $objTable->singular . '->' . $field->name .', ["class" => "form-control", "placeholder" => trans(\'laravel-crud::view.' . $field->name . '\')' . ( $field->size ? ', "maxlength" => "' . $field->size . '"' : '' ) . ( $field->required ? ', "required"' : '' ) . ']) }}
                    </div>' . "\n";
                    } else {
                        $fields .= '
                    <div class="col-xs-12 col-12 mb-3">
                        {{ Form::label("' . $field->name . '", "' . Str::title(str_replace('_', ' ', $field->name)) . '", ["class" => "control-label"]) }}
                        {{ Form::text("' . $field->name . '", @$' . $objTable->singular . '->' . $field->name .', ["class" => "form-control", "placeholder" => "' . Str::title(str_replace('_', ' ', $field->name)) . '"' . ( $field->size ? ', "maxlength" => "' . $field->size . '"' : '' ) . ( $field->required ? ', "required"' : '' ) . ']) }}
                    </div>' . "\n";
                    }
                }
            }

            return $fields;
        };

        // DISPLAY FIELDS
        $prepareDisplayField = function () use ($objTable) {
           return '$' . $objTable->singular . '->' . $this->getPkDisplay($objTable)[1];
        };

        // SHOW FIELDS
        $prepareShowFields = function () use ($objTable) {
            $fields = null;

            foreach ($objTable->fields as $field) {
                if (! in_array($field->name, ['id', 'password'])) {

                    if ($field->fk) {
                        foreach ($this->tables as $t) {
                            if ($t->name === $field->fk) {
                                $tmpFields = null;

                                foreach ($t->fields as $f) {
                                    if ($f->display) {
                                        $tmpFields = "
                        <tr>
                            <th>" . Str::title(str_replace('_',' ',$t->snakeSingular)) . "</th>
                            <td>
                                @if ($" . $objTable->singular . "->" . $t->singular .")
                                    {{ link_to_action('" . ucwords($t->plural) . "Controller@show', $" . $objTable->singular . "->" . $t->singular . '->' . $f->name . ", [$" . $objTable->singular . "->" . $field->name . "], ['class' => 'text-primary']) }}
                                @endif
                            </td>
                        </tr>";
                                        break;
                                    }
                                }

                                if ($tmpFields) {
                                    $fields .= $tmpFields;
                                } else {
                                    $fields .= "
                        <tr>
                            <th>" . Str::title(str_replace('_',' ',$t->snakeSingular)) . "</th>
                            <td>
                                @if ($" . $objTable->singular . "->" . $t->singular .")
                                    {{ link_to_action('" . ucwords($t->plural) . "Controller@show', $" . $objTable->singular . "->" . $field->name . ", [$" . $objTable->singular . "->" . $field->name . "], ['class' => 'text-primary']) }}
                                @endif
                            </td>
                        </tr>";
                                }

                                break;
                            }
                        }
                    } elseif(in_array($field->name, ['created_at', 'updated_at', 'deleted_at'])) {
                        $fields .= '
                        <tr>
                            <th>{{ trans(\'laravel-crud::view.' . str_replace('_', '-', $field->name) . '\') }}</th>
                            <td>{{ blank($' . $objTable->singular . '->' . $field->name . ') ? null : @$' . $objTable->singular . '->' . $field->name . "->format('d/m/Y H:i:s') }}</td>
                        </tr>";
                    } elseif ($field->type === 'date') {
                        $fields .= '
                        <tr>
                            <th>' . Str::title(str_replace('_', ' ', $field->name)) . '</th>
                            <td>{{ blank($' . $objTable->singular . '->' . $field->name . ') ? null : @$' . $objTable->singular . '->' . $field->name . "->format('d/m/Y') }}</td>
                        </tr>";
                    } elseif (in_array($field->type, ['datetime', 'timestamp'])) {
                        $fields .= '
                        <tr>
                            <th>' . Str::title(str_replace('_', ' ', $field->name)) . '</th>
                            <td>{{ blank($' . $objTable->singular . '->' . $field->name . ') ? null : @$' . $objTable->singular . '->' . $field->name . "->format('d/m/Y H:i:s') }}</td>
                        </tr>";
                    } elseif(in_array($field->name, ['name', 'title', 'user', 'username', 'login', 'email'])) {
                        $fields .= '
                        <tr>
                            <th>{{ trans(\'laravel-crud::view.' . $field->name . '\') }}</th>
                            <td>{{ $' . $objTable->singular . '->' . $field->name . ' }}</td>
                        </tr>';
                    } else {
                        $fields .= '
                        <tr>
                            <th>' . Str::title(str_replace('_', ' ', $field->name)) . '</th>
                            <td>{{ $' . $objTable->singular . '->' . $field->name . ' }}</td>
                        </tr>';
                    }
                }
            }

            return $fields;
        };


        // MARKS TO REPLACE
        $marks = [
            // GERAL
            'table_name' => $objTable->name,
            'plural_uc' => ucwords($objTable->plural),
            'plural' => $objTable->plural,
            'kebab_plural' => Str::kebab($objTable->plural),
            'snake_plural' => $objTable->snakePlural,
            'singular_uc' => ucwords($objTable->singular),
            'singular' => $objTable->singular,
            'snake_singular' => $objTable->snakeSingular,

            // Controller
            'uses' => $prepareUses(),
            'validators' => $prepareValidators(),
            'plucks' => $preparePlucks(),
            // 'filter_plucks' => $prepareFilterPlucks(),
            'filters_set' => $prepareFiltersSet(),
            'filters' => $prepareFilters(),
            'compacts' => $compacts,
            'compacts_c' => $compacts_c,

            // Model
            'namespace' => substr($this->pathModels,0,-1),
            'use_soft_deletes' => $useSoftDeletes,
            'trait_soft_deletes' => $traitSoftDeletes,
            'primary_key' => $primary,
            'auto_increment' => $incrementing,
            'fillable' => $prepareFillable(),
            'hidden' => '',
            'with' => $prepareWith(),
            'dates' => $prepareDates(),
            'belongs_to' => $prepareSubTemplates('belongs'),
            'has_one' => $prepareSubTemplates('one'),
            'has_many' => $prepareSubTemplates('many'),
            'belongs_many' => $prepareSubTemplates('belongsMany'),
            'sync_relationships' => $prepareSubTemplates('syncRelationships'),
            'relationships' => $prepareSubTemplates('relationships'),

            // Index
            'filters_fields' => $prepareFiltersFields(),
            'title_fields' => $prepareTitleFields(),
            'value_fields' => $prepareValueFields(),

            // Form
            'form_fields' => $prepareFormFields(),

            // Show
            'display_field' => $prepareDisplayField(),
            'show_fields' => $prepareShowFields(),


        ];

        return $marks;
    }

    /**
     * [marks description]
     * @return [type] [description]
     */
    public function marks()
    {
        return [
            'controller' => [
                'plural_uc',
                'plural',
                'kebab_plural',
                'singular_uc',
                'singular',
                'uses',
                'validators',
                'plucks',
                // 'filter_plucks',
                'filters_set',
                'filters',
                'compacts',
                'compacts_c',
            ],

            'model' => [
                'table_name',
                'plural_uc',
                'plural',
                'snake_plural',
                'singular_uc',
                'singular',
                'namespace',
                'use_soft_deletes',
                'trait_soft_deletes',
                'primary_key',
                'auto_increment',
                'fillable',
                'hidden',
                'with',
                'dates',
                'belongs_to',
                'has_one',
                'has_many',
                'belongs_many',
                'sync_relationships',
                'relationships',
            ],

            'pivot' => [
                'plural_uc',
                'plural',
                'singular_uc',
                'singular',
                'namespace',
                'primary_key',
                'auto_increment',
                'fillable',
                'hidden',
                'with',
                'dates',
                'belongs_to',
                'has_one',
                'has_many',
                'belongs_many',
            ],

            'belongs' => [
                'singular_uc',
                'singular',
                'use_model',
                'primary_model',
            ],

            'many' => [
                'plural',
                'singular_uc',
                'singular',
                'use_model',
                'primary_model',
                'fk_model',
            ],

            'one' => [
                'singular_uc',
                'singular',
                'use_model',
                'primary_model',
            ],

            'belongsMany' => [
                'plural',
                'singular_uc',
                'singular',
                'use_model',
                'fk_model',
                'relation_table',
            ],

            'syncRelationships' => [
                'plural_uc',
                'plural',
            ],

            'relationships' => [
                'plural',
            ],

            'index.blade' => [
                'plural_uc',
                'plural',
                'kebab_plural',
                'singular_uc',
                'singular',
                'filters_fields',
                'title_fields',
                'value_fields',
                'primary_key',
            ],

            'form.blade' => [
                'plural_uc',
                'plural',
                'kebab_plural',
                'singular_uc',
                'singular',
                'form_fields',
                'primary_key',
            ],

            'show.blade' => [
                'plural_uc',
                'plural',
                'kebab_plural',
                'singular_uc',
                'singular',
                'display_field',
                'show_fields',
            ],

            'routes' => [
                'kebab_plural',
                'plural_uc',
                'plural',
            ],

        ];
    }

    /**
     * [processFile description]
     * @param  string $type [description]
     * @return [type]       [description]
     */
    public function processFile(string $type)
    {
        $this->alert(strtoupper($type) . ' PROCESS');

        if ($type == 'model' && strtoupper($this->option('base-model')) == 'Y') {
            // Make the model object
            $objMod = new \stdClass();
            $objMod->singular = 'model';
            $objMod->arqs = [
                $type => str_replace('{{{namespace}}}',
                                    trim(substr($this->pathModels,0,-1)),
                                    $this->getTemplate('baseModel')),
            ];

            $this->createFile($type, $objMod);
        }

        foreach ($this->tables as $key => $table) {
            if ($table->relationTable === true && $type !== 'pivot') {
                continue;
            }

            if ($table->relationTable !== true && $type === 'pivot') {
                continue;
            }

            if (trim($this->option('table')) !== 'all') {
                $tableKey = $this->option('table');
                if ((int) $tableKey !== (int) $key) {
                    continue;
                }
            }

            $table->arqs = [
                $type => $this->getTemplate($type),
            ];

            foreach ($this->marks()[$type] as $mark){
                $table->arqs[$type] = str_replace('{{{' . $mark . '}}}',
                                                        trim($table->marks[$mark]),
                                                        $table->arqs[$type]);
            }

            //$this->info($table->arqs[$type]);
            $this->createFile($type, $table);
        }
    }

    /**
     * [createFile description]
     *
     * @param  string $type [description]
     * @param  string $arq  [description]
     * @return [type]       [description]
     */
    public function createFile(string $type, $objTable)
    {
        $pathModels = explode('\\',$this->pathModels);
        unset($pathModels[0]);

        try {

            // Paths type
            $paths = $this->webService 
                            ? [
                                'controller' => app_path() . '/Http/Controllers/Api/',
                                'model' => app_path() . '/' . implode('/',$pathModels),
                            ]
                            : [
                                'controller' => app_path() . '/Http/Controllers/',
                                'model' => app_path() . '/' . implode('/',$pathModels),
                                'index.blade' => resource_path() . '/views/' . Str::kebab($objTable->plural) . '/',
                                'form.blade' => resource_path() . '/views/' . Str::kebab($objTable->plural) . '/' ,
                                'show.blade' => resource_path() . '/views/' . Str::kebab($objTable->plural) . '/' ,
                            ];

        } catch (\Exception $e) {
            dd($type, $objTable, $e->getMessage());
            $this->error($e->getMessage());
            exit;
        }

        // Name Arq
        $prepareNameArq = function ($t) use ($objTable) {
            $nameArq = '';

            switch ($t) {
                case 'controller':
                    $nameArq = ucwords($objTable->plural) . 'Controller.php';
                    break;

                case 'model':
                    $nameArq = ucwords($objTable->singular) . '.php';
                    break;

                default:
                    $nameArq = $t . '.php';
            }

            return $nameArq;
        };

        @mkdir($paths[$type]);
        $file = fopen($paths[$type] . $prepareNameArq($type), 'w');
        fwrite($file, $objTable->arqs[$type]);
        fclose($file);
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        // Process Theme
        $this->processOptionTheme();

        // Process Api Client
        $this->processOptionApiClient();

        // Process Web Server
        $this->processOptionWebService();

        // Process Routes
        $this->processOptionRoutes();

        // Process Path Models
        $this->processOptionPathModels();

        // Process Table
        $this->processOptionTable();

        // Process Controller
        $this->processFile('controller');   

        // Process Model
        $this->processFile('model');

        if (!$this->webService) {
            // Process Index
            $this->processFile('index.blade');

            // Process Form
            $this->processFile('form.blade');

            // Process Show
            $this->processFile('show.blade');
        }

        // Process Routes
        $this->processRoutes();


    }
}
