<?php
declare(strict_types=1);

namespace Minimal\Database;

use PDO;

/**
 * 语句类
 */
class Statement
{
    /**
     * 操作绑定
     */
    protected array $bindings = [];

    /**
     * 构造函数
     */
    public function __construct(protected string $sql)
    {
    }

    /**
     * 获取Sql
     */
    public function getSql() : string
    {
        return $this->sql;
    }

    /**
     * 获取步骤
     */
    public function getBindings() : array
    {
        return $this->bindings;
    }

    /**
     * 重置步骤
     */
    public function reset() : static
    {
        $this->bindings = [];
        return $this;
    }

    /**
     * 未知函数
     */
    public function __call(string $method, array $arguments) : static
    {
        $this->bindings[] = ['method' => $method, 'arguments' => $arguments];
        return $this;
    }
}