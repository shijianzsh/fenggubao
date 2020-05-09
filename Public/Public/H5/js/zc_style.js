/**
 * Created by 从小就很酷 on 2016/10/26.
 */
$(function(){
    var hint=document.getElementById("hint");
    var password=document.getElementById("password");
    hint.onfocus=function(){
        if(this.value!="密码")
            return;
        this.style.display="none";
        password.style.display="block";
        password.value="";
        password.focus();
    }
    password.onblur=function(){
        if(this.value!=""){
            return;
        }
        this.style.display="none";
        hint.style.display="";
        hint.value="密码";
    }
})