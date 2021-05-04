<?php

return [
    'default'           =>  'mysql',
    'pool'              =>  swoole_cpu_num() * 10,

    'mysql'             =>  [
        'host'          =>  '127.0.0.1',
        'port'          =>  3306,

        'dbname'        =>  'fairs',
        'username'      =>  'root',
        'password'      =>  '123456',
        'charset'       =>  'utf8',
        'collation'     =>  'utf8_unicode_ci',

        'options'       =>  [],
        'attributes'    =>  [],
    ]
];