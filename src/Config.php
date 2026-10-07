<?php

namespace Pachel\TableAdmin\Models;


use Pachel\dbClass;
use pachel\TableAdmin;
use Pachel\TableAdmin\Traits\propertyFromArray;
use function pachel\error;


class Config
{
    /**
     * @var ConfigData $_config
     */
    private $_config;
    /**
     * @var dbClass $_db
     */
    private $_db;
    /**
     * @var Buttons $Buttons
     */
    public $Buttons;
    /**
     * @var Fields $Fields
     */
    public $Fields;
    /**
     * @var columns $Cols
     */
    public $Cols;
    /**
     * @var SqlQuery $SqlQuery
     */
    public $SqlQuery;
    /**
     * @var
     */
    public $_get;
    private $_variables = [];
    /**
     * @var Search $Search
     */

    public $Search;

    private $_sidName;
    private $_hasUploader = false;
    public function setUploader($uploader)
    {
        $this->_hasUploader = (bool)$uploader;
    }
    public function hasUploader()
    {
        return $this->_hasUploader;
    }
    public function __construct($file, $db)
    {
        $this->_db = $db;
        if (!is_null($file)) {
            $this->addConfigFile($file);
        }
        $this->Buttons = new Buttons($this->_db, $this);
        $this->Cols = new columns($this);


        $this->Fields = new Fields($this->_db, $this);
        $this->_setBaseUrl();
        //$this->_sidName = ;
        $this->_get = new get();
    }
    public function getSidName()
    {
        return md5($this->getUrl());
    }
    
    public function getSearch()
    {
        return $this->_config->search;
    }
    public function initSearch()
    {
        if(isset($this->_config->search) && !empty($this->_config->search)) {
            $this->Search = new Search($this->_db, $this);
        }
    }

    public function isAjax()
    {
        return (isset($this->_config->ajax) && $this->_config->ajax ? true : false);
    }

    public function initSqlQueries()
    {
        $this->SqlQuery = new SqlQuery($this);
    }


    private function _setBaseUrl()
    {
        //$this->_config->baseUrl = $_SERVER["REQUEST_SCHEME"] . "://" . $_SERVER["SERVER_NAME"] . (isset($_SERVER["REDIRECT_URL"])?$_SERVER["REDIRECT_URL"]:"");
        $base = $this->_getBaseUrl();
        $path = '/' . ltrim($this->getUrl(), '/');

        $this->_config->baseUrl = $base . $path;

        /*
        if($_SERVER["HTTP_HOST"] == "localhost") {
            //$this->_config->url_full = str_replace("index.php", "", $this->_config->url_full) . $this->_config->url;
            $this->_config->BaseUrl = $this->_setFullUrl();
        }*/
    }

    private function _getBaseUrl(bool $withProtocolAndHost = true): string
    {
        // 1. Protokoll meghatározása (http vagy https, figyelembe véve a proxykat is)
        $isHttps = (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        );
        $protocol = $isHttps ? 'https://' : 'http://';

        // 2. Host (domain vagy localhost + port, ha van)
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        // 3. Az almappa útvonalának kiszámítása a belépési pont (pl. index.php) alapján
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = str_replace('\\', '/', dirname($scriptName));

        // Levágjuk a felesleges lezáró perjelet, ha van (kivéve ha sima "/" lenne)
        $basePath = rtrim($basePath, '/');

        if (!$withProtocolAndHost) {
            return $basePath; // Csak a relatív útvonal: pl. "/ksjdalk"
        }

        return $protocol . $host . $basePath;
    }

    public function getBaseUrl()
    {
        $base = $this->_getBaseUrl();
        $path = '/' . ltrim($this->getUrl(), '/');
        return $base . $path;
        //return $this->_config->baseUrl;
    }

    private static $instances;

    public static function instance(...$args)
    {
        $class = static::class;
        if (!isset(self::$instances[$class])) {
            // 1. Példányosítás
            $obj = !empty($args)
                ? (new \ReflectionClass($class))->newInstanceArgs($args)
                : new static();
            self::$instances[$class] = $obj;
        }
        return self::$instances[$class];
    }

    public function getTables()
    {
        return $this->_config->tables;
    }

    public function getLast()
    {
        return $this->_config->last;
    }

    public function getWhere()
    {
        return $this->_config->where;
    }

    /**
     * @return columnModel[]
     */
    public function getCols()
    {
        $ret = [];
        foreach ($this->_config->cols as $index => $col) {
            $ret[] = $col;
        }
        return $ret;
    }

    public function getUrl()
    {
        return $this->_config->url??"";
    }

    public function isEditable()
    {
        if (isset($this->_config->form) && !empty($this->_config->form))
            return true;

        return false;
    }

    /**
     * @return bool
     */
    public function hasSearch()
    {
        return (!is_null($this->Search)?true:false);
    }

    public function addConfigFile($file)
    {
        $this->_config = new ConfigData($file);
    }

    public function hasForm()
    {
        if (isset($this->_config->form)) {
            return true;
        }
        return false;
    }

    /**
     * @return Field[]
     */
    public function formGetEditableFields()
    {
        $list = $this->formGetFields();
        $ret = [];
        foreach ($list as $field) {
            if (!$field->readonly) {
                $ret[] = $field;
            }
        }
        return $ret;
    }

    public function loadFields()
    {

        $list = [];
        //file_put_contents(__DIR__."/../tmp/log.log",print_r($this->_get,true)."\n",FILE_APPEND);
        if (!$this->Fields->isEmpty()) {
            return;
        }

        foreach ($this->_config->form->cols as $index => $field) {
            if (is_array($field)) {
                foreach ($field as $value) {
                    $value->__row_number = $index;
                    $this->Fields->add($value);
                    //$list[] = new Field($value);
                }
            } else {
                $field->__row_number = $index;
                $this->Fields->add($field);
                //$list[] = new Field($field);
            }
        }

    }

