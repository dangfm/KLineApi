<?php

function get($param){
	$value = $_GET[$param];
	$value = str_replace("'","",$value);
	if($value=="") $value = post($param);
	return $value;
}

function post($param){
	$value = $_POST[$param];
	$value = str_replace("'","",$value);
	$codevalue = $value;
	return $value;
}
	

//checkToken();

function checkToken(){
	$params = array_merge($_GET,$_POST);
	$t = $params["t"];
	$token = $params["token"];
	$hashStr = "";
	foreach ($params as $key => $value) {
		if ($key!="t" && $key!="token") {
			$hashStr = $hashStr . $value;
		}
	}
	$auth = md5($hashStr.$t.API_KEY);
	if ($sourceId<=0) {
		if($auth!=$token){
			showError("服务器验证失败",ERROR_CHECKTOKEN_ERROR);
		}
	}

}

function showPageList($lines,$page,$pageSize)
{
	// var_dump($lines);
	// 计算总页码 
	$pageCount = ceil(count($lines)/$pageSize);
	if ($pageCount<1) {
		$pageCount = 1;
	}
	// echo "pageCount:".$pageCount." count:".count($lines);
	if ($page>$pageCount) {
		return;
	}

	$start = count($lines) - $page * $pageSize;
	if($start<=0 && $page>1){
		return;
	}
	if($start<=0 && $page==1){
		$start = 0;
		$pageSize = count($lines);
	}
	if($start<$pageSize){
		$pageSize = $start;
		$start = 0;
	}

	$end = $start + $pageSize;
	if ($end>=count($lines)) {
		$pageSize = count($lines) - $start;
	}
	if ($start<=0) {
		$pageSize = abs($start);
		if ($start==0) {
			$pageSize = count($lines);
		}
		$start = 0;
	}
	// echo $start."=".$pageSize;
	$lines = array_slice($lines,$start,$pageSize);
	showSuccess($lines);
	// foreach ($lines as $value) {
	// 	echo $value.PHP_EOL;
	// }
}

function getIP()
	{
		$ip = "";
		if (getenv("HTTP_CLIENT_IP"))
			$ip = getenv("HTTP_CLIENT_IP");
		else if(getenv("HTTP_X_FORWARDED_FOR"))
			$ip = getenv("HTTP_X_FORWARDED_FOR");
		else if(getenv("REMOTE_ADDR"))
			$ip = getenv("REMOTE_ADDR");
		else $ip = "Unknow";
		return $ip;
	}

function readDayLine($code,$fq){
	if (empty($fq)) {
		$fq = "data";
	}
	$fileName = CACHEPATH."/day/".$fq."/".$code.".txt";
	// echo $fileName;
	$contents = file_get_contents($fileName);

    $contents = json_decode($contents,true);
    $contents = returnDayDatas($contents,$code);
	return $contents;
}

function readMinuteLine($code,$fq){
	if (empty($fq)) {
		$fq = "data";
	}
	$fileName = CACHEPATH."/min1/".$fq."/".$code.".txt";
	$contents = file_get_contents($fileName);
	// echo $contents;
    $contents = json_decode($contents,true);
    $contents = formatMinuteDatas($contents,$code);

	return $contents;
}

function addRedisMinuteline($minutedata,$code){
	$contents = array();
	$redisLine = readRedisMinLine($code);
    // 查找最新日期
    if (count($minutedata)>0 && count($redisLine)>0) {
    	$last = $minutedata[count($minutedata)-1];
    	$lastDate = explode(",",$last)[0];

    	$redislast = $redisLine[count($redisLine)-1];
    	$redislastDate = explode(",",$redislast)[0];
    	if ($lastDate!=$redislastDate) {
			// 拼接到最后
    		$contents = $redisLine ;// array_merge($contents,$redisLine);
    	}
	}
	return $contents;
}

