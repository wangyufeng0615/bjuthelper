<?php
/**
 * Created by PhpStorm.
 * User: remini
 * Date: 2018/10/11
 * Time: 8:38 AM
 */

include_once ("core/BJUTHelper.php");
include_once("core/model/APIResult.php");

header('Content-type: application/json; charset=utf-8');

$action=null;
if(isset($_POST["action"])){
    $action = $_POST["action"];
}
if(isset($_GET["action"])){
    $action = $_GET["action"];
}
if($action){
    switch ($action){
        case "get_year";
            $response = new APIResult();
            $response->result = [
                "2013-2014",
                "2014-2015",
                "2015-2016",
                "2016-2017",
                "2017-2018",
                ];
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit();
            break;
        case "get_term";
            $response = new APIResult();
            $response->result = [
                "1",
                "2",
                "3",
            ];
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit();
            break;

    }
}

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

$xh=$p_account;
$pw=$p_password;
$current_year=$p_current_year;
$current_term=$p_current_term;

$student = new BJUTHelper($xh, $pw);

$login_success = $student->login();

//若登陆信息输入有误
if(!$login_success){
    $response = new APIResult();
    $response->err = 403;
    $response->err_msg = "您的账号 or 密码输入错误，或者是选择了无效的学年/学期";
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}

$result = $student->get_final_result($current_year, $current_term);

$response = new APIResult();
$response->result = $result;

//var_dump($result);

echo json_encode($response, JSON_UNESCAPED_UNICODE);
