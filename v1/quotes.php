<?php
require_once("inc/config.php");
require_once("inc/redis.php");
require_once("inc/fn.php");

// ini_set("display_errors", "On");


// checkToken();

$redis->getRedis()->select($config->redis->db->default);
// 获取股票代码
$code = get("code");

if (empty($code)) {
	showError("股票代码不能为空");
}

// 获取数据库
$list = explode(",", $code);
$newCode = "";
$newResult = array();

if (count($list)>0) {
	foreach ($list as $value) {
		//echo $value;
		$jsonstr = $redis->get($value);
		if ($jsonstr!="") {
			$jsonstr = str_replace('\"', '', $jsonstr);
			$jsonstr = json_decode($jsonstr);
			array_push($newResult, $jsonstr);
		}
		
	}
}else{
	$jsonstr = $redis->get($code);
		if ($jsonstr!="") {
			$jsonstr = str_replace('\"', '', $jsonstr);
			$jsonstr = json_decode($jsonstr);
			array_push($newResult, $jsonstr);
		}
}

showSuccess($newResult);
?>