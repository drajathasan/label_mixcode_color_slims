<?php
namespace Mixcode\Ui\Components;

use SLiMS\DB;
use Closure;

class Datagrid extends Table
{
    protected array $properties = [
        'table' => '',
        'join' => [],
        'columns' => [],
        'criteria' => [],
        'group' => [],
        'sort' => [],
        'limit' => 0,
        'editable' => true,
        'cast' => [],
        'connection' => 'SLiMS'
    ];

    public function cast(string $column, Closure $callback)
    {
        $hasAlias = false;
        $columnAlias = [];
        $column = $this->aliasExtractor($column, $columnAlias, $hasAlias);

        $this->cast[($hasAlias ? $columnAlias[1] : $columnAlias[0])] = $callback;
        $this->properties['columns'][] = $column;

        return $this;
    }

    public function setTable(string $table, array $joins = [])
    {
        $this->table = $this->aliasExtractor($table);
        $joinType = ['join','inner join','right join','left join','outer join'];
        
        foreach ($joins as $join) {
            list($joinTable, $operands, $type) = $join;
            $joinTable = $this->aliasExtractor($joinTable);
            foreach ($operands as $key => $operand) {
                $operands[$key] = $this->aliasExtractor($operand);
            }

            if (!in_array(strtolower($type), $joinType)) continue;

            $this->table .= ' ' . strtolower($type) . ' ' . $joinTable . ' ' . implode(' and ', $operands);
        }

        return $this;
    }

    public function addColumn()
    {
        $this->columns = array_map(function($col) {
            if ($col instanceof Datagrid) {
                $columns = $col->properties['columns'];
                return $columns[array_key_last($columns)];
            }

            return $this->aliasExtractor($col);
        }, func_get_args());

        return $this;
    }

    public function isEditable(bool $status)
    {
        $this->editable = $status;
    }

    public function setCriteria()
    {
        $this->criteria = func_get_args();
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

    private function aliasExtractor(string $column, array &$extract = [], bool &$hasAlias = false)
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

        $column = str_replace(['\'','"','`'], '', $column);
        $columnExtract = explode(' as ', str_replace(['AS','as','aS','As'], 'as', $column));

        if (($hasAlias = isset($columnExtract[1]))) {
            $extract = $columnExtract;
            return implode(' as ', array_map(function($col) {
                $hasDot = strpos($col, '.') !== false;

                if ($hasDot) {
                    $col = explode('.', $col);
                    return '`' . trim($col[0]) . '`.`' . trim($col[1]) . '`';
                }

                return '`' . trim($col) . '`';
            }, $columnExtract));
        }

        return '`' . trim($column) . '`';
    }

    private function getData()
    {
        // column processing
        $columns = implode(',', $this->columns);
        $sql = [];
        $sql[] = 'select ' . $columns . ' from ' . $this->table;
        
        if ($this->criteria) {
            $sql[] = 'where ' . $this->criteria;
        }

        // $query = DB::query()
        
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
        return parent::__toString();
    }
}