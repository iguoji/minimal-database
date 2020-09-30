<?php
declare(strict_types=1);

namespace Minimal\Database;

use PDO;
use Swoole\Coroutine;
use Minimal\Pool\Cluster;
use Minimal\Support\Context;

/**
 * 数据库
 */
class Manager
{
    /**
     * 集群对象
     */
    protected Cluster $cluster;

    /**
     * 构造函数
     */
    public function __construct(array $configs, public int $workerId)
    {
        $this->cluster = new Cluster($configs);
    }

    /**
     * 获取连接
     */
    public function connection(string $key = null, string $group = 'slave')
    {
        $token = sprintf('pool:database:%s:%s', $group, $key ?? 'default');
        if (Context::has($token)) {
            return Context::get($token);
        }
        [$group, $key, $conn] = $this->cluster->get($group, $key);
        Context::set($token, $conn);
        Coroutine::defer(function() use($group, $key, $conn, $token){
            Context::del($token);
            $this->cluster->put($group, $key, $conn);
        });
        return $this;
    }

    /**
     * 未知函数
     */
    public function __call(string $method, array $arguments) : mixed
    {
        $group = in_array($method, ['beginTransaction', 'commit', 'exec', 'inTransaction', 'rollBack']) ? 'master' : 'slave';
    }
}