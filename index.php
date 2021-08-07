<?php
/**
 * @Created by          : Drajat Hasan
 * @Date                : 2021-06-28 06:37:56
 * @File name           : index.php
 */

use SLiMS\DB;



defined('INDEX_AUTH') OR die('Direct access not allowed!');

// IP based access limitation
require LIB . 'ip_based_access.inc.php';
do_checkIP('smc');
do_checkIP('smc-bibliography');
// start the session
require SB . 'admin/default/session.inc.php';
// set dependency
require SIMBIO . 'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO . 'simbio_GUI/form_maker/simbio_form_table_AJAX.inc.php';
require SIMBIO . 'simbio_GUI/paging/simbio_paging.inc.php';
require SIMBIO . 'simbio_DB/datagrid/simbio_dbgrid.inc.php';
require SIMBIO.'simbio_UTILS/simbio_tokenizecql.inc.php';
require LIB.'biblio_list_model.inc.php';
require LIB.'biblio_list_index.inc.php';
// end dependency

// privileges checking
$can_read = utility::havePrivilege('bibliography', 'r');

if (!$can_read) {
    die('<div class="errorBox">' . __('You are not authorized to view this section') . '</div>');
}

// set page title
$page_title = 'Label Mixcode Color';

// set config
$sysconf['lbc_settings'] = ['chunk' => 2, 'template' => 'right', 'codeType' => 'Barcode', 'marginPage' => '5mm 5mm 5mm 5mm', 'pageBreakAt' => 6, 'autoprint' => 1];
$sysconf['lbc_leftCode'] = ['itemCode' => 'B00017', 'callNumberFontSize' => 'text-sm', 'callNumber' => '7965.555 919 Har n', 'widthBox' => 20, 'heightBox' => 10, 'widthBarcode' => 8,'heightBarcode' => 4, 'topBarcode' => 3.5, 'leftBarcode' => -5];
$sysconf['lbc_rightCode'] = ['itemCode' => 'B00018', 'callNumberFontSize' => 'text-sm', 'callNumber' => '7965.555 919 Har n', 'widthBox' => 20, 'heightBox' => 10, 'widthBarcode' => 8,'heightBarcode' => 4, 'topBarcode' => 3.5, 'leftBarcode' => -5];
$sysconf['lbc_bothCode'] = ['itemCode' => 'B00019', 'callNumberFontSize' => 'text-sm', 'callNumber' => '7965.555 919 Har n', 'widthBox' => 20, 'heightBox' => 10, 'widthBarcode' => 8,'heightBarcode' => 4, 'topBarcode' => 3.5, 'leftBarcode' => -5];
$sysconf['lbc_color'] = ['0XX' => '#ffffff', '1XX' => '#ffffff', '2XX' => '#ffffff', '3XX' => '#ffffff', '4XX' => '#ffffff', '5XX' => '#ffffff','6XX' => '#ffffff', '7XX' => '#ffffff','8XX' => '#ffffff','9XX' => '#ffffff'];

// load settings
utility::loadSettings($dbs);

// helpers
require __DIR__.'/helpers.php';

/* Action Area */
$max_print = 50;

/* RECORD OPERATION */
loadFile('action/generateSession');

// clean print queue
loadFile('action/clearSession');

// print Label
loadFile('action/generateLabel');

// settings page
loadFile('pages/settings');

/* End Action Area */
?>
<div class="menuBox">
    <div class="menuBoxInner memberIcon">
        <div class="per_title">
            <h2><?= $page_title; ?></h2>
        </div>
        <div class="sub_section">
            <div class="btn-group">
                <a target="blindSubmit" href="<?= $_SERVER['PHP_SELF'] . '?' . httpQuery(['action' => 'clear']) ?>" class="notAJAX btn btn-danger mx-1"><?= __('Clear Print Queue') ?></a>
                <a target="blindSubmit" href="<?= $_SERVER['PHP_SELF'] . '?' . httpQuery(['action' => 'print']) ?>" class="notAJAX btn btn-primary mx-1"><?= __('Print Barcodes for Selected Data'); ?></a>
                <a href="<?= $_SERVER['PHP_SELF'] . '?' . httpQuery(['action' => 'settings']) ?>" class="notAJAX btn btn-default openPopUp mx-1" width="780" height="500" title="<?= __('Change print barcode settings'); ?>"><?= __('Change print barcode settings'); ?></a>
            </div>
            <form name="search" action="<?= $_SERVER['PHP_SELF'] . '?' . httpQuery() ?>" id="search" method="get" class="form-inline"><?= __('Search'); ?>
                <input type="text" name="keywords" class="form-control col-md-3"/>
                <input type="submit" id="doSearch" value="<?= __('Search'); ?>" class="s-btn btn btn-default"/>
            </form>
        </div>
        <div class="infoBox">
        <?php
        echo __('Maximum').' <strong class="text-danger">'.$max_print.'</strong> '.__('records can be printed at once. Currently there is').' ';
        if (isset($_SESSION['mix_barcodes'])) {
            echo '<strong id="queueCount" class="text-danger">'.count($_SESSION['mix_barcodes']).'</strong>';
        } else { echo '<strong id="queueCount" class="text-danger">0</strong>'; }
        echo ' '.__('in queue waiting to be printed.');
        ?>
        </div>
    </div>
