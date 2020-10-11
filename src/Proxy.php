<?php
declare(strict_types=1);

namespace Minimal\Database;

use PDO;
use PDOStatement;
use Throwable;
use Minimal\Support\Arr;
use Minimal\Support\Context;

class Proxy
{
    /**
     * 驱动句柄
     */
    protected PDO $handle;

    /**
     * 配置信息
     */
    protected array $config;

    /**
     * 构造函数
     */
    public function __construct(array $config)
    {
        // 保存配置
        $this->config = Arr::array_merge_recursive_distinct($this->getDefaultConfigStruct(), $config);
        // 创建连接
        $this->connect();
    }

    /**
     * 获取默认配置结构
     */
    public function getDefaultConfigStruct() : array
    {
        return [
            'host'          =>  '127.0.0.1',
            'port'          =>  3306,
            'dbname'        =>  '',
            'username'      =>  '',
            'password'      =>  '',
            'charset'       =>  'utf8mb4',
            'collation'     =>  'utf8mb4_unicode_ci',
            'options'       =>  [
                PDO::ATTR_TIMEOUT   =>  2,
                PDO::ATTR_ERRMODE   =>  PDO::ERRMODE_SILENT,
            ]
        ];
    }

    /**
     * 创建连接
     */
    public function connect(bool $reconnect = true) : PDO
    {
        try {
            // 创建驱动
            $this->handle = new PDO(
                sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=%s'
                    , $this->config['host']
                    , (int) $this->config['port']
                    , $this->config['dbname']
                    , $this->config['charset']
                )
                , $this->config['username']
                , $this->config['password']
                , $this->config['options']
            );
            // 返回驱动
            return $this->handle;
        } catch (Throwable $th) {
            // 尝试重连一次
            if ($reconnect) {
                return $this->connect(false);
            }
            throw $th;
        }
    }

    /**
     * 释放连接
     */
    public function release() : void
    {
        if ($this->handle->inTransaction()) {
            $this->handle->rollBack();
        }
    }

    /**
     * 开启事务
     */
    public function beginTransaction() : bool
    {
        $level = Context::incr('database:transaction:level');
        if ($level === 1) {
            $bool = $this->__call('beginTransaction', []);
        } else {
            $this->__call('exec', ['SAVEPOINT TRANS' . $level]);
        }
        return isset($bool) ? $bool : true;
    }

    /**
     * 是否在事务中
     */
    public function inTransaction() : bool
    {
        return Context::has('database:transaction:level') && Context::get('database:transaction:level') >= 1;
    }

    /**
     * 事务回滚
     */
    public function rollBack() : bool
    {
        $level = Context::get('database:transaction:level');
        if ($level === 1) {
            $bool = $this->__call('rollBack', []);
        } else if ($level > 1) {
            $this->__call('exec', ['ROLLBACK TO SAVEPOINT TRANS' . $level]);
        }
        $level = max(0, $level - 1);
        Context::set('database:transaction:level', $level);
        return isset($bool) ? $bool : true;
    }

    /**
     * 提交事务
     */
    public function commit() : bool
    {
        $level = Context::decr('database:transaction:level');
        echo 'commit: ' . $level, PHP_EOL;
        if ($level === 0) {
            return $this->__call('commit', []);
        } else {
            return true;
        }
    }

    /**
     * 查询数据
     */
    public function query(string $sql, array $data = []) : array
    {
        return $this->prepare($sql, $data, PDO::FETCH_ASSOC)->fetchAll();
    }

    /**
     * 查询一行
     */
    public function first(string $sql, array $data = []) : array
    {
        return $this->prepare($sql, $data, PDO::FETCH_ASSOC)->fetch();
    }

    /**
     * 查询值
     */
    public function value(string $sql, array $data = [], int $column = 0) : mixed
    {
        return $this->prepare($sql, $data, PDO::FETCH_NUM)->fetchColumn($column);
    }

    /**
     * 操作数据
     */
    public function execute(string $sql, array $data = []) : int
    {
        return $this->prepare($sql, $data)->rowCount();
    }

    /**
     * 预执行语句
     */
    public function prepare(string $sql, array $data = [], ?int $mode = null) : PDOStatement
    {
        $statement = $this->__call('prepare', [$sql, ...$data]);
        if (isset($mode)) {
            $statement->setFetchMode($mode);
        }
        return $statement;
    }

    /**
     * 未知函数
     */
    public function __call(string $method, array $arguments)
    {
        if (in_array($method, ['prepare', 'exec'])) {
            $data = array_splice($arguments, 1);
        }
        $result = $this->handle->$method(...$arguments);
        if ($result instanceof PDOStatement && isset($data)) {
            $result->execute($data);
        }
        return $result;
    }
}