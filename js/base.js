// var limiturl = "http://119.23.117.81/main/main/checklimit";
// var rooturl = "http://119.23.117.81/main/main/";
var limiturl = "http://119.23.117.81/main/main/checklimit";
var rooturl = "http://119.23.117.81/main/main/";
// JavaScript Document
$(document).ready(function () {
	/*点击新增员工数据按钮*/
	$("input[name='limit']").click(function () {
		if(checkLimit() > 0){
			var url = $(this).attr('url');
			if(url.length > 0){
				$("#showframe").attr('src', url);
			}
		} else {
			alert("你没有权限对此功能进行操作");		
		}
	});
	/*点击新增员工数据按钮*/
	
	/*权限判断*/
	$("a[name='limit']").click(function () {
		if(checkLimit() > 0){
			var url = $(this).attr('href');
			if(url.length > 0){
				$("#showframe").attr('src', url);
			}
		} else {
			alert("你没有权限对此功能进行操作");		
		}
	});
	/*权限判断*/
	
	/*表格选中高亮*/
	$("tr[name='list']").mouseover(function () {
		$(this).css("background-color","#33CCFF");
	});
	$("tr[name='list']").mouseout(function () {
		$(this).css("background-color","#fff");
	});
	/*表格选中高亮*/
	
	/*切换加分规则*/
	$("input[name='rule']").change(function () {
		var val = $(this).val();
		if(val == 1){
			$("#rule2").hide();
			$("#rule1").show();
		} else {
			$("#rule2").show();
			$("#rule1").hide();
		}
	});
	/*切换加分规则*/
	
	/*员工查找*/
	$("input[name='staffName']").blur(function () {
		var staffName = $(this).val();
		if(staffName.length > 0){
			$(".tips label").css("display", "none");
			$("#similar").css("display", "none");
			$("#similar div[name='staffs']").empty();
			$("#loading").css("display", "inherit");
			var url = rooturl+"getstaff";
			$.ajax({
				url:url,
				dataType:"json",
				type:"POST",
				data:"staffName="+staffName,
				success: function(msg){
					$(".tips label").css("display", "none");
					if(msg['state'] == 0){
						$("#no").css("display", "block");
						if(msg["staffs"]){
							$("#similar").css("display", "block");
							var similars = "";
							for(var i=0, j=msg["staffs"].length; i<j; i++){
								similars += "<label style=\"cursor:pointer;background-color:#1e84ab;line-height:26px;border-radius:15px;font-size:16px;color:#FFF; padding-left:5px; padding-right:5px;\">"+msg["staffs"][i]+"</label> ";
								if(i > 5){
									break;
								}
							}
							$("#similar div[name='staffs']").append(similars);
						}
					} else if(msg['state'] == 1){
						$("#yes").css("display", "block");
						$("#yes").css("display", "block");
					}
				}
			});
		}
	});
	$("input[name='staffName']").focus(function () {
		var staffName = $(this).val();
		$("#similar").css("display", "none");
		$(".tips label").css("display", "none");
		$("#similar div[name='staffs']").empty();
	});
	/*员工查找*/
	
	/*搜索员工*/
	$("#searchStaff").click(function () {
		var key = $("input[name='keywords']").val();
		if(!(key.length>0)){
			alert("请输入需要查看的员工姓名");
			return false;
		}
		$("form[name='searchkey']").submit();
		return false;
	});
	/*搜索员工*/
	
	$(".tj01").click(function () {
		$("form").submit();
	});
	
	/*确认扣分、加分*/
	/*$("form[name='score']").submit(function () {
		var tips = "确定因为";
		var score = $("input[name='score']").val();
		var staff = $("input[name='staffName']").val();
		var reason = $("textarea[name='reason']").val();
		var isOk = $("#yes").css("display");
		var isPass = false;
		if(!staff.length>0){
			alert("请输入需要加分/扣分员工姓名");
			$("input[name='staffName']").focus();
		} else if(isOk == "none"){
			alert("请输入正确的员工姓名");
			$("input[name='staffName']").focus();
		}else if(isNaN(score)){
			alert("请正确填写需要增加或则扣除的分数");
			$("input[name='score']").val("");
			$("input[name='score']").focus();
		} else if(!reason.length > 0){
			alert("请填写加分/扣分原因");
			reason = $("textarea[name='reason']").focus();
		} else {
			isPass = true;
		}
		var word = "";
		if(score < 0){
			word = "扣除"; 
			score = -score;
		} else {
			word = "增加"; 
		}
		tips = tips + reason + "为" + staff + word + score + "分？";
		if(isPass){
			var sure = confirm(tips);
		}
		if(!sure){
			return false;
		}
	});*/
	/*确认扣分、加分*/
	
	/*点击选中*/
	$("div[name='staffs'] label").live("click",function () {
		var staffName = $(this).text();
		$("input[name='staffName']").val(staffName);
		$("input[name='staffName']").focus();
		$("#similar").css("display", "none");
		$(".tips label").css("display", "none");
		$("#similar div[name='staffs']").empty();
	});
	/*点击选中*/
	
	$("a[name='del']").click(function () {
		var url = $(this).attr('href');
		var isdel = confirm("确认删除");
		if(!isdel){
			return false;
		}
	});
	
	$("a[name='leave']").click(function () {
		var url = $(this).attr('href');
		var isdel = confirm("确认该员工已离职？");
		if(!isdel){
			return false;
		}
	});
	
	/*选择类型*/
	$("#selectClass").change(function () {
		var op = $(this).val();
		var c1 = $(this);
		$("input[name='scoreCode']").val("");
		if(op == 0){
			return;	
		}
		var scoreClass = $(this).val()
		var url = rooturl + "getdetail";
		$.ajax({
			url:url,
			dataType:"json",
			type:"POST",
			data:"classId="+op+"&score_class="+scoreClass+"&c2=1",
			success: function(msg){
				if(typeof($("#c2s").html()) != "undefined"){
					$(msg['c2']).replaceAll("#c2s");
				} else {
					c1.after(msg['c2']);
				}
				$(msg['html']).replaceAll("#selectDetail");
			}
		});
	});
	
	$("#c2s").live("change", function() {
		var op = $(this).val();
		if(op == 0){
			return;	
		}
		var scoreClass = $(this).val()
		var url = rooturl + "getdetail";
		$.ajax({
			url:url,
			dataType:"json",
			type:"POST",
			data:"classId="+op+"&score_class="+scoreClass,
			success: function(msg){
				$(msg['html']).replaceAll("#selectDetail");
			}
		});
	});
	
	/*选择类型*/
	
	/*按键颜色变换*/
	$(".rb").mouseover(function () {
		$(this).css("background-color", "#333");
	});
	
	$(".rb").mouseout(function () {
		$(this).css("background-color", "#666");
	});
	/*按键颜色变换*/
	
	/*选择处理详情*/
	$("#selectDetail").live("click", function (){
		var op = $("#selectDetail").find("option:selected").val();
		$("#fscore").remove();
		$("#interval").remove();
		if(op > 0){
			$("input[name='scoreCode']").attr("value",$("#selectDetail").find("option:selected").attr("code"));
			var type = $("#selectDetail").find("option:selected").attr('t');
			var mi = $("#selectDetail").find("option:selected").attr('mi');
			var mx = $("#selectDetail").find("option:selected").attr('mx');
			if(type == 2){
				$("#selectDetail").after(' <span id="fscore"><input class="w80" type="text" name="fscore">请输入'+mi+"至"+mx+"分</span>");
			} else if(type == 3){
				$("#selectDetail").after('<div id="interval" >每<input name="interval" type="text" class="w30">个月加分一次</div>');
			}
		}
	});
	/*选择处理详情*/
	
	/*获得职位*/
	$("#steam").change(function() {
		var pid = $(this).val();
		if(!(pid > 0)){
			return ;
		}
		var url = rooturl + "getpos";
		$.ajax({
				url:url,
				dataType:"json",
				type:"POST",
				data:"pid="+pid,
				success: function(msg){
					$(msg['html']).replaceAll("#spos");
				}
			});
	});
	
	$("select[name='st']").change(function () {
		var pid = $(this).val();
		if(!(pid > 0)){
			return ;
		}
		var url = rooturl + "getpos";
		$.ajax({
				url:url,
				dataType:"json",
				type:"POST",
				data:"pid="+pid,
				success: function(msg){
					var spos = $("select[name='spos']");
					if(typeof(spos.html()) != 'undefined'){
						$(msg['html']).replaceAll(spos);
					}
					spos.die();
				}
			});
	});
	/*获得职位*/
	
	/*获得员工姓名*/
	$("#spos").live('change',function() {
		var pid = $(this).val();
		if(!(pid > 0)){
			return ;
		}
		var url = rooturl + "getpeop";
		$.ajax({
				url:url,
				dataType:"json",
				type:"POST",
				data:"pid="+pid,
				success: function(msg){
					$(msg['html']).replaceAll("#sname");
				}
			});
	});
	/*获得员工姓名*/
	
	/*选定员工*/
	$("#sname").live('change',function() {
		var staffId = $(this).val();
		if(!(staffId > 0)){
			return ;
		}
		var name = $(this).find("option:selected").text();
		$("input[name='staffName']").val(name);
		$("input[name='staffName']").focus();
	});
	/*选定员工*/
	
	/*使用代码处理积分*/
	$("input[name='scoreCode']").blur(function () {
		var code = $(this).val();
		$("#c2s").remove();
		$("#interval").remove();
		if(code.length > 0){
			var url = rooturl + "codedeal";
			$.ajax({
				url:url,
				dataType:"json",
				type:"POST",
				data:"code="+code,
				success: function(msg){
					if(msg['s'] == 1){
						$("#selectClass option:selected").removeAttr("selected");
						$("#selectClass option[value='"+msg['c1']+"']").attr("selected", "selected");
						if(msg['c2s']){
							$("#selectClass").after(msg['c2s']);
						}
						$("#c2s option[value='"+msg['c2']+"']").attr("selected", "selected");
						$(msg['detailList']).replaceAll("#selectDetail");
						$("#selectDetail option[value='"+msg['detail']['score_detail_id']+"']").attr("selected", "selected");
						$("input[name='scoreDeal']").removeAttr("checked");
						if(msg['detail']['type']==2){
							$("#selectDetail").after(' <span id="fscore"><input class="w80" type="text" name="fscore">请输入'+msg['detail']['score']+"至"+msg['detail']['score_max']+"分</span>");
						}
					}
				}
			});
		}
	});
	/*使用代码处理积分*/
	$(".dl_left").click(function () {
		$("form").submit();
	});
	
	/*获得二级分类*/
	$("#scoreclass").change(function () {
		var classId = $(this).val();
		var op = $(this);
		var url = rooturl + "get2c";
		if(classId > 0){
			$.ajax({
				url:url,
				dataType:"json",
				type:"POST",
				data:"cid="+classId,
				success: function(msg){
					if(msg['s']==1){
						if(typeof($("#c2").html()) != "undefined"){
							$(msg['html']).replaceAll("#c2");
						} else {
							op.after(msg['html']);
						}
					} else {
						$("").replaceAll("#c2");	
					}
				}
			});
		} else {
			$("").replaceAll("#c2");
		}
	});
	/*获得二级分类*/
	
	/*选择积分方式*/
	$("input[name='type']").click(function () {
		var type = $(this).val();
		if(type==2){
			$("#type1").hide();
			$("#type2").show();
		} else {
			$("#type2").hide();
			$("#type1").show();
		}
	});
	/*选择积分方式*/

	$("select[name='staffLimit']").click(function () {
		if(this.options[this.selectedIndex].value == 1) {
		    $('#roleid_div').show();
		} else {
            $('#roleid_div').hide();
		}
    });
	
}); 

/*查看权限*/
function checkLimit() {
	var limit = 0;
	$.ajax({
		url:limiturl,
		dataType:"json",
		type:"POST",
		async:false,
		success:function(data){
			limit = data["staffLimit"];
		}
	});
	return limit;
}
/*查看权限*/

