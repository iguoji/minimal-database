<?php
declare(strict_types=1);

namespace Minimal\Database;

use PDO;

/**
 * Sql构建类
 */
class Builder
{
    /**
     * 主表
     */
    public string $from = '';

    /**
     * 副表
     */
    public array $joins = [];

    /**
     * 要求
     */
    public array $wheres = [];

    /**
     * 限量
     */
    public string $limit = '';

    /**
     * 排序
     */
    public array $orders = [];

    /**
     * 操作符
     */
    public $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=',
        'like', 'not like',
        'in', 'not in',
    ];

    /**
     * 构造函数
     */
    public function __construct(string $table, string $as = null)
    {
        $this->from = $as ? "`{$table}` AS `{$as}`" : "`$table`";
    }

    /**
     * 表连接
     */
    public function join(string $table, string|callable $field, mixed $op = null, mixed $value = null, string $type = 'inner') : static
    {
        if (false !== stripos($table, ' AS ')) {
            $table = implode(' AS ', array_map(fn($s) => $this->parseField($s), explode(' AS ', $table)));
        }
        if (is_string($field) && is_string($op) && is_null($value)) {
            $value = $op;
            $op = '=';
        }
        $this->joins[] = sprintf(
            '%s JOIN %s ON %s',
            strtoupper($type),
            $table,
            (clone $this)->where($field, $op, new Raw($this->parseField($value)))->parseWhere(),
        );
        return $this;
    }

    /**
     * 左连接
     */
    public function leftJoin(string $table, string $field, string $op = null, string $value = null) : static
    {
        return $this->join($table, $field, $op, $value, 'left');
    }

    /**
     * 右连接
     */
    public function rightJoin(string $table, string $field, string $op = null, string $value = null) : static
    {
        return $this->join($table, $field, $op, $value, 'right');
    }

    /**
     * 要求 - And
     */
    public function where(string|callable $field, mixed $op = null, mixed $value = null, string $logic = 'AND') : static
    {
        if (is_callable($field)) {
            // or
            $that = clone $this;
            $field($that);
            $this->wheres[] = [$logic, $that->wheres];
        } else {
            // and
            if (func_num_args() <= 2) {
                $value = $op;
                $op = is_array($value) ? 'IN' : '=';
            }
            if (is_null($value)) {
                $op = $op == '=' ? 'IS' : 'IS NOT';
            }
            $this->wheres[] = [$logic, sprintf('%s %s %s', $this->parseField($field), strtoupper($op), $this->parseValue($value))];
        }
        return $this;
    }

    /**
     * 要求 - Or
     */
    public function orWhere(string|callable $field, mixed $op = null, mixed $value = null) : static
    {
        return $this->where($field, $op, $value, 'OR');
    }

    /**
     * 限量
     */
    public function limit(int $offset, int $rows = null) : static
    {
        $this->limit = $rows ? "$offset, $rows" : $offset;
        return $this;
    }

    /**
     * 分页
     */
    public function page(int $number, int $size) : static
    {
        $offset = ($number - 1) * $size;
        $offset = $offset < 0 ? 0 : $offset;
        return $this->limit($offset, $size);
    }

    /**
     * 排序
     */
    public function order(string $field, string $direction = 'asc') : static
    {
        $field = $this->parseField($field);
        $direction = strtoupper($direction);
        $this->orders[] = "$field $direction";
        return $this;
    }

    /**
     * 添加
     */
    public function insert(array $data) : Statement
    {
        $from = $this->from;
        $isMul = true;
        if (!is_array(reset($data))) {
            $isMul = false;
            $data = [$data];
        }
        $fields = null;
        $values = [];
        foreach ($data as $item) {
            ksort($item);
            if (is_null($fields)) {
                $fields = $this->parseField(array_keys($item));
            }
            $values[] = $this->parseValue(array_values($item));
        }
        $statement = new Statement(implode(' ', [
            'INSERT INTO ' . $from,
            $fields ? '(' . $fields . ')' : '',
            $values ? 'VALUES ' . implode(', ', $values) : '',
        ]));
        return $isMul
            ? $statement->rowCount()
            : $statement->handle()->lastInsertId();
    }

    /**
     * 修改
     */
    public function update(array $data) : Statement
    {
        $from = $this->from;
        $set = [];
        foreach ($data as $field => $value) {
            $set[] = sprintf('%s = %s', $this->parseField($field), $this->parseValue($value));
        }
        $wheres = $this->parseWhere();

        $statement = new Statement(implode(' ', [
            'UPDATE ' . $from,
            $set ? ' SET ' . implode(', ', $set) : '',
            $wheres ? 'WHERE ' . $wheres : '',
        ]));
        return $statement->rowCount();
    }

    /**
     * 删除
     */
    public function delete() : Statement
    {
        $from = $this->from;
        $wheres = $this->parseWhere();

        $statement = new Statement(implode(' ', [
            'DELETE FROM ' . $from,
            $wheres ? 'WHERE ' . $wheres : '',
        ]));
        return $statement->rowCount();
    }

    /**
     * 查询
     */
    public function select(...$fields) : Statement
    {
        $fields = $fields ? $this->parseField($fields) : '*';
        $from = $this->from;
        $joins = $this->joins;
        $wheres = $this->parseWhere();
        $limit = $this->limit;
        $orders = $this->orders;

        $statement = new Statement(implode(' ', array_filter([
            'SELECT',
            $fields,
            'FROM ' . $from,
            $joins  ? implode(' ', $joins) : '',
            $wheres ? 'WHERE ' . $wheres : '',
            $orders ? 'ORDER BY ' . implode(', ', $orders) : '',
            $limit  ? 'LIMIT ' . $limit : '',
        ], fn($s) => $s !== '')));
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 查询一行
     */
    public function first(...$fields) : Statement
    {
        return $this->select(...$fields)->reset()->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 特殊函数：单列
     */
    public function column(string $field) : Statement
    {
        return $this->select($field)->reset()->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * 特殊函数：单值
     */
    public function value(string $field) : Statement
    {
        return $this->select($field)->reset()->setFetchMode(PDO::FETCH_NUM)->fetchColumn();
    }

    /**
     * 聚合函数：统计
     */
    public function count(string $field = '*') : Statement
    {
        return $this->select(
            new Raw(
                sprintf('COUNT(%s) AS `MINIMAL_COUNT`', $this->parseField($field))
            )
        )->reset()->setFetchMode(PDO::FETCH_NUM)->fetchColumn();
    }

    /**
     * 聚合函数：求和
     */
    public function sum(string $field) : Statement
    {
        return $this->select(
            new Raw(
                sprintf('SUM(%s) AS `MINIMAL_SUM`', $this->parseField($field))
            )
        )->reset()->setFetchMode(PDO::FETCH_NUM)->fetchColumn();
    }

    /**
     * 聚合函数：求平均
     */
    public function avg(string $field) : Statement
    {
        return $this->select(
            new Raw(
                sprintf('AVG(%s) AS `MINIMAL_AVG`', $this->parseField($field))
            )
        )->reset()->setFetchMode(PDO::FETCH_NUM)->fetchColumn();
    }

    /**
     * 聚合函数：最大
     */
    public function max(string $field) : Statement
    {
        return $this->select(
            new Raw(
                sprintf('MAX(%s) AS `MINIMAL_MAX`', $this->parseField($field))
            )
        )->reset()->setFetchMode(PDO::FETCH_NUM)->fetchColumn();
    }

    /**
     * 聚合函数：最小
     */
    public function min(string $field) : Statement
    {
        return $this->select(
            new Raw(
                sprintf('MIN(%s) AS `MINIMAL_MIN`', $this->parseField($field))
            )
        )->reset()->setFetchMode(PDO::FETCH_NUM)->fetchColumn();
    }

    /**
     * 解析字段
     */
    private function parseField(array|string|Raw $field) : string
    {
        if (is_null($field) || $field == '*') {
            return '*';
        } else if (is_array($field)) {
            $arr = [];
            foreach ($field as $k => $v) {
                if (!is_int($k)) {
                    $arr[] = $this->parseField(
                        new Raw(sprintf('%s AS %s', $this->parseField($k), $this->parseField($v)))
                    );
                } else {
                    $arr[] = $this->parseField($v);
                }
            }
            return implode(', ', $arr);
        } else if ($field instanceof Raw) {
            return (string) $field;
        } else {
            $field = (string) $field;
            $array = false !== strpos($field, '.') ? explode('.', $field) : [$field];
            return implode('.', array_map(fn($s) => "`$s`", $array));
        }
    }

    /**
     * 解析值
     */
    private function parseValue(mixed $value) : string|int
    {
        if (is_string($value)) {
            return "'$value'";
        } else if (is_array($value)) {
            return '(' . implode(', ', array_map([$this, 'parseValue'], $value)) . ')';
        } else if (is_null($value)) {
            return 'NULL';
        } else {
            return (string) $value;
        }
    }

    /**
     * 解析条件
     */
    private function parseWhere($wheres = null) : string
    {
        $str = '';
        $wheres = $wheres ?: $this->wheres;
        foreach ($wheres as $item) {
            if (is_array($item[1])) {
                $item[1] = '(' . $this->parseWhere($item[1]) . ')';
            }
            if ($str === '') {
                $str = $item[1];
            } else {
                $str .= " $item[0] $item[1]";
            }
        }
        return $str;
    }
}