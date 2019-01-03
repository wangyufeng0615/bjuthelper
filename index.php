<?php
/**
 * Created by PhpStorm.
 * User: ZE3kr
 * Date: 2019-01-03
 * Time: 22:12
 */

/** Absolute path to the directory. */
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

$is_debug = false;

if ( isset($is_debug) && $is_debug ) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    error_reporting(E_ERROR | E_PARSE);
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

if( isset($_POST['account']) && isset($_POST['password']) &&
    isset($_POST['current_year']) && isset($_POST['current_term']) ) {
    $p_account = $_POST['account'];
    $p_password = $_POST['password'];
    $p_current_year = $_POST['current_year'];
    $p_current_term = $_POST['current_term'];
    include_once ABSPATH.'require_grade.php';
} else if( isset($_GET['account']) && isset($_GET['password']) &&
    isset($_GET['current_year']) && isset($_GET['current_term']) ) {
    $p_account = $_GET['account'];
    $p_password = $_GET['password'];
    $p_current_year = $_GET['current_year'];
    $p_current_term = $_GET['current_term'];
    include_once ABSPATH.'require_grade.php';
} else {
    include_once ABSPATH.'login_grade.php';
}
