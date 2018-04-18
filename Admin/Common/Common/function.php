<?php

function getUser(){
	$user = session('adminuser');
	return '['.$user['role_name'].']'.$user['realname'];
}

function getThumbImg($pic){
	if(!$pic){
		return;
	}
	
	$pics = explode(';',$pic);
	$str = '';
	foreach($pics as $v){
		$v = ltrim($v,'.');
		$str .= "<img class='lazy'  data-original='{$v}'   width='60'>";
	}
	return $str;
}

function getToolIcon($type,$class='',$url='',$name='',$icon='',$color='',$js=''){
	
	if(!IS_ROOT){
		$resources = session('adminuser.allow');
		parse_str($url,$params);
		$module = $params['c'];
		$action = $params['a'];
		$access = $module.'/'.$action;
		$group = $group ? $group : MODULE_NAME;
		if($group != 'Home'){
			$access = $group.'/'.$access;
		}
		
		
		if(!in_array($access, $resources)){
			return false;
		}
	}
	
	$map = array(
			'add'=>array('color'=>'primary','name'=>'添加','icon'=>'plus-sign'),
			'edit'=>array('color'=>'info','name'=>'修改','icon'=>'pencil'),
			'delete'=>array('color'=>'warning','name'=>'删除','icon'=>'trash'),
			'on'=>array('color'=>'success','name'=>'启用','icon'=>'ok-circle'),
			'off'=>array('color'=>'default','name'=>'禁用','icon'=>'ban-circle'),
			'grant'=>array('color'=>'danger','name'=>'权限授予','icon'=>'thumbs-up'),
			'refresh'=>array('color'=>'danger','name'=>'刷新','icon'=>'thumbs-up'),
	);

	if($map[$type]){
		$color = $color ? $color : $map[$type]['color'];
		$name = $name ? $name : $map[$type]['name'];
		$icon = $icon ? $icon : $map[$type]['icon'];
	}

	if($js){
		$jsStr = "onclick='{$js}(this)'";
	}
	
	return '<button type="button" class="btn  '.$class.'  btn-'.$color.'"  data-url="'.$url.'" '.$jsStr.'> 
                    <i class="glyphicon glyphicon-'.$icon.' " aria-hidden="true" ></i>&nbsp;'.$name.'
              </button>';

}



function formBuilder($name,$options=[]){
	require_once APP_PATH."Extend/Tool/Form.php";
	return Form::$name($options);
}



function getAddress($lat,$lon){
	$api = 'http://restapi.amap.com/v3/geocode/regeo?key=92124cdc3eadc74433d01a1a98ebac72&location='.$lat.','.$lon.'&radius=1000&extensions=all&batch=false&roadlevel=1';

	$res = file_get_contents($api);
	$data = json_decode($res,true);
	if($data['status']){
		return $data['regeocode']['formatted_address'];
	}
	return '';
}


function getSignPackage() {
	$jsapiTicket = getTicket();
	// 注意 URL 一定要动态获取，不能 hardcode.
	$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
	$url = "{$protocol}{$_SERVER[HTTP_HOST]}{$_SERVER[REQUEST_URI]}";
	//$url = 'http://mp.weixin.qq.com?params=value';
	//$url = "http://m.110route.com/weixinapp.php?c=Weixin&a=scan";
	$url = str_replace('cdn.', '', $url);
	$timestamp = time();
	$nonceStr = createNonceStr();

	// 这里参数的顺序要按照 key 值 ASCII 码升序排序
	$string = "jsapi_ticket={$jsapiTicket}&noncestr={$nonceStr}&timestamp={$timestamp}&url={$url}";

	$signature = sha1($string);

	$signPackage = array(
			"appId"     => C('WX_OPEN_ID'),
			"nonceStr"  => $nonceStr,
			"timestamp" => $timestamp,
			"url"       => $url,
			"signature" => $signature,
			"rawString" => $string,
			'ticket' => $jsapiTicket
	);
	$log = "appid:".C('WX_OPEN_ID')."\r\n";
	$log .= "nonceStr:".$nonceStr."\r\n";
	$log .= "timestamp:".$timestamp."\r\n";
	$log .= "url:".$url."\r\n";
	$log .= "signature:".$signature."\r\n";
	$log .= "ticket:".$jsapiTicket."\r\n";
	$log .= "rawString".$signature;
	//file_put_contents('./1.log', $log);
	return $signPackage;
}

function createNonceStr($length = 16) {
	$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
	$str = "";
	for ($i = 0; $i < $length; $i++) {
		$str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
	}
	return $str;
}


function getTicket(){
	//$redis = service('Redis');
	//$ticket = $redis->get('WX_JS_API_TICKET');
	$ticket = S('WX_JS_API_TICKET');
	if($ticket){
		return $ticket;
	}

	$token = getToken();

	// 如果是企业号用以下 URL 获取 ticket
	$url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$token";
	$rs = file_get_contents($url);
	if($rs){
		$rs = json_decode($rs,true);
		if($rs['errcode'] == 0){
			S('WX_JS_API_TICKET',$rs['ticket'],7200);
			//$redis->set('WX_JS_API_TICKET',$rs['ticket'],7200);
			return $rs['ticket'];
		}

	}
	return false;
}


/**
 * 获取token
 */
function getToken(){
	//$redis = service('Redis');
	//$token = $redis->get('WX_TOKEN');
	$token = S('WX_TOKEN');
	if($token){
		return $token;
	}else{
		$appID = C('WX_OPEN_ID');
		$appSecret = C('WX_SECRET');
		$toUrl = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appID}&secret={$appSecret}";

		$rs = file_get_contents($toUrl);
		if($rs){
			$tokens = json_decode($rs,true);
			S('WX_TOKEN',$tokens['access_token'],7200);
			//$redis->set('WX_TOKEN',$tokens['access_token'],7200);
			return $tokens['access_token'];
		}
	}

	return false;
}

/**
 *  回复文本消息
 */
function replyText($openid,$content){



	$token = getToken();
	$url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=".$token;
	$data = array();
	$data['touser'] = $openid;
	$data['msgtype'] = 'text';
	$data['text'] = array('content'=>$content);
	$jsonData = json_encode($data,JSON_UNESCAPED_UNICODE);
	$rs = curlInfo($url,$jsonData);
	//$rs = curl_https($url,$jsonData);
	return $rs;
}


/**
 *  回复文本消息
 */
