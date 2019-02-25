<?php
include_once ("model/Course.php");
include_once ("model/CourseDetailed.php");
include_once ("model/CourseSchedule.php");
include_once ("utils.php");

/**
 * 所有的网页解析器
 * User: remini
 * Date: 2018/10/8
 * Time: 11:28 AM
 */

/**
 * 通过response提取set-cookie的值，如无set-cookie则返回''
 * 可配合requests中的login使用
 * @param $http_response
 * @return mixed
 */
function login_cookie_parser($http_response){
    if (preg_match('/Set-Cookie: (.+?);/',$http_response,$str)){
        $cookie = $str[1];
        return $cookie;
    } //match the cookie
    else{
        return '';
    }
}

/**
 * 通过response判断是否登录错误（账号密码错误等等）
 * @param $http_response
 * @return bool
 */
function check_login_success_parser($http_response){
    if(!preg_match("/xs_main/", $http_response)){
        //echo $con2;
        return false;
    }
    return true;
}

/**
 * 解析获取成绩查询接口所需的view_state
 * 配合send_view_state_requests使用
 * @param $http_response
 * @return mixed
 */
function view_state_parser($http_response){
    preg_match_all('/<input type="hidden" name="__VIEWSTATE" value="([^<>]+)" \/>/', $http_response, $vs);
    $state=$vs[1][0];  //$state存放一会post的__VIEWSTATE
    return $state;
}


/**
 * table转array
 * 为了可移植性而保留
 * @param string $content
 * @param bool $delete_tag 是否删除其它html标签
 * @return array
 */
function table_to_array(string $content, bool $delete_tag=true) {

    $content = preg_replace("'<table[^>]*?>'si","",$content);
    $content = preg_replace("'<tr[^>]*?>'si","",$content);
    $content = preg_replace("'<td[^>]*?>'si","",$content);
    $content = str_replace("</tr>","{tr}",$content);
    $content = str_replace("</td>","{td}",$content);
    //去掉 HTML 标记
    if($delete_tag){
        $content = preg_replace("'<[/!]*?[^<>]*?>'si","",$content);
    }
    //去掉空白字符
    $content = preg_replace("'([rn])[s]+'","",$content);
    $content = preg_replace('/&nbsp;/',"",$content);
    $content = str_replace(" ","",$content);
    $content = str_replace(" ","",$content);
    $content = explode('{tr}', $content);
    array_pop($content);
    $td_array = [];
    foreach ($content as $key=>$tr) {
        $td = explode('{td}', $tr);
        array_pop($td);
        $td_array[] = $td;
    }
    return $td_array;

}



/**
 * 该项目专用的课程抽取方法
 * @param $content
 * @return array
 */
function extract_grade_table_parser(string $content) {
    //去除多余空行
    $content = str_replace("\t", "", $content);
    $content = str_replace("\n", "", $content);
    $content = str_replace("\r", "", $content);

    $array = table_to_array($content);

    return $array;

}



/**
 * 该项目专用的课表抽取方法
 * @param $content
 * @return array
 */
function extract_schedule_table_parser(string $content) {
    //去除多余空行
    $content = str_replace("\t", "", $content);
    $content = str_replace("\n", "", $content);
    $content = str_replace("\r", "", $content);

    $array = table_to_array($content, false);

    return $array;

}



/**
 * 请务必注意以下两点：
 *      传进来的是【单个】课程的array数组
 *      获取的接口应该是all课程的接口
 *      输出的是单个Course对象
 * @param $course_array
 * @return Course
 */
function array_course_from_all_factory($course_array){
    $result = new Course();
    $result->year = $course_array[0];
    $result->term = $course_array[1];
    $result->id = $course_array[2];
    $result->name = $course_array[3];
    $result->credit = $course_array[4];
    $result->type = $course_array[5];
    $result->score = $course_array[6];
    $result->belong = $course_array[7];
    return $result;
}
/**
 * 请务必注意以下两点：
 *      传进来的是【单个】课程的array数组
 *      获取的接口应该是指定学期课程的接口
 *      输出的是单个CourseDetailed对象
 * @param $course_array
 * @return CourseDetailed
 */
