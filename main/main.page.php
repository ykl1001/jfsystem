<?php
class main_main extends baselib{

	/*
	*初始化
	*/
	function __construct(){
		$this->arrParamas = array('staffLimit'=>isset($_SESSION['staffLimit']) ? $_SESSION['staffLimit'] : 0);
        $iStaffId = (int)$_SESSION['staff_id'];
		if ($iStaffId && $_SESSION['staffLimit'] != 255) {
		    // 获取用户菜单列表
            $db = new SDb();
            $aids_map = $db->select('permission_map'.SUF,array('roleid'=>$_SESSION['roleid']), 'aid')->items;
            $aids = array();
            if ($aids_map) {
                foreach ($aids_map as $aid_map) {
                    $aids[] = $aid_map['aid'];
                }
            }
            $this->arrParamas['menu_list'] = $db->select('permission'.SUF,'aid in ('.implode(',',$aids).') and parent_id=0','','','ordersort desc')->items;
        }
	}

	function pageIndex(){
		if($this->isLoginStaff()){
			if($_SESSION['staffLimit'] > 0){
					return header('Location:'.$this->makeUrl('main/stafflist', 'main'));
				
			} else {
				return header('Location:'.$this->makeUrl('main/scoreinfo', 'main').'?sid='.$_SESSION['staff_id']);
			}
		} else {
			return header('Location:'.$this->makeUrl('main/loginpage', 'main'));
		}
	}

	//员工登录
	function pageLogin(){
		if($this->isLoginStaff()){
			return header('Location:'.$this->makeUrl('main/index', 'main'));
		} 
		$post = $this->safeData($_POST);
		if(!$this->staffLogin($post['staffname'], $post['password'])){
			return $this->alert('登录名或登录密码错误，请重新输入', $this->makeUrl('main/loginpage', 'main'));
		}
		if($_SESSION['staffLimit'] > 0){
			if(strcmp($_COOKIE['AUTOSCORE'], 'run') != 0){
				$this->runauto();	
			}
			return $this->alert('登录成功', $this->makeUrl('main/index', 'main'));
		} else {
			return $this->alert('登录成功', $this->makeUrl('main/scoreinfo', 'main').'?sid='.$_SESSION['staff_id']);
		}
	}

	//新增员工信息
	function pageAddStaff(){
		if($_SESSION['staffLimit'] > 0){
			$db = new SDb();
			$teams = $db->select('jf_position'.SUF, 'parent_id=0')->items;
			$this->arrParamas['teams'] = $teams;
            $this->arrParamas['roles'] = $db->select('permission_role'.SUF)->items;
			return $this->render('cp/addstaff.html', $this->arrParamas);
		} else {
			return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
		}
	}

	//编辑员工信息
	function pageEditStaff(){
		if($_SESSION['staffLimit'] > 0){
			$staff_id = (int)$_GET['sid'];
			$db = new SDb();
			$teams = $db->select('jf_position'.SUF, 'parent_id=0')->items;
			$this->arrParamas['teams'] = $teams;
			$arrStaff = $db->selectOne('jf_staff'.SUF, array('staff_id'=>$staff_id), 'staff_name,staff_sex,joinymd,pos_level,score,staff_level,staff_limit,password,staff_department'.
									   ',staff_position,roleid');
			if(!$arrStaff) {
				return $this->alert('员工信息不存在，请核对', $this->makeUrl('main/stafflist', 'main'));
			}
			$poses = $db->select('jf_position'.SUF, 'parent_id='.$arrStaff['staff_department']);
			$this->arrParamas['poses'] = $poses->items;
			$_SESSION['upstaffid'] = $staff_id;
			$this->arrParamas['staff'] = $arrStaff;
            $this->arrParamas['roles'] = $db->select('permission_role'.SUF)->items;
			return $this->render('cp/editstaff.html', $this->arrParamas);
		} else {
			return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
		}
	}

	//更新员工信息
	function pageUpdateStaff(){
		if($_SESSION['staffLimit'] > 0){
			$post = $this->safeData($_POST);
			$staff_name = (string)$this->delallspe($post['staffName']);
			$staff_sex = (int)$post['sex'];
			$joinymd = (string)$post['joinymd'];
			$jointime = strtotime($joinymd);
			$score = (int)$post['score'];
			$staff_department = (int)$post['steam'];
			$staff_position = (int)$post['spos'];
			$staff_level ='';
			$staff_limit = (int)$post['staffLimit'];
			$password = (string)$this->delallspe($post['password']);
			$datetime = time();
			$dateymd = date('Y-m-d', $datetime);
			$strUpdate = '';
			$posLevel = (int)$post['posLevel'];
            $roleid = (int)$post['roleid'];
			$db = new SDb();
			if(strlen($staff_name) <= 0){
				return $this->alert('员工姓名不能为空');
			} else if($staff_sex !== 0 && $staff_sex !== 1) {
				return $this->alert('员工性别只能是男或者女，请不要乱来好么？');
			} else if(!$db->selectOne('jf_position'.SUF, array('pos_id'=>$staff_position, 'parent_id'=>$staff_department))) {
				return $this->alert('请选择正确的部门');
			} else if ($staff_limit !== 0 && $staff_limit !== 1){
				return $this->alert('是谁给你的特权？不要乱来。');
			}
			$plen = strlen($password);
			if($plen>0 && $plen<6){
				return $this->alert('密码不能少于6位');
			} else if(strlen($password) >= 6){
				$strUpdate = 'password="'.md5($password).'",';
			}
			$arrPos = $db->selectOne('jf_position'.SUF, array('pos_id'=>$staff_position));
			$staff_level = $arrPos['pos_name'];
			$strUpdate .= 'staff_name='.'"'.$staff_name.'"'.',staff_sex='.$staff_sex.',joinymd='.'"'.$joinymd.'"'.',jointime='.$jointime.',score='.$score
						 .',staff_level="'.$staff_level.'"'.',staff_department='.$staff_department.',staff_position='.$staff_position.',staff_limit='.
						 $staff_limit.',datetime='.$datetime.',dateymd="'.$dateymd.'"'.',pos_level='.$posLevel.',roleid='.$roleid;
			if(!$db->update('jf_staff'.SUF, array('staff_id'=>$_SESSION['upstaffid']), $strUpdate)){
				return $this->alert('操作失败请重试');
			}
			return $this->alert('修改成功', $this->makeUrl('main/stafflist', 'main'));
		} else {
			return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
		}
	}

	//添加员工信息
	function pageSaveStaff(){
		if($_SESSION['staffLimit'] > 0){
			$post = $this->safeData($_POST);
			$staff_name = (string)$this->delallspe($post['staffName']);
			$staff_sex = (int)$post['sex'];
			$joinymd = (string)$post['joinymd'];
			$jointime = strtotime($joinymd);
			$score = (int)$post['score'];
			$staff_department = (int)$post['steam'];
			$staff_position = (int)$post['spos'];
			$staff_level ='';
			$staff_limit = (int)$post['staffLimit'];
			$password = (string)$this->delallspe($post['password']);
			$datetime = time();
			$dateymd = date('Y-m-d', $datetime);
			$posLevel = (int)$post['posLevel'];
            $roleid = (int)$post['roleid'];
			$db = new SDb();
			if(strlen($staff_name) <= 0){
				return $this->alert('员工姓名不能为空');
			} else if($staff_sex !== 0 && $staff_sex !== 1) {
				return $this->alert('员工性别只能是男或者女，请不要乱来好么？');
			} else if(!$db->selectOne('jf_position'.SUF, array('pos_id'=>$staff_position, 'parent_id'=>$staff_department))) {
				return $this->alert('请选择正确的部门');
			} else if($staff_limit !== 0 && $staff_limit !== 1){
				return $this->alert('是谁给你的特权？不要乱来。');
			} else if(strlen($password) < 6){
				return $this->alert('密码不能少于6位');
			}
			$arrPos = $db->selectOne('jf_position'.SUF, array('pos_id'=>$staff_position));
			$staff_level = $arrPos['pos_name'];
			$strInsert = 'staff_name='.'"'.$staff_name.'"'.',password="'.md5($password).'"'.',staff_sex='.$staff_sex.',joinymd='.'"'.$joinymd.'"'.',jointime='.$jointime.',score='.$score
						 .',staff_level="'.$staff_level.'"'.',staff_department='.$staff_department.',staff_position='.$staff_position.',staff_limit='.
						 $staff_limit.',datetime='.$datetime.',dateymd="'.$dateymd.'"'.',pos_level='.$posLevel.',roleid='.$roleid;
			if($db->insert('jf_staff'.SUF, $strInsert)){
				return $this->alert('添加员工成功', $this->makeUrl('main/addstaff', 'main'));
			} else {
				return $this->alert('添加员工失败', $this->makeUrl('main/addstaff', 'main'));
			}
		} else {
			return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
		}
	}

	//加减分项目列表
	function pageScoreList() {
		if($_SESSION['staffLimit'] > 0){
			$page = (int)$_GET['p']>1 ? $_GET['p']:1;
			$limit = 9;
			$arrCondition = array();
			$db = new SDb();
			$db->setLimit($limit);
			$db->setPage($page);
			$db->setCount(true);
			$arrResults = $db->select('jf_score_detail'.SUF, $arrCondition);
			if($arrResults->totalPage>1){
				$this->arrParamas['pagebar'] = $this->pagebar($page, $arrResults->totalPage, $limit);
			}
			$this->arrParamas['scores'] = $arrResults->items;
			$scoreTypes = $db->select('jf_score_class'.SUF)->items;
			$tmp = array();
			foreach($scoreTypes as $val){
				$tmp[$val['class_id']] = $val['class_name'];
			}
			$scoreTypes = $tmp;
			$this->arrParamas['scoreTypes'] = $scoreTypes;
			return $this->render('cp/scorelist.html', $this->arrParamas);
		} else {
			return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
		}
	}