function replyText1($openid,$content){

	$redis = service('Redis');
	$find = $redis->hGet('WX_USER_SETTING_TIME',$openid);

	$flag = 0;
	//用户设置不接收
	if(!$find){
		$flag = 0; //全天接收
	}elseif($find==9){
		$flag = 1; //全天不接收
	}elseif($find == 1){
		$h = (int)date('H');
		if($h>=8 && $h<=22){
			$flag = 0; //8点到22点接收
		}else{
			$flag = 1; //8点到22点外不接收
		}
	}elseif($find == 2){
		$h =(int) date('H');
		if($h>8 && $h<22){
			$flag = 1; //8点到22点不接收
		}else{
			$flag = 0; //8点到22点接收
		}
	}


	if($flag == 1){
		return true;
	}


	$token = getToken();
	$url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=".$token;
	$data = array();
	$data['touser'] = $openid;
	$data['msgtype'] = 'text';
	$data['text'] = array('content'=>$content);
	$jsonData = json_encode($data,JSON_UNESCAPED_UNICODE);
	//$rs = curlInfo($url,$jsonData);
	$rs = curl_https($url,$jsonData);
	return $rs;
}

function curl_https($url, $data=array(), $header=array(), $timeout=30){

	echo APP_PATH;
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_TIMEOUT,$timeout);
	//这里设置代理，如果有的话
	curl_setopt($ch,CURLOPT_URL, $url);
	curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
	curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
	//设置header
	curl_setopt($ch,CURLOPT_HEADER,FALSE);
	//要求结果为字符串且输出到屏幕上
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
	//设置证书
	//使用证书：cert 与 key 分别属于两个.pem文件
	//默认格式为PEM，可以注释
	curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
	curl_setopt($ch,CURLOPT_SSLCERT, APP_PATH.'Weixin/WxPay/cert/apiclient_cert.pem');
	//默认格式为PEM，可以注释
	curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
	curl_setopt($ch,CURLOPT_SSLKEY, APP_PATH.'Weixin/WxPay/cert/apiclient_key.pem');
	$rs = file_get_contents(APP_PATH.'Weixin/WxPay/cert/apiclient_key.pem');
	//post提交方式
	curl_setopt($ch,CURLOPT_POST, true);
	curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
	$data = curl_exec($ch);
	$response = curl_exec($ch);
	$error = curl_errno($ch);
	echo "curl出错，错误码:$error"."<br>";
	echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
	curl_close($ch);
	return $response;

}





//处理超市说明 把span标签换成em
function getDesc($str){
	$str = str_replace('span', 'em', $str);
	
	return htmlspecialchars_decode($str);
}

function isChinaName($name){
	$pattern = '/^([\xe4-\xe9][\x80-\xbf]{2}){2,4}$/';
	return preg_match($pattern, $name);
	
	 
}

function isCardNo($card){
	$pattern = '/^[1-9]\d{7}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}$|^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}([0-9]|X)$/';
	return preg_match($pattern, $card);
}



/**
 * 获取所有用户
 */
function getAllUser(){
	$ulist = S('USER_LIST');
	if(!$ulist){
		$ulist = D('Adminuser')->getField('id,realname',true);
		S('USER_LIST',$ulist,3600);
	}
	
	return $ulist;
}


function getLast($users){
	$users = trim($users,',');
	
	$users = explode(',', $users);
		
	$pop = array_pop($users);
	
	
 	$all = getAllUser();
 	return $all[$pop];
}


function addtowns(){
	$where['tid'] = array('like','3204%');
	$res = M('town')->where($where)->select();
	$dataall = array();
	foreach($res as $k=>$v){
		$data['RegionCode']  = $v['tid'];
		$data['RegionName'] = $v['tname'];
		$data['ParentCode']= substr($v['tid'],0,6);
		$data['HasChild'] = '0';
		$dataall[] = $data;
	} 

	$res = D('Region')->addAll($dataall);

	if($res){
		return 'ok!';
	}else{
		return 'nook!';
	}
}





/**
 * 设置计划任务用永久保存 配置文件
 */
function setCronCfg($key,$value){
	$sql = "insert into tb_sys_croncfg values ( '{$key}','{$value}' ) on duplicate key update `value` = '{$value}'";
	$rs = D()->query($sql);
	return $rs;
}

/**
 * 获取计划任务用永久保存配置文件
 */
function getCronCfg($key){
	if(!$key){
		return false;
	}
	return D('sys_croncfg')->where(array('key'=>$key))->getField('value');
}


/**
 * 获取故障类型
 */
function getProblemType($stype,$ptype){
	$t = C('WORK_PROBLEM_TYPE');
	
	$type = $t[$stype][$ptype];
	return $type ? $type : '其他' ;
}

/**
 * 获取拨打电话按钮
 */
function callPhone($phone,$str,$style){
	if(empty($phone)){
		return '-';
	}
	$str = empty($str) ? $phone : $str;
	$fnum = session('adminuser.fnum');
	if($style){
		$icon = "<i class='iconfont icon-dianhua'></i>";
	}
	if(!$fnum){
		return "<a href='javascript:void(0)' onclick='layer.msg(\"抱歉,您没有拨打电话权限\")' class='btn'>{$icon}{$str}</a>";
	}
	return "<a href='javascript:void(0)' onclick='kcall({$phone})' class='btn'>{$icon}{$str}</a>";
	
	
}




/**
 * 实例跨主机关联模型
 * @param unknown $config
 * @param string $prefix
 * @return Ambigous <Model, \Think\Model, unknown>
 */
function DD($config,$prefix="tb_"){
	$config = $config ? $config : C('CENTER_CONFIG');
	return D('',$prefix,C($config));
}

/**
 * 获取接口信息
 * @param unknown $userid
 * @param unknown $content
 * @param number $agentid
 */
function getApi($params){
	$params['t'] = time();
	$params['sign'] = getSignature($params);
	if($_SERVER['HTTP_HOST'] == 'wsheet.com'){
		$toUrl = "https://web.110route.com/sapi.php";
	}else{
		$toUrl = "https://web.110route.com/sapi.php";
	}
	// dump($params);
	// dump($toUrl);
	// dump(http_build_query($params));
	$rs = curlInfo($toUrl, $params);
	return json_decode($rs,true);
	
}




function qyreply($userid,$content,$agentid=6){
	$token = getToken();
	$url = "https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token=".$token;
	$data = array();
	$data['touser'] = $userid;
	$data['agentid'] = $agentid ;
	$data['msgtype'] = 'text';
	$data['text'] = array('content'=>$content);
	$jsonData = json_encode($data,JSON_UNESCAPED_UNICODE);
	$res = curlInfo($url,$jsonData);
	return $res;
}









