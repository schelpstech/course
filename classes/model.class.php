<?php

class Model
{
    // Refer to database connection
    private $db;

    // Instantiate object with database connection
    public function __construct($db_conn)
    {
        $this->db = $db_conn;
    }
    public function beginTransaction()
    {
        return $this->db->beginTransaction();
    }

    public function commit()
    {
        return $this->db->commit();
    }

    public function rollBack()
    {
        if ($this->db->inTransaction()) {
            return $this->db->rollBack();
        }
    }
    public function inTransaction()
    {
        return $this->db->inTransaction();
    }



    public function insert_data($table, $data)
    {
        if (!empty($data) && is_array($data)) {
            $columns = '';
            $values  = '';
            $i = 0;


            $columnString = implode(',', array_keys($data));
            $valueString = ":" . implode(',:', array_keys($data));
            $sql = "INSERT INTO " . $table . " (" . $columnString . ") VALUES (" . $valueString . ")";
            $query = $this->db->prepare($sql);
            foreach ($data as $key => $val) {
                $query->bindValue(':' . $key, $val);
            }
            $insert = $query->execute();
            return $insert ? $this->db->lastInsertId() : false;
        } else {
            return false;
        }
    }


    public function getRows($table, $conditions = array())
    {
        $sql = 'SELECT ';
        $sql .= array_key_exists("select", $conditions) ? $conditions['select'] : '*';
        $sql .= ' FROM ' . $table;

        // Handle joins
        if (array_key_exists("join", $conditions)) {
            foreach ($conditions['join'] as $table => $condition) {
                $sql .= " INNER JOIN $table $condition";
            }
        }
        if (array_key_exists("leftjoin", $conditions)) {
            $sql .= ' LEFT JOIN ' . $conditions['leftjoin'];
        }
        if (array_key_exists("joinx", $conditions)) {
            foreach ($conditions['joinx'] as $key => $value) {
                $sql .= ' INNER JOIN ' . $key . $value;
            }
        }
        if (array_key_exists("joinl", $conditions)) {
            foreach ($conditions['joinl'] as $key => $value) {
                $sql .= ' LEFT JOIN ' . $key . $value;
            }
        }

        // Handle WHERE conditions
        $whereClauses = [];

        if (array_key_exists("where", $conditions)) {
            foreach ($conditions['where'] as $key => $value) {
                if (is_array($value)) {
                    // Handle IN clause
                    $placeholders = implode(',', array_map(fn($v) => $this->db->quote($v), $value));
                    $whereClauses[] = "$key IN ($placeholders)";
                } else {
                    $whereClauses[] = "$key = " . $this->db->quote($value);
                }
            }
        }

        // Support for raw WHERE conditions (e.g., subqueries)
        if (array_key_exists("where_raw", $conditions)) {
            $whereClauses[] = $conditions['where_raw'];
        }

        if (!empty($whereClauses)) {
            $sql .= ' WHERE ' . implode(' AND ', $whereClauses);
        }

        // Additional conditional operators
        if (array_key_exists("where_not", $conditions)) {
            foreach ($conditions['where_not'] as $key => $value) {
                $sql .= " AND $key != " . $this->db->quote($value);
            }
        }
        if (array_key_exists("where_greater_equals", $conditions)) {
            foreach ($conditions['where_greater_equals'] as $key => $value) {
                $sql .= " AND $key >= " . $this->db->quote($value);
            }
        }
        if (array_key_exists("where_lesser_equals", $conditions)) {
            foreach ($conditions['where_lesser_equals'] as $key => $value) {
                $sql .= " AND $key <= " . $this->db->quote($value);
            }
        }
        if (array_key_exists("where_lesser", $conditions)) {
            foreach ($conditions['where_lesser'] as $key => $value) {
                $sql .= " AND $key < " . $this->db->quote($value);
            }
        }
        if (array_key_exists("where_greater", $conditions)) {
            foreach ($conditions['where_greater'] as $key => $value) {
                $sql .= " AND $key > " . $this->db->quote($value);
            }
        }

        // Handle GROUP BY, ORDER BY, LIMIT
        if (array_key_exists("group_by", $conditions)) {
            $sql .= ' GROUP BY ' . $conditions['group_by'];
        }
        if (array_key_exists("order_by", $conditions)) {
            $sql .= ' ORDER BY ' . $conditions['order_by'];
        }
        if (array_key_exists("limit", $conditions)) {
            if (array_key_exists("start", $conditions)) {
                $sql .= ' LIMIT ' . $conditions['start'] . ', ' . $conditions['limit'];
            } else {
                $sql .= ' LIMIT ' . $conditions['limit'];
            }
        }

        // Prepare and execute query
        $query = $this->db->prepare($sql);
        $query->execute();

        // Handle return type
        if (array_key_exists("return_type", $conditions) && $conditions['return_type'] != 'all') {
            switch ($conditions['return_type']) {
                case 'count':
                    return $query->rowCount();
                case 'single':
                    return $query->fetch(PDO::FETCH_ASSOC);
                default:
                    return false;
            }
        } else {
            return $query->rowCount() > 0 ? $query->fetchAll(PDO::FETCH_ASSOC) : false;
        }
    }



