<?php
declare(strict_types=1);

namespace Minimal\Database\Query;

use Closure;
use Minimal\Database\Raw;
use Minimal\Database\Contracts\QueryInterface;

/**
 * Mysql条件
 */
class MysqlCondition
{
    /**
     * 数据绑定
     */
    protected array $bindings = [];

    /**
     * 构造函数
     */
    public function __construct(protected QueryInterface $query)
    {}

    /**
     * 获取数据
     */
    public function getBindings() : array
    {
        return $this->bindings;
    }

    /**
     * 条件查询
     */
    public function where(Closure|Raw|string $column, mixed $operator = null, mixed $value = null, string $logic = 'AND') : static
    {
        // 按情况处理
        if ($column instanceof Closure) {
            // 回调条件
            $condition = new static($this->query);
            $column($condition);
            $this->bindings[] = [$logic, $condition->getBindings()];
        } else {
            // 符号和值
            if ((is_null($operator) || $operator == '=') && is_null($value)) {
                $operator = 'IS';
                $value = 'NULL';
            } else if (($operator == '<>' || $operator == '!=') && (is_null($value) || (is_string($value) && strtoupper($value) == 'NULL'))) {
                $operator = 'IS NOT';
                $value = 'NULL';
            } else if (!is_null($operator) && is_null($value)) {
                $value = $operator;
                $operator = '=';
            }
            $operator = strtoupper($operator);

            // 值可能是表字段
            $isTableField = false;
            if (is_string($value) && false !== strpos($value, '.')) {
                $tables = $this->query->getBinding('table');
                list($t, $f) = explode('.', $value, 2);
                if (in_array($t, $tables)) {
                    $isTableField = true;
                }
            }

            // 占位标记
            $mark = in_array($operator, ['IS', 'IS NOT']) || $isTableField || $value instanceof Raw ? $value : $this->query->mark($column, $value);
            // 保存数据
            $this->bindings[] = [$logic, $column, $operator, $mark];
        }

        // 返回结果
        return $this;
    }

    /**
     * 条件查询 - OR
     */
    public function orWhere(Closure|Raw|string $column, mixed $operator = null, mixed $value = null) : static
    {
        return $this->where($column, $operator, $value, 'OR');
    }
}