	//添加新的积分项目
	function pageNewScore(){
		if($_SESSION['staffLimit'] > 0){
			$db = new SDb();
			$arrCondition = array('parent_id'=>0);
			$scoreTypes = $db->select('jf_score_class'.SUF, $arrCondition)->items;
			$this->arrParamas['scoreTypes'] = $scoreTypes;
			return $this->render('cp/newscore.html', $this->arrParamas);
		} else {
			return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
		}
	}

	//保存积分项目
	function pageAddScore(){
		if($_SESSION['staffLimit'] > 0){
			$post = $this->safeData($_POST);
			$score_class = (int)$post['score_class'][1]>0 ? (int)$post['score_class'][1]:(int)$post['score_class'][0];
			$score_code = strtoupper($post['score_code']);
			$detail = (string)$post['detail'];
			$score = (int)$post['score'];
			$score_max = (int)$post['scoreMax'];
			$type = (int)$post['type'];
			switch ($type) {
				case 1:
				case 2:
				case 3:
					break;
				
				default:
					$type = 1;
					break;
			}

			if($type === 2){
				$score = $post['min_score'];
				if(($score>0&&$score_max<0) || ($score<0&&$score_max>0)){
					return $this->alert('请保持浮动分数同时为正或同时为负','',5);
				}else if($score===0 || $score_max===0){
					return $this->alert('请保证浮动分数最大值最小值都不为0');
				} else if($score > $score_max){
					$tmp = $score_max;
					$score_max = $socre;
					$score = $tmp;
				}
			} else {
				$score_max = 0;
			}

			$db = new SDb();
			if($db->selectOne('jf_score_detail'.SUF, array('score_code'=>$score_code))){
				return $this->alert('积分代码已存在');
			}else if($score_class === 0){
				return $this->alert('请选择一个积分类型，如果还没有积分类型请添加');
			}else if(!$db->selectOne('jf_score_class'.SUF, array('class_id'=>$score_class))){
				return $this->alert('不存在的积分类型，请重试');
			} else if((int)mb_strlen($score_code, 'utf-8') === 0){
				return $this->alert("请输入积分代码");
			} else if((int)mb_strlen($detail, 'utf-8') === 0){
				return $this->alert('请输入加分/扣分原因');
			} else if($score === 0){
				return $this->alert('请输入分值');
			}
			
			$strInsert = 'class_id='.$score_class.',score_code="'.$score_code.'",detail="'.$detail.'",score='.$score.',staff_id='.$_SESSION['staff_id'].
						 ',staff_name="'.$_SESSION['staff_name'].'",datetime='.time().',type='.$type.',score_max='.$score_max;
			if($db->insert('jf_score_detail'.SUF, $strInsert)){
				return $this->alert('添加成功', $this->makeUrl('main/scorelist', 'main'));
			} else {
				return $this->alert('添加失败');
			}

		} else {
			return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
		}
	}

	//编辑积分项目
	function pageEditScore(){
		if($_SESSION['staffLimit'] > 0){
			$sid = (int)$_GET['sid'];
			$_SESSION['score_detail_sid'] = $sid;
			if($sid === 0){
				return $this->alert('错误编号');
			}
			$db = new SDb();
			$scoreDetail = $db->selectOne('jf_score_detail'.SUF, array('score_detail_id'=>$sid));
			if(!$scoreDetail){
				return $this->alert("不存在的积分详情");
			}
			
			$c2 = $db->selectOne('jf_score_class'.SUF, array('class_id'=>$scoreDetail['class_id']));
			if($c2['parent_id'] > 0){
				$this->arrParamas['cid'] = $scoreDetail['class_id'];
				$arrClasses = $db->select('jf_score_class'.SUF, array('parent_id'=>$c2['parent_id']))->items;
				if(count($arrClasses) > 0){
					$this->arrParamas['classes'] = $arrClasses;
				}
				$scoreDetail['class_id'] = $c2['parent_id'];
			} else {
				$arrClasses = $db->select('jf_score_class'.SUF, array('parent_id'=>$scoreDetail['class_id']))->items;
				if(count($arrClasses) > 0){
					$this->arrParamas['classes'] = $arrClasses;
				}
			}
			$this->arrParamas['scoreDetail'] = $scoreDetail;
			$scoreTypes = $db->select('jf_score_class'.SUF, array('parent_id'=>0))->items;
			$this->arrParamas['scoreTypes'] = $scoreTypes;
			return $this->render('cp/editscore.html', $this->arrParamas);
		} else {
			return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
		}
	}

	//修改积分项目
	function pageUpScore(){
		if($_SESSION['staffLimit'] > 0){
			$post = $this->safeData($_POST);
			$score_class = (int)$post['score_class'][1]>0 ? (int)$post['score_class'][1]:(int)$post['score_class'][0];
			$score_code = strtoupper($post['score_code']);
			$detail = (string)$post['detail'];
			$score = (int)$post['score'];

			$score_max = (int)$post['scoreMax'];
			$type = (int)$post['type'];
			switch ($type) {
				case 1:
				case 2:
				case 3:
					break;
				
				default:
					$type = 1;
					break;
			}

			if($type === 2){
				$score = $post['min_score'];
				if(($score>0&&$score_max<0) || ($score<0&&$score_max>0)){
					return $this->alert('请保持浮动分数同时为正或同时为负','',5);
				}else if($score===0 || $score_max===0){
					return $this->alert('请保证浮动分数最大值最小值都不为0');
				} else if($score > $score_max){
					$tmp = $score_max;
					$score_max = $socre;
					$score = $tmp;
				}
			} else {
				$score_max = 0;
			}
			$arrCodeCondition = array('score_code'=>$score_code);
			$arrCodeCondition[] = 'score_detail_id != '.$_SESSION['score_detail_sid'];
			$db = new SDb();
			if($db->selectOne('jf_score_detail'.SUF, $arrCodeCondition)){
				return $this->alert('积分代码已存在');
			} else if($score_class === 0){
				return $this->alert('请选择一个积分类型，如果还没有积分类型请添加');
			}else if(!$db->selectOne('jf_score_class'.SUF, array('class_id'=>$score_class))){
				return $this->alert('不存在的积分类型，请重试');
			} else if((int)mb_strlen($score_code, 'utf-8') === 0){
				return $this->alert("请输入积分代码");
			} else if((int)mb_strlen($detail, 'utf-8') === 0){
				return $this->alert('请输入加分/扣分原因');
			} else if($score === 0){
				return $this->alert('请输入分值');
			}

			$strUp = 'class_id='.$score_class.',score_code="'.$score_code.'",detail="'.$detail.'",score='.$score.',staff_id='.$_SESSION['staff_id'].
						 ',staff_name="'.$_SESSION['staff_name'].'",datetime='.time().',type='.$type.',score_max='.$score_max;
			if($db->update('jf_score_detail'.SUF, array('score_detail_id'=>$_SESSION['score_detail_sid']), $strUp)){
				$_SESSION['score_detail_sid'] = 0;
				return $this->alert('修改成功', $this->makeUrl('main/scorelist', 'main'));
			} else {
				return $this->alert('修改失败');
			}

		} else {
			return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
		}
	}

	//删除积分项目
	function pageDelScore(){
		if($_SESSION['staffLimit'] > 10){
			$sid = (int)$_GET['sid'];
			if($sid === 0){
				return $this->alert('错误编号');
			}
			$db = new SDb();
			if($db->delete('jf_score_detail'.SUF, array('score_detail_id'=>$sid))){
				return $this->alert('删除成功', $this->makeUrl('main/scorelist', 'main'));
			} else {
				return $this->alert('删除失败');
			}

		} else {
			return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
		}
	}

	//积分类型
	function pageScoreClassList(){
		if($_SESSION['staffLimit'] > 0){
			$page = (int)$_GET['p']>1 ? $_GET['p']:1;
			$limit = 9;
			$arrCondition = array();
			$db = new SDb();
			$db->setLimit($limit);
			$db->setPage($page);
			$db->setCount(true);
			$arrResults = $db->select('jf_score_class'.SUF, $arrCondition);
			if($arrResults->totalPage>1){
				$this->arrParamas['pagebar'] = $this->pagebar($page, $arrResults->totalPage, $limit);
			}
			$this->arrParamas['classes'] = $arrResults->items;
			return $this->render('cp/scoreclasslist.html', $this->arrParamas);
		} else {
			return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
		}
	}

	//添加新类型
	function pageNewClass(){
		if($_SESSION['staffLimit'] > 0){
			$db = new SDb();
			$arrCondition = array('parent_id'=>0);
			$arrClasses = $db->select('jf_score_class'.SUF, $arrCondition)->items;
			$this->arrParamas['classes'] = $arrClasses;
			return $this->render('cp/newclass.html', $this->arrParamas);
		} else {
			return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
		}
	}

