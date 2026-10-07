<?php

namespace Pachel\TableAdmin\Models;

use Pachel\dbClass;
use pachel\TableAdmin;

class Buttons
{
    /**
     * @var Button[] $_buttons
     */
    private $_buttons = [];
    /**
     * @var dbClass $db
     */
    private $_db;
    /**
     * @var Config $_config
     */
    private $_config;

    public static
        $ACTION_RUN_BEFORE_DEFAULT = 0,
        $ACTION_RUN_AFTER_DEFAULT = 1,
        $ACTION_RUN_WITHOUT_DEFAULT = 2,
        $REQUEST_METHOD_POST = "POST",
        $REQUEST_METHOD_GET = "GET",
        $REQUEST_METHOD_AJAX = "XHR",
        $REQUEST_METHOD_ALL = "POSTGET",
        /**
         * Azok gombok, amiknek a "GET" betoltésekor nem kell refresh
         * @var string[] $ACTION_NR
         */
        $ACTION_NR = ["add", "edit", "ajax_api", "file_uploader"],
        $BUTTONS_DEFAULT = ["add", "delete", "edit", "ajax_api", "file_uploader"];

    public function __construct($db, $config)
    {
        $this->_db = $db;
        $this->_config = $config;
        $this->_setDefaultButtons();
    }

    public function generateButtonsHTML($row)
    {
        $html = "";
        $btns = $this->_getVisibleButtons($row);
        foreach ($btns as $index => $btn) {
            $html .= $this->_makeOneButton($btn);
        }
        return $html;
    }

    /**
     * @param Button $btn
     * @return string
     */
    private function _makeOneButton($btn)
    {
        $html = "<a href=\"" . $btn->link_gen . "\"";
        if (!is_null($btn->target)) {
            $html .= " target=\"" . $btn->target . "\"";
        }
        if (!is_null($btn->class)) {
            $html .= " class=\"" . $btn->class . "\"";
        }
        if (!is_null($btn->onclick)) {
            $html .= " onclick=\"" . $btn->onclick . "\"";
        }
        $html .= ">" . $btn->text . "</a>";
        $template = $btn->getTemplate();
        //if(preg_match("/{link}/",$template,$preg)){
        return str_replace(["{link}", "{button}", "{text}", "{onclick}"], [$btn->link_gen, $html, $btn->text, $btn->onclick], $template);
        /*}
        return preg_replace("/\{button\}/i",$html,$template);*/
    }

    public function getVisibleButtons($row = null)
    {
        return $this->_getVisibleButtons($row);
    }

    /**
     * @return Button[]
     */
    private function _getVisibleButtons($row)
    {
        $btns = [];
        foreach ($this->_buttons as $button) {
            $button->makeVariables($row);
            /**
             * Ha az "add|ajax_api" gomb jönne, azt skippeljül, mert azt külön hívjuk meg a táblázat elején
             */
            if (in_array($button->getName(), ["add", "ajax_api"])) {
                continue;
            }
            if ($button->isVisible($row)) {
                $btns[] = $button;
            }
        }
        return $btns;
    }

    public function getAddButtonHTML()
    {
        $button = $this->_getAddButton();
        if (empty($button)) {
            return null;
        }
        $button->makeVariables();
        return $this->_makeOneButton($button);
    }

    /**
     * @return Button|null
     */
    private function _getAddButton()
    {
        foreach ($this->_buttons as $button) {
            if ($button->getName() == "add" && $button->isVisible()) {
                return $button;
            }
        }
        return null;
    }


