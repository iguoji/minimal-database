<?php
declare(strict_types=1);

namespace Minimal\Database\Proxy;

use PDO;
use Throwable;
use PDOException;
use Swoole\Timer;
use Swoole\Coroutine;
use Minimal\Database\Statement\PDOStatement;
use Minimal\Database\Contracts\ProxyInterface;
use Minimal\Database\Contracts\StatementInterface;

/**
 * PDO代理类
 */
class PDOProxy implements ProxyInterface
{
    /**
     * PDO句柄
     */
    protected PDO $handle;

    /**
     * 最后的语句
     */
    protected string $sql;

    /**
     * 用户的参数
     */
    protected array $inputParameters = [];

    /**
     * 预定义选项
     */
    protected array $options = [];

    /**
     * 预定义属性
     */
    protected array $attributes = [
        /**
         * 设置错误模式为抛出异常
         * PDO::ERRMODE_SILENT
         * PDO::ERRMODE_EXCEPTION
         */
        PDO::ATTR_ERRMODE           =>  PDO::ERRMODE_EXCEPTION,

        /**
         * 设置PDO返回的Statement类
         * 必须是数组类型
         */
        PDO::ATTR_STATEMENT_CLASS   =>  [PDOStatement::class],

        /**
         * 启用或禁用预处理语句的模拟。
         * 有些驱动不支持或有限度地支持本地预处理。
         * 使用此设置强制PDO总是模拟预处理语句（如果为 true ），或试着使用本地预处理语句（如果为 false）。
         * 如果驱动不能成功预处理当前查询，它将总是回到模拟预处理语句上。
         */
        PDO::ATTR_EMULATE_PREPARES  =>  false,

        /**
         * 提取的时候将数值转换为字符串
         * 只有同时和 PDO::ATTR_EMULATE_PREPARES 属性保持为 false 才有效果
         */
        PDO::ATTR_STRINGIFY_FETCHES =>  false,
    ];

    /**
     * 构造方法
     */
    public function __construct(protected array $config)
    {
    }

    /**
     * 连接驱动
     */
    public function connect(int $recount = 1) : mixed
    {
        try {
            // 实例化PDO
            $this->handle = new PDO(
                $this->getDsn()
                , $this->getUsername()
                , $this->getPassword()
                , $this->getOptions()
            );
            // 设置属性
            foreach ($this->getAttributes() as $key => $value) {
                $this->handle->setAttribute($key, $value);
            }
            // 给与标识
            $this->handle->token = 'PDO#' . mt_rand(1000, 9999);
        } catch (PDOException $th) {
            // 错误重连
            if ($recount > 0) {
                return $this->connect($recount - 1);
            }
        }
        // 返回结果
        return $this;
    }

    /**
     * 释放驱动
     */
    public function release() : void
    {
        // 清空参数
        $this->inputParameters = [];
    }



    /**
     * 开启事务
     */
    public function beginTransaction() : bool
    {
        // 事务编号
        $level = Coroutine::getContext()['database:transaction:level'] ?? 0;
        $level++;
        Coroutine::getContext()['database:transaction:level'] = $level;

        // 初次事务
        if (1 === $level) {
            return $this->__call('beginTransaction', []);
        }

        // 嵌套事务
        $this->__call('exec', ['SAVEPOINT TRANS' . $level]);

        // 返回结果
        return true;
    }

    /**
     * 提交事务
     */
    public function commit() : bool
    {
        // 事务编号
        $level = Coroutine::getContext()['database:transaction:level'] ?? 0;
        $level--;
        Coroutine::getContext()['database:transaction:level'] = $level;

        // 最终事务
        if (0 === $level) {
            return $this->__call('commit', []);
        }

        // 返回结果
        return true;
    }

    /**
     * 回滚事务
     */
    public function rollBack() : bool
    {
        // 事务编号
        $level = Coroutine::getContext()['database:transaction:level'] ?? 0;

        // 按事务编号处理
        if (1 === $level) {
            // 初次事务
            $bool = $this->__call('rollBack', []);
        } else {
            // 嵌套事务
            $this->__call('exec', ['ROLLBACK TO SAVEPOINT TRANS' . $level]);
        }

        // 更新编号
        $level = max(0, $level - 1);
        Coroutine::getContext()['database:transaction:level'] = $level;

        // 返回结果
        return isset($bool) ? $bool : true;
    }

