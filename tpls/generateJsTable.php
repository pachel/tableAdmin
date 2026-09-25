<?php
/**
 * @var \pachel\TableAdmin $this
 */

?>
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