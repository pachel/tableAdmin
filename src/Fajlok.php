<?php

namespace Pachel\TableAdmin\Models;

use pachel\TableAdmin;

class Fajlok extends fajlokModel
{

    private $_parentId;
    private $_tableName;

    /**
     * @var Options $_options
     */
    private $_options;
    /**
     * @param $db
     * @param $parent_id_name
     * @param $parent_id
     * @param $table_name
     */

    public function __construct($db)
    {

        parent::__construct($db);

        $this->_tableName = TableAdmin::$_Config->formGetTable();
        $this->_parentId = (int)TableAdmin::$_Config->_get->id;
        $this->_setLinks();
        $this->_options = new Options();
    }

    private $_LINK_LIST,$_LINK_DELETE,$_LINK_UPLOAD;
    private function _setLinks()
    {
        $this->_LINK_LIST = TableAdmin::$_Config->getBaseUrl() ."?". url("ta_method=file_uploader&id=".$this->_parentId."&refresh=0&action=list");
        $this->_LINK_DELETE = TableAdmin::$_Config->getBaseUrl() ."?". url("ta_method=file_uploader&id=".$this->_parentId."&refresh=0&action=delete");
        $this->_LINK_UPLOAD = TableAdmin::$_Config->getBaseUrl() ."?". url("ta_method=file_uploader&id=".$this->_parentId."&refresh=0&action=upload");
    }
    public function list()
    {
        //$uid = Login::$_LOGGED_USER->id;
        return $this->db->query("SELECT path,id,name,mime,CONCAT('".$this->_options->uploader_url."',path) link FROM  fajlok WHERE `table`=:table AND deleted=0  AND ((fid=0 AND :id=0 AND id_users=:uid) OR (fid=:id AND :id!=0))")->params(["id"=>$this->_parentId,"table"=>$this->_tableName,"uid"=>$this->_options->uid])->rows();
    }

    public function deleteById($id)
    {
        $fajl = $this->getById($id);
        if($fajl->id_users == $this->_options->uid || $this->_options->uid == $this->_options->admin_uid || $this->_options->gid=$this->_options->admin_gid){
            $file = $this->_options->uploader_dir.$fajl->path;
            if(file_exists($file)){
                //unlink($file);
            }
            $this->update(["deleted"=>1])->id($id);
            //parent::deleteById($id);

        }
        return false;
    }
    public function getJavascript()
    {
        return "<script type=\"text/javascript\">\nvar ajax_list_url = \"".$this->_LINK_LIST."\";\nvar ajax_delete_url = \"".$this->_LINK_DELETE."\";\nvar ajax_upload_url = \"".$this->_LINK_UPLOAD."\";\n</script>
        <script type=\"text/javascript\">".file_get_contents(__DIR__."/../js/uploader.min.js")."</script>";
    }

    /**
     * @param string $filename
     * @return void
     */
    public function upload()
    {
        $ct = count($_FILES["files"]["name"]);
        for ($i=0;$i<$ct;$i++) {
            $dir = $this->_getDirName();
            $pathinfo = pathinfo($_FILES["files"]["name"][$i]);
            $newName = md5(uniqid(rand(), true)) . "." . $pathinfo['extension'];
            $fullpath = $this->_options->uploader_dir . $dir."/" . $newName;
            move_uploaded_file($_FILES["files"]["tmp_name"][$i], $fullpath);

            /**
             * @var fajlokDataModel $data
             */
            $data = new \stdClass();
            $data->path =  $dir . "/" . $newName;
            $data->id_users = $this->_options->uid;
            $data->mime = $pathinfo['extension'];
            $data->name = $pathinfo["filename"];
            $data->fid = $this->_parentId;
            $data->table = $this->_tableName;

            $this->db->insert("fajlok",$data);
            //$this->db->insert($this->_tableName, [$this->_parentIdName => $this->_parentId, "id_fajlok" => $this->db->last_insert_id()]);
        }
    }
    private function _getDirName()
    {
        $i = 1;
        while (true){
            $last = str_pad($i,5,"0",STR_PAD_LEFT);
            $dir = $this->_options->uploader_dir.$last."/";
            if(!file_exists($dir)){
                mkdir($dir);
                return $last;
            }
            $dirs = scandir($dir);
            if(count($dirs)-2<=5000){
                return $last;
            }
            $i++;
        }
    }
}
class Options
{
    public $uid;
    public $gid;
    public $admin_uid;
    public $admin_gid;
    public $uploader_url;
    public $uploader_dir;
    public function __construct()
    {
        $this->uid = (int)TableAdmin::$_Config->getVariable("uid");
        $this->gid = (int)TableAdmin::$_Config->getVariable("gid");
        $this->admin_uid = (int)TableAdmin::$_Config->getVariable("admin_uid");
        $this->admin_gid = (int)TableAdmin::$_Config->getVariable("admin_gid");
        $this->uploader_url = TableAdmin::$_Config->getVariable("uploader_url");
        $this->uploader_dir = TableAdmin::$_Config->getVariable("uploader_dir");
    }
}