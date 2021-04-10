<?php
declare(strict_types=1);

namespace Minimal\Database;

use Exception;
use Minimal\Database\Contracts\QueryInterface;
use Minimal\Database\Contracts\ProxyInterface;

/**
 * 管理类
 */
class Manager
{
    /**
     * 最后的Sql语句
     */
    protected string $sql;

    /**
     * 当前连接名称
     */
    protected string $store = 'default';

    /**
     * 当前连接配置
     */
    protected array $config = [];

    /**
     * 连接列表
     */
    protected array $connections = [];

    /**
     * 构造函数
     */
    public function __construct(protected array $configs)
    {}

    /**
     * 配置处理
     */
    public function config(string $name) : array
    {
        // 获取配置
        $config = $this->configs[$name] ?? [];
        if (empty($config)) {
            throw new Exception("database config [$name] not found");
        }
        // 默认驱动
        $driver = $config['driver'] ?? 'mysql';
        // 默认代理
        if (!isset($config['proxy']) || is_null($config['proxy'])) {
            if ($driver == 'mysql') {
                $config['proxy'] = \Minimal\Database\Proxy\MysqlProxy::class;
            } else {
                throw new Exception("database not support [$driver] driver proxy");
            }
        }
        // 默认查询
        if (!isset($config['query']) || is_null($config['query'])) {
            if ($driver == 'mysql') {
                $config['query'] = \Minimal\Database\Query\MysqlQuery::class;
            } else {
                throw new Exception("database not support [$driver] driver query");
            }
        }
        // 返回配置
        return $config;
    }

    /**
     * 获取连接
     */
    public function connection(string $name = 'default') : ProxyInterface
    {
        // 保存名称
        $this->store = $name;
        // 存在连接
        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }
        // 配置处理
        $config = $this->config($name);
        // 返回连接
        return $this->connections[$name] = new $config['proxy']($config);
    }

    /**
     * 使用主写连接
     */
    public function master(int|string $key = null) : static
    {
        $this->connection('master', $key);
        return $this;
    }

    /**
     * 使用从读连接
     */
    public function slave(int|string $key = null) : static
    {
        $this->connection('slave', $key);
        return $this;
    }

    /**
     * 语法构建
     */
    public function table(string $table, string $as = null) : QueryInterface
    {
        // 获取配置
        $config = $this->config($this->store);
        // 返回查询
        return (new $config['query']($this))->from($table, $as);
    }

    /**
     * 原始数据
     */
    public function raw(string $sql) : Raw
    {
        return new Raw($sql);
    }

    /**
     * 未知函数
     */
    public function __call(string $method, array $arguments) : mixed
    {
        // 获取连接
        $conn = $this->connection();
        // 获取结果
        $result = $conn->$method(...$arguments);
        // 保存本次Sql
        $this->sql = $conn->lastSql();
        // 返回结果
        return $result;
    }
}