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


var_dump($db->all('SELECT `id`, `name` FROM `game` WHERE `name` IN (?, ?)', ['英雄联盟', '王者荣耀']));

var_dump($db->lastSql());