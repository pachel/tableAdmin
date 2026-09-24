<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace pachel;
session_start();
error_reporting(E_WARNING);
ini_set("display_errors",true);

?>
<html>
    <head>
        <title></title>
        <link rel="stylesheet" href="../vendor/twbs/bootstrap/dist/css/bootstrap.min.css" />
        <link rel="stylesheet" href="../vendor/datatables/datatables/media/css/jquery.dataTables.min.css" />
        <link href="https://cdn.datatables.net/buttons/1.7.1/css/buttons.dataTables.min.css" rel="stylesheet" />
        <script type="text/javascript" src="../vendor/components/jquery/jquery.min.js"></script>
        <script type="text/javascript" src="../vendor/twbs/bootstrap/dist/js/bootstrap.min.js"></script>
        <script type="text/javascript" src="http://localhost/tableadmin/js/datatables.min.js"></script>
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
            $db->settings()->setResultmodeToObject();

            $tdadmin = new TableAdmin($db);
            $tdadmin->addButtonActionMethod("delete",function($id){

            });
            $tdadmin->addMethodToButtonsIfVisible(function($row) {


                return true;
            }, "TEszt");
            $tdadmin->loadConfig(__DIR__ . "/felhasznalok.json");
            $tdadmin->addButton("TEszt","Töröl",function($row){

            });
            $tdadmin->show();
            ?>
        </div>




<!--
        <script type="text/javascript" src="https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js"></script>

        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
        <script type="text/javascript" src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.html5.min.js"></script>
-->
    </body>
</html>