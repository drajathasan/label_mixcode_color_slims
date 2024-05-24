<?php
namespace Mixcode\Ui\Components;

use SLiMS\DB;
use Closure;

class Datagrid extends Table
{
    protected array $properties = [
        'sql' => [
            'main' => '',
            'count' => '',
            'parameters' => []
        ],
        'table' => '',
        'join' => [],
        'countable_column' => '',
        'columns' => [],
        'width_per_columns' => [],
        'criteria' => [],
        'group' => [],
        'sort' => [],
        'unsortable_by_anchor' => [],
        'invisible_column' => [],
        'limit' => 20,
        'editable' => true,
        'editable_form' => [
            'id' => '',
            'name' => '',
            'action' => '',
            'method' => 'POST',
            'target' => 'submitExec',
        ],
        'cast' => [],
        'connection' => 'SLiMS'
    ];

    protected array $detail = [
        'record' => [],
        'total' => 0
    ];

    public function __construct(string $name, string $action = '', string $method = 'POST', string $target = 'submitExec')
    {
        $this->properties['editable_form'] = [
            'id' => $this->cleanChar($name),
            'name' => $this->cleanChar($name),
            'action' => empty($action) ? $_SERVER['PHP_SELF'] : $action,
            'method' => $method,
            'target' => $target
        ];
    }

    /**
     * Cast and modify column content
     *
     * @param string $column
     * @param Closure $callback
     * @return string
     */
    public function cast(string $column, Closure $callback):string
    {
        $hasAlias = false;
        $columnAlias = [];
        $columnName = '';

        $this->aliasExtractor($column, $columnAlias, $hasAlias);
        $this->getOriginalColumnFromAlias($column, $columnName);

        $this->properties['cast'][($hasAlias ? $columnAlias[1] : $columnName)] = $callback;
        return $column;
    }

    /**
     * Set main table and join clause if needed
     *
     * @param string $table
     * @param array $joins
     * @return Datagrid
     */
    public function setTable(string $table, array $joins = []):Datagrid
    {
        $this->table = $this->aliasExtractor($table);
        $joinType = ['join','inner join','right join','left join','outer join'];
        
        foreach ($joins as $join) {
            list($joinTable, $operands, $type) = $join;
            $joinTable = $this->aliasExtractor($joinTable);
            
            $chunkOperands = array_chunk($operands, 3);
            foreach ($chunkOperands as $key => $operand) {
                foreach ($operand as $k => $value) {
                    $operand[$k] = $this->aliasExtractor($value);
                }
                $chunkOperands[$key] = implode(' ', $operand);
            }

            if (!in_array(strtolower($type), $joinType)) continue;

            $this->table .= ' ' . strtolower($type) . ' ' . $joinTable . ' on ' . implode(' ', $chunkOperands);
        }

        return $this;
    }

    /**
     * Html width per column
     *
     * @param array $widthPerColumn
     * @return Datagrid
     */
    public function setColumnWidth(array $widthPerColumn):Datagrid
    {
        $this->properties['width_per_columns'] = $widthPerColumn;
        return $this;
    }

    /**
     * Add columns to datagrid
     *
     * @return Datagrid
     */
    public function addColumn():Datagrid
    {
        if (func_num_args() < 1) throw new Exception("Method addColumn need at least 1 argument!");

        $countableColumn = '';
        $this->getOriginalColumnFromAlias(func_get_args()[0], $countableColumn);
        $this->countable_column = $this->cleanChar($countableColumn);
        
        $this->columns = array_map(function($col) {
            return $this->aliasExtractor($col);
        }, func_get_args());

        return $this;
    }

    /**
     * If it set to true datagrid will show up
     * editable attribute such as check all data, uncheck data
     * selected data etc.
     *
     * @param boolean $status
     * @return void
     */
    public function isEditable(bool $status):void
    {
        $this->editable = $status;
    }

    /**
     * Set sql criteria
     * 
     * @param array|string $column
     * @param mixed $value
     * @return Datagrid
     */
    public function setCriteria(array|string $column, $value = ''):Datagrid
    {
        if (empty($value)) {
            foreach ($column as $col) {
                $this->properties['criteria'][$col[0]] = $col[1];
            }

            return $this;
        }

        $this->properties['criteria'][$column] = $value;

        return $this;
    }

