<?php
header("Content-Type: text/html; charset=utf-8");
// ini_set("display_errors", "On");
// // error_reporting(0);
// error_reporting(E_COMPILE_ERROR & E_RECOVERABLE_ERROR & E_ERROR & E_CORE_ERROR);

########################################################## 常量定义 ###############################################
// API_KEY 用作跟客户端签名认证
define("API_KEY", "ed284ef243ee3c6c02f85875842cf21f");
// 默认的每页数据大小
define("PAGE_SIZE", 240);
// K线历史数据目录
define("CACHEPATH", dirname(dirname(dirname(__FILE__)))."/caches");


########################################################## 出错类型定义 ###############################################
// 变量为空
define("ERROR_EMPTY", 10000);
// 服务器异常
define("ERROR_SERVER", 10001);
// 不支持的股票周期类型
define("ERROR_UNSUPORT", 10002);
// 客户端未注册
define("ERROR_CHECKTOKEN_UNREGISTER", 10003);
// 客户端已过期
define("ERROR_CHECKTOKEN_EXPIRED", 10004);
// 非法IP
define("ERROR_CHECKTOKEN_IPERROR", 10005);
// TOKEN验证不通过
define("ERROR_CHECKTOKEN_ERROR", 10006);

if(!class_exists('config')){class config{public $redis;}}
$config = new config();
// redis 
$config->redis->host = "redis";
$config->redis->port = 6379;
$config->redis->auth = "123456";

$config->redis->db->default = 0;
// redis 股票搜索数据库
$config->redis->db->search = 2;
// redis 涨跌幅数据库
$config->redis->db->updownlist = 8;
// redis 股票数据库
$config->redis->db->stocks = 9;
// redis 状态监控数据库
$config->redis->db->status = 13;
// 行情压缩数据
$config->redis->db->marketzip = 10;
// 分时线所在数据库
$config->redis->db->timeline = 12;


// 涨跌幅键名开始值
$config->redis->key->updownlist = "Stock_UpdownList";
// 行业表
$config->redis->key->hangye_tables = "Hangye_Tables";
// 概念表
$config->redis->key->gainian_tables = "Gainian_Tables";
// 地区表
$config->redis->key->diqu_tables = "Diqu_Tables";
// 客户端列表健名
$config->redis->key->socket_clients_server_ips = "socket_clients_server_ips";
// 服务器列表健名
$config->redis->key->socket_socket_server_ips = "socket_socket_server_ips";
// 直播列表key
$config->redis->key->newsLive_sina_globalnews = "newsLive_sina_globalnews";


?>