	//保存新类型
	function pageAddClass(){
		if($_SESSION['staffLimit'] > 0){
			$post = $this->safeData($_POST);
			$class_name = (string)$post['className'];
			$parent_id = (int)$post['parentId'];
			if(!strlen($class_name) > 0){
				return $this->alert('类型名称不能为空', $this->makeUrl('main/newclass', 'main'));
			}
			$db = new SDb();
			$strInsert = 'class_name="'.$class_name.'"'.',parent_id='.$parent_id;
			if(!$db->insert('jf_score_class'.SUF, $strInsert)){
				return $this->alert('保存出错请重试', $this->makeUrl('main/newclass', 'main'));
			} else {
				return $this->alert('保存成功', $this->makeUrl('main/scoreclasslist', 'main'));
			}
			return $this->render('cp/newclass.html');
		} else {
			return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
		}
	}

	//编辑类型
	function pageEditClass(){
		if($_SESSION['staffLimit'] > 0){
			$cid = (int)$_GET['cid'];
			if(!$cid > 0){
				return $this->alert('错误id,请重试', $this->makeUrl('main/scoreclasslist', 'main'));
			}
			$db = new SDb();
			if(!$arrResult = $db->selectOne('jf_score_class'.SUF, array('class_id'=>$cid))){
				return $this->alert('不存在的类型,请重试', $this->makeUrl('main/scoreclasslist', 'main'));
			}
			$arrCondition = array('parent_id'=>0);
			$arrCondition[] = 'class_id!='.$cid;
			$arrClasses = $db->select('jf_score_class'.SUF, $arrCondition)->items;
			$this->arrParamas['class'] = $arrResult;
			$this->arrParamas['classes'] = $arrClasses;
			return $this->render('cp/editclass.html', $this->arrParamas);
		} else {
			return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
		}
	}

	//更新类型
	function pageUpdateClass(){
		if($_SESSION['staffLimit'] > 0){
			$post = $this->safeData($_POST);
			$cid = (int)$post['classId'];
			$class_name = (string)$post['className'];
			$parent_id = (int)$post['parentId'];
			if(!$cid > 0){
				return $this->alert('错误id,请重试', $this->makeUrl('main/scoreclasslist', 'main'));
			} else if(!strlen($class_name) > 0){
				return $this->alert('类型名称不能为空', $this->makeUrl('main/newclass', 'main'));
			}
			$db = new SDb();
			$strInsert = 'class_name="'.$class_name.'"'.',parent_id='.$parent_id;
			if(!$db->update('jf_score_class'.SUF, array('class_id'=>$cid), $strInsert)){
				return $this->alert('保存失败,请重试', $this->makeUrl('main/scoreclasslist', 'main'));
			}
			return $this->alert('修改成功,请重试', $this->makeUrl('main/scoreclasslist', 'main'));
		} else {
			return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
		}
	}

	//删除类型
	function pageDelClass(){
		if($_SESSION['staffLimit'] > 0){
			$cid = (int)$_GET['cid'];
			if(!$cid > 0){
				return $this->alert('错误id,请重试', $this->makeUrl('main/scoreclasslist', 'main'));
			}
			$db = new SDb();
			if(!$db->delete('jf_score_class'.SUF, array('class_id'=>$cid))){
				return $this->alert('删除失败,请重试', $this->makeUrl('main/scoreclasslist', 'main'));
			}
			return $this->alert('删除成功,请重试', $this->makeUrl('main/scoreclasslist', 'main'));
		} else {
			return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
		}
	}

	//查看员工列表
	function pageStaffList(){
		if($_SESSION['staffLimit'] > 0){
			$get = $this->safeData($_GET);
			$page = (int)$get['p']>1 ? $get['p']:1;
			$limit = 9;
			$st = (int)$get['st'];
			$e = (int)$get['e'];
			$e = $e>0?1:0;
			$this->arrParamas['e'] = $e;
			$sort = (int)$get['sort']===1||(int)$get['sort']===2?(int)$get['sort']:1;
			$arrCondition = array();
			if($st>0){
				$arrCondition['staff_department']= $st;
			}
			$this->arrParamas['st'] = $st;
			$arrCondition[] = 'staff_id != 10000';
			$arrCondition['is_leave'] = $e;
			$orderby = ('score '.($sort===2?'asc':'desc'));
			$this->arrParamas['sort'] = $sort;
			$db = new SDb();
			$db->setLimit($limit);
			$db->setPage($page);
			$db->setCount(true);
			$arrResults = $db->select('jf_staff'.SUF, $arrCondition, '', '', $orderby);
			if($arrResults->totalPage>1){
				$this->arrParamas['pagebar'] = $this->pagebar($page, $arrResults->totalPage, $limit);
			}
			$this->arrParamas['staffs'] = $arrResults->items;
			$arrCondition = array('parent_id'=>0);
			$arrDepartments = $db->select('jf_position'.SUF,$arrCondition)->items;
			$this->arrParamas['departments'] = $arrDepartments;
            $this->arrParamas['roles'] = $db->select('permission_role'.SUF)->items;

			return $this->render('cp/stafflist.html', $this->arrParamas);
		} else {
			return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
		}
	}

	//查看积分报表
	function pageScoreTable(){
		if($_SESSION['staffLimit'] > 0){
			$get = $this->safeData($_GET);
			$page = (int)$get['p']>1 ? $get['p']:1;
			$rank = ($page-1)*9+1;
			$spos = (int)$get['spos'];
			$this->arrParamas['rank'] = $rank;
			$limit = 9;
			$sex = isset($get['sex'])? (int)$get['sex'] : -1;
			$arrCondition = array();
			if($sex !== -1){
				$arrCondition['js.staff_sex'] = $sex;
			}
			$this->arrParamas['sex'] = $sex;
			$sort = (int)$get['sort']===1||(int)$get['sort']===2?(int)$get['sort']:1;
			$st = (int)$get['st'];
			$strt = strtotime((string)$get['strt']);
			$endt = strtotime((string)$get['endt']);
			$posLevel = (int)$get['pl'];
			$db = new SDb();
			if(!$strt && !$endt){
				$strt = strtotime(date('Y-m'.'-01'));
				$endt = strtotime(date('Y-m-d'));
			} else if(!$strt || !$endt){
				$endt = $strt? $strt:$endt;
				$strt = strtotime(date('Y-m', $endt).'-01');
			} else if($strt>$endt){
				$tmp = $strt;
				$strt = $endt;
				$endt = $tmp;
			}

			$this->arrParamas['strt'] = date('Y-m-d', $strt);
			$this->arrParamas['endt'] = date('Y-m-d', $endt);
			$arrCondition[] = 'js.staff_id != 10000';
			$arrCondition[] = '(joi.datetime>='.$strt.' and joi.datetime<='.($endt+86399).')';
			$arrCondition['is_leave'] = 0;
			if($posLevel !== 0){
				$arrCondition['js.pos_level'] = $posLevel;
				$this->arrParamas['pl'] = $posLevel;
			}
			
			if($st>0){
				$arrCondition['staff_department']= $st;
				if($spos > 0){
					$arrCondition['staff_position'] = $spos;
					$this->arrParamas['spos'] = $spos;
					$this->arrParamas['sposes'] = $db->select('jf_position'.SUF, array('parent_id'=>$st))->items;
				}
			}
			$this->arrParamas['st'] = $st;
			
			$db->setLimit($limit);
			$db->setPage($page);
			$db->setCount(true);
			$strItem = 'js.staff_id as staff_id,js.staff_name as staff_name, js.staff_sex as staff_sex, js.staff_level as staff_level, sum(joi.deal_score) as total';
			$groupby = 'js.staff_id';
			$orderby = ('total '.($sort===2?'asc':'desc'));
			$leftjoin = 'jf_operation_info'.SUF.' as joi on js.staff_id=joi.staff_id';
			$arrResults = $db->select('jf_staff'.SUF.'` as `js', $arrCondition, $strItem, $groupby, $orderby, $leftjoin);
			$db->setLimit(0);
			$arrResults->totalSize = count($db->select('jf_staff'.SUF.'` as `js', $arrCondition, $strItem, $groupby, $orderby, $leftjoin)->items);
			$arrResults->totalPage = ceil($arrResults->totalSize/1.0/$limit);
			if($arrResults->totalPage>1){
				$this->arrParamas['pagebar'] = $this->pagebar($page, $arrResults->totalPage, $limit);
			}
			if($arrResults->totalSize > 0){
				foreach ($arrResults->items as &$value) {
					$arrCondition = array();
					$arrCondition['staff_id'] = $value['staff_id'];
					$arrCondition[] = '(datetime>='.$strt.' and datetime<='.($endt+86399).')';
					$scoreInfo = $db->select('jf_operation_info'.SUF, $arrCondition)->items;
					$add = 0;
					$sub = 0;
					foreach ($scoreInfo as $info) {
						if($info['deal_score'] > 0){
							$add += $info['deal_score'];
						} else {
							$sub += $info['deal_score'];
						}
					}
					$total = $add + $sub;

					$value['add'] = (int)$add;
					$value['sub'] = (int)$sub;
				}
			}
			$arrStaffs = $arrResults->items;

			$arrCondition = array('parent_id'=>0);
			$arrDepartments = $db->select('jf_position'.SUF,$arrCondition)->items;

			$this->sortArray($arrStaffs, $sort, 'total');
			$this->arrParamas['sort'] = $sort;
			$this->arrParamas['staffs'] = $arrStaffs;
			$this->arrParamas['departments'] = $arrDepartments;
            $this->arrParamas['queryStr'] = http_build_query($get);
            $this->arrParamas['doprint'] = isset($get['doprint']) ? $get['doprint'] : 0;
            if ($this->arrParamas['doprint']) {
                return $this->render('cp/scoretable_print.html', $this->arrParamas);
            } else {
                return $this->render('cp/scoretable.html', $this->arrParamas);
            }
		} else {
			return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
		}
	}

