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

    /**
     * pointers to form config
     * @var array
     */
    private $onlyFormCols = [];
    private $onlyCols = [];

    /**
     *
     *  A gombokhoz tartozó függvények, csak akkor jelennek meg a gombok, ha a függvény visszatérési értéke igaz
     * @var array
     */
    private $methods = ["delete" => [], "edit" => []];

    /**
     *  Függvénylista a gombokhoz
     *  A gomb linkjének meghívásakor fut(nak) le
     * @var array
     */
    private $buttonActionMethods = ["delete" => []];

    /**
     *  Extra gombok, 
     * @var array
     */
    private $buttons = []; /* ["name"=>"verk","text"=>"VERKBE"] */
    private $trClassMethod = [];

    /**
     * 
     * @param type pachel/dbClass
     */
    public function __construct(&$db = null) {
        if ($db != null) {
            $this->db = &$db;
        }

        $this->keyfile = __DIR__ . "/../tmp/ta_key_" . md5($_SERVER["HTTP_HOST"] . $_SERVER["SCRIPT_FILENAME"] . session_id());

        if (!isset($_GET["ta_method"])) {
            $this->key = md5(time() . microtime());
            file_put_contents($this->keyfile, $this->key);
        } else {
            $this->key = file_get_contents($this->keyfile);
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
            $this->config = $config;
            return;
        }
        if (!is_array($config)) {
            if (is_file($config)) {
                $this->config = json_decode(file_get_contents($config), true);
            } else {
                throw new \Exception(error(1));
            }
        }
        foreach ($this->config["cols"] AS &$col) {
            if (!isset($col["alias"])) {
                $col["alias"] = $col["name"];
            }
        }
    }

    private function runActions() {
        if (isset($_POST) && !empty($_POST)) {
            if ($_GET["ta_method"] == "edit") {
                if ($_GET["key"] == $this->key) {
                    $this->saveForm();
                } else {
                    throw new \Exception(error(2));
                }
            } elseif ($_GET["ta_method"] == "add") {
                if ($_GET["key"] == $this->key) {
                    $this->saveForm();
                    header("location:" . $this->config["baseUrl"] . $this->config["url"]);
                    exit();
                } else {
                    throw new \Exception(error(2));
                }
            } else {
                
            }
        }

        if (isset($_GET["ta_method"]) && $_GET["ta_method"] == "delete") {
            if ($_GET["key"] == $this->key) {

                if (isset($this->buttonActionMethods["delete"]) && gettype($this->buttonActionMethods["delete"]) == "object") {

                    $this->buttonActionMethods["delete"]($_GET["id"]);
//                    $this->db->toDatabase($this->config["delete"]);
                } else {
                    $this->db->delete($this->config["formTable"], [$this->config["id"] => $_GET["id"]]);
                }
                header("location:" . $this->config["baseUrl"] . $this->config["url"]);
                exit();
            } else {
                throw new \Exception(error(3));
            }
        }
        if (isset($_GET["ta_method"]) && $_GET["ta_method"] != "edit" && $_GET["ta_method"] != "add") {
            if ($_GET["key"] == $this->key) {
                if (isset($this->buttonActionMethods[$_GET["ta_method"]]) && gettype($this->buttonActionMethods[$_GET["ta_method"]]) == "object") {
                    $this->buttonActionMethods[$_GET["ta_method"]]($_GET["id"]);
                }
            }
            //die($this->buttonMethods[$_GET["ta_method"]]);
            header("location:" . $this->config["baseUrl"] . $this->config["url"]);
            exit();
        }
    }

    public function addBeforeActionMehod($button, $method) {
        
    }

    private function ifnosave($name) {
        if (empty($this->onlyFormCols) || !is_array($this->onlyFormCols)) {
            return false;
        }
        foreach ($this->onlyFormCols AS &$col) {
            if ($col["name"] == $name && isset($col["noSave"]) && $col["noSave"]) {
                return true;
            }
        }
        return false;
    }

    private function saveForm() {
        $elements = $this->getFormElements();
        $data = [];
        foreach ($elements AS $name) {
            if (isset($_POST[$name]) && !$this->ifnosave($name)) {
                $data[$name] = $_POST[$name];
            }
        }

        if ($_GET["ta_method"] == "edit") {
            $this->db->update($this->config["formTable"], $data, [$this->config["id"] => $_GET["id"]]);
            if (isset($this->buttonActionMethods["edit"]) && gettype($this->buttonActionMethods["edit"]) == "object") {
                $this->buttonActionMethods["edit"]($_GET["id"]);
            }
        } elseif ($_GET["ta_method"] == "add") {
            $this->db->insert($this->config["formTable"], $data);
            if (isset($this->buttonActionMethods["add"]) && gettype($this->buttonActionMethods["add"]) == "object") {
                $this->buttonActionMethods["add"]($this->db->last_insert_id());
            }
        }
    }

    public function appendConfig($config, $overwrite = true) {
        if (!is_array($config)) {
            throw new \Exception(error(4));
        }
        if ($overwrite) {
            //$this->config = array_merge($this->config,$config);
            $this->addValuesToConfig($config);
        } else {
            
            foreach ($config AS $key => $value) {
                $this->config[$key] .= $value;
            }
        }        
    }
    private function addValuesToConfig($array,&$config = null) {
        if(!is_array($array)){
            return;            
        }
        if(empty($config)){
            $config = &$this->config;
        }
        $keys[] = key($array);
        foreach ($keys as $key) {
            if(!isset($config[$key])){
                $config[$key] = $array[$key];
            }
            else{
                $this->addValuesToConfig($array[$key], $config[$key]);
            }
        }
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
            $sql .= " " . $this->config["last"];
        }
        $this->sql_query = $sql;
    }

    /**
     * 
     * @param type object
     * @param type string delete|edit
     * @return type
     */
    public function addMethodToButtonsIfVisible($method, $button/* delete|edit */) {
        if (gettype($method) != "object") {
            return;
        }
        $this->methods[$button][] = $method;
    }

    /**
     * 
     * @param type $method
     * @param type $button
     * @return type
     */
    public function addButtonActionMethod($button, $method) {
        if (gettype($method) != "object") {
            return;
        }
        $this->buttonActionMethods[$button] = $method;
    }

    public function addMethodToTRClass($method) {
        if (gettype($method) != "object") {
            return;
        }
        $this->trClassMethod[0] = $method;
    }

    public function addMethodToTDClass() {
        
    }

    private function checkFormConfig() {
        /**
         * 
         */
        if (isset($this->config["form"])) {
            foreach ($this->config["form"] AS &$row) {
                foreach ($row AS &$col) {
                    $this->onlyFormCols[] = &$col;
                }
            }
        }
        if (!empty($this->onlyFormCols)) {
            foreach ($this->onlyFormCols as &$col) {
                if (isset($col["sqlData"]) && !empty($col["sqlData"])) {
                    $col["data"] = $this->db->fromDatabase($col["sqlData"]);
                }
            }
        }
    }

    public function addButton($name, $text, $action = NULL) {
        if (!empty($action) && gettype($action) == "object") {
            $this->addButtonActionMethod($name, $action);
            //$this->buttonMethods[$name] = $action;
        }
        if ($name != "delete" && $name != "edit" && $name != "add") {
            $this->buttons[] = ["name" => $name, "text" => $text];
        }
    }

    private function runMethods($button, $row) {
        foreach ($this->methods[$button] AS &$method) {
            if (!$method($row)) {
                return false;
            }
        }
        return true;
    }

    private function setData() {
        $this->setQuery();
        $this->data = $this->db->fromDatabase($this->sql_query);
    }

    private function getFormElements() {
        $elements = [];
        foreach ($this->config["form"] AS $row) {
            foreach ($row as $col) {
                $elements[] = $col["name"];
            }
        }
        return $elements;
    }

    private function generateSelectToForm() {
        $sql = "SELECT ";
        $elements = $this->getFormElements();
        $counter = 0;
        foreach ($elements as $index => $col) {
            if (!$this->ifnosave($col)) {
                if ($counter > 0) {
                    $sql .= ",";
                }
                $sql .= $col;
                $counter++;
            }
        }

        $sql .= " FROM " . $this->config["formTable"];
        $sql .= " WHERE " . $this->config["id"] . "=" . $_GET["id"];
        return $sql;
    }

    public function show() {
        if (empty($this->config)) {
            throw new \Exception(error(0));
        }
        $this->checkFormConfig();

        $this->runActions();

        if (!isset($_GET["ta_method"])) {
            $this->setData();
            require __DIR__ . "/../tpls/generateTable.php";
            require __DIR__ . "/../tpls/datatable.js.php";
        } elseif ($_GET["ta_method"] == "edit") {

            $result = $this->db->fromDatabase($this->generateSelectToForm(), "@line");
            require __DIR__ . "/../tpls/editForm.php";
        } elseif ($_GET["ta_method"] == "add") {
            require __DIR__ . "/../tpls/editForm.php";
        }
    }

}