function getAvatar($avt){
	if(!$avt){
		return "/Public/images/avatar.png";
	}
	
	return rtrim($avt,'0').'64';
}

function getUploadDir(){
	$date = date('Ymd');
	$dir = './Uploads/'.$date.'/';
	if(!is_dir($dir)){
		mkdir($dir,true);
	}
	chmod($dir, 0777);
	return $dir;
}

//根据网吧编号获取网吧信息
function getBarinfo($barcode,$flag){
	$api = "http://sn.110route.com/getbarinfobybarcode.php";
	
	$param = array();
	
	$param['sign'] = md5($barcode.C('ROUTER_CHECK_SECRET').date('Ymd'));
	$param['barcode'] = $barcode;
	$param['flag'] = $flag;

	$barinfo = curlInfo($api, $param);
	
	return json_decode($barinfo,true);
}

function allow($act){
	if(IS_ROOT){
		return true;
	}
	
	$allow = session('adminuser.allow');
	if(in_array($act,$allow)){
		return true;
	}
	return false;
}


function query_user($id){
	$uinfo = D('AdminUser')->where(array('id'=>$id))->find();
	if(!$uinfo){
		return false;
	}
	$role = D('Role')->where(array('id'=>$uinfo['role_id']))->find();
	
	$uinfo['role_name'] = $role['title'];
	
	
	return $uinfo;
	
}


function str2arr($string=''){
	if(empty($string)) return false;
	$array = preg_split('/[\r\n]+/', trim($string, ",;\r\n"));
	if(strpos($string,':')){
		$value  = array();
		foreach ($array as $val) {
			//解决domain:http://static.mitangtrip.com问题
			$_i=strpos($val,':');
			$_k=substr($val, 0,$_i);
			$_v=substr($val, $_i+1);
			$value[$_k] = $_v;
		}
		return $value;
	}else{
		return $array;
	}
}

/*------------------------- 数字转文字 -------------------------*/
/*
 *	数字替换文字
*	tpl:int_str(1,'1:开启,0:关闭')
*   tpl:int_str(1,'1:true,0:false')
*	tpl:int_str(0,'TYPE_NAME');
*/
function int_str($int=0,$str=''){
	if(empty($str)) return false;
	$array=array();$icon=false;
	//int_str(1,'1:开启,0:关闭')
	if(strpos($str,',') !== false){
		
		//判断英文返回符号
		if(preg_match('/:([a-z]+),*/', $str)) $icon=true;
		$arr=explode(',',$str);
		foreach ($arr as $k => $v) {
			list($kk,$vv)=explode(':', $v);
			$array[$kk]=$vv;
		}
	}
	//int_str(0,'TYPE_NAME',1);
	elseif(preg_match('/^[a-zA-z]+$/', $str)){
		
		$array=C($str);
		
	}else{
		return false;
	}
	if(isset($array[$int])){
		if(!$icon){
			return $array[$int];
		}else{
			$iconfont = $int == 1 ? "icon iconfont icon-chenggongzhuangtai" : "icon iconfont icon-shibai";
			$able = $int==1 ? 'enable' : 'disable';
			$title = $int == 1 ? '启用': '禁用';
			return "<i class='".$iconfont." ".$able."' title='".$title."' ></i>";
		}
		
	}else{
		return false;
	}
}


function getConfig($key,$value){
	
	$arr = C($key);
	if($value){
		return  $arr[$value];
	}
	
	return C($key);
	
}

//带标记的一维数组 array('│', '├', '└','─');
 function listLevel($cate,$parentid = 0, $level = 0, $mark ='-----') {
	$arr = array();
	// dump($cate);
	foreach ($cate as $k => $v) {
		// dump($v);
		
		if ($v['pid'] == $parentid) {
			
			if($level>0){
				$v['mark'] = '|-'.str_repeat($mark, $level);
			}else{
				$v['mark'] = '|-';
			}
			$v['level'] = $level + 1;
			$arr[] = $v;
			$return = listLevel($cate, $v['id'], $v['level'], $mark);
			// dump($return);
			// if(empty($return)){
			//     $_end=array_pop($arr);
			//     $_end['end']=$level;
			//     array_push($arr,$_end);
			// }
			$arr = array_merge($arr,$return);
		}
	}
	
	return $arr;
}



function getMoney($money){
	return round($money/100,2).'元';
}

function  log_result($file,$word)
{
	$fp = fopen($file,"a");
	flock($fp, LOCK_EX) ;
	fwrite($fp,"执行日期：".strftime("%Y-%m-%d-%H：%M：%S",time())."\n".$word."\n\n");
	flock($fp, LOCK_UN);
	fclose($fp);
}




//判断一个IP地址是否为私有IP
//（10开头，100.64-100.127，172.16-172.31，192.168）
function isPrivateIP($ip){
	$p= explode('.', $ip);
	if($p[0] == 10){
		return true;
	}
	
	if($p[0] == 100){
		if($p[1]>=64 && $p[1]<=127){
			return true;
		}
	}
	
	if($p[0] == 172){
		if($p[1] >=16 && $p[1]<=31){
			return true;
		}
	}
	
	if($p[0] == 192 && $p[1] == 168){
		return true;
	}
	
	return false;
}




/**
 * 根据经纬度获取位置
 */
function parseLocation($jing,$wei){
	$smap = service('Amap');
	//$location = $smap->getAddress($jing,$wei);
	
	return $location;
}



/**
 * 获取token
 */
function getWeixinToken(){
	$redis = service('Redis');
	$token = $redis->get('WX_TOKEN');
	if($token){
		return $token;
	}else{
		$appID = C('WX_APP_ID');
		$appSecret = C('WX_APP_SECRET');
		$toUrl = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appID}&secret={$appSecret}";

		$rs = curlInfo($toUrl);
		if($rs){
			$tokens = json_decode($rs,true);
			$redis->set('WX_TOKEN',$tokens['access_token'],$tokens['expires_in']);
			return $tokens['access_token'];
		}
	}

	return false;
}


/**
 * 获取一个时间离现在过了多少小时
 * @param unknown $time
 * @return string
 */
function getTimeLast($time){
	$start = strtotime($time);
	$second = time()-$start;
	
	if($second<60){
		$return = $second.'秒';
	}elseif($second<3600){
		$return = ceil($second/60).'分';
	}else{
		$return = ceil($second/3600).'小时';
	}
	
	return $return;
}



/**
 * 获取二维码图片
 */
