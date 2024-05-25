<?php
namespace Mixcode\Ui\Components;

use SLiMS\DB;
use Closure;

class Datagrid extends Table
{
    /**
     * Datagrid properties
     */
    protected array $properties = [
        // For debugging process
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
        'limit' => 30,
        'editable' => true,
        'editable_form' => [
            'id' => '',
            'name' => '',
            'action' => '',
            'method' => 'POST',
            'target' => 'submitExec',
        ],
        'cast' => [],
        'on_search' => null,
        'connection' => 'SLiMS'
    ];

    protected array $detail = [
        'record' => [],
        'total' => 0
    ];

    public function __construct(string $name = 'datagrid', string $action = '', string $method = 'POST', string $target = 'submitExec')
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
        $countableColumn = $this->getOriginalColumnFromAlias(func_get_args()[0])[0];
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

    /**
     * Register some column to unsortable
     * 
     * @param array|string $column
     * @return Datagrid
     */
    public function setUnsort(array|string $column):Datagrid
    {
        if (is_array($column)) {
            $column = array_merge($this->properties['unsortable_by_anchor'], $column);
            $this->properties['unsortable_by_anchor'] = $column;
        } else {
            $this->properties['unsortable_by_anchor'][] = $column;
        }

        return $this;
    }

    /**
     * Register some column to invisible on 
     * html rendering.
     *
     * @param array $column
     * @return Datagrid
     */
    public function setInvisibleColumn(array $column):Datagrid
    {
        $this->properties['invisible_column'] = $column;
        return $this;
    }

    /**
     * A method to handle serching data
     *
     * @param Closure $callback
     * @return Datagrid
     */
    public function onSearch(Closure $callback, string $searchQuery = 'keywords'):Datagrid
    {
        if (isset($_GET[$searchQuery])) {
            $this->properties['on_search'] = $callback;
        }

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
                $customParams = [];
                $criteria[$column] = $this->aliasExtractor($column) . ' ' . $value($this, $customParams);
                $parameters = array_merge($parameters, $customParams);
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
    public function cleanChar(string $input):string
    {
        return str_replace(['\'','"','`','--'], '', $input);
    }

    /**
     * Encapsulate string between quote
     */
    public function encapsulate(string|array $input):string
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
    public function getOriginalColumnFromAlias(string $input, string &$originalColumn = ''):array
    {
        $extract = explode(' as ', str_replace(['AS','as','aS','As'], 'as', $input));

        $isDotExists = false;
        $dotAlias = $this->dotExtractor($input, $isDotExists);
        $originalColumn = $isDotExists ? $dotAlias[1] : $extract[0];

        return $extract;
    }

    /**
     * Extract some input based on dot char
     *
     * @param string $input
     * @param boolean $isAvailable
     * @return array|string
     */
    public function dotExtractor(string $input, bool &$isAvailable = false):array|string
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
            '|*=','all','and',
            'any','between','exists',
            'in','like','not','or',
            'some'
        ];

        if (in_array(strtolower($column), $operator)) return $column;

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

    /**
     * Generate url based on editable form action
     *
     * @param array $additionalUrl
     * @return string
     */
    protected function setUrl(array $additionalUrl = []):string
    {
        // seperate querystring and self
        $url = explode('?', $this->properties['editable_form']['action']);

        // had queries?
        if (isset($url[1])) {
            parse_str($url[1], $queries); // convert http query to array
            // merging and turn it back to http query format
            $url[1] = http_build_query(array_merge($queries, $additionalUrl));
        } else {
            // not http query? make it from additional url and $_GET
            $url[1] = http_build_query(array_merge($_GET, $additionalUrl));
        }

        // as string with queries
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

        // Select statement
        $sql['select'] = 'select ' . $columns . ' from ' . $this->table;
        
        // Where clause
        if ($this->criteria) {
            $where = $this->getWhere();
            if (is_callable($this->properties['on_search'])) {
                $parameters = [];
                $this->properties['on_search']($this);
                $where = $this->getWhere();
                $where['parameters'] = array_merge($where['parameters'], $parameters);
            }

            $sql['criteria'] = 'where ' . $where['criteria'];

        }

        // Groupting data
        if ($this->group) {
            $sql['group'] = 'group by ' . $this->group;
        }

        // sorting data
        $direction = isset($_GET['dir']);
        if ($this->sort || $direction) {
            if ($direction) {
                $this->setSort($this->encapsulate($this->cleanChar($_GET['field'])), $_GET['dir']);
            }
            $sql['order'] = 'order by ' . $this->sort;
        }

        // pagination
        $offset = ((int)($_GET['page']??0) * $this->limit / 2);
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

        // If grouping available in main query,
        // datagrid will counting data from how many data in result
        if ($totalQuery->count() > 1) {
            $this->detail['total'] = $totalQuery->count();
        } else {
            $result = $totalQuery->first();
            $this->detail['total'] = $result['total']??0;
        }
        
        if (!empty($totalQueryError = $totalQuery->getError())) {
            throw new \Exception('Total query : ' . $totalQueryError . '. Raw Query : ' . $rawTotalQuery);
        }

        $this->properties['sql'] = [
            'main' => $rawMainQuery,
            'count' => $rawTotalQuery,
            'parameters' => $where['parameters']??[]
        ];
    }

