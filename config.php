<?php
    error_reporting(E_ALL);
	session_set_cookie_params(2592000,'/','',false,false);
	session_start();
	date_default_timezone_set("Asia/Shanghai");
	define("ROOT",				dirname(__FILE__).'/');
	define("ROOT_SLIGHTPHP",	ROOT."slightphp/");
	define("ROOT_PLIGUNS",		ROOT."slightphp/plugins");
	define("SELF_LIB",			ROOT);
	require_once(ROOT_SLIGHTPHP."SlightPHP.php");
	require_once("constant.php");

	//SRoute::setConfigFile("route.ini");
	function __autoload($class){
		if($class{0}=="S"){
			$file = ROOT_PLIGUNS."/$class.class.php";
		}else{
			$file = SlightPHP::$appDir."/lib/".$class.".class.php";
		}
		if(file_exists($file)) return require_once($file);
	}
	spl_autoload_register('__autoload');
?>