function getQrImg($id,$type='ticket'){
	$id = strval($id);
	$token = getToken();
	$url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=".$token;
	$data = array();
	
	$data['expire_seconds'] = 2592000;
	$data['action_name'] = 'QR_LIMIT_SCENE';
	$data['action_info'] = array('scene'=>array('scene_id'=>$id));
	$data = json_encode($data,JSON_UNESCAPED_UNICODE);
	//dump($data);exit;
	$res = curlInfo($url, $data);
	$res = json_decode($res,true);
	if($res['errcode'] == '40001'){
		//getQrImg($id);
	}
	
	$t = urlencode($res[$type]);
	if(!$t){
		return false;
	}
	if($type == 'ticket'){
		$str = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=".$t;
	}else{
		$str = urldecode($res[$type]);
	}
	return $str;
}



/**
 * 获取IP内容
 */
function getIpAddress($IP){
	
	$redis = service('Redis');
	$str = $redis->hget('IP_MAP',$IP);
	if($str){
		return $str;
	}
	
	$url = "http://ip.taobao.com/service/getIpInfo.php?ip=".$IP;
	$opts = array(
			'http'=>array(
					'method'=>"GET",
					'timeout'=>1,//单位秒
			)
	);
	$res = file_get_contents($url,false, stream_context_create($opts));
	if($res){
		$res = json_decode($res,true);
		$res = $res['data'];
		$str = $res['region'].'-'.$res['city'].'-'.$res['county'].'-'.$res['isp'];
		$redis->hset('IP_MAP',$IP,$str);
	}else{
		$str= '未知';
	}
	
	return $str;
	
}




function pd($result, $type = "string",$FILTER = 'strip_tags'){
    if(is_array($result)){
	   foreach($result as $k => $v){
	      $result[$k] = pd($v, $type,$FILTER);
	   }
	}else{
		if (is_array ( $type )) {
			if (! in_array ( $result, $type ) || $result == '')
				$result = $type [0];
		} else {
			if ($type == "string") {
				$result = trim ( $result );
			} elseif ($type == "int") {
				$result = intval ( $result );
			}
		}
		if($FILTER!='')
			$result = $FILTER($result);
	}

	return $result;
}

function p($key, $type = "string",$FILTER = 'strip_tags') { // 获取用户提交数据
	$result = isset ( $_POST [$key] ) ? $_POST [$key] : (isset ( $_GET [$key] ) ? $_GET [$key] : '');	
	return pd($result, $type,$FILTER);
}

function pp($key, $type = "string",$FILTER = 'strip_tags') { // 获取用户提交数据
	$result = isset ( $_POST [$key] ) ? $_POST [$key] : '';	
	return pd($result, $type,$FILTER);
}

function pg($key, $type = "string",$FILTER = 'strip_tags') { // 获取用户提交数据
	$result = isset ( $_GET [$key] ) ? $_GET [$key] : '';	
	return pd($result, $type,$FILTER);
}

/**
 * gML函数用于实例化模型类 格式 [资源://][模块/]模型
 * @param string $name 资源地址
 * @return Model
 */
function gM($name = '', $siteid = 0){
	return gML($name,"",$siteid);
}

/**
 * gML函数用于实例化模型类 格式 [资源://][模块/]模型
 * @param string $name 资源地址
 * @param string $layer 模型层名称
 * @return Model
 */
function gML($name = '',$layer = '', $siteid = 0) {
	static $_model = array ();
	$sites = array ();
	if ($siteid > 0) {
		$sites = getSites ( $siteid );
		if (empty ( $sites )) {
			E ( "站点ID[$siteid]配置不存在,请在后台管理" );
		}
	}
	$model = D ( $name ,$layer);

	if ($siteid > 0 && $sites ['ispool'] == 1) { // 分库
		if (! C ( 'dbconfig_' . $siteid )) {
			C ( include CONF_PATH . 'dbconfig/' . $siteid . '.php' );
		}
		$db_config = C ( 'dbconfig_' . $siteid );
		if (empty ( $db_config )) {
			E ( '网站ID:' . $siteid . '的数据库配置不存在!' );
		}
		$model->db ( $siteid, $db_config );
	} else {
		$model->db ( 0 );
	}
	return $model;
}

/**
 * 获得互斥锁
 * @param unknown $lockname 锁名
 * @param number $time	锁等待时间
 * return boolean
 */
function getLock($lockname, $time = 0) {
	$time <= 0 && $time = C ( "LOCKED_TIME" );
	return \Common\Util\Locked::getLocked ( $lockname, $time );
} 
/**
 * 释放锁
 * @param string $lockname锁名
 */
function unLock($lockname) {
	\Common\Util\Locked::unLocked ( $lockname );
}
/**
 * 清空所有锁
 */
function clearLock() {
	\Common\Util\Locked::clear ();
}




//创建guid
function create_guid() {
	$charid = (md5(uniqid(mt_rand(), true)));
	$hyphen = chr(45);// "-"
	$uuid = substr(md5(uniqid()),10,5).substr($charid, 8, 5);
	return strtoupper($uuid);

}

function encrypt($string,$key) {
	//加密用的密钥文件
	//$key = "l$`h.V,&";
	 
	//加密方法
	$cipher_alg = MCRYPT_TRIPLEDES;
	//初始化向量来增加安全性
	$iv = mcrypt_create_iv(mcrypt_get_iv_size($cipher_alg,MCRYPT_MODE_ECB), MCRYPT_RAND);
	 
	//开始加密
	$encrypted_string = mcrypt_encrypt($cipher_alg, $key, $string, MCRYPT_MODE_ECB, $iv);
	return base64_encode($encrypted_string);//转化成16进制
	//  return $encrypted_string;
}
function decrypt($string,$key) {
	$string = base64_decode($string);
	//加密用的密钥文件
	//$key = "l$`h.V,&";

	//加密方法
	$cipher_alg = MCRYPT_TRIPLEDES;
	//初始化向量来增加安全性
	$iv = mcrypt_create_iv(mcrypt_get_iv_size($cipher_alg,MCRYPT_MODE_ECB), MCRYPT_RAND);

	//开始加密
	$decrypted_string = mcrypt_decrypt($cipher_alg, $key, $string, MCRYPT_MODE_ECB, $iv);
	return trim($decrypted_string);
}

function allToString(&$value,$key){
	$value = "".($value);
}

	/*
	* author 获取不同查询条件组合
	*
	*/