function readRedisMinLine($code){
	global $config,$redis;
	$code = strtolower($code);
	
	$redis->getRedis()->select(0);
	$lastDate = date("Ymd");
	$lastTime = date("Hi");
	$jsonstr = $redis->get($code);
	$open = 0;
	$high = 0;
	$low = 0;
	$close = 0;
	$volumn = 0;
	$volPrice = 0;
	if ($jsonstr!="") {
		$jsonstr = str_replace('\"', '', $jsonstr);
		$jsonstr = json_decode($jsonstr,true);
		$lastDate = $jsonstr["lastDate"];
		$lastDate = str_replace('-','',$lastDate);
		$open = $jsonstr["openPrice"];
		$high = $jsonstr["highPrice"];
		$low = $jsonstr["lowPrice"];
		$close = $jsonstr["closePrice"];
		$lastTime = $jsonstr["lastTime"];
		$lastTime = str_replace(':','',$lastTime);
		$lastTime = mb_substr($lastTime,0,4);

	}		
	$redis->getRedis()->select($config->redis->db->timeline);
	$realtimelist = $redis->getRedis()->hgetall($code."_today");
	$stocks = array();
	$lastRow = array($lastDate,"0930",$open,$high,$low,$open,0,0);
	// var_dump($realtimelist);
	if(count($realtimelist)>0){
		ksort($realtimelist);
		// var_dump($realtimelist);
		$lastV=0;
		$i = 0;
		$startTime = strtotime(date("Y-m-d 9:30:00"));
		// 上午120分钟的数据
		for ($i=0; $i < 242; $i++) { 
			$filed = date("Hi",$startTime);
			// echo $filed."\r\n";
			$linestr = $redis->getRedis()->hget($code."_today",$filed);;
			
			if (strpos($linestr,",")!==false && $linestr!="" && !empty($linestr)){
				
				$row = explode(",",$linestr);
				if(count($row)>=8){
					
					if(strpos($code,"sh000")!==false || strpos($code,"sz399")!==false){
						// $vol = $vol/100;
						// $volPrice = $volPrice/10;
					}else{
						// $vol = $vol/100;
						// $volPrice = $volPrice/100;
						// if(count($row)>=9){
						// 	$lastV = $row[8];
						// }
					}
					

					$vol = ($row[6]-$lastV);
					if ($vol<=0) {
						$vol = 0;
					}
					$volPrice = $row[7];
					
					$newRow = array(
					$row[0],
					$row[1],
					$row[2],
					$row[3],
					$row[4],
					$row[5],
					$vol,
					$volPrice
					);
					
					
					$linestr = implode(",",$newRow);

					if($lastDate==$row[0]){
						if ($row[6]>0) {
							$lastV=$row[6];
						}
						// echo $lastDate."=".$row[0];
						array_push($stocks,$linestr);
						$lastRow = $newRow;
					}else{
						if ($filed<=$lastTime) {
							$newRow = $lastRow;
							$newRow[1]=$filed;
							$newRow[6]=0;
							$linestr = implode(",",$newRow);
							array_push($stocks,$linestr);
							$lastRow = $newRow;
						}
						
					}

				}
			}else{
				// echo $linestr."=".count($lastRow)."</br>";
				if (count($lastRow)>0) {
					if ($filed<=$lastTime) {
						$newRow = array(
							$lastRow[0],
							$filed,
							$lastRow[2],
							$lastRow[3],
							$lastRow[4],
							$lastRow[5],
							$lastRow[6],
							$lastRow[7]
						);
						$linestr = implode(",",$lastRow);
						array_push($stocks,$linestr);
						$lastRow = $newRow;
						// var_dump($lastRow);
					}else{
						// echo $filed."=".$lastTime."</br>";
					}
				}else{
					// var_dump($lastRow);
					// $linestr = ",,,,,,,,";
					// array_push($stocks,$linestr);
				}
				
				// $lastRow = $linestr;
			}
			// echo $linestr."</br>";

			if ($i==120) {
				$startTime += 90*60;
			}else{
				$startTime += 60;
			}
			
		}

		// var_dump($stocks);
		
	}
	return $stocks;
}

function readFiveMinuteLine($code,$fq){
	if (empty($fq)) {
		$fq = "data";
	}
	$fileName = CACHEPATH."/min5/".$fq."/".$code.".txt";
	$contents = file_get_contents($fileName);
    $contents = json_decode($contents,true);
	return $contents;
}

