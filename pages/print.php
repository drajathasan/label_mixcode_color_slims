<?php
/**
 * @author Drajat Hasan
 * @email <drajathasan20@gmail.com>
 * @create date 2021-06-28 06:37:56
 * @modify date 2024-05-21 05:38:18
 * @license GPLv3
 */
use SLiMS\DB;
use Mixcode\Ui\Sections\MenuBox;
use Mixcode\Ui\Components\Link;
use Mixcode\Ui\Components\Search;
use Mixcode\Ui\Components\Datagrid;
use Mixcode\Ui\Components\Td;

defined('INDEX_AUTH') or die('Direct access not allowed!');

$box = new MenuBox;
$box
    ->setTitle('Label Mixcode Warna')
    ->setButton(
        (new Link)
            ->setClass('btn btn-danger')
            ->setAttribute('href', function($component) {
                return $component->getSelfUrl();
            })
            ->setSlot('Kosongkan Antrian'),
        (new Link)
            ->setClass('btn btn-primary')
            ->setAttribute('href', function($component) {
                return $component->getSelfUrl();
            })
            ->setSlot('Cetak Sekarang'),
        (new Link)
            ->setAttribute('href', SWB)
            ->setSlot($slot = 'Pengaturan Label')
            ->openPopUp($slot)
    )
    ->setForm(new Search);

$datagrid = new Datagrid;
$datagrid->setTable('biblio as b', [
    ['mst_gmd as mg', ['mg.gmd_id','=','b.gmd_id'], 'inner join']
])->addColumn('b.biblio_id as id','b.title as judul','publisher_year as "Tahun Terbit"');

echo $datagrid;

echo $box;