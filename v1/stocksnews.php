<?php
require_once("inc/config.php");
require_once("inc/redis.php");
require_once("inc/fn.php");

// ini_set("display_errors", "On");


// checkToken();

/**
	股票文字直播接口
*/
$list = $redis->getRedis()->get($config->redis->key->newsLive_sina_globalnews);
// $newList = array();
// for ($i=0;$i<count($list);$i++) {
// 	$key = $list[$i];
// 	$value = $redis->getRedis()->get($key);
// 	$newList[$key] = $value;
// }
// $list = str_replace("[", "", $list);
// $list = str_replace("]", "", $list);
$list = json_decode($list);
showSuccess($list);

//echo $str;
?>


    
    