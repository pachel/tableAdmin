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

use http\Url;
use Pachel\Functions\Session;
use Pachel\TableAdmin\Models\Button;
use Pachel\TableAdmin\Models\Buttons;
use Pachel\TableAdmin\Models\columns;
use Pachel\TableAdmin\Models\Config;
use Pachel\TableAdmin\Models\ContentGenerator;

class TableAdmin
{

    /**
     *  Ide lesznek betöltve a konfig adatok
     * @var type
     */
    private $config = [];
    /**
     * @var Config $_Config
     */
    public static $_Config;

    /**
     * Legenerált SQL QUERY a konfigból
     * @var type
     */
    private $sql_query = "";

    private $_get = null;

    /**
     * pachel/dbClass object
     * @var dbClass $db
     */
    private $db;
    /**
     * @var array $_variables
     */
    private $_variables;
    private $strings = [];
    private static $self;
    private $cols = [];
    private $data = [];
    private $key = "";
    private $keyfile = "";

    private $keyCheck = false;

    private $custom_buttons = 0;
    /**
     * pointers to form config
     * @var array
     */
    private $onlyFormCols = [];
    private $onlyCols = [];
    private $array = false;
    /**
     * @var Buttons $Buttons
     */
    public $Buttons;

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
    private $get;
    /**
     * @var columns $columns
     */
    private $columns;
    public function getConfig()
    {
        return $this->config;
    }
    public static $_HTML;
    public static $_JAVASCRIPT;
    public static $_JSON;

    public function generateButtonsNew()
    {
        $row = new \stdClass();
        $row->id = 1;
        //return self::$_Config->Buttons->generateButtonsHTML($row);
    }

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
        $this->get = url();
    }

    /**
     * @param Button $button
     * @return Button
     */
    public function addButton($button)
    {
        return self::$_Config->Buttons->add($button);
    }
    public function printButtons()
    {
        self::$_Config->Buttons->printButtons();
    }

    public function addVariable($name, $value)
    {
        self::$_Config->addVariable($name, $value);
    }

    private function replaceVariable($string)
    {
        if(!is_array($this->_variables) || empty($this->_variables)){
            return $string;
        }
        foreach ($this->_variables AS $varname => $value){
            $search[] = "{{".$varname."}}";
            $replace[] = $value;
        }
        $string = str_replace($search,$replace,$string);
        return $string;
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

        self::$_Config = Config::instance($config,$this->db);
        $this->_get = url();
        /*
        //$this->Buttons = new Buttons($this->db);
        //$this->columns = new columns();

        if(!isset($this->config["ajax"]) || !is_bool($this->config["ajax"])){
            $this->config["ajax"] = false;
        }


        if (isset($this->config["keycheck"]) && !$this->config["keycheck"]) {
            $this->keyCheck = false;
        }*/
    }

    public function appendConfig($config, $overwrite = true)
    {
        self::$_Config->appendConfig($config,$overwrite);

    }
    public function isAjax()
    {
        return self::$_Config->isAjax();
    }



    /**
     * Ha a configban van beállítva string opció
     * akkor itt kicseréljük a szövegeket
     * @return void
     */
    private function setStringData()
    {
        if (empty($this->strings)) {
            return;
        }
        foreach ($this->data as &$row) {
            foreach ($row as $index => &$col) {
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

    /**
     * @param bool $show ha nem akarjuk a táblázatot megjeleníteni
     * @return void
     * @throws \Exception
     */
    public function show($show = true)
    {
        if (empty($this->config)) {
            throw new \Exception(error(0));
        }
        if(!self::$_Config->Buttons->runActions()){
            $content = new ContentGenerator($this->db);
            $content->table();
        }
    }
    public function getUrl()
    {
        return self::$_Config->getUrl();
    }
    public function isAjaxRequest()
    {
        if(isset(self::$_Config->_get->ta_method) && in_array(self::$_Config->_get->ta_method,["ajax_api","file_uploader"])){
            return true;
        }
        return false;
    }



    private function linkCsere($link, $row)
    {
        $c = [];
        if(preg_match("/%/",$link)) {
            foreach ($row as $index => $value) {
                $c[0][] = "%" . $index;
                $c[1][] = $value;
            }
            $link = str_replace($c[0], $c[1], $link);
        }
        if(preg_match_all("/\{(.+?)\}/",$link,$preg)) {
            foreach ($preg[1] AS $name){
                $c[0][] = "{" . $name."}";
                $c[1][] = (is_array($row)?(isset($row[$name])?$row[$name]:"{" . $name."}"):(is_object($row)?(isset($row->{$name})?$row->{$name}:"{" . $name."}"):""));
            }
            $link = str_replace($c[0], $c[1], $link);
        }
        if(preg_match("/(.+)#ec:(.+)/",$link,$preg)) {
            $link = $preg[1].url($preg[2]);
        }
        return $link;
    }



    private function getDirName()
    {

        $root = str_replace($_SERVER["DOCUMENT_ROOT"], '', str_replace(["\\", "/src"], ["/", ""], __DIR__));
        return $_SERVER["REQUEST_SCHEME"] . "://" . $_SERVER["SERVER_NAME"] . $root . "";
    }


    private function setError($text)
    {
        $this->errorText = $text;
    }

    private function getError()
    {
        $html = $this->errorText;
        if (empty($this->errorText)) {
            return "";
        }

        return $html;
    }
    private function replaceAllVariables(&$config = null)
    {


        if(empty($this->_variables) || !is_array($this->_variables)){
            return;
        }
        if ($config === null) {
            $config = &$this->config;
        }
        foreach ($config as $key => &$value) {
            if (is_array($value)) {
                $this->replaceAllVariables($value);
                //echo $key.":".print_r($value,true)."<br>";
            } else {
                if (is_string($value)) {
                    $config[$key] = $this->replaceVariable($value);
                }
            }
        }

    }
}