	//加减分页面
	function pageScore(){
		if($_SESSION['staffLimit'] > 0){
			$db = new SDb();
			$strCondition = 'parent_id=0';
			$teams = $db->select('jf_position'.SUF, $strCondition)->items;
			$this->arrParamas['teams'] = $teams;
			$arrCondition = array('parent_id'=>0);
			$scoreTypes = $db->select('jf_score_class'.SUF, $arrCondition)->items;
			$this->arrParamas['scoreTypes'] = $scoreTypes;
			return $this->render('cp/score.html', $this->arrParamas);
		} else {
			return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
		}
	}

	//积分详情
	function pageScoreInfo(){
		$get = $this->safeData($_GET);
		$staff_id = (int)$get['sid'];
		if(($_SESSION['staffLimit'] > 0) || ($_SESSION['staff_id'] == $staff_id)){
			$db = new SDb();
			$strt = strtotime((string)$get['strt']);
			$endt = strtotime((string)$get['endt']);
			if(!$strt && !$endt){
				$strt = strtotime(date('Y-m'.'-01'));
				$endt = strtotime(date('Y-m-d'));
			} else if(!$strt || !$endt){
				$endt = $strt? $strt:$endt;
				$strt = strtotime(date('Y-m', $endt).'-01');
			} else if($strt>$endt){
				$tmp = $strt;
				$strt = $endt;
				$endt = $tmp;
			}
			$type = (int)$get['type'];
			$type = ($type===2||$type===3)?$type:1;
			$this->arrParamas['type'] = $type;
			$arrCondition = array('staff_id'=>$staff_id);
			switch ($type) {
				case 2:
					$arrCondition[] = '(deal_score > 0)';
					break;
				case 3:
					$arrCondition[] = '(deal_score < 0)';
					break;
			}
			$arrCondition[] = '(datetime>='.$strt. ' and datetime<'.($endt+86399).' )';
			$this->arrParamas['strt'] = date('Y-m-d', $strt);
			$this->arrParamas['endt'] = date('Y-m-d', $endt);
			$limit = 10;
			$page = (int)$get['p']>1 ? $get['p']:1;
			$arrStaff = $db->selectOne('jf_staff'.SUF, array('staff_id'=>$staff_id));
			$db->setLimit($limit);
			$db->setPage($page);
			$db->setCount(true);
			$arrScoreInfo = $db->select('jf_operation_info'.SUF, $arrCondition, '', '', 'datetime desc');
			if(!$arrStaff){
				return $this->alert('员工不存在请重新选择', $this->makeUrl('main/stafflist', 'main'));
			}
			if($arrScoreInfo->totalPage > 1){
				$this->arrParamas['pagebar'] = $this->pagebar($page, $arrScoreInfo->totalPage, $limit);
			}
			$this->arrParamas['staff'] = $arrStaff;
			$this->arrParamas['score'] = $arrScoreInfo->items;
			return $this->render('cp/scoreinfo.html', $this->arrParamas);
		} else {
			return $this->alert('你不是管理员，无权查看他人信息',$this->makeUrl('main/loginpage', 'main'));
		}
	}

	//删除积分
	function pageDelScoreInfo() {
		if($_SESSION['staffLimit'] > 0){
			$ii = (int)$_GET['ii'];
			$sid = (int)$_GET['sid'];
			$db = new SDb();
			$arrCondition['info_id'] = $ii;
			$opinfo = $db->selectOne('jf_operation_info'.SUF, $arrCondition);
			if($opinfo['deal_score'] > 0){
				$db->update('jf_staff'.SUF, array('staff_id='.$sid), 'score=score-'.$opinfo['deal_score']);
			} else {
				$db->update('jf_staff'.SUF, array('staff_id='.$sid), 'score=score-'.$opinfo['deal_score']);
			}
			if($db->delete('jf_operation_info'.SUF, $arrCondition)){
				return $this->alert('删除成功');
			} else {
				return $this->alert('删除失败，请重试');
			}
		} else {
			return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
		}
	}

	//查看排名
	function pageRanking(){
		$get = $this->safeData($_GET);
			$page = (int)$get['p']>1 ? $get['p']:1;
			$rank = ($page-1)*9+1;
			$this->arrParamas['rank'] = $rank;
			$limit = 9;
			$arrCondition = array();
			$sort = (int)$get['sort']===1||(int)$get['sort']===2?(int)$get['sort']:1;
			$strt = strtotime((string)$get['strt']);
			$endt = strtotime((string)$get['endt']);
			if(!$strt && !$endt){
				$strt = strtotime(date('Y-m'.'-01'));
				$endt = strtotime(date('Y-m-d'));
			} else if(!$strt || !$endt){
				$endt = $strt? $strt:$endt;
				$strt = strtotime(date('Y-m', $endt).'-01');
			} else if($strt>$endt){
				$tmp = $strt;
				$strt = $endt;
				$endt = $tmp;
			}

			$this->arrParamas['strt'] = date('Y-m-d', $strt);
			$this->arrParamas['endt'] = date('Y-m-d', $endt);
			$arrCondition[] = 'js.staff_id != 10000';
			$arrCondition[] = '(joi.datetime>='.$strt.' and joi.datetime<='.($endt+86399).')';
			$arrCondition['is_leave'] = 0;
			$db = new SDb();
			$db->setLimit($limit);
			$db->setPage($page);
			$db->setCount(true);
			$strItem = 'js.staff_id as staff_id,js.staff_name as staff_name, js.staff_sex as staff_sex, js.staff_level as staff_level, sum(joi.deal_score) as total';
			$groupby = 'js.staff_id';
			$orderby = ('total '.($sort===2?'asc':'desc'));
			$leftjoin = 'jf_operation_info as joi on js.staff_id=joi.staff_id';
			$arrResults = $db->select('jf_staff` as `js', $arrCondition, $strItem, $groupby, $orderby, $leftjoin);
			$db->setLimit(0);
			$arrResults->totalSize = count($db->select('jf_staff` as `js', $arrCondition, $strItem, $groupby, $orderby, $leftjoin)->items);
			$arrResults->totalPage = ceil($arrResults->totalSize/1.0/$limit);
			if($arrResults->totalPage>1){
				$this->arrParamas['pagebar'] = $this->pagebar($page, $arrResults->totalPage, $limit);
			}
			foreach ($arrResults->items as &$value) {
				$arrCondition = array();
				$arrCondition['staff_id'] = $value['staff_id'];
				$arrCondition[] = '(datetime>'.$strt.' and datetime<'.($endt+86399).')';
				$scoreInfo = $db->select('jf_operation_info'.SUF, $arrCondition)->items;
				$add = 0;
				$sub = 0;
				foreach ($scoreInfo as $info) {
					if($info['deal_score'] > 0){
						$add += $info['deal_score'];
					} else {
						$sub += $info['deal_score'];
					}
				}
				$total = $add + $sub;

				$value['add'] = (int)$add;
				$value['sub'] = (int)$sub;
			}
			$arrStaffs = $arrResults->items;
			$this->sortArray($arrStaffs, $sort, 'total');
			$this->arrParamas['sort'] = $sort;
			$this->arrParamas['staffs'] = $arrStaffs;
			return $this->render('cp/ranking.html', $this->arrParamas);
	}

	//加、减分数据库操作
	function pageDealScore(){
		if($_SESSION['staffLimit'] > 0){
			$post = $this->safeData($_POST);
			$staff_name = (string)$post['staffName'];
			$staff_name = $this->delallspe($staff_name);
			$dealName = (string)$post['dealName'];
			$score = 0;
			$reason = 0;
			$score_id = (int)$post['score_detail'];
			$info_id = 0;
			$prule = $post['prule'];
			$rule = (int)$post['rule'];
			$addTime = strlen($post['addtime'])>0?(string)$post['addtime']:date('Y-m-d');
			if(!strlen($staff_name) > 0){
				return $this->alert('请输入员工姓名');
			} else if($score_id === 0 && $rule === 1){
				return $this->alert('请正确的处理原因');
			}

			$db = new SDb();
			$arrDeal = $db->selectOne('jf_staff'.SUF, array('staff_name'=>$dealName));
			if(!$arrDeal){
				return $this->alert('不存在的处理人，请重试');
			}

			if($rule === 1){
				$scoreDetail = $db->selectOne('jf_score_detail'.SUF, array('score_detail_id'=>$score_id));
				if($scoreDetail['type'] == 2){
					$score = (int)abs($post['fscore']);
					if(!($score>=$scoreDetail['score'] && $score<=$scoreDetail['score_max'])){
						return $this->alert('请输入正确的分数，从'.$scoreDetail['score'].'至'.$scoreDetail['score_max'], '', 6);
					}
				} else {
					$score = $scoreDetail['score'];
				}
				$reason = $scoreDetail['detail'].($score>0 ? "增加":"减少").$score."分";
				if(!$staff = $db->selectOne('jf_staff'.SUF, array('staff_name'=>$staff_name, 'is_leave'=>0), 'staff_id')){
					return $this->alert('没有找到'.$staff_name.'请核对员工姓名');
				} else if (!$scoreDetail){
					return $this->alert('错误的处理原因');
				}
			} else {
				$score = (int)abs($post['score']);
				$score = $prule>0?$score:0-$score;
				$reason = (string)$post['dealdetail'];
				if(!$staff = $db->selectOne('jf_staff'.SUF, array('staff_name'=>$staff_name, 'is_leave'=>0), 'staff_id')){
					return $this->alert('没有找到'.$staff_name.'请核对员工姓名');
				} else if($score === 0){
					return $this->alert('请输入正确的分数');
				} else if(strlen($reason) === 0){
					return $this->alert('请输入处理原因');
				}
			}

			//按月加分放入按月执行列表
			if($scoreDetail['type'] == 3) {
				$interval = (int)$post['interval'];
				if($interval < 1){
					return $this->alert('请输入加分间隔不小于1个月');
				}
				$strInsert = 'score_detail_id='.$score_id.',staff_id='.$staff['staff_id'].',add_interval='.$interval.',datetime='.strtotime($addTime).
							 ',dateymd="'.$addTime.'"';
				if(!$db->insert('jf_autoscore'.SUF, $strInsert)){
					return $this->alert('保存按月份加分失败');
				}
			} 
			//按月加分放入按月执行列表
			$strInsert = 'staff_id='.$staff['staff_id'].',deal_score='.$score.',deal_reason="'.$reason.'"'.',deal_staff="'.$dealName.'"'.
						 ',datetime='.strtotime($addTime).',dateymd="'.$addTime.'"'.',score_detail_id='.$score_id;
			if(!($info_id = $db->insert('jf_operation_info'.SUF, $strInsert))){
			}
			if(!$db->update('jf_staff'.SUF, array('staff_id'=>$staff['staff_id']), 'score=score+('.$score.')')){
				$db->delete('jf_operation_info'.SUF, array('info_id'=>$info_id));
			}
			return $this->alert('操作成功', $this->makeUrl('main/score', 'main'));
		} else {
			return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
		}
		
	}

