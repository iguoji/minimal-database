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
    public function __construct(array $configs)
    {
        $this->cluster = new Cluster($configs, Proxy::class);
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
     * 获取主写服务器
     */
    public function master(string $key = null)
    {
        return $this->connection($key, 'master');
    }

    /**
     * 获取从读服务器
     */
    public function slave(string $key = null)
    {
        return $this->connection($key, 'slave');
    }

    /**
     * 开启事务
     */
    public function beginTransaction() : bool
    {
        Context::incr('database:transaction:level');
        if (Context::get('database:transaction:level') === 1) {
            return $this->master()->beginTransaction();
        } else {
            return (bool) $this->master()->exec('SAVEPOINT TRANS' . Context::get('database:transaction:level'));
        }
    }

    /**
     * 是否在事务中
     */
    public function inTransaction() : bool
    {
        return Context::has('database:transaction:level') && Context::get('database:transaction:level') >= 1;
    }

    /**
     * 提交事务
     */
    public function commit() : bool
    {
        $level = Context::decr('database:transaction:level');
        if ($level === 0) {
            return $this->master()->commit();
        } else {
            return true;
        }
    }

    /**
     * 事务回滚
     */
    public function rollBack() : bool
    {
        $level = Context::get('database:transaction:level');
        if ($level === 1) {
            $bool = $this->master()->rollBack();
        } else if ($level > 1) {
            $bool = (bool) $this->master()->exec('ROLLBACK TO SAVEPOINT TRANS' . $level);
        }
        $level = max(0, $level - 1);
        Context::set('database:transaction:level', $level);
        return isset($bool) ? $bool : true;
    }

    /**
     * 查询数据
     */
    public function query(string $sql, array $data = []) : array
    {
        $conn = $this->inTransaction() ? $this->master() : $this->slave();
        $statement = $conn->prepare($sql);
        $statement->execute($data);
        $statement->setFetchMode(PDO::FETCH_ASSOC);
        return $statement->fetchAll();
    }

    /**
     * 获取一行
     */
    public function first(string $sql, array $data = []) : mixed
    {
        $conn = $this->inTransaction() ? $this->master() : $this->slave();
        $statement = $conn->prepare($sql);
        $statement->execute($data);
        $statement->setFetchMode(PDO::FETCH_ASSOC);
        return $statement->fetch();
    }

    /**
     * 查询数值
     */
    public function number(string $sql, array $data = []) : mixed
    {
        $conn = $this->inTransaction() ? $this->master() : $this->slave();
        $statement = $conn->prepare($sql);
        $statement->execute($data);
        $statement->setFetchMode(PDO::FETCH_NUM);
        return $statement->fetchColumn();
    }

    /**
     * 操作数据
     */
    public function execute(string $sql, array $data = []) : int
    {
        $statement = $this->master()->prepare($sql);
        $statement->execute($data);
        return $statement->rowCount();
    }

    /**
     * 最后的Sql
     */
    public function getLastSql() : string
    {
        return Context::get('database:sql');
    }

    /**
     * 未知函数
     */
    public function __call(string $method, array $arguments) : mixed
    {
        var_dump($method, $arguments);
        $group = in_array($method, ['beginTransaction', 'commit', 'exec', 'inTransaction', 'rollBack']) ? 'master' : 'slave';
    }
}