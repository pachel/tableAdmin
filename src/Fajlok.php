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
    public function setTempToNewRow($row_id)
    {
        $where = ["fid"=>0,"table"=>$this->_tableName,"id_users"=>$this->_options->uid];
        $this->db->update("fajlok",["fid"=>$row_id],$where);
    }
    public function copyFilesTo($idFromCopy,$tableToCopy,$idToCopy)
    {
        $fajlok = $this->db->query("SELECT id FROM `fajlok` WHERE `fid`=:fid AND `table`=:table")->params(["fid"=>$idFromCopy,"table"=>$this->_tableName])->array();

        if(empty($fajlok) || !is_array($fajlok) || !$this->_options->hasDir()){
            return;
        }

        foreach ($fajlok AS $fajl_id){
            $this->_copyFile($fajl_id,$tableToCopy,$idToCopy);
        }
    }
    private function _copyFile($id,$tableToCopy,$idToCopy)
    {
        $subdir = $this->_getDirName();
        /**
         * @var fajlokDataModel $file
         */
        $file = $this->db->query("SELECT *FROM `fajlok` WHERE id=?")->params($id)->line();
        if(empty($file)){
            return;
        }
        $fileToCopy = trimmer($this->_options->uploader_dir."/".$file->path);
        $newPath = trimmer($subdir."/".md5(microtime().$id).".".$file->mime);
        $newFile = trimmer($this->_options->uploader_dir."/".$newPath);
        if(!file_exists($fileToCopy)){
            return;
        }
        copy($fileToCopy,$newFile);
        $data = [
            "path" => $newPath,
            "name" => $file->name,
            "mime" => $file->mime,
            "fid" => $idToCopy,
            "table" => $tableToCopy,
            "id_users" => $this->_options->uid
        ];
        $this->db->insert("fajlok",$data);
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
        $this->uploader_url = trimmer(TableAdmin::$_Config->getVariable("uploader_url"));
        $this->uploader_dir = trimmer(TableAdmin::$_Config->getVariable("uploader_dir"));
    }
    public function hasDir()
    {
        return file_exists($this->uploader_dir);
    }
}