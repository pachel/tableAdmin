<?php

namespace Pachel\TableAdmin\Models;
use pachel\TableAdmin;

class columns
{
    /**
     * @var columnModel[] $columns
     */
    private $columns = [];
    /**
     * @var Config $_config
     */
    private $_config;

    /**
     * @param Config $_config
     */
    public function __construct($_config)
    {
        $cols =  $_config->getCols();
        foreach ($cols as $col) {
            $this->add($col);

            if (!isset($col->alias)) {
                $col->alias = $col->name;
            }
            if (!isset($col->visible)) {
                $col->visible = true;
            }
            if (!isset($col->text)) {
                $col->text = $col->alias;
            }
            /**
            if (isset($col["string"])) {
                $this->strings[$col["alias"]] = $col["string"];
            }*/
        }
    }

    public function add($dataFromConfig)
    {
        $this->columns[] = new columnModel($dataFromConfig);
    }
    public function getColumn($name)
    {
        foreach ($this->columns as $column) {
            if($column->alias == $name) {
                return $column;
            }
        }
        return null;
    }

    /**
     * @return columnModel[]
     */
    public function getVisibleColumns()
    {
        $ret = [];
        foreach ($this->columns as $column) {
            if ($column->visible) {
                $ret[] = $column;
            }
        }
        return $ret;
    }


    /**
     * @return columnModel[]
     */
    public function getAllColumns()
    {
        
        return $this->columns;
    }
    public function getColumnNames($onlyVisible = true)
    {
        $ret = [];
        foreach ($this->columns as $column) {
            if (!$onlyVisible) {
                $ret[] = $column->name;
            } elseif ($column->visible) {
                $ret[] = $column->name;
            }
        }
        return $ret;
    }

    public function getColumnAliases($onlyVisible = true)
    {
        $ret = [];
        foreach ($this->columns as $column) {
            if (!$onlyVisible) {
                $ret[] = $column->alias;
            } elseif ($column->visible) {
                $ret[] = $column->alias;
            }
        }
        return $ret;
    }
    public function addWhereCode($name,$object)
    {
        foreach ($this->columns as &$column) {
            if ($column->name == $name) {
                $column->whereForSearch = $object;
            }
        }
    }
}

class columnModel
{
    public $name;
    public $text;
    public $alias;
    public $searchable = true;
    public $visible = true;
    public $where = null;

    public function __construct($col)
    {
        foreach ($col as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
        if (empty($this->alias))
            $this->alias = $this->name;
        if (empty($this->text))
            $this->text = $this->alias;
        if(empty($this->where)){
            $this->where = $this->name."='{post.".$this->alias."}'";
        }
        else{
            $this->makeWhere();
        }
    }
    private function makeWhere()
    {
        if(preg_match("/\{self\.(.+?)\}/",$this->where,$preg)){
            $this->where = str_replace($preg[0],$this->name,$this->where);
        }
    }
}