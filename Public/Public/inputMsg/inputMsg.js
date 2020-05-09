$.extend({
	// obj: 对象 
	// dir: 提示框位于输入框上、下方向[top,bottom] 
	// msg: 提示信息[可省略] 
	// top: 指定top值[可忽略] 
	// auto_hide: 是否自动隐藏[可忽略,默认false不隐藏]
	inputMsg:function(obj,dir,msg,top,auto_hide){
		console.log('1');
		if($('#input_msg').length){
			$.inputMsgClose();
		}
		
		var msg_text = (typeof(msg)=='undefined') ? obj.attr('msg') : msg;
		var input_msg = '';
		var input_top = (typeof(top)=='undefined') ? false : (isNaN(top) ? false : top);
		var input_left = 0;
		var auto_hide = (typeof(auto_hide)=='undefined') ? false : auto_hide;
		switch(dir){
			case 'top':
				input_msg = "<div class='input_msg_top' id='input_msg'><i></i><span>"+msg_text+"</span></div>";
				input_top = input_top===false ? obj.offset().top-35 : obj.offset().top-input_top;
				input_left = obj.offset().left;
			break;
			case 'bottom':
				input_msg = "<div class='input_msg_bottom' id='input_msg'><i></i><span>"+msg_text+"</span></div>";
				input_top = input_top===false ? obj.offset().top+obj.height() : obj.offset().top+input_top;
				input_left = obj.offset().left;
			break;
		}
		$('body').append(input_msg);
		$('#input_msg').css({'top':input_top+'px','left':input_left+'px'});
		
		if(auto_hide){
			obj.mouseleave(function(){
				$.inputMsgClose();
			});
		}
	},
	inputMsgClose:function(){
		console.log('2');
		$('#input_msg').remove();
	}
});