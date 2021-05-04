<?php
declare(strict_types=1);

namespace Minimal\Database\Query;

use Minimal\Database\Raw;
use Minimal\Database\Manager;
use Minimal\Database\Contracts\QueryInterface;
use Minimal\Database\Query\MysqlBuilder as Builder;
use Minimal\Database\Query\MysqlCondition as Condition;

/**
 * Mysql查询
 */
class MysqlQuery implements QueryInterface
{
    /**
     * 数据绑定
     */
    protected array $bindings = [];

    /**
     * 占位符
     */
    protected array $marks = [];

    /**
     * 参数
     */
    protected array $values = [];

    /**
     * 构造方法
     */
    public function __construct(protected Manager $manager)
    {}





    /**
     * 主表别名
     */
    public function from(string $table, string $as = null) : static
    {
        return $this->setBinding(__FUNCTION__, func_get_args());
    }

    /**
     * 显示字段
     */
    public function field(Raw|string ...$fields) : static
    {
        return $this->mergeBinding(__FUNCTION__, $fields);
    }

    /**
     * 表连接
     */
    public function join(string $table, string $as, Raw|callable|string $column, mixed $operator = null, mixed $value = null, string $type = 'INNER JOIN') : static
    {
        // 按情况处理
        $condition = new Condition($this);
        $condition->where($column, $operator, $value);
        $bindings = $condition->getBindings();

        // 返回结果
        return $this->addBinding(__FUNCTION__, [$type, $table, $as, $bindings]);
    }

    /**
     * 表连接 - 左
     */
    public function leftJoin(string $table, string $as, Raw|callable|string $column, mixed $operator = null, mixed $value = null) : static
    {
        return $this->join($table, $as, $column, $operator, $value, 'LEFT JOIN');
    }

    /**
     * 表连接 - 右
     */
    public function rightJoin(string $table, string $as, Raw|callable|string $column, mixed $operator = null, mixed $value = null) : static
    {
        return $this->join($table, $as, $column, $operator, $value, 'RIGHT JOIN');
    }

    /**
     * 表连接 - 交叉
     */
    public function crossJoin(string $table, string $as, Raw|callable|string $column, mixed $operator = null, mixed $value = null) : static
    {
        return $this->join($table, $as, $column, $operator, $value, 'CROSS JOIN');
    }

    /**
     * 条件
     */
    public function where(callable|string $column, mixed $operator = null, mixed $value = null, string $logic = 'AND') : static
    {
        $condition = new Condition($this);
        $condition->where($column, $operator, $value, $logic);

        return $this->mergeBinding(__FUNCTION__, $condition->getBindings(), 'merge');
    }

    /**
     * 条件 - 或
     */
    public function orWhere(callable|string $column, mixed $operator = null, mixed $value = null) : static
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * 分组
     */
    public function groupBy(string ...$fields) : static
    {
        return $this->mergeBinding(__FUNCTION__, $fields);
    }

    /**
     * 条件 - 分组后
     */
    public function having(callable|string $column, mixed $operator = null, mixed $value = null, string $logic = 'AND') : static
    {
        $condition = new Condition($this);
        $condition->where($column, $operator, $value, $logic);

        return $this->mergeBinding(__FUNCTION__, $condition->getBindings());
    }

    /**
     * 条件 - 分组后 - 或
     */
    public function orHaving(callable|string $column, mixed $operator = null, mixed $value = null) : static
    {
        return $this->having($column, $operator, $value, 'OR');
    }

    /**
     * 排序
     */
    public function orderBy(string $column, string $direction = 'ASC') : static
    {
        return $this->addBinding(__FUNCTION__, [$column, $direction]);
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
        return $this->limit(($no - 1) * $size, $size);
    }

    /**
     * 限量偏移
     */
    public function limit(int $offset, int $count = null) : static
    {
        return $this->setBinding(__FUNCTION__, [$offset, $count]);
    }

    /**
     * 表联合
     */
    public function union(QueryInterface|callable $query, bool $all = false) : static
    {
        if (is_callable($query)) {
            $query = $query(clone $this);
        }

        return $this->setBinding(__FUNCTION__, $query->toSql());
    }





    /**
     * 查询数据 - 所有
     */
    public function all(Raw|string ...$columns) : array
    {
        // 查询数据
        $result = $this->mergeBinding('field', $columns)->manager->all(Builder::select($this->getBindings()), $this->values) ?: [];
        // 重置绑定
        $this->reset();
        // 返回结果
        return $result;
    }

    /**
     * 查询数据 - 第一行
     */
    public function first(Raw|string ...$columns) : array
    {
        // 查询数据
        $result = $this->mergeBinding('field', $columns)->manager->first(Builder::select($this->getBindings()), $this->values) ?: [];
        // 重置绑定
        $this->reset();
        // 返回结果
        return $result;
    }

    /**
     * 查询数据 - 第一列
     */
    public function column(Raw|string $column) : array
    {
        // 查询数据
        $result = $this->setBinding('field', $column)->manager->column(Builder::select($this->getBindings()), $this->values) ?: [];
        // 重置绑定
        $this->reset();
        // 返回结果
        return $result;
    }