	//查询员工
	function pageSearch(){
		if($_SESSION['staffLimit'] > 0){
			$db = new SDb();
			$get = $this->safeData($_GET);
			$arrCondition = array();
			$staffName = (string)$get['keywords'];
			if(!(strlen($staffName)>0)){
				return $this->alert("请输入需要查询的员工姓名", $this->makeUrl('main/stafflist', 'main'));
			}
			$this->arrParamas['keywords'] = $staffName;
			$arrCondition['staff_name'] = $staffName;
			$arrCondition['is_leave'] = 0;
			$arrResults = $db->select('jf_staff'.SUF, $arrCondition);
			$this->arrParamas['staffs'] = $arrResults->items;
			$this->arrParamas['issearch'] = 1;
			return $this->render('cp/stafflist.html', $this->arrParamas);
		} else {
			return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
		}
	}

	//ajax查询员工
	function pageGetStaff(){
		if($_SESSION['staffLimit'] > 0){
			$db = new SDb();
			$post = $this->safeData($_POST);
			$arrCondition = array();
			$staffName = (string)$post['staffName'];
			$arrCondition['staff_name'] = $staffName;
			$arrCondition['is_leave'] = 0;
			$arrParamas = array();
			if(!$arrResults = $db->selectOne('jf_staff'.SUF, $arrCondition)){
				$arrCondition = array();
				$arrParamas['state'] = 0;
				$arrCondition['is_leave'] = 0;
				$arrStaffs = array();
				$tmpStaffs = array();
				$items = 'staff_name';
				$nameLen = mb_strlen($staffName, 'UTF-8');
				if($nameLen < 3){
					$arrCondition[] = 'staff_name like "_'.mb_substr($staffName, 1, 1, 'UTF-8').'"';
					$arrStaffs = $db->select('jf_staff'.SUF, $arrCondition, $items)->items;
				}

				if(count($arrStaffs) > 0){
					$tmpStaffs = $arrStaffs;
				}

				$arrStaffs = $db->select('jf_staff'.SUF, 'is_leave=0', $items)->items;
				$arrKeys = array();
				for($i=0; $i<$nameLen; $i++){
					$arrKeys[] = mb_substr($staffName, $i, 1, 'UTF-8');
				}
				foreach ($arrStaffs as $staffNum => $value) {
					$times = 0;
					$tmpName = $value['staff_name'];
					foreach ($arrKeys as $key) {
						if(mb_strpos($tmpName, $key, 0, 'UTF-8') !== false){
							$times++;
						}
					}
					if($times < 2){
						unset($arrStaffs[$staffNum]);
					}
				}
				$arrStaffs = array_merge($arrStaffs, $tmpStaffs);
				if(count($arrStaffs)>0){
					foreach ($arrStaffs as $value) {
						$arrParamas['staffs'][] = $value['staff_name'];
					}
				}
				echo json_encode($arrParamas);
				return;
			} else {
				$arrParamas['state'] = 1;
				echo json_encode($arrParamas);
				return;
			}
		} else {
			return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
		}
	}

	//部门编辑
	function pageTeamAbout(){
		if($_SESSION['staffLimit'] > 0){
            $get = $this->safeData($_GET);
            $page = (int)$get['p']>1 ? $get['p']:1;
            $limit = 9;
            $db = new SDb();
            $db->setCount(true);
            $db->setLimit($limit);
            $db->setPage($page);
            $teams = $db->select('jf_position'.SUF, 'parent_id=0');
            if($teams->totalPage > 1){
                $this->arrParamas['pagebar'] =  $this->pagebar($page, $teams->totalPage, $limit);
            }
            $this->arrParamas['teams'] = $teams->items;
            return $this->render('cp/teamabout.html', $this->arrParamas);
//			return $this->render('cp/teamabout.html');
		} else {
			return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
		}
	}

	//部门列表
	function pageTeamList(){
		if($_SESSION['staffLimit'] > 0){
			$get = $this->safeData($_GET);
			$page = (int)$get['p']>1 ? $get['p']:1;
			$limit = 9;
			$db = new SDb();
			$db->setCount(true);
			$db->setLimit($limit);
			$db->setPage($page);
			$teams = $db->select('jf_position'.SUF, 'parent_id=0');
			if($teams->totalPage > 1){
				$this->arrParamas['pagebar'] =  $this->pagebar($page, $teams->totalPage, $limit);
			}
			$this->arrParamas['teams'] = $teams->items;
			return $this->render('cp/teamlist.html', $this->arrParamas);
		} else {
			return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
		}
	}

	//添加部门
	function pageNewTeam(){
		if($_SESSION['staffLimit'] > 0){
			return $this->render('cp/newteam.html');
		} else {
			return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
		}
	}

	//保存部门
	function pageAddTeam(){
		if($_SESSION['staffLimit'] > 0){
			$post = $this->safeData($_POST);
			$teamName = $post['teamName'];
			$parent_id = (int)$post['parent_id'];
			$db = new SDb();
			$strInsert = 'pos_name="'.$teamName.'"';
			if( ($parent_id > 0) && $db->selectOne('jf_position'.SUF, array('pos_id'=>$parent_id)) ){
				$strInsert .= ', parent_id='.$parent_id;
				$gu = 1;
			}
			if(!(strlen($teamName) > 0)){
				return $this->alert('部门名称不能为空', $this->makeUrl('main/newteam', 'main'));
			}
			if(!$db->insert('jf_position'.SUF, $strInsert)){
				return $this->alert('部门名称不能为空', $this->makeUrl('main/newteam', 'main'));
			}
			
			if($gu == 1){
				return $this->alert('添加成功', $this->makeUrl('main/poslist', 'main'));
			}
			return $this->alert('添加成功', $this->makeUrl('main/teamlist', 'main'));
		} else {
			return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
		}
	}

	//编辑部门
	function pageEditTeam(){
		if($_SESSION['staffLimit'] > 0){
			$db = new SDb();
			$pid = (int)$_GET['pid'];
			$team = $db->selectOne('jf_position'.SUF, array('pos_id'=>$pid));
			$this->arrParamas['team'] = $team;
			$_SESSION['jf_position_id'] = $pid;
			return $this->render('cp/editteam.html', $this->arrParamas);
		} else {
			return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
		}
	}

	//更新部门信息
	function pageUpdateTeam(){
		if($_SESSION['staffLimit'] > 0){
			$post = $this->safeData($_POST);
			$teamName = $post['teamName'];
			$arrCondition = array();
			$db = new SDb();
			$pid = (int)$_SESSION['jf_position_id'];
			$_SESSION['jf_position_id'] = 0;
			$parent_id = (int)$post['parent_id'];
			$strUpdate = 'pos_name="'.$teamName.'"';
			if($parent_id > 0){
				$strUpdate .= ',parent_id='.$parent_id;
				$gu = 1;
			}

			$arrCondition['pos_id'] = $pid;

			if(!($pid > 0)){
				return $this->alert('错误信息', $this->makeUrl('main/teamlist', 'main'));
			}

			if(!(strlen($teamName) > 0)){
				return $this->alert('部门名称不能为空');
			}
			if(!$db->update('jf_position'.SUF, $arrCondition, $strUpdate)){
				return $this->alert('更新失败请重试', $this->makeUrl('main/teamlist', 'main'));
			}
			if($gu == 1){
				return $this->alert('添加成功', $this->makeUrl('main/poslist', 'main'));
			}
			return $this->alert('修改成功', $this->makeUrl('main/teamlist', 'main'));
		} else {
			return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
		}
	}

	//删除部门
	function pageDelTeam(){
		if($_SESSION['staffLimit'] > 0){
			$db = new SDb();
			$pid = (int)$_GET['pid'];
			if($pid>0 && $db->delete('jf_position'.SUF, array('pos_id'=>$pid))){
				return $this->alert('删除成功', $this->makeUrl('main/teamlist', 'main'));
			} else {
				return $this->alert('删除失败，请重试', $this->makeUrl('main/teamlist', 'main'));
			}

		} else {
			return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
		}
	}

