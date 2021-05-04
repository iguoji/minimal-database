<?php
declare(strict_types=1);

namespace Minimal\Database;

use Exception;
use Swoole\Coroutine\Channel;
use Minimal\Database\Contracts\QueryInterface;
use Minimal\Database\Contracts\ProxyInterface;

/**
 * 管理类
 */
class Manager
{
    /**
     * 当前驱动
     */
    protected string $driver;

    /**
     * 当前代理
     */
    protected string $proxy;

    /**
     * 当前查询
     */
    protected string $query;

    /**
     * 超时时间
     */
    protected float $timeout;

    /**
     * 连接池子
     */
    protected array $pool = [];

    /**
     * 构造函数
     */
    public function __construct(protected array $config, protected int $workerNum)
    {
        // 设置驱动
        $this->use($config['default'] ?? 'mysql');
    }

    /**
     * 切换驱动
     */
    public function use(string $name) : static
    {
        // 不存在配置
        if (!isset($this->config[$name])) {
            throw new Exception('database ' . $name . ' driver config dont\'s exists');
        }

        // 当前驱动
        $this->driver = $name;
        // 当前代理
        $this->proxy = $config[$this->driver]['proxy'] ?? \Minimal\Database\Proxy\MysqlProxy::class;
        // 当前查询
        $this->query = $config[$this->driver]['query'] ?? \Minimal\Database\Query\MysqlQuery::class;
        // 超时时间
        $this->timeout = $config[$this->driver]['timeout'] ?? 2;

        // 不存在连接则填充
        if (!isset($this->pool[$this->driver])) {
            $this->fill();
        }

        // 返回结果
        return $this;
    }

    /**
     * 填充连接
     */
    public function fill() : static
    {
        // 获取配置
        $config = $this->config[$this->driver];
        // 获取数量
        $size = max(1, (int) (($this->config['pool'] ?? swoole_cpu_num() * 10) / $this->workerNum));

        // 循环处理
        if (!isset($this->pool[$this->driver])) {
            $this->pool[$this->driver] = new Channel($size);
            for ($i = 0;$i < $size;$i++) {
                $proxyInterface = $this->proxy;
                $this->pool[$this->driver]->push(new $proxyInterface($config), $this->timeout);
            }
        }

        // 返回结果
        return $this;
    }

    /**
     * 获取连接
     */
    public function connection() : ProxyInterface
    {
        // 已有连接
        if (isset(\Swoole\Coroutine::getContext()['database:connection'])) {
            return \Swoole\Coroutine::getContext()['database:connection'];
        }

        // 获取连接
        $conn = $this->pool[$this->driver]->pop($this->timeout);
        if (false === $conn) {
            throw new Exception('很抱歉、数据库繁忙！');
        }

        // 临时保存
        \Swoole\Coroutine::getContext()['database:connection'] = $conn;
        // 记得归还
        \Swoole\Coroutine::defer(function() use($conn){
            $conn->release();
            $this->pool[$this->driver]->push($conn, $this->timeout);
        });
        // 返回连接
        return $conn;
    }

    /**
     * 语法构建
     */
    public function table(string $table, string $as = null) : QueryInterface
    {
        $queryInterface = $this->query;
        return (new $queryInterface($this))->from($table, $as);
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
        return $this->connection()->$method(...$arguments);
    }
}