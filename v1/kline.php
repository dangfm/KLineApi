<?php
require_once("inc/config.php");
require_once("inc/redis.php");
require_once("inc/fn.php");

// ini_set("display_errors", "On");


// checkToken();


/*
	获取K线历史数据
	$cycle k线类型 day=日线 week=周线 month=月线 min1=1分钟 min5=5分钟 min15=15分钟 min30=30分钟 min60=1小时  timeline5=五日分时 timeline=分时
	$fq    复权类型 默认原始数据 before=前复权 after=后复权
*/

$cycleList = array("day","week","month","min1","min5","min15","min30","min60","timeline5","timeline");
$fqlist = array("data","before","after");

// 周期
$cycle = get("cycle");
if (empty($cycle)) {
	showError("股票周期类型不能为空",ERROR_EMPTY);
}
if (!in_array($cycle, $cycleList)) {
	showError("不支持的股票周期类型",ERROR_UNSUPORT);
}

// 复权
$fq = get("fq");
if (!in_array($fq, $fqlist)) {
	showError("不支持的股票复权类型",ERROR_UNSUPORT);
}

// 股票代码 例如：sh600000
$code = get("code");
if (empty($code)) {
	showError("股票代码不能为空",ERROR_EMPTY);
}

// 分页
$page = get("page");
if ($page<=1) {
	$page = 1;
}
// 每页大小 默认200
$pageSize = get("pageSize");
if ($pageSize<=0) {
	$pageSize = PAGE_SIZE;
}

// 通过redis行情数据库拿到当前实时行情的日期
$marketDate = readMarketDate();

/*
	请求日K线
*/
if ($cycle == "day") {
	$code = strtoupper($code);
	// 读取日线数据
	$lines = readDayLine($code,$fq);
	showPageList($lines,$page,$pageSize);
	exit;
}

/*
	请求周K线
*/
if ($cycle == "week") {
	$code = strtoupper($code);
	// 读取日线数据
	$lines = readDayLine($code,$fq);
	$lines = returnWeekDatas($lines,$code);
	showPageList($lines,$page,$pageSize);
	exit;
}
/*
	请求月K线
*/
if ($cycle == "month") {
	$code = strtoupper($code);
	// 读取日线数据
	$lines = readDayLine($code,$fq);
	$lines = returnMonthDatas($lines,$code);
	showPageList($lines,$page,$pageSize);
	exit;
}

/*
	请求1分钟线
*/
if ($cycle == "min1") {
	$code = strtoupper($code);
	// 读取一分钟线数据
	$lines = readMinuteLine($code,$fq);
	showPageList($lines,$page,$pageSize);
	exit;
}

/*
	请求5分钟线线
*/
if ($cycle == "min5") {
	$code = strtoupper($code);
	// 读取5分钟线数据
	// $lines = readFiveMinuteLine($code,$fq);
	// 读取一分钟线数据
	$lines = readMinuteLine($code,$fq);
	$lines = returnMinuteData($lines,5);
	showPageList($lines,$page,$pageSize);
	exit;
}

/*
	请求15分钟线线
*/
if ($cycle == "min15") {
	$code = strtoupper($code);
	// 读取一分钟线数据
	$lines = readMinuteLine($code,$fq);
	$lines = returnMinuteData($lines,15);
	showPageList($lines,$page,$pageSize);
	exit;
}
/*
	请求30分钟线线
*/
if ($cycle == "min30") {
	$code = strtoupper($code);
	// 读取一分钟线数据
	$lines = readMinuteLine($code,$fq);
	$lines = returnMinuteData($lines,30);
	showPageList($lines,$page,$pageSize);
	exit;
}
/*
	请求60分钟线线
*/
if ($cycle == "min60") {
	$code = strtoupper($code);
	// 读取一分钟线数据
	$lines = readMinuteLine($code,$fq);
	$lines = returnMinuteData($lines,60);
	showPageList($lines,$page,$pageSize);
	exit;
}

/*
	请求分时线
*/
if ($cycle == "timeline") {
	$page = 1;
	$pageSize = 242;
	$code = strtoupper($code);
	// 读取redis分钟线数据
	$lines = readRedisMinLine($code,$fq);
	if (count($lines)<=0) {
		$lines = readMinuteLine($code,$fq);

	}
	// var_dump($lines);
	$lines = returnTimeLineData($lines,1);
	showPageList($lines,$page,$pageSize);
	exit;
}
/*
	请求5日分时线
*/
if ($cycle == "timeline5") {
	$page = 1;
	$pageSize = 5*242;
	$code = strtoupper($code);
	// 读取一分钟线数据
	$lines = readMinuteLine($code,$fq);
	$lines = returnTimeLineData($lines,5);
	showPageList($lines,$page,$pageSize);
	exit;
}

?>