function readMarketDate(){
	global $redis;
	$code = "sh000001";
	$json = $redis->get($code);
	// $json = str_replace("\\", "", $json);
	$jsonObj = json_decode($json);
	// var_dump($jsonObj);
	$lastDate = $jsonObj->{"lastDate"};
	$lastDate = str_replace("-", "", $lastDate);
	return $lastDate;
}

/*
	写入硬盘缓存
*/
function setCache($fileName,$content){
	if (!is_dir(CACHEPATH)) mkdir(CACHEPATH,0777); // 如果不存在则创建
	$fileName = CACHEPATH.$fileName.".txt";
	// 读取文件，不存在就创建
	//$file = fopen($fileName, 'a+');
	//var_dump($file);
	// echo $fileName;
	// 写入缓存
	$content = json_encode($content);
	// echo $content;
	$success = file_put_contents($fileName,$content);
	//fclose($file);
	//var_dump($success);
}
function getCache($fileName,$timeout){
	

	// echo $fileName;
	if (!is_dir(CACHEPATH)) mkdir(CACHEPATH,0777); // 如果不存在则创建
	$fileName = CACHEPATH.$fileName.".txt";
	$fileTime = strtotime(date("Y-m-d H:i:s.",filemtime($fileName)));
	$nowTime = strtotime(date("Y-m-d H:i:s.",time()));
	// echo "cache=".date("F d Y H:i:s.",time())."====".$fileTime;
	//echo "Last modified: ".date("F d Y H:i:s.",filemtime($fileName));
	if (($nowTime-$fileTime)>$timeout) {
		// 超时
		return "";
	}
	
	// 读取文件，不存在就创建
	//$file = fopen($fileName, 'r');
	//通过filesize获得文件大小，将整个文件一下子读到一个字符串中
    //$contents = fread($handle, filesize($fileName));
    $contents = file_get_contents($fileName);
    $contents = json_decode($contents);
	return $contents;
    //echo "cache=".$contents;
    //echo "getCache";
    //fclose($handle);
  
}

// 把股票代码写入缓存
function setCodes($zm,$code){
	$array = getCodes($zm);
	// 如果存在缓存
	if (!array_search($code, $array) || !$array) {
		$array[$code] = $array[$code] + 1;
	}
	$zm->cache->set(CODEKEYNAME,$array,3600*24);
}

function getCodes($zm){
	return $zm->cache->get(CODEKEYNAME);
}

/*
	打印API
*/
function showAPI($str,$codenum=0,$success=false,$data=null){
	$encode = mb_detect_encoding($str, array("ASCII","UTF-8","GB2312","GBK","BIG5"));
	if ($encode != "UTF-8"){
		$str = iconv($encode,"UTF-8",$str);
	}
	$data = array(
		"error"=>$codenum,
		"msg"=>$str,//iconv("GB2312//IGNORE","UTF-8",  $str),
		"success"=>$success,
		"data"=>$data
	);
	echo json_encode($data);
	exit;
}

function showError($str,$codenum=0){
	showAPI($str,$codenum);
}
function showSuccess($data,$msg=''){
	showAPI($msg,0,true,$data);
}

/*
	请求网络接口
*/
function getUrl($url,$cookie=false){
	//$url = "http://hq.sinajs.cn/list=".$stock_code."";
	//$contents = file_get_contents($url);
	$UserAgent = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; SLCC1; .NET CLR 2.0.50727; .NET CLR 3.0.04506; .NET CLR 3.5.21022; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_HEADER, 0);  //0表示不输出Header，1表示输出
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($curl, CURLOPT_ENCODING, '');
	curl_setopt($curl, CURLOPT_USERAGENT, $UserAgent);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
	$contents = curl_exec($curl);
	//echo $contents;
	return $contents;
}