function mapArrayMerge($map,$key='e.regplatform'){
	$authuser = session("authuser");
	$platform = $authuser['platform'];
	if(!in_array($platform,C('ADMIN_USER_LIST')) && $platform != '' && !in_array($authuser['roleid'],array(1))){
		switch($key){
			case in_array($key,array('userid','id')):
				$mapa['regplatform']=array('in',$platform);
				$list = gM("member_ext")->field("id")->where($mapa)->order("id")->select();
				foreach($list as $v){
					$idList .= $line.$v['id'];
					$line = ',';
				}
				$map[$key] = array('in',$idList);
				return $map;
				break;
			default:
				return array_merge($map,array($key=>$platform));
		}
	}else{
		return $map;
	}
}


	/*
	* author 根据权限获取渠道列表
	*
	*/
function getRegplatList($plat = ""){
	$authuser = session("authuser");
	$str = '';
	
	$plat = $_REQUEST['regplatsearch'];
	if(in_array($authuser['name'],C('ADMIN_USER_LIST'))|| in_array($authuser['roleid'],array(1)) ){
		$list = gM("regplat_conf")->field("rpid,rp_name")->order("id")->select();
		if(!empty($list)){
			$str .= '渠道:<SELECT class=" bLeft" name="regplatsearch"><option value="">请选择</option>';
			foreach($list as  $val){
				if($plat == $val["rpid"])
					$str .= '<option value="'.$val["rpid"].'" selected="selected">'.$val["rp_name"].'</option>';
				else
					$str .= '<option value="'.$val["rpid"].'">'.$val["rp_name"].'</option>';
			}
			$str .= '</SELECT>';
		}
	}
	echo $str;
}

function utf8_strlen($string = null) {
	// 将字符串分解为单元
	preg_match_all('/./us', $string, $match);
	// 返回单元个数
	return count($match[0]);
}

/** 
 +---------------------------------------------------------- 
 * 字符串截取，支持中文和其他编码 
 +---------------------------------------------------------- 
 * @static 
 * @access public 
 +---------------------------------------------------------- 
 * @param string $str 需要转换的字符串 
 * @param string $start 开始位置 
 * @param string $length 截取长度 
 * @param string $charset 编码格式 
 * @param string $suffix 截断显示字符 
 +---------------------------------------------------------- 
 * @return string 
 +---------------------------------------------------------- 
 */  
function msubstr($str, $start=0, $length, $charset="utf-8", $suffix=true)  
{  
	
	if(utf8_strlen($str)>$length){
		$suffix = '...';
	}else{
		$suffix = '';
	}
    if(function_exists("mb_substr")){   
    if($suffix)   
      return mb_substr($str, $start, $length, $charset).$suffix;  
      else  
      return mb_substr($str, $start, $length, $charset);   
       }  
    elseif(function_exists('iconv_substr')) {  
        if($suffix)   
       return iconv_substr($str,$start,$length,$charset).$suffix;  
       else  
       return iconv_substr($str,$start,$length,$charset);  
    }  
    $re['utf-8']   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";  
    $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";  
    $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";  
    $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";  
    preg_match_all($re[$charset], $str, $match);  
    $slice = join("",array_slice($match[0], $start, $length));  
    if($suffix) return $slice.$suffix;  
    return $slice;  
} 

function toLifeTime($date){
	if($date == "0000-00-00 00:00:00")
		return "--";
	$time = time() - strtotime($date);
	$str = "";
	if($time/86400 > 0){
		$str .= intval($time/86400)."天";
	}
	if($time/3600 > 0){
		$str .= intval(($time - intval($time/86400)*86400)/3600) ."时";
	}
	if($time/60 > 0){
		$str .= intval(($time - intval($time/3600)*3600)/60) ."分";
	}else{
		$str = $time."秒";
	}
	return $str;
}

function toLifeTime1($date){
	if($date == "0000-00-00 00:00:00")
		return "--";
	$time = time() - strtotime($date);
	$str = "";
	if($time>86400){
		$str .= intval($time/86400)."天";
	}elseif($time>3600){
		$str .= intval(($time - intval($time/86400)*86400)/3600) ."时";
	}elseif($time>60){
		$str .= intval(($time - intval($time/3600)*3600)/60) ."分";
	}else{
		$str = $time."秒";
	}
	return $str;
}

function areaListLink($type){
	$AreaCode = session('AreaCode');
	$authuser = session('authuser');
	if($AreaCode == $authuser['auth']) return '';
	if($AreaCode){
		$Region = gM("Region")->query(" (select * from ".C('DB_PREFIX')."region where RegionCode='".$AreaCode."')");
		if($Region){
			$url = U(ACTION_NAME,array('areacode'=>($Region[0]['ParentCode']==""?"0":$Region[0]['ParentCode'])));
			if ($type){
				if (is_array($type)) {
					$url .= '&'.http_build_query($type);
				}
				
			}
			return "当前地区:<strong style='color:rgb(0,0,255)'>".$Region[0]['RegionName']."</strong> 
					<a href='".$url."'>返回</a>";
		}
	}
	return "";
}




function Adds($list,$key,$num = 1){
	if($num<1)
		return -1;
	$sum = 0;
	$tmp = explode("/",$key);
	foreach($list as $v){
		$i=1;
		$_v = $v[$tmp[0]];
		while($i<count($tmp)){
			$_v = $_v[$tmp[$i]];
			$i++;
		}
			
		$sum += $_v;
	}
	return $sum/$num;
}

if( ! function_exists('array_column'))
{
	function array_column($input, $columnKey, $indexKey = NULL)
	{
		$columnKeyIsNumber = (is_numeric($columnKey)) ? TRUE : FALSE;
		$indexKeyIsNull = (is_null($indexKey)) ? TRUE : FALSE;
		$indexKeyIsNumber = (is_numeric($indexKey)) ? TRUE : FALSE;
		$result = array();

		foreach ((array)$input AS $key => $row)
		{
			if ($columnKeyIsNumber)
			{
				$tmp = array_slice($row, $columnKey, 1);
				$tmp = (is_array($tmp) && !empty($tmp)) ? current($tmp) : NULL;
			}
			else
			{
				$tmp = isset($row[$columnKey]) ? $row[$columnKey] : NULL;
			}
			if ( ! $indexKeyIsNull)
			{
				if ($indexKeyIsNumber)
				{
					$key = array_slice($row, $indexKey, 1);
					$key = (is_array($key) && ! empty($key)) ? current($key) : NULL;
					$key = is_null($key) ? 0 : $key;
				}
				else
				{
					$key = isset($row[$indexKey]) ? $row[$indexKey] : 0;
				}
			}

			$result[$key] = $tmp;
		}

		return $result;
	}
}


