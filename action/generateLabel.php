<?php
/**
 * @author Drajat Hasan
 * @email drajathasan20@gmail.com
 * @create date 2021-06-29 08:15:59
 * @modify date 2024-05-19 20:00:27
 * @desc [description]
 */

isDirect();

if (isset($_GET['action']) AND $_GET['action'] == 'print') {
  // check if label session array is available
  if (!isset($_SESSION['mix_barcodes'])) {
    utility::jsToastr('Item Barcode', __('There is no data to print!'), 'error');
    die();
  }
  if (count($_SESSION['mix_barcodes']) < 1) {
    utility::jsToastr('Item Barcode', __('There is no data to print!'), 'error');
    die();
  }

  // global settings
  $globalSettings = $sysconf['lbc_settings'];
  // set settings per Template
  $settingsTemplate = $sysconf['lbc_' . strtolower($globalSettings['template']) . 'Code'];
  // color pallet
  $palletColor = $sysconf['lbc_color'];
  // set Template dir
  $templateDir = __DIR__.'/../template/'.strtolower($globalSettings['template']) . 'Code_template.php';

  if (!isset($settingsTemplate))
  {
    utility::jsToastr('Item Barcode', 'Template tidak tersedia!', 'error');
    die();
  }

  if (!file_exists($templateDir))
  {
    utility::jsToastr('Item Barcode', 'File template tidak tersedia!', 'error');
    die();
  }

  // concat all ID together
  $item_ids = '';
  foreach ($_SESSION['mix_barcodes'] as $id) {
    $item_ids .= '\''.$id.'\',';
  }
  // strip the last comma
  $item_ids = substr_replace($item_ids, '', -1);
  // send query to database
  $item_q = $dbs->query('SELECT b.title, i.item_code, i.call_number, b.biblio_id FROM item AS i
    LEFT JOIN biblio AS b ON i.biblio_id=b.biblio_id
    WHERE i.item_code IN('.$item_ids.')');
  $item_data_array = array();
  while ($item_d = $item_q->fetch_assoc()) {
    if ($item_d['item_code']) {
      $item_data_array[] = $item_d;
    }
  }

  // all data
  $allData = count($item_data_array);

  // chunk barcode array
  $chunked_barcode_arrays = array_chunk($item_data_array, $globalSettings['chunk']);
  
  // include main template
  include __DIR__.'/../template/'.strtolower($globalSettings['template']) . 'Code_template.php';

  // unset the session
  unset($_SESSION['mix_barcodes']);
  // write to file
  $print_file_name = 'label_mixcode_warna_gen_print_result_'.strtolower(str_replace(' ', '_', $_SESSION['uname'])).'.html';
  $file_write = @file_put_contents(UPLOAD.$print_file_name, $html_str);
  if ($file_write) {
    // update print queue count object
    echo '<script type="text/javascript">top.document.querySelector(\'#queueCount\').innerHTML = 0</script>';
    // open result in window
    echo '<script type="text/javascript">top.$.colorbox({href: "'.SWB.FLS.'/'.$print_file_name.'", iframe: true, width: 1341, height: 597, title: "Label Barcode Warna"})</script>';
  } else { utility::jsToastr('Item Barcode', str_replace('{directory}', SB.FLS, __('ERROR! Item barcodes failed to generate, possibly because {directory} directory is not writable')), 'error'); }
  exit();
}