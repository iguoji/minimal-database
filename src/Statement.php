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
     * Sql包含字符串
     */
    public function sqlContains(...$strs) : bool
    {
        foreach ($strs as $key => $str) {
            if (str_contains($this->sql, $str)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 获取步骤
     */
    public function getBindings() : array
    {
        return $this->bindings;
    }

    /**
     * 存在步骤
     */
    public function has(string $name) : bool
    {
        foreach ($this->bindings as $bind) {
            if ($bind['method'] == $name) {
                return true;
            }
        }
        return false;
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