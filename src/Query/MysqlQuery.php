<?php
declare(strict_types=1);

namespace Minimal\Database\Query;

use Closure;
use Minimal\Database\Raw;
use Minimal\Database\Manager;
use Minimal\Database\Contracts\QueryInterface;

/**
 * Mysql查询
 */
class MysqlQuery implements QueryInterface
{
    /**
     * 主表
     */
    protected string $from;

    /**
     * 副表
     */
    protected array $joins = [];

    /**
     * 字段
     */
    protected array $fields = [];

    /**
     * 条件绑定
     */
    protected array $bindings = [
        'where'     =>  [],
        'having'    =>  [],
    ];

    /**
     * 占位符
     */
    protected array $marks = [];

    /**
     * 参数
     */
    protected array $values = [];

    /**
     * 分组
     */
    protected array $groups = [];

    /**
     * 分页
     */
    protected array $page = [];

    /**
     * 排序
     */
    protected array $orders = [];

    /**
     * 构造方法
     */
    public function __construct(protected Manager $manager)
    {}

    /**
     * 主表别名
     */
    public function raw(string $sql) : Raw
    {
        return new Raw($sql);
    }

    /**
     * 主表别名
     */
    public function from(string $table, string $as = null) : static
    {
        $this->from = empty($as)
                ? static::backquote($table)
                : static::backquote($table) . ' AS ' . static::backquote($as);
        return $this;
    }

    /**
     * 表连接
     */
    public function join(string $table, Closure|string $first, string $operator = null, string $second = null, string $type = 'INNER') : static
    {
        $table = false !== strpos($table, ' AS ')
            ? implode(' AS ', array_map(fn($s) => static::backquote($s), explode(' AS ', $table)))
            : static::backquote($table);

        [$second, $operator] = $this->prepareValueAndOperator(
            $second, $operator, true
        );

        $this->joins[] = sprintf(
            '%s JOIN %s %s'
            , strtoupper($type)
            , $table
            , (clone $this)->on($first, $operator, new Raw(static::backquote($second)))->buildOn()
        );

        return $this;
    }

    /**
     * 表连接 - 左
     */
    public function leftJoin(string $table, Closure|string $first, string $operator = null, string $second = null) : static
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    /**
     * 表连接 - 右
     */
    public function rightJoin(string $table, Closure|string $first, string $operator = null, string $second = null) : static
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    /**
     * 表连接 - 交叉
     */
    public function crossJoin(string $table, Closure|string $first, string $operator = null, string $second = null) : static
    {
        return $this->join($table, $first, $operator, $second, 'CROSS');
    }

    /**
     * 条件 - 表连接
     */
    public function on(string $column, string $operator = null, mixed $value = null, string $logic = 'AND') : static
    {
        return $this->where($column, $operator, $value, $logic, 'on');
    }

    /**
     * 条件 - 表连接 - 或
     */
    public function orOn(string $column, string $operator = null, mixed $value = null) : static
    {
        return $this->on($column, $operator, $value, 'OR');
    }

    /**
     * 字段
     */
    public function field(Raw|string ...$columns) : static
    {
        if (empty($columns)) {
            $columns = ['*'];

            if (!empty($this->fields)) {
                return $this;
            }
        }

        $this->fields = [];

        foreach ($columns as $column) {
            if ($column instanceof Raw) {
                $this->fields[] = $column;
            } else {
                $this->fields[] = static::backquote($column);
            }
        }

        return $this;
    }

    /**
     * 条件
     */
    public function where(Closure|string|array $column, string $operator = null, mixed $value = null, string $logic = 'AND', string $location = 'where') : static
    {
        if ($column instanceof Closure) {
            // or
            $that = clone $this;
            $column($that);
            $this->addBinding($logic, $that->getBinding($location), $location);
        } else {
            // and
            [$value, $operator] = $this->prepareValueAndOperator(
                $value, $operator, func_num_args() === 2
            );

            $mark = $value;
            if (! $value instanceof Raw) {
                $mark = $this->markPlaceholder($column);
                $this->values[$mark] = $value;
            }

            $this->addBinding($logic, sprintf(
                '%s %s %s'
                , static::backquote($column)
                , strtoupper($operator)
                , $mark
            ), $location);
        }
        return $this;
    }

    /**
     * 条件 - 或
     */
    public function orWhere(Closure|string|array $column, string $operator = null, mixed $value = null) : static
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * 获取所有条件
     */
    public function getWheres() : array
    {
        return $this->getBinding('where');
    }

    /**
     * 准备运算符和值
     */
    public function prepareValueAndOperator(mixed $value = null, string $operator = null, bool $useDefault = false) : array
    {
        if (true === $useDefault) {
            $value = $operator;
            $operator = is_array($value) ? 'IN' : '=';
        }

        if ($operator == '=' && is_null($value)) {
            $operator = 'IS';
        } else if ($operator == '!=' && is_null($value)) {
            $operator = 'IS NOT';
        }

        return [$value, $operator];
    }

