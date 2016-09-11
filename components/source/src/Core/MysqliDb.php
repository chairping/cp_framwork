<?php
namespace Core;

class MysqliDb {
    /**
     * MySQLi instance
     * @var mysqli
     */
    protected $_mysqli;

    private static $_instance;

    /**
     * 预处理的SQL语句
     * @var string
     */
    protected $_query;

    /**
     * 最后一条执行语句
     * @var string
     */
    protected $_lastQuery;

    protected $_where = [];
    protected $_orderBy = [];
    protected $_groupBy = [];
    /**
     * mysqli绑定数据
     * @var array
     */
    protected $_bindParams = ['']; // Create the empty 0 index

    public $count = 0;
    public $totalCount = 0;

    /**
     * Variable which holds last statement error
     * @var string
     */
    protected $_stmtError;

    protected $_lastInsertId = null;

    /**
     * 事务
     */
    protected $traceStartQ;
    protected $traceEnabled;
    protected $traceStripPrefix;
    public $trace = array();

    public function __construct($config = 'db')
    {
        $conf = config($config);

        if (!$conf) {
            throw new Exception("数据库配置信息不存在");
        }

        $this->connect($conf);
    }

    public static function getInstance($config = 'db') {
        if (self::$_instance == null) {
            self::$_instance = new self($config);
        }

        return self::$_instance;
    }

    /**
     * 连接数据库
     *
     * @throws Exception
     * @return void
     */
    public function connect($conf)
    {
        $this->_mysqli = new \mysqli($conf['host'], $conf['user'], $conf['pwd'], $conf['database'], $conf['port']);

        if ($this->_mysqli->connect_error) {
            throw new Exception('Connect Error ' . $this->_mysqli->connect_errno . ': ' . $this->_mysqli->connect_error);
        }

        $this->_mysqli->set_charset($conf['charset']);
    }

    public function reconnect($config = 'db') {
        $conf = config($config);

        $this->close();
        $this->connect($conf);
    }

    /**
     * 重置状态
     */
    protected function reset()
    {
        if ($this->traceEnabled) {
            $this->trace[] = array($this->_lastQuery, (microtime(true) - $this->traceStartQ), $this->_traceGetCaller());
        }

        $this->_where = array();
        $this->_orderBy = array();
        $this->_groupBy = array();
        $this->_bindParams = array(''); // Create the empty 0 index
        $this->_query = null;
        $this->_lastInsertId = null;
    }

    /**
     * 执行原生语句
     * @param string $query  例子  rayQuery('select * from user where name = ? ', [11] ).
     * @param array  $bindParams 绑定参数.
     * @return array
     */
    public function rawQuery($query, $bindParams = null)
    {
        $params = ['']; // Create the empty 0 index
        $this->_query = $query;
        $stmt = $this->_prepareQuery();

        if (is_array($bindParams)) {
            foreach ($bindParams as $prop => $val) {
                $params[0] .= $this->_determineType($val);
                array_push($params, $bindParams[$prop]);
            }

            call_user_func_array(array($stmt, 'bind_param'), $this->refValues($params));
        }

        $stmt->execute();
        $this->count = $stmt->affected_rows;
        $this->_stmtError = $stmt->error;

        $this->_lastQuery = $this->replacePlaceHolders($this->_query, $params);

        $res = $this->_dynamicBindResults($stmt);
        $this->reset();

        return $res;
    }

    /**
     *  执行原生语句 返回一条数据
     * @param string $query      rayQuery('select * from user where name = ? limit 1', [11] ).
     * @param array  $bindParams 绑定参数.
     * @return array|null
     */
    public function rawQueryOne($query, $bindParams = null)
    {
        $res = $this->rawQuery($query, $bindParams);
        if (is_array($res) && isset($res[0])) {
            return $res[0];
        }

        return null;
    }

    /**
     * 获取一条记录
     * @param string  $table
     * @param string  $columns 返回的字段 默认返回全部
     * @return array
     */
    public function first($table, $columns = '*')
    {
        $res = $this->get($table, 1, $columns);

        if (is_array($res) && isset($res[0])) {
            return $res[0];
        } elseif ($res) {
            return $res;
        }

        return null;
    }


