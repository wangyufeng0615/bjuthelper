<?php
/**
 * Created by PhpStorm.
 * User: remini
 * Date: 2018/10/11
 * Time: 8:38 AM
 */
if(file_exists('config.php')){
	include 'config.php';
}

include_once("core/BJUTHelper.php");
include_once("core/model/APIResult.php");
include_once("core/utils.php");

header('Content-type: application/json; charset=utf-8');

include_once ("core/const.php");

$action=get_argument("action");

if($action){
    switch ($action){
        case "get_year";
            $response = new APIResult();
            $response->result = $years;
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit();
            break;
        case "get_term";
            $response = new APIResult();
            $response->result = $terms;
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit();
            break;
    }
}

$xh = get_argument('account');
$pw = get_argument('password');
$current_year = get_argument('current_year', "");
$current_term = get_argument('current_term', "");

$student = new BJUTHelper($xh, $pw);

if(isset($proxyUserName)){
	if(!$student->login_vpn()){
		$response = new APIResult();
		$response->err = 403;
		$response->err_msg = "VPN网关账户信息错误";
		echo json_encode($response, JSON_UNESCAPED_UNICODE);
		exit();
	}
}
$login_success = $student->login();

//若登陆信息输入有误
if(!$login_success){
    $response = new APIResult();
    $response->err = 403;
    $response->err_msg = "您的账号 or 密码输入错误，或者是选择了无效的学年/学期";
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}

if($action == "schedule"){
    $result = $student->get_specified_schedule($current_year, $current_term);
}
else{
    $result = $student->get_final_result($current_year, $current_term);
}

$response = new APIResult();
$response->result = $result;

//var_dump($result);

echo json_encode($response, JSON_UNESCAPED_UNICODE);
