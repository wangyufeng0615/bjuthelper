<?php
/**
 * 该文件内为网络请求行为本身
 * 主要有一个用于自动更新cookie的类
 * Created by PhpStorm.
 * User: remini
 * Date: 2018/10/8
 * Time: 11:28 AM
 */

include_once("parser.php");


/**
 * Class HttpHolder 会自动更换cookie的post请求
 */
class HttpHolder{

    private $cookie = "";

    /**构造post数据
     * ！ 请注意，为了保证函数的功能单一性，现该函数不再承担提取cookie的功能，需要用extract_login_cookie
     * 【这个函数是静态方法，更推荐实例化http_holder后使用其post函数】
     * @param $url
     * @param $post
     * @param string $cookie 使用的cookie，可选。
     * @return mixed
     */
    static function login_post_fp($url, $post="", $cookie="", $follow=1){
//    global $cookie;
        $ch = curl_init($url);
        //curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   //不自动输出数据，要echo才行
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $follow);  //重要，抓取跳转后数据
        if (strlen($cookie)) curl_setopt($ch, CURLOPT_COOKIE, $cookie); //if have cookie, set it
        //curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
        //curl_setopt($ch, CURLOPT_REFERER, 'http://gdjwgl.bjut.edu.cn/default2.aspx');  //重要，302跳转需要referer，可以在Request Headers找到
        curl_setopt($ch, CURLOPT_POSTFIELDS,$post);   //post提交数据
        $result=curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    function set_cookie($cookie){
        $this->cookie = $cookie;
    }

	function get_cookie(){
		return $this->cookie;
	}

    /**
     * 自动修改所属对象cookie的post函数
     * @param $url
     * @param string $post
     * @return mixed
     */
    function post($url, $post="", $set_cookie=false, $follow=1){
        $result = self::login_post_fp($url, $post, $this->cookie, $follow);
        $cookie = login_cookie_parser($result);
        if($set_cookie && $cookie){
            $this->cookie .= $cookie . ";";
        }
        //统一转码至utf-8
        $result = iconv("gb2312","utf-8//IGNORE", $result);
        return $result;
    }
}
