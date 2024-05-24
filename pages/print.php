<?php
/**
 * @author Drajat Hasan
 * @email <drajathasan20@gmail.com>
 * @create date 2021-06-28 06:37:56
 * @modify date 2024-05-23 17:59:39
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

$datagrid = new Datagrid(name: 'Test');
$datagrid->setTable('search_biblio')
->addColumn('biblio_id as id', $datagrid->cast('title as judul', function($datagrid, $original, $data) {
    return <<<HTML
    <strong class="d-block w-100">{$original}</strong>
    <small class="text-muted">{$data['Tahun Terbit']}</small>
    HTML;
}),'publish_year as "Tahun Terbit"')
->setSort('biblio_id')->setInvisibleColumn(['Tahun Terbit']);

// $datagrid->isEditable(false);

echo $box;
echo $datagrid;