    public function get($tableName, $numRows = null, $columns = '*')
    {
        if (empty($columns)) {
            $columns = '*';
        }

        $column = is_array($columns) ? implode(', ', $columns) : $columns;

        $this->_query = 'SELECT ' . $column . " FROM " . $tableName;
        $stmt = $this->_buildQuery($numRows);

        $stmt->execute();
        $this->_stmtError = $stmt->error;
        $res = $this->_dynamicBindResults($stmt);
        $this->reset();

        return $res;
    }

    /**
     * 插入一条数据
     * @param array $insertData .
     * @return bool Boolean indicating whether the insert query was completed succesfully.
     */
    public function insert($table, $insertData)
    {
        return $this->_buildInsert($table, $insertData);
    }

    /**
     * 更新数据. 执行前必须确保执行"where"方法.
     * @param array  $tableData 需要更新的数据.
     * @return bool
     */
    public function update($table, $tableData)
    {

        if (!$this->_where) {
            return false;
        }

        $this->_query = "UPDATE " . $table;

        $stmt = $this->_buildQuery(null, $tableData);
        $status = $stmt->execute();
        $this->reset();
        $this->_stmtError = $stmt->error;
        $this->count = $stmt->affected_rows;

        return $status;
    }

    /**
     * 删除语句. 执行前必须确保执行"where"方法 todo 限制条件.
     *
     * @param string  $table The name of the database table to work with.
     * @param int|array $numRows Array to define SQL limit in format Array ($count, $offset)
     *                               or only $count
     *
     * @return bool Indicates success. 0 or 1.
     */
    public function delete($table, $numRows = null)
    {
        if (!$this->_where) {
            return false;
        }

        $this->_query = "DELETE FROM " . $table;

        $stmt = $this->_buildQuery($numRows);
        $stmt->execute();
        $this->_stmtError = $stmt->error;
        $this->reset();

        return ($stmt->affected_rows > 0);
    }

    /**
     * @uses $MySqliDb->where('id', 7)->where('title', 'MyTitle');
     *
     * @param string $whereProp  字段名.
     * @param mixed  $whereValue 对应的值.
     * @param string $operator 操作符
     * @param string $cond 连接符 (OR, AND)
     *
     * @return MysqliDb
     */
    public function where($whereProp, $whereValue, $operator = '=', $cond = 'AND')
    {
        if (count($this->_where) == 0) {
            $cond = '';
        }

        $this->_where[] = array($cond, $whereProp, $operator, $whereValue);
        return $this;
    }

    /**
     * @uses $MySqliDb->orWhere('id', 7)->orWhere('title', 'MyTitle');
     *
     * @param string $whereProp  字段名.
     * @param mixed  $whereValue 对应的值.
     * @param string $operator 操作符
     *
     * @return MysqliDb
     */
    public function orWhere($whereProp, $whereValue, $operator = '=')
    {
        return $this->where($whereProp, $whereValue, $operator, 'OR');
    }

    /**
     * @uses $MySqliDb->orderBy('id', 'desc')->orderBy('name', 'desc');
     *
     * @param string $orderByField 字段名
     * @param string $orderbyDirection 排序规则
     *
     * @throws Exception
     * @return MysqliDb
     */
    public function orderBy($orderByField, $orderbyDirection = "DESC")
    {
        $allowedDirection = Array("ASC", "DESC");
        $orderbyDirection = strtoupper(trim($orderbyDirection));

        $orderByField = preg_replace("/[^-a-z0-9\.\(\),_`\*\'\"]+/i", '', $orderByField);

        if (empty($orderbyDirection) || !in_array($orderbyDirection, $allowedDirection)) {
            throw new Exception('Wrong order direction: ' . $orderbyDirection);
        }

        $this->_orderBy[$orderByField] = $orderbyDirection;
        return $this;
    }

