<?php
/**
 * @var \pachel\TableAdmin $this
 */
?>

let table = $('#datatables').DataTable({
    processing: true,
    serverSide: true,
    searchDelay: 500,
    ajax: {
        url: '<?=$this->config["url"] . "?" . url("ajax_api=1")?>',
        type: 'POST',
        data: function (d) {
            let formData = $('#tableAdminForm').serializeArray();
            $.each(formData, function (i, field) {
                d[field.name] = field.value;
            });
            d["first"] = 1;
        }
    },
    // <"dt-top-bar"lf> => létrehoz egy .dt-top-bar divet, benne a (l)ength és (f)ilter elemekkel
    // r = processing, t = table
    // <"dt-bottom-bar"ip> => létrehoz egy .dt-bottom-bar divet az (i)nfo és (p)aginate elemekkel
    dom: '<"dt-top-bar"lf>rt<"dt-bottom-bar"ip>',
    columns: [<?php $c = 0;$cols = $this->getVisibleCols(); foreach ($cols as $col) {
        if ($c > 0) {
            echo ",";
        }

            echo "{ data:'" . $col["alias"] . "' }";
            $c++;

    }?>],
    createdRow: function (row, data, dataIndex) {

    }

});
$('.dataTables_filter input')
    .unbind()
    .bind('keyup', function (e) {
        if (e.keyCode === 13) { // 13 = Enter gomb
            table.search(this.value).draw();
        }
    });