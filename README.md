## 2021-05-03

### 思路

1. 先读取配置，生成配置列表
2. 循环配置，依次创建数据库连接
3.

假设连接池总数为100
Worker#1    100 / 2  = 50
    Write       50 * 0.2 = 10
    Read        50 - 10  = 40
Worker#2    100 - 50 = 50
    Write       50 * 0.2 = 10
    Read        50 - 10  = 40



## 2020-11-08

### 代理类

如 PDOProxy

连接：connect(连接数据库，支持重连一次)

释放：release(释放连接时调用)

通信：message(操作数据库，支持断线重连)

事务：beginTransaction、commit、rollback、inTransaction

### 查询类

如 MysqlQuery / MssqlQuery / OracleQuery

查询：query(查询多行，返回数组)、first(查询第一行，返回对象)、value(查询第一行的某一个值)

修改：execute、exec






## 概念

### 管理类

对用户来说，只有管理类，通过管理类即可方便的操作数据库

连接：connection（获取、选择、自动回收连接）

调度：__call(用查询类解析用户操作，再通过连接来实现数据库操作，最后返回结果)

### 查询类

如 MysqlQuery / MssqlQuery / OracleQuery

事务：beginTransaction、commit、rollback、inTransaction

查询：query(查询多行，返回数组)、first(查询第一行，返回对象)、value(查询第一行的某一个值)

修改：execute、exec

其他：getLastSql、getLastInsertID，getErrorInfo

### 连接池

获取：get(获取连接)

回收：put(释放连接)

### 代理类

如 PDOProxy

连接：connect(连接数据库，支持重连一次)

释放：release(释放连接时调用)

通信：message(操作数据库，支持断线重连)

## 示例

```php
/**
 * 第一步：实例化数据库管理类
 */
$db = new Database(array $configs, string MysqlQuery::class, string PDOProxy::class);

/**
 * 第二步：在管理类中，实例化集群连接池，实例化查询类
 */
$cluster = new Cluster(array $configs, string PDOProxy::class);
$query = new MysqlQuery();

/**
 * 第三步：在控制器中，测试效果
 */
$result = $db->query('SELECT * FROM `table` WHERE `id` = ?', [1]);

/**
 * 第四步：管理类分配调度
 */
public function __call(string $method, array $arguments)
{
    // 当方法为query且不在事务中时，使用从读


    // 如果查询类中存在这个方法
    if (method_exists($this->query, $method)) {
        // 使用查询类解析
        $context = $this->query->$method(...$arguments);
    }
    // 得到连接
    $conn = $context['master'] ? $this->cluster->master() : $this->cluster->slave();
    // 执行
    $conn->$context['method'](...$context['arguments']);
}
```

## 一

### 思路

管理类 -> 连接池 -> 代理类

管理类直接从连接池获取连接，将用户的方法转发到代理类，代理类负责操作数据库驱动

### 介绍

管理类：负责在操作数据库前自动从连接池里拿到代理类，以及自动回收代理类

连接池：负责发放和回收连接

代理类：负责封装数据库方法和操作数据库，以及断线重连

### 优点

1. 可同时使用不同数据库，只需要在配置文件中设置好服务器所使用的代理类即可

### 缺点

1. 无法自动处理读写分离，需要用户手动选择主从数据库


## 二

### 思想

管理类 -> 查询类 -> 连接池

管理类转发用户的方法到查询类进行第一次处理，再根据情况从连接池获取对应连接进行二次处理。

第一次处理：判断应该使用主或从数据库，封装事务兼容嵌套事务，人性化的数据库操作方法。

第二次处理：根据第一次的处理结果来获取对应连接，从而操作数据库。

### 介绍

管理类：负责在操作数据库时自动调度查询类和连接池

查询类：负责封装数据库方法和操作数据库

连接池：负责发放和回收连接

### 优点

1. 具备自动处理读写分离功能

### 缺点

1. 无法同时使用不同的数据库




流程：

Manager -> Cluster($constructor) -> Mysql -> PDO

Manager: 创建集群，获取连接

Cluster：创建连接池分组，通过构造器创建连接

Mysql：封装驱动方法，断线重连

PDO：操作数据库

示例：

$db = new Manager(array $configs, string|callable $constructor);

$row = $db->first(string $sql, array $arguments = []);

## 二

流程：

Manager -> Cluster -> Mysql -> PDO

Manager: 创建集群，获取连接

Cluster：创建连接池分组，通过构造器创建连接

Mysql：封装驱动方法，断线重连

PDO：操作数据库

示例：

$db = new Manager(array $configs, string|callable $constructor);

$row = $db->first(string $sql, array $arguments = []);

