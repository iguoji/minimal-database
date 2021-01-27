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
     * 构造函数
     */
    public function __construct(protected string $sql, protected int $fetchMode = PDO::FETCH_ASSOC, protected string $fetchResult = 'fetchAll')
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
     * 设置模式
     */
    public function setFetchMode(int $fetchMode) : static
    {
        $this->fetchMode = $fetchMode;
        return $this;
    }

    /**
     * 获取模式
     */
    public function getFetchMode() : int
    {
        return $this->fetchMode;
    }

    /**
     * 设置结果集
     */
    public function setFetchResult(string $fetchResult) : static
    {
        $this->fetchResult = $fetchResult;
        return $this;
    }

    /**
     * 获取结果
     */
    public function getFetchResult() : string
    {
        return $this->fetchResult;
    }

    /**
     * 获取原始方法
     */
    public function getOriginMethod() : string
    {
        return stripos($this->getSql(), 'select') === 0 ? 'query' : 'execute';
    }
}