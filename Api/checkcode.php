<?php
defined('APP_NAME') or exit('No permission resources.'); 

$class = 'Session'. ucwords(strtolower(C('SESSION_TYPE')));
require_cache(EXTEND_PATH.'Driver/Session/'.$class.'.class.php');
$hander = new $class();
$hander->execute();//如果没有在construct中设置session_start()则必须手动开启
session_start();//这也是必须的，打开session，必须在session_set_save_handler后面执行
$checkcode = vendor('Pc.Checkcode','',1);

if (isset ( $_GET ['codeLen'] ) && intval ( $_GET ['codeLen'] ))
	$checkcode->codeLen = intval ( $_GET ['codeLen'] );
if ($checkcode->codeLen > 8 || $checkcode->codeLen < 2) {
	$checkcode->codeLen = 4;
}
if (isset ( $_GET ['fontSize'] ) && intval ( $_GET ['fontSize'] ))
	$checkcode->fontSize = intval ( $_GET ['fontSize'] );
if (isset ( $_GET ['width'] ) && intval ( $_GET ['width'] ))
	$checkcode->width = intval ( $_GET ['width'] );
if ($checkcode->width <= 0) {
	$checkcode->width = 130;
}
if (isset ( $_GET ['height'] ) && intval ( $_GET ['height'] ))
	$checkcode->height = intval ( $_GET ['height'] );
if ($checkcode->height <= 0) {
	$checkcode->height = 50;
}
if (isset ( $_GET ['fontColor'] ) && trim ( urldecode ( $_GET ['fontColor'] ) ) && preg_match ( '/(^#[a-z0-9]{6}$)/im', trim ( urldecode ( $_GET ['fontColor'] ) ) ))
	$checkcode->fontColor = trim ( urldecode ( $_GET ['fontColor'] ) );
if (isset ( $_GET ['background'] ) && trim ( urldecode ( $_GET ['background'] ) ) && preg_match ( '/(^#[a-z0-9]{6}$)/im', trim ( urldecode ( $_GET ['background'] ) ) ))
	$checkcode->background = trim ( urldecode ( $_GET ['background'] ) );
$checkcode->doimage ();
$_SESSION['code']=$checkcode->getCode();