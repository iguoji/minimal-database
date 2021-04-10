<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Minimal\Database\Manager;

// 显示错误
ini_set('display_errors', 'stderr');
// 报告错误
error_reporting(E_ALL);


$db = new Manager(
    require 'db.php'
);


// $db->query('SELECT * FROM `account`');
// $statement = $db->query('SELECT * FROM `game`');
// var_dump($statement);
// var_dump($statement->execute());
// var_dump($statement->fetchAll());
// $db->execute('UPDATE `game` SET `sort` = `sort` + 1');


// var_dump($db->first('SELECT * FROM `game`'));
// var_dump($db->all('SELECT * FROM `game`'));
// var_dump($db->column('SELECT `name`,`icon` FROM `game`'));
// var_dump($db->value('SELECT `sort`,`icon` FROM `game`'));


// var_dump($db->execute('UPDATE `game` SET `sort` = `sort` + ? WHERE `name` = ?', [rand(10, 20), '英雄联盟']));

// var_dump($db->execute('UPDATE `game` SET `sort` = `sort` + :sort WHERE `name` = :name', [rand(10, 20), "`刺激战场' or 1=1"]));


// var_dump($db->first('SELECT * FROM `game` WHERE `name` = ?', ["'1' OR 2=2"]));

// var_dump($db->all('SELECT `id`, `name` FROM `game` WHERE `name` IN (?, ?)', ['英雄联盟', '王者荣耀']));


// var_dump($db->table('game', 'g')->toSql());

// var_dump($db->table('game', 'g')->leftJoin('account', 'account.id', '=', 'g.id')->toSql());
// var_dump($db->table('game', 'g')->rightJoin('account', 'account.id', '=', 'g.id')->toSql());
// var_dump($db->table('game', 'g')->crossJoin('account', 'account.id', '=', 'g.id')->toSql());

var_dump(
    // $db->table('account')->count('id'),
    // $db->table('account')->min('phone'),
    // $db->table('account')->max('id'),
    // $db->table('account')->sum('realname'),

    // $db->table('account')->where('zone', '86')->where('phone', '13000000003')->first(),

    // $db->table('account', 'aaa')
    //     ->leftJoin('message', 'message.uid', 'aaa.id')
    //     ->field('aaa.id', 'aaa.realname', 'aaa.money', 'aaa.updated_at')
    //     ->where('aaa.zone', '86')
    //     ->where('aaa.bank_name', '民生银行')
    //     ->page(2, 2)
    //     ->orderByDesc('aaa.updated_at')
    //     ->orderByDesc('aaa.id')
    //     ->all(),


    // $db->table('game_course', 'gc')
    //     ->join('game', 'game.id', 'gc.gid')
    //     ->page(1, 20)
    //     ->select('game.name', 'gc.index', 'gc.start_at', 'gc.end_at'),

    $db->table('game')
        ->leftJoin('game_course', 'game_course.gid', 'game.id')
        ->where('game.id', '>', 2)
        ->groupBy('game.id')
        ->having('game.name', '=', '香港跑马')
        ->select('game.id', 'game.name', $db->raw('COUNT(game_course.id)')),


    $db->table('log')
        ->insert([[
            'type'  =>  1,
            'uid'   =>  rand(1, 5),
            'method'=>  rand(1, 2),
            'path'  =>  '/test',
            'param' =>  null,
            'ip'    =>  '127.0.0.1',
            'ua'    =>  null,
            'created_at'    =>  date('Y-m-d H:i:s')
        ], [
            'type'  =>  1,
            'uid'   =>  rand(1, 5),
            'method'=>  rand(1, 2),
            'path'  =>  '/test',
            'param' =>  null,
            'ip'    =>  '127.0.0.1',
            'ua'    =>  null,
            'created_at'    =>  date('Y-m-d H:i:s')
        ]]),

    // $db->table('account', 'a')
    //     ->field('message', 'message.uid', '=', 'a.id')
    //     ->where('account.phone', 'like', '1680000%')
    //     ->all()
);


var_dump($db->lastSql());