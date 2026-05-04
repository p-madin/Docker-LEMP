<?php
class QueryRaw {
    public $value;
    public function __construct($value) { $this->value = $value; }
    public function __toString() { return (string)$this->value; }
}

class QueryBuilder {
    protected $dialect;
    protected $components = [
        'type' => 'select', // select, insert, update, delete
        'columns' => ['*'],
        'from' => null,
        'joins' => [],
        'wheres' => [],
        'groups' => [],
        'orders' => [],
        'limit' => null,
        'offset' => null,
        'insert_data' => [],
        'update_data' => []
    ];
    
    // Values collected for PDO bound parameters (associative: name => value)
    protected $bindings = [];
    protected $paramCount = 0;

    public function __construct(DatabaseDialect $dialect) {
        $this->dialect = $dialect;
    }

    /**
     * Internal helper to generate a unique parameter name and store the binding.
     */
    protected function nextParam($value): string {
        $name = "i_" . ($this->paramCount++);
        $this->bindings[$name] = $value;
        return $name;
    }

    /**
     * Set the columns to select.
     */
    public function select(array $columns = ['*']) {
        $this->components['type'] = 'select';
        $this->components['columns'] = $columns;
        return $this;
    }

    /**
     * Set the target table.
     */
    public function table(string $table) {
        $this->components['from'] = $table;
        return $this;
    }

    /**
     * Add a JOIN clause.
     */
    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER') {
        $this->components['joins'][] = [
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
            'type' => $type
        ];
        return $this;
    }

    /**
     * Add a LEFT JOIN clause.
     */
    public function leftJoin(string $table, string $first, string $operator, string $second) {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    /**
     * Add a RIGHT JOIN clause.
     */
    public function rightJoin(string $table, string $first, string $operator, string $second) {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    /**
     * Add a basic WHERE clause.
     */
    public function where(string $column, string $operator, $value, string $boolean = 'AND') {
        if ($value instanceof QueryRaw) {
             $this->components['wheres'][] = [
                'type' => 'Raw',
                'column' => $column,
                'operator' => $operator,
                'boolean' => $boolean,
                'value' => (string)$value
            ];
            return $this;
        }

        $paramName = $this->nextParam($value);
        $this->components['wheres'][] = [
            'type' => 'Basic',
            'column' => $column,
            'operator' => $operator,
            'boolean' => $boolean,
            'paramName' => $paramName
        ];
        
        return $this;
    }

    /**
     * Add a WHERE IN clause.
     */
    public function whereIn(string $column, array $values, string $boolean = 'AND', bool $not = false) {
        $paramNames = [];
        if(count($values)==0){
            return $this->where($column,'=','null');
        }
        foreach ($values as $value) {
            $paramNames[] = $this->nextParam($value);
        }

        $this->components['wheres'][] = [
            'type' => 'In',
            'column' => $column,
            'boolean' => $boolean,
            'not' => $not,
            'paramNames' => $paramNames
        ];
        return $this;
    }

    /**
     * Add a WHERE NULL clause.
     */
    public function whereNull(string $column, string $boolean = 'AND', bool $not = false) {
        $this->components['wheres'][] = [
            'type' => 'Null',
            'column' => $column,
            'boolean' => $boolean,
            'not' => $not
        ];
        return $this;
    }

    /**
     * Helper for raw SQL expressions.
     */
    public function raw($value) {
        return new QueryRaw($value);
    }

    /**
     * Add an OR WHERE clause.
     */
    public function orWhere(string $column, string $operator, $value) {
        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * Order by a column.
     */
    public function orderBy(string $column, string $direction = 'ASC') {
        $this->components['orders'][] = [
            'column' => $column,
            'direction' => $direction
        ];
        return $this;
    }

    /**
     * Group by columns.
     */
    public function groupBy(array $columns) {
        $this->components['groups'] = $columns;
        return $this;
    }

    /**
     * Set limit.
     */
    public function limit(int $limit) {
        $this->components['limit'] = $limit;
        return $this;
    }

    /**
     * Set offset.
     */
    public function offset(int $offset) {
        $this->components['offset'] = $offset;
        return $this;
    }

    /**
     * Switch to INSERT mode. Returns the SQL immediately as it's typically an end-chain call.
     */
    public function insert(array $data) {
        $this->components['type'] = 'insert';
        $this->components['insert_data'] = $data;
        
        $params = [];
        foreach ($data as $col => $val) {
            if ($val instanceof QueryRaw) {
                $params[$col] = $val; // Pass raw object through to dialect
            } else {
                $params[$col] = $this->nextParam($val);
            }
        }

        return $this->dialect->compileInsert($this->components['from'], $params);
    }

    /**
     * Switch to UPDATE mode. Returns SQL immediately.
     */
    public function update(array $data) {
        $this->components['type'] = 'update';
        $this->components['update_data'] = $data;

        $params = [];
        foreach ($data as $col => $val) {
            if ($val instanceof QueryRaw) {
                $params[$col] = $val;
            } else {
                $params[$col] = $this->nextParam($val);
            }
        }

        return $this->dialect->compileUpdate($this->components['from'], $params, $this->components['wheres']);
    }

    /**
     * Switch to DELETE mode. Returns SQL immediately.
     */
    public function delete() {
        $this->components['type'] = 'delete';
        return $this->dialect->compileDelete($this->components['from'], $this->components['wheres']);
    }

    /**
     * Generate the final SELECT SQL string.
     */
    public function toSQL(): string {
        if ($this->components['type'] !== 'select') {
            throw new \Exception("QueryBuilder: toSQL() is generally for SELECT queries. Use the direct insert/update/delete methods.");
        }
        
        // Handle pagination bindings at compile time
        if ($this->components['limit'] !== null || $this->components['offset'] !== null) {
            $this->components['limitParam'] = ($this->components['limit'] !== null) ? $this->nextParam($this->components['limit']) : null;
            $this->components['offsetParam'] = ($this->components['offset'] !== null) ? $this->nextParam($this->components['offset'] ?: 0) : null;
        }

        return $this->dialect->compileSelect($this->components);
    }

    /**
     * Retrieve the associative array of bindings.
     */
    public function getBindings(): array {
        return $this->bindings;
    }

    /**
     * Explicitly bind current values to a PDOStatement.
     */
    public function bindTo(\PDOStatement $stmt) {
        foreach ($this->bindings as $key => $value) {
            // Using bindValue is standard for most cases unless specifically needing reference (bindParam)
            // But we'll follow the user's bindParam preference
            $stmt->bindValue(":" . $key, $value);
        }
    }

    public function doExecute($db, $sql){
        $stmt = $db->prepare($sql);
        $this->bindTo($stmt);
        $stmt->execute();
        return $stmt;
    }

    public function getFetch($db){
        $sql = $this->toSQL();
        $stmt = $db->prepare($sql);
        $this->bindTo($stmt);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function getFetchAll($db){
        $sql = $this->toSQL();
        $stmt = $db->prepare($sql);
        $this->bindTo($stmt);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
