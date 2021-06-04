<?php
declare(strict_types=1);

namespace Minimal\Database\Query;

use Minimal\Database\Raw;

/**
 * Mysql语法构建
 */
class MysqlBuilder
{
    /**
     * 字段
     */
    public static function field(Raw|array|string $fields) : string
    {
        if ($fields instanceof Raw) {
            return (string) $fields;
        }

        if (empty($fields)) {
            $fields = ['*'];
        } else if (is_string($fields)) {
            $fields = [ $fields ];
        }

        $blocks = [];
        foreach ($fields as $key => $value) {
            if ($value instanceof Raw) {
                $blocks[] = (string) $value;
            } else if (is_array($value)) {
                $blocks[] = static::field($value);
            } else {
                if (is_int($key)) {
                    $blocks[] = static::backquote($value);
                } else {
                    $blocks[] = static::as($key, $value);
                }
            }
        }

        return implode(', ', $blocks);
        return implode(', ', array_map(fn($s) => static::backquote($s), $fields));
    }

    /**
     * AS别名
     */
    public static function as(string $name, string $alias = null) : string
    {
        return empty($alias) ? static::backquote($name) : static::backquote($name) . ' AS ' . static::backquote($alias);
    }

    /**
     * 表连接
     */
    public static function join(array $joins) : string
    {
        $sql = [];

        foreach ($joins as $key => $item) {
            $express = static::complex($item[3]);
            $sql[] = sprintf('%s %s ON %s', $item[0], static::as($item[1], $item[2]), $express);
        }

        return implode(' ', $sql);
    }

    /**
     * 前置条件
     */
    public static function where(array $conditions) : string
    {
        return empty($conditions) ? '' : 'WHERE ' . static::complex($conditions);
    }

    /**
     * 分组
     */
    public static function groupBy(array $fields) : string
    {
        if (empty($fields)) {
            return '';
        }
        return sprintf('GROUP BY %s', implode(', ', array_map(fn($f) => static::backquote($f), $fields)));
    }

    /**
     * 后置条件
     */
    public static function having(array $conditions) : string
    {
        return empty($conditions) ? '' : 'HAVING ' . static::complex($conditions);
    }

    /**
     * 排序
     */
    public static function orderBy(array $data) : string
    {
        if (empty($data)) {
            return '';
        }
        return sprintf('ORDER BY %s', implode(', ', array_map(fn($arr) => static::backquote($arr[0]) . ' ' . strtoupper($arr[1]), $data)));
    }

    /**
     * 分页
     */
    public static function limit(array $data) : string
    {
        if (empty($data)) {
            return '';
        }
        return 'LIMIT ' . $data[0] . (is_null($data[1]) ? '' : ', ' . $data[1]);
    }





    /**
     * 反引号
     */
    public static function backquote(array|string $value, string $symbol = '`', string $delimiter = '.', array $excepts = ['*']) : array|string
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = static::backquote($item, $symbol, $delimiter, $excepts);
            }
            return $value;
        }

        return implode(
            $delimiter,
            array_map(
                fn($s) => in_array($s, $excepts) ? $s : $symbol . $s . $symbol,
                explode(
                    $delimiter,
                    str_replace($symbol, '', $value)
                )
            )
        );
    }

    /**
     * 表达式
     */
    public static function express(Raw|string $column, string $operator, mixed $value) : string
    {
        $column = $column instanceof Raw ? $column : static::backquote($column);

        return sprintf('%s %s %s', $column, $operator, $value);
    }

    /**
     * 复杂条件
     */
    public static function complex(array $condition) : string
    {
        $blocks = [];

        foreach ($condition as $key => $item) {
            $logic = array_shift($item);
            if ($key) {
                $blocks[] = $logic;
            }
            if (is_array($item[0])) {
                $blocks[] = '('. static::complex($item[0]) . ')';
            } else {
                $blocks[] =  static::express(...$item);
            }
        }

        return implode(' ', $blocks);
    }





    /**
     * 查询语句
     */
    public static function select(array $bindings) : string
    {
        return implode(' ', array_filter([
            'SELECT',
            static::field($bindings['field'] ?? ['*']),
            'FROM',
            static::as(...$bindings['from']),
            static::join($bindings['join'] ?? []),
            static::where($bindings['where'] ?? []),
            static::groupBy($bindings['groupBy'] ?? []),
            static::having($bindings['having'] ?? []),
            $bindings['union'] ?? '',
            static::orderBy($bindings['orderBy'] ?? []),
            static::limit($bindings['limit'] ?? []),
        ], fn($s) => trim($s)));
    }

    /**
     * 插入语句
     */
    public static function insert(array $table, array $fields, array $values) : string
    {
        // 字段处理
        $fields = static::backquote($fields);

        // 数据处理
        foreach ($values as $key => $mark) {
            $values[$key] = '(' . implode(', ', $mark) . ')';
        }

        // 返回语句
        return implode(' ', [
            'INSERT INTO',
            static::as(...$table),
            '(' . implode(', ', $fields) . ')',
            'VALUES',
            implode(', ', $values),
        ]);
    }

    /**
     * 修改语句
     */
    public static function update(array $table, array $setdata, array $wheres = []) : string
    {
        // 数据处理
        foreach ($setdata as $key => $item) {
            $setdata[$key] = static::backquote($item[0]) . ' = ' . $item[1];
        }

        // 返回语句
        return implode(' ', [
            'UPDATE',
            static::as(...$table),
            'SET',
            implode(', ', $setdata),
            static::where($wheres),
        ]);
    }

    /**
     * 删除语句
     */
    public static function delete(array $table, array $wheres = []) : string
    {
        return implode(' ', [
            'DELETE FROM',
            static::as(...$table),
            static::where($wheres),
        ]);
    }

    /**
     * 清空语句
     */
    public static function truncate(array $table) : string
    {
        return implode(' ', [
            'TRUNCATE TABLE',
            static::as(...$table),
        ]);
    }
}