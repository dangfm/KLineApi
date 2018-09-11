<?php
require_once("inc/config.php");
require_once("inc/redis.php");
require_once("inc/fn.php");

// ini_set("display_errors", "On");


// checkToken();

/*
	行业涨幅列表 
	直接拿redis里面的数据
*/


$redis->getRedis()->select(8); 
$typeCode = get("typeCode");
// 行业涨幅列表 
$key = "Hangye_UpListKey";
if ($typeCode=="diyu") {
	$key = "Diqu_UpListKey";
}
if ($typeCode=="gainian") {
	$key = "Gainian_UpListKey";
}
if ($typeCode=="hangye") {
	$key = "Hangye_UpListKey";
}

$list = $redis->get($key);
if ($list) {
	$list = str_replace('\"', '', $list);
	$list = json_decode($list);
}

showSuccess($list);
?>