/**
 * 根据code获取区域信息
 * @param unknown $code
 * @param unknown $type
 * @return string|Ambigous <string, \Think\mixed, NULL, mixed, multitype:Ambigous <unknown, string> unknown , object, unknown>
 */
function getRegionInfo($code,$type){
	//$redis = service('Redis');
	if(!$type){
		if(strlen($code) == 6){
			$type = 'country';
		}elseif(strlen($code) == 4){
			$type = 'city';
		}elseif(strlen($code) == 2){
			$type = 'province';
		}
	}
	if($type == 'province'){
		if(strlen($code)<2) return '无';
		$area = substr($code, 0,2);
		
		// $name = $redis->hget('REGION_MAP',$area);
		$name = D('Region')->where("RegionCode=".$area)->getField('RegionName');
		
		if(!$name) $name = '无';
	}
	if($type == 'city'){
		if(strlen($code)<4) return '无';
		$area = substr($code, 0,4);
				
		// $name = $redis->hget('REGION_MAP',$area);
		// dump($name);
		$name = D('Region')->where("RegionCode=".$area)->getField('RegionName');
		if(!$name) $name = '无';
	}
	if($type == 'country'){
		if(strlen($code)<6) return '无';
		$area = substr($code, 0,6);
	
		// $name = $redis->hget('REGION_MAP',$area);
		$name = D('Region')->where("RegionCode=".$area)->getField('RegionName');
		if(!$name) $name = '无';
	}
	if($type == 'town'){
		if(strlen($code)<9) return '无';
		$area = substr($code, 0,9);
				
		// $name = $redis->hget('REGION_MAP',$area);
		$name = D('Region')->where("RegionCode=".$area)->getField('RegionName');
		if(!$name) $name = '无';
	}
	
	return $name;
	
}

function queryFullRegionInfo($regionCode){
	// dump($regionCode);
	if(empty($regionCode)){
		return '全国';
	}
	$info = '';
	if(strlen($regionCode) >= 2){
		
		$info .= getRegionInfo($regionCode, 'province');
	}
	
	if(strlen($regionCode) >= 4){
		
		$info .= '-'.getRegionInfo($regionCode, 'city');
	}
	
	if(strlen($regionCode) >= 6){

		$info .= '-'.getRegionInfo($regionCode, 'country');
	}
	
	if(strlen($regionCode) >= 9){
		$info .= '-'.getRegionInfo($regionCode, 'town');
	}
	
	
	return $info;
}






/**
 * 判断身份证号是否合法
 * @param string $idcard
 * @return boolean
 */
function isValidIdCard($idcard){
	if(strlen($idcard) == 15){
		return  preg_match("/^[1-9]\d{7}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}$/", $idcard);
	}
	if(strlen($idcard) == 18){
		return preg_match("/^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}(\d|x|X)$/", $idcard);
	}
	return false;
}

/**
 * 地区权限基础过滤
 */
function listAreaFilter(){
	$auth = session('authuser.auth');
	if(empty($auth)){
		return array('neq','0');
	}
	return array('like',"{$auth}%");
}


/**
 * 获取身份证年龄
 */
function howold($str){
	return getAgeById($str);
}

/**
 * 把返回的数据集转换成Tree
 * @param array  $list 要转换的数据集
 * @param string $pid parent标记字段
 * @param string $level level标记字段
 * @return array
 */
function list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_child', $root = 0)
{


	// 创建Tree
	$tree = array();
	if (is_array($list)) {
		// 创建基于主键的数组引用
		$refer = array();
		foreach ($list as $key => $data) {
			$refer[$data[$pk]] =& $list[$key];
		}
		foreach ($list as $key => $data) {
			// 判断是否存在parent
			$parentId = $data[$pid];
			if ($root == $parentId) {
				$tree[] =& $list[$key];
			} else {
				if (isset($refer[$parentId])) {
					$parent =& $refer[$parentId];
					$parent[$child][] =& $list[$key];
				}
			}
		}
	}

	return $tree;
}

/**
 * 通过身份证获取性别
 * @param string $id 
 * @return 1男 0 女
 */
function getSexById($id){
	$len = strlen($id);
	if($len == 15){
		$c = (int)$id{14};
		if($c%2==0){ //最后一位是偶数就是女
			return 0;
		}
		return 1;
	}
	if($len == 18){
		$c = (int)$id{16};
		if($c%2 ==0){ //倒数第二位是偶数就女
			return 0;
		}
		return 1;
	}
}

/**
 * 通过身份证获取年龄
 * @param string $id
 */
function getAgeById($id){
	$idYear = (int)substr($id, 6,4);
	$idMonth = (int)substr($id, 10,2);
	$idDay = (int)substr($id, 12,2);
	$nowYear = (int) date('Y');
	$nowMonth =(int) date('m');
	$nowDay = (int)date('d');

	$ageYear = $nowYear-$idYear; // 年
	
	if($nowMonth > $idMonth){ //月份已过 实岁
		return $ageYear;
	}elseif($nowMonth<$idMonth){ //月份未过 实岁减一
		return $ageYear-1;
	}else{
		if($nowDay<$idDay){ //月份相同  天数未到 实岁减一
			return $ageYear-1; 
		}
	}
	return $ageYear;

}

/**
 * 18生日到时提醒
 * @param unknown $ID
 */
function get18YearTips($id){
	$idYear = (int)substr($id, 6,4);
	$idMonth = (int)substr($id, 10,2);
	$idDay = (int)substr($id, 12,2);
	$year18 = $idYear+18;
	
	$birthday18 = $year18.'-'.$idMonth.'-'.$idDay;
	
	$now = date('Y-m-d');
	$res = diffDate($now, $birthday18);
	return '距18岁还有 '.$res['year'].'年'.$res['month'].'月'.$res['day'].'天';
}


/*
 *function：计算两个日期相隔多少年，多少月，多少天
*param string $date1[格式如：2011-11-5]
*param string $date2[格式如：2012-12-01]
*return array array('年','月','日');
*/
function diffDate($date1,$date2){
	if(strtotime($date1)>strtotime($date2)){
		$tmp=$date2;
		$date2=$date1;
		$date1=$tmp;
	}
	list($Y1,$m1,$d1)=explode('-',$date1);
	list($Y2,$m2,$d2)=explode('-',$date2);
	$Y=$Y2-$Y1;
	$m=$m2-$m1;
	$d=$d2-$d1;
	if($d<0){
		$d+=(int)date('t',strtotime("-1 month $date2"));
		$m--;
	}
	if($m<0){
		$m+=12;
		$y--;
	}
	return array('year'=>$Y,'month'=>$m,'day'=>$d);
}

