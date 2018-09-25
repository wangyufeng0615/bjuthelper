<?php
/**
 * Created by PhpStorm.
 * User: mwr
 * Date: 2017/1/17
 * Time: 16:03
 */
include("core/BJUThelper.php");

$account = $_GET['account'];   //用户名
$pwd = $_GET['password'];      //密码
$year = $_GET['year'];          //学年，如：2016-2017
$term = $_GET['term'];          //学期，如：1 2 3

try {
    $b = new BJUThelper($account, $pwd);
} catch (exception $e) {
    echo 'Error';
    exit();
}

echo BJUThelper::to_json($b->compute_data($year, $term));