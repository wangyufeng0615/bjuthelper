<?php 
    session_start();
    $id=session_id();
    $_SESSION['id']=$id;
    $rand_id = rand(100000, 999999);    //for verifycode
    require_verify_code();  //获取验证码
    function require_verify_code(){
        $cookie = dirname(__FILE__).'/cookie/'.$_SESSION['id'].'.txt';    //cookie路径  
        $verify_code_url = "http://gdjwgl.bjut.edu.cn/CheckCode.aspx";      //验证码地址
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $verify_code_url);
        curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie);                     //保存cookie
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //$img = curl_exec($curl);                                            //执行curl
        //curl_close($curl);
        //global $rand_id;
        //$path_of_verifyCode =dirname(__FILE__).'/verifyCodes/verifyCode_'.$rand_id.'.jpg';
        //$fp = fopen($path_of_verifyCode,"w");                                  //文件名
        //fwrite($fp,$img);                                                   //写入文件 
        //fclose($fp);
    }
?>

<!DOCTYPE html>
<html lang="zh_cn">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=0">
	<title>野生工大助手 - 无验证码版</title>
	<link rel="stylesheet" href="//cdn.bootcss.com/weui/0.4.0/style/weui.min.css"/>
    <script src="//cdn.bootcss.com/jquery/3.0.0/jquery.min.js"></script>
	<script src="/js/login_score.js"></script>  
</head>
<body>
    <!-- 使用的是WeUI -->
	<form action="./require_grade.php" method="post">
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
                        <option value="2017-2018">2017-2018</option>
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
                        <option value="1">1</option>
                        <option selected="" value="2">2</option>
                        <option value="3">3</option>
                    </select>
                </div>
            </div>
<!--
			<div class="weui_cell weui_vcode">
				<div class="weui_cell_hd"><label class="weui_label">验证码</label></div>
				<div class="weui_cell_bd weui_cell_primary">
					<input class="weui_input" name="verify_code" type="text" placeholder="请输入验证码"/>
				</div>
				<div class="weui_cell_ft">
                <img id="verify_code" src="/verifyCodes/verifyCode_<?php print $rand_id ?>.jpg" onclick="update_verify_code()" />
				</div>
			</div>
-->
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
                    $('#loadingToast').fadeIn();
                });
            })
        </script>

		<input class="weui_btn weui_btn_primary" type="submit" value="查询" id="showLoadingToast"/>
	</form>		

	<article class="weui_article">


<h1>
<i class="weui_icon_success_circle"></i>&nbsp;账号和密码不会保留，请放心使用。<br>
<i class="weui_icon_warn"></i>&nbsp;数据仅供参考，请以教务系统为准。<br></h1>
<br>
<p>总加权平均分和总平均 GPA 的数据只对<b>没报二专业/辅修</b>的同学有效。<br>
GPA 根据 <a href="http://undergrad.bjut.edu.cn/WebInfo.aspx?Id=752">北工大教务处文件</a>，采用四分制计算。其他学校可能采用不同算法。<br>
如果存在分数不足60分的科目，默认加权分数计算则不包含该科目。<br>
展开详情可以查看该科目补考通过后的参考均分</p>

<br>
<a href="http://www.devchen.com/blog/coding/PHP/20170112-bjut-helper/#2017-1-11">关于更新后加权和绩点变化的说明</a><br>
<section>
如数据有问题(或者网站打不开了)请联系:<br>
<a href="http://www.wangyufeng.org">王雨峰</a>(alanwang424@gmail.com)<br>
<a href="http://www.coder17.com">陈仕玺</a>(chen17@coder17.com)<br>
辅修查询 by <a href = "https://blog.nyan.im/">郭都豪</a><br>
代码重构 by 马文瑞<br>
<br>
QR Code is designed by the most beautiful girl in the world.<br>
适用北京工业大学, <a href="https://github.com/wangyufeng0615/bjuthelper">Github</a><br>
<i class="weui_icon_warn"></i>&nbsp;本项目是已结题星火重点项目，已报备相关单位<br>
<a href="http://www.miit.gov.cn/">京ICP备16062922号-1</a>
</section>
</article>
<div style="display:none"><script type="text/javascript">var cnzz_protocol = (("https:" == document.location.protocol) ? " https://" : " http://");document.write(unescape("%3Cspan id='cnzz_stat_icon_1259582707'%3E%3C/span%3E%3Cscript src='" + cnzz_protocol + "s11.cnzz.com/stat.php%3Fid%3D1259582707' type='text/javascript'%3E%3C/script%3E"));</script></div>
</body>
</html>
