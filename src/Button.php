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
     * @var Config
     */
    private $_config;

    public static
        $ACTION_RUN_BEFORE_DEFAULT = 0,
        $ACTION_RUN_AFTER_DEFAULT = 1,
        $ACTION_RUN_WITHOUT_DEFAULT = 2,
        $POST = "POST",
        $GET = "GET",
        $ALL = "POSTGET";

    public function __construct($db)
    {
        $this->_db = $db;
        $this->_setDefaultButtons();
    }
    public function generateButtonsHTML($row)
    {
        $html = "";
        $btns = $this->_getVisibleButtons($row);

        foreach ($btns as $index => $btn) {
            $html.=" [ <a href=\"".$btn->link."\"";
            if(!is_null($btn->target)) {
                $html.=" target=\"".$btn->target."\"";
            }
            if(!is_null($btn->class)) {
                $html.=" class=\"".$btn->class."\"";
            }
            if(!is_null($btn->onclick)){
                $html.=" onclick=\"".$btn->onclick."\"";
            }
            $html.=">".$btn->text."</a> ] \n";
        }
        echo $html;
        exit();

        return $html;
    }

    /**
     * @return Button[]
     */
    private function _getVisibleButtons($row)
    {
        $btns = [];
        foreach ($this->_buttons as $button) {
            if($button->isVisible($row)) {
                $button->makeVariables($row);
                $btns[] = $button;
            }
        }
        return $btns;
    }

    private function _setDefaultButtons()
    {

        if (TableAdmin::$_Config->isEditable()) {
            $this->add(new Button("edit"))->addAction(function ($id) {
                $fields = TableAdmin::$_Config->formGetEditableFields();
                $for_update = [];
                foreach ($fields as $field) {
                    if (isset($_POST[$field->name])) {
                        $for_update[$field->name] = $_POST[$field->name];
                    }
                }
                $this->_db->update(TableAdmin::$_Config->formGetTable(), $for_update, [TableAdmin::$_Config->getId() => $id]);
            }, true, self::$POST)->setText("Szerkeszt");
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
}

class Button
{
    private $_isVisible = true;

    private $VisibleMethod = null;
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
    public function makeVariables($row)
    {
        $row = (array)$row;
        if(empty($this->link)) {
            $this->link = TableAdmin::$_Config->getUrl() . "?" . url("ta_method=" . $this->name . "&id=" . $row["id"]);
        }
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
            } else {
                $position = $a;
            }
        }
        if (!isset($method)) {
            throw new \Exception("Nincs feldolgozó függvény!");
        }
        if (!isset($position)) {
            $position = Buttons::$ACTION_RUN_AFTER_DEFAULT;
        }
        if (!isset($default)) {
            $default = false;
        }
        if ($default) {
            $this->actions = [$method];
        } else {
            switch ($position) {
                case Buttons::$ACTION_RUN_BEFORE_DEFAULT:
                    array_unshift($this->actions, $method);
                    break;
                case Buttons::$ACTION_RUN_WITHOUT_DEFAULT:
                    $this->actions = [$method];
                    break;
                default:
                    $this->actions[] = $method;
            }
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
    public function setOnclick($onclick)
    {
        $this->onclick = $onclick;
    }
    public function setClass($class)
    {
        $this->class = $class;
    }

    public function isVisible($row)
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
}
