<?php
ob_start();
require_once("inc/config.php");
require_once("inc/redis.php");
require_once("inc/fn.php");

// ini_set("display_errors", "On");


// checkToken();

// 把第三个数据库设置为搜索数据库，放索引
$redis->getRedis()->select($config->redis->db->search);
$key = get("key");
if ($key=="") {
	$list = $redis->getRedis()->keys("*");

}else{
	$list = $redis->getRedis()->keys("*".$key."*");
}


showSuccess($list);
// $str = "";
// ob_end_clean();
// $i = 0;
// foreach ($list as $key => $value) {
// 	if ($i>=100) {
// 		break;
// 	}
// 	$value = urlencode($value);
// 	echo $value.PHP_EOL;
// 	$i ++;
// }
//echo $str;
?>


    
    