    /**
     * @uses $MySqliDb->groupBy('name');
     *
     * @param string $groupByField 组名.
     *
     * @return MysqliDb
     */
    public function groupBy($groupByField)
    {
        $groupByField = preg_replace("/[^-a-z0-9\.\(\),_\*]+/i", '', $groupByField);

        $this->_groupBy[] = $groupByField;
        return $this;
    }

    /**
     * This methods returns the ID of the last inserted item
     *
     * @return int The last inserted item ID.
     */
    public function getInsertId()
    {
        return $this->_mysqli->insert_id;
    }

    /**
     * Escape harmful characters which might affect a query.
     *
     * @param string $str The string to escape.
     *
     * @return string The escaped string.
     */
    public function escape($str)
    {
        return $this->_mysqli->real_escape_string($str);
    }

    protected function _determineType($item)
    {
        switch (gettype($item)) {
            case 'NULL':
            case 'string':
                return 's';
                break;

            case 'boolean':
            case 'integer':
                return 'i';
                break;

            case 'blob':
                return 'b';
                break;

            case 'double':
                return 'd';
                break;
        }
        return '';
    }

    protected function _bindParam($value)
    {
        $this->_bindParams[0] .= $this->_determineType($value);
        array_push($this->_bindParams, $value);
    }

    protected function _bindParams($values)
    {
        foreach ($values as $value) {
            $this->_bindParam($value);
        }
    }

    /**
     * Helper function to add variables into bind parameters array and will return
     * its SQL part of the query according to operator in ' $operator ?' or
     * ' $operator ($subquery) ' formats
     *
     * @param string $operator
     * @param mixed $value Variable with values
     *
     * @return string
     */
    protected function _buildPair($operator, $value)
    {
        $this->_bindParam($value);
        return ' ' . $operator . ' ? ';
    }

    /**
     * @param array $insertData Data containing information for inserting into the DB.
     * @param string $operation Type of operation (INSERT)
     *
     * @return bool Boolean
     */
    private function _buildInsert($table, $insertData)
    {
        $this->_query = "INSERT INTO " . $table;
        $stmt = $this->_buildQuery(null, $insertData);
        $status = $stmt->execute();
        $this->_stmtError = $stmt->error;
        $haveOnDuplicate = !empty($this->_updateColumns);
        $this->reset();
        $this->count = $stmt->affected_rows;

        if ($stmt->affected_rows < 1) {
            // in case of onDuplicate() usage, if no rows were inserted
            if ($status && $haveOnDuplicate) {
                return true;
            }
            return false;
        }

        if ($stmt->insert_id > 0) {
            return $stmt->insert_id;
        }

        return true;
    }

    /**
     * Abstraction method that will compile the WHERE statement,
     * any passed update data, and the desired rows.
     * It then builds the SQL query.
     *
     * @param int|array $numRows Array to define SQL limit in format Array ($count, $offset)
     *                               or only $count
     * @param array $tableData Should contain an array of data for updating the database.
     *
     * @return mysqli_stmt Returns the $stmt object.
     */
    protected function _buildQuery($numRows = null, $tableData = null)
    {
        $this->_buildInsertQuery($tableData);
        $this->_buildCondition('WHERE', $this->_where);
        $this->_buildGroupBy();
        $this->_buildOrderBy();
        $this->_buildLimit($numRows);

        $this->_lastQuery = $this->replacePlaceHolders($this->_query, $this->_bindParams);

        // Prepare query
        $stmt = $this->_prepareQuery();

        // Bind parameters to statement if any
        if (count($this->_bindParams) > 1) {
            call_user_func_array(array($stmt, 'bind_param'), $this->refValues($this->_bindParams));
        }

        return $stmt;
    }

