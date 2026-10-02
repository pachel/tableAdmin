<?php

namespace Pachel\TableAdmin\Models;

use Pachel\dbClass;
use pachel\TableAdmin;
use Pachel\TableAdmin\Traits\propertyFromArray;

class Fields
{
    /**
     * @var dbClass $_db
     */
    private $_db;
    /**
     * @var Config $_config
     */
    private $_config;
    /**
     * @var Field[] $_fields
     */
    private $_fields = [];

    public function __construct($db, $cfg)
    {
        $this->_db = $db;
        $this->_config = $cfg;
    }

    public function isEmpty()
    {
        return empty($this->_fields);
    }

    public function add($field)
    {
        $this->_fields[] = new Field($field);
    }

    /**
     * Lefuttatja a belső sql-eket a selecteknél meg beállítja a value értéket is
     * @param array $row
     * @return void
     */
    public function setFieldsData($row)
    {
        foreach ($this->_fields as $field) {
            $field->set($row, $this->_db);
        }
    }

    /**
     * @return Field[]
     */
    public function getEditableFields()
    {
        if (empty($this->_fields)) {
            $this->_config->loadFields();
        }
        $ret = [];
        foreach ($this->_fields as $field) {
            if (!$field->readonly) {
                $ret[] = $field;
            }
        }
        return $ret;
    }

    /**
     * @return Field[]
     */
    public function getFields()
    {
        return $this->_fields;
    }
}

/**
 * A form elemei
 */
class Field extends \stdClass
{
    public $name;
    public $text;
    public $value = null;
    public $type = "text";
    public $bt_num = null;
    public $__row_number = 0;
    public $placeholder = null;
    public $require = false;

    public $classes = null;
    public $alias = null;
    public $readonly = false;
    /**
     * Alapértelmezett érték, ha nincs kiválasztva semmi a selectben, akkor ez az érték lesz a value
     * @var string
     */
    public $default = "";
    /**
     * Ez a select adatait tartalazza
     * @var selectData[] $data
     */
    public $data = null;
    /**
     * Ez a selecthez tartozó sql lekérdezést tartalmazza.
     * text, value vagy default tulajdonságai vannak a selectnek, erre figyelni kell
     * @var string $sqlData
     */
    public $sqlData = null;
    public $saveable = true;

    use propertyFromArray;

    public function __construct($data)
    {
        $this->_setProperty($data);
        if (!is_null($this->data)) {
            foreach ($this->data as &$value) {
                $value = new selectData($value);
            }
        }
        if (is_null($this->text)) {
            $this->text = $this->name;
        }
        if (is_null($this->alias)) {
            $this->alias = $this->name;
        }
    }

    /**
     * @param array $row
     * @param dbClass $db
     * @return void
     */
    public function set($row, $db)
    {
        $this->_setValue($row);
        if(!empty($this->default)){
            $this->default = TableAdmin::$_Config->replaceVariables($this->default);
        }
        if (!empty($this->sqlData)) {
            $this->data = $db->query($this->sqlData)->rows();
        }
        /**
         * Beállítjuk a select-ekhez az alapértelmezett értékekeket
         * meg a kijelölést a szerkesztésnél
         */
        if (!empty($this->data) && is_array($this->data)) {
            $row = (array)$row;
            $hasdefault = false;
            foreach ($this->data as &$value) {
                $v = (array)$value;
                $value = new \stdClass();
                $value->text = $v["text"];
                $value->value = $v["value"];
                $value->default = ((isset($row[$this->alias]) && $value->value == $row[$this->alias]) || (empty($row) && $v["value"]==$this->default) ? true : false);
                if ($value->default) {
                    $hasdefault = true;
                }
            }
            if(!$hasdefault) {
                $val = new \stdClass();
                $val->text = "Válasszon";
                $val->value = $this->default??"";
                $val->default = true;
                $val->disabled = true;
                array_unshift($this->data,$val);
            }


        }
    }

    private function _setValue($row)
    {
        $row = (array)$row;
        if (isset($row[$this->alias])) {
            $this->value = $row[$this->alias];
        }
    }
}

class selectData extends \stdClass
{
    public $text;
    public $value = null;
    public $default = false;
    use propertyFromArray;

    public function __construct($data)
    {
        $this->_setProperty($data);
    }
}