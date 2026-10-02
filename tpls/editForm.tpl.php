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
    $cct = -1;
    foreach ($fields as $index => $field):

        if($field->type == "hidden"){
            echo "<input type=\"hidden\" name=\"".$field->alias."\" value=\"".(empty($row)?$field->default:$row[$field->alias])."\" />";
            continue;
        }
        if($ct == 0 || $cct!=$field->__row_number){
            $cct = $field->__row_number;
            echo "<div class=\"row\">";
        }
        $ct+=(is_null($field->bt_num)?1:$field->bt_num);
    ?>
    <div class="col<?=(empty($field->bt_num)?"":"-".$field->bt_num)?>">
        <div class="form-group mb-3">
            <label><?=$field->text?></label>
            <?php if($field->type == "textarea"): ?>
            <textarea <?=$this->_commonForInput($row,$field)?>><?=$field->value?></textarea>
            <?php elseif($field->type == "select" || $field->type == "select2"): ?>
            <select <?=$this->_commonForInput($row,$field)?>>
                <?php
                foreach ($field->data AS $value){
                    echo "<option value=\"".$value->value."\"".($value->default?" selected":"").(isset($value->disabled) && $field->require?" disabled":"").">".$value->text."</option>";
                }
                ?>
            </select>

            <?php else:?>
            <input type="<?=$field->type?>" <?=$this->_commonForInput($row,$field)?> value="<?=$field->value?>">
            <?php endif; ?>
        </div>
    </div>
    <?php
        if($ct >= $this->_bs_maxCols || $index == count($fields)-1 || $cct!=$fields[$index+1]->__row_number){
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