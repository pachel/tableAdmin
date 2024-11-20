<?php

use TDF\Models\AjanlatLog;

class Ajanlatok extends BaseClass
{

    /**
     * @var AjanlatLog $Log
     */
    private $Log;
    /**
     * @var \TDF\Models\ajanlatModel $ajanlat
     */
    private $ajanlat;
    public function __construct($app)
    {
        parent::__construct($app);
        $this->Log = new AjanlatLog($this->db);
        $this->ajanlat = new \TDF\Models\ajanlatModel($this->db);
    }
    public function uzenetek(){
        $path = sslDec($_SERVER["QUERY_STRING"]);
        if(!preg_match("/aid=([0-9]+)&url=(.+)/",$path,$preg)){
            $this->app->error(403);
        }
        $id = $preg[1];
        if(isset($_POST["uzenet"]) && !empty($_POST["uzenet"])){
            $this->Log->msg($_POST["uzenet"],$id);
        }

        $sql = file_get_contents(__DIR__."/../sql-queries/ajanlatok/idovonal.sql");
        $idovonal = $this->db->query($sql)->params($id)->rows();

        $ajanlat = $this->ajanlat->getById($id);
        foreach ($idovonal AS &$item){
            //$this->Log->get($item["id"])->
            $item["header"] = $this->Log->get($item["id"])->title;
            $item["body"] = $this->Log->get($item["id"])->body;
            $item["last_data"] = $this->Log->get($item["id"])->last_date;
        }
        $this->app->set("_idovonal",$idovonal,1);
        $this->app->set("_url",$preg[2]);
        $this->app->set("_ajanlat",$ajanlat);
        $this->loadContent("ajanlatok/uzenetek.php");
    }
    private function ms_body($id_msg){
        $this->db->settings()->setResultmodeToObject();
        $msg = $this->Log->getById($id_msg);
        $this->db->settings()->setResultmodeToDefault();
    }


