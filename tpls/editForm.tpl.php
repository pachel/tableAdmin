<?php
/**
 * @var array $row
 * @var Field[] $fields
 * @var \Pachel\TableAdmin\Models\ContentGenerator $this
 */

use Pachel\TableAdmin\Models\Field;
$link  = $this->_getLinkForNewForm();
?>
<form method="post"<?=(!empty($link)?" action=\"".$link."\"":"")?>>
    <?php
    $ct = 0;
    foreach ($fields as $index => $field):
        if($field->type == "hidden"){
            echo "<input type=\"hidden\" name=\"".$field->alias."\" value=\"".$row[$field->alias]."\" />";
            continue;
        }
        if($ct == 0){
            echo "<div class=\"row\">";
        }
        $ct+=(int)$field->bt_num;
    ?>
    <div class="col<?=(empty($field->bt_num)?"":"-".$field->bt_num)?>">
        <div class="form-group mb-3">
            <label><?=$field->text?></label>
            <?php if($field->type == "textarea"): ?>
            <textarea <?=$this->_commonForInput($row,$field)?>></textarea>
            <?php elseif($field->type == "select"): ?>
            <?php elseif ($field->type == "checkbox"): ?>
            <?php elseif ($field->type == "select2"): ?>
            <?php else:?>
            <input type="<?=$field->type?>" <?=$this->_commonForInput($row,$field)?> value="<?=$this->_value($row,$field)?>">
            <?php endif; ?>
        </div>
    </div>
    <?php
        if($ct >= $this->_bs_maxCols || $index == count($fields)-1){
            echo "</div>";
            $ct = 0;
        }
    endforeach;
    ?>
    <div class="row">
        <div class="col-3">
            <div class="form-group">
                <a class="btn btn-info form-control" href="<?= $this->_config->getBaseUrl() ?>">Vissza</a>
            </div>
        </div>
        <div class="col-9">
            <div class="form-group">
                <button class="btn btn-success form-control" type="submit">Ment</button>
            </div>
        </div>
    </div>
</form>