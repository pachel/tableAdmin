<?php

namespace Pachel\TableAdmin\Models;


use Pachel\dbClass;

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
    public function __construct($file,$db)
    {
        $this->_db = $db;
        if (!is_null($file)) {
            $this->addConfigFile($file);
        }
        $this->Buttons = new Buttons($this->_db,$this);
        $this->Cols = new columns($this);
        $this->SqlQuery = new SqlQuery($this);
        $this->_setBaseUrl();
        $this->_get = new get();
    }
    private function _setBaseUrl()
    {
        $this->_config->baseUrl = $_SERVER["REQUEST_SCHEME"] . "://" . $_SERVER["SERVER_NAME"] . (isset($_SERVER["REDIRECT_URL"])?$_SERVER["REDIRECT_URL"]:"");
        if($_SERVER["HTTP_HOST"] == "localhost") {
            $this->config["url_full"] = str_replace("index.php", "", $this->config["url_full"]) . $this->config["url"];
        }
    }
    public function getBaseUrl()
    {
        return $this->_config->baseUrl;
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
        return $this->_config->url;
    }

    public function isEditable()
    {

        return true;
    }

    public function addConfigFile($file)
    {
        $this->_config = new ConfigData(json_decode(file_get_contents($file)));
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
            if(!$field->readonly){
                $ret[] = $field;
            }
        }
        return $ret;
    }
    /**
     * @return Field[]
     */
    public function formGetFields()
    {
        $list = [];
        foreach ($this->_config->form->cols as $field) {
            if (is_array($field)) {
                foreach ($field as $value) {
                    $list[] = new Field($value);
                }
            } else {
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
        $this->_config->variables[$name] = $value;
    }
}

/**
 * @property string $baseUrl
 * @property string $url
 * @property string[] $tables
 * @property bool $keyCheck
 * @property string $last
 * @property string $where
 * @property string $id id's name
 * @property bool $addButton
 * @property string $formTable
 * @property string $form
 * @property ConfigDataCol[] $cols
 * @property array $variables
 *
 */
class ConfigData extends \stdClass
{
    private $_defaults = [
        "addButton" => true,
        "id" => "id",
        "keyCheck"=>false,
        "last"=>null,
        "form"=>null,
        "where"=>null,
    ];

    public function __construct($data)
    {
        foreach ($data as $name => $value) {
            $this->{$name} = $value;
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
            if(!property_exists($this, $name)) {
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


class Field extends \stdClass
{
    public $name;
    public $text;
    public $type = "text";
    public $bt_num = null;
    public $placeholder = null;
    public $require = false;
    public $classes = null;
    public $alias = null;
    public $readonly = false;
    public $saveable = true;

    public function __construct($data)
    {
        if (is_object($data)) {
            foreach ($data as $key => $value) {
                $this->{$key} = $value;
            }
            if (is_null($this->text)) {
                $this->text = $this->name;
            }
            if (is_null($this->alias)) {
                $this->alias = $this->name;
            }
        }
    }
}

class get extends \stdClass
{
    public $id;
    public $ta_method;
    public $refresh = 1;
    public function __construct()
    {
        $ar = url();
        if(!empty($ar)){
            foreach ($ar as $key => $value) {
                $this->{$key} = $value;
            }
        }
    }
}