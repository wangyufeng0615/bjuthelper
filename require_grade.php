<?php

include_once("core/BJUTHelper.php");

	if(isset($_POST["account"])) {
		$p_account = $_POST['account'];
		$p_password = $_POST['password'];
		$p_current_year = $_POST['current_year'];
		$p_current_term = $_POST['current_term'];
	}
	else {
		$p_account = $_GET['account'];
		$p_password = $_GET['password'];
		$p_current_year = $_GET['current_year'];
		$p_current_term = $_GET['current_term'];
	}

    session_start();
    header("Content-type: text/html; charset=utf-8");  //视学校而定，一般是gbk编码，php也采用的gbk编码方式

    $_SESSION['xh']=$p_account;

    $xh=$p_account;
    $pw=$p_password;
    $current_year=$p_current_year;
    $current_term=$p_current_term;

    $student = new BJUTHelper($xh, $pw);

    $login_success = $student->login();

    //若登陆信息输入有误
    if(!$login_success){
        echo '<h2>&nbsp;<i class="weui_icon_warn"></i>&nbsp;您的账号 or 密码输入错误，或者是选择了无效的学年/学期，请<a href="/login_grade.php">返回</a>重新输入</h2>';
        exit();
    }

    $result = $student->get_final_result($current_year, $current_term);

?>

<!DOCTYPE html>
<html lang='zh_cn'>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=0">
    <title><?php printf($p_account); ?> - 成绩查询结果</title>
    <link rel="stylesheet" href="//cdn.bootcss.com/weui/0.4.0/style/weui.min.css"/>
    <link rel="stylesheet" href="style/accordion.css">
</head>
<body>

<div class="weui_cells_title">课程统计情况</div>
<div class="container">
    <div class="weui_accordion_box">
        <div class="weui_accordion_title">
            <?php printf("本学期已出分课程数: %.2d ",$result["term_lesson_count"]); ?>
        </div>
        <div class="weui_accordion_content">
            <p>
                <?php printf("大学总已出分课程数: %.2d ",$result["total_lesson_count"]); ?>
            </p>
            <p>
                <?php printf("大学总未通过课程数: %.2d ",$result["all_number_of_lesson_unpassed"]); ?>
            </p>
        </div>
    </div>
</div>
<div class="weui_cells_title">总平均分</div>
<div class="container">
    <div class="weui_accordion_box">
        <div class="weui_accordion_title">
            <?php printf("大学期间总加权平均分: %.2lf 分",$result["average_score_all"]); ?>
        </div>
        <div class="weui_accordion_content">
            <p>
                <?php printf("含未通过课程均分（计实际分数）：%.2lf 分",  $result["average_score_include_unpassed"]); ?>
            </p>
            <p>
                <?php printf("未通过课程补考后均分（计60分）：%.2lf 分", $result["average_score_include_unpassed_passed"]); ?>
            </p>
        </div>
    </div>
</div>
<div class="container">
    <div class="weui_accordion_box">
        <div class="weui_accordion_title">
            <?php printf("大学期间总平均学分绩点（GPA）: %.2lf ",$result["average_GPA_all"]); ?>
        </div>
        <div class="weui_accordion_content">
            <p>
                <?php printf("含未通过课程绩点（未通过计0绩点）：%.2lf",  $result["average_GPA_include_unpassed"]); ?>
            </p>
            <p>
                <?php printf("未通过课程补考后绩点（计60分2绩点）：%.2lf", $result["average_GPA_include_unpassed_passed"]); ?>
            </p>
        </div>
    </div>
</div>
<div class="weui_cells_title">学期平均分</div>
<div class="weui_cells">
    <div class="weui_cell">
        <div class="weui_cell_bd weui_cell_primary" id="average_score">
            <p>
                <?php printf("本学期加权平均分: %.2lf 分", $result["average_score_term"]); ?>
            </p>
        </div>
    </div>
    <div class="weui_cell">
        <div class="weui_cell_bd weui_cell_primary" id="average_GPA">
            <p>
                <?php printf("本学期平均学分绩点（GPA）: %.2lf",$result["average_GPA_term"]); ?>
            </p>
        </div>
    </div>
    <div class="weui_cell">
        <div class="weui_cell_bd weui_cell_primary" id="average_score">
            <h5>
                <i class="weui_icon_info"></i> GPA 根据 <a href="http://undergrad.bjut.edu.cn/WebInfo.aspx?Id=752">北工大教务处文件</a>，采用四分制计算。其他学校可能采用不同算法。
            </h5>
        </div>
    </div>
</div>

<?php
//辅修/二专业课程信息输出
if ($result["total_value_minor"] > 0) {

?>
    <div class="weui_cell">
        <div class="weui_cell_bd weui_cell_primary" id="average_score">
			<p>
                <?php printf("辅修/二专业课程的加权平均分为: %.2lf 分", $result["average_score_minor"]); ?>
            </p>
        </div>
    </div>
    <div class="weui_cell">
        <div class="weui_cell_bd weui_cell_primary" id="average_GPA">
			<p>
                <?php printf("辅修/二专业课程的平均学分绩点为 %.2lf 分",$result["average_GPA_minor"]); ?>
            </p>
        </div>
    </div>
<?php
}
?>

<!-- <script src="weui/dist/example/zepto.min.js"></script> -->
<!-- <script src="weui/dist/example/toast.js"></script> -->
<script src="//cdn.bootcss.com/jquery/3.1.0/jquery.min.js"></script>
<script src="/js/accordion.js"></script>
<script src="/js/require_score.js"></script>

<div class="weui_cells_title">课程明细</div>
<div class="weui_cells">

<?php
//输出课程明细,主修课程
foreach($result["grade_term"] as $course){
    if ($course->minor_maker == 0){
        echo '<div class="weui_cell">';
        echo '<div class="weui_cell_bd weui_cell_primary">';
        echo $course->name."  分数: ".$course->score."   课程学分: ".$course->credit;
        echo '</div>';
        echo '</div>';
    }
}
?>
</div>
<?php
//输出辅修/二专业课程信息
if ($result["total_value_minor"] > 0) {
    // if ($total_score_fuxiu > 0 || $total_score_secondmajor > 0) {
    ?>
<div class="weui_cells_title">辅修/二专业课程</div>
<div class="weui_cells">
        <?php
    foreach ($result["grade_term"] as $course){
        if ($course->minor_maker == 2){
            echo '<div class="weui_cell">';
            echo '<div class="weui_cell_bd weui_cell_primary">';
            echo $course->name."  分数: ".$course->score."   课程学分: ".$course->credit;
            echo '</div>';
            echo '</div>';
        }
        //辅修信息
        if ($course->minor_maker == 1){
            echo '<div class="weui_cell">';
            echo '<div class="weui_cell_bd weui_cell_primary">';
            echo $course->name."  分数: ".$course->score."   课程学分: ".$course->credit;
            echo '</div>';
            echo '</div>';
        }
    }
    ?>
</div>
<?php
}
?>

<a class="weui_btn weui_btn_default" href="javascript:;" onClick="location.href=document.referrer">返回</a>

</body>
</html>