    public function visszadob(){

        $path = sslDec($_SERVER["QUERY_STRING"]);
        if(!preg_match("/id=([0-9]+)&url=(.+)&met=(.+)/",$path,$preg)){
            $this->app->error(403);
        }
        $id = $preg[1];
        $met = $preg[3];


        //$prev = $this->ajanlat->prevStatusz($id);
        $ajanlat = $this->db->query("SELECT *FROM aa_ajanlatok WHERE id=?")->params($id)->line();
        if(isset($_POST) && $_POST["h"] == 12){
            if($met == "visszadob") {
                $prev = $this->ajanlat->prevStatusz($ajanlat["id"]);
            }
            else{
                $prev = $this->ajanlat->nextStatusz($ajanlat["id"]);
            }
            if ($met == "torol"){
                $this->db->update("aa_ajanlatok",["id_statuszok"=>AA_DELETED_ID],["id"=>$id]);
                $this->Log->del()->ajanlat($id);
            }
            else {
                $this->ajanlat->update(["id_statuszok" => $prev])->id($id);
                $uzenet = str_replace("\n", "<br>", $_POST["szoveg"]);

                $this->Log->sts($ajanlat["id"], $ajanlat["id_statuszok"], $prev, $uzenet);
            }

            if (!empty($_POST["szoveg"])) {
                $this->Log->msg($_POST["szoveg"], $id);
            }
            $this->app->reroute($preg[2]);
        }
        $text = [
            "tovabbdob" => "Továbbdob",
            "visszadob" => "Visszadob",
            "torol"=>"Töröl/Nem gyártható",
        ];
        $ajanlat["url"] = $preg[2];
        $this->app->set("_data",$ajanlat);
        $this->app->set("_text",$text);
        $this->app->set("_met",$met);
        $this->loadContent("ajanlatok/visszadob.php");
    }
    private function ajanlatok($uid = null)
    {
        //echo encrypt("askjas&dsjh=asdasd",session_id());


        $szovegek = [
            "torol"=>"Töröl/Nem gyártható",
            "szerkeszt"=>"Módosít",
            "tovabbdob"=>"Továbbít",
            "visszadob"=>"Visszadob",
            "fajlok"=>"Fájlok",
            "uzenetek"=>"Üzenetek",
        ];
        $statusz = new \Pachel\generatedModels\aa_statuszfelelosokModel($this->db);
        //$this->db->settings()->generateModelClass("aa_ajanlatok");

        //$statuszok = $statusz->eq()->statusz(2)->id_felhasznalok($this->app->User->getID())->rows();

        $admin = new pachel\TableAdmin($this->app->db);
        $admin->loadConfig(__DIR__ . "/../config/tableadmin/ajanlatok.json");
        if (!isset($_GET["ta_method"])) {
            Sessions::set("_LAST_URL", $_SERVER["QUERY_STRING"]);
        }
        else {
            $admin->appendConfig(["url" => "?" . Sessions::get("_LAST_URL")], false);
        }

        $statuszok = $this->db->query("SELECT id_statuszok FROM aa_statuszfelelosok WHERE statusz=2 AND id_felhasznalok=?")->params($this->app->User->getID())->array();
        if(in_array(0,$statuszok)){
            $_statuszok = $this->db->query("SELECT s.* FROM aa_statuszok s WHERE s.statusz=2 ORDER BY sorszam")->rows();
        }
        else {
            $_statuszok = $this->db->query("SELECT s.* FROM aa_statuszfelelosok sf,aa_statuszok s WHERE sf.id_felhasznalok=? AND sf.statusz=2 AND sf.id_statuszok!=0 AND s.id=sf.id_statuszok")->params($this->app->User->getID())->rows();
        }

        if(isset($_GET["statusz"]) && $_GET["statusz"] != ""){
            $admin->appendConfig(["where" => " a.id_statuszok IN(" . $_GET["statusz"]. ")"], false);
        }
        else {
            if (isset($_GET["csakazenyem"]) && $_GET["csakazenyem"] == 1) {
                //   print_r($statuszok);
                if (!in_array(0, $statuszok)) {
                    $admin->appendConfig(["where" => " a.id_statuszok IN(" . implode(",", $statuszok) . ")"], false);
                } else {
                    $admin->appendConfig(["where" => " a.id_statuszok NOT IN(4,5,6)"], false);
                }

            }
        }

        //},"_self",null,"return confirm('Biztos hogy továbbdobot az ajánlatot?')");

        $admin->addMethodToButtonsIfVisible(function ($row){
            if($row["statusz_statusz"]==0){
                return false;
            }
            if($row["id_statuszok"] == $this->ajanlat->lastId() || !$this->ajanlat->sajateAzAjanlat($row["id"])){
                return false;
            }
            return true;
        },"tovabbdob");

        $admin->addButton("fajlok", $szovegek["fajlok"]." (%fajlok)", function ($id) {
            //   $this->app->reroute("Penzugy/fajlok?" . base64_encode("id=" . $id . "&table=aa_ajanlatfajlok&name=id_ajanlatok&url=" . $this->app->get("PATH") . "?" . Sessions::get("_LAST_URL")));
            $this->app->reroute("Penzugy/fajlok?" . base64_encode("id=" . $id . "&table=aa_ajanlatfajlok&name=id_ajanlatok&url=" . $this->app->get("PATH") . "?" . Sessions::get("_LAST_URL")));
        });

        $admin->addButton("uzik", $szovegek["uzenetek"], function ($id) {
            $this->app->reroute("Ajanlatok/uzenetek?".sslEnc("aid=".$id."&url=Ajanlatok/teszt?".Sessions::get("_LAST_URL")));
        });

        $admin->addButton("visszadob", $szovegek["visszadob"], function ($id) {
            $this->app->reroute("Ajanlatok/visszadob?".sslEnc("id=".$id."&url=Ajanlatok/teszt?".Sessions::get("_LAST_URL")."&met=visszadob"));
        });
        //},"_self",null,"return confirm('Biztos hogy visszadobod az ajánlatot az előző státuszra?')");



        $admin->addButton("tovabbdob", $szovegek["tovabbdob"], function ($id) {

            $this->app->reroute("Ajanlatok/visszadob?".sslEnc("id=".$id."&url=Ajanlatok/teszt?".Sessions::get("_LAST_URL")."&met=tovabbdob"));
        });
        $admin->addMethodToButtonsIfVisible(function ($row){
            if($row["statusz_statusz"]==0){
                return false;
            }
            if($row["id_statuszok"] == $this->ajanlat->firstId() || !$this->ajanlat->sajateAzAjanlat($row["id"]) || $row["id_statuszok"] == $this->ajanlat->lastId()){
                return false;
            }
            return true;
        },"visszadob");

        $admin->addBeforeActionMehod("edit",function ($id){
            $this->Log->set()->ajanlat($id);
        });


        $admin->addMethodToButtonsIfVisible(function ($row){
            if($row["statusz_statusz"]==0){
                return false;
            }
            return $this->ajanlat->sajateAzAjanlat($row["id"]);
        },"edit");

        $admin->addMethodToButtonsIfVisible(function ($row){
            if($row["statusz_statusz"]==0){
                return false;
            }
            return $this->ajanlat->sajateAzAjanlat($row["id"]);
        },"fajlok");

        $admin->addMethodToButtonsIfVisible(function ($row){
            if($row["statusz_statusz"]==0){
                return false;
            }
            return $this->ajanlat->sajateAzAjanlat($row["id"]);
        },"delete");


        $admin->addButton("delete",$szovegek["torol"],function ($id){
            /*
            $this->db->update("aa_ajanlatok",["id_statuszok"=>6],["id"=>$id]);
            $this->Log->del()->ajanlat($id);
            */
            $this->app->reroute("Ajanlatok/visszadob?".sslEnc("id=".$id."&url=Ajanlatok/teszt&met=torol"));
        },"_self",null,"return confirm('Biztos hogy törli az ajánlatot?')");

        $admin->addButton("add",null,function ($id){
            $azonosito = $this->ajanlat->getID($id);
            if(is_numeric($id)){
                //$this->ajanlat->update(["azonosito"=>$azonosito])->id($id);
                $this->ajanlat->up()->azonosito($azonosito)->where()->id($id)->exec();
                //$this->db->update("aa_ajanlatok",["azonosito"=>$azonosito],["id"=>$id]);
                $this->Log->new()->ajanlat($id);
                $this->Log->sts($id,0,$_POST["id_statuszok"],"Új ajánlat lett rögzítve");
            }
        });
        $admin->addMethodToTRClass(function ($row){
            if($row["id_statuszok"]==AA_DELETED_ID){
                return "text-deleted";
            }
            if(!$this->ajanlat->sajateAzAjanlat($row["id"])){
                return "text-inactive";
            }
            if($row["varakozas"]>0 && $row["last"]>=$row["varakozas"]){
                return "bg-pink";
            }
        });
        $admin->addVariable("userid",Base::instance()->User->getId());
        $this->app->set("_filter", 'filters/aa-ajanlatokhoz.php');
        $this->app->set("_admin", $admin);
        $this->app->set("_statuszok", $_statuszok);
        $this->loadContent("tableadmin.php");
    }
    public function ajanlatok_sajat()
    {

    }
    public function teszt()
    {
        // $this->db->settings()->generateModelClass("aa_ajanlatok");
        $this->ajanlatok();

    }
}