function returnDayDatas($daysdatas,$code){
	$newa = array();
	$code = strtolower($code);
	$i = 0;
	foreach ($daysdatas as $rs) {
	
		if (strpos($rs,",")===false) {
			$i ++;
			continue;
		}
		$rs = explode(",", $rs);
		// 判断当前是星期几
		$datetime = $rs[0];
		$vol = $rs[5];
		$volPrice = $rs[6];
		if(strpos($code,"sh000")!==false || strpos($code,"sz399")!==false){
			$vol = $vol*100;
			// $volPrice = $volPrice/1000;
		}
		$line = array(
				$rs[0],
				$rs[1],
				$rs[2],
				$rs[3],
				$rs[4],
				$vol,
				$volPrice
				);
			$linestr = implode(",",$line);
			// 添加进数组
			array_push($newa, $linestr);
		
		$i++;
	}

	return $newa;
}

function formatMinuteDatas($datas,$code){
	$newa = array();
	$code = strtolower($code);
	$i = 0;
	foreach ($datas as $rs) {
		
		if (empty($rs)) {
			continue;
		}
		if (strpos($rs,",")===false) {
			continue;
		}
		$rs = explode(",", $rs);
		// var_dump($rs);
		$vol = $rs[6];
		$volPrice = $rs[7];
		if(strpos($code,"sh000")!==false || strpos($code,"sz399")!==false){
			$vol = $vol*1000;
			// $volPrice = $volPrice/1000;
		}
		$line = array(
				$rs[0],
				$rs[1],
				$rs[2],
				$rs[3],
				$rs[4],
				$rs[5],
				$vol,
				$volPrice
				);
			$linestr = implode(",",$line);
			// 添加进数组
			array_push($newa, $linestr);
		
		$i++;
	}

	return $newa;
}

function returnWeekDatas($daysdatas,$code){
	$code = strtolower($code);
	$weekarray=array("7","1","2","3","4","5","6");
	$newa = array();
	$line = array();
	$lastWeek = 0;
	$weekHeightPrice = 0;
	$weekLowPrice = 100000000;
	$weekVolumn = 0;
	$weekVolumnPrice = 0;
	$weekClosePrice = 0;
	$weekOpenPrice = 0;
	$weekStartDate = '';
	$weekYestodayClosePrice = 0;
	$i = 0;
	foreach ($daysdatas as $rs) {
	
		if (strpos($rs,",")===false) {
			$i ++;
			continue;
		}
		$rs = explode(",", $rs);
		// 判断当前是星期几
		$datetime = $rs[0];
		// echo $datetime;
		//$datetime = str_replace(substr($datetime, 0,4), substr($datetime, 0,4)."-", $datetime);
		//$datetime = str_replace(substr($datetime, 5,2), substr($datetime, 5,2)."-", $datetime);
		$datetime = substr($datetime, 0,4)."-".substr($datetime, 4,2)."-".substr($datetime, 6,2);
		$datearr = explode("-",$datetime);     //将传来的时间使用“-”分割成数组
		$year = $datearr[0];       //获取年份
		$month = sprintf('%02d',$datearr[1]);  //获取月份
		$day = sprintf('%02d',$datearr[2]);      //获取日期
		$hour = $minute = $second = 0;   //默认时分秒均为0
		$dayofweek = mktime($hour,$minute,$second,$month,$day,$year);    //将时间转换成时间戳
		// 拿到星期
		$week = $weekarray[date("w",$dayofweek)];
		// echo $datetime.'=week='.$week."</br>";
		// 如果是星期五
		if ($week<$lastWeek || $i==count($daysdatas)-1) {
			if (strpos($code, "sh000")!==false || strpos($code, "sz399")!==false) {
				$weekVolumn = $weekVolumn*100;
				// $weekVolumnPrice = $weekVolumnPrice/1000;
			}else{
				// $weekVolumn = $weekVolumn/100;
			}
			// 证明本星期最后一天
			$line = array(
				$weekStartDate,
				$weekOpenPrice,
				$weekHeightPrice,
				$weekLowPrice,
				$weekClosePrice,
				$weekVolumn,
				$weekVolumnPrice
				);
			$linestr = implode(",",$line);
			// 添加进数组
			array_push($newa, $linestr);
			// 初始化
			$weekHeightPrice = 0;
			$weekLowPrice = 100000000;
			$weekVolumn = 0;
			$weekVolumnPrice = 0;
			$weekClosePrice = 0;
			$weekOpenPrice = 0;
			$weekYestodayClosePrice = 0;
			$weekStartDate = "";
		}
		$lastWeek = $week;
		if ($rs[2]>$weekHeightPrice) {
			$weekHeightPrice = $rs[2];
		}
		if ($rs[3]<$weekLowPrice){
			$weekLowPrice = $rs[3];
		}
		$weekVolumn += $rs[5];
		$weekVolumnPrice += $rs[6];
		$weekOpenPrice = $rs[1];
		if ($weekClosePrice<=0) {
			$weekClosePrice = $rs[4];
			$weekStartDate = $rs[0];
		}
		
		$weekYestodayClosePrice = $rs[4];
		$i++;
	}

	return $newa;
}

