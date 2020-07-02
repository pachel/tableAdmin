<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
?>
<form method="post">
    <?php foreach ($this->config["form"] AS $row): ?>
        <div class="row">
            <?php foreach ($row AS $col): ?>
            <div class="col<?=(isset($col["bt_num"])?"-".$col["bt_num"]:"")?>">
                    <div class="form-group">
                        <label><?=$col["text"]?></label>
                        <?php if($col["type"] == "textarea"):?>
                        <textarea class="form-control" id="ta_form_<?=$col["name"]?>" placeholder="<?=$col["text"]?>" name="<?=$col["name"]?>"<?=(isset($col["required"]) && $col["required"])?" required=\"true\"":""?>><?=(isset($col["value"])?$col["value"]:(isset($result[$col["name"]])?$result[$col["name"]]:""))?></textarea>
                        <?php elseif($col["type"] == "select"): ?>
                        <select class="form-control" id="ta_form_<?=$col["name"]?>" name="<?=$col["name"]?>"<?=(isset($col["required"]) && $col["required"])?" required=\"true\"":""?>>
                        <?php if(isset($col["data"])): foreach ($col["data"] AS $option):?>
                            <option value="<?=$option["value"]?>"<?=(isset($result[$col["name"]])?($result[$col["name"]] == $option["value"]?" selected=\"true\"":""):(isset($option["default"]) && $option["default"]?" selected=\"true\"":""))?>><?=$option["text"]?></option>
                        <?php endforeach;endif;?>
                        </select>                                
                        <?php elseif($col["type"] == "hidden"): ?>
                        <input type="hidden" class="form-control" id="ta_form_<?=$col["name"]?>" name="<?=$col["name"]?>" value="<?=(isset($col["value"])?$col["value"]:(isset($result[$col["name"]])?$result[$col["name"]]:""))?>">
                        <?php else: ?>
                        <input type="<?=$col["type"]?>" class="form-control" id="ta_form_<?=$col["name"]?>" placeholder="<?=$col["text"]?>" name="<?=$col["name"]?>"<?=(isset($col["required"]) && $col["required"])?" required=\"true\"":""?> value="<?=(isset($col["value"])?$col["value"]:(isset($result[$col["name"]])?$result[$col["name"]]:""))?>">
                        <?php endif;?>
                    </div>
                </div>
            <?php endforeach; ?>  
        </div>
    <?php endforeach; ?>  
    <div class="row">
        <div class="col-3">
            <div class="form-group">
                <a class="btn btn-info form-control" href="<?=$this->config["url"]?>">Vissza</a>
            </div>
        </div>
        <div class="col-9">
            <div class="form-group">
                <button class="btn btn-success form-control" type="submit">Ment</button>
            </div>
        </div>
    </div>
</form>