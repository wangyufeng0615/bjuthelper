<!DOCTYPE html>
<html lang='zh_cn'>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=0">
    <title>成绩查询结果</title>
    <link rel="stylesheet" href="http://cdn.bootcss.com/weui/0.4.0/style/weui.min.css"/>
    <link rel="stylesheet" href="style/accordion.css">
</head>
<body>

<?php
include('core/BJUThelper.php');

session_start();
header("Content-type: text/html; charset=utf-8");  //视学校而定，一般是gbk编码，php也采用的gbk编码方式

$_SESSION['xh'] = $_POST['account'];
$xh = $_POST['account'];
$pw = $_POST['password'];
$current_year = $_POST['current_year'];
$current_term = $_POST['current_term'];

try {
    $b = new BJUThelper($xh, $pw);
} catch (Exception $e) {
    echo '<h2>&nbsp;<i class="weui_icon_warn"></i>&nbsp;您的账号 or 密码输入错误，或者是选择了无效的学年/学期，请<a href="/login_grade.php">返回</a>重新输入</h2>';
    exit();
}

$d = $b->compute_data($current_year, $current_term);
?>


<div class="weui_cells_title">课程统计情况</div>
<div class="container">
    <div class="weui_accordion_box">
        <div class="weui_accordion_title">
            <?= sprintf("本学期已出分课程数: %.2d ", $d['term_lesson_count']); ?>
        </div>
        <div class="weui_accordion_content">
            <p>
                <?= sprintf("大学总已出分课程数: %.2d ", $d['total_lesson_count']); ?>
            </p>
            <p>
                <?= sprintf("大学总未通过课程数: %.2d ", $d['all_number_of_lesson_with_nopass'] - $d['all_number_of_lesson']); ?>
            </p>
        </div>
    </div>
</div>
<div class="weui_cells_title">平均分</div>
<div class="weui_cells">
    <div class="weui_cell">
        <div class="weui_cell_bd weui_cell_primary" id="average_score">
            <p>
                <?= sprintf("您上大学以来总的加权平均分为: %.2lf 分", $d['average_score_all']); ?>
            </p>
        </div>
    </div>
    <div class="weui_cell">
        <div class="weui_cell_bd weui_cell_primary" id="average_score">
            <p>
                <?= sprintf("您上大学以来总的平均学分绩点(GPA)为: %.2lf ", $d['average_GPA_all']); ?>
            </p>
        </div>
    </div>
    <div class="weui_cell">
        <div class="weui_cell_bd weui_cell_primary" id="average_score">
            <p>
                <?= sprintf("您本学期的加权平均分为: %.2lf 分", $d['average_score_term']); ?>
            </p>
        </div>
    </div>
    <div class="weui_cell">
        <div class="weui_cell_bd weui_cell_primary" id="average_GPA">
            <p>
                <?= sprintf("您本学期的平均学分绩点(GPA)为: %.2lf", $d['average_GPA_term']); ?>
            </p>
        </div>
    </div>


    <div class="weui_cell">
        <div class="weui_cell_bd weui_cell_primary" id="average_score">
            <p>
                <i class="weui_icon_info"></i> GPA算法已更新为带权平均算法，和教务一致。
            </p>
        </div>
    </div>
</div>
<div>
    <?php
    //辅修/二专业课程信息输出
    if ($d['total_score_fuxiu'] > 0) {
        echo '
    <div class="weui_cell">
        <div class="weui_cell_bd weui_cell_primary" id="average_score">
            <p>';
        printf("辅修/二专业课程的加权平均分为: %.2lf 分", $d['average_score_fuxiu']);
        echo '</p>
        </div>
    </div>
    <div class="weui_cell">
        <div class="weui_cell_bd weui_cell_primary" id="average_GPA">
            <p>';
        printf("辅修/二专业课程的平均学分绩点为 %.2lf 分", $d['average_GPA_fuxiu']);
        echo ' </p >
        </div >
    </div >
    ';
    }
    ?>
</div>

<!--输出课程明细,主修课程-->
<div class="weui_cells_title"> 课程明细</div>
<div class="weui_cells">
    <?php
    $i = 5;
    $content = $d['grade_term'];
    while (isset($content[$i][7])) {
        if ($content[$i][9] == 0) {
            echo '
    <div class="weui_cell"> ';
            echo '
        <div class="weui_cell_bd weui_cell_primary"> ';
            echo iconv("gb2312", "utf-8//IGNORE", $content[$i][3]) . " 分数:
            " . iconv("gb2312", "utf-8//IGNORE", $content[$i][8]) . " 课程学分: " . $content[$i][6];
            echo '
        </div>
        ';
            echo '
    </div>
    ';
        }
        $i++;
    }
    echo '
</div> ';
    //输出辅修/二专业课程信息
    if ($d['total_score_fuxiu'] > 0) {
        echo '
<div class="weui_cells_title"> 辅修 / 二专业课程</div> ';
        echo '
<div class="weui_cells"> ';
        $i = 5;
        while (isset($content[$i][7])) {
            if ($content[$i][9] == 2) {
                echo '
    <div class="weui_cell"> ';
                echo '
        <div class="weui_cell_bd weui_cell_primary"> ';
                echo iconv("gb2312", "utf-8//IGNORE", $content[$i][3]) . " 分数: " . $content[$i][8] . " 课程学分: " . $content[$i][6];
                echo '
        </div>
        ';
                echo '
    </div>
    ';
            }
            $i++;
        }
        echo '
</div> ';
    }
    echo '<a class="weui_btn weui_btn_default" href="javascript:;" onClick="location.href=document.referrer"> 返回</a> ';
    ?>
    <!-- <script src = "weui/dist/example/zepto.min.js" ></script > -->
    <!-- <script src = "weui/dist/example/toast.js" ></script > -->
    <script src="http://cdn.bootcss.com/jquery/3.1.0/jquery.min.js"></script>
    <script src="/js/accordion.js"></script>
    <script src="/js/require_score.js"></script>
</body>
</html>
