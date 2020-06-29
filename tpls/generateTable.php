<?php if(isset($this->config["add_button"]) && $this->config["add_button"]):?>
<a href="<?=$this->config["url"]."?ta_method=add&key=".$this->key?>">Új sor hozzáadása</a>
<?php endif;?>
<table id="datatables" class="table table-bordered table-striped">
    <thead>
        <tr>
            <?php foreach ($this->config["cols"] AS $sor): if(!isset($sor["visible"]) || $sor["visible"]):?>
            <th><?=$sor["text"]?></th>
            <?php endif;endforeach; ?>
            <th>Műveletek</th>
        </tr>
    </thead>
    <tbody>
            <?php foreach ($this->data AS $row):?>
            <tr>
            <?php foreach ($this->config["cols"] AS $sor): if(!isset($sor["visible"]) || $sor["visible"]):?>
            <td><?=$row[$sor["alias"]]?></td>
            <?php endif;endforeach;?>            
            <td>
                <?php if($this->runMethods("delete",$row)):?>
                <a href="<?=$this->config["url"]."?ta_method=delete&key=".$this->key."&id=".$row[$this->config["id"]]?>" onclick="return confirm('Biztos hogy törli?')">Töröl</a>
                <?php endif;?>
                <a href="<?=$this->config["url"]."?ta_method=edit&key=".$this->key."&id=".$row[$this->config["id"]]?>">Szerkeszt</a>
                <?php foreach ($this->buttons AS $button):?>
                <a href="<?=$this->config["url"]."?ta_method=".$button["name"]."&key=".$this->key."&id=".$row[$this->config["id"]]?>"><?=$button["text"]?></a>
                <?php endforeach;?>
            </td>
            </tr>            
            <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
             <?php foreach ($this->config["cols"] AS $sor): if(!isset($sor["visible"]) || $sor["visible"]):?>
            <th><?=$sor["text"]?></th>
            <?php endif;endforeach; ?>
            <th>Műveletek</th>
        </tr>
    </tfoot>
</table>