<?php
/**
 * Created by PhpStorm.
 * User: remini
 * Date: 2018/10/8
 * Time: 8:17 AM
 */
include_once ("http.php");
include_once ("requests.php");
include_once ("parser.php");
class BJUTHelper
{
    //学生信息
    private $stu_id = '';
    private $password = '';
    private $view_state = '';
    private $http;
    private $is_login = false;
    private $info;
    /**
     * BJUThelper constructor.
     * @param $stu_id string 学生用户名
     * @param $password string 学生密码
     */
    function __construct(string $stu_id, string $password)
    {
        $this->stu_id = $stu_id;
        $this->password = $password;
        $this->http = new HttpHolder();
    }
    /**
     * 登录并检查密码。一切操作前都需要执行该方法
     * 成功返回真，失败（账号密码有误等等）返回假
     * @return bool
     */
    function login(){
        $login_context = send_login_request($this->http, $this->stu_id, $this->password);
        if(!check_login_success_parser($login_context)){
            $this->is_login = false;
            return false;
        }
        //获取view_state以供后续查询成绩使用
        $state_context = send_view_state_request($this->http, $this->stu_id);
        $this->view_state = view_state_parser($state_context);
        $this->is_login = true;
        return true;
    }
    /**
     * 查询是否登录
     * @return mixed
     * @throws Exception 账号密码有误
     */
    function has_login(){
        return $this->is_login && $this->view_state;
    }
    /**
     * 获得指定一学期课程数据
     * @param string $current_year
     * @param string $current_term
     * @return array
     */
    function get_specified_course(string $current_year, string $current_term){
        $context = send_specified_grade_request(
            $this->http,
            $this->stu_id,
            $this->view_state,
            $current_year,
            $current_term);
        $courses = specified_grade_parser($context);
        $this->info = personal_info_parser($context);
        return $courses;
    }
    /**
     * 获得总成绩数据
     * 吐槽！为什么需要这两个参数啊。
     * @param string $current_year
     * @param string $current_term
     * @return array
     */
    function get_all_course(string $current_year, string $current_term){
        $context = send_all_grade_request(
            $this->http,
            $this->stu_id,
            $this->view_state,
            $current_year,
            $current_term);
        $courses = all_grade_parser($context);
        $this->info = personal_info_parser($context);
        return $courses;
    }
    /**
     * 返回计算后结果
     * @param string $current_year
     * @param string $current_term
     * @return array
     */
    function get_final_result(string $current_year, string $current_term){
        $grade_total = $this->get_all_course($current_year, $current_term);
        $grade_term = $this->get_specified_course($current_year, $current_term);
        //计算总的加权分数和总的GPA
        $all_score = 0; //总的加权*分数
        $all_score_include_unpassed = 0; // 包含不及格课程的总分数
        $all_score_include_unpassed_passed = 0; // 不及格课程按60计算的分数
        $all_value = 0; //总的学分权值
        $all_value_include_unpassed = 0;
        $all_GPA = 0;   //总的GPA*分数
        $all_GPA_include_unpassed_passed = 0;
        $all_number_of_lesson_passed = 0;  //总的课程数
        $all_number_of_lesson_include_unpassed = 0; //包含未过课程的总数
        //计算总和的东西，学分/GPA
        foreach ($grade_total as $course){
            //不计算第二课堂和新生研讨课以及未通过课程
            if ($course->belong == "第二课堂"
                || $course->name == "新生研讨课"
                || $course->score < 60
                || strpos($course->type, "（辅）")) {
                //通过判断课程类型中的“（辅）”字样来过滤辅修成绩
                if ($course->score < 60 && is_numeric($course->score)){
                    $all_number_of_lesson_include_unpassed++;
                    $all_score_include_unpassed += ($course->credit * $course->score);
                    $all_score_include_unpassed_passed += ($course->credit * 60);
                    $all_value_include_unpassed += $course->credit;
                    $all_GPA_include_unpassed_passed += (2.0 * $course->credit);
                }
            }
            else{
                $all_score += ($course->credit * $course->score);  //  累加总分
                $all_score_include_unpassed += ($course->credit * $course->score);
                $all_score_include_unpassed_passed += ($course->credit * $course->score);
                $all_value += $course->credit;    //  累加学分(权值)
                $all_value_include_unpassed += $course->credit;
                if ($course->score >= 85 && $course->score <= 100){
                    $all_GPA += (4.0 * $course->credit);
                    $all_GPA_include_unpassed_passed += (4.0 * $course->credit);
                }
                else if ($course->score >= 70 && $course->score < 85){
                    $all_GPA += (3.0 * $course->credit);
                    $all_GPA_include_unpassed_passed += (3.0 * $course->credit);
                }
                else if ($course->score >= 60 && $course->score < 70){
                    $all_GPA += (2.0 * $course->credit);
                    $all_GPA_include_unpassed_passed += (2.0 * $course->credit);
                }
                $all_number_of_lesson_passed++;
                $all_number_of_lesson_include_unpassed++;
            }
        }
        $total_lesson_count = count($grade_total);
        //个别学期加权平均分和GPA的计算
        //主修课程
        $total_score = 0;
        $total_value = 0;
        $total_GPA = 0;
        $number_of_lesson = 0;        //主修总课程数
        //二专业和辅修，$course->minor_maker == 2
        $total_score_minor = 0;
        $total_value_minor = 0;
        $total_GPA_minor = 0;
        $number_of_lesson_minor = 0;  //二专业/辅修课程数
        //计算个别学期的信息
        foreach($grade_term as $course){
            if (!($course->belong =="第二课堂"
                || $course->name === "新生研讨课"
                || $course->score < 60)){
                //处理辅修/二专业
                if ($course->minor_maker == 2){
                    $total_score_minor += ($course->score * $course->credit);  //  累加总分
                    $total_value_minor += $course->credit;    //  累加学分(权值)
                    $total_GPA_minor += ($course->gpa * $course->credit); //加权总绩点
                    $number_of_lesson_minor++;
                }
                //辅修
                if ($course->minor_maker == 1){
                    $total_score_minor += ($course->score * $course->credit);  //  累加总分
                    $total_value_minor += $course->credit;    //  累加学分(权值)
                    $total_GPA_minor += ($course->gpa * $course->credit); //加权总绩点
                    $number_of_lesson_minor++;
                }
                //普通课程
                if ($course->minor_maker == 0){
                    $total_score += ($course->score * $course->credit);  //  累加总分
                    $total_value += $course->credit;    //  累加学分(权值)
                    $total_GPA += ($course->gpa * $course->credit); //加权总绩点
                    $number_of_lesson++;
                }
            }
        }
//        $average_score = $total_score / $total_value;
        $term_lesson_count = count($grade_term);
        $average_score_all = $all_value !== 0 ? $all_score / $all_value : 0;
        $average_score_term = $total_value !== 0 ? $total_score / $total_value : 0;
        $average_score_minor = $total_value_minor !== 0 ? $total_score_minor / $total_value_minor : 0;
        $average_score_include_unpassed = $all_value_include_unpassed !== 0 ? $all_score_include_unpassed / $all_value_include_unpassed : 0;
        $average_score_include_unpassed_passed = $all_value_include_unpassed !== 0 ? $all_score_include_unpassed_passed / $all_value_include_unpassed : 0;
        $average_GPA_all = $all_value !== 0 ? $all_GPA / $all_value : 0;
        $average_GPA_term = $total_value !== 0 ? $total_GPA / $total_value : 0;
        $average_GPA_minor = $total_value_minor !== 0 ? $total_GPA_minor / $total_value_minor : 0;
        $average_GPA_include_unpassed = $all_value_include_unpassed !== 0 ? $all_GPA / $all_value_include_unpassed : 0;
        $average_GPA_include_unpassed_passed = $all_value_include_unpassed !== 0 ? $all_GPA_include_unpassed_passed / $all_value_include_unpassed : 0;
        $all_number_of_lesson_unpassed = $all_number_of_lesson_include_unpassed - $all_number_of_lesson_passed;
        $result = array(
            "sid"=> $this->info["sid"],
            "name"=> $this->info["name"],
            "institute"=> $this->info["institute"],
            "major"=> $this->info["major"],
            "direction"=> $this->info["direction"],
            "class"=> $this->info["class"],
            "grade_term" => $grade_term,                                                    //学习成绩数据集
            "grade_total" => $grade_total,                                                  //总成绩数据集
            "all_score" => $all_score,                                                      //总的加权*分数
            "all_value" => $all_value,                                                      //总的学分权值
            "all_GPA" => $all_GPA,                                                          //总的GPA*分数
            "all_number_of_lesson_passed" => $all_number_of_lesson_passed,                              //总的课程数
            "all_number_of_lesson_include_unpassed" => $all_number_of_lesson_include_unpassed,    //包含未过课程的总数
            "all_number_of_lesson_unpassed" => $all_number_of_lesson_unpassed,          //大学总未通过课程数
            "total_score" => $total_score,                                                //学期累加总分
            "total_value" => $total_value,                                                //学期累加学分(权值)
            "total_GPA" => $total_GPA,                                                    //学期GPA*分数
            "number_of_lesson" => $number_of_lesson,                                     //主修总课程数
            "total_score_minor" => $total_score_minor,                                   //二专业和辅修
            "total_value_minor" => $total_value_minor,
            "total_GPA_minor" => $total_GPA_minor,
            "number_of_lesson_minor" => $number_of_lesson_minor,                        //二专业/辅修课程数
            "average_score_all" => $average_score_all,                                  //大学期间总加权平均分
            "average_score_term" => $average_score_term,                                //学期平均分
            "average_score_minor" => $average_score_minor,                              //辅修平均分
            "average_score_include_unpassed" => $average_score_include_unpassed,        //含未通过课程均分（计实际分数）
            "average_score_include_unpassed_passed" => $average_score_include_unpassed_passed,//未通过课程补考后均分（计60分）
            "average_GPA_all" => $average_GPA_all,                                      //大学期间总平均学分绩点（GPA）
            "average_GPA_term" => $average_GPA_term,                                    //学期加权GPA
            "average_GPA_minor" => $average_GPA_minor,                                  //辅修加权GPA
            "average_GPA_include_unpassed" => $average_GPA_include_unpassed,            //含未通过课程绩点（未通过计0绩点）
            "average_GPA_include_unpassed_passed" => $average_GPA_include_unpassed_passed, //未通过课程补考后绩点（计60分2绩点）
            "term_lesson_count" => $term_lesson_count,                                  //本学期已出分课程数
            "total_lesson_count" => $total_lesson_count,                                //大学总已出分课程数
        );
//        var_dump($result);
//        exit();
        return $result;
    }
}
//$test = new BJUTHelper("16080211", "");
//if($test->login()){
//    $r = $test->get_all_course("2017-2018", "2");
//    var_dump($r);
//}