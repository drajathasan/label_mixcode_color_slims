<?php

isDirect();

if (isset($_POST['itemID']) AND !empty($_POST['itemID']) AND isset($_POST['itemAction'])) {
    if (!is_array($_POST['itemID'])) {
      // make an array
      $_POST['itemID'] = array((integer)$_POST['itemID']);
    }
    // loop array
    if (isset($_SESSION['mix_barcodes'])) {
      $print_count = count($_SESSION['mix_barcodes']);
    } else {
      $print_count = 0;
    }
    // create AJAX request
    echo '<script type="text/javascript">';
    // loop array
    foreach ($_POST['itemID'] as $itemID) {
      if ($print_count == $max_print) {
        $limit_reach = true;
        break;
      }
      if (isset($_SESSION['mix_barcodes'][$itemID])) {
        continue;
      }
      if (!empty($itemID)) {
        // add to sessions
        $_SESSION['mix_barcodes'][$itemID] = $itemID;
        $print_count++;
      }
    }
    echo 'top.document.querySelector(\'#queueCount\').innerHTML = \''.$print_count.'\'';
    echo '</script>';
    // update print queue count object
    sleep(2);
    if (isset($limit_reach)) {
      $msg = str_replace('{max_print}', $max_print, __('Selected items NOT ADDED to print queue. Only {max_print} can be printed at once'));
      utility::jsToastr('Item Barcode', $msg, 'warning');
    } else {
      utility::jsToastr('Item Barcode', __('Selected items added to print queue'), 'success');
    }
    exit();
}