	//添加职位
	function pageNewPos(){
		if($_SESSION['staffLimit'] > 0){
			$db = new SDb();
			$strCondition = 'parent_id=0';
			$teams = $db->select('jf_position'.SUF, $strCondition)->items;
			$this->arrParamas['teams'] = $teams;
			return $this->render('cp/newpos.html', $this->arrParamas);
		} else {
			return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
		}
	}

	//编辑职位
	function pageEditPos(){
		if($_SESSION['staffLimit'] > 0){
			$pid = (int)$_GET['pid'];
			$db = new SDb();
			$_SESSION['jf_position_id'] = $pid;
			$strCondition = 'parent_id=0';
			$teams = $db->select('jf_position'.SUF, $strCondition)->items;
			$this->arrParamas['teams'] = $teams;
			$arrCondition = array('pos_id'=>$pid);
			$pos = $db->selectOne('jf_position'.SUF, $arrCondition);
			$this->arrParamas['teams'] = $teams;
			$this->arrParamas['pos'] = $pos;
			return $this->render('cp/editpos.html', $this->arrParamas);
		} else {
			return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
		}
	}

	//职位列表
	function pagePosList(){
		if($_SESSION['staffLimit'] > 0){
			$get = $this->safeData($_GET);
			$page = (int)$get['p']>1 ? $get['p']:1;
			$limit = 9;
			$db = new SDb();
			$db->setCount(true);
			$db->setLimit($limit);
			$db->setPage($page);
			$strCondition = 'parent_id!=0';
			$teams = $db->select('jf_position'.SUF, $strCondition);
			if($teams->totalPage > 1){
				$this->arrParamas['pagebar'] =  $this->pagebar($page, $teams->totalPage, $limit);
			}
			$this->arrParamas['teams'] = $teams->items;
			return $this->render('cp/poslist.html', $this->arrParamas);
		} else {
			return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
		}
	}

	//查看自动加分详情
	function pageAutoScore(){
		if($_SESSION['staffLimit'] > 0){
			$get = $this->safeData($_GET);
			$sid = (int)$get['sid'];
			$page = (int)$get['p']>1 ? $get['p']:1;
			$limit = 10;
			$db = new SDb();
			$arrCondition = array('staff_id'=>$sid);
			$staff = $db->selectOne('jf_staff'.SUF, $arrCondition);
			if(!$staff){
				return $this->alert('不存在该员工');
			}
			$this->arrParamas['staff'] = $staff;
			$db->setCount(true);
			$db->setLimit($limit);
			$db->setPage($page);
			$arrAutoScores = $db->select('jf_autoscore'.SUF, $arrCondition);
			if($arrAutoScores->totalPage > 1){
				$this->arrParamas['pagebar'] =  $this->pagebar($page, $arrAutoScores->totalPage, $limit);
			}
			$arrAutos = $arrAutoScores->items;
			$strCondition = '';
			foreach ($arrAutos as $value) {
				$strCondition .= 'score_detail_id='.$value['score_detail_id'].' or ';
			}
			if(strlen($strCondition) > 0){
				$strCondition = mb_substr($strCondition, 0, (mb_strlen($strCondition, 'UTF-8')-4), 'UTF-8');
				$arrScoreDetails = $db->select('jf_score_detail'.SUF, $strCondition)->items;
				$arrDetails = array();
				foreach ($arrScoreDetails as $value) {
					$detail_id = $value['score_detail_id'];
					$arrDetails[$detail_id]['detail'] = $value['detail'];
					$arrDetails[$detail_id]['score'] = $value['score'];
				}
				$this->arrParamas['details'] = $arrDetails;
			}
			$this->arrParamas['autoscores'] = $arrAutos;
			return $this->render('cp/autoscore.html', $this->arrParamas);
		} else {
			return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
		}
	}

	//删除自动加分
	function pageDelAuto(){
		if($_SESSION['staffLimit'] > 0){
			$get = $this->safeData($_GET);
			$sid = (int)$get['sid'];
			$aid = (int)$get['aid'];
			if($sid > 0 && $aid > 0){
				$db = new SDb();
				$arrCondition = array('staff_id'=>$sid, 'auto_id'=>$aid);
				if($db->delete('jf_autoscore'.SUF, $arrCondition)){
					return $this->alert('删除成功', $this->makeUrl('main/autoscore', 'main').'?sid='.$sid);
				} else {
					return $this->alert('删除失败请重试','',1000);
				}
			} else {
				return $this->alert('错误删除，请重试');
			}
		} else {
			return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
		}
	}

	//退出登录
	function pageLoginOut(){
		$this->staffLogout();
		return $this->alert('已退出系统', $this->makeUrl('main/loginpage', 'main'));
	}

	//进入登录页面
	function pageLoginPage(){
		if($this->isLoginStaff()){
			return header('Location:'.$this->makeUrl('main/index', 'main'));
		}
		return $this->render('cp/loginpage.html');
	}

	//查看权限
	function pageCheckLimit(){
		$parames['staffLimit'] = $_SESSION['staffLimit'];
		echo json_encode($parames);
		return;
	}

	//获得积分详情
	function pageGetDetail(){
		if($_SESSION['staffLimit'] > 0){
			Tpl::getHtmlStr(true);
			$cid = (int)$_POST['classId'];
			$c2 = (int)$_POST['c2'];
			$arrCondition = array('class_id'=>$cid);
			$db = new SDb();
			$results = $db->select('jf_score_detail'.SUF, $arrCondition);
			$this->arrParamas['details'] = $results->items;
			$html = $this->render('ajax/getdetail.html', $this->arrParamas);
			$arrJson = array('html'=>$html);
			if($c2 === 1){
				$classes = $db->select('jf_score_class'.SUF, array('parent_id'=>$cid))->items;
				$this->arrParamas['classes'] = $classes;
				$html = $this->render('ajax/get2cs.html', $this->arrParamas);
				$arrJson['c2'] = $html;
			}
			echo json_encode($arrJson);
			Tpl::getHtmlStr(false);
			return ;
		}
	}

	//使用代码处理积分
	function pageCodeDeal(){
		if($_SESSION['staffLimit'] > 0){
			Tpl::getHtmlStr(true);
			$strCode = strtoupper($_POST['code']);
			if(strlen($strCode) > 0){
				$db = new SDb();
				$detail = $db->selectOne('jf_score_detail'.SUF, array('score_code'=>$strCode));
				if($detail){
					$this->arrParamas['s'] = 1;
					$this->arrParamas['detail'] = $detail;
					$arrDeCon = array('class_id'=>$detail['class_id']);
					if($detail['score'] > 0){
						$arrDeCon[] = 'score > 0';
					} else {
						$arrDeCon[] = 'score < 0';
					}
					$detailList = $db->select('jf_score_detail'.SUF, $arrDeCon)->items;
					$this->arrParamas['detailList'] = $this->render('ajax/getdetail.html', array('details'=>$detailList, 'type'=>$detail['type']));
					$this->arrParamas['c1'] = $detail['class_id'];
					$class = $db->selectOne('jf_score_class', array('class_id'=>$detail['class_id']));
					if((int)$class['parent_id'] !== 0){
						$c2s = $db->select('jf_score_class', array('parent_id'=>$class['parent_id']))->items;
						$this->arrParamas['c2s'] = $this->render('ajax/get2cs.html', array('classes'=>$c2s));
						$this->arrParamas['c1'] = $class['parent_id'];
						$this->arrParamas['c2'] = $detail['class_id'];
					}
				} else {
					$this->arrParamas['s'] = -1;
				}
				echo json_encode($this->arrParamas);
				Tpl::getHtmlStr(false);
			}
			return ;
		}
	}

	//修改密码
	function pageRePass() {
		return $this->render('cp/repass.html');
	}

	//员工修改密码
	function pageRepassword(){
		$post = $this->safeData($_POST);
		$db = new SDb();
		$staff = $db->selectOne('jf_staff'.SUF, array('staff_id'=>$_SESSION['staff_id'],'password'=>md5($post['password'])));
		if(!$staff){
			return $this->alert('密码错误请重试');
		} else if(strlen($post['newpassword'])<6){
			return $this->alert('密码不能少于6位哦');
		} else if(strcmp($post['newpassword'], $post['repassword']) != 0 ){
			return $this->alert('两次密码不一致请重新输入');
		}

		if($db->update('jf_staff'.SUF, array('staff_id'=>$_SESSION['staff_id']), 'password="'.md5($post['newpassword']).'"')){
			$this->staffLogout();
			return $this->alert('密码修改成功', $this->makeUrl('main/index', 'main'));
		} else {
			return $this->alert('密码修改失败，请重试');
		}
	}

	//员工离职
	function pageLeave(){
		if($_SESSION['staffLimit'] > 100){
			$pid = (int)$_GET['pid'];
			$upStr = 'is_leave=1,datetime='.time().',dateymd="'.date('Y-m-d').'"';
			$strCondition = 'staff_id='.$pid;
			$db = new SDb();
			if($db->update('jf_staff'.SUF, $strCondition, $upStr)){
				$this->alert('修改成功', $this->makeUrl('main/stafflist', 'main'));
			} else {
				$this->alert('修改失败，请重试', $this->makeUrl('main/stafflist', 'main'));
			}
		} else {
			return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
		}
	}

