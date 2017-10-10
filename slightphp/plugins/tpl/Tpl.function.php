<?php
function tpl_function_tostring($mixed){
	return var_export($mixed,true);
}
function tpl_function_part($path){
	return !empty($path)?SlightPHP::run($path):"";
}
function tpl_function_include($tpl){
	return Tpl::fetch($tpl);
}

//获得链接
function tpl_function_geturl($url, $domain=""){
	$rooturl = ROOTURL;
	switch ($domain) {
		case 'main':
				return $rooturl .= 'main/'.$url;
			break;
		
		case 'cp':
			return $rooturl .= 'cp/'.$url;
			break;

		case 'reg':
			return $rooturl .= 'register/'.$url;
			break;
		case 'user':
			return $rooturl .= 'user/'.$url;
			break;
		default:
				return $_SERVER['REQUEST_URI'].'../../'.$url;
			break;
	}
}

//得到图片地址
function tpl_function_getpicurl($picname, $type='jpg', $level='movie'){
	$picurl = PICURL;
	return $picurl.$level.'/'.$picname.'.'.$type;
}

//截取字部分文字
function tpl_function_cutstr($str, $len=10, $connent='...'){
	if(mb_strlen($str,'UTF8') > $len){
		return mb_substr($str, 0, $len, 'UTF-8').$connent;
	}
	return $str;
}

//获得js、css地址
function tpl_function_getfile($type, $filename){
	$rootUrl = ROOTURL;
	switch ($type) {
		case 'js':
			$rootUrl.='js/'.$filename.'.js';
			break;
		case 'css':
			$rootUrl.='css/'.$filename.'.css';
			break;
		default:
			# code...
			break;
	}
	return $rootUrl;
}