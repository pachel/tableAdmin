<?php
/**
 * @var \pachel\TableAdmin $this
 */
?>
(function () {
    ['excelHtml5', 'pdfHtml5', 'csvHtml5'].forEach(function (buttonType) {
        if ($.fn.dataTable.ext.buttons[buttonType]) {
            let originalAction = $.fn.dataTable.ext.buttons[buttonType].action;

            $.fn.dataTable.ext.buttons[buttonType].action = function (e, dt, button, config) {
                let self = this;
                let settings = dt.settings()[0];

                if (!settings.oFeatures.bServerSide) {
                    originalAction.call(self, e, dt, button, config);
                    return;
                }

                if (settings.oApi && settings.oApi._fnProcessingDisplay) {
                    settings.oApi._fnProcessingDisplay(settings, true);
                }

                let params = dt.ajax.params();
                params.start = 0;
                params.length = -1; // Minden sor lekérése

                let ajaxConfig = settings.ajax;
                let ajaxUrl = typeof ajaxConfig === 'string'
                    ? ajaxConfig
                    : (ajaxConfig && ajaxConfig.url ? ajaxConfig.url : dt.ajax.url());
                let ajaxType = (ajaxConfig && (ajaxConfig.type || ajaxConfig.method)) ? (ajaxConfig.type || ajaxConfig.method) : 'POST';

                $.ajax({
                    url: ajaxUrl,
                    type: ajaxType,
                    data: params,
                    dataType: 'json',
                    success: function (json) {
                        if (settings.oApi && settings.oApi._fnProcessingDisplay) {
                            settings.oApi._fnProcessingDisplay(settings, false);
                        }

                        // 1. Elmentjük a jelenlegi oldal állapotát és a megjelenített sorokat
                        let oldStart = settings._iDisplayStart;
                        let oldLength = settings._iDisplayLength;
                        let oldRecordsDisplay = settings._iRecordsDisplay;
                        let oldData = dt.rows().data().toArray();

                        // 2. Ideiglenesen betöltjük a teljes letöltött adatot a táblázatba (draw nélkül)
                        dt.clear();
                        dt.rows.add(json.data);
                        settings._iDisplayStart = 0;
                        settings._iDisplayLength = json.data.length;

                        // 3. Lefuttatjuk az exportálót az összes sorra
                        originalAction.call(self, e, dt, button, config);

                        // 4. Visszaállítjuk a korábbi oldalt és az eredeti sorokat
                        dt.clear();
                        dt.rows.add(oldData);
                        settings._iDisplayStart = oldStart;
                        settings._iDisplayLength = oldLength;
                        settings._iRecordsDisplay = oldRecordsDisplay;

                        // Újrarajzoljuk a felületet az eredeti nézettel (AJAX kérés nélkül)
                        dt.draw(false);
                    },
                    error: function (xhr, status, error) {
                        if (settings.oApi && settings.oApi._fnProcessingDisplay) {
                            settings.oApi._fnProcessingDisplay(settings, false);
                        }
                        alert('Hiba történt az export adatok letöltésekor: ' + error);
                    }
                });
            };
        }
    });
})();
let table = $('#datatables').DataTable({
    processing: true,
    serverSide: true,
    searchDelay: 500,
    stateSave: true,
    <?php if(isset($this->config["datatables"])){
        echo preg_replace("/^\{|\}$/","",json_encode($this->config["datatables"],JSON_PRETTY_PRINT)).",";
    }
    ?>
    ajax: {
        url: '<?=$this->config["url"] . "?" . url("ajax_api=1")?>',
        type: 'POST',
        data: function (d) {
            let formData = $('#tableAdminForm').serializeArray();
            d["ta_extra"] = {};
            $.each(formData, function (i, field) {
                d["ta_extra"][field.name] = field.value;
            });
            d["first"] = 1;
        }
    },
// <"dt-top-bar"lf> => létrehoz egy .dt-top-bar divet, benne a (l)ength és (f)ilter elemekkel
// r = processing, t = table
// <"dt-bottom-bar"ip> => létrehoz egy .dt-bottom-bar divet az (i)nfo és (p)aginate elemekkel
    //dom: '<"dt-top-bar"lf>rt<"dt-bottom-bar"ip>',
    columns: [<?php $c = 0;$cols = $this->columns->getColumnAliases(); foreach ($cols as $col) {
        if ($c > 0)
            echo ",";
        echo "{ data:'" . $col . "' }";
        $c++;

    }
        if ((isset($this->config["form"]) && !empty($this->config["form"])) || $this->custom_buttons > 0) {
            echo ",{data: 'tb___buttons'}";
        }
        ?>],
    initComplete: function () {
        let state = this.api().state.loaded();
        if (state && state.search && state.search.search) {
            $('.dataTables_filter input').val(state.search.search);
        }
    },
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