    /**
     * @return Field[]
     */
    public function formGetFields()
    {
        $list = [];
        foreach ($this->_config->form->cols as $index => $field) {
            if (is_array($field)) {
                foreach ($field as $value) {
                    $value->__row_number = $index;
                    $list[] = new Field($value);
                }
            } else {
                $field->__row_number = $index;
                $list[] = new Field($field);
            }
        }
        return $list;

    }

    public function formGetTable()
    {
        if (!$this->hasForm()) {
            return null;
        }
        return $this->_config->form->table;
    }

    public function formGetId()
    {
        if (!$this->hasForm()) {
            return null;
        }
        return $this->_config->form->id;
    }

    public function getId()
    {
        return $this->_config->id;
    }

    public function addVariable($name, $value)
    {
        $this->_config->variables->{$name} = $value;
    }

    /**
     * Kicseréli a szövegben az összes változót, arra ami a konfigban definiálva lett
     * @param $string
     * @return string
     */
    public function replaceVariables($string)
    {

        if (isset($this->_config->variables) && !empty($this->_config->variables)) {
            foreach ($this->_config->variables as $name => $value) {
                $string = str_replace("{" . $name . "}", $value, $string);
            }
        }
        if (preg_match_all("/\{config\.(.+?)\}/i", $string, $matches)) {
            foreach ($matches[1] as $index => $name) {
                if (!isset($this->_config->{$name})) {
                    continue;
                }
                $search[] = $matches[0][$index];
                $replace[] = $this->_config->{$name};
            }
            if (!empty($replace)) {
                $string = str_replace($search, $replace, $string);
            }
        }
        return $string;
    }

    public function getVariable($name = null)
    {
        if(is_null($name)){
            return $this->_config->variables;
        }
        return $this->_config->variables->{$name} ?? null;
    }

    public function getDatatables()
    {
        return (isset($this->_config->datatables) && !empty($this->_config->datatables) ? $this->_config->datatables : null);
    }

    public function appendConfig($config, $overwrite = true)
    {
        if (!is_array($config)) {
            throw new \Exception(error(4));
        }
        $keys = array_keys($config);

        if (is_array($keys)) {
            foreach ($keys as $key) {
                if ($overwrite) {
                    $this->_config->{$key} = $config[$key];
                } else {
                    $this->_config->{$key} .= $config[$key];
                }
            }
        }
    }

    public function getButtonTemplate()
    {
        return $this->_config->buttonTemplate;
    }
    public function getClasses()
    {
        return $this->_config->classes;
    }
}

/**
 * @property string $baseUrl
 * @property string $url
 * @property string[]|string $tables
 * @property bool $buttonTemplate
 * @property string $last
 * @property bool $ajax
 * @property string $classes
 * @property bool $datatables
 * @property string $where
 * @property string $id id's name
 * @property bool $addButton
 * @property string $form
 * @property ConfigDataCol[] $cols
 * @property array $variables
 * @property array $search
 *
 */
class ConfigData extends \stdClass
{
    private $_defaults = [
        "id" => "id",
        "last" => null,
        "form" => null,
        "ajax" => false,
        "where" => null,
        "classes" => "table table-bordered table-striped",
        "buttonTemplate" => " [ {button} ] ",
    ];

    public function __construct($file)
    {
        if (!file_exists($file)) {
            throw new \Exception(error(1));
        }
        $data = json_decode(file_get_contents($file));
        /**
         * Ide betöltjük először azokat az adatokat, amiket kell
         */
        if (isset($data->load)) {
            if (!is_array($data->load)) {
                $data->load = [$data->load];
            }
            foreach ($data->load as $loadfile) {
                $toload = dirname($file) . "/" . $loadfile;
                if (!file_exists($toload)) {
                    throw new \Exception(error(5));
                } else {
                    $data2 = json_decode(file_get_contents($toload));
                    foreach ($data2 as $name => $value) {
                        if (!isset($data->{$name})) {
                            $data->{$name} = $value;
                        } elseif (is_object($data->{$name}) && is_object($value)) {
                            foreach ($value as $key => $value2) {
                                $data->{$name}->{$key} = $value2;
                            }
                        }

                    }
                }
            }
        }
        foreach ($data as $name => $value) {
            $this->{$name} = $value;
        }
        if(!isset($this->variables)){
            $this->variables = new \stdClass();
        }
        $this->setDefaults();
        /*
        if(isset($data->cols)){
            foreach ($this->cols as &$col) {
                if (!isset($col->alias)) {
                    $col->alias = $col->name;
                }
            }
        }*/
    }

    private function setDefaults()
    {
        foreach ($this->_defaults as $name => $value) {
            if (!property_exists($this, $name)) {
                $this->{$name} = $value;
            }
        }
    }
}

/**
 * @property string $name
 * @property string $alias
 * @property bool $searchable
 * @property string $text
 * @property bool $visible
 * @property string $where
 */
class ConfigDataCol extends \stdClass
{

}


class get extends \stdClass
{
    public $id;
    public $ta_method;
    public $refresh = 1;
    /**
     * @var string $action Ez csak a fileuploader-nél kell
     */
    public $action;

    public function __construct()
    {
        $ar = url();
        if (!empty($ar)) {
            foreach ($ar as $key => $value) {
                $this->{$key} = $value;
            }
        }
    }
}