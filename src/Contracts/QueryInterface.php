<?php
declare(strict_types=1);

namespace Minimal\Database\Contracts;

use Closure;
use Minimal\Database\Raw;
use Minimal\Database\Manager;

/**
 * 查询类接口
 */
interface QueryInterface
{
    /**
     * 构造方法
     */
    public function __construct(Manager $manager);

    /**
     * 原始语句
     */
    public function raw(string $sql) : Raw;

    /**
     * 主表别名
     */
    public function from(string $table, string $as = null) : static;

    /**
     * 表连接
     */
    public function join(string $table, Closure|string $first, string $operator = null, string $second = null, string $type = 'INNER') : static;

    /**
     * 表连接 - 左
     */
    public function leftJoin(string $table, Closure|string $first, string $operator = null, string $second = null) : static;

    /**
     * 表连接 - 右
     */
    public function rightJoin(string $table, Closure|string $first, string $operator = null, string $second = null) : static;

    /**
     * 表连接 - 交叉
     */
    public function crossJoin(string $table, Closure|string $first, string $operator = null, string $second = null) : static;

    /**
     * 字段
     */
    public function field(Raw|string ...$columns) : static;

    /**
     * 条件
     */
    public function where(Closure|string|array $column, string $operator = null, mixed $value = null, string $logic = 'AND') : static;

    /**
     * 条件 - 或
     */
    public function orWhere(Closure|string|array $column, string $operator = null, mixed $value = null) : static;

    /**
     * 分组
     */
    public function groupBy(string ...$groups) : static;

    /**
     * 条件 - 分组后
     */
    public function having(string $column, string $operator = null, mixed $value = null, string $logic = 'AND') : static;

    /**
     * 条件 - 分组后 - 或
     */
    public function orHaving(string $column, string $operator = null, mixed $value = null) : static;

    /**
     * 排序
     */
    public function orderBy(string $column, string $direction = 'ASC') : static;

    /**
     * 排序 - 倒序
     */
    public function orderByDesc(string $column) : static;

    /**
     * 数据偏移
     */
    public function offset(int $value) : static;

    /**
     * 数据限量
     */
    public function limit(int $value) : static;

    /**
     * 表联合
     */
    public function union(QueryInterface|Closure $query, bool $all = false) : static;

    /**
     * 聚合函数
     */
    public function aggregate(string $func, array $columns = ['*']) : mixed;

    /**
     * 聚合 - 统计
     */
    public function count(Raw|string $column = '*') : int;

    /**
     * 聚合 - 最小值
     */
    public function min(Raw|string $column) : mixed;

    /**
     * 聚合 - 最大值
     */
    public function max(Raw|string $column) : mixed;

    /**
     * 聚合 - 总和
     */
    public function sum(Raw|string $column) : mixed;

    /**
     * 聚合 - 平均值
     */
    public function avg(Raw|string $column) : mixed;

    /**
     * 递增
     */
    public function inc(Raw|string $column, float|int $step = 1, array $extra = []) : int|float;

    /**
     * 递减
     */
    public function dec(Raw|string $column, float|int $step = 1, array $extra = []) : int|float;

    /**
     * 查询数据 - 所有
     */
    public function all(Raw|string ...$columns) : array;

    /**
     * 查询数据 - 所有 - 别名
     */
    public function select(Raw|string ...$columns) : array;

    /**
     * 查询数据 - 第一行
     */
    public function first(Raw|string ...$columns) : array;

    /**
     * 查询数据 - 第一列
     */
    public function column(Raw|string $column) : array;

    /**
     * 查询数据 - 单个值
     */
    public function value(Raw|string $column) : mixed;

    /**
     * 插入数据
     */
    public function insert(array $values) : bool;

    /**
     * 修改数据
     */
    public function update(array $values) : int;

    /**
     * 删除数据
     */
    public function delete(mixed $id = null) : int;

    /**
     * 清空表
     */
    public function truncate() : bool;

    /**
     * 分块处理
     */
    public function chunk(int $count, Closure $callback) : bool;

    /**
     * 转成Sql
     */
    public function toSql() : string;
}