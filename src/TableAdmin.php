<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of TableAdmin
 *
 * @author Tóth Láaszló
 */

namespace pachel;

class TableAdmin {

    /**
     *  Ide lesznek betöltve a konfig adatok
     * @var type 
     */
    private $config = [];

    /**
     * Legenerált SQL QUERY a konfigból
     * @var type 
     */
    private $sql_query = "";

    /**
     * pachel/dbClass object
     * @var type 
     */
    private $db;
    private static $self;
    private $cols = [];
    private $data = [];
    private $key = "";
    private $keyfile = "";
    private $methods = ["delete"=>[],"edit"=>[]];
    /**
     * 
     * @param type pachel/dbClass
     */
    public function __construct(&$db = null) {
        if ($db != null) {
            $this->db = &$db;
        }

        $this->keyfile = __DIR__ . "/../tmp/ta_key_" . md5($_SERVER["HTTP_HOST"] . $_SERVER["SCRIPT_FILENAME"].session_id());
        if (!isset($_GET["ta_method"])) {
            $this->key = md5(time() . microtime());
            file_put_contents($this->keyfile, $this->key);
        }
    }

    /**
     * 
     * @return type
     */
    public static function instance() {
        if (empty(self::$self)) {
            $ref = new \Reflectionclass("pachel\TableAdmin");
            $args = func_get_args();
            self::$self = ($args ? $ref->newinstanceargs($args) : new TableAdmin());
        }
        return self::$self;
    }

    /**
     * 
     * @param type $config
     */
    public function loadConfig($config) {
        if (is_array($config)) {
            return;
        }
        if (is_file($config)) {
            $this->config = json_decode(file_get_contents($config), true);
        }
        foreach ($this->config["cols"] AS &$col) {
            if (!isset($col["alias"])) {
                $col["alias"] = $col["name"];
            }
        }
    }

    public function appendConfig($config) {
        
    }

    private function setQuery() {
        $sql = "SELECT ";

        foreach ($this->config["cols"] AS $index => $col) {
            $this->cols[] = ["text" => $col["text"]];
            if ($index > 0) {
                $sql .= ",";
            }
            $sql .= $col["name"] . (isset($col["alias"]) ? " AS " . $col["alias"] : "");
        }
        $sql .= " FROM ";
        foreach ($this->config["tables"] AS $index => $table) {
            if ($index > 0) {
                $sql .= ",";
            }
            $sql .= " " . $table;
        }
        if (isset($this->config["where"]) && !empty($this->config["where"])) {
            $sql .= " WHERE " . $this->config["where"];
        }
        if (isset($this->config["last"])) {
            $sql .= " ".$this->config["last"];
        }
        $this->sql_query = $sql;
    }
    /**
     * 
     * @param type object
     * @param type string delete|edit
     * @return type
     */
    public function addMethodToButtons($method,$button/*delete|edit*/) {
        if(gettype($method)!= "object"){
            return;
        }
        $this->methods[$button][] = $method;
    }
    public function addMethodToTRClass() {
        
    }
    public function addMethodToTDClass() {
        
    }
    private function runMethods($button,$row){
        foreach ($this->methods[$button] AS &$method){
            if(!$method($row)){
                return false;
            }
        }
        return true;
    }
    private function setData() {
        $this->setQuery();
        $this->data = $this->db->fromDatabase($this->sql_query);
    }

    public function show() {
        
        if (!isset($_GET["ta_method"])) {
            $this->setData();
            require __DIR__ . "/../tpls/generateTable.php";
            require __DIR__ . "/../tpls/datatable.js.php";
        }
    }

}
