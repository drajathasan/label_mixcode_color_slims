<?php
use SLiMS\DB;
use SLiMS\Ui\Sections\MenuBox;
use SLiMS\Ui\Components\Link;
use SLiMS\Ui\Components\Search;
use SLiMS\Ui\Components\Datagrid;
use SLiMS\Ui\Components\Reportgrid;
use SLiMS\Ui\Components\Queuegrid;
use SLiMS\Ui\Components\Td;

defined('INDEX_AUTH') or die('Direct access not allowed!');

// dd(DB::connection('pgsql'));

$datagrid = new Reportgrid(name: 'Test');
$datagrid->setTable('biblio')->setColumn('biblio_id','title','last_update');

$datagrid->onSearch(function($datagrid) {
    $datagrid->setCriteria('title', function($datagrid, &$parameter) {
        $parameter[] = '%' . $_GET['keywords'] . '%';
        return ' like ?';
    });
});

$datagrid->onExport(function($reportGrid) {
    $reportGrid->exportSpreadSheet();
});

$box = new MenuBox;
$box
    ->setTitle('Label SLiMS Warna')
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
    ->setForm(new Search)
    ->setEtc($datagrid->inIframe());

echo $box;
