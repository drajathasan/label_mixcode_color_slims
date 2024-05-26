<?php
namespace Mixcode\Ui\Components;

class Pagination extends Base
{
    protected string $tag = 'span';
    protected array $attributes = [
        'class' => 'pagingList'
    ];

    protected array $properties = [
        'total_data' => 0,
        'per_page' => 0,
        'total_page' => 0
    ];

    public static function create(string $baseUrl, int $totalData, int $perPage)
    {
        $instance = new static;

        $instance->properties = [
            'base_url' => $baseUrl,
            'total_data' => $totalData,
            'per_page' => $perPage,
            'total_page' => intval(ceil($totalData/$perPage))
        ];

        return $instance;
    }

    public function __toString()
    {
        $currentPage = $_GET['page']??1;
        $page = [];

        if ($currentPage > 1) {
            $page[] = createComponent('a', [
                'class' => 'first_link',
                'href' => $this->properties['base_url'] . '&page=1'
            ])->setSlot(__('First Page'));
            $page[] = createComponent('a', [
                'class' => 'first_link',
                'href' => $this->properties['base_url'] . '&page=' . ($currentPage - 1)
            ])->setSlot(__('Previous'));
        }

        $firstPage = ($currentPage - 2) < 1 ? 1 : ($currentPage - 2);
        $lastPage = ($currentPage + 2) < $this->properties['total_page'] ? $currentPage + 2 : $this->properties['total_page'];

        if ($firstPage == 1 && ($this->properties['total_page'] > 5)) $lastPage = 5;

        $range = range($firstPage, $lastPage);

        foreach ($range as $value) {
            if ($value == $currentPage) {
                $page[] = createComponent('b')->setSlot($value);
                continue;
            }

            $page[] = createComponent('a', [
                'href' => $this->properties['base_url'] . '&page=' . $value
            ])->setSlot($value);
        }

        if ($lastPage < $this->properties['total_page']) {
            $page[] = createComponent('a', [
                'class' => 'first_link',
                'href' => $this->properties['base_url'] . '&page=' . ($currentPage + 1)
            ])->setSlot(__('Next'));
            
            $page[] = createComponent('a', [
                'class' => 'first_link',
                'href' => $this->properties['base_url'] . '&page=' . ($this->properties['total_page'])
            ])->setSlot(__('Last Page'));
        }

        $this->setSlot(implode('', $page));

        return parent::__toString();
    }
}