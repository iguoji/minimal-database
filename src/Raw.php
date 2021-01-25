<?php
declare(strict_types=1);

namespace Minimal\Database;

/**
 * 原始类
 */
class Raw
{
    /**
     * Sql语句
     */
    protected string $sql;

    /**
     * 构造函数
     */
    public function __construct(string $sql)
    {
        $this->sql = $sql;
    }

    /**
     * 获取语句
     */
    public function getSql() : string
    {
        return $this->sql;
    }

    /**
     * 转换为字符串
     */
    public function __toString() : string
    {
        return $this->getSql();
    }
}