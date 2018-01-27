<?php

namespace SantosAlan\LaravelCrud\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Pluralizer;

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
    protected $signature = 'make:crud {--t=} {--table=} {--pm=} {--path-models=}  {--r=} {--routes=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a CRUD';

    /**
     * The path of Models
     * 
     * @var string
     */
    private $pathModels = 'App\\';

    /**
     * [$routes description]
     * @var boolean
     */
    private $routes = true;

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

        // Verify option TABLE 
        if (in_array(strtoupper(trim($this->option('r'))), ['N','NO','FALSE']) || in_array(strtoupper(trim($this->option('routes'))), ['N','NO','FALSE'])) {
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


            if (trim($this->option('t')) === 'all' || trim($this->option('table')) === 'all') {

                foreach ($this->tables as $table) {
                    $m = [
                        'plural_uc' => ucwords($table->plural),
                        'plural' => $table->plural,
                    ];

                    $temp = $template;
                    
                    foreach ($this->marks()['routes'] as $mark){
                        $temp = str_replace('{{{' . $mark . '}}}', trim($m[$mark]), $temp);
                    }
                    
                    $routes .= $temp;
                }
                
            
            } elseif (trim($this->option('t')) !== '' || trim($this->option('table')) !== '') {
               
                $tableKey = trim($this->option('t')) !== '' ? $this->option('t') : $this->option('table');
                    
                $table = $this->tables[$tableKey]


                $m = [
                    'plural_uc' => ucwords($table->plural),
                    'plural' => $table->plural,
                ];

                $temp = $template;
                
                foreach ($this->marks()['routes'] as $mark){
                    $temp = str_replace('{{{' . $mark . '}}}', trim($m[$mark]), $temp);
                }
                
                $routes = $temp;
                
            }   

            $fileWeb = fopen(app_base() . 'routes/web.php', 'a+');
            fwrite($fileWeb, $routes);
            fclose($fileWeb);
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
        if (trim($this->option('pm')) !== '' || trim($this->option('path-models')) !== '') {
            $this->pathModels = trim($this->option('pm')) !== '' ? str_finish($this->option('pm'), '\\') : str_finish($this->option('path-models'), '\\');
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

        foreach ($tables as $t) {
            if (in_array($t->Tables_in_portal_rh, ['migrations', 'password_resets'])) {
                continue;
            }

            // Make the table object
            $objTab = new \stdClass();
            $objTab->name = $t->Tables_in_portal_rh;
            $objTab->singular = Pluralizer::singular($objTab->name);
            $objTab->plural = Pluralizer::plural($objTab->name);
            $objTab->fieldDisplay = false;
            $objTab->fk = 'id_' . $objTab->singular;
            $objTab->fields = [];
            $objTab->belongsTo = [];
            $objTab->hasMany = [];
            $objTab->hasOne = [];
            $objTab->marks = [];
            $objTab->arqs = [];




            array_push($this->tables, $objTab);
        }

        //dump($this->tables);


        // Verify option TABLE 
        if (trim($this->option('t')) === '' && trim($this->option('table')) === '') {
            
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
                if ($table->name === $t->name) {
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
        /*
        if (trim($this->option('t')) === 'all' || trim($this->option('table')) === 'all') {
            dump($this->tables);
        } elseif (trim($this->option('t')) !== '' || trim($this->option('table')) !== '') {
            $tableKey = trim($this->option('t')) !== '' ? $this->option('t') : $this->option('table');
            dump($this->tables[$tableKey]);
        }
        */

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

        $fields = DB::select('DESC ' . $table->name);

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
        preg_match('/id_([a-zA-Z_0-9]+)/', $field->Field, $fk);


        $objField->name = $field->Field;
        $objField->type = $type[0];
        $objField->size = isset($size[0]) ? $size[0] : null;
        $objField->unsigned = strpos($field->Type, 'unsigned') !== false ? true : false;
        $objField->required = $field->Null === 'NO' ? true : false;
        $objField->pk = $field->Key === 'PRI' ? true : false;
        $objField->fk = !empty($fk) ? Pluralizer::plural($fk[1]) : false;
        $objField->display = false;
        $objField->unique = $field->Key === 'UNI' ? true : false; 
        $objField->default = $field->Default;
        $objField->autoIncrement = strpos($field->Extra, 'auto_increment') !== false ? true : false;
        $objField->validator = $this->generateValidator($objField, $table);


        $displays = [
                        'name',
                        $table->singular . '_name',
                        'name_' . $table->singular,
                        'title',
                        $table->singular . '_title',
                        'title_' . $table->singular,
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
                    $type = 'string';
                    break;

                default:
                    $type = $objField->type;
            }

            return $type;
        };
        
        $validator = $funcType();
        $validator .= $objField->size && in_array($objField->type, ['char','varchar','text']) 
                        ? '|max:' . $objField->size 
                        : '';
        $validator .= strpos($objField->name, 'email') !== false ? '|email' : '';
        $validator .= $objField->unique ? '|unique:' . $table->name : '';
        $validator .= $objField->required ? '|required' : '';

        return $validator;
        
    }

    /**
     * getTemplate
     *
     * @param  [type] $type [description]
     * @return string      [description]
     */
    public function getTemplate($type)
    {        
        $template = file_get_contents(__DIR__ . '/stubs/' . $type . '.stub');

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
            $uses = 'use ' . $this->pathModels . ucwords($objTable->singular) . ";\n";
            foreach ($objTable->belongsTo as $b) {
                $uses .= 'use ' . $this->pathModels . ucwords(Pluralizer::singular($b)) . ";\n";
            }

            return $uses;
        };

        // VALIDATORS
        $prepareValidators = function () use ($objTable) {
            $validators = '';
            foreach ($objTable->fields as $f) {
                if (in_array($f->name, ['id', 'created_at', 'updated_at', 'remember_token'])){
                    continue;
                }

                if (empty($validators)) {
                    $validators = "'".$f->name."' => '" . $f->validator . "',\n";
                } else {
                    $validators .= "            '".$f->name."' => '" . $f->validator . "',\n";
                }
            }

            return $validators;
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
                        $plucks = '$' . $t->plural . ' = ' 
                                . ucwords($t->singular) . "::pluck('" . $display . "', '" . $pk . "')" 
                                . ";\n";
                    } else {
                        $plucks .= '        $' . $t->plural . ' = ' 
                                . ucwords($t->singular) . "::pluck('" . $display . "', '" . $pk . "')" 
                                . ";\n";
                    }
                }
            }

            return $plucks;
        };


        // COMPACTS
        $prepareCompacts = function () use ($objTable) {
            $compacts = '';
            foreach ($objTable->belongsTo as $b) {
                $compacts .= ", '" . $b . "'";
            }

            return $compacts;
        };
        $compacts = $prepareCompacts();
        $compacts_c = substr($compacts, 1);

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

        // FILLABLES
        $prepareFillable = function () use ($objTable) {
            $fillable = null;

            foreach ($objTable->fields as $f) {
                if (in_array(strtolower($f->name), ['id', 'created_at', 'updated_at'])) {
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

            $attr = $type === 'belongs' 
                    ? $objTable->belongsTo
                    : ($type === 'many' 
                        ? $objTable->hasMany
                        : $objTable->hasOne);

            foreach ($attr as $item) {
                foreach ($this->tables as $t) {
                    if ($t->name !== $item) {
                        continue;
                    }

                    $m = [
                        'singular_uc' => ucwords($t->singular),
                        'singular' => $t->singular,
                        'use_model' => $this->pathModels . ucwords($t->singular),
                        'primary_model' => $prepPrimary($t),
                    ];

                    $temp = $this->getTemplate($type);

                    foreach ($this->marks()[$type] as $mark){
                        $temp = str_replace('{{{' . $mark . '}}}', trim($m[$mark]), $temp);
                    }

                    $subTemp .= $temp;
                }

            }

            return $subTemp;
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
                            <th>' . title_case(str_replace('_',' ',$t->singular)) . '</th>';
                            }
                        }
                    } else {
                        $fields .= '
                            <th>' . title_case(str_replace('_', ' ', $field->name)) . '</th>';
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
                                foreach ($t->fields as $f) {
                                    if ($f->display) {
                                        $fields .= '
                                <td>{{ $' . $objTable->singular . '->' . $t->singular . '->' . $f->name . ' }}</td>';
                                    }
                                }       
                            }
                        }
                    } elseif ($field->type === 'date') {
                        $fields .= '
                                <td>{{ $' . $objTable->singular . '->' . $field->name . "->format('d/m/Y') }}</td>";
                    } elseif (in_array($field->type, ['datetime', 'timestamp'])) {
                        $fields .= '
                                <td>{{ $' . $objTable->singular . '->' . $field->name . "->format('d/m/Y H:i:s') }}</td>";
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
                if (! in_array($field->name, ['id',  'updated_at', 'created_at', 'remember_token'])) {

                    if ($field->fk) {
                        foreach ($this->tables as $t) {
                            if ($t->name === $field->fk) {
                                $fields .= '
                    <div class="col-xs-12"> 
                        {{ Form::label("' . $field->name . '", "' . title_case(str_replace('_',' ',$t->singular)) . '", ["class" => "control-label"]) }}
                        {{ Form::select("' . $field->name . '", $' . $t->plural . ', @$' . $objTable->singular . '->' . $field->name .', ["class" => "form-control", "placeholder" => ""' . ( $field->required ? ', "required"' : '' ) . ']) }}
                    </div>' . "\n";
                                
                            }
                        }
                    } elseif ($field->type === 'date') {
                        $fields .= '
                    <div class="col-xs-12"> 
                        {{ Form::label("' . $field->name . '", "' . title_case(str_replace('_', ' ', $field->name)) . '", ["class" => "control-label"]) }}
                        {{ Form::date("' . $field->name . '", @$' . $objTable->singular . '->' . $field->name .', ["class" => "form-control", "placeholder" => ""' . ( $field->size ? ', "maxlength" => "' . $field->size . '"' : '' ) . ( $field->required ? ', "required"' : '' ) . ']) }}
                    </div>' . "\n";

                    } elseif (in_array($field->type, ['datetime', 'timestamp'])) {
                        $fields .= '
                    <div class="col-xs-12"> 
                        {{ Form::label("' . $field->name . '", "' . title_case(str_replace('_', ' ', $field->name)) . '", ["class" => "control-label"]) }}
                        {{ Form::datetime("' . $field->name . '", @$' . $objTable->singular . '->' . $field->name .', ["class" => "form-control", "placeholder" => ""' . ( $field->size ? ', "maxlength" => "' . $field->size . '"' : '' ) . ( $field->required ? ', "required"' : '' ) . ']) }}
                    </div>' . "\n";

                    } elseif ($field->type === 'int') {
                        $fields .= '
                    <div class="col-xs-12"> 
                        {{ Form::label("' . $field->name . '", "' . title_case(str_replace('_', ' ', $field->name)) . '", ["class" => "control-label"]) }}
                        {{ Form::number("' . $field->name . '", @$' . $objTable->singular . '->' . $field->name .', ["class" => "form-control", "placeholder" => ""' . ( $field->size ? ', "maxlength" => "' . $field->size . '"' : '' ) . ( $field->required ? ', "required"' : '' ) . ']) }}
                    </div>' . "\n";

                    } elseif ($field->name === 'email') {
                        $fields .= '
                    <div class="col-xs-12"> 
                        {{ Form::label("' . $field->name . '", "' . title_case(str_replace('_', ' ', $field->name)) . '", ["class" => "control-label"]) }}
                        {{ Form::email("' . $field->name . '", @$' . $objTable->singular . '->' . $field->name .', ["class" => "form-control", "placeholder" => "' . title_case(str_replace('_', ' ', $field->name)) . '"' . ( $field->size ? ', "maxlength" => "' . $field->size . '"' : '' ) . ( $field->required ? ', "required"' : '' ) . ']) }}
                    </div>' . "\n";

                } elseif ($field->name === 'password') {
                        $fields .= '
                    <div class="col-xs-12"> 
                        {{ Form::label("' . $field->name . '", "' . title_case(str_replace('_', ' ', $field->name)) . '", ["class" => "control-label"]) }}
                        {{ Form::password("' . $field->name . '", @$' . $objTable->singular . '->' . $field->name .', ["class" => "form-control", "placeholder" => ""' . ( $field->size ? ', "maxlength" => "' . $field->size . '"' : '' ) . ( $field->required ? ', "required"' : '' ) . ']) }}
                    </div>' . "\n";

                    } else {
                        $fields .= '
                    <div class="col-xs-12"> 
                        {{ Form::label("' . $field->name . '", "' . title_case(str_replace('_', ' ', $field->name)) . '", ["class" => "control-label"]) }}
                        {{ Form::text("' . $field->name . '", @$' . $objTable->singular . '->' . $field->name .', ["class" => "form-control", "placeholder" => "' . title_case(str_replace('_', ' ', $field->name)) . '"' . ( $field->size ? ', "maxlength" => "' . $field->size . '"' : '' ) . ( $field->required ? ', "required"' : '' ) . ']) }}
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
                                foreach ($t->fields as $f) {
                                    if ($f->display) {
                                        $fields .= '
                        <tr>
                            <th>' . title_case(str_replace('_',' ',$t->singular)) . '</th>
                            <td>{{ $' . $objTable->singular . '->' . $t->singular . '->' . $f->name . ' }}</td>
                        </tr>';
                                    }
                                }       
                            }
                        }
                    } elseif ($field->type === 'date') {
                        $fields .= '
                        <tr>
                            <th>' . title_case(str_replace('_', ' ', $field->name)) . '</th>
                            <td>{{ $' . $objTable->singular . '->' . $field->name . "->format('d/m/Y') }}</td>
                        </tr>";
                    } elseif (in_array($field->type, ['datetime', 'timestamp'])) {
                        $fields .= '
                        <tr>
                            <th>' . title_case(str_replace('_', ' ', $field->name)) . '</th>
                            <td>{{ $' . $objTable->singular . '->' . $field->name . "->format('d/m/Y H:i:s') }}</td>
                        </tr>";
                    } else {
                        $fields .= '
                        <tr>
                            <th>' . title_case(str_replace('_', ' ', $field->name)) . '</th>
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
            'plural_uc' => ucwords($objTable->plural),
            'plural' => $objTable->plural,
            'singular_uc' => ucwords($objTable->singular),
            'singular' => $objTable->singular,

            // Controller
            'uses' => $prepareUses(),
            'validators' => $prepareValidators(),
            'plucks' => $preparePlucks(),
            'compacts' => $compacts,
            'compacts_c' => $compacts_c,

            // Model
            'namespace' => substr($this->pathModels,0,-1),
            'primary_key' => $primary,
            'auto_increment' => $incrementing,
            'fillable' => $prepareFillable(),
            'hidden' => '',
            'with' => $prepareWith(),
            'dates' => $prepareDates(),
            'belongs_to' => $prepareSubTemplates('belongs'),
            'has_many' => $prepareSubTemplates('many'),
            'has_one' => $prepareSubTemplates('one'),

            // Index
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
                'singular_uc',
                'singular',
                'uses',
                'validators',
                'plucks',
                'compacts',
                'compacts_c',
            ],
            
            'model' => [
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
                'has_many',
                'has_one',
            ],
            
            'belongs' => [
                'singular_uc',
                'singular',
                'use_model',
                'primary_model',
            ],

            'many' => [
                'singular_uc',
                'singular',
                'use_model',
                'primary_model',
            ],

            'one' => [
                'singular_uc',
                'singular',
                'use_model',
                'primary_model',
            ],

            'index.blade' => [
                'plural_uc',
                'plural',
                'singular_uc',
                'singular',
                'title_fields',
                'value_fields',
                'primary_key',
            ],
            
            'form.blade' => [
                'plural_uc',
                'plural',
                'singular_uc',
                'singular',
                'form_fields',
                'primary_key',
            ],
           
            'show.blade' => [
                'plural_uc',
                'plural',
                'singular_uc',
                'singular',
                'display_field',
                'show_fields',
            ],

            'routes' => [
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
        foreach ($this->tables as $key => $table) {

            if (trim($this->option('t')) !== 'all' && trim($this->option('table')) !== 'all') {
                $tableKey = trim($this->option('t')) !== '' ? $this->option('t') : $this->option('table');
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

        // Paths type
        $paths = [
            'controller' => app_path() . '/Http/Controllers/',
            'model' => app_path() . '/' . implode('/',$pathModels),
            'index.blade' => resource_path() . '/views/' . $objTable->plural . '/',
            'form.blade' => resource_path() . '/views/' . $objTable->plural . '/' ,
            'show.blade' => resource_path() . '/views/' . $objTable->plural . '/' ,
        ];

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

        // Process Routes
        $this->processOptionRoutes();

        // Process Path Models
        $this->processOptionPathModels();

        // Process TABLES
        $this->processOptionTable();
        
        // Process File
        $this->processFile('controller');

        // Process File
        $this->processFile('model');

        // Process Show
        $this->processFile('index.blade');

        // Process Form
        $this->processFile('form.blade');

        // Process Show
        $this->processFile('show.blade');

        // Process Routes
        $this->processRoutes();

        
    }
}