    /**
     * Preparing html table header
     *
     * @return void
     */
    protected function setHeader()
    {
        $header = [];

        if ($this->editable) {
            // deleted 
            $header[] = __('DELETE');
            // edita
            $header[] = __('EDIT');
        }

        foreach (array_keys($this->detail['record'][0]??[]) as $key => $value) {
            // hidden some column
            if (in_array($value, $this->properties['invisible_column'])) continue;

            // set header as clear text if it available in unsortable list
            if (in_array($value, $this->properties['unsortable_by_anchor'])) {
                $header[] = $value;
                continue;
            }

            // editable? skip first column replaced by edit and delete
            if ($this->editable) {
                if ($key === 0) continue;
            }

            // Direction or sorting process
            $dir = 'DESC';
            if (isset($_GET['dir']) && isset($_GET['field']) && $_GET['field'] === $value) {
                $dir = $_GET['dir'] === 'ASC' ? 'DESC' : 'ASC';
            }

            // set http query
            $defaultParam = [
                'field' => $value,
                'dir' => $dir
            ];

            $header[] = (new Td)->setSlot((string)createComponent('a', [
                'href' => $this->setUrl($defaultParam)
            ])->setSlot($value));
        }

        // add header to datagrid
        $this->addHeader(...$header);

        // clear header variable
        unset($header);
    }

    /**
     * Preparing table body
     *
     * @return void
     */
    public function setBody()
    {
        $recordNum = 0;
        foreach ($this->detail['record'] as $columnName => $value) {
            $recordNum++;

            $originalValue = array_values($value);

            // default options for row attribute
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

                // td options
                $tdOption = [
                    'align' => 'center',
                    'valign' => 'top',
                    'style' => 'width: 5%'
                ];

                // Checkbox
                $editableValue[] = createComponent('td', $tdOption)->setSlot(createComponent('input', [
                    'id' => 'cbRow' . $recordNum,
                    'class' => 'selected-row',
                    'type' => 'checkbox',
                    'name' => 'itemID[]',
                    'value' => $originalValue[0]
                ]));

                // edit button
                $editableValue[] = createComponent('td', $tdOption)->setSlot(createComponent('a', [
                    'class' => 'editLink',
                    'href' => $this->setUrl($editableParam = ['itemID' => $originalValue[0], 'detail' => 'true']),
                    'postdata' => http_build_query($editableParam),
                    'title' => __('Edit')
                ])->setSlot(''));

                // remove first column
                unset($columns[0]);

                // new columns
                $columns = array_merge($editableValue, $columns);
            }

            // Add column to row
            $this->addRow($columns, $options);
        }
    }

    /**
     * Set action bar such as
     * delete, checkall, uncheckall button
     * and pagination
     * to manage row on datagrid
     *
     * @return string
     */
    public function setActionBar():string
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

        $pagiNation = (string)createComponent('td', ['class' => 'paging-area'])
                        ->setSlot((string)Pagination::create($this->setUrl(), $this->detail['total'], $this->properties['limit']));


        return (string)createComponent('table', [
            'class' => 'datagrid-action-bar',
            'cellspacing' => 0,
            'cellpadding' => 5,
            'style' => 'width: 100%'
        ])->setSlot($actionButton . $pagiNation);
    }

    /**
     * Debug process
     *
     * @return void
     */
    public function debug()
    {
        dump($this->properties['sql']);
    }

    /**
     * Some magic method
     *
     * @return boolean
     */
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
        // Fetching data from database
        $this->getData();

        // set iframe to catch from result
        $submitExec = createComponent('iframe', [
            'id' => 'submitExec',
            'name' => 'submitExec',
            'class' => isDev() ? 'd-block' : 'd-none'
        ])->setSlot('');

        // For development process
        ob_start();
        debugBox(function() use($submitExec) {
            $this->debug();
            echo $submitExec;
        });
        $debugBox = ob_get_clean();

        if ($this->detail['total'] > 0) {
            // Add column header
            $this->setHeader();            

            // set column body
            $this->setBody();

            // rendering object to html
            $output = parent::__toString();

            // set form
            if ($this->editable) {
                $this->properties['editable_form']['action'] = $this->setUrl();
                
                $actionBar = $this->setActionBar();
                $datagrid = createComponent('form', $this->properties['editable_form'])
                                ->setSlot($debugBox . $actionBar . $output . $actionBar);

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

            $output = $debugBox . parent::__toString();
        }

        return $output;
    }
}