/*
 * 通过小网分钟 获取小网小时数
 * @param int $time
 */
function getHours($time){
	if(empty($time)){
		return '暂未统计';
	}
	$str = '';	
	$hour = floor($time/3600);//小时
	$min = ceil(($time- $hour*3600)/60); //分
	if($hour){
		$str .= $hour.'小时';
	}
	
	$str .= $min."分钟";
	
	return $str;

}

function getAvgHours($t,$n){
	return getHours($t/$n);
}

/**
 * ftp文件上传
 * @param  $localfile
 * @return string
 */
function uploadByFtp($localfile,$targetPapth){
	$fp = fopen ($localfile, "r");
	$arr_ip = gethostbyname(C('FTP_CONFIG.FTP_HOST'));
	$ftp = "ftp://".$arr_ip.$targetPapth."/".basename($localfile);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_FTP_USE_EPSV);
	curl_setopt($ch, CURLOPT_VERBOSE, 1);
	curl_setopt($ch, CURLOPT_USERPWD, C('FTP_CONFIG.FTP_USER').':'.C('FTP_CONFIG.FTP_PWD'));
	curl_setopt($ch, CURLOPT_URL, $ftp);
	curl_setopt($ch, CURLOPT_PUT, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_INFILE, $fp);
	curl_setopt($ch, CURLOPT_INFILESIZE, filesize($localfile));
	$http_result = curl_exec($ch);
	$error = curl_error($ch);
	$http_code = curl_getinfo($ch ,CURLINFO_HTTP_CODE);
	curl_close($ch);
	fclose($fp);
	return C('FTP_CONFIG.FILE_HOST').$targetPapth.'/'.basename($localfile);
}

/**
 * 图片服务器ftp文件上传
 * @param  $localfile
 * @return string
 */
function uploadByFtpToPic($localfile,$targetPapth){
	$fp = fopen ($localfile, "r");
	$arr_ip = C('FTP_PIC_CONFIG.FTP_HOST');
	$ftp = "ftp://".$arr_ip.$targetPapth."/".basename($localfile);
	$user = C('FTP_PIC_CONFIG.FTP_USER');
	$pwd = C('FTP_PIC_CONFIG.FTP_PWD');
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_FTP_USE_EPSV);
	curl_setopt($ch, CURLOPT_VERBOSE, 1);
	curl_setopt($ch, CURLOPT_USERPWD, $user.':'.$pwd);
	curl_setopt($ch, CURLOPT_URL, $ftp);
	curl_setopt($ch, CURLOPT_PUT, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_INFILE, $fp);
	curl_setopt($ch, CURLOPT_INFILESIZE, filesize($localfile));
	$http_result = curl_exec($ch);
	$error = curl_error($ch);
	$http_code = curl_getinfo($ch ,CURLINFO_HTTP_CODE);
	curl_close($ch);
	fclose($fp);
	return C('FTP_PIC_CONFIG.FILE_HOST').$targetPapth.'/'.basename($localfile);
}




/**
 * 难身份证
 * @param unknown $card
 */
function parseCard($card){
	$pattern = "/^(^[1-9]\d{7}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}$)|(^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])((\d{4})|\d{3}[Xx])$)$/";
	if(!preg_match($pattern, $card)){
		return false;
	}
	
	return true;
	
	if(strlen($card) == 18){
		$wi = array( 7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2 );//将前17位加权因子保存在数组里
		$y = array(1, 0, 10, 9, 8, 7, 6, 5, 4, 3, 2 );//这是除以11后，可能产生的11位余数、验证码，也保存成数组
		for($i=0;$i<17;$i++){
			$idCardWiSum += $card[substr($i,$i+1)]*$wi[$i];
		}
		$idCardMod=$idCardWiSum%11;//计算出校验码所在数组的位置
		$idCardLast=$card[17];//得到最后一位身份证号码
		if($idCardMod==2){
			if($idCardLast=="X"||$idCardLast=="x"){
				return true;
			}else{
				return false;
			}
		}else{
			if($idCardLast == $y[$idCardMod]){
				return true;
			}else{
				return false;
			}
		}	
	}
	
	
}

function parseIP($ip){
	if(!$ip){
		return false;
	}
	
	$redis = service('Redis');
	$res = $redis->hget('REGION_IP_MAP',$ip);
	if($res){
		return $res;
	}
	
	$url = "http://ip.taobao.com/service/getIpInfo.php?ip=".$ip;
	$res = file_get_contents($url);
	
	//dump($res);
	if($res){
		$res = json_decode($res,true);
	}
	
	if($res['code'] == 0){
		$data = $res['data'];
		if(strlen($data['county_id']) == 6){
			$code = $data['county_id'];
		}elseif(strlen($data['city_id']) == 6){
			$code = rtrim($data['city_id'],0);
		}elseif(strlen($data['region_id']) == 6){
			$code = rtrim($data['region_id'],0);
		}
		
		if($code){
			$redis->hset('REGION_IP_MAP',$ip,$code);
			return $code;
		}
	
	}
	
	
	//dump($res);
	return false;
}

/**
 * 发送短信
 * @param string $mobile 发送的手机号码
 * @param string $content 发送的内容
 * @see $mobile	 = '13811299934,18610310066,15210954922';	//号手机码
 * @see $content = '您的短信验证码是：ABCD，注意，必须加签名【签名】';		//内容
 * @see $provicne 省份 短信未开启网关下做递减处理
 */
function sendSMS($mobile,$content,$province)
{	
	$last =  (int) S('HAS_SEND_MSG');
	if($last>=500){
		return true;	
	}
	
	$content = $content.'【钞速钱包】';
	
	$url = C('MOBILE_CONFIG.API_URL'); //接口地址
	$content = iconv("utf-8","gb2312//IGNORE",$content);
	$data = array
	(
			'username'=>C('MOBILE_CONFIG.USER_NAME'),//用户账号
			'password'=>C('MOBILE_CONFIG.USER_PASS'),//密码
			'mobile'=>$mobile,					//号码
			'content'=>$content,				//内容
			'apikey'=>C('MOBILE_CONFIG.USER_KEY'), //apikey
	);
	$result= curlSMS($url,$data);			//POST方式提交
	
	$last++;
	
	S('HAS_SEND_MSG',$last);
	
	return $result;
}