</div>
<script>
    // set variable
    let popUp = document.querySelector('.openPopUp')
    let w = parseInt(window.innerWidth) - 100
    let h = parseInt(window.innerHeight) - 100
    
    // set attribute
    popUp.setAttribute('width', w)
    popUp.setAttribute('height', h)
</script>
<?php
/* Datagrid area */
/**
 * table spec
 * ---------------------------------------
 * Tuliskan nama tabel pada variabel $table_spec. Apabila anda 
 * ingin melakukan pegabungan banyak tabel, maka anda cukup menulis kan
 * nya saja layak nya membuat query seperti biasa
 *
 * Contoh :
 * - dummy_plugin as dp left join non_dummy_plugin as ndp on dp.id = ndp.id ... dst
 *
 */
$table_spec = 'item LEFT JOIN search_biblio AS `index` ON item.biblio_id=`index`.biblio_id';

// membuat datagrid
$datagrid = new simbio_datagrid();

/** 
 * Menyiapkan kolom
 * -----------------------------------------
 * Format penulisan sama seperti anda menuliskan di query pada phpmyadmin/adminer/yang lain,
 * hanya di SLiMS anda diberikan beberapa opsi seperti, penulisan dengan gaya multi parameter,
 * dan gaya single parameter.
 *
 * Contoh :
 * - Single Parameter : $datagrid->setSQLColumn('id', 'kolom1, kolom2, kolom3'); // penulisan langsung
 * - Single Parameter : $datagrid->setSQLColumn('id', 'kolom1', 'kolom2', 'kolom3'); // penulisan secara terpisah
 *
 * Catatan :
 * - Jangan lupa menyertakan kolom yang bersifat PK (Primary Key) / FK (Foreign Key) pada urutan pertama,
 *   karena kolom tersebut digunakan untuk pengait pada proses lain.
 */
 $datagrid->setSQLColumn('item.item_code',
 'item.item_code AS \''.__('Item Code').'\'',
 'index.title AS \''.__('Title').'\'',
 'item.call_number AS \'Nomor Panggil\'');

$datagrid->setSQLorder('item.last_update DESC');

/** 
 * Pencarian data
 * ------------------------------------------
 * Bagian ini tidak lepas dari nama kolom dari tabel yang digunakan.
 * Jadi, untuk pencarian yang lebih banyak anda dapat menambahkan kolom pada variabel
 * $criteria
 *
 * Contoh :
 * - $criteria = ' kolom1 = "'.$keywords.'" OR kolom2 = "'.$keywords.'" OR kolom3 = "'.$keywords.'"';
 * - atau anda bisa menggunakan query anda.
 */
if (isset($_GET['keywords']) AND $_GET['keywords']) {
    $keywords = utility::filterData('keywords', 'get', true, true, true);
    $searchable_fields = array('title', 'author', 'subject', 'itemcode');
    $search_str = '';
    // if no qualifier in fields
    if (!preg_match('@[a-z]+\s*=\s*@i', $keywords)) {
      foreach ($searchable_fields as $search_field) {
        $search_str .= $search_field.'='.$keywords.' OR ';
      }
    } else {
      $search_str = $keywords;
    }
    $biblio_list = new biblio_list($dbs, 20);
    $criteria = $biblio_list->setSQLcriteria($search_str);
}

if (isset($criteria)) {
    $datagrid->setSQLcriteria('('.$criteria['sql_criteria'].')');
}

/** 
 * Atribut tambahan
 * --------------------------------------------
 * Pada bagian ini anda dapat menentukan atribut yang akan muncul pada datagrid
 * seperti judul tombol, dll
 */
// set table and table header attributes
$datagrid->table_attr = 'id="dataList" class="s-table table"';
$datagrid->table_header_attr = 'class="dataListHeader" style="font-weight: bold;"';
// edit and checkbox property
$datagrid->edit_property = false;
$datagrid->chbox_property = array('itemID', __('Add'));
$datagrid->chbox_action_button = __('Add To Print Queue');
$datagrid->chbox_confirm_msg = __('Add to print queue?');
$datagrid->column_width = array('10%', '85%');
// set checkbox action URL
$datagrid->chbox_form_URL = $_SERVER['PHP_SELF'] . '?' . httpQuery();
// put the result into variables
$datagrid_result = $datagrid->createDataGrid(DB::getInstance('mysqli'), $table_spec, 20, true); // object database, spesifikasi table, jumlah data yang muncul, boolean penentuan apakah data tersebut dapat di edit atau tidak.
if (isset($_GET['keywords']) AND $_GET['keywords']) {
    $msg = str_replace('{result->num_rows}', $datagrid->num_rows, __('Found <strong>{result->num_rows}</strong> from your keywords'));
    echo '<div class="infoBox">' . $msg . ' : "' . htmlspecialchars($_GET['keywords']) . '"<div>' . __('Query took') . ' <b>' . $datagrid->query_time . '</b> ' . __('second(s) to complete') . '</div></div>';
}
// menampilkan datagrid
echo $datagrid_result;
/* End datagrid */