    /**
     * 标记占位符
     */
    public function markPlaceholder(string $column) : string
    {
        $mark = ':' . preg_replace('/[^\w]/', '_', $column);

        if (!isset($this->marks[$mark])) {
            $this->marks[$mark] = 0;
        }
        $this->marks[$mark]++;

        return $mark . $this->marks[$mark];
    }

    /**
     * 添加条件绑定
     */
    public function addBinding(string $logic, mixed $data, string $location) : static
    {
        $this->bindings[$location][] = [$logic, $data];

        return $this;
    }

    /**
     * 获取条件绑定
     */
    public function getBinding(string $location) : array
    {
        return $this->bindings[$location] ?? [];
    }

    /**
     * 分组
     */
    public function groupBy(string ...$groups) : static
    {
        $this->groups = array_map(fn($g) => static::backquote($g), $groups);

        return $this;
    }

    /**
     * 条件 - 分组后
     */
    public function having(string $column, string $operator = null, mixed $value = null, string $logic = 'AND') : static
    {
        return $this->where($column, $operator, $value, $logic, 'having');
    }

    /**
     * 条件 - 分组后 - 或
     */
    public function orHaving(string $column, string $operator = null, mixed $value = null) : static
    {
        return $this->having($column, $operator, $value, 'OR');
    }

    /**
     * 排序
     */
    public function orderBy(string $column, string $direction = 'ASC') : static
    {
        $this->orders[] = static::backquote($column) . ' ' . strtoupper($direction);

        return $this;
    }

    /**
     * 排序 - 倒序
     */
    public function orderByDesc(string $column) : static
    {
        return $this->orderBy($column, 'DESC');
    }

    /**
     * 分页
     */
    public function page(int $no, int $size) : static
    {
        return $this->offset(($no - 1) * $size)->limit($size);
    }

    /**
     * 分页 - 偏移
     */
    public function offset(int $value) : static
    {
        $this->page[0] = $value;

        return $this;
    }

    /**
     * 分页 - 限量
     */
    public function limit(int $value) : static
    {
        $this->page[1] = $value;

        return $this;
    }

    /**
     * 表联合
     */
    public function union(QueryInterface|Closure $query, bool $all = false) : static
    {
        if ($query instanceof Closure) {
            $query = $query(clone $this);
        }

        $this->union = $query->toSql();

        return $this;
    }

    /**
     * 聚合函数
     */
    public function aggregate(string $func, array $columns = ['*']) : mixed
    {
        return $this->value(
            $this->raw(
                strtoupper($func)
                . '('
                . implode(', ', array_map(fn($c) => static::backquote($c), $columns))
                . ')'
            )
        );
    }

