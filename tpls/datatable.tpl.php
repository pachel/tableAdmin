<?php
/**
 * @var \Pachel\TableAdmin\Models\ContentGenerator $this
 * @var \Pachel\TableAdmin\Models\columnModel[] $headers
 */
$add = $this->_config->Buttons->getAddButtonHTML();
if (!empty($add)) {
    echo $add;
}
?>
<table id="datatables" class="table table-bordered table-striped display" style="width: 100%">
    <thead>
    <tr>
        <?php
        foreach ($headers as $header) {
            echo "<th>" . $header->text . "</th>";
        }
        ?>
    </tr>
    </thead>
    <tbody>
        <?php
        if (!empty($rows)):
            foreach ($rows as $row):
                echo "<tr>";
                foreach ($headers as $header) {
                    echo "<td>" . $row[$header->alias] . "</td>";
                }
                echo "</tr>";
            endforeach;
        endif;
        ?>
    </tbody>
    <thead>

    </thead>
</table>
