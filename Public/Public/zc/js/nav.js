$(function(){
       $(".nav li").click(function(){
		   // 删除其他兄弟元素的样式
	    $(this).siblings('li').removeClass('on');  
        $(this).addClass('on');  
	
		var subMenu = $(this).children(".submenu");
		if(subMenu.length > 0)
		{ 
		subMenu = subMenu.eq(0);
			var curHeight = subMenu.height();
			if(subMenu.css("display") != "none")
				subMenu.slideUp(300);
			else
			{
				$(".nav li > .submenu").each(function() {
                    if($(this).css("display") != "none")
						$(this).hide();
                });
				subMenu.slideDown(300);
			}
		}
	});
	
	$(".nav li").mouseleave(function(){
		var subMenu = $(this).children(".submenu");
		if(subMenu.length > 0)
		{
			subMenu = subMenu.eq(0);
			if(subMenu.css("display") != "none")
				subMenu.slideUp(300);
		}
	});
	});