    /**
     * This helper method takes care of prepared statements' "bind_result method
     * , when the number of variables to pass is unknown.
     *
     * @param mysqli_stmt $stmt Equal to the prepared statement object.
     *
     * @return array The results of the SQL fetch.
     */
    protected function _dynamicBindResults(\mysqli_stmt $stmt)
    {
        $parameters = array();
        $results = array();
        /**
         * @see http://php.net/manual/en/mysqli-result.fetch-fields.php
         */
        $mysqlLongType = 252;
        $shouldStoreResult = false;

        $meta = $stmt->result_metadata();

        // if $meta is false yet sqlstate is true, there's no sql error but the query is
        // most likely an update/insert/delete which doesn't produce any results
        if (!$meta && $stmt->sqlstate)
            return array();

        $row = array();
        while ($field = $meta->fetch_field()) {
            if ($field->type == $mysqlLongType) {
                $shouldStoreResult = true;
            }

            $row[$field->name] = null;
            $parameters[] = & $row[$field->name];
        }

        // avoid out of memory bug in php 5.2 and 5.3. Mysqli allocates lot of memory for long*
        // and blob* types. So to avoid out of memory issues store_result is used
        // https://github.com/joshcam/PHP-MySQLi-Database-Class/pull/119
        if ($shouldStoreResult) {
            $stmt->store_result();
        }

        call_user_func_array(array($stmt, 'bind_result'), $parameters);

        $this->totalCount = 0;
        $this->count = 0;

        while ($stmt->fetch()) {

            $result = array();
            foreach ($row as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $k => $v) {
                        $result[$key][$k] = $v;
                    }
                } else {
                    $result[$key] = $val;
                }
            }