	//获得职位
	function pageGetPos(){
		if($_SESSION['staffLimit'] > 0){
			Tpl::getHtmlStr(true);
			$pid = (int)$_POST['pid'];
			if(!($pid>0)){
				return ;
			}
			$db = new SDb();
			$poses = $db->select('jf_position'.SUF, 'parent_id='.$pid)->items;
			$this->arrParamas['poses'] = $poses;
			$html = $this->render('ajax/getpos.html', $this->arrParamas);
			echo json_encode(array('html'=>$html));
			Tpl::getHtmlStr(false);
		}
	}

	//ajax获得二级分类
	function pageGet2c(){
		if($_SESSION['staffLimit'] > 0){
			Tpl::getHtmlStr(true);
			$cid = (int)$_POST['cid'];
			if(!($cid>0)){
				return ;
			}
			$db = new SDb();
			$classes = $db->select('jf_score_class'.SUF, array('parent_id'=>$cid))->items;
			$this->arrParamas['classes'] = $classes;
			$html = $this->render('ajax/get2c.html', $this->arrParamas);
			$arrJson = array('s'=>1,'html'=>$html);
			echo json_encode($arrJson);
			Tpl::getHtmlStr(false);
		}
	}

	//获得当前职位所有员工
	function pageGetPeop(){
		if($_SESSION['staffLimit'] > 0){
			Tpl::getHtmlStr(true);
			$pid = (int)$_POST['pid'];
			if(!($pid>0)){
				return ;
			}
			$db = new SDb();
			$peop = $db->select('jf_staff'.SUF, 'staff_position='.$pid.' and is_leave=0')->items;
			$this->arrParamas['peop'] = $peop;
			$html = $this->render('ajax/getpeop.html', $this->arrParamas);
			echo json_encode(array('html'=>$html));
			Tpl::getHtmlStr(false);
		}
	}

	//自动加分
	function runauto(){
		if($_SESSION['staffLimit'] > 0){
			$db = new SDb();
			$arrAutos = $db->select('jf_autoscore'.SUF)->items;
			$nowtime = time();
			$nowY = date('Y', $nowtime);
			$nowM = date('m', $nowtime);
			foreach ($arrAutos as $value) {
				$atime = $value['datetime'];
				$aY = date('Y', $atime);
				$aM = date('m', $atime);

				$totalT = (int)((($nowY-$aY)*12 + ($nowM-$aM))/$value['add_interval']);

				//加分
				$arrCondition = array('score_detail_id'=>$value['score_detail_id']);
				$detail = $db->selectOne('jf_score_detail'.SUF, $arrCondition);
				$arrCondition = array('staff_id'=>$value['staff_id'], 'is_leave'=>0);
				$staff = $db->selectOne('jf_staff'.SUF, $arrCondition);
				for($i=1; $i<=$totalT; $i++){
					$addY = (int)($aY+(($aM+$i*$value['add_interval']-1)/12));
					$addM = (int)(($aM+$i*$value['add_interval'])%12);
					$addM = $addM==0?12:$addM;
					$addDate = $addY.'-'.$addM.'-01';
					$addTime = strtotime($addDate);
					$addDate = date('Y-m-d', $addTime);
					$strInsert = 'staff_id='.$value['staff_id'].',deal_score='.$detail['score'].',deal_reason="'.$detail['detail'].'"'.',deal_staff="'.
								 $staff['staff_name'].'"'.',datetime='.$addTime.',dateymd="'.$addDate.'"'.',score_detail_id='.$detail['score_detail_id'];
					if(!($info_id = $db->insert('jf_operation_info'.SUF, $strInsert))){
					}
					if(!$db->update('jf_staff'.SUF, array('staff_id'=>$staff['staff_id']), 'score=score+('.$detail['score'].')')){
						$db->delete('jf_operation_info'.SUF, array('info_id'=>$info_id));
					}
				}
				if($addTime > 0){
					$arrCondition = array('staff_id'=>$value['staff_id'], 'auto_id'=>$value['auto_id']);
					$strUpdate = 'datetime='.$addTime.',dateymd="'.$addDate.'"';
					if(!$db->update('jf_autoscore'.SUF, $arrCondition, $strUpdate)){
						return $this->alert('更新失败');
					}
				}
			}
			setcookie('AUTOSCORE', 'run', time()+2592000);
		}
	}

	//查看加分人
	function pageDealInfo(){
		if($_SESSION['staffLimit'] > 0){
			$db = new SDb();
			$arrCondition = array();
			$get = $this->safeData($_GET);
			$strt = strtotime((string)$get['strt']);
			$endt = strtotime((string)$get['endt']);
			if(!$strt && !$endt){
				$strt = strtotime(date('Y-m'.'-01'));
				$endt = strtotime(date('Y-m-d'));
			} else if(!$strt || !$endt){
				$endt = $strt? $strt:$endt;
				$strt = strtotime(date('Y-m', $endt).'-01');
			} else if($strt>$endt){
				$tmp = $strt;
				$strt = $endt;
				$endt = $tmp;
			}
			$this->arrParamas['strt'] = date('Y-m-d', $strt);
			$this->arrParamas['endt'] = date('Y-m-d', $endt);
			//加分
			$arrCondition[] = '(datetime>='.$strt.' and datetime<='.($endt+86399).')';
			$arrCondition[] = 'deal_score > 0';
			$grounpby = 'deal_staff';
			$items = 'deal_staff,sum(deal_score) as score';
			$arrDealInfoPlus = $db->select('jf_operation_info'.SUF, $arrCondition, $items, $grounpby);
			//扣分
			$arrCondition = array();
			$arrCondition[] = '(datetime>='.$strt.' and datetime<='.($endt+86399).')';
			$arrCondition[] = 'deal_score < 0';
			$grounpby = 'deal_staff';
			$items = 'deal_staff,sum(deal_score) as score';
			$arrDealInfoSub = $db->select('jf_operation_info'.SUF, $arrCondition, $items, $grounpby);
			$this->arrParamas['plus'] = $arrDealInfoPlus->items;
			$this->arrParamas['sub'] = $arrDealInfoSub->items;
			$this->render('cp/dealinfo.html', $this->arrParamas);
		} else {
			return $this->alert('你无权查看该内容');
		}
	}


	function pageImport() {
        if($_SESSION['staffLimit'] > 0){
            $db = new SDb();
            $get = $this->safeData($_GET);
            if (isset($get['type']) && $get['type'] == 1) {
            	// 导出
				$tabledump = '';
				// list tables;
				$tables = $db->execute('show tables');
//				var_dump($tables);
                foreach ($tables as $table) {
                	$tableName = array_shift($table);
                	$createTable = $db->execute('SHOW CREATE TABLE `'.$tableName.'`');
//                	var_dump($createTable);
					$tabledump .= "DROP TABLE IF EXISTS `$tableName`;\n";
					$tabledump .= $createTable[0]['Create Table'].";\n\n";
					$rows = $db->select($tableName);
//					var_dump($rows);
//					break;
                    if (!empty($rows->items)) {
                    	foreach ($rows->items as $item) {
//                    		var_dump($item);
                            $comma = "";
                            $tabledump .= "INSERT INTO `$tableName` VALUES (";
                            foreach ($item as $field=>$fieldVal) {
                            	$tabledump .= $comma. "'".str_replace('\'', '\\\'', $fieldVal)."'";
                            	$comma = ",";
							}
							$tabledump .= ");\n";
						}
						$tabledump .="\n";

					}
				}
//				var_dump($tabledump);
				$filetype = 'sql';
                $filename = 'jifensystem_'.date('YmdHis').'00'.mt_rand(1,10).'.sql';
                header('Pragma: public');
                header('Last-Modified: '.gmdate('D, d M Y H:i:s') . ' GMT');
                header('Cache-Control: no-store, no-cache, must-revalidate');
                header('Cache-Control: pre-check=0, post-check=0, max-age=0');
                header('Content-Transfer-Encoding: binary');
                header('Content-Encoding: none');
                header('Content-type: '.$filetype);
                header('Content-Disposition: attachment; filename="'.$filename.'"');
                header('Content-length: '.strlen($tabledump));
                echo $tabledump;
                exit;

			} else if (isset($_FILES['uploadfile']) && !empty($_FILES['uploadfile'])) {
            	// 导入
                $uploadinfo = $_FILES['uploadfile'];
                $filename = $uploadinfo['tmp_name'];
                if (!$filename) {
                    return $this->alert('您没有选择文件上传', $this->makeUrl('main/import', 'main'));
				}
                $sql = file_get_contents($filename);
                $queries_arr = explode(";\n", $sql);
                unset($sql);
                foreach ($queries_arr as $query) {
//                    var_dump(trim($query));
                	$db->execute(trim($query));
				}
                return $this->alert('导入成功', $this->makeUrl('main/import', 'main'));
			}
            $html = $this->render('cp/import.html', $this->arrParamas);
        } else {
            return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
		}
	}

