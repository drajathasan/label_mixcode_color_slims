<?php
namespace Mixcode\Ui\Components;

use SLiMS\DB;
use Closure;

class Datagrid extends Table
{
    protected array $properties = [
        'table' => '',
        'join' => [],
        'countable_column' => '',
        'columns' => [],
        'criteria' => [],
        'group' => [],
        'sort' => [],
        'limit' => 20,
        'editable' => true,
        'cast' => [],
        'connection' => 'SLiMS'
    ];

    protected array $detail = [
        'record' => [],
        'total' => 0
    ];

    public function cast(string $column, Closure $callback)
    {
        $hasAlias = false;
        $columnAlias = [];
        $columnName = '';

        $this->aliasExtractor($column, $columnAlias, $hasAlias);
        $this->getOriginalColumnFromAlias($column, $columnName);

        $this->properties['cast'][($hasAlias ? $columnAlias[1] : $columnName)] = $callback;
        return $column;
    }

    public function setTable(string $table, array $joins = [])
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

    public function addColumn()
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

    public function isEditable(bool $status)
    {
        $this->editable = $status;
    }

    public function setCriteria(string $column, $value = '')
    {
        if (empty($value)) {
            foreach ($column as $col) {
                $this->properties['criteria'][$col[0]] = $col;
            }

            return $this;
        }

        $this->properties['criteria'][$column] = $value;

        return $this;
    }

    public function setGroup()
    {
        $this->group = func_get_args();
        return $this;
    }

    public function setSort()
    {
        $this->sort = func_get_args();
        return $this;
    }

    public function setLimit(int $limit)
    {
        $this->limit = $limit;
        return $this;
    }

    protected function getWhere()
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

    protected function cleanChar(string $input)
    {
        return str_replace(['\'','"','`','--'], '', $input);
    }

    protected function encapsulate(string|array $input)
    {
        // have alias?
        if (is_array($input)) {
            return implode('.', array_map(fn($char) => '`' . trim($char) . '`', $input));
        }

        return '`' . trim($input) . '`';
    }

    protected function getOriginalColumnFromAlias(string $input, string &$originalColumn = '')
    {
        $extract = explode(' as ', str_replace(['AS','as','aS','As'], 'as', $input));
        $originalColumn = $extract[0];

        return $extract;
    }

    protected function dotExtractor(string $input, bool &$isAvailable = false)
    {
        $isAvailable = is_numeric(strpos($input, '.'));
        return $isAvailable ? explode('.', trim($input)) : $input;
    }
    
    protected function aliasExtractor(string $column, array &$extract = [], bool &$hasAlias = false)
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

    private function getData()
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

        if ($this->order) {
            $sql['order'] = 'order by ' . $this->order;
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

    public function __toString()
    {
        $this->getData();

        if ($this->detail['total'] > 0) {
            $this->addHeader(...array_keys($this->detail['record'][0]));

            foreach ($this->detail['record'] as $columnName => $value) {
                foreach ($value as $col => $val) {
                    if (isset($this->properties['cast'][$col])) {
                        $value[$col] = call_user_func_array($this->properties['cast'][$col], [$this, $val]);
                    }
                }
                $this->addRow(array_values($value));
            }
        }

        return parent::__toString();
    }
}