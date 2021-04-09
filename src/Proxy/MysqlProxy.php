<?php
declare(strict_types=1);

namespace Minimal\Database\Proxy;

/**
 * Mysql代理
 */
class MysqlProxy extends PDOProxy
{
    /**
     * 获取DSN
     */
    public function getDsn() : string
    {
        return $this->config('unix_socket')
            ? sprintf('mysql:unix_socket=%s;dbname=%s;charset=%s', $this->config('unix_socket'), $this->config('dbname'), $this->config('charset', 'utf8'))
            : sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $this->config('host'), $this->config('port'), $this->config('dbname'), $this->config('charset', 'utf8'));
    }
}