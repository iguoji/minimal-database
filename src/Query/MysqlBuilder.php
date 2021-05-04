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
    public static function backquote(string $value, string $symbol = '`', string $delimiter = '.', array $excepts = ['*']) : string
    {
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
}