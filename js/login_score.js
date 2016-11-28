function update_verify_code(){
    //解决微信浏览器的缓存问题，强制刷新
    window.location.href='/login_grade.php'+'?id='+Math.floor((Math.random()*1000000))+1;
}

$(function() {
    $('#showLoadingToast').click(function() {
        $('#loadingToast').fadeIn().delay(3000).fadeOut();
    });
})