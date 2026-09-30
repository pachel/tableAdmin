<?php

namespace Pachel\TableAdmin\Models;

use Pachel\dbClass;
use pachel\TableAdmin;

class ContentGenerator
{
    /**
     * @var dbClass $_db
     */
    private $_db;
    /**
     * @var Config $_config
     */
    private $_config;
    private $_bs_maxCols = 12;
    public function __construct($db){
        $this->_db = $db;
        $this->_config = TableAdmin::$_Config;
    }

    /**
     * @param array $row
     * @param Field $field
     * @return string
     */
    private function _commonForInput($row,$field)
    {
        $text = ($field->readonly?"readonly ":"")."class=\"form-control".(!empty($field->classes)?$field->classes:"")."\" placeholder=\"".(!empty($field->placeholder)?$field->placeholder:"")."\" name=\"".$field->alias."\"".$this->_required($field);
        return $text;
    }
    /**
     * @param Field $field
     * @return string
     */
    private function _required($field)
    {
        return ($field->require?" require":"");
    }
    private function _value($row, $field)
    {
        return (isset($row[$field->alias])?$row[$field->alias]:"");
    }
    public function editor($id = null)
    {
        if(is_numeric($id)) {
            $sql = $this->_config->SqlQuery->getEditorQuery();
            $row = $this->_db->query($sql)->params($this->_config->_get->id)->line();
            $row = (array)$row;
        }
        else{
            $row = [];
        }
        $fields = $this->_config->formGetFields();
        ob_start();
        include __DIR__."/../tpls/editForm.tpl.php";
        TableAdmin::$_HTML = ob_get_clean();
    }

    /**
     * @return Field[]|array
     */
    private function _getVisibleColumns()
    {
        $return = [];
        $fields = $this->_config->getCols();
        foreach($fields as $field){
            if($field->visible){
                $return[] = $field;
            }
        }
        return $return;
    }
    private function _getDataForTables()
    {
        $sql = $this->_config->SqlQuery->getDefaultQuery();
        $rows = $this->_db->query($sql)->rows();
        $return = [];
        $visibleColumns = $this->_getVisibleColumns();
        foreach($rows as $row){
            $row_a = (array)$row;
            $row2 = [];
            foreach($visibleColumns as $field){
                $row2[$field->alias] = $row_a[$field->alias];
            }
            $row2["ta_buttons"] = $this->_config->Buttons->generateButtonsHTML($row);
            $return[] = $row2;

        }

        return $return;
    }
    private function _getLinkForNewForm()
    {
        return $this->link = TableAdmin::$_Config->getBaseUrl() . "?" . url("ta_method=add&refresh=1");
    }

    public function table()
    {

        $rows = $this->_getDataForTables();
        $headers = $this->_getVisibleColumns();
        $headers[] = new columnModel(["text"=>"Műveletek","name"=>"ta_buttons"]);
        ob_start();
        include __DIR__."/../tpls/datatable.tpl.php";
        TableAdmin::$_HTML = ob_get_clean();
    }

    /**
     * @param Field $field
     * @return string
     */
    private function _genInput($field)
    {
        $html = "";

        return $html;
    }
}