    public function countRows($table, $conditions = [])
    {
        $sql = "SELECT COUNT(*) as total_row FROM {$table}";
        $params = [];

        if (!empty($conditions['where'])) {
            $sql .= " WHERE ";
            $whereParts = [];

            foreach ($conditions['where'] as $key => $value) {
                $paramKey = ":{$key}";
                $whereParts[] = "{$key} = {$paramKey}";
                $params[$paramKey] = $value;
            }

            $sql .= implode(' AND ', $whereParts);
        }

        $query = $this->db->prepare($sql);
        $query->execute($params);

        $row = $query->fetch(PDO::FETCH_ASSOC);
        return $row['total_row'] ?? 0;
    }

    public function upDate($table, $data, $conditions)
    {
        if (!empty($data) && is_array($data)) {
            $colvalSet = '';
            $whereSql = '';
            $i = 0;

            foreach ($data as $key => $val) {
                $pre = ($i > 0) ? ', ' : '';
                $colvalSet .= $pre . $key . "='" . $val . "'";
                $i++;
            }
            if (!empty($conditions) && is_array($conditions)) {
                $whereSql .= ' WHERE ';
                $i = 0;
                foreach ($conditions as $key => $value) {
                    $pre = ($i > 0) ? ' AND ' : '';
                    $whereSql .= $pre . $key . " = '" . $value . "'";
                    $i++;
                }
            }
            $sql = "UPDATE " . $table . " SET " . $colvalSet . $whereSql;
            $query = $this->db->prepare($sql);
            $update = $query->execute();
            return $update ? $query->rowCount() : false;
        } else {
            return false;
        }
    }

    /**
     * Delete records
     */
    public function delete($table, $condition = "1=1")
    {
        $params = [];

        if (is_array($condition)) {
            $whereParts = [];
            foreach ($condition as $key => $value) {
                $paramKey = ":where_" . $key;
                $whereParts[] = "$key = $paramKey";
                $params[$paramKey] = $value; // bind value later
            }
            $conditionSql = implode(" AND ", $whereParts);
        } else {
            $conditionSql = $condition; // raw string condition
        }

        $sql = "DELETE FROM {$table} WHERE {$conditionSql}";
        $stmt = $this->db->prepare($sql);

        foreach ($params as $param => $val) {
            $stmt->bindValue($param, $val);
        }

        return $stmt->execute();
    }
    // Log Out User
    public function log_out_user()
    {
        session_unset();
        session_destroy();
    }

    public function sumQuery($table, $column, $conditions = [])
    {
        $sql = "SELECT SUM($column) as total_sum FROM $table";

        if (array_key_exists("where", $conditions)) {
            $sql .= ' WHERE ';
            $i = 0;
            foreach ($conditions['where'] as $key => $value) {
                $pre = ($i > 0) ? ' AND ' : '';
                $sql .= $pre . $key . " = :" . $key;
                $i++;
            }
        }

        $query = $this->db->prepare($sql);

        // Bind parameters
        if (array_key_exists("where", $conditions)) {
            foreach ($conditions['where'] as $key => $value) {
                $query->bindValue(':' . $key, $value);
            }
        }

        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result['total_sum'] ?? 0;
    }

    public function exists($table, $conditions = [])
    {
        $sql = "SELECT 1 FROM {$table}";

        $params = [];

        // WHERE clause
        if (!empty($conditions)) {
            $sql .= " WHERE ";
            $i = 0;

            foreach ($conditions as $key => $value) {
                $pre = ($i > 0) ? " AND " : "";
                $sql .= $pre . "{$key} = :{$key}";
                $params[":{$key}"] = $value;
                $i++;
            }
        }

        // limit to 1 row for performance
        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
    }

    public function getById($table, $id, $idColumn = 'id')
    {
        $sql = "SELECT * FROM {$table} WHERE {$idColumn} = :id LIMIT 1";

        $query = $this->db->prepare($sql);
        $query->bindParam(':id', $id);
        $query->execute();

        return $query->fetch(PDO::FETCH_ASSOC);
    }
}
