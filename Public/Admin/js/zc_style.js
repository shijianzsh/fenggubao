/**
 * Created by Administrator on 2016/11/7.
 */
$(function(){
    $(".zc_list_left .zc_item_title").on("click",function(){
        $(this).next("ul").stop().slideToggle(500);
    });

    $(".zc-pic").on("click",function(e){
        if($(this).next().css("display")=="block"){
            $(this).next().stop().fadeOut(500);
        }else{
            $(this).next().stop().fadeIn(500);
        };
        e.stopPropagation();
    });
    $(".zc_aniqbfi").click(function(e){
        e.stopPropagation();
    });
    $(document.body).on("click",function(){
        $(".zc_aniqbfi").stop().fadeOut(500);
    });
});