function curlSMS($url,$post_fields=array()){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 3600); //60秒
	curl_setopt($ch, CURLOPT_HEADER,1);
	curl_setopt($ch, CURLOPT_REFERER,'http://www.yourdomain.com');
	curl_setopt($ch,CURLOPT_POST,1);
	curl_setopt($ch, CURLOPT_POSTFIELDS,$post_fields);
	$data = curl_exec($ch);
	curl_close($ch);
	$res = explode("\r\n\r\n",$data);
	return $res[2];
}

/**
 * service调用方法
 * @param string $name
 * @return object
 */
function service($name='',$forceMaster = 3){
	
	static $model = array();
	if(!isset($model[$name])){
		if($name){
			$class = "\\Home\\Service\\".$name.'Service';
			$model[$name]      =   class_exists($class)? new $class($name) : new \Home\Service\Service();
		}else{
			$model[$name]      =   new \Home\Service\Service();
		}
	}
	
	$model[$name]->forceMaster = $forceMaster;
	
	return $model[$name];
}

/**
 * 水平切割算法
 * @param 关键字 $keyword
 * @param 水平分割基数 $n
 */
function crc_hash(&$keyword, $n = 8)
{
	$hash = crc32($keyword) >> 16 & 0xffff;
	return sprintf("%02s", $hash % $n);
}



 function decryptStr($hex){
	/* 	$string="";
		for($i=0;$i<strlen($hex)-1;$i+=2){
			$string.=chr(hexdec($hex[$i].$hex[$i+1]));
		} */
		
 	$string = hex2bin($hex);
	return	encryptStr($string);
}


function encryptStr($str){
	$len = strlen($str);
	$key = 'zcpwOIEWQODxaca!@COSJO%@%^*&MSODCJO))(}|MSCJOICScmsaodc';
	$klen = strlen($key);
	$newStr = '';
	$k = 0;
	for($i=0;$i<$len;$i++){
		$newStr .= chr(ord($str{$i}) ^ ord($key{$k}));
		$k++;
		if($k>=$klen){
			$k = 0;
		}
	}
	return $newStr;
}

//通过身份证获取用户基本信息
function getIdInfoByCard($card){
	$map = array('Card'=>$card);
	return D('IdCard')->where($map)->find();
}

function killHtml($str)
{
	$search = array ("'<script[^>]*?>.*?</script>'si",  // 去掉 javascript
			"'<[\/\!]*?[^<>]*?>'si",           // 去掉 HTML 标记
			"'([\r\n])[\s]+'",                 // 去掉空白字符
			"'&(quot|#34);'i",                 // 替换 HTML 实体
			"'&(amp|#38);'i",
			"'&(lt|#60);'i",
			"'&(gt|#62);'i",
			"'&(nbsp|#160);'i",
			"'&(iexcl|#161);'i",
			"'&(cent|#162);'i",
			"'&(pound|#163);'i",
			"'&(copy|#169);'i",
			
			
			
			
			
	"'&#(\d+);'e");                    // 作为 PHP 代码运行
	$replace = array ("","","\\1","\"","&","<",">"," ",chr(161),chr(162),chr(163),chr(169),"chr(\\1)");
	$text = preg_replace ($search, $replace, $str);
	return $text;
}

function  curlInfo($toUrl,$urlParams){
	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_URL, $toUrl );//目标网址
	curl_setopt($ch, CURLOPT_POST,1);  //post方式传递
	curl_setopt($ch, CURLOPT_POSTFIELDS,$urlParams);
	// curl_setopt( $ch, CURLOPT_COOKIEJAR, $cookie );
	// curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
	// curl_setopt( $ch, CURLOPT_ENCODING, "" );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls
	// curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
	// curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
	// curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
	$content = curl_exec( $ch );
	$response = curl_getinfo( $ch );
	curl_close ( $ch );

	return $content;
}

/**
 * 根据接口名称获取调用wsdl地址
 * @param string $api
 * @return string
 */
function getWsdlByApi($api){
	$soap = C('SOAP_API');
	$path = $soap[$api];
	return C('SOAP_HOST').$path.'?wsdl';
}

/**
 * 将stdClass转换成对象
 * @param object $array
 * @return array
 */
function object2array($array) {
	if(is_object($array)) {
		$array = (array)$array;
	} if(is_array($array)) {
		foreach($array as $key=>$value) {
			$array[$key] = object2array($value);
		}
	}
	return $array;
}


function forbidden_filter($content){
	$wordArr = getForBiddenWord();
	foreach ( $wordArr as $row){
		if (false !== strstr($content,$row)) return false;
	}
	return true;
}

function getForBiddenWord(){
	$redis = service('Redis');
	$list = $redis->smembers('FORBIDDEN_WORD');
	if(empty($list)){
		$list= D('sys_forbidden_word')->getField('forbidden_words',true);
		foreach($list as $v){
			$redis->sadd('FORBIDDEN_WORD',$v);
		}
	}
	
	return $list;
	
}

/**
 * Redis缓存
 * @param string $name
 * @param string $value
 * @param number $expire
 */
function SS($name,$value='',$expire=0){
	return;
 	$cache = service('Redis');
 	if(''=== $value){ // 获取缓存
 		$data = $cache->get($name);
        $jsonData = json_decode($data,true);
        return ($jsonData === NULL) ? $data : $jsonData;	//检测是否为JSON数据 true 返回JSON解析数组, false返回源数据
    }elseif(is_null($value)) { // 删除缓存
        return $cache->remove($name);
    }else { // 缓存数据
    	$value  =  (is_object($value) || is_array($value)) ? json_encode($value) : $value;
        return $cache->set($name, $value, $expire);
    }
}


function getImgColor($path){
	$im  =  imagecreatefromjpeg($path);
	$rgb  =  imagecolorat ( $im ,  10 ,  15 );
	$r  = ( $rgb  >>  16 ) &  0xFF ;
	$g  = ( $rgb  >>  8 ) &  0xFF ;
	$b  =  $rgb  &  0xFF ;
	
	$arr = array('r'=>$r,'g'=>$g,'b'=>$b);
	return $arr;
}

function trimContent($content){
	$content = trim($content);
	$content = htmlspecialchars($content);
	$pattern = array('#','@','$',' ');
	$content = str_replace($pattern, '', $content);
	return $content;
}

/**
 * 用户名登录错误次数累计
 */
function setErrorLogin($errorName){
	$redis = service('Redis');
	
	$key = 'ERR_LOGIN_'.$errorName;
	$res = $redis->get($key);
	if($res){
		$redis->incr($key);
	}else{
		$end = strtotime(date('Y-m-d 23:59:59'));
		$exp = $end - time();
		$res = $redis->set($key,1,$exp);
	}
}


