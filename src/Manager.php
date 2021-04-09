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
     * 连接列表
     */
    protected array $connections = [];

    /**
     * 构造函数
     */
    public function __construct(protected array $configs)
    {}

    /**
     * 获取连接
     */
    public function connection(string $name = 'default') : ProxyInterface
    {
        // 存在连接
        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }
        // 获取配置
        $config = $this->configs[$name] ?? [];
        if (empty($config)) {
            throw new Exception("database config [$name] not found");
        }
        // 默认配置
        $proxy = $config['proxy'] ?? null;
        if (is_null($proxy)) {
            $driver = $config['driver'] ?? 'mysql';
            if ($driver == 'mysql') {
                $proxy = \Minimal\Database\Proxy\MysqlProxy::class;
            } else {
                throw new Exception("database not support [$driver] driver");
            }
        }
        // 返回连接
        return $this->connections[$name] = new $proxy($config);
    }

    /**
     * 使用主写连接
     */
    public function master(string $key = null) : static
    {
        $this->connection($key, 'master');
        return $this;
    }

    /**
     * 使用从读连接
     */
    public function slave(string $key = null) : static
    {
        $this->connection($key, 'slave');
        return $this;
    }

    /**
     * 语法构建
     */
    public function table(string $table, string $as = null) : QueryInterface
    {
        // 等待：系统预留
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