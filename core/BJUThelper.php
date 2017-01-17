<?php

/**
 * Created by PhpStorm.
 * User: mwr
 * Date: 2017/1/17
 * Time: 4:30
 */
class BJUThelper
{
    //教务处域名
    const web_url = 'http://gdjwgl.bjut.edu.cn/';

    //查询地址
    private $login_url = '';
    private $score_url = '';

    //中间信息
    private $cookie = '';
    private $viewstate = '';

    //学生信息
    private $account = '';
    private $password = '';


    /**
     * BJUThelper constructor.
     * @param $account string 学生用户名
     * @param $password string 学生密码
     * @throws Exception 账号密码有误
     */
    function __construct($account, $password)
    {
        $this->account = $account;
        $this->password = $password;

        $this->login_url = self::web_url . 'default_vsso.aspx';  //教务地址
        $this->score_url = self::web_url . "xscjcx.aspx?xh=" . $this->account;

        //如果登陆失败，抛出异常
        if (!$this->login()) throw new Exception("您的账号 or 密码输入错误");

        $this->viewstate = $this->get_viewstate();

    }


    /**向url发送post数据，并且自动更新cookie
     * @param $url string post的网址
     * @param $post string post的内容
     * @return mixed 成功时会返回curl的执行的结果，失败时返回 FALSE。
     */
    private function connect($url, $post)
    {
        $cookie = $this->cookie;
        $ch = curl_init($url);
        //curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   //不自动输出数据，要echo才行
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);  //重要，抓取跳转后数据
        if (strlen($cookie)) curl_setopt($ch, CURLOPT_COOKIE, $cookie); //if have cookie, set it
        //curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
        //curl_setopt($ch, CURLOPT_REFERER, self::web_url.'default2.aspx');  //重要，302跳转需要referer，可以在Request Headers找到
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);   //post提交数据
        $result = curl_exec($ch);
        if (preg_match('/Set-Cookie: (.+?);/', $result, $str)) {
            $cookie = $str[1];
        } //match the cookie
        curl_close($ch);
        $this->cookie = $cookie;
        //echo 'myCookie = ' . $cookie;
        return $result;
    }

    /**登陆获取Cookie
     * @return bool 返回登陆是否成功
     */
    private function login()
    {
        $account = $this->account;
        $password = $this->password;

        $url = $this->login_url;  //教务地址

        //为登陆准备POST数据
        $post = array(
            //'__VIEWSTATE' =>$ view[1][0],
            'TextBox1' => $account,
            'TextBox2' => $password,
            //'txtSecretCode'=>$code,
            'RadioButtonList1_2' => '%D1%A7%C9%FA',  //“学生”的gbk编码
            'Button1' => '',
            //'lbLanguage'=>'',
            //'hidPdrs'=>'',
            //'hidsc'=>''
        );
        $con2 = $this->connect($url, http_build_query($post)); //将数组连接成字符串, 登陆教务系统

        //若登陆信息输入有误
        if (!preg_match("/xs_main/", $con2)) {
            return false;
        }
//        echo $con2;

        return true;
    }

    /**获取viewstate
     * @return mixed 成功返回string，失败返回false
     */
    private function get_viewstate()
    {
        // 不知道为什么，不提交姓名信息也能查询
        // preg_match_all('/<span id="xhxm">([^<>]+)/', $con2, $xm);   //正则出的数据存到$xm数组中
        // print_r($xm);
        // $xm[1][0]=substr($xm[1][0],0,-4);  //字符串截取，获得姓名
        $url = $this->score_url;
        $viewstate = $this->connect($url, '');
//        echo '$viewstate = ' . $viewstate;
        preg_match_all('/<input type="hidden" name="__VIEWSTATE" value="([^<>]+)" \/>/', $viewstate, $vs);
//        echo '$vs = ' . json_encode($vs);
//        echo '$vs[1][0] = ' . $vs[1][0];
        return $vs[1][0];  //返回__VIEWSTATE
    }


    /**查询某一学期的成绩
     * @param $current_year string 第几学年
     * @param $current_term string 第几学期
     * @return array|mixed 返回数组
     */
    public function fetch($current_year, $current_term)
    {
        //查询某一学期的成绩
        $url = $this->score_url;
        $post = array(
            '__EVENTTARGET' => '',
            '__EVENTARGUMENT' => '',
            '__VIEWSTATE' => $this->viewstate,
            'hidLanguage' => '',
            'ddlXN' => $current_year,  //当前学年
            'ddlXQ' => $current_term,  //当前学期
            'ddl_kcxz' => '',
            'btn_xq' => '%D1%A7%C6%DA%B3%C9%BC%A8'  //“学期成绩”的gbk编码，视情况而定
        );
        $content = $this->connect($url, http_build_query($post)); //获取原始数据
//        echo '$content=' . $content;
        $content = $this->get_td_array($content);    //table转array
//        echo '$array=' . json_encode($content);
        return $content;
    }

    /**查询总成绩
     * @return array|mixed 返回数组
     */
    public function fetch_total()
    {
        //查询总成绩
        $url = $this->score_url;
        $post_allgrade = array(
            '__EVENTTARGET' => '',
            '__EVENTARGUMENT' => '',
            '__VIEWSTATE' => $this->viewstate,
            'hidLanguage' => '',
            'ddlXN' => '1',  //原是当前学年，这里改为1
            'ddlXQ' => '1',  //原是当前学期，这里改为1
            'ddl_kcxz' => '',
            'btn_zg' => '%BF%CE%B3%CC%D7%EE%B8%DF%B3%C9%BC%A8'  //课程最高成绩-gbk
        );
        $content_allgrade = $this->connect($url, http_build_query($post_allgrade)); //获取原始数据
        $content_allgrade = $this->get_td_array($content_allgrade);    //table转array
        return $content_allgrade;
    }

    /**计算并返回学业数据
     * @param $current_year string 第几学年
     * @param $current_term string 第几学期
     * @return array 数据集数组
     */
    public function compute_data($current_year, $current_term)
    {
        $grade_term = $this->fetch($current_year, $current_term);
        $grade_total = $this->fetch_total();

        //计算总的加权分数和总的GPA
        $i = 5;         //从array[5]开始是有效信息
        $all_score = 0; //总的加权*分数
        $all_value = 0; //总的学分权值
        $all_GPA = 0;   //总的GPA*分数
        $all_number_of_lesson = 0;  //总的课程数
        $all_number_of_lesson_with_nopass = 0; //包含未过课程的总数
        //计算总和的东西，学分/GPA
        while (isset($grade_total[$i][4])) {
            //不计算第二课堂和新生研讨课以及未通过课程
            if ($grade_total[$i][5] == iconv("utf-8", "gb2312//IGNORE", "第二课堂") || $grade_total[$i][1] == iconv("utf-8", "gb2312//IGNORE", "新生研讨课") || $grade_total[$i][4] < 60) {
                if ($grade_total[$i][4] < 60 && is_numeric($grade_total[$i][4])) $all_number_of_lesson_with_nopass++;
                $i++;
            } else {
                $all_score += ($grade_total[$i][3] * $grade_total[$i][4]);  //  累加总分
                $all_value += $grade_total[$i][3];    //  累加学分(权值)

                if ($grade_total[$i][4] >= 85 && $grade_total[$i][4] <= 100) {
                    $all_GPA += (4.0 * $grade_total[$i][3]);
                } else if ($grade_total[$i][4] >= 70 && $grade_total[$i][4] < 85) {
                    $all_GPA += (3.0 * $grade_total[$i][3]);
                } else if ($grade_total[$i][4] >= 60 && $grade_total[$i][4] < 70) {
                    $all_GPA += (2.0 * $grade_total[$i][3]);
                }
                $i++;
                $all_number_of_lesson++;
                $all_number_of_lesson_with_nopass++;
            }
        }
        $total_lesson_count = $i - 5;
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
        while (isset($grade_term[$i][8])) {
            if ($grade_term[$i][5] == iconv("utf-8", "gb2312//IGNORE", "第二课堂") || $grade_term[$i][3] === iconv("utf-8", "gb2312//IGNORE", "新生研讨课") || $grade_term[$i][8] < 60) {
                $i++;
            } else {
                //处理辅修/二专业
                if ($grade_term[$i][9] == 2) {
                    $total_score_fuxiu += ($grade_term[$i][8] * $grade_term[$i][6]);  //  累加总分
                    $total_value_fuxiu += $grade_term[$i][6];    //  累加学分(权值)
                    $total_GPA_fuxiu += ($grade_term[$i][7] * $grade_term[$i][6]); //加权总绩点
                    $i++;
                    $number_of_lesson_fuxiu++;
                }
                //普通课程
                if ($grade_term[$i][9] == 0) {
                    $total_score += ($grade_term[$i][8] * $grade_term[$i][6]);  //  累加总分
                    $total_value += $grade_term[$i][6];    //  累加学分(权值)
                    $total_GPA += ($grade_term[$i][7] * $grade_term[$i][6]); //加权总绩点
                    $i++;
                    $number_of_lesson++;
                }
            }
        }

        $average_score_all = $all_value !== 0 ? $all_score / $all_value : 0;
        $average_score_term = $total_value !== 0 ? $total_score / $total_value : 0;
        $average_score_fuxiu = $total_value_fuxiu !== 0 ? $total_score_fuxiu / $total_value_fuxiu : 0;
        $term_lesson_count = $i - 5;


        $result = array(
            "grade_term" => $grade_term,                                                    //学习成绩数据集
            "grade_total" => $grade_total,                                                  //总成绩数据集

            "all_score" => $all_score,                                                      //总的加权*分数
            "all_value" => $all_value,                                                      //总的学分权值
            "all_GPA" => $all_GPA,                                                          //总的GPA*分数
            "all_number_of_lesson" => $all_number_of_lesson,                              //总的课程数
            "all_number_of_lesson_with_nopass" => $all_number_of_lesson_with_nopass,    //包含未过课程的总数
            "total_lesson_count" => $total_lesson_count,

            "total_score" => $total_score,                                                //学期累加总分
            "total_value" => $total_value,                                                //学期累加学分(权值)
            "total_GPA" => $total_GPA,                                                    //学期GPA*分数
            "number_of_lesson" => $number_of_lesson,                                     //主修总课程数
            "total_score_fuxiu" => $total_score_fuxiu,                                   //二专业和辅修
            "total_value_fuxiu" => $total_value_fuxiu,
            "total_GPA_fuxiu" => $total_GPA_fuxiu,
            "number_of_lesson_fuxiu" => $number_of_lesson_fuxiu,                        //二专业/辅修课程数

            "average_score_all" => $average_score_all,                                //总平均分
            "average_score_term" => $average_score_term,                                //学期平均分
            "average_score_fuxiu" => $average_score_fuxiu,                              //辅修平均分
            "term_lesson_count" => $term_lesson_count,                                  //学期课程总数
        );

        return $result;
    }

    /**将表格形式的string转化为二维array
     * @param $table string
     * @return array
     */
    private static function get_td_array($table)
    {
        $table = preg_replace("'<table[^>]*?>'si", "", $table);
        $table = preg_replace("'<tr[^>]*?>'si", "", $table);
        $table = preg_replace("'<td[^>]*?>'si", "", $table);
        $table = str_replace("</tr>", "{tr}", $table);
        $table = str_replace("</td>", "{td}", $table);
        //去掉 HTML 标记
        $table = preg_replace("'<[/!]*?[^<>]*?>'si", "", $table);
        //去掉空白字符
        $table = preg_replace("'([rn])[s]+'", "", $table);
        $table = preg_replace('/&nbsp;/', "", $table);
        $table = str_replace(" ", "", $table);
        $table = str_replace(" ", "", $table);
        $table = explode('{tr}', $table);
        array_pop($table);
        $td_array = array();
        foreach ($table as $key => $tr) {
            $td = explode('{td}', $tr);
            array_pop($td);
            array_push($td_array, $td);
        }
        return $td_array;
    }


    /**将gbk数组转换为utf-8 Json
     * @param $arr array
     * @return string
     */
    public static function to_json($arr)
    {
        $ret = eval('return ' . iconv("gbk", "utf-8", var_export($arr, true) . ';'));
        return json_encode($ret);
    }

}