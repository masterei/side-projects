<?php

/**
 * @author          : Rei Junior
 * @dateCreated     : November  23, 2023
 * @github          : https://github.com/masterei
 * @description     : A simple sql query builder.
 * @supported       : MySQL
 *
 * This source file is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE.
 */

class DB
{
    protected ?string $table = null;

    protected ?string $tableAlias = null;

    protected array $selects = [];

    protected array $joins = [];

    protected array $wheres = [];

    protected array $orderBys = [];

    protected ?int $limit = null;

    protected ?int $offset = null;

    protected ?string $query = null;

    public function __construct(string $tableName)
    {
        $this->table = $tableName;
    }

    public static function table(string $name): self
    {
        return new self($name);
    }

    /**
     * Checked if column is already prefix by table name.
     */
    private function isPrefixed(string $name): bool
    {
        return count(explode('.', $name)) > 1;
    }

    /**
     * Strip string for potential sql injection.
     */
    private function ensureSafeString(string $string): string
    {
        return preg_replace('/[^\pL\pN_.()\s*]+/', '', $string);
    }

    private function getTable(): string
    {
        return !empty($this->tableAlias) ? $this->tableAlias : $this->table;
    }

    private function prefixColumn(string $table, string $column): string
    {
        return "$table.$column";
    }

    /**
     * Table alias.
     */
    public function alias(string $name): self
    {
        $this->tableAlias = $name;
        return $this;
    }

    public function select(array $columns): self
    {
        $this->selects = $columns;
        return $this;
    }

    private function addJoin(string $type, string $baseTable, string $baseColumn, string $operator, $foreignColumn): void
    {
        $this->joins[] = (object)[
            'type' => $type,
            'baseTable' => $baseTable,
            'baseColumn' => $baseColumn,
            'operator' => $operator,
            'foreignColumn' => $foreignColumn
        ];
    }

    public function leftJoin(string $table, string $baseColumn, string $operator, $foreignColumn): self
    {
        $this->addJoin('left join', $table, $baseColumn, $operator, $foreignColumn);
        return $this;
    }

    public function rightJoin(string $table, string $baseColumn, string $operator, $foreignColumn): self
    {
        $this->addJoin('right join', $table, $baseColumn, $operator, $foreignColumn);
        return $this;
    }

    public function join(string $table, string $baseColumn, string $operator, $foreignColumn): self
    {
        $this->addJoin('join', $table, $baseColumn, $operator, $foreignColumn);
        return $this;
    }

    private function addWhere(string $type, string $column, string $value, string $operator = '='): void
    {
        $this->wheres[] = (object)[
            'type' => strtoupper($type),
            'column' => $column,
            'value' => $value,
            'operator' => $operator
        ];
    }

    public function where(string $column, string $value, string $operator = '='): self
    {

        $this->addWhere('and', $column, $value, $operator);
        return $this;
    }

    public function whereOr(string $column, string $value, string $operator = '='): self
    {
        $this->addWhere('or', $column, $value, $operator);
        return $this;
    }

    public function orderBy(string $column, string $order): self
    {
        $this->orderBys[] = (object)[
            'column' => $column,
            'order' => $order
        ];

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    private function makeSelect(): self
    {
        $selects = [];
        // preparing select columns
        foreach ($this->selects as $select) {
            // exclude COUNT on prefixing
            if (!str_contains(strtolower($select), 'count')) {
                $select = $this->isPrefixed($select) ? $select : $this->prefixColumn($this->getTable(), $select);
            }

            $selects[] = $this->ensureSafeString($select);
        }

        $selectColumns = !empty($selects) ? implode(', ', $selects) : '*';
        $alias = !empty($this->tableAlias) ? " AS " . $this->getTable() : null;

        $this->query = "SELECT $selectColumns FROM $this->table" . $alias;
        return $this;
    }

    private function makeJoin(): self
    {
        $joins = [];
        foreach ($this->joins as $join) {
            // strip unsafe strings
            $baseTable = $this->ensureSafeString($join->baseTable);
            $baseColumn = $this->ensureSafeString($join->baseColumn);
            $foreignColumn = $this->ensureSafeString($join->foreignColumn);

            // prefixing column names
            $baseColumn = $this->isPrefixed($baseColumn) ? $baseColumn : $this->prefixColumn($baseTable, $baseColumn);
            $foreignColumn = $this->isPrefixed($foreignColumn) ? $foreignColumn : $this->prefixColumn($this->getTable(), $foreignColumn);

            $joins[] = strtoupper($join->type) . " $baseTable ON $baseColumn $join->operator $foreignColumn";
        }

        if (!empty($joins)) {
            $this->query .= " " . implode(' ', $joins);
        }

        return $this;
    }

    private function makeWhere(): self
    {
        $whereAnds = [];
        $whereOrs = [];

        foreach ($this->wheres as $where) {
            $column = $this->ensureSafeString($where->column);
            $column = $this->isPrefixed($column) ? $column : $this->prefixColumn($this->getTable(), $column);

            switch ($where->type) {
                case 'AND':
                    $whereAnds[] = "$column $where->operator '$where->value'";
                    break;
                case 'OR':
                    $whereOrs[] = "$column $where->operator '$where->value'";
                    break;
            }
        }

        if (!empty($whereAnds) || !empty($whereOrs)) {
            $this->query .= " WHERE ";

            if (!empty($whereAnds)) {
                $this->query .= implode(' AND ', $whereAnds);

                if (!empty($whereOrs)) {
                    $this->query .= " AND ";
                }
            }

            if (!empty($whereOrs)) {
                $this->query .= implode(' OR ', $whereOrs);
            }
        }

        return $this;
    }

    private function makeOrderBy(): self
    {
        $orderBys = [];
        foreach ($this->orderBys as $orderBy) {
            $order = strtoupper($orderBy->order);

            if (in_array($order, ['ASC', 'DESC'])) {
                $column = $this->ensureSafeString($orderBy->column);
                $column = $this->isPrefixed($column) ? $column : $this->prefixColumn($this->getTable(), $column);

                $orderBys[] = "$column $order";
            }
        }

        if (!empty($orderBys)) {
            $orderBys = implode(' AND ', $orderBys);
            $this->query .= " ORDER BY $orderBys";
        }

        return $this;
    }

    private function makeLimitOffset(): self
    {
        // limit
        if ((int)$this->limit > 0) {
            $this->query .= " LIMIT $this->limit";
        }

        // offset 50 >
        if ((int)$this->offset > 0) {
            $this->query .= " OFFSET $this->offset";
        }

        return $this;
    }

    public function toSql(): string
    {
        $this->makeSelect()->makeJoin()->makeWhere()->makeOrderBy()->makeLimitOffset();
        return $this->query;
    }

    protected function getSqlsrv()
    {
        $query = sqlsrv_query($GLOBALS['server']['connection'], $this->toSql());

        $results = [];
        while($row = sqlsrv_fetch_array($query, SQLSRV_FETCH_ASSOC)){
            $results[] = $row;
        }

        return json_decode(json_encode($results));
    }

    protected function getDefault()
    {
        // mysqli
        echo 'no function yet!';
        exit;
    }

    public function get()
    {
        return match ($GLOBALS['server']['driver']){
            'sqlsrv' => $this->getSqlsrv(),
            default => $this->getDefault()
        };
    }
}
