<script type="text/javascript">
    window.onload = function () {
        if (typeof $.fn.select2 === "function") {
            $(".select2").select2();
        }
        else{
            console.error("Select2 is not loaded. Please include the Select2 library.");
        }
    }
</script>