    /**
     * @return void
     * @throws \Exception
     */
    private function _setDefaultButtons()
    {

        if ($this->_config->isEditable()) {
            /**
             * EDIT alapértelmezett funkció
             */
            $this->add("edit")->addAction(function ($row) {
                $fields = $this->_config->Fields->getEditableFields();
                $for_update = [];
                foreach ($fields as $field) {
                    if (isset($_POST[$field->name])) {
                        $for_update[$field->name] = $_POST[$field->name];
                    }
                }
                $row = (array)$row;
                $this->_db->update($this->_config->formGetTable(), $for_update, [$this->_config->formGetId() => $row[$this->_config->formGetId()]]);
            }, true, self::$REQUEST_METHOD_POST)->addAction(function ($row) {
                $content = new ContentGenerator($this->_db);
                $content->editor($this->_config->_get->id);
            }, true, self::$REQUEST_METHOD_ALL)->setText("Szerkeszt");
            /**
             * DELETE alapértelmezett funkció
             */
            $this->add("delete")->addAction(function ($row) {
                $this->_db->query("DELETE FROM `" . $this->_config->formGetTable() . "` WHERE `" . $this->_config->getId() . "`=?")->params($row->{$this->_config->getId()})->exec();
            }, true)->setText("Töröl")->setOnclick("return confirm('Biztos, hogy törli?')");
            /**
             * ADD alapértelmezett funkció
             */
            $this->add("add")->addAction(function ($row) {
                $content = new ContentGenerator($this->_db);
                $content->editor();
            }, true, self::$REQUEST_METHOD_GET)->addAction(function ($row) {//POST ESET, amikor elmentjük az adatokat
                $fields = $this->_config->Fields->getEditableFields();
                $for_insert = [];
                foreach ($fields as $field) {
                    if (isset($_POST[$field->alias])) {
                        $for_insert[$field->alias] = $_POST[$field->alias];
                    }
                }
                $this->_db->insert($this->_config->formGetTable(), $for_insert);
            }, self::$REQUEST_METHOD_POST, true)->setText("+ Új sor hozzáadása")->setClass("btn p-2 btn-info mb-3 align-self-start")->setTemplate("{button}");
        }
        /**
         * Az ajaxos táblageneráláshoz kell, ez adja vissza a találatokat
         * AJAX_API
         */
        $this->add("ajax_api")->setUnvisible()->addAction(function ($row) {
            $draw = isset($_POST['draw']) ? (int)$_POST['draw'] : 1;
            $start = isset($_POST['start']) ? (int)$_POST['start'] : 0;
            $length = isset($_POST['length']) ? (int)$_POST['length'] : 10;

            $sql_count = $this->_config->SqlQuery->getCounterQuery();
            $sql_all = $this->_config->SqlQuery->getAllForAjax();
            $sql_limited = $this->_config->SqlQuery->getLimitedQuery();

            $filtered = $this->_db->query($sql_count)->cache("5m")->simple();
            $data = $this->_db->query($sql_limited)->rows();
            $all = $this->_db->query($sql_all)->cache("30m")->simple();
            foreach ($data as &$row) {
                $r = $row;
                $row = (array)$row;
                $row["tb___buttons"] = $this->_config->Buttons->generateButtonsHTML($r);
            }
            TableAdmin::$_JSON = [
                "draw" => $draw,
                "recordsTotal" => $all,
                "recordsFiltered" => $filtered,
                "data" => $data
            ];
        }, Buttons::$REQUEST_METHOD_POST, true);
        $this->add("file_uploader")->setInvisible()->addAction(function ($row) {
            $id = $this->_config->_get->id ?? 0;
            $fajlok = new Fajlok($this->_db);
            TableAdmin::$_JSON = ["status" => "ok", "files" => $fajlok->list()];

        }, Buttons::$REQUEST_METHOD_GET)->setText("Fájlok feltöltése")->addAction(function ($row) {
            $id = $this->_config->_get->id ?? 0;
            $fajlok = new Fajlok($this->_db);
            if ($this->_config->_get->action == "delete") {
                $fajlok->deleteById((int)$_POST["id"]);
                TableAdmin::$_JSON = ["status" => "ok"];
            } else {
                $fajlok->upload();
                TableAdmin::$_JSON = ["status" => "ok"];
            }
        }, Buttons::$REQUEST_METHOD_POST);
    }

    /**
     * @param Button $button
     * @return Button
     * @throws \Exception
     */
    public function add($button)
    {
        if (gettype($button) == "string") {
            $button = new Button($button);
        }
        if (gettype($button) != "object" && get_class($button) != "Pachel\TableAdmin\Models\Button") {
            throw new \Exception("sdfgsdf");
        }
        $vane = $this->get($button->getName());
        //echo $button->getName() . "\n";
        if ($vane != null) {
            return $vane;
        } else {
            $button->setTemplate($this->_config->getButtonTemplate());
            $this->_buttons[] = $button;
            $index = count($this->_buttons) - 1;
        }
        return $this->_buttons[$index];
    }

