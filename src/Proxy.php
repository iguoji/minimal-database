<?php
declare(strict_types=1);

namespace Minimal\Database;

use PDO;
use PDOStatement;
use PDOException;
use Throwable;
use RuntimeException;
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
     * 句柄标识
     */
    protected int $token;

    /**
     * 构造函数
     */
    public function __construct(array $config)
    {
        // 保存配置
        $this->config = Arr::array_merge_recursive_distinct($this->getDefaultConfigStruct(), $config);
        // 创建连接
        $this->connect();
        // 当前标志
        $this->token = Context::incr('database:proxy');
    }

    /**
     * 句柄标识
     */
    public function getToken() : string
    {
        return 'Proxy# ' . $this->token;
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
            $bool = $this->handle->beginTransaction();
        } else {
            $this->handle->exec('SAVEPOINT TRANS' . $level);
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
            $bool = $this->handle->rollBack();
        } else if ($level > 1) {
            $this->handle->exec('ROLLBACK TO SAVEPOINT TRANS' . $level);
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
        if ($level === 0) {
            return $this->handle->commit();
        } else {
            return true;
        }
    }

    /**
     * 运行语句
     */
    public function run(Statement $origin) : mixed
    {
        // 最终结果
        $result = null;
        // 预定义
        $statement = $this->handle->prepare($origin->getSql());
        if (false === $statement) {
            echo 'Sql::' . PHP_EOL;
            echo $origin->getSql() . PHP_EOL;
            echo PHP_EOL;
            throw new PDOException($this->handle->errorInfo()[2]);
        }
        // 执行
        if (false === $statement->execute()) {
            echo 'Sql::' . PHP_EOL;
            echo $origin->getSql() . PHP_EOL;
            echo PHP_EOL;
            throw new PDOException($statement->errorInfo()[2]);
        }
        // 当前句柄
        $handle = $statement;
        // 循环处理
        $bindings = $origin->getBindings();
        foreach ($bindings as $bind) {
            // 当前句柄
            if ($bind['method'] == 'handle') {
                $handle = $this->handle;
                continue;
            }
            // 方法名称
            $method = $bind['method'];
            // 处理程序
            $result = $handle->$method(...$bind['arguments']);
        }
        // 像数字，就转成数字
        if (is_numeric($result) && !is_int($result) && !is_float($result)) {
            $result = false === strpos($result, '.')
                ? (int) $result
                : (float) number_format((float) $result, 2, '.', '');
        }
        // 返回结果
        return $result;
    }

    /**
     * 查询数据
     */
    public function query(Statement|string $sql, array $data = []) : array|string|int|float
    {
        $statement = $sql instanceof Statement ? $sql : new Statement($sql);
        $statement->fetchAll(PDO::FETCH_ASSOC);
        return $this->run($statement);
    }

    /**
     * 执行语句
     */
    public function execute(Statement|string $sql) : int|string
    {
        $statement = $sql instanceof Statement ? $sql : new Statement($sql);
        $statement->rowCount();
        return $this->run($statement);
    }

    /**
     * 未知函数
     */
    public function __call(string $method, array $arguments)
    {
        if (!method_exists($this->handle, $method)) {
            throw new RuntimeException(sprintf('Call to undefined method %s::%s()', $this->handle::class, $method));
        }
        return $this->handle->$method(...$arguments);
    }
}