function array_course_from_specific_factory($course_array){
    $result = new CourseDetailed();
    $result->year = $course_array[0];           //学年
    $result->term = $course_array[1];           //学期
    $result->id = $course_array[2];             //课程代码
    $result->name = $course_array[3];           //课程名称
    $result->type = $course_array[4];           //课程性质
    $result->belong = $course_array[5];         //课程归属
    $result->credit = $course_array[6];         //学分
    $result->gpa = $course_array[7];            //绩点
    $result->score = $course_array[8];          //成绩
    $result->minor_maker = $course_array[9];    //辅修标记
    $result->makeup_score = $course_array[10];   //补考成绩
    $result->retake_score = $course_array[11];   //重修成绩
    $result->academy = $course_array[12];        //开课学院
    $result->comment = $course_array[13];        //备注
    $result->retake_maker = $course_array[14];   //重修标记
    return $result;
}

/**
 * 请务必注意以下两点：
 *      传进来的是【单个】课程的array数组
 *      获取的接口应该是指定学期课程的接口
 *      输出的是单个CourseDetailed对象
 * @param $course_array
 * @return CourseSchedule
 */
function array_course_from_schedule_factory($course_array){
    $result = new CourseSchedule();
    $result->name = $course_array[0];
    $result->time = $course_array[1];
    $result->teacher = $course_array[2];
    $result->classroom = $course_array[3];
    return $result;
}

/**
 * 解析获取成绩为数组
 * 配合send_view_state_requests使用
 * @param $http_response
 * @return mixed
 */
function all_grade_parser(string $http_response){

    $array = extract_grade_table_parser($http_response);
    //去前
    $array = array_slice($array, 1);
    //排后
    $array = array_slice($array, 0, count($array) - 0);

    $result = [];
    foreach ($array as $i){
        $r = array_course_from_all_factory($i);
        array_push($result, $r);
    }

    return $result;

    /*
         * 解析后数组格式大致如下：
         * 特此记录供以后参考。
         * 因此，从第五个开始为课程，最后3个也是废弃信息。
         * 序号与课程对应关系见[4]
         *
array (size=56)
  4 =>
    array (size=6)
      0 => string '学年' (length=39)
      1 => string '学期' (length=12)
      2 => string '课程代码' (length=12)
      3 => string '课程名称' (length=12)
      4 => string '学分' (length=6)
      5 => string '课程性质' (length=12)
      6 => string '最高成绩值' (length=15)
      7 => string '课程归属' (length=12)
     */
//    array (
//        0 => '2017-2018',
//        1 => '2',
//        2 => '0003338',
//        3 => 'JAVA程序设计',
//        4 => '2.0',
//        5 => '学科基础选修课',
//        6 => '83',
//        7 => '',
//    )
    }

/**
 * 从查分页面解析基本信息
 * 配合send_view_state_requests使用
 * @param $http_response
 * @return mixed
 */
function personal_score_info_parser(string $http_response){

    $result = array(
        "sid"=> divide_string_by_colon(
            get_content_by_tag_and_id($http_response, "span", "Label3")
        )[1], //学号
        "name"=> divide_string_by_colon(
            get_content_by_tag_and_id($http_response, "span", "Label5")
        )[1],//姓名
        "institute"=> divide_string_by_colon(
            get_content_by_tag_and_id($http_response, "span", "Label6")
        )[1],//学院
        "major"=>
            get_content_by_tag_and_id($http_response, "span", "Label7"),//专业
        "direction"=> "", //这项学校不再提供了
        "class"=> divide_string_by_colon(
            get_content_by_tag_and_id($http_response, "span", "Label8")
        )[1],//行政班
    );

    return $result;

}



/**
 * 从课表页面解析基本信息
 * 配合send_view_state_requests使用
 * @param $http_response
 * @return mixed
 */
function personal_schedule_info_parser(string $http_response){

    $result = array(
        "sid"=> divide_string_by_colon(
            get_content_by_tag_and_id($http_response, "span", "Label5")
        )[1], //学号
        "name"=> divide_string_by_colon(
            get_content_by_tag_and_id($http_response, "span", "Label6")
        )[1],//姓名
        "institute"=> divide_string_by_colon(
            get_content_by_tag_and_id($http_response, "span", "Label7")
        )[1],//学院
        "major"=> divide_string_by_colon(
            get_content_by_tag_and_id($http_response, "span", "Label8")
        )[1],//专业
        "direction"=> "", //这项学校不再提供了
        "class"=> divide_string_by_colon(
            get_content_by_tag_and_id($http_response, "span", "Label9")
        )[1],//行政班
    );

    return $result;

}



    /**
     * 解析某一学期成绩
     * @param $http_response
     * @return mixed
     */
