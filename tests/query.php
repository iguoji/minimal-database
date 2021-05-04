<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Swoole\Coroutine;
use Minimal\Database\Manager;
use Minimal\Database\Query\MysqlQuery;

// 显示错误
ini_set('display_errors', 'stderr');
// 报告错误
error_reporting(E_ALL);
// 地区时区
date_default_timezone_set('Asia/Shanghai');




Coroutine\run(function(){
    // 查询对象
    $query = new MysqlQuery(new Manager(require 'config.php', 4));
    // 语法测试
    var_dump(
        // $query->from('account')->all(),
        $query->from('account')->where('username', 'iguoji')->where('phone', 'like', '%187%')->value('username'),
        $query->from('account')->where('username', 'iguoji2')->column('uid'),
        $query->from('account')->count('uid'),
        $query->from('account')->min('id'),
        $query->from('account')->avg('id'),
        $query->from('account')->max('id'),
        // $query->from('account')->where('country')->where('phone', '!=')->all(),


        // $query->from('account_address', 'aa')
            // ->join('account', 'a', 'a.uid', '=', 'aa.uid')
            // ->leftJoin('region', 'r1', 'r1.country', 'aa.country')
            // ->rightJoin('region', 'r2', 'r2.province', 'aa.province')
        //     ->debug()

        // $query->from('account_address', 'aa')
        //     ->join('region', 'r1', function($subQuery){
        //         $subQuery->where('r1.country', '=', 'aa.country')->where('r1.type', 1);
        //     })
        //     ->debug()

        // $query->from('account_address', 'aa')
        //     ->join('region', 'r1', function($subQuery){
        //         $subQuery->where('r1.country', '=', 'aa.country')->where('r1.type', 1);
        //     })
        //     ->where('r1.country', 86)
        //     ->where('r1.country')
        //     ->where('r1.country', '!=')
        //     ->debug(),

        // $query->from('account_address', 'aa')
        //     ->where(function($subQuery){
        //         $subQuery->where('aa.country', 86);
        //         $subQuery->orWhere('aa.country', 68);
        //     })
        //     ->where('aa.country_name', '中国')
        //     ->debug(),

        // $query->from('account_address', 'aa')
        //     ->where('aa.country_name', '中国')
        //     ->where('aa.aaa', '111')
        //     ->orWhere('aa.bbb', '222')
        //     ->debug(),

        // $query->from('account_address', 'aa')
        //     ->join('account', 'a', 'a.uid', '=', 'aa.uid')
        //     ->leftJoin('region', 'r1', 'r1.country', 'aa.country')
        //     ->rightJoin('region', 'r2', 'r2.province', 'aa.province')
        //     ->where('aa.country_name', '中国')
        //     ->orWhere(function($subQuery){
        //         $subQuery->where('aaa', 111);
        //         $subQuery->where('bbb', 222);
        //     })
        //     ->orWhere(function($subQuery){
        //         $subQuery->where(function($subQuery1){
        //             $subQuery1->where('ddd', 444);
        //             $subQuery1->orWhere('eee', 555);
        //         });
        //         $subQuery->where(function($subQuery1){
        //             $subQuery1->where('fff', 666);
        //             $subQuery1->orWhere('ggg', 777);
        //             $subQuery1->orWhere(function($subQuery2){
        //                 $subQuery2->where('1', 1);
        //                 $subQuery2->where('2', 1);
        //             });
        //         });
        //     })
        //     ->debug(),

        // $query->from('account_address', 'aa')
        //     ->join('region', 'r1', function($subQuery){
        //         $subQuery->where('r1.country', 'aa.country');
        //         $subQuery->where('r1.type', 1);
        //     })
        //     ->join('region', 'r2', function($subQuery){
        //         $subQuery->where('r2.province', 'aa.province')
        //             ->where('r2.type', 2);
        //     })
        //     ->where('aa.country_name', '中国')
        //     ->orWhere(function($subQuery){
        //         $subQuery->where('aaa', 111);
        //         $subQuery->where('bbb', 222);
        //     })
        //     ->orWhere(function($subQuery){
        //         $subQuery->where(function($subQuery1){
        //             $subQuery1->where('ddd', 444);
        //             $subQuery1->orWhere('eee', 555);
        //         });
        //         $subQuery->where(function($subQuery1){
        //             $subQuery1->where('fff', 666);
        //             $subQuery1->orWhere('ggg', 777);
        //             $subQuery1->orWhere(function($subQuery2){
        //                 $subQuery2->where('1', 1);
        //                 $subQuery2->where('2', 1);
        //             });
        //         });
        //     })
        //     ->groupBy('aa.xxx', 'sdf')
        //     ->having('aa.country_name', '中国')
        //     ->orHaving(function($subQuery){
        //         $subQuery->where('aaa', 1111);
        //         $subQuery->where('bbb', 2222);
        //     })
        //     ->orHaving(function($subQuery){
        //         $subQuery->where(function($subQuery1){
        //             $subQuery1->where('ddd', 4444);
        //             $subQuery1->orWhere('eee', 5555);
        //         });
        //         $subQuery->where(function($subQuery1){
        //             $subQuery1->where('fff', 6666);
        //             $subQuery1->orWhere('ggg', 7777);
        //             $subQuery1->orWhere(function($subQuery2){
        //                 $subQuery2->where('1', 11);
        //                 $subQuery2->where('2', 11);
        //             });
        //         });
        //     })
        //     ->orderBy('aaa')
        //     ->orderBy('bbb')
        //     // ->limit(5)
        //     ->page(1, 20)
        //     ->all(),

        // $query->from('account_address', 'aa')
        //     ->where('aa.country_name', '中国')
        //     ->where(function($subQuery){
        //         $subQuery->where(function($subQuery1){
        //             $subQuery1->where('1', '1')->orWhere('11', '11');
        //         })->orWhere(function($subQuery2){
        //             $subQuery2->where('2', '2')->orWhere('22', '22');
        //         });
        //     })
        //     ->debug(),
    );
});