/*
	dateTime = 0;
    yestodayClosePrice = 1;
    openPrice = 2;
    highPrice = 3;
    lowPrice = 4;
    closePrice = 5;
    volumn = 6 + 0000;
    volumnPrice = 7;
*/
function returnMonthDatas($daysdatas,$code){
	$code = strtolower($code);
	$newa = array();
	$lastMonth = 12;
	$lastDay = 1;
	$monthHeightPrice = 0;
	$monthLowPrice = 100000000;
	$monthVolumn = 0;
	$monthVolumnPrice = 0;
	$monthClosePrice = 0;
	$monthOpenPrice = 0;
	$monthStartDate = '';
	$monthYestodayClosePrice = 0;
	$i = 0;
	// 从最新日期倒序遍历
	foreach ($daysdatas as $rs) {
		if (strpos($rs,",")===false) {
			$i++;
			continue;
		}
		$rs = explode(",", $rs);
		// 日期
		$datetime = $rs[0];
		//$datetime = str_replace(substr($datetime, 0,4), substr($datetime, 0,4)."-", $datetime);
		//$datetime = str_replace(substr($datetime, 5,2), substr($datetime, 5,2)."-", $datetime);
		$datetime = substr($datetime, 0,4)."-".substr($datetime, 4,2)."-".substr($datetime, 6,2);
		$datearr = explode("-",$datetime);     //将传来的时间使用“-”分割成数组
		$year = $datearr[0];       //获取年份
		$month = sprintf('%02d',$datearr[1]);  //获取月份
		$day = sprintf('%02d',$datearr[2]);      //获取日期
		$hour = $minute = $second = 0;   //默认时分秒均为0
		$dayofweek = mktime($hour,$minute,$second,$month,$day,$year);    //将时间转换成时间戳
		// 拿到月份
		//$month = $weekarray[date("m",$dayofweek)];
		// 日期天数
		$nowDay = date("d",$dayofweek);
		$nowDay = $day;
		//echo $nowDay."</br>";
		// 天数不断变小，如果大于上一天数表明遇到了上个月的最后一天
		if ($nowDay<$lastDay  || $i==count($daysdatas)-1) {
			if (strpos($code, "sh000")!==false || strpos($code, "sz399")!==false) {
				$monthVolumn = $monthVolumn*100;
			}else{
			
			}
			// 证明本月最后一天
			$line = array(
				$monthStartDate,
				$monthOpenPrice,
				$monthHeightPrice,
				$monthLowPrice,
				$monthClosePrice,
				$monthVolumn,
				$monthVolumnPrice
				);
			$linestr = implode(",",$line);
			// 添加进数组
			array_push($newa, $linestr);
	
			// 初始化
			$monthHeightPrice = 0;
			$monthLowPrice = 100000000;
			$monthVolumn = 0;
			$monthVolumnPrice = 0;
			$monthClosePrice = 0;
			$monthOpenPrice = 0;
			$monthStartDate = '';
			$monthYestodayClosePrice = 0;
		}
		//$lastMonth = $month;
			// 保存当前月K开盘价，最高价，最低价，收盘时间
			$lastDay = $nowDay;
			if ($rs[2]>$monthHeightPrice) {
				$monthHeightPrice = $rs[2];
			}
			if ($rs[3]<$monthLowPrice){
				$monthLowPrice = $rs[3];
			}
			// 成交量成交额要累加起来
			$monthVolumn += $rs[5];
			$monthVolumnPrice += $rs[6];
			// 开盘价就是当月最开始那天的价格
			$monthOpenPrice = $rs[1];
			if ($monthClosePrice<=0) {
				$monthClosePrice = $rs[4];
				$monthStartDate = $rs[0];
			}
			
			$monthYestodayClosePrice = $rs[4];
			$i++;
		
	}

	return $newa;
}

