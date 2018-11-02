<?php
include_once ("model/Course.php");
include_once ("model/CourseDetailed.php");

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
 * @param $content
 * @return array
 */
function table_to_array(string $content) {

    $content = preg_replace("'<table[^>]*?>'si","",$content);
    $content = preg_replace("'<tr[^>]*?>'si","",$content);
    $content = preg_replace("'<td[^>]*?>'si","",$content);
    $content = str_replace("</tr>","{tr}",$content);
    $content = str_replace("</td>","{td}",$content);
    //去掉 HTML 标记
    $content = preg_replace("'<[/!]*?[^<>]*?>'si","",$content);
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

    //统一转码至utf-8
    $content = iconv("gb2312","utf-8//IGNORE", $content);

    //去除多余空行
    $content = str_replace("\t", "", $content);
    $content = str_replace("\n", "", $content);
    $content = str_replace("\r", "", $content);

    $array = table_to_array($content);

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
    $result->id = $course_array[0];
    $result->name = $course_array[1];
    $result->type = $course_array[2];
    $result->credit = $course_array[3];
    $result->score = $course_array[4];
    $result->belong = $course_array[5];
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
 * 解析获取成绩为数组
 * 配合send_view_state_requests使用
 * @param $http_response
 * @return mixed
 */
function all_grade_parser(string $http_response){

    $array = extract_grade_table_parser($http_response);
    //去前
    $array = array_slice($array, 5);
    //排后
    $array = array_slice($array, 0, count($array) - 3);

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
  0 =>
    array (size=1)
      0 => string 'HTTP/1.1100ContinueHTTP/1.1200OKCache-Control:privateContent-Length:39679Content-Type:text/html;chaet=gb2312Server:Microsoft-IIS/7.0X-AspNet-Veion:1.1.4322X-Powered-By:ASP.NETDate:Tue,09Oct201806:49:16GMT现代教学管理信息系统functionshowWXKC(t,nr){Ext.Msg.getDialog().setWidth(500);Ext.MessageBox.alert(t+"还需修课程",nr);}functionwindow.onbeforeprint(){document.all.tabHidden.style.display="none"}functionwindow.onafterprint(){document.all.tabHidden.style.display="block"}functionclick(){if(event.'... (length=2108)
  1 =>
    array (size=3)
      0 => string '学号：1608XXXX' (length=17)
      1 => string '姓名：XXX' (length=18)
      2 => string '学院：信息学部' (length=21)
  2 =>
    array (size=3)
      0 => string '专业：软件工程（实验班）' (length=36)
      1 => string '专业方向:' (length=13)
      2 => string '行政班：1608XX' (length=18)
  3 =>
    array (size=1)
      0 => string '' (length=0)
  4 =>
    array (size=6)
      0 => string '已修课程最高成绩：课程代码' (length=39)
      1 => string '课程名称' (length=12)
      2 => string '课程性质' (length=12)
      3 => string '学分' (length=6)
      4 => string '最高成绩值' (length=15)
      5 => string '课程归属' (length=12)
  5 =>
    array (size=6)
      0 => string '0007069' (length=7)
      1 => string '“中国特色社会主义建设”实践' (length=42)
      2 => string '实践环节必修课' (length=21)
      3 => string '2.0' (length=3)
      4 => string '76' (length=2)
      5 => string '' (length=0)

  ……………………………………………………………………

  52 =>
    array (size=6)
      0 => string '0004312' (length=7)
      1 => string '中国近现代史纲要' (length=24)
      2 => string '公共基础必修课' (length=21)
      3 => string '2.0' (length=3)
      4 => string '83' (length=2)
      5 => string '' (length=0)
  53 =>
    array (size=1)
      0 => string '' (length=0)
  54 =>
    array (size=4)
      0 => string '' (length=0)
      1 => string '' (length=0)
      2 => string '' (length=0)
      3 => string '' (length=0)
  55 =>
    array (size=2)
      0 => string '成绩及绩点仅供参考，以教务管理端为准。' (length=57)
      1 => string '' (length=0)
     */
    }

/**
 * 从页面解析基本信息
 * 配合send_view_state_requests使用
 * @param $http_response
 * @return mixed
 */
function personal_info_parser(string $http_response){

    $array = extract_grade_table_parser($http_response);

    $info = [];

    foreach (array_merge($array[1], $array[2]) as $index => $item) {
        $item = str_replace(array("："),':',$item);
        $result = explode(":", $item);
        $info[trim($result[0])] = $result[1];
    }

    $result = array(
        "sid"=> $info["学号"],
        "name"=> $info["姓名"],
        "institute"=> $info["学院"],
        "major"=> $info["专业"],
        "direction"=> $info["专业方向"],
        "class"=> $info["行政班"],
    );

    return $result;

    /*
     * 解析后数组格式大致如下：
     * 特此记录供以后参考。
     *
     * 理论上传什么进来都一样
     *
array (size=56)
  0 =>
    array (size=1)
      0 => string 'HTTP/1.1100ContinueHTTP/1.1200OKCache-Control:privateContent-Length:39679Content-Type:text/html;chaet=gb2312Server:Microsoft-IIS/7.0X-AspNet-Veion:1.1.4322X-Powered-By:ASP.NETDate:Tue,09Oct201806:49:16GMT现代教学管理信息系统functionshowWXKC(t,nr){Ext.Msg.getDialog().setWidth(500);Ext.MessageBox.alert(t+"还需修课程",nr);}functionwindow.onbeforeprint(){document.all.tabHidden.style.display="none"}functionwindow.onafterprint(){document.all.tabHidden.style.display="block"}functionclick(){if(event.'... (length=2108)
  1 =>
    array (size=3)
      0 => string '学号：1608XXXX' (length=17)
      1 => string '姓名：XXX' (length=18)
      2 => string '学院：信息学部' (length=21)
  2 =>
    array (size=3)
      0 => string '专业：软件工程（实验班）' (length=36)
      1 => string '专业方向:' (length=13)
      2 => string '行政班：1608XX' (length=18)
  3 =>
    array (size=1)
      0 => string '' (length=0)
  4 =>
    array (size=6)
      0 => string '已修课程最高成绩：课程代码' (length=39)
      1 => string '课程名称' (length=12)
      2 => string '课程性质' (length=12)
      3 => string '学分' (length=6)
      4 => string '最高成绩值' (length=15)
      5 => string '课程归属' (length=12)
  5 =>
    array (size=6)
      0 => string '0007069' (length=7)
      1 => string '“中国特色社会主义建设”实践' (length=42)
      2 => string '实践环节必修课' (length=21)
      3 => string '2.0' (length=3)
      4 => string '76' (length=2)
      5 => string '' (length=0)

  ……………………………………………………………………

  52 =>
    array (size=6)
      0 => string '0004312' (length=7)
      1 => string '中国近现代史纲要' (length=24)
      2 => string '公共基础必修课' (length=21)
      3 => string '2.0' (length=3)
      4 => string '83' (length=2)
      5 => string '' (length=0)
  53 =>
    array (size=1)
      0 => string '' (length=0)
  54 =>
    array (size=4)
      0 => string '' (length=0)
      1 => string '' (length=0)
      2 => string '' (length=0)
      3 => string '' (length=0)
  55 =>
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
function specified_grade_parser(string $http_response){

    $array = extract_grade_table_parser($http_response);
    //去前
    $array = array_slice($array, 5);
    //排后
    $array = array_slice($array, 0, count($array) - 3);

    $result = [];
    foreach ($array as $i){
        $r = array_course_from_specific_factory($i);
        array_push($result, $r);
    }

    return $result;

    /*
     * 解析后数组格式大致如下：
     * 特此记录供以后参考。
     * 因此，从第五个开始为课程，最后3个也是废弃信息。
     * 序号与课程对应关系见[4]
     *
 array (size=22)
  0 =>
    array (size=1)
      0 => string 'HTTP/1.1100ContinueHTTP/1.1200OKCache-Control:privateContent-Length:59141Content-Type:text/html;chaet=gb2312Server:Microsoft-IIS/7.0X-AspNet-Veion:1.1.4322X-Powered-By:ASP.NETDate:Tue,09Oct201806:44:33GMT现代教学管理信息系统functionshowWXKC(t,nr){Ext.Msg.getDialog().setWidth(500);Ext.MessageBox.alert(t+"还需修课程",nr);}functionwindow.onbeforeprint(){document.all.tabHidden.style.display="none"}functionwindow.onafterprint(){document.all.tabHidden.style.display="block"}functionclick(){if(event.'... (length=2145)
  1 =>
    array (size=3)
      0 => string '学号：1608XXXX' (length=17)
      1 => string '姓名：XXX' (length=18)
      2 => string '学院：信息学部' (length=21)
  2 =>
    array (size=3)
      0 => string '专业：软件工程（实验班）' (length=36)
      1 => string '专业方向:' (length=13)
      2 => string '行政班：1608XX' (length=18)
  3 =>
    array (size=1)
      0 => string '' (length=0)
  4 =>
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

