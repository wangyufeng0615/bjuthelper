<?php 
    session_start();
    $id=session_id();
    $_SESSION['id']=$id;
    require_verify_code();  //获取验证码
    $rand_id = rand(100000, 999999);    //for verifycode
    function require_verify_code(){
        $cookie = dirname(__FILE__).'/cookie/'.$_SESSION['id'].'.txt';    //cookie路径  
        $verify_code_url = "http://gdjwgl.bjut.edu.cn/CheckCode.aspx";      //验证码地址
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $verify_code_url);
        curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie);                     //保存cookie
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $img = curl_exec($curl);                                            //执行curl
        curl_close($curl);
        echo $rand_id;
        $path_of_verifyCode =dirname(__FILE__).'/verifyCodes/verifyCode_'.$GLOBALS['rand_id'].'.jpg';
        echo $path_of_verifyCode;
        $fp = fopen($path_of_verifyCode,"w");                                  //文件名
        fwrite($fp,$img);                                                   //写入文件 
        fclose($fp);
    }
?>

<!DOCTYPE html>
<html lang="zh_cn">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=0">
	<title>野生工大助手</title>
	<link rel="stylesheet" href="//cdn.bootcss.com/weui/0.4.0/style/weui.min.css"/>
    <script src="//cdn.bootcss.com/jquery/3.0.0/jquery.min.js"></script>
	<script src="/js/login_score.js"></script>  
</head>
<body>
    <!-- 使用的是WeUI -->
	<form action="/require_grade.php" method="post">
		<div class="weui_cells_title">登录信息</div>
		<div class="weui_cells weui_cells_form">
			<div class="weui_cell">
				<div class="weui_cell_hd">
					<label class="weui_label">学号</label>
				</div>
				<div class="weui_cell_bd weui_cell_primary">
					<input class="weui_input" name="account" type="text" placeholder="请输入学号">
				</div>
			</div>

			<div class="weui_cell">
				<div class="weui_cell_hd">
					<label class="weui_label">密码</label>
				</div>
				<div class="weui_cell_bd weui_cell_primary">
					<input class="weui_input" name="password" type="password" placeholder="请输入教务密码(gdjwgl.bjut.edu.cn)">
				</div>
			</div>

            <div class="weui_cell weui_cell_select weui_select_after">
                <div class="weui_cell_hd">
                    学年
                </div>
                <div class="weui_cell_bd weui_cell_primary">
                    <select class="weui_select" name="current_year">
                        <option value="2016-2017">2016-2017</option>
                        <option value="2015-2016">2015-2016</option>
                        <option value="2014-2015">2014-2015</option>
                        <option value="2013-2014">2013-2014</option>
                    </select>
                </div>
            </div>

            <div class="weui_cell weui_cell_select weui_select_after">
                <div class="weui_cell_hd">
                    学期
                </div>
                <div class="weui_cell_bd weui_cell_primary">
                    <select class="weui_select" name="current_term">
                        <option selected="" value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                    </select>
                </div>
            </div>

			<div class="weui_cell weui_vcode">
				<div class="weui_cell_hd"><label class="weui_label">验证码</label></div>
				<div class="weui_cell_bd weui_cell_primary">
					<input class="weui_input" name="verify_code" type="text" placeholder="请输入验证码"/>
				</div>
				<div class="weui_cell_ft">
                <img id="verify_code" src="/verifyCodes/verifyCode_<?php print $rand_id ?>.jpg" onclick="update_verify_code()" />
				</div>
			</div>

		</div>

        <!-- loading toast -->
            <div id="loadingToast" class="weui_loading_toast" style="display:none;">
                <div class="weui_mask_transparent"></div>
                <div class="weui_toast">
                    <div class="weui_loading">
                        <div class="weui_loading_leaf weui_loading_leaf_0"></div>
                        <div class="weui_loading_leaf weui_loading_leaf_1"></div>
                        <div class="weui_loading_leaf weui_loading_leaf_2"></div>
                        <div class="weui_loading_leaf weui_loading_leaf_3"></div>
                        <div class="weui_loading_leaf weui_loading_leaf_4"></div>
                        <div class="weui_loading_leaf weui_loading_leaf_5"></div>
                        <div class="weui_loading_leaf weui_loading_leaf_6"></div>
                        <div class="weui_loading_leaf weui_loading_leaf_7"></div>
                        <div class="weui_loading_leaf weui_loading_leaf_8"></div>
                        <div class="weui_loading_leaf weui_loading_leaf_9"></div>
                        <div class="weui_loading_leaf weui_loading_leaf_10"></div>
                        <div class="weui_loading_leaf weui_loading_leaf_11"></div>
                    </div>
                    <p class="weui_toast_content">数据加载中</p>
                </div>
            </div>

        <script>
            //Loading旋转菊花
            $(function() {
                $('#showLoadingToast').click(function() {
                    $('#loadingToast').fadeIn().delay(5000).fadeOut();
                });
            })
        </script>

		<input class="weui_btn weui_btn_primary" type="submit" value="查询" id="showLoadingToast"/>
	</form>		

	<article class="weui_article">
		&nbsp;<h1><i class="weui_icon_success_circle"></i>&nbsp;账号和密码不会被保留，请放心使用。<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;总加权平均分和总平均GPA的数据只对没报二专业/辅修的同学有效<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;数据仅供参考，请以教务系统为准。<br/><br/>
		<section>
如数据有问题请联系王雨峰同学<br />
Contact: wyf0615@emails.bjut.edu.cn<br />
感谢陈仕玺同学对本站稳定运营的大力支持!<br />
        </section>
    </article>

    <div style="display:none"><script type="text/javascript">var cnzz_protocol = (("https:" == document.location.protocol) ? " https://" : " http://");document.write(unescape("%3Cspan id='cnzz_stat_icon_1259582707'%3E%3C/span%3E%3Cscript src='" + cnzz_protocol + "s11.cnzz.com/stat.php%3Fid%3D1259582707' type='text/javascript'%3E%3C/script%3E"));</script></div>
    </body>
</html>
