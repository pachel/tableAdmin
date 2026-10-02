<?php

namespace Pachel\TableAdmin\Models;

use Pachel\Functions\Session;
use stdClass;

class SqlQuery
{
    /**
     * @var Config $_config
     */
    private $_config;
    private $_sqlQuery;
    public static
        /**
         * @var int $QUERY_TYPE_DEFAULT Alap eset, összes találat
         */
        $QUERY_TYPE_DEFAULT = 0,
        /**
         * @var int $QUERY_TYPE_LIMITED Amikor limit is van, ilyenkor a második paraméter egy tömb a limitről
         */
        $QUERY_TYPE_LIMITED = 1,
        /**
         * @var int $QUERY_TYPE_COUNT Ez csak egy COUNT(*)
         */
        $QUERY_TYPE_COUNT = 2;
    /**
     * @var string $_sessionName
     */
        private $_sessionName;
        public function getSessionName()
        {
            return $this->_sessionName;
        }
    /**
     * @param $config
     */
    public function __construct($config)
    {
        $this->_config = $config;
        $this->_sessionName = md5("__post".$this->_config->getUrl());
        /*
        //$this->_sessionNames->where = md5("__where".$this->_config->getUrl());

        $_POST["first"] = 1;
        $_POST["start"] = 0;
        $_POST["length"] = 25;

        $_POST["ta_extra"]["nev"] = "Tóth";
        $_POST["search"]["value"] = "Tóth László";
*/
        $this->setSelect();
    }

    /**
     * Lekéri az adott sort, amire kattintottunk
     * @return string
     */
    public function getRowQuery()
    {
        return $this->_query_simple_base." WHERE `" . $this->_config->getId() . "`=?";
    }
    public function getEditorQuery()
    {
        $fields = $this->_config->Fields->getFields();
        $sql = "SELECT ";
        foreach ($fields as $index => $field){
            if($index>0){
                $sql .= ", ";
            }
            $sql .= $field->name." AS `".$field->alias."`";
        }
        $sql .= " FROM `".$this->_config->formGetTable()."`";
        $sql .= " WHERE `" . $this->_config->getId() . "`=?";

        return $sql;
    }
    private $_query_base="";
    private $_query_simple_base="";
    private function setSelect()
    {
        $query = "SELECT /*START_CLMN*/ ";
        $cols = $this->_config->Cols->getAllColumns();
        foreach ($cols as $index => $col) {
            if ($index > 0) {
                $query .= ", ";
            }
            $query .= $col->name . " AS " . $col->alias;
        }
        $query .= " /*END_CLMN*/FROM ";
        $tables = $this->_config->getTables();
        if (is_string($tables)) {
            $query .= $tables . " ";
        } else {
            foreach ($tables as $index => $table) {
                if ($index > 0) {
                    $query .= ", ";
                }
                $query .= $table . " ";
            }
        }
        $this->_query_simple_base = $query;
        $query .= " WHERE 1";
        $query .= (is_null($this->_config->getWhere()) ? "" : " AND " . $this->_config->getWhere()) . " /*WHERE*/";
        $query .= (is_null($this->_config->getLast()) ? "" : " " . $this->_config->getLast()) . " /*ORDER*/ /*LIMIT*/";
        $this->_query_base = $query;
        $this->_sqlQuery = $this->_setWhere($query);
       // $this->_setOrder();
    }
    private function getOrderedQuery()
    {
        if($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST["order"]) || !is_array($_POST["order"])){
            return  $this->_sqlQuery;
        }
        $nr = $_POST["order"][0]["column"];
        $dir = $_POST["order"][0]["dir"];
        $cols = $this->_config->Cols->getVisibleColumns();

        foreach ($cols as $index => $col) {
            if($index == $nr){
                return str_replace("/*ORDER*/"," ORDER BY ".$col->alias." ".$dir,$this->_sqlQuery);
                //echo $this->_sqlQuery;
                //exit();
            }
        }
    }
    private function _setWhere($query)
    {

        if(empty($_POST)) {
            return $query."/*EMPTY POST*/";
        }
        $post = $_POST;
        $where = "";

        if(!isset($post["first"])){
            $s = Session::get($this->_sessionName);
            $where = $s["where"];
        }

        if(!empty($post["ta_extra"])) {
            $where .= " ".$this->_sqlWhereFromOutSearch($post["ta_extra"]);
        }
        if(!empty($post["search"]["value"])) {
            $where .= " AND ".$this->_sqlWhereFromSearchText($post["search"]["value"]);
        }
        if(isset($post["first"])){
           Session::set($this->_sessionName, ["where"=>$where,"post"=>$post]);
        }
        end:
        return (!empty($where)?str_replace("/*WHERE*/",$where,$query):$query);
    }
    private function _sqlWhereFromSearchText($text)
    {
        $cols = $this->_config->Cols->getAllColumns();
        $names = [];
        foreach ($cols as $index => $col) {
            if($col->searchable) {
                $names[] = $col->name;
            }
        }
        return (empty($names)?"":sqlWhereFromSearchText2($text, $names));
    }
    private function _sqlWhereFromOutSearch($posts)
    {
        $sql = "";
        $c=0;
        foreach ($posts as $name => $value) {
            $column = $this->_config->Cols->getColumn($name);
            if(empty($column) || empty($column->where) || $value=="") {
                continue;
            }
            if(preg_match_all("/\{post\.".$name."\}/",$column->where,$matches)) {
                $search = [];
                $replace = [];
                foreach ($matches[0] as $index => $match) {
                    $search[] = $matches[0][$index];
                    $replace[] = $value;
                }
                $sql .= " AND ".str_replace($search,$replace,$column->where);
            }
            else{
                $sql .= " AND ".$column->where;
            }

        }
        return $sql;
    }
    public function getAllForAjax()
    {
        return $this->_allForAjaxCount();
    }
    public function getDefaultQuery()
    {
        return $this->_defaultQuery();
    }
    public function getLimitedQuery()
    {
        return $this->_limitedQuery();
    }
    public function getCounterQuery()
    {
        return $this->_counterQuery();
    }

    private function _defaultQuery()
    {
        return $this->_sqlQuery;
    }
    private function _limitedQuery()
    {

        if(!isset($_POST["start"]) || !isset($_POST["length"]) || $_POST["length"] < 0) {
            return $this->getOrderedQuery();
        }
        return str_replace("/*LIMIT*/"," LIMIT ".$_POST["start"].",".$_POST["length"],$this->getOrderedQuery());
    }
    private function _counterQuery()
    {
        return preg_replace("/\/\*START_CLMN\*\/.+\/\*END_CLMN\*\//","COUNT(*) AS ct ",$this->_sqlQuery);
    }
    private function _allForAjaxCount()
    {
        return preg_replace("/\/\*START_CLMN\*\/.+\/\*END_CLMN\*\//","COUNT(*) AS ct ",$this->_query_base);
    }
}