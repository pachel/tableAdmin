<?php if(isset($this->config["addButton"]) && $this->config["addButton"]):?>
    <a href="<?=$this->config["url"].(preg_match("/\?/",$this->config["url"])?"&":"?")."ta_method=add&key=".$this->key?>">Új sor hozzáadása</a>
<?php endif;?>

<table id="datatables" class="table table-bordered table-striped display">
    <thead>
    <tr>
        <?php foreach ($this->config["cols"] AS $sor): if(!isset($sor["visible"]) || $sor["visible"]):?>
            <th><?=$sor["text"]?></th>
        <?php endif;endforeach; ?>
        <?php if((isset($this->config["form"]) && !empty($this->config["form"])) || $this->custom_buttons>0 ):?>
            <th>Műveletek</th>
        <?php endif; ?>
    </tr>
    </thead>
    <?php if(isset($this->config["datatables"]["serverSide"]) && $this->config["datatables"]["serverSide"]): else:?>
        <tbody>
        <?php foreach ($this->data AS $row):?>
            <tr<?=(!empty($this->trClassMethod)?" class=\"".$this->trClassMethod[0]($row)."\"":"")?>>
                <?php foreach ($this->config["cols"] AS $sor): if(!isset($sor["visible"]) || $sor["visible"]):?>
                    <td><?=$row[$sor["alias"]]?></td>
                <?php endif;endforeach;?>
                <?=$this->generateButtons($row)?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    <?php endif;?>
    <tfoot>
    <tr>
        <?php foreach ($this->config["cols"] AS $sor): if(!isset($sor["visible"]) || $sor["visible"]):?>
            <th><?=$sor["text"]?></th>
        <?php endif;endforeach; ?>
        <?php if((isset($this->config["form"]) && !empty($this->config["form"])) || $this->custom_buttons>0 ):?>
            <th>Műveletek</th>
        <?php endif; ?>
    </tr>
    </tfoot>
</table>