/**
 * Created by Administrator on 2016/11/7.
 */
$(function(){
    //给登陆按钮绑定回车事件 回车登陆
    $(document).keyup(function(event){
        if(event.keyCode ==13){
            $("#zc_take").trigger("click");
        }
    });

    // 忘记密码提示
    $('#forget').on("click",function(){
    	layer.alert('PC端暂时不提供在线找回密码功能哟~您可以在APP上面个人中心修改密码或者找回', {
    		skin: 'layui-layer-molv' //样式类名
    		,closeBtn: 0
    	});
	});

    //点击登陆按钮的时候判断全部的input是否为空，三个变量可以临时存储验证号码，
    $("#zc_take").on("click",function(){
		$(this).val("请等待...").css("background","#ddd")
		if ($(this).hasClass('iserror')) {
			layer.alert('请检查输入内容', {
				skin: 'layui-layer-molv'
				,closeBtn: 0
			});
			verify_code_init();
			$("#zc_take").val("登陆").css("background","#5e85b5")
			return false;
        }
        var username = $('#zc_name').val();
        var password = $('#zc_password').val();
        var verify_code = $('#zc_verification').val();
		var remember = $('#zc_remember').val();
		var wcd = $('#wcd').val();
		if (username=='' || password=='') {
			layer.alert('请认真填写登陆信息', {
				skin: 'layui-layer-molv'
				,closeBtn: 0
			});
			verify_code_init();
            $("#zc_take").val("登陆").css("background","#5e85b5")
			return false;
		}
                            
		$.ajax({
			url: $_G.login_url,
			type: 'POST',
			data: {username:username,password:password,verify_code:verify_code,remember:remember,wcd:wcd},
			success: function(re){
				if(re==''){
					layer.load();
					window.location.href= $_G.login_success_url;
				}else{
					var login_fail_warning = (re=='LOGIN_FIAL_WARNING') ? true : false;
					re = (login_fail_warning==true) ? '你的帐号登录失败已超过'+login_fail_count_max+'次，请输入短信验证码进行登录' : re;
					layer.alert(re, {
						skin: 'layui-layer-molv'
						,closeBtn: 0
					});
					verify_code_init(login_fail_warning);
                    $("#zc_take").val("登陆").css("background","#5e85b5");
					return false;
				}
			}
		});

    });
    //所有的input获取焦点的时候加上边框
    $("input.input").focus(function(){
        $(this).css("border","1px solid #1db9aa");
    });
    //验证用户是否输入了手机号
    $('#zc_name').blur(function(){
    	return true;
        var molide=$(this).val();
        //if(!/^(13[0-9]|14[0-9]|15[0-9]|18[0-9])\d{8}$/i.test(molide)){
        if(!/^([0-9a-zA-Z]+)$/i.test(molide)){
            $(this).parent("div").siblings(".zc_title").children("em").html("请输入正确的用户名");
            $(this).css("border","1px solid #1db9aa");
			$('#zc_take').addClass('iserror');
        }else{
            $(this).css("border","1px solid #ddd");
            $(this).parent("div").siblings(".zc_title").children("em").html("");
			$('#zc_take').removeClass('iserror');
        }
    });
    //验证密码是否输入或者是否为空
    $("#zc_password").blur(function(){
        var password=$(this).val();
        if(password==""){
            $(this).parent("div").siblings(".zc_title").children("em").html("请输入密码");
            $(this).css("border","1px solid #1db9aa");
			$('#zc_take').addClass('iserror');
        }else{
            $(this).parent("div").siblings(".zc_title").children("em").html("");
            $(this).css("border","1px solid #ddd");
			$('#zc_take').removeClass('iserror');
        }
    });
    
});

//验证码初始化
function verify_code_init(){
	var login_fail_warning = arguments[0] ? arguments[0] : false;
	
	$('#zc_verification').val('');
	
	if (login_fail_warning==true) {
		$('#verify_img').remove();
		$('#zc_password').val('');
		$('#zc_verification').attr('placeholder', '请输入短信验证码');
	} else {
		$('#verify_img').click();
	}
};