    //查看报表
    function pageScoreRank(){
        if($_SESSION['staffLimit'] > 0){
            $get = $this->safeData($_GET);
            $page = (int)$get['p']>1 ? $get['p']:1;
            $rank = ($page-1)*9+1;
            $spos = (int)$get['spos'];
            $this->arrParamas['rank'] = $rank;
            $limit = 9;
            $sex = isset($get['sex'])? (int)$get['sex'] : -1;
            $arrCondition = array();
            if($sex !== -1){
                $arrCondition['js.staff_sex'] = $sex;
            }
            $this->arrParamas['sex'] = $sex;
            $sort = (int)$get['sort']===1||(int)$get['sort']===2?(int)$get['sort']:1;
            $st = (int)$get['st'];
            $strt = strtotime((string)$get['strt']);
            $endt = strtotime((string)$get['endt']);
            $posLevel = (int)$get['pl'];
            $classId = isset($get['score_class']) ? intval($get['score_class'][0]) : 0;
            $this->arrParamas['class_pid'] = (int)$get['class_pid'];
            $db = new SDb();
            if(!$strt && !$endt){
                $strt = strtotime(date('Y-m'.'-01'));
                $endt = strtotime(date('Y-m-d'));
            } else if(!$strt || !$endt){
                $endt = $strt? $strt:$endt;
                $strt = strtotime(date('Y-m', $endt).'-01');
            } else if($strt>$endt){
                $tmp = $strt;
                $strt = $endt;
                $endt = $tmp;
            }

            $this->arrParamas['strt'] = date('Y-m-d', $strt);
            $this->arrParamas['endt'] = date('Y-m-d', $endt);
            $arrCondition[] = 'js.staff_id != 10000';
            $arrCondition[] = '(joi.datetime>='.$strt.' and joi.datetime<='.($endt+86399).')';
            $arrCondition['is_leave'] = 0;
            if($posLevel !== 0){
                $arrCondition['js.pos_level'] = $posLevel;
                $this->arrParamas['pl'] = $posLevel;
            }

            if($st>0){
                $arrCondition['staff_department']= $st;
                if($spos > 0){
                    $arrCondition['staff_position'] = $spos;
                    $this->arrParamas['spos'] = $spos;
                    $this->arrParamas['sposes'] = $db->select('jf_position'.SUF, array('parent_id'=>$st))->items;
                }
            }
            $this->arrParamas['st'] = $st;
            if ($classId > 0) {
            	$arrCondition['jsd.class_id'] = $classId;
			}

            $db->setLimit($limit);
            $db->setPage($page);
            $db->setCount(true);
            $strItem = 'js.staff_id as staff_id,js.staff_name as staff_name, js.staff_sex as staff_sex, js.staff_level as staff_level, joi.deal_score as total,joi.deal_reason as reason,jsc.class_name as class_name,jsc.parent_id as class_pid';
//            $groupby = 'js.staff_id';
            $groupby = '';
            $orderby = ('total '.($sort===2?'asc':'desc'));
//            $leftjoin = 'jf_operation_info'.SUF.' as joi on js.staff_id=joi.staff_id';
            $leftjoin = array(
            	'jf_operation_info'.SUF.' as joi'=>'js.staff_id=joi.staff_id',
            	'jf_score_detail'.SUF.' as jsd'=>'jsd.score_detail_id=joi.score_detail_id',
                'jf_score_class'.SUF.' as jsc'=>'jsc.class_id=jsd.class_id'
			);
            $arrResults = $db->select('jf_staff'.SUF.'` as `js', $arrCondition, $strItem, $groupby, $orderby, $leftjoin);
            $db->setLimit(0);
            $arrResults->totalSize = count($db->select('jf_staff'.SUF.'` as `js', $arrCondition, $strItem, $groupby, $orderby, $leftjoin)->items);
            $arrResults->totalPage = ceil($arrResults->totalSize/1.0/$limit);
            if($arrResults->totalPage>1){
                $this->arrParamas['pagebar'] = $this->pagebar($page, $arrResults->totalPage, $limit);
            }
            if($arrResults->totalSize > 0){
                foreach ($arrResults->items as &$value) {
                    $add = 0;
                    $sub = 0;
                    if($value['total'] > 0){
                        $add = $value['total'];
                    } else {
                        $sub = $value['total'];
                    }
                    $value['add'] = (int)$add;
                    $value['sub'] = (int)$sub;
                }
            }
            $arrStaffs = $arrResults->items;

            $arrCondition = array('parent_id'=>0);
            $arrDepartments = $db->select('jf_position'.SUF,$arrCondition)->items;
            $arrClasses = $db->select('jf_score_class'.SUF,'parent_id=0')->items;

            $this->sortArray($arrStaffs, $sort, 'total');
            $this->arrParamas['sort'] = $sort;
            $this->arrParamas['staffs'] = $arrStaffs;
            $this->arrParamas['departments'] = $arrDepartments;
            $this->arrParamas['classes'] = $arrClasses;
            $this->arrParamas['queryStr'] = http_build_query($get);
            $this->arrParamas['doprint'] = isset($get['doprint']) ? $get['doprint'] : 0;
            if ($this->arrParamas['doprint']) {
                return $this->render('cp/scorerank_print.html', $this->arrParamas);
			} else {
                return $this->render('cp/scorerank.html', $this->arrParamas);
			}
        } else {
            return $this->alert('你不是管理员，无权进行此操作',$this->makeUrl('main/loginpage', 'main'));
        }
    }


    public function pagePermission() {
        $get = $this->safeData($_GET);
        $this->arrParamas['roleid'] = $get['roleid'];
        $db = new SDb();
        $this->arrParamas['roles'] = $db->select('permission_role'.SUF)->items;
        return $this->render('cp/permission.html', $this->arrParamas);
    }

    public function pageGetPermission() {
        Tpl::getHtmlStr(true);
        $get = $this->safeData($_GET);
        $roleid = $get['roleid'];
        $parentid = isset($get['parent_id']) ? intval($get['parent_id']) : 0;
        $db = new SDb();
        $aids_map = $db->select('permission_map'.SUF,array('roleid'=>$roleid), 'aid')->items;
        $aids = array();
        if ($aids_map) {
            foreach ($aids_map as $aid_map) {
                $aids[] = $aid_map['aid'];
            }
        }
        $permissions = $db->select('permission'.SUF,array('parent_id'=>$parentid),'','','ordersort desc')->items;
        foreach ($permissions as $key=>$permission) {
            $permissions[$key]['ishave'] = in_array($permission['aid'],$aids);
            $children = $db->select('permission'.SUF,array('parent_id'=>$permission['aid']),'','','ordersort desc')->items;
            if ($children) {
            	foreach ($children as $ckey=>$child) {
            		$children[$ckey]['ishave'] = in_array($child['aid'], $aids);
            		// 第三级权限
                    $threechildren = $db->select('permission'.SUF,array('parent_id'=>$child['aid']),'','','ordersort desc')->items;
                    if ($threechildren) {
                    	foreach ($threechildren as $thkey=>$threechild) {
                    		$threechildren[$thkey]['ishave'] = in_array($threechild['aid'], $aids);
						}
						$children[$ckey]['children'] = $threechildren;
					}
				}
				$permissions[$key]['children'] = $children;
			}

        }
        echo json_encode(array('status'=>1,'data'=>$permissions));
        Tpl::getHtmlStr(false);
    }

    // 添加权限
    public function pageAddPermission() {
        Tpl::getHtmlStr(true);
        $get = $this->safeData($_GET);
        $db = new SDb();
        $permission = array('name'=>$get['name'],'url'=>$get['url'],'parent_id'=>intval($get['parent_id']));
        $permission['aid'] = $db->insert('permission'.SUF,'name="'.$get['name'].'",url="'.$get['url'].'",parent_id='.intval($get['parent_id']));
        echo json_encode(array('status'=>1,'data'=>$permission));
        Tpl::getHtmlStr(false);
    }

    // 删除权限
    public function pageDeletePermission() {
        Tpl::getHtmlStr(true);
        $get = $this->safeData($_GET);
        $db = new SDb();
        $aid = intval($get['aid']);
        $isdel = $db->delete('permission'.SUF,array('aid'=>$aid));
        echo json_encode(array('status'=>1,'data'=>array(),'msg'=>$isdel ? '删除成功':'删除失败'));
        Tpl::getHtmlStr(false);

    }

    // 编辑权限
    public function pageEditPermission() {
        Tpl::getHtmlStr(true);
        $get = $this->safeData($_GET);
        $db = new SDb();
        $aid = intval($get['aid']);
        $isSuccess = $db->update('permission'.SUF,array('aid'=>$aid),'name="'.$get['name'].'",url="'.$get['url'].'",parent_id='.intval($get['parent_id']));
        echo json_encode(array('status'=>1,'data'=>array(),'msg'=>$isSuccess ? '修改成功' : '修改失败'));
        Tpl::getHtmlStr(false);
    }

    public function pageAddRolePermission() {
        Tpl::getHtmlStr(true);
        $get = $_GET;
        $db = new SDb();
        $roleid = intval($get['roleid']);
        $db->delete('permission_map'.SUF,array('roleid'=>$roleid));
        if (isset($get['aid']) && $get['aid']) {
            $aids = explode(',',$get['aid']);
            foreach ($aids as $iAid) {
                $db->insert('permission_map'.SUF,'roleid='.$roleid.',aid='.intval($iAid));
            }
        }
        echo json_encode(array('status'=>1,'data'=>array(),'msg'=> '保存成功'));
        Tpl::getHtmlStr(false);

    }

    public function pageAddRole() {
        Tpl::getHtmlStr(true);
        $get = $this->safeData($_GET);
        $db = new SDb();
        $permission = array('rolename'=>$get['rolename']);
        $permission['roleid'] = $db->insert('permission_role'.SUF,'rolename="'.$get['rolename'].'"');
        echo json_encode(array('status'=>1,'data'=>$permission));
        Tpl::getHtmlStr(false);

    }
}
?>
