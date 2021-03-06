<?php if(isset($this->config["addButton"]) && $this->config["addButton"]):?>
<a href="<?=$this->config["url"]."?ta_method=add&key=".$this->key?>">Új sor hozzáadása</a>
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
    <tbody>
            <?php foreach ($this->data AS $row):?>
        <tr<?=(!empty($this->trClassMethod)?" class=\"".$this->trClassMethod[0]($row)."\"":"")?>>
            <?php foreach ($this->config["cols"] AS $sor): if(!isset($sor["visible"]) || $sor["visible"]):?>
            <td><?=$row[$sor["alias"]]?></td>
            <?php endif;endforeach;?>            
            <?php if((isset($this->config["form"]) && !empty($this->config["form"])) || $this->custom_buttons>0 ):?>                        
            <td>
                <?php if((isset($this->config["form"]) && !empty($this->config["form"])) && $this->runMethods("delete",$row)):?>
                [<a href="<?=$this->config["url"]."?ta_method=delete&key=".$this->key."&id=".$row[$this->config["id"]]?>" onclick="return confirm('Biztos hogy törli?')">Töröl</a>]
                <?php endif;?>
                <?php if((isset($this->config["form"]) && !empty($this->config["form"])) && $this->runMethods("edit",$row)):?>
                [<a href="<?=$this->config["url"]."?ta_method=edit&key=".$this->key."&id=".$row[$this->config["id"]]?>">Szerkeszt</a>]
                <?php endif;?>
                <?php foreach ($this->buttons AS $button):if($this->runMethods($button["name"],$row)):?>
                [<a href="<?=$this->config["url"]."?ta_method=".$button["name"]."&key=".$this->key."&id=".$row[$this->config["id"]]?>" target="<?=$button["target"]?>"><?=$button["text"]?></a>]
                <?php endif;endforeach;?>
            </td>
            <?php endif; ?>
            </tr>            
            <?php endforeach; ?>
    </tbody>
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