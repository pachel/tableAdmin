<?php

namespace Pachel\TableAdmin\Models;

use Pachel\dbClass;
use Pachel\Functions\Session;

class Search
{
    /**
     * @var dbClass $_db
     */
    private $_db;
    /**
     * @var Config $_config
     */
    private $_config;
    /**
     * @var Field[] $_elements
     */
    private $_elements;
    public function __construct($db,$config)
    {
        $this->_db = $db;
        $this->_config = $config;
        $post = Session::get($this->_config->getSidName());
        $search = $this->_config->getSearch();

        if(!is_object($search) || empty($search)){
            throw new \Exception("Search config is not an array or empty");
        }
        foreach ($search->cols as $col) {
            $field = new Field($col);
            $field->set($post,$this->_db);
            $this->_elements[] = $field;
        }
    }

    /**
     * @param $name
     * @return mixed|Field|null
     */
    public function getElement($name)
    {
        foreach ($this->_elements as $element) {
            if($element->alias == $name) {
                return $element;
            }
        }
        return null;
    }

    public function run()
    {
        $content = new ContentGenerator($this->_db);
        $content->search();
    }

    /**
     * @return array|Field[]
     */
    public function getElements()
    {
        return $this->_elements??[];
    }
}