    /**
     * 查询数据 - 单个值
     */
    public function value(Raw|string $column) : mixed
    {
        // 查询数据
        $result = $this->setBinding('field', $column)->manager->value(Builder::select($this->getBindings()), $this->values);
        // 重置绑定
        $this->reset();
        // 返回结果
        return $result;
    }

    /**
     * 插入数据
     */
    public function insert(array $data) : bool
    {
        return $this->manager->execute($this->buildInsert($data), $this->values) > 0;
    }

    /**
     * 修改数据
     */
    public function update(array $data) : int
    {
        return $this->manager->execute($this->buildUpdate($data), $this->values);
    }

    /**
     * 删除数据
     */
    public function delete(mixed $id = null) : int
    {
        return $this->manager->execute($this->buildDelete(), $this->values);
    }

    /**
     * 清空表
     */
    public function truncate() : bool
    {
        $result = $this->manager->execute('TRUNCATE TABLE ' . $this->from, []);

        return true;
    }






    /**
     * 聚合函数
     */
    public function aggregate(string $func, array $columns = ['*']) : mixed
    {
        return $this->value(
            $this->manager->raw(strtoupper($func) . '(' . Builder::field($columns). ')')
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
     * 标记占位符
     */
    public function mark(string $column, mixed $value = null) : string
    {
        $mark = ':' . preg_replace('/[^\w]/', '_', $column);

        if (!isset($this->marks[$mark])) {
            $this->marks[$mark] = 0;
        }
        $this->marks[$mark]++;

        $mark .= $this->marks[$mark];

        if (2 === func_num_args()) {
            $this->values[$mark] = $value;
        }

        return $mark;
    }

    /**
     * 添加条件绑定
     */
    public function addBinding(string $token, mixed $data) : static
    {
        $this->bindings[$token][] = $data;

        return $this;
    }

    /**
     * 设置条件绑定
     */
    public function setBinding(string $token, mixed $data) : static
    {
        $this->bindings[$token] = $data;

        return $this;
    }

    /**
     * 合并条件绑定
     */
    public function mergeBinding(string $token, mixed $data) : static
    {
        $this->bindings[$token] = array_merge($this->bindings[$token] ?? [], $data);

        return $this;
    }

    /**
     * 获取条件绑定
     */
    public function getBinding(string $token) : array
    {
        return $this->bindings[$token] ?? [];
    }

    /**
     * 获取所有条件绑定
     */
    public function getBindings() : array
    {
        return $this->bindings;
    }

    /**
     * 重置数据
     */
    public function reset() : void
    {
        $this->bindings = [];
        $this->marks = [];
        $this->values = [];
    }




    /**
     * 构建插入
     */
    public function buildInsert(array $data) : string
    {
        if (!is_array(reset($data))) {
            $data = [$data];
        }

        $fields = array_keys($data[0]);
        $values = [];
        foreach ($data as $key => $item) {
            foreach ($item as $k => $v) {
                $values[$key][] = $this->markPlaceholder($k, $v);
            }
            $values[$key] = '(' . implode(', ', $values[$key]) . ')';
        }

        return 'INSERT INTO'
            . ' ' . $this->from
            . (empty($fields) ? '' : '(' . implode(', ', $fields) . ')')
            . ' VALUES'
            . (empty($values) ? '' : implode(', ', $values));
    }

    /**
     * 构建修改
     */
    public function buildUpdate(array $data) : string
    {
        $setdata = [];
        foreach ($data as $column => $value) {
            $mark = $this->markPlaceholder($column, $value);
            $setdata[] = $column . ' = ' . $mark;
        }

        return 'UPDATE'
            . ' ' . $this->from
            . ' SET '
            . implode(', ', $setdata)
            . ' ' . $this->buildWhere();
    }

    /**
     * 构建删除
     */
    public function buildDelete() : string
    {
        return 'DELETE FROM'
            . ' ' . $this->from
            . ' ' . $this->buildWhere();
    }

    /**
     * 反引号
     */
    public static function backquote(string $value, string $symbol = '`', string $delimiter = '.', array $excepts = ['*']) : string
    {
        return implode($delimiter, array_map(fn($s) => in_array($s, $excepts) ? $s : $symbol . $s . $symbol, explode($delimiter, str_replace($symbol, '', $value))));
    }

    /**
     * 分块处理
     */
    public function chunk(int $count, callable $callback) : bool
    {
        $page = 1;

        do {
            $results = $this->page($page, $count)->all();
            $countResults = count($results);
            if ($countResults == 0) {
                break;
            }
            if ($callback($results, $page) === false) {
                return false;
            }
            unset($results);
            $page++;
        } while ($countResults == $count);

        return true;
    }

    /**
     * 转成Sql
     */
    public function toSql(string $type = 'select', array $data = []) : string
    {
        $sql = '';

        if ($type == 'insert') {
            $sql = $this->buildInsert($data);
        } else if ($type == 'update') {
            $sql = $this->buildUpdate($data);
        } else if ($type == 'delete') {
            $sql = $this->buildDelete();
        } else {
            $sql = $this->buildSelect();
        }

        return $sql;
    }

    /**
     * 对象克隆
     */
    public function __clone()
    {
        $this->bindings = [];
        $this->marks = [];
        $this->values = [];
    }
}