    /**
     * @param $name
     * @return Button|null
     */
    public function get($name)
    {
        foreach ($this->_buttons as $button) {
            if ($button->getName() == $name) {
                return $button;
            }
        }
        return null;
    }

    /**
     * @return bool|void
     */

    public function runActions()
    {

        //$_POST["nev"] = "Tóth László 2";

        $this->_config->initSearch();

        $this->_config->initSqlQueries();
        if ($this->_config->hasSearch()) {
            $this->_config->Search->run();
        }
        if (!isset($this->_config->_get->ta_method)) {
            return false;
        }

        $button = $this->get($this->_config->_get->ta_method);

        if (empty($button)) {
            return false;
        }

        $actions = $button->getActions();
        if (empty($actions)) {
            return false;
        }

        if (is_numeric($this->_config->_get->id)) {
            $sql = $this->_config->SqlQuery->getRowQuery();
            $row = $this->_db->query($sql)->params($this->_config->_get->id)->line();

        } else {
            $row = [];
        }
        //   echo $button->text."\n";
        //   echo count($actions)."\n";

        foreach ($actions as $action) {
//            echo "type:".$action->type."\n";
            if ($action->type == self::$REQUEST_METHOD_ALL) {
                ($action->method)($row);
                continue;
            }
            /*if (!empty($_POST) && $action->type == self::$REQUEST_METHOD_POST) {
                ($action->method)($row);
            } els*/
            if (strtoupper($_SERVER["REQUEST_METHOD"]) == $action->type) {
                ($action->method)($row);
            }
        }

        if ($this->_config->_get->refresh == 1) {
            header("location:" . $this->_config->getBaseUrl());
            exit();
        }
        // exit();
        return true;
    }

    public function hasCustomButtons()
    {
        foreach ($this->_buttons as $button) {
            if (!in_array($button->getName(), self::$BUTTONS_DEFAULT)) {
                return true;
            }
        }
        return false;
    }

    public function hasButtons()
    {
        return ($this->_config->isEditable() || $this->hasCustomButtons() ? true : false);
    }
}

class Button
{
    private $_isVisible = true;

    private $VisibleMethod = null;
    /**
     * @var Action[] $actions
     */
    private $actions = [];
    private $name;
    public $text;
    public $link;
    public $link_gen = null;
    public $onclick = null;
    public $class = null;
    public $target = "_self";
    private $_default = false;
    private $Template;

    public function getName()
    {
        return $this->name;
    }

    public function setTemplate($template)
    {
        $this->Template = $template;
    }

    public function getTemplate()
    {
        return $this->Template;
    }

    /**
     * @return Action[]
     */
    public function getActions()
    {
        return $this->actions;
    }

    public function makeVariables($row = null)
    {
        $row = (array)$row;

        if (empty($this->link)) {
            $this->link_gen = TableAdmin::$_Config->getBaseUrl() . "?" . url("ta_method=" . $this->name . (empty($row) ? "" : "&id=" . $row[TableAdmin::$_Config->getId()]) . "&refresh=" . (in_array($this->name, Buttons::$ACTION_NR) ? 0 : 1));
            return;
        }
        $this->link_gen = $this->_linkCsere($this->link, $row);
    }

    private function _linkCsere($link, $row)
    {
        $c = [];
        $row = (array)$row;
        if (preg_match("/%/", $link)) {
            foreach ($row as $index => $value) {
                $c[0][] = "%" . $index . "%";
                $c[1][] = $value;
            }
            $link = str_replace($c[0], $c[1], $link);
        }
        $link = TableAdmin::$_Config->replaceVariables($link);

        if (preg_match_all("/\{row\.(.+?)\}/i", $link, $matches)) {
            foreach ($matches[1] as $index => $name) {
                if (!isset($row[$name])) {
                    continue;
                }
                $search[] = $matches[0][$index];
                $replace[] = $row[$name];
            }
            if (!empty($replace)) {
                $link = str_replace($search, $replace, $link);
            }
        }
        /*
        if(preg_match_all("/\{(.+?)\}/i", $link, $matches)) {
            foreach ($matches[1] AS $index => $name){
                if(!isset($row[$name])){
                    continue;
                }
                $search[] = $matches[0][$index];
                $replace[] = $row[$name];
            }
            if(!empty($replace)){
                $link = str_replace($search, $replace, $link);
            }
        }
        /*
        if(preg_match_all("/\{(.+?)\}/",$link,$preg)) {
            $c = [];
            foreach ($preg[1] AS $name){
                if(!isset($row[$name])) {
                    continue;
                }
                $c[0][] = "{" . $name."}";
                $c[1][] = $row[$name];
            }
            if(!empty($c)) {
                $link = str_replace($c[0], $c[1], $link);
            }
        }*/
        if (preg_match("/(.+)#ec:(.+)/", $link, $preg)) {
            $link = $preg[1] . url($preg[2]);
        }
        return $link;
    }