exit;















// 容器类
class Container
{
    protected static array $instances;
    public static function set($key, $value)
    {
        self::$instances[$key] = $value;
    }
    public static function get($key)
    {
        return self::$instances[$key];
    }
}

// 网络请求
function ajax(string $url, string $method = 'get', array $data = [], array $header = [], int $timeout = 2) : mixed
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    if ($method == 'post') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if (!empty($header)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    }
    $res = curl_exec($ch);
    if ($error = curl_errno($ch)) {
        echo curl_error($ch), PHP_EOL;
    }
    curl_close($ch);
    return $res;
}

// 测试方法
function test($data = []) : array
{
    // 最终结果
    $result = [];

    try {
        // 数据库对象
        $db = Container::get('db');
        // 查询
        $result[] = $db->table('account')->all();

        // 开启事务
        $db->beginTransaction();

        // 随机错误
        if (mt_rand(1, 2) === 2) {
            throw new Exception('测试回滚事务！');
        } else {
            ajax('https://www.ruciwan.com/search.php?mod=forum&searchid=771&orderby=lastpost&ascdesc=desc&searchsubmit=yes&kw=%B4%AB%C6%E6%B7%FE%CE%F1%B6%CB');
        }

        // 插入数据
        $data['time'] = microtime(true);
        $bool = $db->table('test')->insert($data);
        if (!$bool) {
            throw new Exception('插入失败了！');
        }

        // 提交事务
        $db->commit();
    } catch (\Throwable $th) {
        // 回滚事务
        $db->rollback();
        // 抛出异常
        throw $th;
    }


    // 返回结果
    return $result;
}

// 一键协程化
\Swoole\Runtime::enableCoroutine($flags = SWOOLE_HOOK_ALL);

// 连接池测试
$http = new \Swoole\Http\Server('0.0.0.0', 9501);
$http->set([
    'worker_num'        =>  2,
    'task_worker_num'   =>  2,
    'task_enable_coroutine' =>  true,
]);
$http->on('workerStart', function($server, $workerId){
    Coroutine::create(function() use($server){
        // 实例化数据库
        Container::set('db', new Manager(require 'config.php', $server->setting['worker_num'] + $server->setting['task_worker_num']));
    });
});
$http->on('task', function(){});
$http->on('request', function($req, $res){
    Coroutine::create(function() use($req, $res){
        // 没有参数
        if (empty($req->get)) {
            // 页面代码
            $html = '<script src="https://cdn.bootcdn.net/ajax/libs/jquery/3.6.0/jquery.min.js"></script>';
            $html .= '<script>';
                $html .= 'var request = function(num){ ';
                    $html .= 'for(let i = 0;i < num;i++) {';
                        $html .= '$.get("http://192.168.2.12:9501/", {content: Date.now() + "_" + i}, function(res){';
                            $html .= 'console.log(i, res);';
                        $html .= '});';
                    $html .= '}';
                $html .= '}';
            $html .= '</script>';
            $html .= '<p><button onClick="request(1)">request 1</button></p>';
            $html .= '<p><button onClick="request(10)">request 10</button></p>';
            $html .= '<p><button onClick="request(50)">request 50</button></p>';
            $html .= '<p><button onClick="request(100)">request 100</button></p>';
            $html .= '<p><button onClick="request(500)">request 500</button></p>';
            $html .= '<p><button onClick="request(1000)">request 1000</button></p>';
            $html .= '<p><button onClick="request(5000)">request 5000</button></p>';
            $html .= '<p><button onClick="request(10000)">request 10000</button></p>';

            // 响应
            $res->end($html);
        } else {
            // 存在参数，表示为页面AJAX请求
            try {
                // 响应数据
                $res->header('Content-Type', 'application/json;charset=utf-8');
                $res->end(json_encode([
                    'code'      =>  200,
                    'message'   =>  '恭喜您、操作成功！',
                    'data'      =>  test($req->get)
                ]));
            } catch (\Throwable $th) {
                // 响应数据
                $res->header('Content-Type', 'application/json;charset=utf-8');
                $res->end(json_encode([
                    'code'      =>  500,
                    'message'   =>  $th->getMessage(),
                    'data'      =>  []
                ]));
            }
        }
    });
});
$http->start();