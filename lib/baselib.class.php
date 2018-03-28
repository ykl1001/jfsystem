<?php
class baselib extends STpl{  
	//判断员工是否登录
	function isLoginStaff(){
		$iStaffId = (int)$_SESSION['staff_id'];
		if($iStaffId > 0){
			return true;
		} else {
			return false;
		}
	}

	//员工登录
	function staffLogin($strStaffname, $strPassword){
		$dbStaff = new SDb();
		$arrCondition = array();
		$arrCondition['staff_name'] = (string)$strStaffname;
		$arrCondition['password'] = (string)md5($strPassword);
		$strItems = 'staff_id,staff_name,staff_limit,roleid';
		if($arrResult = $dbStaff->selectOne('jf_staff'.SUF, $arrCondition, $strItems)){
			$_SESSION['staff_id'] = $arrResult['staff_id'];
			$_SESSION['staff_name'] = $arrResult['staff_name'];
			$_SESSION['staffLimit'] = $arrResult['staff_limit'];
            $_SESSION['roleid'] = $arrResult['roleid'];
			return true;
		} else {
			return false;
		}
	}

	//员工登出
	function staffLogout(){
		unset($_SESSION['staff_id']);
		unset($_SESSION['staff_name']);
		unset($_SESSION['staffLimit']);
	}

	//页面提示
	function alert($strMsg, $strUrl='', $sec=3, $iState=0){
		if(strlen($strUrl) === 0){
			$strUrl = $_SERVER['HTTP_REFERER'];
		}
		$arrParams = array('msg'=>$strMsg, 'url'=>$strUrl, 'state'=>$iState, 'sec'=>$sec);
		return $this->render('cp/alert.html', $arrParams);
	}

	//生成链接
	function makeUrl($method, $domain){
		$rooturl = ROOTURL;
		switch ($domain) {
			case 'main':
					return $rooturl .= 'main/'.$method;
				break;
			
			case 'cp':
				return $rooturl .= 'cp/'.$method;
				break;

			case 'reg':
				return $rooturl .= 'register/'.$method;
				break;
			case 'user':
				return $rooturl .= 'user/'.$method;
				break;
			default:
					return $_SERVER['HTTP_REFERER'];
				break;
		}
	}

	//生成翻页
	function pagebar($iPage=1, $iTotal=1, $iLimit=10, $strStyle='style1'){
		$strGetPath = '&';
		unset($_GET['p']);
		foreach ($_GET as $key => $value) {
			$strGetPath .= $key.'='.$value.'&';
		}
		$strGetPath = mb_substr($strGetPath, 0, ((int)(mb_strlen($strGetPath)-1)));
		$getPath = $strGetPath;
		$curPage = (int)$iPage;
		$totalPage = (int)$iTotal;
		$curLink = mb_substr($_SERVER['REQUEST_URI'], 0, mb_strpos($_SERVER['REQUEST_URI'],'?'));
		$arrParams['curPage'] = $curPage;
		$arrParams['getPath'] = $getPath;
		$arrParams['totalPage'] = $totalPage;
		$arrParams['curLink'] = $curLink;
		Tpl::getHtmlStr(true);
		$strPagebar = $this->render('common/pagebar.html', $arrParams);
		Tpl::getHtmlStr(false);
		return $strPagebar;
	}

