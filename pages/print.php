<?php
/**
 * @author Drajat Hasan
 * @email <drajathasan20@gmail.com>
 * @create date 2021-06-28 06:37:56
 * @modify date 2024-05-24 15:12:39
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

$title = $datagrid->cast('b.title', function($datagrid, $original, $data) {
    $thumbUrl = SWB . 'lib/minigalnano/createthumb.php?filename=images/docs/' . $data['image'] . '&width=30&height=40px';
    return <<<HTML
    <div class="d-flex flex-row">
        <div>
            <strong class="d-block">{$original}</strong>
        </div>
    </div>
    HTML;
});

$datagrid
    ->setTable('biblio as b', joins: [
        ['item as i', ['i.biblio_id','=','b.biblio_id'], 'left join']
    ])
    ->addColumn('b.biblio_id', $title,'b.isbn_issn','!count(i.item_code) as copy','b.image','b.last_update')
    ->setCriteria('i.biblio_id', fn() => ' is not null')
    ->setGroup('b.biblio_id')
    ->setInvisibleColumn(['image','author'])
    ->onSearch(function($datagrid) {
        $datagrid->setCriteria('!(match (b.title)', function($datagrid, &$parameter) {
            $parameter = [$_GET['keywords']??''];

            return ' against (? in boolean mode))';
        });
    });

echo $box;
echo $datagrid;