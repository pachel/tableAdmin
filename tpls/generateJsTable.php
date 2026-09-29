<?php
/**
 * @var \pachel\TableAdmin $this
 */

?>
<?php if(isset($this->config["addButton"]) && ($this->config["addButton"] || is_array($this->config["addButton"]))):
    $url = (preg_match("/\?/",$this->config["url"])?"&":"?").url("ta_method=add&key=".$this->key);
    ?>

    <a href="<?=$this->config["url"].$url?>" class="ta-uj-sor"><?=(is_array($this->config["addButton"])?$this->config["addButton"][1]:(is_string($this->config["addButton"])?$this->config["addButton"]:"Új sor hozzáadása"))?></a>
<?php endif;?>
<table id="datatables" class="<?=(isset($this->config["tableClass"])?$this->config["tableClass"]:"table table-bordered table-striped display")?>">
    <thead>
    <tr>
        <?php

        $cols = $this->columns->getVisibleColumns();
        foreach ($cols as $col) {
            echo "<th>$col->text</th>";
        }
        if((isset($this->config["form"]) && !empty($this->config["form"])) || $this->custom_buttons > 0){
            echo "<th></th>";
        }
        ?>
    </tr>
    </thead>
    <tbody></tbody>
    <tfoot>
    <tr>
        <?php
        $cols = $this->columns->getVisibleColumns();
        foreach ($cols as $col) {
            echo "<th>$col->text</th>";
        }
        if((isset($this->config["form"]) && !empty($this->config["form"])) || $this->custom_buttons > 0) {
            echo "<th></th>";
        }
        ?>
    </tr>
    </tfoot>
</table>