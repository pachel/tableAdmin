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
        $POST = "POST",
        $GET = "GET",
        $ALL = "POSTGET",
        /**
         * Azok gombok, amiknek a "GET" betoltésekor nem kell refresh
         * @var string[] $ACTION_NR
         */
        $ACTION_NR = ["add", "edit","ajax_api"];
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
            $html .= " [ " . $this->_makeOneButton($btn) . " ] \n";
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

        return $html;
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
             * Ha az "add" gomb jönne, azt skippeljül, mert azt külön hívjuk meg a táblázat elején
             */
            if ($button->getName() == "add") {
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
        if(empty($button)){
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
                $fields = $this->_config->formGetEditableFields();
                $for_update = [];
                foreach ($fields as $field) {
                    if (isset($_POST[$field->name])) {
                        $for_update[$field->name] = $_POST[$field->name];
                    }
                }
                $row = (array)$row;
                $this->_db->update($this->_config->formGetTable(), $for_update, [$this->_config->formGetId() => $row[$this->_config->formGetId()]]);
            }, true, self::$POST)->addAction(function ($row) {
                $content = new ContentGenerator($this->_db);
                $content->editor($this->_config->_get->id);
                //$this->_makeEditContent($row);
            }, self::$ALL)->setText("Szerkeszt");
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
            }, true,self::$GET)->addAction(function ($row){//POST ESET, amikor elmentjük az adatokat
                $fields = $this->_config->formGetEditableFields();
                $for_insert = [];
                foreach ($fields as $field) {
                    if (isset($_POST[$field->name])) {
                        $for_insert[$field->name] = $_POST[$field->name];
                    }
                }
                $this->_db->insert($this->_config->formGetTable(), $for_insert);
            },self::$POST)->setText("+ Új sor hozzáadása")->setClass("btn p-2 btn-info mb-3");

        }
    }

    public function printButtons()
    {
        print_r($this->_buttons);
        exit();
    }

    /**
     * @param Button $button
     * @return Button
     * @throws \Exception
     */
    public function add($button)
    {
        if(gettype($button) == "string") {
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

        if(is_numeric($this->_config->_get->id)) {
            $sql = $this->_config->SqlQuery->getRowQuery();
            $row = $this->_db->query($sql)->params($this->_config->_get->id)->line();
        }
        else{
            $row = [];
        }
        foreach ($actions as $action) {
            if ($action->type == self::$ALL) {
                ($action->method)($row);
            }
            if (!empty($_POST) && $action->type == self::$POST) {
                ($action->method)($row);
            } elseif (empty($_POST) && $action->type == self::$GET) {
                ($action->method)($row);
            }
        }
        if ($this->_config->_get->refresh == 1) {
            header("location:" . $this->_config->getBaseUrl());
            exit();
        }
        return true;
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
    public $onclick = null;
    public $class = null;
    public $target = "_self";

    public function getName()
    {
        return $this->name;
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
            $this->link_gen = TableAdmin::$_Config->getBaseUrl() . "?" . url("ta_method=" . $this->name . ( empty($row)?"":"&id=".$row[TableAdmin::$_Config->getId()]) . "&refresh=" . (in_array($this->name, Buttons::$ACTION_NR) ? 0 : 1));
            return;
        }
        $this->link_gen = $this->_linkCsere($this->link,$row);
    }
    private function _linkCsere($link, $row)
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
            $type = Buttons::$GET;
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
    public function setUnvisible()
    {
        $this->VisibleMethod = function () {
            return false;
        };
        return $this;
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
        $this->_class = $class;
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