    /**
     * Set group by
     *
     * @return Datagrid
     */
    public function setGroup():Datagrid
    {
        $this->group = implode(',', array_map(fn($col) => $this->aliasExtractor($col), func_get_args()));
        return $this;
    }

    /**
     * Sorting data
     *
     * @param string|array $columnName
     * @param string $type
     * @return Datagrid
     */
    public function setSort(string|array $columnName, string $type = 'asc'):Datagrid
    {
        if (in_array(($type = strtolower($type)), ['asc','desc'])) {
            if (is_string($columnName)) $columnName = [$columnName];
            $this->sort = implode(',', array_map(fn($col) => $this->aliasExtractor($col), $columnName)) . ' ' . $type;
        }
        return $this;
    }

    public function setUnsort(array|string $column)
    {
        if (is_array($column)) {
            $column = array_merge($this->properties['unsortable_by_anchor'], $column);
            $this->properties['unsortable_by_anchor'] = $column;
        } else {
            $this->properties['unsortable_by_anchor'][] = $column;
        }

        return $this;
    }

    public function setInvisibleColumn(array $column)
    {
        $this->properties['invisible_column'] = $column;
        return $this;
    }

    /**
     * Make pagination data
     *
     * @param integer $limit
     * @return Datagrid
     */
    public function setLimit(int $limit): Datagrid
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Compile criteria data
     *
     * @return array
     */
    protected function getWhere():array
    {
        $criteria = [];
        $parameters = [];
        foreach ($this->criteria as $column => $value) {
            if (is_callable($value)) {
                $criteria[$column] = $this->aliasExtractor($column) . ' ' . $value($this, $parameters);
                continue;
            }

            $criteria[$column] = $this->aliasExtractor($column) . ' = ?';
            $parameters[] = $value;
        }

        return [
            'criteria' => implode(' and ', $criteria),
            'parameters' => $parameters
        ];
    }

    /**
     * @param string $input
     * @return void
     */
    protected function cleanChar(string $input):string
    {
        return str_replace(['\'','"','`','--'], '', $input);
    }

    /**
     * Encapsulate string between quote
     */
    protected function encapsulate(string|array $input):string
    {
        // have alias?
        if (is_array($input)) {
            return implode('.', array_map(fn($char) => '`' . trim($char) . '`', $input));
        }

        return '`' . trim($input) . '`';
    }

    /**
     * Retrieve original column without alias
     *
     * @param string $input
     * @param string $originalColumn
     * @return array
     */
    protected function getOriginalColumnFromAlias(string $input, string &$originalColumn = ''):array
    {
        $extract = explode(' as ', str_replace(['AS','as','aS','As'], 'as', $input));
        $originalColumn = $extract[0];

        return $extract;
    }

    /**
     * Extract some input based on dot char
     *
     * @param string $input
     * @param boolean $isAvailable
     * @return array|string
     */
    protected function dotExtractor(string $input, bool &$isAvailable = false):array|string
    {
        $isAvailable = is_numeric(strpos($input, '.'));
        return $isAvailable ? explode('.', trim($input)) : $input;
    }
    
    /**
     * compile coloumn string into MySQL format
     *
     * @param string $column
     * @param array $extract
     * @param boolean $hasAlias
     * @return string
     */
    protected function aliasExtractor(string $column, array &$extract = [], bool &$hasAlias = false):string
    {
        $operator = [
            '+','-','*',
            '/','%','&',
            '|','^','=',
            '>','<','>=',
            '<=','<>','+=',
            '-=','*=', '/=',
            '%=','&=','^-=',
            '|*=','ALL','AND',
            'ANY','BETWEEN','EXISTS',
            'IN','LIKE','NOT','OR',
            'SOME'
        ];

        if (in_array($column, $operator)) return $column;

        // bypass for raw query
        if (substr($column, 0,1) === '!') return trim($column,'!');

        $column = $this->cleanChar($column);
        $columnExtract = $this->getOriginalColumnFromAlias($column);
        if (($hasAlias = isset($columnExtract[1]))) {
            $extract = $columnExtract;
            return implode(' as ', array_map(function($col) {
                return $this->encapsulate($this->dotExtractor($col));
            }, $columnExtract));
        }

        return $this->encapsulate($this->dotExtractor($column));
    }

