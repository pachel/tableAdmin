<?php
/**
 * @var \Pachel\TableAdmin\Models\ContentGenerator $this
 * @var \Pachel\TableAdmin\Models\Field[] $inputs
 */
if (!empty($inputs)):
    $ct = 0;
    $cct = -1;
    ?>
    <fieldset class="ta-search">
        <legend>Keresés</legend>
        <form id="tableAdminForm">
            <?php
            foreach ($inputs as $field):
                if ($ct == 0 || $cct != $field->__row_number) {
                    $cct = $field->__row_number;
                    echo "<div class=\"row\">";
                }
                $ct += (is_null($field->bt_num) ? 1 : $field->bt_num);
                ?>
                <div class="col<?= (empty($field->bt_num) ? "" : "-" . $field->bt_num) ?>">
                    <div class="form-group mb-3">
                        <label><?= ($field->type!="send" && $field->type!="reset"?$field->text:"&nbsp;") ?></label>
                        <?php if ($field->type == "textarea"): ?>
                            <textarea <?= $this->_commonForInput($post, $field) ?>><?= $field->value ?></textarea>
                        <?php elseif ($field->type == "select" || $field->type == "select2"): ?>
                            <select <?= $this->_commonForInput($post, $field) ?>>
                                <?php
                                foreach ($field->data as $value) {
                                    echo "<option value=\"" . $value->value . "\"" . ($value->default ? " selected" : "") . (isset($value->disabled) && $field->require ? " disabled" : "") . ">" . $value->text . "</option>";
                                }
                                ?>
                            </select>
                        <?php elseif($field->type == "send"): ?>
                        <button type="button" class="<?=$field->classes??"btn btn-primary form-control"?>" id="sendSearch"><?=$field->text?></button>
                        <?php elseif($field->type == "reset"): ?>
                        <button type="reset" class="<?=$field->classes??"btn btn-secondary form-control"?>" id="clearBtn"><?=$field->text?></button>
                        <?php else: ?>
                            <input type="<?= $field->type ?>" <?= $this->_commonForInput($post, $field) ?>
                                   value="<?= $field->value ?>">
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </form>
    </fieldset>
<?php endif; ?>