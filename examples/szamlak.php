<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace pachel;

use Pachel\TableAdmin\Models\Button;

session_start();
//ob_start();
error_reporting(E_ALL);
ini_set("display_errors", 1);

?>
<html>
<head>
    <title></title>
    <link rel="stylesheet" href="../vendor/twbs/bootstrap/dist/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="../vendor/datatables/datatables/media/css/jquery.dataTables.min.css"/>
    <script type="text/javascript" src="../vendor/components/jquery/jquery.min.js"></script>

    <style>
        .nemfizetve {
            color: red;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Teszt</h1>
    <?php
    require __DIR__ . "/../vendor/autoload.php";

    $db = new \Pachel\dbClass([
        "prename" => "",
        "server" => "localhost",
        "dbname" => "ttr2",
        "username" => "ttr2",
        "password" => "ttr2"
    ]);
    $tdadmin = new TableAdmin($db);
    $tdadmin->loadConfig(__DIR__ . "/szamlak.json");
    $cegek = $db->fromDatabase("SELECT id AS value,nev AS text FROM p_cegek WHERE statusz=1 ORDER BY nev ASC");
    $d["form"][1][0]["data"] = $cegek;
    $tdadmin->appendConfig($d);
    $tdadmin->addMethodToTRClass(function ($row) {
        if ($row["egyenleg"] < 0) {
            return "nemfizetve";
        }
    });
    $tdadmin->addButtons(new Button("add"))->setText("Új számla hozzáadása")->addClass("btn btn-primary");
    //$tdadmin->checkAjaxRequest();
    $tdadmin->addButtons(new Button("delete"))->setText("Törlés")->addAction(function($id){

    },Button::$RUN_WITHOUT_DEFAULT_ACTION)->setIsVisible(function ($row){

    })->addClass("btn btn-success");
    $tdadmin->show();
    ?>

</div>
<script type="text/javascript" src="../vendor/components/jquery/jquery.min.js"></script>
<script type="text/javascript" src="../vendor/twbs/bootstrap/dist/js/bootstrap.min.js"></script>
<?php $tdadmin->getJS()?>
</body>
</html>