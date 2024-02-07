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

class TableAdmin
{

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
    private $strings = [];
    private static $self;
    private $cols = [];
    private $data = [];
    private $key = "";
    private $keyfile = "";

    private $keyCheck = true;

    private $custom_buttons = 0;
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

    private $beforMethods = [];
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
    public function __construct(&$db = null)
    {
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
    public static function instance()
    {
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
    public function loadConfig($config)
    {
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
        foreach ($this->config["cols"] as &$col) {

            if (!isset($col["alias"])) {
                $col["alias"] = $col["name"];
            }
            /**
             * Csekkoljuk a string opciót
             */
            if(isset($col["string"])){
                $this->strings[$col["alias"]] = $col["string"];
            }
        }
        if (isset($this->config["keycheck"]) && !$this->config["keycheck"]) {
            $this->keyCheck = false;
        }
    }

    private function runActions()
    {
        if (isset($_POST) && !empty($_POST)) {
            if ($_GET["ta_method"] == "edit") {
                if ($_GET["key"] == $this->key || !$this->keyCheck) {
                    $this->saveForm();
                } else {
                    throw new \Exception(error(2));
                }
            } elseif ($_GET["ta_method"] == "add") {
                if ($_GET["key"] == $this->key || !$this->keyCheck) {
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
            if ($_GET["key"] == $this->key || !$this->keyCheck) {

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
            if ($_GET["key"] == $this->key || !$this->keyCheck) {
                if (isset($this->buttonActionMethods[$_GET["ta_method"]]) && gettype($this->buttonActionMethods[$_GET["ta_method"]]) == "object") {
                    $this->buttonActionMethods[$_GET["ta_method"]]($_GET["id"]);
                }
            }
            //die($this->buttonMethods[$_GET["ta_method"]]);
            header("location:" . $this->config["baseUrl"] . $this->config["url"]);
            exit();
        }
    }

    public function addBeforeActionMehod($button, $method)
    {
        if (gettype($method) != "object") {
            return;
        }
        $this->beforMethods[$button] = $method;

    }

    private function ifnosave($name)
    {
        if (empty($this->onlyFormCols) || !is_array($this->onlyFormCols)) {
            return false;
        }
        foreach ($this->onlyFormCols as &$col) {
            if ($col["name"] == $name && isset($col["noSave"]) && $col["noSave"]) {
                return true;
            }
        }
        return false;
    }

    private function saveForm()
    {
        $elements = $this->getFormElements();
        $data = [];
        foreach ($elements as $name) {
            if (isset($_POST[$name]) && !$this->ifnosave($name)) {
                $data[$name] = $_POST[$name];
            }
        }
        foreach ($this->beforMethods AS $button => $method){
            if($_GET["ta_method"] == $button){
                $this->beforMethods[$button]($_GET["id"]);
                break;
            }
        }
        if ($_GET["ta_method"] == "edit") {
            $this->db->update($this->config["formTable"], $data, [(isset($this->config["formId"])?$this->config["formId"]:$this->config["id"]) => $_GET["id"]]);
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

    public function appendConfig($config, $overwrite = true)
    {
        if (!is_array($config)) {
            throw new \Exception(error(4));
        }
        if ($overwrite) {
            //$this->config = array_merge($this->config,$config);
            $this->addValuesToConfig($config);
            // print_r($this->config);

        } else {

            foreach ($config as $key => $value) {
                $this->config[$key] .= $value;
            }
        }
    }

    private function addValuesToConfig($array, &$config = null)
    {
        if (!is_array($array)) {
            return;
        }
        if (empty($config)) {
            $config = &$this->config;
        }
        $keys[] = key($array);
        foreach ($keys as $key) {

            if (!isset($config[$key]) || !is_array($array[$key])) {
                $config[$key] = $array[$key];
            } else {
                $this->addValuesToConfig($array[$key], $config[$key]);
            }
        }
    }

    private function setQuery($limit = [])
    {

        $sql = "SELECT ";

        foreach ($this->config["cols"] as $index => $col) {
            $this->cols[] = ["text" => $col["text"]];
            if ($index > 0) {
                $sql .= ",";
            }
            $sql .= $col["name"] . (isset($col["alias"]) ? " AS " . $col["alias"] : "");
        }
        $sql .= ",'' tb___buttons FROM ";
        foreach ($this->config["tables"] as $index => $table) {
            if ($index > 0) {
                $sql .= ",";
            }
            $sql .= " " . $table;
        }
        if (isset($this->config["where"]) && !empty($this->config["where"])) {
            $sql .= " WHERE " . $this->config["where"];
        }
        if (isset($limit["search"]) && !empty($limit["search"]["value"])) {
            $search = explode(" ", $limit["search"]["value"]);
            foreach ($search as $item) {
                $sql .= " AND (";
                $ct = 0;
                foreach ($this->config["cols"] as $index => $col) {
                    if ($ct > 0) {
                        $sql .= " OR ";
                    }

                    if (!isset($col["visible"]) || $col["visible"] != false) {
                        $sql .= $col["name"] . " LIKE '%" . $item . "%'";
                        $ct++;
                    }
                }
                $sql .= ")";
            }

        }
        if (isset($this->config["last"])) {
            $sql .= " " . $this->config["last"];
        }
        if (!empty($limit) && isset($limit["start"]) && isset($limit["length"])) {
            if (!empty($limit["order"])) {
                $sql = preg_replace("/order by [^ ]+$/i", "", $sql);
                $sql .= " ORDER BY " . $this->config["cols"][$limit["order"]["column"]]["alias"] . " " . $limit["order"]["dir"];
            }
            $sql .= " LIMIT " . $limit["start"] . "," . $limit["length"];
            //  die($sql);
        }


        $this->sql_query = $sql;
    }

    /**
     *
     * @param type object
     * @param type string delete|edit
     * @return type
     */
    public function addMethodToButtonsIfVisible($method, $button/* delete|edit */)
    {
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
    public function addButtonActionMethod($button, $method)
    {
        if (gettype($method) != "object") {
            return;
        }
        $this->buttonActionMethods[$button] = $method;
    }

    public function addMethodToTRClass($method)
    {
        if (gettype($method) != "object") {
            return;
        }
        $this->trClassMethod[0] = $method;
    }

    public function addMethodToTDClass()
    {

    }

    private function checkFormConfig()
    {
        /**
         *
         */
        if (isset($this->config["form"])) {
            foreach ($this->config["form"] as &$row) {
                foreach ($row as &$col) {
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

    public function addButton($name, $text, $action = NULL, $link_target = "_self", $link = null, $onclick = null)
    {

        if (!empty($action) && gettype($action) == "object") {
            $this->addButtonActionMethod($name, $action);
            //$this->buttonMethods[$name] = $action;
        }
        if ($name != "delete" && $name != "edit" && $name != "add") {
            $this->buttons[] = ["name" => $name, "text" => $text, "target" => $link_target, "link" => $link, "onclick" => $onclick];
            $this->custom_buttons++;
        }
    }

    private function runMethods($button, $row)
    {
        if (!isset($this->methods[$button]) || !is_array($this->methods[$button])) {
            return true;
        }
        foreach ($this->methods[$button] as &$method) {
            if (!$method($row)) {
                return false;
            }
        }
        return true;
    }

    private function checkSearchSessions($search)
    {
        $live = 20 * 3600;//sec
        $hash = md5(serialize($search) . serialize($this->config));


        if ((isset($_SESSION["search_____"][$hash]) && $_SESSION["search_____"][$hash]["time"] > (time() - $live))) {

            $_SESSION["recordsFiltered"] = $_SESSION["search_____"][$hash]["ct"];

        } else {
            $_SESSION["search_____"][$hash]["time"] = time();

            $this->setQuery(["search" => $search]);
            $this->data = $this->db->fromDatabase($this->sql_query);
            $_SESSION["recordsFiltered"] = count($this->data);
            $_SESSION["search_____"][$hash]["ct"] = $_SESSION["recordsFiltered"];

        }
        /*   print_r($_SESSION);
           die();*/
    }

    private function setData($limit = [], $type = null)
    {
        if (!empty($limit)) {
            /*
            if ($limit["draw"] == 1) {
                $this->setQuery();
                $this->data = $this->db->fromDatabase($this->sql_query, $type);
                $_SESSION["recordsTotal"] = count($this->data);
                $_SESSION["recordsFiltered"] = $_SESSION["recordsTotal"];
            }
            else {
                if(!$this->checkSearchSessions($limit["search"])){

                }
                else {
                    $this->setQuery($limit);
                }
            }*/
            if ($limit["draw"] == 1) {
                $this->setQuery();
                $this->data = $this->db->fromDatabase($this->sql_query, $type);
                $_SESSION["recordsTotal"] = count($this->data);
                $_SESSION["recordsFiltered"] = $_SESSION["recordsTotal"];

            } else {
                $this->checkSearchSessions($limit["search"]);
                $this->setQuery($limit);

                /*
                $this->setQuery(["search" => $_POST["search"]]);
                $this->data = $this->db->fromDatabase($this->sql_query, $type);
                $_SESSION["recordsTotal"] = count($this->data);
                $_SESSION["recordsFiltered"] = count($this->data);*/
            }

            $this->setQuery($limit);

        } else {
            $this->setQuery();
        }
        $this->data = $this->db->fromDatabase($this->sql_query, $type);
        $this->setStringData();
    }

    /**
     * Ha a configban van beállítva string opció
     * akkor itt kicseréljük a szövegeket
     * @return void
     */
    private function setStringData(){
        if(empty($this->strings)){
            return;
        }
        foreach ($this->data AS &$row){
            foreach ($row AS $index => &$col) {
                if (!isset($this->strings[$index])) {
                    continue;
                }
                /**
                 * template karakter ##|%%
                 * Kicseréljük amire kell
                 */
                $col = str_replace(["##", "%%"], $col, $this->strings[$index]);
            }
        }
    }

    private function getFormElements()
    {
        $elements = [];
        foreach ($this->config["form"] as $row) {
            foreach ($row as $col) {
                $elements[] = $col["name"];
            }
        }
        return $elements;
    }

    private function generateSelectToForm()
    {
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

    public function getJS()
    {
        echo "<script type=\"text/javascript\" src=\"" . $this->getDirName() . "/js/datatables.min.js\"></script>";
    }

    public function show()
    {
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

    public function checkAjaxRequest()
    {
        $this->checkFormConfig();
        $this->runActions();


        if (isset($_POST["draw"])) {
            $limit = [
                "start" => $_POST["start"],
                "length" => $_POST["length"],
                "draw" => $_POST["draw"],
                "search" => $_POST["search"],
                "order" => (isset($_POST["order"][0]) ? $_POST["order"][0] : null)
            ];
            $this->setData($limit);
            $data_array = [];

            if (!empty($this->data)) {
                foreach ($this->data as &$row) {
                    $buttons = $this->generateButtons($row);

                    $row2 = [];
                    foreach ($row as $index => $value) {
                        if (!isset($this->config["cols"][$index]["visible"]) || !$this->config["cols"][$index]["visible"]) {
                            //print_r($this->config["cols"][$index]["visible"]);
                            $row2[] = $value;
                        }
                    }
                    if (empty($buttons)) {
                        //unset($row["tb___buttons"]);
                    } else {

                        $row2[count($row) - 1] = $buttons;
                    }
                    $data_array[] = $row2;
                }
            }
            header('Content-Type: application/json;charset=utf-8');
            $data = [
                "draw" => $_POST["draw"],
                //"recordsTotal" => $_SESSION["recordsTotal"],
                "recordsTotal" => $_SESSION["recordsTotal"],
                //"recordsFiltered" => $_SESSION["recordsFiltered"],
                "recordsFiltered" => $_SESSION["recordsFiltered"],
                "data" => $data_array

            ];

            echo json_encode($data, JSON_PRETTY_PRINT);
            die();
        }
    }
    private function linkCsere($link,$row){
        $c = [];
        foreach ($row AS $index => $value){
            $c[0][] = "%".$index;
            $c[1][] = $value;
        }
        return str_replace($c[0],$c[1],$link);
    }

    private function generateButtons($row)
    {
        $html = "";
        if ((isset($this->config["form"]) && !empty($this->config["form"])) || $this->custom_buttons > 0):
            $html = "<td>";
            if (((isset($this->config["form"]) && !empty($this->config["form"])) || (isset($this->config["deleteButton"]) && $this->config["deleteButton"])) && $this->runMethods("delete", $row)):
                $html .= "[<a href=\"" . $this->config["url"] . "?ta_method=delete&key=" . $this->key . "&id=" . $row[$this->config["id"]] . "\" onclick=\"return confirm('Biztos hogy törli?')\">Töröl</a>]";
            endif;
            if ((isset($this->config["form"]) && !empty($this->config["form"])) && $this->runMethods("edit", $row)):
                $html .= "[<a href=\"" . $this->config["url"] . "?ta_method=edit&key=" . $this->key . "&id=" . $row[$this->config["id"]] . "\">Szerkeszt</a>]";
            endif;
            foreach ($this->buttons as $button):if ($this->runMethods($button["name"], $row)):
                if (empty($button["link"])) {
                    $button["link"] = $this->config["url"] . "?ta_method=" . $button["name"] . "&key=" . $this->key . "&id=" . $row[$this->config["id"]];
                }
                $button["link"] = $this->linkCsere($button["link"],$row);
                $button["onclick"] = $this->linkCsere($button["onclick"],$row);
                $html .= "[<a href=\"" . $button["link"] . "\" target=\"" . $button["target"] . "\"".(!empty($button["onclick"])?" onclick=\"".$button["onclick"]."\"":"").">" . $button["text"] . "</a>]";

            endif;endforeach;
            $html .= "</td>";
        endif;
        return $html;
    }

    private function getDirName()
    {

        $root = str_replace($_SERVER["DOCUMENT_ROOT"], '', str_replace(["\\", "/src"], ["/", ""], __DIR__));
        return $_SERVER["REQUEST_SCHEME"] . "://" . $_SERVER["SERVER_NAME"] . $root . "";
    }

    private function getAjaxData()
    {
        $data = [];
        foreach ($this->data as $item) {
            $rc = [];
            foreach ($item as $row) {
                $rc[] = $row;
            }
            $rc[] = "";
            $data[] = $rc;
        }
        return $data;
    }

    private function addParamaterToWhere($param)
    {
        if (empty($param)) {
            return;
        }
        $having = " HAVING ";
        if (!empty($this->config["last"])) {
            //$where = " AND ";
        }
        if (!is_array($param)) {
            $having .= "(";
            foreach ($this->config["cols"] as $index => $col) {
                if ($index > 0) {
                    $having .= " OR ";
                }
                $having .= "`" . $col["alias"] . "` LIKE '%" . $param . "%'";
            }
            $having .= ")";
        }
        if (strlen($param) > 5) {
            // $this->config["last"] = preg_replace("/LIMIT [0-9]+/i", "", $this->config["last"]);
            //die($this->config["last"]);
        }
        $this->config["last"] = $having;
        // die($having);
    }

    /**
     * @param $param
     */
    public function ajaxSearch($param = [])
    {
        /*print_r($_GET);
        die();*/
        $data = [
            "draw" => 1,
            "recordsTotal" => 10,
            "recordsFiltered" => 10,
            "data" => []
        ];
        $this->addParamaterToWhere($param);
        $this->setData();

        $data["data"] = $this->getAjaxData();
        $data["recordsFiltered"] = count($data["data"]);
        return json_encode($data, JSON_PRETTY_PRINT);
    }
    private function setError($text){
        $this->errorText = $text;
    }
    private function getError(){
        $html = $this->errorText;
        if(empty($this->errorText)){
            return  "";
        }

        return $html;
    }
}