    protected function setUrl(array $additionalUrl = [])
    {
        $url = explode('?', $this->properties['editable_form']['action']);
        if (isset($url[1])) {
            parse_str($url[1], $queries);
            $url[1] = array_merge($queries, $additionalUrl);
        } else {
            $url[1] = http_build_query(array_merge($_GET, $additionalUrl));
        }

        return implode('?', $url);
    }

    /**
     * Retrieve data from database
     *
     * @return void
     */
    private function getData():void
    {
        // column processing
        $columns = implode(',', $this->columns);
        $sql = [];
        $sql['select'] = 'select ' . $columns . ' from ' . $this->table;
        
        if ($this->criteria) {
            $where = $this->getWhere();
            $sql['criteria'] = 'where ' . $where['criteria'];

        }

        if ($this->group) {
            $sql['group'] = 'group by ' . $this->group;
        }

        $direction = isset($_GET['dir']);
        if ($this->sort || $direction) {
            if ($direction) {
                $this->setSort($this->encapsulate($this->cleanChar($_GET['field'])), $_GET['dir']);
            }
            $sql['order'] = 'order by ' . $this->sort;
        }

        $offset = (int)($_GET['page']??0);
        $sql['limit'] = 'limit ' . ((int)$this->limit) . ' offset ' . $offset;
        
        // set main query
        $mainQuery = DB::query($rawMainQuery = implode(' ', $sql), $where['parameters']??[]);
        $this->detail['record'] = $mainQuery->toArray();

        if (!empty($mainQueryError = $mainQuery->getError())) {
            throw new \Exception('Main query : ' . $mainQueryError . '. Raw Query : ' . $rawMainQuery);
        }

        // set total query
        $totalSql = [];
        $totalSql['select'] = 'select count(' . $this->countable_column . ') as total from ' . $this->table;
        if (isset($sql['criteria'])) $totalSql['criteria'] = $sql['criteria'];
        if (isset($sql['group'])) $totalSql['group'] = $sql['group'];
        
        $totalQuery = DB::query($rawTotalQuery = implode(' ', $totalSql), $where['parameters']??[]);
        $this->detail['total'] = $totalQuery->first()['total'];
        
        if (!empty($totalQueryError = $totalQuery->getError())) {
            throw new \Exception('Total query : ' . $totalQueryError . '. Raw Query : ' . $rawTotalQuery);
        }

        $this->properties['sql'] = [
            'main' => $rawMainQuery,
            'count' => $rawTotalQuery,
            'parameters' => $where['parameters']??[]
        ];
    }

    protected function setHeader()
    {
        $header = [];

        if ($this->editable) {
            // deleted 
            $header[] = __('DELETE');
            $header[] = __('EDIT');
        }

        foreach (array_keys($this->detail['record'][0]) as $key => $value) {
            // hidden some column
            if (in_array($value, $this->properties['invisible_column'])) continue;

            // set header as clear text if it available in unsorted list
            if (in_array($value, $this->properties['unsortable_by_anchor'])) {
                $header[] = $value;
                continue;
            }

            if ($this->editable) {
                if ($key === 0) continue;
            }

            $dir = 'DESC';
            if (isset($_GET['dir']) && isset($_GET['field']) && $_GET['field'] === $value) {
                $dir = $_GET['dir'] === 'ASC' ? 'DESC' : 'ASC';
            }

            $defaultParam = [
                'field' => $value,
                'dir' => $dir
            ];

            $header[] = (new Td)->setSlot((string)createComponent('a', [
                'href' => $this->setUrl($defaultParam)
            ])->setSlot($value));
        }

        $this->addHeader(...$header);
        unset($header);
    }

