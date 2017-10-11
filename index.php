<?php
	require_once("config.php");
	SlightPHP::setAppDir(SELF_LIB);
	SlightPHP::setDefaultZone("main");
	SlightPHP::setDefaultPage("main");
	SlightPHP::setDefaultEntry("index");
	SlightPHP::setSplitFlag("-_.");

	if(($r=SlightPHP::run())===false){
		echo "404 error";
	}
?>
