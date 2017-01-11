<!DOCTYPE html>
<html lang='zh_cn'>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=0">
	<title>成绩查询结果</title>
	<link rel="stylesheet" href="//cdn.bootcss.com/weui/0.4.0/style/weui.min.css"/>
	<link rel="stylesheet" href="style/accordion.css">
</head>
<body>

<?php 
    session_start();
	$cookie='';
    header("Content-type: text/html; charset=utf-8");  //视学校而定，一般是gbk编码，php也采用的gbk编码方式
    
    //function: 构造post数据并登陆
    function login_post($url,$post){
		global $cookie;
        $ch = curl_init($url);
        //curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   //不自动输出数据，要echo才行
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);  //重要，抓取跳转后数据
		if (strlen($cookie)) curl_setopt($ch, CURLOPT_COOKIE, $cookie); //if have cookie, set it
        //curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie); 
        //curl_setopt($ch, CURLOPT_REFERER, 'http://gdjwgl.bjut.edu.cn/default2.aspx');  //重要，302跳转需要referer，可以在Request Headers找到 
        curl_setopt($ch, CURLOPT_POSTFIELDS,$post);   //post提交数据
        $result=curl_exec($ch);
		if (preg_match('/Set-Cookie: (.+?);/',$result,$str)){
			$cookie = $str[1];
		} //match the cookie
		curl_close($ch);
        return $result;
    }
    //获取VIEWSTATE
    $_SESSION['xh']=$_POST['account'];
    $xh=$_POST['account'];
    $pw=$_POST['password'];
    $current_year=$_POST['current_year'];
    $current_term=$_POST['current_term'];
    //$code= $_POST['verify_code'];
    //$cookie = dirname(__FILE__) . '/cookie/'.$_SESSION['id'].'.txt';
    $url="http://gdjwgl.bjut.edu.cn/default_vsso.aspx";  //教务地址
    //$con1=login_post($url,$cookie,'');               //登陆
    //preg_match_all('/<input type="hidden" name="__VIEWSTATE" value="([^<>]+)" \/>/', $con1, $view); //获取__VIEWSTATE字段并存到$view数组中
    //为登陆准备的POST数据
    
    $post=array(
        //'__VIEWSTATE'=>$view[1][0],
        'TextBox1'=>$xh,
        'TextBox2'=>$pw,
        //'txtSecretCode'=>$code,
        'RadioButtonList1_2'=>'%D1%A7%C9%FA',  //“学生”的gbk编码
        'Button1'=>'',
        //'lbLanguage'=>'',
        //'hidPdrs'=>'',
        //'hidsc'=>''
        );
    $con2=login_post($url,http_build_query($post)); //将数组连接成字符串, 登陆教务系统
    
    //若登陆信息输入有误
    if(!preg_match("/xs_main/", $con2)){
		//echo $con2;
        echo '<h2>&nbsp;<i class="weui_icon_warn"></i>&nbsp;您的账号 or 密码输入错误，或者是选择了无效的学年/学期，请<a href="/login_grade.php">返回</a>重新输入</h2>';
        exit();
    }

    //Login done.
    require_score($cookie, $current_year, $current_term);    //获取加权平均分和成绩明细
    
	function require_score($cookie, $current_year, $current_term){
		// 不知道为什么，不提交姓名信息也能查询
		// preg_match_all('/<span id="xhxm">([^<>]+)/', $con2, $xm);   //正则出的数据存到$xm数组中
		// print_r($xm);
		// $xm[1][0]=substr($xm[1][0],0,-4);  //字符串截取，获得姓名
		$url2="http://gdjwgl.bjut.edu.cn/xscjcx.aspx?xh=".$_SESSION['xh'];
		$viewstate=login_post($url2,'');
		preg_match_all('/<input type="hidden" name="__VIEWSTATE" value="([^<>]+)" \/>/', $viewstate, $vs);
		$state=$vs[1][0];  //$state存放一会post的__VIEWSTATE
		//查询某一学期的成绩
		$post=array(
		 '__EVENTTARGET'=>'',
		 '__EVENTARGUMENT'=>'',
		 '__VIEWSTATE'=>$state,
		 'hidLanguage'=>'',
		   'ddlXN'=>$current_year,  //当前学年
		   'ddlXQ'=>$current_term,  //当前学期
		   'ddl_kcxz'=>'',
		   'btn_xq'=>'%D1%A7%C6%DA%B3%C9%BC%A8'  //“学期成绩”的gbk编码，视情况而定
		   );
		$content=login_post($url2,http_build_query($post)); //获取原始数据
		$content=get_td_array($content);    //table转array
		//查询总成绩
		$post_allgrade=array(
		 '__EVENTTARGET'=>'',
		 '__EVENTARGUMENT'=>'',
		 '__VIEWSTATE'=>$state,
		 'hidLanguage'=>'',
		   'ddlXN'=>$current_year,  //当前学年
		   'ddlXQ'=>$current_term,  //当前学期
		   'ddl_kcxz'=>'',
		   'btn_zg'=>'%BF%CE%B3%CC%D7%EE%B8%DF%B3%C9%BC%A8'  //课程最高成绩-gbk
		   );
		$content_allgrade=login_post($url2,http_build_query($post_allgrade)); //获取原始数据
		$content_allgrade=get_td_array($content_allgrade);    //table转array
		//计算总的加权分数和总的GPA
		$i = 5;         //从array[5]开始是有效信息
		$all_score = 0; //总的加权*分数
		$all_value = 0; //总的学分权值
		$all_GPA = 0;   //总的GPA*分数
		$all_number_of_lesson = 0;  //总的课程数
		$all_number_of_lesson_with_nopass = 0; //包含未过课程的总数
		//计算总和的东西，学分/GPA
		while(isset($content_allgrade[$i][4])){
			//不计算第二课堂和新生研讨课以及未通过课程
			if ($content_allgrade[$i][5] == iconv("utf-8","gb2312//IGNORE","第二课堂") || $content_allgrade[$i][1] == iconv("utf-8","gb2312//IGNORE","新生研讨课") || $content_allgrade[$i][4] < 60){
				if ($content_allgrade[$i][4] < 60 && is_integer($content_allgrade[$i][4])) $all_number_of_lesson_with_nopass++;
				$i++;
			}
			else{
				$all_score += ($content_allgrade[$i][3] * $content_allgrade[$i][4]);  //  累加总分
				$all_value += $content_allgrade[$i][3];    //  累加学分(权值)
				
				if ($content_allgrade[$i][4] >= 85 && $content_allgrade[$i][4] <= 100){
					$all_GPA += (4.0 * $content_allgrade[$i][3]);
				}
				else if ($content_allgrade[$i][4] >= 70 && $content_allgrade[$i][4] < 85){
					$all_GPA += (3.0 * $content_allgrade[$i][3]);
				}
				else if ($content_allgrade[$i][4] >= 60 && $content_allgrade[$i][4] < 70){
					$all_GPA += (2.0 * $content_allgrade[$i][3]);
				}
				$i++;
				$all_number_of_lesson++;
				$all_number_of_lesson_with_nopass++;
			}
		}
		$total_lesson_count = $i-5;
		//个别学期加权平均分和GPA的计算
		$i = 5;                       //array从5开始是课程，定死了，不能改
		//主修课程
		$total_score = 0;
		$total_value = 0;
		$total_GPA = 0;
		$number_of_lesson = 0;        //主修总课程数
		//二专业和辅修，content[$i][9] == 2
		$total_score_fuxiu = 0;
		$total_value_fuxiu = 0;
		$total_GPA_fuxiu = 0;
		$number_of_lesson_fuxiu = 0;  //二专业/辅修课程数
		//计算个别学期的信息
		while(isset($content[$i][8])){
			if ($content[$i][5] == iconv("utf-8","gb2312//IGNORE","第二课堂") || $content[$i][3] === iconv("utf-8","gb2312//IGNORE","新生研讨课") || $content[$i][8] < 60){
				$i++;
			}
			else{
				//处理辅修/二专业
				if ($content[$i][9] == 2){
					$total_score_fuxiu += ($content[$i][8] * $content[$i][6]);  //  累加总分
					$total_value_fuxiu += $content[$i][6];    //  累加学分(权值)
					$total_GPA_fuxiu += ($content[$i][7] * $content[$i][6]); //加权总绩点
					$i++;
					$number_of_lesson_fuxiu++;
				}  
				//普通课程
				if ($content[$i][9] == 0){
					$total_score += ($content[$i][8] * $content[$i][6]);  //  累加总分
					$total_value += $content[$i][6];    //  累加学分(权值)
					$total_GPA += ($content[$i][7] * $content[$i][6]); //加权总绩点
					$i++;
					$number_of_lesson++;
				}
			}
		}
		$average_score = $total_score / $total_value;
		$average_score_fuxiu = $total_score_fuxiu / $total_value_fuxiu;
		$term_lesson_count = $i-5;
		echo'
		<div class="weui_cells_title">课程统计情况</div>
		<div class="container">
		<div class="weui_accordion_box">
		<div class="weui_accordion_title">
		';
		printf("本学期已出分课程数: %.2d ",$term_lesson_count);
		echo'
		</div>
		<div class="weui_accordion_content">
		<p>';
		printf("已出分课程总数: %.2d ",$total_lesson_count);
		echo '
		</p>
		<p>';
		printf("未通过课程总数: %.2d ",$all_number_of_lesson_with_nopass - $all_number_of_lesson);
		echo '
		</p>
		</div>
		</div>
		</div>';

		echo'
		<div class="weui_cells_title">平均分</div>
		<div class="weui_cells">
		<div class="weui_cell">
		<div class="weui_cell_bd weui_cell_primary" id="average_score">
		<p>';
		printf("您上大学以来总的加权平均分为: %.2lf 分",$all_score / $all_value);
		echo'</p>
		</div>
		</div>
		<div class="weui_cell">
		<div class="weui_cell_bd weui_cell_primary" id="average_score">
		<p>';
		printf("您上大学以来总的平均学分绩点为: %.2lf ",$all_GPA / $all_value);
		echo'</p>
		</div>
		</div>
		<div class="weui_cell">
		<div class="weui_cell_bd weui_cell_primary" id="average_score">
		<p>';
		printf("您本学期的加权平均分为: %.2lf 分",$average_score);
		echo'</p>
		</div>
		</div>
		<div class="weui_cell">
		<div class="weui_cell_bd weui_cell_primary" id="average_GPA">
		<p>';
		printf("您本学期的平均学分绩点为: %.2lf",$total_GPA / $total_value);
		echo'
		</p>
		</div>
		</div>';
		//辅修/二专业课程信息输出
		if ($total_score_fuxiu > 0) {
			echo'
			<div class="weui_cell">
			<div class="weui_cell_bd weui_cell_primary" id="average_score">
			<p>';
			printf("辅修/二专业课程的加权平均分为: %.2lf 分",$average_score_fuxiu);
			echo'</p>
			</div>
			</div>
			<div class="weui_cell">
			<div class="weui_cell_bd weui_cell_primary" id="average_GPA">
			<p>';
			printf("辅修/二专业课程的平均学分绩点为 %.2lf 分",$total_GPA_fuxiu / $total_value_fuxiu);
			echo'</p>
			</div>
			</div>';
		}
		echo'
		</div> 
		<!-- <script src="weui/dist/example/zepto.min.js"></script> -->
		<!-- <script src="weui/dist/example/toast.js"></script> -->
		<script src="//cdn.bootcss.com/jquery/3.1.0/jquery.min.js"></script>
		<script src="/js/accordion.js"></script>
		<script src="/js/require_score.js"></script>
		</body>
		</html>';
		//输出课程明细,主修课程
		echo '<div class="weui_cells_title">课程明细</div>';
		echo '<div class="weui_cells">';
		$i = 5;
		while(isset($content[$i][7])){   
			if ($content[$i][9] == 0){
				echo '<div class="weui_cell">';
				echo '<div class="weui_cell_bd weui_cell_primary">';
				echo iconv("gb2312","utf-8//IGNORE",$content[$i][3])."  分数: ".iconv("gb2312","utf-8//IGNORE",$content[$i][8])."   课程学分: ".$content[$i][6];
				echo '</div>';
				echo '</div>';    
			}  
			$i++;
		}   
		echo '</div>';
		//输出辅修/二专业课程信息
		if ($total_score_fuxiu > 0 || $total_score_secondmajor > 0) {
			echo '<div class="weui_cells_title">辅修/二专业课程</div>';
			echo '<div class="weui_cells">';
			$i = 5;
			while(isset($content[$i][7])){
				if ($content[$i][9] == 2){
					echo '<div class="weui_cell">';
					echo '<div class="weui_cell_bd weui_cell_primary">';
					echo iconv("gb2312","utf-8//IGNORE",$content[$i][3])."  分数: ".$content[$i][8]."   课程学分: ".$content[$i][6];
					echo '</div>';
					echo '</div>';    
				}     
				$i++;
			}   
			echo '</div>';       
		}
		echo '<a class="weui_btn weui_btn_default" href="javascript:;" onClick="location.href=document.referrer">返回</a>';
	}
	//table转array
    function get_td_array($table) {
        $table = preg_replace("'<table[^>]*?>'si","",$table);
        $table = preg_replace("'<tr[^>]*?>'si","",$table);
        $table = preg_replace("'<td[^>]*?>'si","",$table);
        $table = str_replace("</tr>","{tr}",$table);
        $table = str_replace("</td>","{td}",$table);
            //去掉 HTML 标记
        $table = preg_replace("'<[/!]*?[^<>]*?>'si","",$table);
            //去掉空白字符
        $table = preg_replace("'([rn])[s]+'","",$table);
        $table = preg_replace('/&nbsp;/',"",$table);
        $table = str_replace(" ","",$table);
        $table = str_replace(" ","",$table);
        $table = explode('{tr}', $table);
        array_pop($table);
        foreach ($table as $key=>$tr) {
            $td = explode('{td}', $tr);
            array_pop($td);
            $td_array[] = $td;
        }
        return $td_array;
    }
?>