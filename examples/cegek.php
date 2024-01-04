<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace pachel;
session_start();
error_reporting(E_ALL);
ini_set("display_errors",true);

?>
<html>
    <head>
        <title></title>
        <link rel="stylesheet" href="../vendor/twbs/bootstrap/dist/css/bootstrap.min.css" />
        <link rel="stylesheet" href="../vendor/datatables/datatables/media/css/jquery.dataTables.min.css" />
        <link href="https://cdn.datatables.net/buttons/1.7.1/css/buttons.dataTables.min.css" rel="stylesheet" />
    </head>
    <body>            

        <div class="container">
            <h1>Teszt</h1>
            <?php
            require __DIR__ . "/../vendor/autoload.php";

            $db = new \Pachel\dbClass([
                "prename" => "",
                "server" => "localhost",
                "dbname" => "persons_231102",
                "username" => "persons2",
                "password" => "Jsx_Juz_cv.7867"
            ]);
            $tdadmin = new TableAdmin($db);
            $tdadmin->addButtonActionMethod("delete",function($id){
                global $db;
                $db->update("p_cegek",["statusz"=>0],["id"=>$id]);
            });
            $tdadmin->addMethodToButtonsIfVisible(function($row) {
                if ($row["egyedek"] == 0) {
                    return true;
                }
                return true;
            }, "delete");
            $tdadmin->loadConfig(__DIR__ . "/cegek.json");
            $tdadmin->show();
            ?>
        </div>
        <script type="text/javascript" src="../vendor/components/jquery/jquery.min.js"></script>
        <script type="text/javascript" src="../vendor/twbs/bootstrap/dist/js/bootstrap.min.js"></script>
        <?php $tdadmin->getJS()?>


<!--
        <script type="text/javascript" src="https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js"></script>

        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
        <script type="text/javascript" src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.html5.min.js"></script>
-->
    </body>
</html>