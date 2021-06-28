<?php

isDirect();

if (isset($_GET['action']) AND $_GET['action'] == 'clear') {
    // update print queue count object
    echo '<script type="text/javascript">top.document.querySelector(\'#queueCount\').innerHTML = 0</script>';
    utility::jsToastr('Item Barcode', __('Print queue cleared!'), 'success');
    unset($_SESSION['mix_barcodes']);
    exit();
}