	//封面图片处理
	function dealPic($strPicAddr, $iWidth=110, $iHeight=150){
		if(file_exists($strPicAddr)){
			$strExt = substr($strPicAddr, (strrpos($strPicAddr, '.')+1));
			$arrExt = array('jpg', 'png');
			if(!in_array($strExt, $arrExt)){
				return false;
			}
			list($iPicX, $iPicY) = getimagesize($strPicAddr);
			$iSaveX = $iPicX;
			$iSaveY = $iPicY;
			if(($iPicY > $iHeight) || ($iPicX > $iWidth)){
				if($iPicY > $iPicX){
					$fDivisor = $iHeight/$iPicY;
					$iSaveY = (int)($fDivisor*$iPicY);
					$iSaveX = (int)($fDivisor*$iPicX);
				} else {
					$fDivisor = $iWidth/$iPicX;
					$iSaveY = (int)($fDivisor*$iPicY);
					$iSaveX = (int)($fDivisor*$iPicX);
				}
			}
			
			switch ($strExt) {
				case 'jpg':
					$imgC = imagecreatetruecolor($iSaveX, $iSaveY);
					$img = imagecreatefromjpeg($strPicAddr);
					imagecopyresampled($imgC, $img, 0, 0, 0, 0, $iSaveX, $iSaveY, $iPicX, $iPicY);
					imagejpeg($imgC, $strPicAddr, 90);
					break;
				
				case 'png':
					imagecreatetruecolor($iSaveX, $iSaveY);
					imagepng($handlePic, $strPicAddr);
					break;
			}
			return true;
		} else {
			return false;
		}
	}

	//过滤攻击
	function safeData($data){
		foreach ($data as $key => $value) {
			if(is_array($value)){
				$data[$key] = $this->safeData($value);
			}
			if(is_string($value)){
				$data[$key] = $this->RemoveXSS($value);
			}
		}
		return $data;
	}

	//过滤Xss攻击
	function RemoveXSS($val) {  
	   // remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed  
	   // this prevents some character re-spacing such as <java\0script>  
	   // note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs  
	   $val = preg_replace('/([\x00-\x08,\x0b-\x0c,\x0e-\x19])/', '', $val);  
	     
	   // straight replacements, the user should never need these since they're normal characters  
	   // this prevents like <IMG SRC=@avascript:alert('XSS')>  
	   $search = 'abcdefghijklmnopqrstuvwxyz'; 
	   $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';  
	   $search .= '1234567890!@#$%^&*()'; 
	   $search .= '~`";:?+/={}[]-_|\'\\';
	   for ($i = 0; $i < strlen($search); $i++) { 
	      // ;? matches the ;, which is optional 
	      // 0{0,7} matches any padded zeros, which are optional and go up to 8 chars 
	    
	      // @ @ search for the hex values 
	      $val = preg_replace('/(&#[xX]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $val); // with a ; 
	      // @ @ 0{0,7} matches '0' zero to seven times  
	      $val = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $val); // with a ; 
	   } 
	    
	   // now the only remaining whitespace attacks are \t, \n, and \r 
	   $ra1 = Array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base'); 
	   $ra2 = Array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload'); 
	   $ra = array_merge($ra1, $ra2); 
	    
	   $found = true; // keep replacing as long as the previous round replaced something 
	   while ($found == true) { 
	      $val_before = $val; 
	      for ($i = 0; $i < sizeof($ra); $i++) { 
	         $pattern = '/'; 
	         for ($j = 0; $j < strlen($ra[$i]); $j++) { 
	            if ($j > 0) {
	               $pattern .= '(';
	               $pattern .= '(&#[xX]0{0,8}([9ab]);)';
	               $pattern .= '|';
	               $pattern .= '|(&#0{0,8}([9|10|13]);)';
	               $pattern .= ')*';
	            }
	            $pattern .= $ra[$i][$j];
	         }
	         $pattern .= '/i';
	         $replacement = substr($ra[$i], 0, 2).'<x>'.substr($ra[$i], 2); // add in <> to nerf the tag  
	         $val = preg_replace($pattern, $replacement, $val); // filter out the hex tags  
	         if ($val_before == $val) {
	            // no replacements were made, so exit the loop  
	            $found = false;
	         }
	      }
	   }
	   return $val;
	}


	/*数组排序*/
	function sortArray(&$arrData, $desc=1, $field=false){
		if($field !== false){
			$arrSorts = array();
			for($i=count($arrData); $i>0 ;) {
				$pick = reset($arrData);
				$ak = key($arrData);
				for($tmp=next($arrData); $tmp!==false;){
					switch ($desc) {
						case 1:
							if($pick[$field] < $tmp[$field]){
								$pick = $tmp;
								$ak = key($arrData);
							}
							break;
						
						default:
							if($pick[$field] > $tmp[$field]){
								$pick = $tmp;
								$ak = key($arrData);
							}
							break;
					}
					$tmp = next($arrData);
				}
				unset($arrData[$ak]);
				$arrSorts[] = $pick;
				$i--;
			}
			$arrData = $arrSorts;
		} else {
			if($desc === 1){
				rsort($arrData);
			} else {
				sort($arrData);
			}
		}
		return;
	}

