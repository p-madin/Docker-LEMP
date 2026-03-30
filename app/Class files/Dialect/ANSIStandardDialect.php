<?php

abstract class ANSIStandardDialect implements DatabaseDialect {

    public function compileSelect(array $components): string {
        $sql = "SELECT ";

        // 1. Columns
        $columns = $components['columns'] ?? ['*'];
        $quotedColumns = array_map(function($col) {
            if ($col instanceof QueryRaw) return (string)$col;
            if ($col === '*') return '*';
            
            $quoted = $this->quoteIdentifier($col);
            
            // Extract the leaf column name (e.g., 'scName' from 'sysConfig.scName' or 'scName')
            $lastDot = strrpos($col, '.');
            $originalName = ($lastDot === false) ? $col : substr($col, $lastDot + 1);
            
            // Only alias if the original name is different from its lowercased version.
            // This ensures that 'scName' gets aliased to preserve case, but 'id' does not.
            if (strtolower($originalName) !== $originalName) {
                return $quoted . ' AS ' . $this->quoteIdentifier($originalName, false);
            }
            
            return $quoted;
        }, $columns);
        $sql .= implode(', ', $quotedColumns);

        // 2. FROM
        if (!empty($components['from'])) {
            $sql .= " FROM " . $this->quoteIdentifier($components['from']);
        } else {
            throw new \Exception("QueryBuilder: SELECT requires a FROM clause.");
        }

        // 3. JOINS
        if (!empty($components['joins'])) {
            $sql .= $this->compileJoins($components['joins']);
        }

        // 4. WHERES
        if (!empty($components['wheres'])) {
            $sql .= " " . $this->compileWheres($components['wheres']);
        }

        // 5. GROUP BY
        if (!empty($components['groups'])) {
            $sql .= " GROUP BY " . $this->compileGroups($components['groups']);
        }

        // 6. ORDER BY
        if (!empty($components['orders'])) {
            $sql .= " " . $this->compileOrders($components['orders']);
        }

        // 5. LIMIT / OFFSET
        $pagination = $this->compileLimitOffset($components);
        if ($pagination !== '') {
            $sql .= " " . $pagination;
        }

        return $sql;
    }

    public function compileInsert(string $table, array $params): string {
        $quotedTable = $this->quoteIdentifier($table);
        $quotedCols = [];
        $placeholders = [];
        
        foreach ($params as $col => $paramName) {
            $quotedCols[] = $this->quoteIdentifier($col);
            if ($paramName instanceof QueryRaw) {
                $placeholders[] = (string)$paramName;
            } else {
                $placeholders[] = ":" . $paramName;
            }
        }

        return "INSERT INTO " . $quotedTable . " (" . implode(', ', $quotedCols) . ") VALUES (" . implode(', ', $placeholders) . ")";
    }

    public function compileUpdate(string $table, array $params, array $wheres): string {
        $quotedTable = $this->quoteIdentifier($table);
        $setClauses = [];
        
        foreach ($params as $col => $paramName) {
            if ($paramName instanceof QueryRaw) {
                $setClauses[] = $this->quoteIdentifier($col) . " = " . (string)$paramName;
            } else {
                $setClauses[] = $this->quoteIdentifier($col) . " = :" . $paramName;
            }
        }

        $sql = "UPDATE " . $quotedTable . " SET " . implode(', ', $setClauses);

        if (!empty($wheres)) {
            $sql .= " " . $this->compileWheres($wheres);
        }

        return $sql;
    }

    public function compileDelete(string $table, array $wheres): string {
        $quotedTable = $this->quoteIdentifier($table);
        $sql = "DELETE FROM " . $quotedTable;

        if (!empty($wheres)) {
            $sql .= " " . $this->compileWheres($wheres);
        }

        return $sql;
    }

    protected function compileWheres(array $wheres): string {
        $sql = "WHERE ";
        foreach ($wheres as $i => $where) {
            if ($i > 0) {
                $sql .= " " . $where['boolean'] . " ";
            }

            $quotedCol = $this->quoteIdentifier($where['column']);

            switch ($where['type']) {
                case 'Basic':
                    $sql .= $quotedCol . " " . $where['operator'] . " :" . $where['paramName'];
                    break;
                case 'In':
                    $placeholders = array_map(function($p) { return ":" . $p; }, $where['paramNames']);
                    $operator = $where['not'] ? "NOT IN" : "IN";
                    $sql .= $quotedCol . " " . $operator . " (" . implode(', ', $placeholders) . ")";
                    break;
                case 'Null':
                    $operator = $where['not'] ? "IS NOT NULL" : "IS NULL";
                    $sql .= $quotedCol . " " . $operator;
                    break;
                case 'Raw':
                    $sql .= $quotedCol . " " . $where['operator'] . " " . $where['value'];
                    break;
            }
        }
        return $sql;
    }

    protected function compileGroups(array $groups): string {
        $quoted = array_map(function($group) {
            return ($group instanceof QueryRaw) ? (string)$group : $this->quoteIdentifier($group);
        }, $groups);
        return implode(', ', $quoted);
    }

    protected function compileOrders(array $orders): string {
        $clauses = [];
        foreach ($orders as $order) {
            $col = ($order['column'] instanceof QueryRaw) ? (string)$order['column'] : $this->quoteIdentifier($order['column']);
            $clauses[] = $col . " " . strtoupper($order['direction']);
        }
        return "ORDER BY " . implode(', ', $clauses);
    }

    /**
     * Compile JOIN clauses.
     */
    protected function compileJoins(array $joins): string {
        $sql = "";
        foreach ($joins as $join) {
            $sql .= " " . strtoupper($join['type']) . " JOIN " . $this->quoteIdentifier($join['table']);
            $sql .= " ON " . $this->quoteIdentifier($join['first']) . " " . $join['operator'] . " " . $this->quoteIdentifier($join['second']);
        }
        return $sql;
    }

    /**
     * Standard ANSI SQL (SQL:2008) limit/offset using named placeholders
     */
    public function compileLimitOffset(array $components): string {
        $sql = "";
        
        if (isset($components['offsetParam'])) {
            $sql .= "OFFSET :" . $components['offsetParam'] . " ROWS";

            if (isset($components['limitParam'])) {
                $sql .= " FETCH NEXT :" . $components['limitParam'] . " ROWS ONLY";
            }
        } elseif (isset($components['limitParam'])) {
            // ANSI requires OFFSET to be present for FETCH NEXT in some strict interpretations, 
            // but we'll try FETCH NEXT alone if no offset.
            $sql .= "FETCH NEXT :" . $components['limitParam'] . " ROWS ONLY";
        }
        
        return $sql;
    }

    /**
     * Standard ANSI identifier quoting uses double quotes.
     */
    public function quoteIdentifier(string $identifier, bool $normalize = true): string {
        if ($identifier === '*') {
            return '*';
        }

        if (strpos($identifier, '.') !== false) {
            $parts = explode('.', $identifier);
            return implode('.', array_map(function($part) use ($normalize) {
                return $this->quoteIdentifier($part, $normalize);
            }, $parts));
        }

        // Normalize to lowercase for portability across systems like PostgreSQL
        if ($normalize) {
            $identifier = strtolower($identifier);
        }

        return '"' . str_replace('"', '""', $identifier) . '"';
    }
}