    /**
     * 是否在事务中
     */
    public function inTransaction() : bool
    {
        return isset(Coroutine::getContext()['database:transaction:level'])
            && Coroutine::getContext()['database:transaction:level'] >= 1;
    }



    /**
     * 执行查询
     */
    public function query(string $sql, array $parameters = []) : StatementInterface
    {
        $this->inputParameters = $parameters;
        return $this->__call('prepare', [$sql]);
    }

    /**
     * 执行语句
     */
    public function execute(string $sql, array $parameters = []) : int
    {
        $statement = $this->query($sql, $parameters);
        return $statement->rowCount();
    }



    /**
     * 获取单行
     */
    public function first(string $sql, array $parameters = []) : array|bool
    {
        $statement = $this->query($sql, $parameters);
        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 获取全部
     */
    public function all(string $sql, array $parameters = []) : array|bool
    {
        $statement = $this->query($sql, $parameters);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 获取单列
     */
    public function column(string $sql, array $parameters = []) : array|bool
    {
        $statement = $this->query($sql, $parameters);
        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * 获取单值
     */
    public function value(string $sql, array $parameters = []) : mixed
    {
        $statement = $this->query($sql, $parameters);
        return $statement->fetchColumn();
    }



    /**
     * 获取最后的语句
     */
    public function lastSql() : string
    {
        return $this->sql ?? '';
    }

    /**
     * 获取最后的自增ID
     */
    public function lastInsertId(string $name = null) : string
    {
        return $this->__call('lastInsertId', [$name]);
    }



    /**
     * 获取配置
     */
    public function config(string $key, mixed $default = null) : mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * 获取Dsn
     */
    public function getDsn() : string
    {
        return $this->config('dsn', '');
    }

    /**
     * 获取账号
     */
    public function getUsername() : string
    {
        return $this->config('username', '');
    }

    /**
     * 获取密码
     */
    public function getPassword() : string
    {
        return $this->config('password', '');
    }

    /**
     * 获取选项
     */
    public function getOptions() : array
    {
        $userOptions = $this->config('options', []);
        foreach ($userOptions as $key => $value) {
            $this->options[$key] = $value;
        }
        return $this->options;
    }

    /**
     * 获取所有预定义属性
     */
    public function getAttributes() : array
    {
        $userAttributes = $this->config('attributes', []);
        foreach ($userAttributes as $key => $value) {
            $this->attributes[$key] = $value;
        }
        return $this->attributes;
    }



    /**
     * 未知方法
     */
    public function __call(string $method, array $arguments)
    {
        // 连接驱动
        if (!isset($this->handle)) {
            $this->connect();
        }

        // 出现错误可重试3次
        for ($i = 0;$i < 3; $i++) {
            try {
                // 调用方法
                $result = $this->handle->$method(...$arguments);

                // 保存语句
                if ($result instanceof PDOStatement) {
                    $this->sql = $result->queryString;
                } else if ($method == 'exec') {
                    $this->sql = $arguments[0] ?? '';
                }

                // 主动执行
                if ($result instanceof PDOStatement) {
                    $bool = $result->execute($this->inputParameters);
                    if (false === $bool) {
                        throw new PDOException('database PDOStatement execute fail');
                    }
                }

                // 执行结束
                break;
            } catch (Throwable $ex) {
                // 错误重连
                $pdoCode = $this->handle->errorInfo()[1];
                $statementCode = isset($result) ? $result->errorInfo()[1] : 0;
                $reConnectCode = [2002, 2006, 2013];
                if (in_array($pdoCode, $reConnectCode) || in_array($statementCode, $reConnectCode)) {
                    $this->connect();
                    continue;
                }
                // 其他错误
                var_dump($ex->getMessage(), $method, $arguments, $this->inputParameters, $this->handle->errorInfo(), isset($result) ? $result?->errorInfo() : []);
                echo PHP_EOL;
                echo PHP_EOL;
                throw $ex;
            }
        }

        // 清空参数
        $this->inputParameters = [];

        // 返回结果
        return $result;
    }
}