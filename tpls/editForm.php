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
                <div class="col">
                    <div class="form-group">
                        <label><?=$col["text"]?></label>
                        <?php if($col["type"] == "textarea"):?>
                        <textarea class="form-control" id="ta_form_<?=$col["name"]?>" placeholder="<?=$col["text"]?>" name="<?=$col["name"]?>"<?=(isset($col["reqired"]) && $col["reqired"])?" required=\"true\"":""?>>
                            <?=$result[$col["name"]]?>
                        </textarea>
                        <?php else: ?>
                        <input type="<?=$col["type"]?>" class="form-control" id="ta_form_<?=$col["name"]?>" placeholder="<?=$col["text"]?>" name="<?=$col["name"]?>"<?=(isset($col["reqired"]) && $col["reqired"])?" required=\"true\"":""?> value="<?=$result[$col["name"]]?>">
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