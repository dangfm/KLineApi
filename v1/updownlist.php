<?php
require_once("inc/config.php");
require_once("inc/redis.php");
require_once("inc/fn.php");

// ini_set("display_errors", "On");


// checkToken();


// 涨跌幅接口
// {
// 	change,changeRate,name,code,type,price,closePrice
// }

$year = date("Y",time());
$page = get("page");
$pageSize = get("pageSize");
// 兼容旧版本
$start = get("start");
$count = get("count");
if ($start>=0) {
	$page = $start + 1;
}
if ($count>0) {
	$pageSize = $count;
}

$typeCode = get("typeCode");

if ($page<=1) {
	$page = 1;
}
if($pageSize<=0) $pageSize = 20;

$ups = array();					// 涨幅榜
$downs = array();				// 跌幅榜
$turnover = array();			// 换手率榜
$swing = array();				// 振幅榜
$totalValue = array();			// 总市值排行
$circulationValue = array();	// 流通市值排行


// 如果是自定义的实时分类筛选，就是要动态实时的去拿股票数据了
if (!empty($typeCode) && is_numeric($typeCode)) {
	$typeids = "*";
	$typeids = "*,".$typeCode.",*";
	$typeCode = "up";
	// 切换到股票数据库
	$redis->getRedis()->select($config->redis->db->stocks); 
	$allCodes = $redis->getRedis()->keys($typeids);
	// var_dump($allCodes);
	// exit;
	$redis->getRedis()->select($config->redis->db->default); 
	foreach ($allCodes as $rs) {
		$code = substr($rs, 0,8);
		// 拿股票的实时行情
		$value = $redis->get($code);
		//echo $code;
		if (!empty($value)) {
			$value = str_replace('\"', '', $value);
			$obj = json_decode($value);
			$numCode = substr($code, 2);
			//
			$price = $obj->{"price"};
			$closePrice = $obj->{"closePrice"};
			$openPrice = $obj->{"openPrice"};
			$turnoverRate = $obj->{"turnoverRate"};
			$swingValue = $obj->{"swing"};
			$totalValues = $obj->{"totalValue"};
			$circulationValues = $obj->{"circulationValue"};
			$isStop = $obj->{"isStop"};
			$type = $obj->{"type"};
			if ($isStop>0 || $openPrice<=0 || $closePrice<=0 || $type>0) {
				
				//echo $code."哈哈继续\n";
				continue;
			}
			// 去掉B股
			if (strpos($numCode,"90") === 0 || strpos($numCode,"20") === 0) {
				continue;
			}
			// change
			$change = $price - $closePrice;
			$changeRate = $change/$closePrice;
			$changeRate = round($changeRate*100,2);
			$change = round($change,2);
			$obj->{'change'} = $change;
			$obj->{'changeRate'} = $changeRate;
			if ($v>=2) {
					//echo $price." - ".$closePrice." = ".$change.'</br>';
				}
			if (abs($change)>=0) {
				$ups[$code] = $changeRate;
			}
			else if (!empty($typeids)) {
				//$ups[$code] = -100;
			}
			
			if ($turnoverRate>0) {
				$turnover[$code] = $turnoverRate;
			}
			if (abs($swingValue)>0) {
				$swing[$code] = $swingValue;
			}
			$totalValue[$code] = $totalValues;
			$circulationValue[$code] = $circulationValues;

		}
	}
	// 生序排序
	$downs = $ups;
	// 降序排序
	asort($downs);
	arsort($ups);
	arsort($turnover);
	arsort($swing);
	arsort($totalValue);
	arsort($circulationValue);
	// var_dump($ups);
}else{
	// 拿redis缓存的数据
	// 切换涨跌幅数据库
	$redis->getRedis()->select($config->redis->db->updownlist); 
	$ups = $redis->get($config->redis->key->updownlist);
	$turnover = $redis->get($config->redis->key->updownlist."_turnoverRate");
	$swing = $redis->get($config->redis->key->updownlist."_swing");
	$totalValue = $redis->get($config->redis->key->updownlist."_totalValue");
	$circulationValue = $redis->get($config->redis->key->updownlist."_circulationValue");

	$ups = json_decode($ups,true);
	$turnover = json_decode($turnover,true);
	$swing = json_decode($swing,true);
	$totalValue = json_decode($totalValue,true);
	$circulationValue = json_decode($circulationValue,true);
	// 生序排序
	$downs = $ups;
	// 降序排序
	asort($downs);
}

// 涨幅
if($typeCode==""){
	$ups = getTopValues($ups);
	$downs = getTopValues($downs);
	$turnover = getTopValues($turnover);
	$swing = getTopValues($swing);
	$totalValue = getTopValues($totalValue);
	$circulationValue = getTopValues($circulationValue);

	if (count($ups)<10) {
		showError("暂无数据");
	}
	

	

	$list = array(
		array(
			"name"=>"涨幅榜",
			"typeCode"=>"up",
			"data"=>$ups
		),
		array(
			"name"=>"跌幅榜",
			"typeCode"=>"down",
			"data"=>$downs
		),
		array(
			"name"=>"换手率榜",
			"typeCode"=>"turnover",
			"data"=>$turnover
		),
		array(
			"name"=>"振幅榜",
			"typeCode"=>"swing",
			"data"=>$swing
		),
		array(
			"name"=>"市值排行",
			"typeCode"=>"totalValue",
			"data"=>$totalValue
		),
		array(
			"name"=>"流通市值排行",
			"typeCode"=>"circulationValue",
			"data"=>$circulationValue
		),
		);

	
		// 切换到涨跌幅数据库
		$redis->getRedis()->select($config->redis->db->updownlist);
		// 行业涨幅列表 
		$key = "Hangye_UpListKey";
		$hylist = $redis->get($key);
		$hylist = str_replace('\"', '', $hylist);
		$hylist = json_decode($hylist);
		$hylist = array_slice($hylist, 0,6);

		$key = "Gainian_UpListKey";
		$gnlist = $redis->get($key);
		$gnlist = str_replace('\"', '', $gnlist);
		$gnlist = json_decode($gnlist);
		$gnlist = array_slice($gnlist, 0,6);

		$key = "Diqu_UpListKey";
		$dylist = $redis->get($key);
		$dylist = str_replace('\"', '', $dylist);
		$dylist = json_decode($dylist);
		$dylist = array_slice($dylist, 0,6);

		array_unshift($list, array(
			"name"=>"地域涨幅榜",
			"typeCode"=>"diyu",
			"data"=>$dylist
		));

		array_unshift($list, array(
			"name"=>"概念涨幅榜",
			"typeCode"=>"gainian",
			"data"=>$gnlist
		));

		array_unshift($list, array(
			"name"=>"行业涨幅榜",
			"typeCode"=>"trade",
			"data"=>$hylist
		));

	showSuccess($list);
}else{
	if($typeCode=="up"){
		$list = getTopValues($ups,$page,$pageSize);
		
	}
		
	if($typeCode=="down"){
		$list = getTopValues($downs,$page,$pageSize);
	}
		
	if($typeCode=="turnover"){
		$list = getTopValues($turnover,$page,$pageSize);
	
	}
		
	if($typeCode=="swing"){
		$list = getTopValues($swing,$page,$pageSize);
	
	}
	
	if($typeCode=="totalValue"){
		$list = getTopValues($totalValue,$page,$pageSize);
	}
	
	if($typeCode=="circulationValue"){
		$list = getTopValues($circulationValue,$page,$pageSize);
	}
		
	//$list = eval('return '.iconv('gbk','utf-8',var_export($list,true).';'));
	showSuccess($list);
}

?>


    
    