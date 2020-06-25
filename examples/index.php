<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace pachel;
?>
<html>
    <head>
        <title></title>
        <link rel="stylesheet" href="../vendor/twbs/bootstrap/dist/css/bootstrap.min.css" />
        <link rel="stylesheet" href="../vendor/datatables/datatables/media/css/jquery.dataTables.min.css" />
    </head>
    <body>            

        <div class="container">
            <h1>Teszt</h1>
            <?php
            require __DIR__ . "/../vendor/autoload.php";

            $db = new \Pachel\dbClass([
                "prename" => "",
                "server" => "localhost",
                "dbname" => "persons",
                "username" => "persons2",
                "password" => "Jsx_Juz_cv.7867"
            ]);
            $tdadmin = new TableAdmin($db);
            $tdadmin->addMethodToButtons(function($row) {
                if ($row["egyedek"] == 0) {
                    return true;
                }

                return false;
            }, "delete");
            $tdadmin->loadConfig(__DIR__ . "/cegek.json");
            $tdadmin->show();
            ?>
        </div>
        <script type="text/javascript" src="../vendor/components/jquery/jquery.min.js"></script>
        <script type="text/javascript" src="../vendor/twbs/bootstrap/dist/js/bootstrap.min.js"></script>
        <script type="text/javascript" src="../vendor/datatables/datatables/media/js/jquery.dataTables.js"></script>
    </body>
</html>