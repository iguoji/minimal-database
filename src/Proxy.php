<?php
declare(strict_types=1);

namespace Minimal\Database;

use PDO;
use Throwable;
use Minimal\Support\Arr;

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
     * 未知函数
     */
    public function __call(string $method, array $arguments)
    {
        return $this->handle->$method(...$arguments);
    }
}