    /**
     * 聚合 - 统计
     */
    public function count(Raw|string $column = '*') : int
    {
        return (int) $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * 聚合 - 最小值
     */
    public function min(Raw|string $column) : mixed
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * 聚合 - 最大值
     */
    public function max(Raw|string $column) : mixed
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * 聚合 - 总和
     */
    public function sum(Raw|string $column) : mixed
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * 聚合 - 平均值
     */
    public function avg(Raw|string $column) : mixed
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * 递增
     */
    public function inc(Raw|string $column, float|int $step = 1, array $extra = []) : int|float
    {
        return 0;
    }

    /**
     * 递减
     */
    public function dec(Raw|string $column, float|int $step = 1, array $extra = []) : int|float
    {
        return 0;
    }

    /**
     * 查询数据 - 所有
     */
    public function all(Raw|string ...$columns) : array
    {
        return $this->field(...$columns)->manager->all(implode(' ', [
            $this->buildSelect()
            , $this->buildJoin()
            , $this->buildWhere()
            , $this->buildGroup()
            , $this->buildHaving()
            , $this->buildOrder()
            , $this->buildPage()
        ]), $this->values) ?: [];
    }

    /**
     * 查询数据 - 所有 - 别名
     */
    public function select(Raw|string ...$columns) : array
    {
        return $this->all(...$columns);
    }

    /**
     * 查询数据 - 第一行
     */
    public function first(Raw|string ...$columns) : array
    {
        return $this->field(...$columns)->manager->first(implode(' ', [
            $this->buildSelect()
            , $this->buildJoin()
            , $this->buildWhere()
            , $this->buildGroup()
            , $this->buildHaving()
            , $this->buildOrder()
            , $this->buildPage()
        ]), $this->values) ?: [];
    }

    /**
     * 查询数据 - 第一列
     */
    public function column(Raw|string $column) : array
    {
        return $this->field(...$columns)->manager->column(implode(' ', [
            $this->buildSelect()
            , $this->buildJoin()
            , $this->buildWhere()
            , $this->buildGroup()
            , $this->buildHaving()
            , $this->buildOrder()
            , $this->buildPage()
        ]), $this->values) ?: [];
    }

    /**
     * 查询数据 - 单个值
     */
    public function value(Raw|string $column) : mixed
    {
        return $this->field($column)->manager->value(implode(' ', [
            $this->buildSelect()
            , $this->buildJoin()
            , $this->buildWhere()
            , $this->buildGroup()
            , $this->buildHaving()
            , $this->buildOrder()
            , $this->buildPage()
        ]), $this->values);
    }

    /**
     * 插入数据
     */
    public function insert(array $data) : bool
    {
        $isMul = true;
        if (!is_array(reset($data))) {
            $isMul = false;
            $data = [$data];
        }

        $fields = [];
        $values = [];
        foreach ($data as $key => $item) {
            if (empty($fields)) {
                $fields = array_keys($item);
            }

            foreach ($item as $k => $v) {
                $mark = $this->markPlaceholder($k);
                $this->values[$mark] = $v;

                $values[$key][] = $mark;
            }

            $values[$key] = '(' . implode(', ', $values[$key]) . ')';
        }

        return $this->manager->execute(implode(' ', [
            // $this->buildInsert()
            'INSERT INTO'
            , $this->from
            , empty($fields) ? '' : '(' . implode(', ', $fields) . ')'
            , 'VALUES'
            , empty($values) ? '' : implode(', ', $values)
        ]), $this->values) > 0;
    }

    /**
     * 修改数据
     */
    public function update(array $values) : int
    {
        return 0;
    }

    /**
     * 删除数据
     */
    public function delete(mixed $id = null) : int
    {
        return 0;
    }

    /**
     * 清空表
     */
    public function truncate() : bool
    {
        return true;
    }

    /**
     * 分块处理
     */
    public function chunk(int $count, Closure $callback) : bool
    {
        return true;
    }

    /**
     * 构建Select
     */
    public function buildSelect() : string
    {
        return sprintf('SELECT %s FROM %s', $this->buildField(), $this->from);
    }

    /**
     * 构建字段
     */
    public function buildField() : string
    {
        return implode(', ', $this->fields);
    }

    /**
     * 构建Join
     */
    public function buildJoin() : string
    {
        return implode(' ', $this->joins);
    }

    /**
     * 构建On
     */
    public function buildOn() : string
    {
        return empty($this->getBinding('on')) ? '' : 'ON ' . $this->buildExpress($this->getBinding('on'));
    }

    /**
     * 构建Where
     */
    public function buildWhere() : string
    {
        return empty($this->getBinding('where')) ? '' : 'WHERE ' . $this->buildExpress($this->getBinding('where'));
    }

    /**
     * 构建表达式
     */
    public function buildExpress(array $wheres = []) : string
    {
        $sql = '';
        foreach ($wheres as $item) {
            if (is_array($item[1])) {
                $item[1] = '(' . $this->buildExpress($item[1]) . ')';
            }
            $sql .= '' === $sql
                ? $item[1]
                : ' ' . $item[0] . ' ' . $item[1];
        }
        return $sql;
    }

    /**
     * 构建Group
     */
    public function buildGroup() : string
    {
        return empty($this->groups) ? '' : 'GROUP BY ' . implode(', ', $this->groups);
    }

    /**
     * 构建Having
     */
    public function buildHaving() : string
    {
        return empty($this->getBinding('having')) ? '' : 'HAVING ' . $this->buildExpress($this->getBinding('having'));
    }

    /**
     * 构建Page
     */
    public function buildPage() : string
    {
        return empty($this->page) ? '' : 'LIMIT ' . implode(', ', $this->page);
    }

    /**
     * 构建排序
     */
    public function buildOrder() : string
    {
        return empty($this->orders) ? '' : 'ORDER BY ' . implode(', ', $this->orders);
    }

    /**
     * 反引号
     */
    public static function backquote(string $value, string $symbol = '`', string $delimiter = '.', array $excepts = ['*']) : string
    {
        return implode($delimiter, array_map(fn($s) => in_array($s, $excepts) ? $s : $symbol . $s . $symbol, explode($delimiter, str_replace($symbol, '', $value))));
    }

    /**
     * 转成Sql
     */
    public function toSql() : string
    {
        var_dump([
            $this->from,
            $this->joins,
            $this->fields,
            $this->wheres,
            $this->values,
        ]);
        return '';
    }

    /**
     * 对象克隆
     */
    public function __clone()
    {
        $this->joins = [];
        $this->fields = [];
        $this->wheres = [];
        $this->marks = [];
        $this->values = [];
    }
}