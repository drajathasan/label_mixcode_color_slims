<?php
/**
 * @author Drajat Hasan
 * @email drajathasan20@gmail.com
 * @create date 2021-06-29 08:17:10
 * @modify date 2021-09-24 10:45:36
 * @desc [description]
 */

isDirect();

$_POST = jsonPost();

if (isset($_POST['type']))
{
    // set key
    $key = $_POST['type'];
    // check type
    if (!isset($sysconf['lbc_'.$key])) jsonResponse(['status' => false, 'msg' => 'Data tidak ada!']);

    // template available
    $templateCode = ['left', 'right', 'Both','settings','color'];
    
    // set data
    $data = $sysconf['lbc_'.$key];
    // unset data
    unset($_POST['type']);
    // serialize data
    $data = $_POST;
    // Template check
    if (!in_array(str_replace('Code', '', $key), $templateCode)) jsonResponse(['status' => false, 'msg' => 'Template ' . str_replace('Code', '', $key) . ' tidak ada perhatikan besar kecilnya karakter yang anda masukan.']);
    $serializeData = serialize($data);
    // set instance
    $dbs = SLiMS\DB::getInstance();
    // check data
    $checkData = $dbs->prepare('select setting_name from setting where setting_name = ?');
    $checkData->execute(['lbc_'.$key]);
    // get number
    $isDataAvailable = $checkData->rowCount();
    

    if (!$isDataAvailable)
    {
        $process = $dbs
                    ->prepare('insert into `setting` (setting_name, setting_value) values (?,?)')
                    ->execute(['lbc_'.$key, $serializeData]);
    }
    else
    {
        $process = $dbs
                    ->prepare('update `setting` set setting_value = ? where setting_name = ?')
                    ->execute([$serializeData, 'lbc_'.$key]);
    }

    if ($process)
    {
        jsonResponse(['status' => true, 'msg' => 'Data berhasil disimpan!']);
    }
    else
    {
        jsonResponse(['status' => false, 'msg' => 'Gagal menyimpan data']);
    }
}

// Modify for detail
$codeType = $sysconf['lbc_settings']['codeType'];
$settings = [];
foreach ($sysconf['lbc_settings'] as $config => $value) {
    // check
    switch ($config) {
        case 'template':
            $label = 'template tersedia (right,left,& both)';
            break;
        
        case 'codeType':
            $label = 'tipe pola tersedia (Barcode,& Qrcode)';
            break;
        case 'pageBreakAt':
            $label = 'Pisah halaman pada baris ke - ';
            break;
        case 'marginPage':
            $label = 'Margin Halaman Cetak (Atas,Kanan,Bawah,Kiri)';
            break;
        default:
            $label = $config;
            break;
    }
    // set up settings
    $settings[] = ['key' => $config, 'label' => ucwords($label), 'value' => str_replace('"', '', $value)];
}

// If request is action and action = settings
if (isset($_GET['action']) && $_GET['action'] === 'settings'):
    ob_start();
    ?>
    <!-- Vue Area-->

    <!-- Form Options -->
    <h3 class="font-bold p-3 text-gray-800 inline-block">Form pengaturan</h3>
    <select v-on:change="section = $event.target.value" class="p-2">
        <option v-for="option in [['settings','Utama'],['rightCode','Label Kanan'],['leftCode','Label Kiri'],['bothCode','Label Kanan Kiri'],['selectColor','Warna Per Klasifikasi']]" :value="option[0]">{{ option[1] }}</option>
    </select>

    <!-- Setting -->
    <Settings v-if="section === 'settings'" code-type="<?= $codeType ?>" fields="<?= jsonProps($settings) ?>"></Settings>

    <!-- Right Code -->
    <Rightcode v-if="section === 'rightCode'" code-type="<?= $codeType ?>" measurement="<?= jsonProps($sysconf['lbc_rightCode']) ?>" library-name="<?= $sysconf['library_name'] ?>"></Rightcode>

    <!-- Left Code -->
    <Leftcode v-if="section === 'leftCode'" code-type="<?= $codeType ?>" measurement="<?= jsonProps($sysconf['lbc_leftCode']) ?>" library-name="<?= $sysconf['library_name'] ?>"></Leftcode>

    <!-- Both Code -->
    <Bothcode v-if="section === 'bothCode'" code-type="<?= $codeType ?>" measurement="<?= jsonProps($sysconf['lbc_bothCode']) ?>" library-name="<?= $sysconf['library_name'] ?>"></Bothcode>

    <!-- Color -->
    <Selectcolor v-if="section === 'selectColor'" color-string="<?= jsonProps($sysconf['lbc_color']) ?>"></Selectcolor>

    <!-- End Vue Area -->
    <?php
    // set buffer
    $content = ob_get_clean();
    // load file
    loadFile('template/buffer_template', 'include');
    exit;
endif;
?>