function specified_grade_parser(string $http_response){

    $array = extract_grade_table_parser($http_response);
    //去前
    $array = array_slice($array, 1);
    //排后
//    $array = array_slice($array, 0, count($array) - 0);

    $result = [];
    foreach ($array as $i){
        $r = array_course_from_specific_factory($i);
        array_push($result, $r);
    }

    return $result;

    /*
     * 解析后数组格式大致如下：
     * 下面这个已
     *
 array (size=22)
  0 =>
    array (size=15)
      0 => string '学年' (length=6)
      1 => string '学期' (length=6)
      2 => string '课程代码' (length=12)
      3 => string '课程名称' (length=12)
      4 => string '课程性质' (length=12)
      5 => string '课程归属' (length=12)
      6 => string '学分' (length=6)
      7 => string '绩点' (length=6)
      8 => string '成绩' (length=6)
      9 => string '辅修标记' (length=12)
      10 => string '补考成绩' (length=12)
      11 => string '重修成绩' (length=12)
      12 => string '开课学院' (length=12)
      13 => string '备注' (length=6)
      14 => string '重修标记' (length=12)
    【这里多了一个课程英文名称】
  5 =>
    array (size=15)
      0 => string '2017-2018' (length=9)
      1 => string '2' (length=1)
      2 => string '0002549' (length=7)
      3 => string '数据库原理Ⅰ' (length=18)
      4 => string '学科基础必修课' (length=21)
      5 => string '' (length=0)
      6 => string '3.0' (length=3)
      7 => string '4.00' (length=4)
      8 => string '87' (length=2)
      9 => string '0' (length=1)
      10 => string '' (length=0)
      11 => string '' (length=0)
      12 => string '信息学部' (length=12)
      13 => string '' (length=0)
      14 => string '' (length=0)
  6 =>
    array (size=15)
      0 => string '2017-2018' (length=9)
      1 => string '2' (length=1)
      2 => string '0003338' (length=7)
      3 => string 'JAVA程序设计' (length=16)
      4 => string '学科基础选修课' (length=21)
      5 => string '' (length=0)
      6 => string '2.0' (length=3)
      7 => string '3.00' (length=4)
      8 => string '83' (length=2)
      9 => string '0' (length=1)
      10 => string '' (length=0)
      11 => string '' (length=0)
      12 => string '信息学部' (length=12)
      13 => string '' (length=0)
      14 => string '' (length=0)

    …………………………

   18 =>
    array (size=15)
      0 => string '2017-2018' (length=9)
      1 => string '2' (length=1)
      2 => string 'ty01403' (length=7)
      3 => string '篮球' (length=6)
      4 => string '公共基础必修课' (length=21)
      5 => string '' (length=0)
      6 => string '1.0' (length=3)
      7 => string '4.00' (length=4)
      8 => string '86' (length=2)
      9 => string '0' (length=1)
      10 => string '' (length=0)
      11 => string '' (length=0)
      12 => string '' (length=0)
      13 => string '' (length=0)
      14 => string '' (length=0)
  19 =>
    array (size=1)
      0 => string '' (length=0)
  20 =>
    array (size=4)
      0 => string '' (length=0)
      1 => string '' (length=0)
      2 => string '' (length=0)
      3 => string '' (length=0)
  21 =>
    array (size=2)
      0 => string '成绩及绩点仅供参考，以教务管理端为准。' (length=57)
      1 => string '' (length=0)

     */

}


/**
 * 解析某一学期成绩
 * @param $http_response
 * @return mixed
 */
function specified_schedule_parser(string $http_response){

    $courses_content = get_content_by_tag_and_id($http_response, "table", "Table1");
//    $courses = explode("<br><br>");

    $array = extract_schedule_table_parser($courses_content);

    $result = [];

    foreach ($array as $a){
        foreach ($a as $i){
            if(!$i or !preg_match("/<br>/", $i)){
                continue;
            }
            $cs = explode("<br><br>", $i);
            foreach ($cs as $ca){
                if(!$ca){
                    continue;
                }
                $a = explode("<br>", $ca);
                $c = array_course_from_schedule_factory($a);
                array_push($result, $c);
            }
        }
    }

    return $result;
}

