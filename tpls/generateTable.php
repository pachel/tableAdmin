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
                <a href="<?=$this->config["url"]."?ta_method=delete&id=".$row[$this->config["id"]]?>">Töröl</a>
                <?php endif;?>
                <a href="<?=$this->config["url"]."?ta_method=edit&id=".$row[$this->config["id"]]?>">Szerkeszt</a>
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