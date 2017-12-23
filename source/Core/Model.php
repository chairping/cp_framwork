<?php
namespace Core;

class Model {

    protected $table;
    protected $_mysqliDb;

    public $limit = null;
    public $pageSize = 20;
    public $page = 1;

    public $totalPages = 0;

    public function __construct() {
        $this->_mysqliDb = MysqliDb::getInstance();
    }

    public function __call($method, $arg = []) {
        //  继承model的类无法直接在外部调用受保护的方法 仅通过__call方法调用， 定义受保护的方法是为了配合__callStatic方法
        if (method_exists($this, $method)) {
            return call_user_func_array(array ($this, $method), $arg);
        }

        call_user_func_array([$this->_mysqliDb, $method], $arg);

        return $this;
    }

    /**
     * 根据id获取数据
     * @param $id
     * @param string $columns
     * @return array
     */
    protected function find($id, $columns = '*') {
        $this->_mysqliDb->where('id', $id);
        return $this->_mysqliDb->first($this->table, $columns);
    }

    /**
     * 获取一条记录
     * @param string  $columns 返回的字段 默认返回全部
     * @return array
     */
    protected function first($columns = '*') {
        return $this->_mysqliDb->first($this->table, $columns);
    }

    /**
     * 获取列表
     * @param string $columns 返回字段
     * @return array
     */
    protected function get($columns = '*') {
        $result = $this->_mysqliDb->get($this->table, $this->limit, $columns);

        $this->limit = null;

        return $result;
    }

    /**
     * 获取列表 获取列表并返回总数
     * @param string $columns
     * @return array
     */
    protected function getWithTotal($columns = '*') {
        $offset = $this->pageSize * ($this->page - 1);
        $result = $this->_mysqliDb->paginate($this->table, $offset, $this->pageSize, $columns);

        $this->page = 20;
        $this->pageSize = 1;

        return $result;
    }

    /**
     * 获取总数
     * @return int
     */
    protected function getCount() {
        return $this->_mysqliDb->count($this->table);
    }

    /**
     * 插入一条数据
     * @param $insertData
     * @return bool  返回新增的id
     */
    protected function insert($insertData) {
        return $this->_mysqliDb->insert($this->table, $insertData);
    }

    /**
     * 更新数据
     * @param $insertData
     * @return bool
     */
    protected function update($insertData) {
        return $this->_mysqliDb->update($this->table, $insertData);
    }

    /**
     * 删除一条数据
     * @return bool
     */
    protected function delete() {
        return $this->_mysqliDb->delete($this->table);
    }

    /**
     * 限制返回条数 仅 Model::get 使用
     * @param $limit
     * @return $this
     */
    protected function limit($limit) {
        $this->limit = $limit;

        return $this;
    }

    /**
     * 分页 仅 Model::getWithTotal 使用
     * @param $page
     * @param $pageSize
     * @return $this
     */
    protected function forPage($page, $pageSize) {
        $this->page = $page;
        $this->pageSize = $pageSize;

        return $this;
    }

    /**
     * @use Model::find(1); __callStatic方法仅能调用 该类中 不存在的方法 或 受保护的方法
     * @param $method
     * @param $arg
     * @return mixed|static
     */
    public static function __callStatic($method, $arg) {
        $obj = new static;

        $result = call_user_func_array([$obj, $method], $arg);
        if (method_exists($obj, $method)) {
            return $result;
        }

        return $obj;
    }
}