            $this->count++;
            array_push($results, $result);
        }

        if ($shouldStoreResult) {
            $stmt->free_result();
        }

        $stmt->close();

        // stored procedures sometimes can return more then 1 resultset
        if ($this->_mysqli->more_results()) {
            $this->_mysqli->next_result();
        }

        return $results;
    }

    /**
     * Insert/Update query helper
     * @param array $tableData
     * @param array $tableColumns
     * @param bool $isInsert INSERT operation flag
     *
     * @throws Exception
     */
    public function _buildDataPairs($tableData, $tableColumns, $isInsert)
    {
        foreach ($tableColumns as $column) {
            $value = $tableData[$column];

            if (!$isInsert) {
                $this->_query .= "`" . $column . "` = ";
            }

            // Simple value
            if (!is_array($value)) {
                $this->_bindParam($value);
                $this->_query .= '?, ';
                continue;
            }

            // Function value
            $key = key($value);
            $val = $value[$key];
            switch ($key) {
                case '[I]':
                    $this->_query .= $column . $val . ", ";
                    break;
                case '[F]':
                    $this->_query .= $val[0] . ", ";
                    if (!empty($val[1])) {
                        $this->_bindParams($val[1]);
                    }
                    break;
                case '[N]':
                    if ($val == null) {
                        $this->_query .= "!" . $column . ", ";
                    } else {
                        $this->_query .= "!" . $val . ", ";
                    }
                    break;
                default:
                    throw new Exception("Wrong operation");
            }
        }
        $this->_query = rtrim($this->_query, ', ');
    }

    /**
     * Abstraction method that will build an INSERT or UPDATE part of the query
     * @param array $tableData
     */
    protected function _buildInsertQuery($tableData)
    {
        if (!is_array($tableData)) {
            return;
        }

        $isInsert = preg_match('/^[INSERT|REPLACE]/', $this->_query);
        $dataColumns = array_keys($tableData);
        if ($isInsert) {
            if (isset ($dataColumns[0]))
                $this->_query .= ' (`' . implode($dataColumns, '`, `') . '`) ';
            $this->_query .= ' VALUES (';
        } else {
            $this->_query .= " SET ";
        }

        $this->_buildDataPairs($tableData, $dataColumns, $isInsert);

        if ($isInsert) {
            $this->_query .= ')';
        }
    }

    /**
     * Abstraction method that will build the part of the WHERE conditions
     *
     * @param string $operator
     * @param array $conditions
     */
    protected function _buildCondition($operator, &$conditions)
    {
        if (empty($conditions)) {
            return;
        }

        //Prepare the where portion of the query
        $this->_query .= ' ' . $operator;

        foreach ($conditions as $cond) {
            list ($concat, $varName, $operator, $val) = $cond;
            $this->_query .= " " . $concat . " " . $varName;

            switch (strtolower($operator)) {
                case 'not in':
                case 'in':
                    $comparison = ' ' . $operator . ' (';
                    if (is_object($val)) {
                        $comparison .= $this->_buildPair("", $val);
                    } else {
                        foreach ($val as $v) {
                            $comparison .= ' ?,';
                            $this->_bindParam($v);
                        }
                    }
                    $this->_query .= rtrim($comparison, ',') . ' ) ';
                    break;
                case 'not between':
                case 'between':
                    $this->_query .= " $operator ? AND ? ";
                    $this->_bindParams($val);
                    break;
                case 'not exists':
                case 'exists':
                    $this->_query.= $operator . $this->_buildPair("", $val);
                    break;
                default:
                    if (is_array($val)) {
                        $this->_bindParams($val);
                    } elseif ($val === null) {
                        $this->_query .= ' ' . $operator . " NULL";
                    } elseif ($val != 'DBNULL' || $val == '0') {
                        $this->_query .= $this->_buildPair($operator, $val);
                    }
            }
        }
    }

    protected function _buildGroupBy()
    {
        if (empty($this->_groupBy)) {
            return;
        }

        $this->_query .= " GROUP BY ";

        foreach ($this->_groupBy as $key => $value) {
            $this->_query .= $value . ", ";
        }

        $this->_query = rtrim($this->_query, ', ') . " ";
    }

    protected function _buildOrderBy()
    {
        if (empty($this->_orderBy)) {
            return;
        }

        $this->_query .= " ORDER BY ";
        foreach ($this->_orderBy as $prop => $value) {
            if (strtolower(str_replace(" ", "", $prop)) == 'rand()') {
                $this->_query .= "rand(), ";
            } else {
                $this->_query .= $prop . " " . $value . ", ";
            }
        }

        $this->_query = rtrim($this->_query, ', ') . " ";
    }

    /**
     * limit 拼装
     * @param int|array
     */
    protected function _buildLimit($numRows)
    {
        if (!isset($numRows)) {
            return;
        }

        if (is_array($numRows)) {
            $this->_query .= ' LIMIT ' . (int) $numRows[0] . ', ' . (int) $numRows[1];
        } else {
            $this->_query .= ' LIMIT ' . (int) $numRows;
        }
    }

    /**
     * mysqli 预处理器
     * @return mysqli_stmt
     */
    protected function _prepareQuery()
    {
        if (!$stmt = $this->_mysqli->prepare($this->_query)) {
            $msg = "Problem preparing query ($this->_query) " . $this->_mysqli->error;
            $this->reset();
            throw new Exception($msg);
        }

        if ($this->traceEnabled) {
            $this->traceStartQ = microtime(true);
        }

        return $stmt;
    }

    public function __destruct() {
        $this->close();
    }

    public function close() {
        if ($this->_mysqli) {
            $this->_mysqli->close();
            $this->_mysqli = null;
        }
    }

    protected function refValues(array &$arr)
    {
        $refs = array();
        foreach ($arr as $key => $value) {
            $refs[$key] = & $arr[$key];
        }
        return $refs;
    }

    /**
     * Function to replace ? with variables from bind variable
     *
     * @param string $str
     * @param array $vals
     *
     * @return string
     */
    protected function replacePlaceHolders($str, $vals)
    {
        $i = 1;
        $newStr = "";

        if (empty($vals)) {
            return $str;
        }

        while ($pos = strpos($str, "?")) {
            $val = $vals[$i++];
            if (is_object($val)) {
                $val = '[object]';
            }
            if ($val === null) {
                $val = 'NULL';
            }
            $newStr .= substr($str, 0, $pos) . "'" . $val . "'";
            $str = substr($str, $pos + 1);
        }
        $newStr .= $str;
        return $newStr;
    }

    /**
     * 获取最后一条执行语句
     * @return string
     */
    public function getLastQuery()
    {
        return $this->_lastQuery;
    }

    /**
     * 获取mysqli错误信息
     * @return string
     */
    public function getLastError()
    {
        if (!$this->_mysqli) {
            return "mysqli is null";
        }
        return trim($this->_stmtError . " " . $this->_mysqli->error);
    }

    /* Helper functions */
    /**
     * Method returns a copy of a mysqlidb subquery object
     *
     * @return MysqliDb new mysqlidb object
     */
    public function copy()
    {
        $copy = unserialize(serialize($this));
        $copy->_mysqli = null;
        return $copy;
    }

    public function ping() {
        return $this->_mysqli->ping();
    }

    /**
     * 开始事务
     *
     * @uses mysqli->autocommit(false)
     * @uses register_shutdown_function(array($this, "_transaction_shutdown_check"))
     */
    public function startTransaction()
    {
        $this->_mysqli->autocommit(false);
        $this->_transaction_in_progress = true;
        register_shutdown_function(array($this, "_transaction_status_check"));
    }

    /**
     * 事务提交
     *
     * @uses mysqli->commit();
     * @uses mysqli->autocommit(true);
     */
    public function commit()
    {
        $result = $this->_mysqli->commit();
        $this->_transaction_in_progress = false;
        $this->_mysqli->autocommit(true);
        return $result;
    }

    /**
     * 事务回滚
     *
     * @uses mysqli->rollback();
     * @uses mysqli->autocommit(true);
     */
    public function rollback()
    {
        $result = $this->_mysqli->rollback();
        $this->_transaction_in_progress = false;
        $this->_mysqli->autocommit(true);
        return $result;
    }

    /**
     * Shutdown handler to rollback uncommited operations in order to keep
     * atomic operations sane.
     *
     * @uses mysqli->rollback();
     */
    public function _transaction_status_check()
    {
        if (!$this->_transaction_in_progress) {
            return;
        }
        $this->rollback();
    }

    /**
     * Query exection time tracking switch
     *
     * @param bool $enabled Enable execution time tracking
     * @param string $stripPrefix Prefix to strip from the path in exec log
     *
     * @return MysqliDb
     */
    public function setTrace($enabled, $stripPrefix = null)
    {
        $this->traceEnabled = $enabled;
        $this->traceStripPrefix = $stripPrefix;
        return $this;
    }

    /**
     * Get where and what function was called for query stored in MysqliDB->trace
     *
     * @return string with information
     */
    private function _traceGetCaller()
    {
        $dd = debug_backtrace();
        $caller = next($dd);
        while (isset($caller) && $caller["file"] == __FILE__) {
            $caller = next($dd);
        }

        return __CLASS__ . "->" . $caller["function"] . "() >>  file \"" .
        str_replace($this->traceStripPrefix, '', $caller["file"]) . "\" line #" . $caller["line"] . " ";
    }

    /**
     * 分页
     * @param int $offset 偏移量
     * @param int $pageLimit 分页大小
     * @param array|string
     * @return array
     */
    public function paginate($table, $offset, $pageLimit, $columns = null) {
        $where = $this->_where;
        $res = $this->get($table, [$offset, $pageLimit], $columns);

        return ['list' => $res, 'total' => $this->count($table, $where)];
    }

    public function count($table, $where = null) {
        if ($where) {
            $this->_where = $where;
        }
        $this->_query = "SELECT count('*') as count FROM " . $table;

        $this->_buildCondition('WHERE', $this->_where);
        $this->_buildLimit(1);
//
        $this->_lastQuery = $this->replacePlaceHolders($this->_query, $this->_bindParams);
//        // Prepare query
        $stmt = $this->_prepareQuery();

        if (count($this->_bindParams) > 1) {
            call_user_func_array(array($stmt, 'bind_param'), $this->refValues($this->_bindParams));
        }
//
        $stmt->execute();
        $this->_stmtError = $stmt->error;
        $res = $this->_dynamicBindResults($stmt);
        $this->reset();


        return $res && isset($res[0]) ? $res[0]['count'] : 0;
    }
}

