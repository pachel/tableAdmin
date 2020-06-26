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
    private $methods = ["delete" => [], "edit" => []];

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
            } else {
                
            }
        }

        if (isset($_GET["ta_method"]) && $_GET["ta_method"] == "delete") {
            if ($_GET["key"] == $this->key) {
                if (isset($this->config["delete"]) && !empty($this->config["delete"])) {
                    $this->db->toDatabase($this->config["delete"]);
                } else {
                    $this->db->delete($this->config["formTable"], [$this->config["id"] => $_GET["id"]]);
                }
                header("location:" . $this->config["url"]);
                exit();
            } else {
                throw new \Exception(error(3));
            }
        }
    }

    private function saveForm() {
        $elements = $this->getFormElements();
        $data = [];
        foreach ($elements AS $name) {
            if (isset($_POST[$name])) {
                $data[$name] = $_POST[$name];
            }
        }
        $this->db->update($this->config["formTable"], $data, [$this->config["id"] => $_GET["id"]]);
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
    public function addMethodToButtons($method, $button/* delete|edit */) {
        if (gettype($method) != "object") {
            return;
        }
        $this->methods[$button][] = $method;
    }

    public function addMethodToTRClass() {
        
    }

    public function addMethodToTDClass() {
        
    }

    private function checkFormConfig() {
        
    }

    public function addButton($name, $button, $action) {
        
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

        foreach ($elements as $index => $col) {
            if ($index > 0) {
                $sql .= ",";
            }
            $sql .= $col;
        }

        $sql .= " FROM " . $this->config["formTable"];
        $sql .= " WHERE " . $this->config["id"] . "=" . $_GET["id"];
        return $sql;
    }

    public function show() {
        if (empty($this->config)) {
            throw new \Exception(error(0));
        }
        $this->runActions();

        if (!isset($_GET["ta_method"])) {
            $this->setData();
            require __DIR__ . "/../tpls/generateTable.php";
            require __DIR__ . "/../tpls/datatable.js.php";
        } elseif ($_GET["ta_method"] == "edit") {
            $this->checkFormConfig();
            $result = $this->db->fromDatabase($this->generateSelectToForm(), "@line");
            require __DIR__ . "/../tpls/editForm.php";
        }
    }

}
