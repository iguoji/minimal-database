<?php
declare(strict_types=1);

namespace Minimal\Database;

use PDO;
use Minimal\Support\Context;

/**
 * Mysql
 */
class Mysql
{
    /**
     * 开启事务
     */
    public function beginTransaction() : bool
    {
        Context::incr('database:transaction:level');
        if (Context::get('database:transaction:level') === 1) {
            $sql = 'BEGIN';
        } else {
            $sql = 'SAVEPOINT TRANS' . Context::get('database:transaction:level');
        }
        return $this->hello($sql);
    }

    /**
     * 是否在事务中
     */
    public function inTransaction() : bool
    {
        return Context::has('database:transaction:level') && Context::get('database:transaction:level') >= 1;
    }

    /**
     * 提交事务
     */
    public function commit() : bool
    {
        $level = Context::decr('database:transaction:level');
        if ($level === 0) {
            return $this->hello('COMMIT');
        } else {
            return true;
        }
    }

    /**
     * 事务回滚
     */
    public function rollBack() : bool
    {
        $level = Context::get('database:transaction:level');
        if ($level === 1) {
            $sql = 'ROLLBACK';
        } else if ($level > 1) {
            $sql = 'ROLLBACK TO SAVEPOINT TRANS' . $level;
        }
        $level = max(0, $level - 1);
        Context::set('database:transaction:level', $level);
        return isset($sql) ? $this->hello($sql) : true;
    }

    /**
     * 查询数据
     */
    public function query(string $sql, array $data = []) : array
    {
        return $this->hello($sql, $data, [ 'fetch' => [PDO::FETCH_ASSOC, 'fetchAll'] ]);
    }

    /**
     * 获取一行
     */
    public function first(string $sql, array $data = []) : mixed
    {
        return $this->hello($sql, $data, [ 'fetch' => [PDO::FETCH_ASSOC, 'fetch'] ]);
    }

    /**
     * 查询数值
     */
    public function number(string $sql, array $data = []) : mixed
    {
        return $this->hello($sql, $data, [ 'fetch' => [PDO::FETCH_NUM, 'fetchColumn'] ]);
    }

    /**
     * 操作数据
     */
    public function execute(string $sql, array $data = []) : int
    {
        return $this->hello($sql, $data);
    }

    /**
     * 最后的Sql
     */
    public function getLastSql() : string
    {
        return Context::get('database:sql');
    }

    /**
     * 和数据库交互
     */
    public function hello(string $sql, array $data = [], array $context = []) : mixed
    {
        return $this->connection(null, 'master')->$method(...$arguments);
    }

    /**
     * 未知函数
     */
    public function __call(string $method, array $arguments) : mixed
    {

    }
}