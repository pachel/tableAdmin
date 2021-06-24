
<script type="text/javascript">
    var dt;
    window.onload = function () {
    dt = $("#datatables").DataTable(
        <?php if(isset($this->config["datatables"])){
            echo json_encode($this->config["datatables"],JSON_PRETTY_PRINT);
        }
        else{

        }
        $this->getDirName();
        ?>
    );
}
</script>

