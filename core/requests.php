<?php

/**
 * 该文件内记录网络层请求
 * 该文件中函数返回值均为请求发出后获得的带有header的全文本内容
 * Created by PhpStorm.
 * User: remini
 * Date: 2018/10/8
 * Time: 8:53 AM
 */

include_once('parser.php');
include_once('http.php');

/**
 * 登录，传进来的$http_holder的内置cookie会自动更换
 * @param HttpHolder $http_holder 需要自动更换cookie的http_holder
 * @param $stu_id
 * @param $pwd
 * @return mixed 返回登录后的页面html
 */
function send_login_request(HttpHolder $http_holder, string $stu_id, string $pwd){

    //$code= $_POST['verify_code'];
    //$cookie = dirname(__FILE__) . '/cookie/'.$_SESSION['id'].'.txt';
    $url="http://gdjwgl.bjut.edu.cn/default_vsso.aspx";  //教务地址
    //$con1=login_post($url,$cookie,'');               //登陆
    //preg_match_all('/<input type="hidden" name="__VIEWSTATE" value="([^<>]+)" \/>/', $con1, $view); //获取__VIEWSTATE字段并存到$view数组中
    //为登陆准备的POST数据

    $post=array(
        //'__VIEWSTATE'=>$view[1][0],
        'TextBox1'=>$stu_id,
        'TextBox2'=>$pwd,
        //'txtSecretCode'=>$code,
        'RadioButtonList1_2'=>'%D1%A7%C9%FA',  //“学生”的gbk编码
        'Button1'=>'',
        //'lbLanguage'=>'',
        //'hidPdrs'=>'',
        //'hidsc'=>''
    );

    return $http_holder->post($url,http_build_query($post)); //将数组连接成字符串, 登陆教务系统

}

/**
 * 用于生成查询成绩和获取view_state的链接
 * @param string $stu_id 学号
 * @return string
 */
function generate_grade_url(string $stu_id){
    return "http://gdjwgl.bjut.edu.cn/xscj_gc2.aspx?xh=".$stu_id;
}

/**
 * 用于生成查询课表的链接
 * @param string $stu_id 学号
 * @return string
 */
function generate_course_url(string $stu_id){
    return "http://gdjwgl.bjut.edu.cn/xscj_gc2.aspx?xh=".$stu_id;
}


/**
 * 可配合view_state_parser获取页面的 view state
 * 用于查询成绩的请求
 * @param HttpHolder $http_holder 传入已经登录的HttpHolder
 * @param string $stu_id 学号
 * @return mixed
 */
function send_view_state_request(HttpHolder $http_holder, string $stu_id){

    // 不知道为什么，不提交姓名信息也能查询
    // preg_match_all('/<span id="xhxm">([^<>]+)/', $con2, $xm);   //正则出的数据存到$xm数组中
    // print_r($xm);
    // $xm[1][0]=substr($xm[1][0],0,-4);  //字符串截取，获得姓名

    $url=generate_grade_url($stu_id);

    $http_content = $http_holder->post($url);

    return $http_content;

}

/**
 * 获取指定学期的成绩
 * 可配合specified_grade_parser获取页面的成绩
 * @param HttpHolder $http_holder 传入已经登录的HttpHolder
 * @param string $stu_id 学号
 * @param string $view_state
 * @param string $current_year
 * @param string $current_term
 * @return mixed
 */
function send_specified_grade_request(HttpHolder $http_holder,
                                      string $stu_id,
                                      string $view_state,
                                      string $current_year,
                                      string $current_term){

    $url = generate_grade_url($stu_id);

    //查询某一学期的成绩
    $post=array(
        '__VIEWSTATE'=>$view_state,
        'ddlXN'=>$current_year,  //当前学年
        'ddlXQ'=>$current_term,  //当前学期
        'Button1'=>'%B0%B4%D1%A7%C6%DA%B2%E9%D1%AF',  //别问我是啥 我也不知道
    );

    $content=$http_holder->post($url,http_build_query($post)); //获取原始数据

    return $content;
}

/**
 * 获取所有学期的成绩
 * 可配合all_grade_parser获取页面的成绩
 * @param HttpHolder $http_holder 传入已经登录的HttpHolder
 * @param string $stu_id 学号
 * @param string $view_state
 * @param string $current_year
 * @param string $current_term
 * @return mixed
 */
function send_all_grade_request(HttpHolder $http_holder,
                                      string $stu_id,
                                      string $view_state){

    $url = generate_grade_url($stu_id);

    //查询总成绩
    $post = array(
        '__VIEWSTATE'=>$view_state,
//        'ddlXN'=>$current_year,  //当前学年
//        'ddlXQ'=>$current_term,  //当前学期
        'Button6'=>'%B2%E9%D1%AF%D2%D1%D0%DE%BF%CE%B3%CC%D7%EE%B8%DF%B3%C9%BC%A8', //蜜汁
    );

    $content=$http_holder->post($url,http_build_query($post)); //获取原始数据

    return $content;
}