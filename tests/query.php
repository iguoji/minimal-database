<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Minimal\Database\Raw;
use Minimal\Database\Query;
use Minimal\Database\Builder;
use Minimal\Database\Manager;
use Minimal\Support\Context;
use Swoole\Coroutine;



Coroutine::create(function(){
    try {
        $manager = new Manager(
            require dirname(dirname(__DIR__)) . '/framework/tests/config/db.php'
        );
        echo Context::get('proxy'), PHP_EOL;
        echo PHP_EOL;
        echo PHP_EOL;
        echo PHP_EOL;


        var_dump($manager->table('account')->select());
        var_dump($manager->getLastSql());

        // echo $manager->master()->test(), PHP_EOL;
        // echo $manager->test(), PHP_EOL;
        // echo $manager->test(), PHP_EOL;
        // echo $manager->slave()->test(), PHP_EOL;
        // echo $manager->test(), PHP_EOL;
        // echo $manager->master()->test(), PHP_EOL;
        // echo $manager->master()->inTransaction(), PHP_EOL;


        // for ($i=0; $i < 200; $i++) {
        //     echo $manager->test(), PHP_EOL;
        // }
    } catch (\Throwable $th) {
        echo $th->getMessage(), PHP_EOL;
        echo $th->getFile(), PHP_EOL;
        echo $th->getLine(), PHP_EOL;
        print_r($th->getTrace());
        echo PHP_EOL;
    }
});










// $query = new Query();
// var_dump($query->beginTransaction());





/*

基本语法测试ok


$builder = new Builder('personal', 'p');
echo $builder
    ->join('hobby', 'hobby.user', '=', 'p.id')
    ->leftJoin('pets', 'pets.user', '=', 'p.id')
    ->where('field', 111)
    ->where('field', '222')
    ->where('field', '=', '333')
    ->where('field', '!=', 444)
    ->where('field')
    ->where('field', null)
    ->where('field', '=', null)
    ->where('field', '!=', null)
    ->where('field', '<>', null)
    ->where(function($query){
        $query->where('field', '>', 555);
        $query->where('field', '<', 666);
    })
    ->orWhere('field', '>=', 777)
    ->orWhere(function($query){
        $query->where('field', '>', 888);
        $query->orWhere('field', '<', 999);
    })
    ->where('field', 'in', [1, 2, 3])
    ->orWhere('field', 'not in', ['a', 'b', 'c'])
    ->page(1, 20)
    ->order('field')
    ->order('field2', 'desc')
    ->select('p.id', new Raw('p.*'));

echo PHP_EOL;
echo PHP_EOL;
echo PHP_EOL;

$builder = new Builder('personal');
echo $builder
    ->insert([
        'a'     =>  100,
        'b'     =>  '200',
        'c'     =>  300.33,
        'd'     =>  new Raw('current_timestamp()')
    ]);

echo PHP_EOL;
echo PHP_EOL;
echo PHP_EOL;

$builder = new Builder('personal');
echo $builder
->insert([
    [
        'a'     =>  100,
        'b'     =>  '200',
        'c'     =>  300.33,
        'd'     =>  new Raw('current_timestamp()')
    ],
    [
        'a'     =>  400,
        'b'     =>  '500',
        'c'     =>  600.66,
        'd'     =>  new Raw('current_timestamp()')
    ]
]);

echo PHP_EOL;
echo PHP_EOL;
echo PHP_EOL;

$builder = new Builder('personal');
echo $builder
    ->where('a', 1)
    ->orWhere('b', '!=', null)
    ->update([
        'updated_at' => date('Y-m-d H:i:s'),
        'deleted_at' => date('Y-m-d H:i:s'),
    ]);

    echo PHP_EOL;
    echo PHP_EOL;
    echo PHP_EOL;

$builder = new Builder('personal');
echo $builder
    ->where('c', 1)
    ->orWhere('d', '!=', null)
    ->delete();


*/