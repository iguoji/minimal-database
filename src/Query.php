<?php
declare(strict_types=1);

namespace Minimal\Database;

use Minimal\Support\Context;

/**
 * 查询类
 */
class Query
{
    /**
     * 开启事务
     */
    public function beginTransaction() : Statement
    {
        Context::incr('database:transaction:level');
        if (Context::get('database:transaction:level') === 1) {
            $sql = 'BEGIN';
        } else {
            $sql = 'SAVEPOINT TRANS' . Context::get('database:transaction:level');
        }
        return (new Statement($sql))
            ->setDataType(Statement::DATA_TYPE_BOOL);
    }

    /**
     * 是否在事务中
     */
    public function inTransaction() : Statement
    {
        return (new Statement())
            ->setData(Context::has('database:transaction:level') && Context::get('database:transaction:level') >= 1)
            ->setDataType(Statement::DATA_TYPE_BOOL);
    }

    /**
     * 提交事务
     */
    public function commit() : Statement
    {
        $level = Context::decr('database:transaction:level');
        
        if ($level === 0) {
            return (new Statement('COMMIT'))
                ->setData(false)
                ->setDataType(Statement::DATA_TYPE_BOOL);
        } else {
            return (new Statement())
                ->setData(true)
                ->setDataType(Statement::DATA_TYPE_BOOL);
        }
    }

    /**
     * 事务回滚
     */
    public function rollBack() : Statement
    {
        $level = Context::get('database:transaction:level');
        if ($level === 1) {
            $sql = 'ROLLBACK';
        } else if ($level > 1) {
            $sql = 'ROLLBACK TO SAVEPOINT TRANS' . $level;
        }
        $level = max(0, $level - 1);
        Context::set('database:transaction:level', $level);

        if (isset($sql)) {
            return (new Statement($sql))
                ->setData(false)
                ->setDataType(Statement::DATA_TYPE_BOOL);
        } else {
            return (new Statement())
                ->setData(true)
                ->setDataType(Statement::DATA_TYPE_BOOL);
        }
    }

    /**
     * 根据表查
     */
    public function table(string $table, string $as = null)
    {
        
    }

    /**
     * 查询数据
     */
    public function query(string $sql, array $data = []) : Statement
    {
        return new Statement($sql, $data);
    }

    /**
     * 获取一行
     */
    public function first(string $sql, array $data = []) : Statement
    {
        return (new Statement($sql, $data))
            ->setFetchResult(Statement::RESULT_ROW);
    }

    /**
     * 查询数值
     */
    public function number(string $sql, array $data = []) : Statement
    {
        return (new Statement($sql, $data))
            ->setDataType(Statement::DATA_TYPE_INT)
            ->setFetchMode(Statement::FETCH_MODE_NUM)
            ->setFetchResult(Statement::FETCH_RESULT_COLUMN);
    }

    /**
     * 操作数据
     */
    public function execute(string $sql, array $data = []) : Statement
    {
        return (new Statement($sql, $data))
        ->setDataType(Statement::DATA_TYPE_INT);
    }
}