	function ubb2html($content)
	{
	  $tmpstr = '';
	  $tcontent = $content;
	  if (!empty($tcontent))
	  {
	    $tcontent = str_replace('&', '&amp;', $tcontent);
	    $tcontent = str_replace('>', '&gt;', $tcontent);
	    $tcontent = str_replace('<', '&lt;', $tcontent);
	    $tcontent = str_replace('"', '&quot;', $tcontent);
	    $tcontent = str_replace('&amp;#91;', '&#91;', $tcontent);
	    $tcontent = str_replace('&amp;#93;', '&#93;', $tcontent);
	    $tcontent = preg_replace("/\[br\]/is", "<br />", $tcontent);
	    //******************************************************************
	    $tRegexAry = array();
	    $tRegexAry[0] = array("\[p\]([^\[]*?)\[\/p\]", "\\1<br />");
	    $tRegexAry[1] = array("\[b\]([^\[]*?)\[\/b\]", "<b>\\1</b>");
	    $tRegexAry[2] = array("\[i\]([^\[]*?)\[\/i\]", "<i>\\1</i>");
	    $tRegexAry[3] = array("\[u\]([^\[]*?)\[\/u\]", "<u>\\1</u>");
	    $tRegexAry[4] = array("\[ol\]([^\[]*?)\[\/ol\]", "<ol>\\1</ol>");
	    $tRegexAry[5] = array("\[ul\]([^\[]*?)\[\/ul\]", "<ul>\\1</ul>");
	    $tRegexAry[6] = array("\[li\]([^\[]*?)\[\/li\]", "<li>\\1</li>");
	    $tRegexAry[7] = array("\[code\]([^\[]*?)\[\/code\]", "<div class=\"ubb_code\">\\1</div>");
	    $tRegexAry[8] = array("\[quote\]([^\[]*?)\[\/quote\]", "<div class=\"ubb_quote\">\\1</div>");
	    $tRegexAry[9] = array("\[color=([^\]]*)\]([^\[]*?)\[\/color\]", "<font style=\"color: \\1\">\\2</font>");
	    $tRegexAry[10] = array("\[hilitecolor=([^\]]*)\]([^\[]*?)\[\/hilitecolor\]", "<font style=\"background-color: \\1\">\\2</font>");
	    $tRegexAry[11] = array("\[align=([^\]]*)\]([^\[]*?)\[\/align\]", "<div style=\"text-align: \\1\">\\2</div>");
	    $tRegexAry[12] = array("\[url=([^\]]*)\]([^\[]*?)\[\/url\]", "<a href=\"\\1\">\\2</a>");
	    $tRegexAry[13] = array("\[img\]([^\[]*?)\[\/img\]", "<a href=\"\\1\" target=\"_blank\"><img src=\"\\1\" /></a>");
	    //******************************************************************
	    $tState = true;
	    while($tState)
	    {
	      $tState = false;
	      for ($ti = 0; $ti < count($tRegexAry); $ti ++)
	      {
	        $tnRegexString = "/" . $tRegexAry[$ti][0] . "/is";
	        if (preg_match($tnRegexString, $tcontent))
	        {
	          $tState = true;
	          $tcontent = preg_replace($tnRegexString, $tRegexAry[$ti][1], $tcontent);
	        }
	      }
	    }
	    //******************************************************************
	    $tmpstr = $tcontent;
	  }
	  return $tmpstr;
	}
	/*
	*去掉所有空格
	*/
	function delallspe($str){
		$str = trim($str);
	    $str = preg_replace('/\s/',"",$str);
	    return $str;
	}
}
?>
