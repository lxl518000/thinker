<?php if (!defined('THINK_PATH')) exit();?> <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>欢迎登录<?php echo (C("SITE_TITLE")); ?></title>
<link href="/Public/young/css/style.css" rel="stylesheet" type="text/css" />
<link href="/Public/images/favicon.ico" rel="shortcut icon" />
	<script type="text/javascript" src="/Public/js/jquery.js" ></script>
	<script type="text/javascript" src="/Public/acc/js/common.js"></script>
	<script type="text/javascript" src="/Public/acc/js/CheckForm.js"></script>
	<script type="text/javascript" src="/Public/Vendor/layer/layer.js"></script>
	
<style>
.systemtitle {
    width: 100%;
    height: 71px;
    margin-top: 120px;
    text-align: center;
	font-size:42px;
	font-weight:bold;
	color:#fff;
}
 </style>	
</head>

<body >

<div class="loginbody"> 

<span class='systemtitle'>
	<?php echo (C("SITE_TITLE")); ?>
</span>

   <form method="post" id="form1" name="form1"  action="<?php echo U('dologin');?>">
  <div class="loginbox loginbox1">
 	<h3>用户登录</h3>
    <ul>
      <li>
        <input name="cellphone" type="text" class="loginphone text" placeholder="手机号" />
      </li>
      <li>
        <input name="password" type="password" class="loginpwd text" placeholder="密码" />
      </li>
      <li class="yzm"> <span>
        <input name="yzm" type="text" placeholder="验证码"/>
        </span><cite><img src="<?php echo U('verify');?>" id="loginyzm"  onclick="flush(this)";> </cite> </li>
      <li>
        <input name="" type="button" class="loginbtn" value="登录"  onclick="formcheckLogin('form1','<?php echo U('dologin');?>','subFun')"  />
      <!-- 
            <label><a href="<?php echo U('Public/register');?>" class="sj_zc">注册</a></label>
  	     <label><a href="<?php echo U('Public/forget');?>" class="sj_zc">忘记密码？</a></label> -->
  
      </li>
    </ul>
  </div>
  </form>
</div>

<img src="/Public/acc/images/bg.jpg" style="position:absolute; top:0; left:0; bottom:0;" height="100%" width="100%">
</body>
</html>
<script type="text/javascript">


document.onkeydown = function(e){ 
    var ev = document.all ? window.event : e;
    if(ev.keyCode==13) {
    	formcheckLogin('form1','<?php echo U('dologin');?>','subFun')
	/* 	if($('input[name="username"]').val()!='' && $('input[name="password"]').val()!=''){
			// formchecklogin('form1','<?php echo U('userlogin');?>','subFun');
			//formcheck('form1','<?php echo U('login');?>','subFun');
		} */
     }
}
function subFun(status,info,data){
	if(status == 1){
		 document.location="<?php echo U('Index/index');?>";
		 return;
		// UsubFun(true,info,function(){ document.location="<?php echo U('Index/index');?>"; },"登录成功")
	  }
}

function flush(obj){
	var url = "<?php echo U('verify');?>?t="+Math.random();
	$(obj).attr('src',url);
}
	


	function getCookie(name) 
	{ 
	    var arr,reg=new RegExp("(^| )"+name+"=([^;]*)(;|$)");
	 
	    if(arr=document.cookie.match(reg))
	 
	        return unescape(arr[2]); 
	    else 
	        return null; 
	} 

	function setCookie(name,value) 
	{ 
	    var Days = 30; 
	    var exp = new Date(); 
	    exp.setTime(exp.getTime() + Days*24*60*60*1000); 
	    document.cookie = name + "="+ escape (value) + ";expires=" + exp.toGMTString(); 
	} 
	
	
	
	
	
	
</script>