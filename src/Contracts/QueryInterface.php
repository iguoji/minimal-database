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
     * 主表别名
     */
    public function from(string $table, string $as = null) : static;

    /**
     * 显示字段
     */
    public function field(Raw|string ...$fields) : static;

    /**
     * 表连接
     */
    public function join(string $table, string $as, Closure|Raw|string $column, mixed $operator = null, mixed $value = null, string $type = 'INNER JOIN') : static;

    /**
     * 表连接 - 左
     */
    public function leftJoin(string $table, string $as, Closure|Raw|string $column, mixed $operator = null, mixed $value = null) : static;

    /**
     * 表连接 - 右
     */
    public function rightJoin(string $table, string $as, Closure|Raw|string $column, mixed $operator = null, mixed $value = null) : static;

    /**
     * 表连接 - 交叉
     */
    public function crossJoin(string $table, string $as, Closure|Raw|string $column, mixed $operator = null, mixed $value = null) : static;

    /**
     * 条件
     */
    public function where(Closure|string $column, mixed $operator = null, mixed $value = null, string $logic = 'AND') : static;

    /**
     * 条件 - 或
     */
    public function orWhere(Closure|string $column, mixed $operator = null, mixed $value = null) : static;

    /**
     * 分组
     */
    public function groupBy(string ...$groups) : static;

    /**
     * 条件 - 分组后
     */
    public function having(Closure|string $column, mixed $operator = null, mixed $value = null, string $logic = 'AND') : static;

    /**
     * 条件 - 分组后 - 或
     */
    public function orHaving(Closure|string $column, mixed $operator = null, mixed $value = null) : static;

    /**
     * 排序
     */
    public function orderBy(string $column, string $direction = 'ASC') : static;

    /**
     * 排序 - 倒序
     */
    public function orderByDesc(string $column) : static;

    /**
     * 分页
     */
    public function page(int $no, int $size) : static;

    /**
     * 限量偏移
     */
    public function limit(int $offset, int $count = null) : static;

    /**
     * 表联合
     */
    public function union(QueryInterface|Closure $query, bool $all = false) : static;





    /**
     * 查询数据 - 所有
     */
    public function all(Raw|string ...$columns) : array;

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
    public function insert(array $data) : int;

    /**
     * 修改数据
     */
    public function update(array $data) : int;

    /**
     * 删除数据
     */
    public function delete(mixed $id = null) : int;

    /**
     * 清空表
     */
    public function truncate() : bool;





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
     * 分块处理
     */
    public function chunk(int $count, Closure $callback) : bool;

    /**
     * 转成Sql
     */
    public function toSql(string $type = 'select', array $data = []) : string;
}