<?php
/**
 * @author Drajat Hasan
 * @email <drajathasan20@gmail.com>
 * @create date 2021-06-28 06:37:56
 * @modify date 2024-05-19 23:01:06
 * @license GPLv3
 */
use SLiMS\DB;
use Mixcode\Ui\Sections\MenuBox;
use Mixcode\Ui\Components\Link;
use Mixcode\Ui\Components\Search;

defined('INDEX_AUTH') or die('Direct access not allowed!');

$box = new MenuBox;
$box
    ->setTitle('Label Mixcode Warna')
    ->setButton([
        (new Link)
            ->setClass('btn btn-danger')
            ->setAttribute('href', '#')
            ->setSlot('Kosongkan Antrian'),
        (new Link)
            ->setClass('btn btn-primary')
            ->setAttribute('href', '#')
            ->setSlot('Cetak Sekarang'),
        (new Link)
            ->setAttribute('href', SWB)
            ->setSlot($slot = 'Pengaturan Label')
            ->openPopUp($slot)
    ])
    ->setForm(new Search);

echo $box;