<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace pachel;

session_start();
ob_start();
error_reporting(E_ALL);
ini_set("display_errors", true);
?>
<html>
    <head>
        <title></title>
        <link rel="stylesheet" href="../vendor/twbs/bootstrap/dist/css/bootstrap.min.css" />
        <link rel="stylesheet" href="../vendor/datatables/datatables/media/css/jquery.dataTables.min.css" />
        <style>
            .nemfizetve{
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
                "dbname" => "persons_240802",
                "username" => "persons2",
                "password" => "Jsx_Juz_cv.7867"
            ]);
            $tdadmin = new TableAdmin($db);
            $tdadmin->loadConfig(__DIR__ . "/szamlak.json");
            $cegek = $db->fromDatabase("SELECT id AS value,nev AS text FROM p_cegek WHERE statusz=1 ORDER BY nev ASC");
            $d["form"][1][0]["data"] = $cegek;
            $tdadmin->appendConfig($d);
            $tdadmin->addMethodToTRClass(function($row) {
                if ($row["egyenleg"] < 0) {
                    return "nemfizetve";
                }
            });
            //$tdadmin->checkAjaxRequest();
            $button = new Button("add");
            $button->addAction(function ($d){

            },Button::$RUN_WITHOUT_ACTION);
            $tdadmin->show();
            ?>
        </div>
        <script type="text/javascript" src="../vendor/components/jquery/jquery.min.js"></script>
        <script type="text/javascript" src="../vendor/twbs/bootstrap/dist/js/bootstrap.min.js"></script>
        <script type="text/javascript" src="../vendor/datatables/datatables/media/js/jquery.dataTables.js"></script>
    </body>
</html>