<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace pachel;

use Pachel\TableAdmin\Models\Button;
use Pachel\TableAdmin\Models\Buttons;

session_start();
ob_start();
error_reporting(E_ALL);
ini_set("display_errors", 1);

?>
<html>
<head>
    <title></title>
    <link rel="stylesheet" href="../vendor/twbs/bootstrap/dist/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="../vendor/datatables/datatables/media/css/jquery.dataTables.min.css"/>
    <link rel="stylesheet" href="../css/datatables.min.css"/>
    <link rel="stylesheet" href="../css/style.min.css"/>
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
    $tdadmin->loadConfig(__DIR__ . "/szamlak_ceg2.json");
    $tdadmin->addButton(new Button("add"))->setText("Új számla hozzáadása")->addClass("btn btn-primary");
    $tdadmin->addButton(new Button("delete"))->addAction(function($id){

    },Buttons::$ACTION_RUN_WITHOUT_DEFAULT);
    $tdadmin->addButton("teszt")->setLink("{config.baseUrl}?link{teszt} {fizetve} {egyenleg} id:%id% id:{row.id} {city}");
    $tdadmin->addVariable("teszt",1);

    $tdadmin->show(false);

    if($tdadmin->isAjax() && !empty(TableAdmin::$_JSON)){
        ob_end_clean();
        echo json_encode($tdadmin::$_JSON);
        exit();
    }
    echo $tdadmin::$_HTML;
    ?>

</div>
<script type="text/javascript" src="../vendor/components/jquery/jquery.min.js"></script>
<script type="text/javascript" src="../vendor/twbs/bootstrap/dist/js/bootstrap.min.js"></script>
<script type="text/javascript" src="../js/datatables.min.js"></script>

<?php echo TableAdmin::$_JAVASCRIPT;?>
</body>
</html>