<?php

namespace Pachel\TableAdmin\Models;



class Config
{
    /**
     * @var ConfigData $_config
     */
    private $_config;

    public function __construct($file=null)
    {

        if(!is_null($file)) {
            $this->addConfigFile($file);
        }
    }
    public function getUrl()
    {
        return $this->_config->url;
    }
    public function isEditable()
    {

        return true;
    }
    public function addConfigFile($file)
    {
        $this->_config = json_decode(file_get_contents($file));
    }
    public function hasForm()
    {
        if(isset($this->_config->form) && is_array($this->_config->form)){
            return true;
        }
        return false;
    }

    /**
     * @return Field[]
     */
    public function formGetEditableFields()
    {
        $list = [];
        foreach ($this->_config->form as $field) {
            if(is_array($field)){
                foreach ($field as $value) {
                    $list[] = new Field($value);
                }
            }
            else{
                $list[] = new Field($field);
            }
        }
        return $list;
    }
    public function formGetTable()
    {
        if(!$this->hasForm()){
            return null;
        }
        return $this->_config->formTable;
    }
    public function getId()
    {
        return $this->_config->id;
    }
    public function addVariable($name, $value)
    {
        $this->_config->variables[$name] = $value;
    }
}

/**
 * @property string $baseUrl
 * @property string $url
 * @property string $ajaxUrl
 * @property string[] $tables
 * @property bool $keyCheck
 * @property string $last
 * @property string $id id's name
 * @property bool $addButton
 * @property string $formTable
 * @property ConfigDataCol[] $cols
 * @property array $variables
 *
 */
class ConfigData extends \stdClass
{

}

/**
 * @property string $name
 * @property string $alias
 * @property string $text
 * @property bool $visible
 * @property string $where
 */
class ConfigDataCol extends \stdClass{

}


class Field extends \stdClass
{
    public $name;
    public $text;
    public $type = "text";
    public $saveable = true;
    public function __construct($data)
    {
        if(is_object($data)){
            foreach ($data as $key => $value) {
                $this->{$key} = $value;
            }
            if(is_null($this->text)){
                $this->text = $this->name;
            }
        }
    }
}