function returnMinuteData($datas,$minute){
		$mHeightPrice = 0;
		$mLowPrice = 100000000;
		$mVolumn = 0;
		$mVolumnPrice = 0;
		$mClosePrice = 0;
		$mOpenPrice = 0;
		$mStartDate = '';
		$i = 1;
		// $datas = implode(, $datas);
		// var_dump($datas);
		$newLines = array();
		foreach ($datas as $row) {
			$rs = $row;
			if (empty($rs)) {
				continue;
			}
			if (strpos($rs,",")===false) {
				continue;
			}
			$rs = explode(",", $rs);
			$datetime = $rs[0];
			$mintime = $rs[1];
			$min = substr($mintime, 2,2);
			$shidian = $mintime;

			// echo $datetime."==".$mintime."==".$min."\r\n";

			if (($min%$minute==0 && $i>1) || ($shidian==1030 && $minute==60)|| ($shidian==1130 && $minute==60)) {
				if ($shidian==1000 && $minute==60) {
					# code...
				}else if ($shidian==1100 && $minute==60) {
					# code...
				}else{
					$line = array(
						$datetime,
						$mintime,
						$mOpenPrice,
						$mHeightPrice,
						$mLowPrice,
						$mClosePrice,
						$mVolumn,
						$mVolumnPrice,
					);
					$line = implode(",",$line);
					// echo $line;
					// $newLines = $newLines.$line;
					array_push($newLines, $line);
	
					$mHeightPrice = 0;
					$mLowPrice = 100000000;
					$mVolumn = 0;
					$mVolumnPrice = 0;
					$mClosePrice = 0;
					$mOpenPrice = 0;
					$mYestodayClosePrice = 0;
				}
				
			}
			if ($rs[3]>$mHeightPrice) {
				$mHeightPrice = $rs[3];
			}
			if ($rs[4]<$mLowPrice){
				$mLowPrice = $rs[4];
			}
			$mVolumn += $rs[6];
			$mVolumnPrice += $rs[7];
			$mClosePrice = $rs[5];
			if ($mOpenPrice<=0) {
				$mOpenPrice = $rs[2];
			}
			
			
			$i++;
		}
		// $newLines = explode("\r\n", $newLines);
		return $newLines;
}

function returnTimeLineData($datas,$day){
		$mHeightPrice = 0;
		$mLowPrice = 100000000;
		$mVolumn = 0;
		$mVolumnPrice = 0;
		$mClosePrice = 0;
		$mOpenPrice = 0;
		$mStartDate = '';
		$j = 0;
		// $datas = implode(, $datas);
		// var_dump($datas);
		$newLines = array();
		$count = $day * 241;
		
		// echo count($datas);
		for ($i=count($datas)-1;$i>=0;$i--) {
			$rs = $datas[$i];
			if ($j>=$count) {
				break;
			}
			if (empty($rs)) {
				continue;
			}
			if (strpos($rs,",")===false) {
				continue;
			}
			// echo $i."=".$rs."</br>";
			$rs = explode(",", $rs);
			$datetime = $rs[0];
			$mintime = $rs[1];
			$mOpenPrice = $rs[2];
			$mHeightPrice = $rs[3];
			$mLowPrice = $rs[4];
			$mClosePrice = $rs[5];
			$mVolumn = $rs[6];
			$mVolumnPrice = $rs[7];

			$line = array(
						$datetime,
						$mintime,
						$mClosePrice,
						$mVolumn,
						$mVolumnPrice,
					);
			$line = implode(",",$line);
					// echo $line;
					// $newLines = $newLines.$line;
			array_push($newLines, $line);
			
			
			$j++;
		}
		// $newLines = explode("\r\n", $newLines);
		asort($newLines);
		// var_dump($newLines);
		return $newLines;
}



