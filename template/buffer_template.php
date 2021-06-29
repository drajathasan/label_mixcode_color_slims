<?php
isDirect();

// set path
$assetPath = SWB . 'plugins/' . pluginDirName();
// set js type
$js = (ENVIRONMENT === 'development') ? '.js' : '.min.js';

?>
<!DOCTYPE Html>
<html>
    <head>
        <title><?= $title ?></title>
        <link href="<?= $assetPath . '/assets/css/tailwind.min.css'?>" rel="stylesheet"/>
        <style>
            .rot90 {
                transform: rotate(90deg) !important;
            }
            .rot270 {
                transform: rotate(270deg) !important;
            }
        </style>
    </head>
    <body>
        <div id="content">
            <?= $content; ?>
        </div>
        <script src="<?= $assetPath . '/assets/js/vue' . $js?>"></script>
        <script src="<?= $assetPath . '/assets/js/JsBarcode.all.min.js'?>"></script>
        <script src="<?= $assetPath . '/assets/js/qrcode.min.js'?>"></script>
        <script src="<?= $assetPath . '/assets/js/vanilla-picker.min.js'?>"></script>
        <script type="module" src="<?= $assetPath . '/assets/js/app.js?ver='.date('this')?>"></script>
    </body>
</html>