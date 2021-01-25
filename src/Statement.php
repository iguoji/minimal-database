<?php
declare(strict_types=1);

namespace Minimal\Database;

use PDO;

/**
 * 预处理类
 */
class Statement
{
    /**
     * 获取的方式
     * 驱动返回给PHP的数据表
     */
    public const FETCH_MODE_ASSOC = PDO::FETCH_ASSOC;
    public const FETCH_MODE_NUM = PDO::FETCH_NUM;
    public const FETCH_MODE_NAMED = PDO::FETCH_NAMED;
    public const FETCH_MODE_BOTH = PDO::FETCH_BOTH;
    public const FETCH_MODE_OBJ = PDO::FETCH_OBJ;

    /**
     * 获取结果集
     * PHP返回给框架的数据集
     */
    public const FETCH_RESULT_ROW = 1;                  // 只需一行
    public const FETCH_RESULT_COLUMN = 2;               // 只需一列
    public const FETCH_RESULT_ALL = 88;                 // 要所有行

    /**
     * 返回的数据类型
     */
    public const DATA_TYPE_BOOL = PDO::PARAM_BOOL;      // 布尔型
    public const DATA_TYPE_NULL = PDO::PARAM_NULL;      // 空
    public const DATA_TYPE_INT = PDO::PARAM_INT;        // 数值型
    public const DATA_TYPE_STR = PDO::PARAM_STR;        // 字符型
    public const DATA_TYPE_ARRAY = 88;                  // 数组型

    /**
     * Sql语句
     */
    protected string $sql;

    /**
     * 参数列表
     */
    protected array $parameters;

    /**
     * 取出模式
     */
    protected int $fetchMode;

    /**
     * 取出结果
     */
    protected int $fetchResult;

    /**
     * 最终数据
     */
    protected mixed $data;

    /**
     * 返回的数据类型
     */
    protected int $dataType;

    /**
     * 构造函数
     */
    public function __construct(string $sql = '', array $parameters = [])
    {
        $this->sql = $sql;
        $this->parameters = $parameters;
    }

    /**
     * 获取Sql语句
     */
    public function getSql() : string
    {
        return $this->sql;
    }

    /**
     * 设置取出方式
     */
    public function setFetchMode(int $mode) : static
    {
        $this->fetchMode = $mode;
        return $this;
    }

    /**
     * 获取取出方式
     */
    public function getFetchMode() : int
    {
        return $this->fetchMode ?? self::FETCH_MODE_ASSOC;
    }

    /**
     * 设置取出结果
     */
    public function setFetchResult(int $result) : static
    {
        $this->fetchResult = $result;
        return $this;
    }

    /**
     * 获取取出结果
     */
    public function getFetchResult() : int
    {
        return $this->fetchResult ?? self::FETCH_RESULT_ALL;
    }

    /**
     * 设置数据类型
     */
    public function setDataType(int $type) : static
    {
        $this->dataType = $type;
        return $this;
    }

    /**
     * 获取数据类型
     */
    public function getDataType() : int
    {
        return $this->dataType ?? self::DATA_TYPE_ARRAY;
    }

    /**
     * 设置数据
     */
    public function setData(mixed $data) : static
    {
        $this->data = $data;
        return $this;
    }

    /**
     * 获取数据
     */
    public function getData() : mixed
    {
        return $this->data ?? null;
    }
}