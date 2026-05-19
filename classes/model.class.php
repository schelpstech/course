<?php

class model
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
        if (empty($data) || !is_array($data)) {
            return false;
        }

        /**
         * ============================
         * TABLE VALIDATION (NEW SAFETY LAYER)
         * ============================
         */
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return false;
        }

        $columns = [];
        $placeholders = [];

        $sql = "INSERT INTO $table (";
        $params = [];

        /**
         * ============================
         * BUILD COLUMNS + VALIDATE KEYS
         * ============================
         */
        foreach ($data as $key => $val) {

            // column safety check
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $key)) {
                return false;
            }

            $columns[] = $key;
            $placeholders[] = ":" . $key;
            $params[":$key"] = $val;
        }

        $sql .= implode(',', $columns);
        $sql .= ") VALUES (" . implode(',', $placeholders) . ")";

        /**
         * ============================
         * EXECUTE
         * ============================
         */
        $query = $this->db->prepare($sql);

        if (!$query->execute($params)) {
            return false;
        }

        return $this->db->lastInsertId();
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

    public function rawQuery($sql)
    {
        $query = $this->db->prepare($sql);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
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

            $colvalSet = [];
            $whereSql  = [];
            $params    = [];

            /**
             * ===========================
             * BUILD SET PART SAFELY
             * ===========================
             */
            foreach ($data as $key => $val) {
                $colvalSet[] = "$key = :set_$key";
                $params["set_$key"] = $val;
            }

            /**
             * ===========================
             * BUILD WHERE PART SAFELY
             * ===========================
             */
            if (!empty($conditions) && is_array($conditions)) {

                foreach ($conditions as $key => $value) {
                    $whereSql[] = "$key = :where_$key";
                    $params["where_$key"] = $value;
                }
            }

            $sql = "UPDATE $table SET " . implode(', ', $colvalSet);

            if (!empty($whereSql)) {
                $sql .= " WHERE " . implode(' AND ', $whereSql);
            }

            $query = $this->db->prepare($sql);
            $update = $query->execute($params);

            return $update ? $query->rowCount() : false;
        }

        return false;
    }

    public function Newupdate($table, $data, $conditions)
    {
        if (empty($data) || !is_array($data)) {
            return false;
        }

        /**
         * ==========================
         * TABLE WHITELIST (IMPORTANT)
         * ==========================
         */
        $allowedTables = ['payments', 'users','students', 'semesterregistration', 'semesters'];

        if (!in_array($table, $allowedTables)) {
            return false;
        }

        $setParts = [];
        $whereParts = [];
        $params = [];

        /**
         * ==========================
         * BUILD SET CLAUSE
         * ==========================
         */
        foreach ($data as $column => $value) {

            // validate column name
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
                return false;
            }

            $setParts[] = "$column = ?";
            $params[] = $value;
        }

        /**
         * ==========================
         * BUILD WHERE CLAUSE
         * ==========================
         */
        if (!empty($conditions) && is_array($conditions)) {

            foreach ($conditions as $column => $value) {

                // validate column name
                if (!preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
                    return false;
                }

                $whereParts[] = "$column = ?";
                $params[] = $value;
            }
        } else {
            return false;
        }

        $setSql = implode(', ', $setParts);
        $whereSql = implode(' AND ', $whereParts);

        $sql = "UPDATE $table SET $setSql WHERE $whereSql";

        $query = $this->db->prepare($sql);

        if (!$query->execute($params)) {
            return false;
        }

        return [
            "success" => true,
            "affected_rows" => $query->rowCount()
        ];
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

    public function query($sql, $params = [])
    {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {

            // Log internally
            error_log($e->getMessage());

            // Return safe message
            throw new Exception("Database query failed");
        }
    }

    public function queryOne($sql, $params = [])
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
