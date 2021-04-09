<?php
declare(strict_types=1);

namespace Minimal\Database\Contracts;

/**
 * 代理类接口
 */
interface ProxyInterface
{
    /**
     * 连接驱动
     */
    public function connect(int $recount = 1) : mixed;

    /**
     * 释放驱动
     */
    public function release() : void;



    /**
     * 开启事务
     */
    public function beginTransaction() : bool;

    /**
     * 提交事务
     */
    public function commit() : bool;

    /**
     * 回滚事务
     */
    public function rollBack() : bool;

    /**
     * 是否在事务中
     */
    public function inTransaction() : bool;



    /**
     * 执行查询
     */
    public function query(string $sql, array $parameters = []) : StatementInterface;

    /**
     * 执行语句
     */
    public function execute(string $sql, array $parameters = []) : int;



    /**
     * 获取单行
     */
    public function first(string $sql, array $parameters = []) : array|bool;

    /**
     * 获取全部
     */
    public function all(string $sql, array $parameters = []) : array|bool;

    /**
     * 获取单列
     */
    public function column(string $sql, array $parameters = []) : array|bool;
    
    /**
     * 获取单值
     */
    public function value(string $sql, array $parameters = []) : mixed;



    /**
     * 获取最后的语句
     */
    public function lastSql() : string;

    /**
     * 获取最后的自增ID
     */
    public function lastInsertId(string $name = null) : string;
}