//php获取中文字符拼音首字母
function getFirstCharter($str){
    if(empty($str)){return '';}
    $fchar=ord($str{0});
    if($fchar>=ord('A')&&$fchar<=ord('z')) return strtoupper($str{0});
    $s1=iconv('UTF-8','gb2312',$str);
    $s2=iconv('gb2312','UTF-8',$s1);
    $s=$s2==$str?$s1:$str;
    $asc=ord($s{0})*256+ord($s{1})-65536;
    if($asc>=-20319&&$asc<=-20284) return 'A';
    if($asc>=-20283&&$asc<=-19776) return 'B';
    if($asc>=-19775&&$asc<=-19219) return 'C';
    if($asc>=-19218&&$asc<=-18711) return 'D';
    if($asc>=-18710&&$asc<=-18527) return 'E';
    if($asc>=-18526&&$asc<=-18240) return 'F';
    if($asc>=-18239&&$asc<=-17923) return 'G';
    if($asc>=-17922&&$asc<=-17418) return 'H';
    if($asc>=-17417&&$asc<=-16475) return 'J';
    if($asc>=-16474&&$asc<=-16213) return 'K';
    if($asc>=-16212&&$asc<=-15641) return 'L';
    if($asc>=-15640&&$asc<=-15166) return 'M';
    if($asc>=-15165&&$asc<=-14923) return 'N';
    if($asc>=-14922&&$asc<=-14915) return 'O';
    if($asc>=-14914&&$asc<=-14631) return 'P';
    if($asc>=-14630&&$asc<=-14150) return 'Q';
    if($asc>=-14149&&$asc<=-14091) return 'R';
    if($asc>=-14090&&$asc<=-13319) return 'S';
    if($asc>=-13318&&$asc<=-12839) return 'T';
    if($asc>=-12838&&$asc<=-12557) return 'W';
    if($asc>=-12556&&$asc<=-11848) return 'X';
    if($asc>=-11847&&$asc<=-11056) return 'Y';
    if($asc>=-11055&&$asc<=-10247) return 'Z';
    return $str;
}
 
 
function fmPinyin($zh){
    $ret = "";
    $s1 = iconv("UTF-8","gb2312", $zh);
    $s2 = iconv("gb2312","UTF-8", $s1);
    if($s2 == $zh){$zh = $s1;}
    for($i = 0; $i < strlen($zh); $i++){
        $s1 = substr($zh,$i,1);
        $p = ord($s1);
        if($p > 160){
            $s2 = substr($zh,$i++,2);
            $ret .= getFirstCharter($s2);
        }else{
            $ret .= $s1;
        }
    }
    return $ret;
}



// 循环创建目录
function mk_dir($dir, $mode = 0777)
{
if (is_dir($dir) || @mkdir($dir,$mode)) return true;
if (!mk_dir(dirname($dir),$mode)) return false;
return @mkdir($dir,$mode);
}

function getTopValues($lines,$page=1,$pageSize=10){
	Global $redis;
	// 计算总页码 
	$pageCount = ceil(count($lines)/$pageSize);
	// echo "pageCount:".$pageCount." count:".count($lines);
	if ($page>$pageCount) {
		return;
	}

	$start = ($page-1) * $pageSize;

	$end = $start + $pageSize;
	if ($end>=count($lines)) {
		$pageSize = count($lines) - $start;
	}
	
	// echo $start."=".$pageSize;
	$lines = array_slice($lines,$start,$pageSize);

	$newArray = array();
	$i = 0;
	// var_dump($array);
	foreach ($lines as $key => $value) {
		//echo $key;
		$redis->getRedis()->select($config->redis->db->default);
		$jsonStr = $redis->get($key);
		$jsonStr = str_replace('\"', '', $jsonStr);
		// var_dump($key);
		$obj = json_decode($jsonStr,true);
		$newObj = array(
			"code"=>$obj["code"],
			"name"=>$obj["name"],
			"price"=>$obj["price"],
			"changeRate"=>round($value,2)
			);
		$obj['changeRate'] = round($value,2);
		$isStop = $obj["isStop"];
		if ($isStop<=0) {
			array_push($newArray, $newObj);
			$i++;
		}
		
	}
	return $newArray;
}



?>