    public function setBody()
    {
        $recordNum = 0;
        foreach ($this->detail['record'] as $columnName => $value) {
            $recordNum++;

            $originalValue = array_values($value);

            $options = [
                'class' => (($recordNum%2) === 0 ? 'alterCell2' : 'alterCell'),
                'style' => 'cursor: pointer',
                'row' => $recordNum
            ];

            // Value processing
            foreach ($value as $col => $val) {
                // hidden some column
                if (in_array($col, $this->properties['invisible_column'])) {
                    unset($value[$col]);
                    continue;
                }

                $td = new Td;

                // modify column content
                if (isset($this->properties['cast'][$col])) {
                    $value[$col] = call_user_func_array($this->properties['cast'][$col], [$this, $val, $value]);
                }

                // set default attribute
                $td->setAttribute('valign', 'top');

                // set column width
                if (isset($this->properties['width_per_columns'][$col])) {
                    $td->setAttribute('width', $this->properties['width_per_columns'][$col]);
                }

                // set content inner td
                $value[$col] = $td->setSlot($value[$col]);
                unset($td);
            }

            // Add row
            $columns = array_values($value);
            if ($this->editable) {
                $editableValue = [];

                // Checkbox
                $editableValue[] = createComponent('td', [
                    'align' => 'center',
                    'valign' => 'top',
                    'style' => 'width: 5%'
                ])->setSlot(createComponent('input', [
                    'id' => 'cbRow' . $recordNum,
                    'class' => 'selected-row',
                    'type' => 'checkbox',
                    'name' => 'itemID[]',
                    'value' => $originalValue[0]
                ]));

                // edit button
                $editableValue[] = createComponent('td', [
                    'align' => 'center',
                    'valign' => 'top',
                    'style' => 'width: 5%'
                ])->setSlot(createComponent('a', [
                    'class' => 'editLink',
                    'href' => $this->setUrl($editableParam = ['itemID' => $originalValue[0], 'detail' => 'true']),
                    'postdata' => http_build_query($editableParam),
                    'title' => __('Edit')
                ])->setSlot(''));

                unset($columns[0]);
                $columns = array_merge($editableValue, $columns);
            }
            $this->addRow($columns, $options);
        }
    }

    public function setActionBar()
    {
        $actionButton = (string)createComponent('td')->setSlot(
            (string)createComponent('input', [
                'class' => 's-btn btn btn-danger',
                'type' => 'button',
                'onclick' => '!chboxFormSubmit(\'' . $this->properties['editable_form']['name'] . '\', \'' . __('Are You Sure Want to DELETE Selected Data?') . '\', 1)',
                'value' => __('Delete Selected Data')
            ]) . 
            (string)createComponent('input', [
                'class' => 'check-all button btn btn-default',
                'type' => 'button',
                'value' => __('Check All')
            ]) .
            (string)createComponent('input', [
                'class' => 'uncheck-all button btn btn-default ml-1',
                'type' => 'button',
                'value' => __('Uncheck All')
            ])
        );

        return (string)createComponent('table', [
            'class' => 'datagrid-action-bar',
            'cellspacing' => 0,
            'cellpadding' => 5,
            'style' => 'width: 100%'
        ])->setSlot($actionButton);
    }

    public function debug()
    {
        if (isDev()) {
            dump($this->properties['sql']);
        }
    }

    public function __isset($key) {
        return isset($this->properties[$key]);
    }

    public function __set($key, $value) {
        if (isset($this->$key)) {
            $this->properties[$key] = $value;
        }
    }

    public function __get($key) {
        return $this->properties[$key]??null;
    }

    /**
     * Finally we need to print out
     * this object to string as html output
     *
     * @return string
     */
    public function __toString()
    {
        $this->getData();

        $submitExec = createComponent('iframe', [
            'id' => 'submitExec',
            'name' => 'submitExec',
            'class' => isDev() ? 'd-block' : 'd-none'
        ])->setSlot('');

        // For development process
        debugBox(function() use($submitExec) {
            $this->debug();
            echo $submitExec;
        });




        if ($this->detail['total'] > 0) {
            // Add column header
            $this->setHeader();            

            // set column body
            $this->setBody();

            $output = parent::__toString();
            if ($this->editable) {
                $this->properties['editable_form']['action'] = $this->setUrl();
                
                $actionBar = $this->setActionBar();
                $datagrid = createComponent('form', $this->properties['editable_form'])
                                ->setSlot($actionBar . $output . $actionBar);
                $output = (!isDev() ? $submitExec : '') . ((string)$datagrid);
            }
        } else {
            // No Data
            $this->setSlot(
                createComponent('tr', [
                    'row' => 0,
                    'style' => 'cursor: pointer;'
                ])->setSlot((string)(new Td)->setAttribute([
                    'class' => 's-table__no-data',
                    'align' => 'center'
                ])->setSlot(__('No Data')))
            );
            $output = parent::__toString();
        }

        return $output;
    }
}