    public function __construct($name)
    {
        if (is_string($name)) {
            $this->name = $name;
        } elseif (is_array($name)) {
            foreach ($name as $k => $v) {
                if (method_exists($this, $k)) {
                    $this->{$k} = $v;
                }
            }
        }
        if (!isset($this->name)) {
            throw new \Exception("A name nincs megadva!");
        }
        if (!isset($this->text)) {
            $this->text = $this->name;
        }

    }

    /**
     * @param $text
     * @return Button
     */
    public function setText($text)
    {
        $this->text = $text;
        return $this;
    }

    /**
     * @param object|int|bool ...$args
     * @return $this
     */
    public function addAction(...$args)
    {

        foreach ($args as $a) {
            if (is_object($a)) {
                $method = $a;
            } elseif (is_bool($a)) {
                $default = $a;
            } elseif (is_numeric($a)) {
                $position = $a;
            } else {//string
                $type = $a;
            }
        }
        if (!isset($method)) {
            throw new \Exception("Nincs feldolgozó függvény!");
        }
        if (!isset($position)) {
            $position = Buttons::$ACTION_RUN_AFTER_DEFAULT;
        }
        if (!isset($type)) {
            $type = Buttons::$REQUEST_METHOD_GET;
        }
        if (!isset($default)) {
            $default = false;
        }
        $method = new Action($method, $type, $default);
        switch ($position) {
            case Buttons::$ACTION_RUN_BEFORE_DEFAULT:
                array_unshift($this->actions, $method);
                break;
            case Buttons::$ACTION_RUN_WITHOUT_DEFAULT:
                $replace = false;
                foreach ($this->actions as &$action) {
                    if ($method->type == $action->type) {
                        $action = $method;
                        $replace = true;
                    }
                }
                if (!$replace) {
                    $this->actions[] = $method;
                }
                break;
            default:
                $this->actions[] = $method;
                break;
        }
        return $this;
    }

    /**
     * @param $method
     * @return Button
     */
    public function setIsVisible($method)
    {
        $this->VisibleMethod = $method;
        return $this;
    }

    /**
     * @return Button
     */

    public function setInvisible()
    {
        $this->VisibleMethod = function () {
            return false;
        };
        return $this;
    }

    /**
     * @return Button
     *
     */
    public function setUnvisible()
    {
        return $this->setInvisible();
    }

    /**
     * @param $onclick
     * @return Button
     */
    public function setOnclick($onclick)
    {
        $this->onclick = $onclick;
        return $this;
    }

    /**
     * @param $class
     * @return Button
     */
    public function setClass($class)
    {
        $this->class = $class;
        return $this;
    }

    /**
     * @param $lnk
     * @return Button
     */
    public function setLink($lnk)
    {
        $this->link = $lnk;
        return $this;
    }

    public function isVisible($row = null)
    {

        if (is_null($this->VisibleMethod) || !is_object($this->VisibleMethod)) {
            return true;
        }
        return ($this->VisibleMethod)($row);
    }

    /**
     * @param $class
     * @return Button
     */
    public function addClass($class)
    {
        $this->class = $class;
        return $this;
    }
}

class Action
{
    public $method;
    public $default = false;
    public $type;

    public function __construct(...$args)
    {
        foreach ($args as $name => $a) {
            if (is_object($a)) {
                $this->method = $a;
            } elseif (is_bool($a)) {
                $this->default = $a;
            } elseif (is_string($a)) {
                $this->type = $a;
            }
        }
    }
}
