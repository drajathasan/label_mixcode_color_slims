<?php
/**
 * @author Drajat Hasan
 * @email drajathasan20@gmail.com
 * @create date 2021-06-29 08:12:31
 * @modify date 2021-06-29 08:12:31
 * @desc Left Template
 */

isDirect();

// set path
$assetPath = SWB . 'plugins/' . pluginDirName();

ob_start();
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

            @media print {
                button {
                    display: none !important
                }
                .pagebreak {
                    clear: both;
                    page-break-after: always;
                }
            }

            @page  
            { 
                size: auto;   /* auto is the initial value */ 

                /* this affects the margin in the printer settings */ 
                margin: <?= $globalSettings['marginPage'] ?>;  
            } 
        </style>
    </head>
    <body>
        <div class="w-full bg-gray-100 h-screen">
            <button class="bg-green-500 p-1 text-white" onClick="self.print()">Print</button>
            <?php
            // set row
            $row = 0;
            // conver comma to dot
            $responsiveWidth = commaToDot(($settingsTemplate['widthBox'] - 5.4));
            // loop chunked barcode
            foreach ($chunked_barcode_arrays as $barcode_array):
                // set flex wrap
                echo '<div class="flex flex-wrap">';
                foreach ($barcode_array as $barcode):
                    // slicing number
                    $callNumber = sliceCallNumber($barcode['call_number']);
                    // shorting and slice it
                    $titleSlice = substr($barcode['title'], 0,5);
                    // get color
                    $color = callNumberColor($barcode['call_number'], $palletColor);
                    // set template
                    echo <<<HTML
                        <div style="width:{$settingsTemplate['widthBox']}em; height: {$settingsTemplate['heightBox']}em; border: 1px solid black; margin-left: 8px; margin-top: 10px">
                            <div class="inline-block float-left mr-2" style="width: 75px;">
                                <small class="pl-2 pt-1">{$titleSlice} ...</small>
                                <img class="inline-block rot270 barcode" jsbarcode-format="CODE128" jsbarcode-value="{$barcode['item_code']}" style="width: {$settingsTemplate['widthBarcode']}em; height: {$settingsTemplate['heightBarcode']}em; margin-top: {$settingsTemplate['topBarcode']}em; margin-left: {$settingsTemplate['leftBarcode']}em; position: absolute;"/>
                            </div>
                            <div class="inline-block" style="width: {$responsiveWidth}em ;height: {$settingsTemplate['heightBox']}em; border-left: 1px solid black">
                                <span class="w-full block text-center text-sm" style="border-bottom: 1px solid black; background-color:{$color}">{$sysconf['library_name']}</span>
                                <span class="w-full block text-center text-md mt-8 font-bold {$settingsTemplate['callNumberFontSize']}"> {$callNumber[0]}<br/>{$callNumber[1]}<br/>{$callNumber[2]}</span>
                            </div>
                        </div>
                    HTML;
                endforeach; 
                // increment row
                $row++;
                echo '</div>';
                // set page break
                echo (($row % $globalSettings['pageBreakAt']) === 0) ? '<div class="pagebreak"></div>' : null;
            endforeach; 
            ?>
        </div>
        <!-- Load JS Barcode -->
        <script src="<?= $assetPath . '/assets/js/JsBarcode.all.min.js'?>"></script>
        <!-- Make init -->
        <script>JsBarcode(".barcode").init();</script>
        <!-- Auto show print dialog -->
        <?php if ($globalSettings['autoprint']): ?>
            <script>self.print()</script>
        <?php endif; ?>
    </body>
</html>
<?php
// get buffer
$html_str = ob_get_clean();
