var LH = 'http://' + location.host + '/';
var LHR = location.href;
var CW = document.documentElement.clientWidth;
var CH = document.documentElement.clientHeight;

var pattern_list = ['zc_if_email', 'zc_if_price', 'zc_if_number', 'zc_if_figure', 'zc_if_name', 'zc_if_identity', 'zc_if_qq', 'zc_if_mobile', 'zc_if_fixed', 'zc_if_empty', 'zc_if_sundry', 'zc_if_www', 'zc_if_regulation'];

$(function () {

    //common ajax loading notice
    $('body').ajaxStart(function () {
        loadingWaiting();
        $(this).ajaxStop(function () {
            loadingWaiting(true);
        });
    });

    //分页跳转
    $('.gopage').click(function () {
        pn = $(this).siblings('.pageinput').val();
        tmpurl = $(this).attr('base');
        targeturl = tmpurl.replace('jay', pn);
        if (pn == '') {
            alert('请输入页码');
            return;
        }
        window.location.href = targeturl;
    })

    //通用readonly属性的input统一底色变灰
    $("input[type='text']").each(function () {
        if ($(this).attr('readonly')) {
            $(this).addClass('input_readonly');
        }
    });

});

function logout(url) {
    location.href = url;
}

//输入框弹出select选项
function showSelect($obj, select_option) {
    var date = new Date();
    var select_id = 'select_' + date.getSeconds() + date.getMilliseconds();
    var select_html = '<select id="' + select_id + '">' + select_option + '</select>';
    $obj.after(select_html);
    $('#' + select_id).css({
        'left': $obj.offset().left,
        'top': $obj.offset().top + $obj.height() + 3,
        'position': 'absolute',
        'z-index': 10
    });
    $('#' + select_id).live('change', function () {
        $obj.val($(this).val());
        $(this).remove();
    });
}

//通用 -- 顶部正中弹出提示并可自动关闭
function commonNotice() {
    var str = arguments[0] ? arguments[0] : '缺少提示信息';
    var close = arguments[1] ? arguments[1] : 1;
    if ($('#commonNotice').length) {
        $('#commonNotice>strong').html(str);
    }
    else {
        $('body').append('<div id="commonNotice"><strong>' + str + '</strong></div>');
    }
    if (close == 1) {
        $('#commonNotice').fadeOut(2000, function () {
            $(this).remove();
            //关闭后自动检测加载通用提示内容
            autoLoadCommonNotice();
        });
    }
}

//通用 -- 页面右下角弹窗显示并可自动关闭
function commonRightBottomAlert() {
    var content = arguments[0] ? arguments[0] : '';
    var close = arguments[1] ? arguments[1] : 1;
    var name = arguments[2] ? arguments[2] : 'common_right_bottom_alert';

    if (content == '') {
        return false;
    }
    if (name == '') {
        return false;
    }

    var id_name = 'commonRightBottomAlert' + name;
    if ($('.' + id_name).length) {
        $('.' + id_name).html(content);
    }
    else {
        $('body').append('<div id="commonRightBottomAlert" class="' + id_name + '"><i class="close"></i>' + content + '</div>');
    }
    if (close == 1) {
        $('.' + id_name).find('.close').hide();
        $('.' + id_name).slideUp(2000);
    }
    $('.' + id_name).find('.close').live('click', function () {
        common_right_bottom_alert.push(name);
        $.cookies.set(common_right_bottom_alert_cookie, common_right_bottom_alert);
        $(this).hide();
        $('.' + id_name).slideUp(2000);
    });
}

//通用确认操作弹窗
function confirmWin() {
    if (confirm('确认执行该操作?')) {
        waitingWin();
        return true;
    } else {
        return false;
    }
}

//通用请等待遮罩弹窗
function waitingWin() {
    var msg = arguments[0] ? arguments[0] : '正在执行中，请稍后...';

    var index = layer.open({
        type: 1,
        title: false, //不显示标题栏
        closeBtn: false,
        area: '300px;',
        shade: 0.8,
        id: 'LAY_layuipro', //设定一个id，防止重复弹出
        moveType: 1, //拖拽模式，0或者1
        content: '<div id="waitingWin"><div class="loading"></div>' + msg + '</div>',
    });

    return index;
}


//通用异步获取商家信息
//obj:要展示提示信息的对象名称(如:name);value:获取提示信息的传入参数值(只接受用户ID或用户名)
function getStoreInfo(obj, value) {
    if (value == '') {
        $.inputMsg($(obj), 'bottom', '缺少参数');
        return false;
    }
    $.ajax({
        url: $_G.get_store_info_url,
        data: {key: value},
        type: 'POST',
        success: function (re) {
            if (re.error != '') {
                $.inputMsg($(obj), 'bottom', re.error, false, true);
                return false;
            } else {
                $.inputMsg($(obj), 'bottom', re.data.store_name, false, true);
                return true;
            }
        },
    });
}

//获取IP所属位置
function getIpLocation() {
    if ($('.ip').length) {
        $('.ip').each(function () {
            var T = $(this);
            $.ajax({
                url: 'http://restapi.amap.com/v3/ip',
                type: 'get',
                data: {
                    'key': 'd7410ab7b422bce6206699dcf72976a3',
                    'ip': T.text()
                },
                dataType: 'JSONP',
                success: function (detail) {
                    html = detail.province + detail.city;
                    if (html != '') {
                        T.html(T.text() + '&nbsp;[' + html + ']');
                    }
                },
                global: false
            });
        });
    }
}

//通用右上角加载中提示(主要用于页面跳转等待的过程中)
function loadingWaiting() {
    var is_close = arguments[0] ? arguments[0] : false;
    var msg = arguments[1] ? arguments[1] : '加载中...';
    if (is_close) {
        $('.loadingWaiting').remove();
    } else {
        $('body').append('<div class="loadingWaiting"><span>' + msg + '</span></div>');
    }
}

/***************************************************************************************************/

$(function () {

    //页面a-click触发loadingWaiting()
    $('a').click(function () {
        var pattern = /^(http|\/[a-zA-Z])/;
        var onclick = $(this).attr('onclick'); //当a中有onclick时不加载loadingWaiting
        if (pattern.test($(this).attr('href')) && onclick == 'undefined') {
            loadingWaiting();
        }
    });

    //增加修改的
    $(".form-group").addClass("left");
    $(".form-group label,.form-group div").addClass("left");
    $(".form-inline").find("br").remove();
    $("ul").attr("style", "margin-left:0px;");
    $(".text-danger").attr("href", "javascript:;")
    
    $(".text-danger").on("click", function () {
        var pic = $(this).prev("img").attr("src");
        layer.open({
            title: "<a href='" + pic + "' target='_blank'>查看大图</a>",
            type: 1,
            skin: 'layui-layer-demo', //样式类名
            closeBtn: 0, //不显示关闭按钮
            move: 'layui-layer-content',
            area: ['50%', '50%'],
            resize: false,
            anim: 2,
            shadeClose: true, //开启遮罩关闭
            content: '<div id="zc_datu"><span></span><img class="zc_img" src=' + pic + ' alt="完蛋~图片没有找到" style="width:100%;height:auto;margin:0 auto;"></div>'
        });
        var num = 0;
        $("#zc_datu>span").on("click", function () {
            num++
            $(this).parent("#zc_datu").parent(".layui-layer-content").rotate(90 * num);
        });
    });

    //通用顶部导航自动聚焦
    var top_navigation = ['Admin', 'Shop', 'Merchant', 'System'];
    $.each(top_navigation, function (key, val) {
        if (LHR.indexOf(val) != -1) {
            //$('.zc_Top .zc_lainainfe li:eq('+key+')').addClass('hover');
            $('.zc_Top .zc_lainainfe li.top_menu_' + key).addClass('hover');
        }
        ;
    });


    //通用侧边栏导航自动聚焦
    var zc_broadside_left_i = 0;
    $('.zc_broadside_left a').each(function (index) {
        var href = $(this).attr('href');
        var suffix = href.substring(href.length - 5);
        href = suffix == '.html' ? href.replace('.html', '') : href;
        if (LHR.indexOf(href) != -1) {
            zc_broadside_left_i = index;
            //$(this).parent('li').addClass('on');
        }
        ;
    });
    $('.zc_broadside_left a:eq(' + zc_broadside_left_i + ')').parent('li').addClass('on');

    //通用返回上一页
    $('.zc_broadside_right .zc_anwia').append('<a href="javascript:history.back(-1);" class="history_back">返回</a>');

    $(".zc_anwia a").addClass("zc_btuaineq");
    $("span.glyphicon").css("padding", "0");

    $(".zc_shop_img").on("click", function () {
        var img = $(this).attr("src");
        layer.open({
            title: "<a href='" + img + "' target='_blank'>查看大图</a>",
            type: 1,
            skin: 'layui-layer-demo', //样式类名
            closeBtn: 0, //不显示关闭按钮
            move: 'layui-layer-content',
            anim: 2,
            resize: false,
            area: ['50%', '50%'],
            shadeClose: true, //开启遮罩关闭
            content: '<div id="zc_datu"><span></span><img class="zc_img" src=' + img + ' alt="完蛋~图片没有找到"></div>'
        });
        var num = 0;
        $("#zc_datu>span").on("click", function () {
            num++;
            $(this).parent("#zc_datu").parent(".layui-layer-content").rotate(90 * num);
        });
    });


    $(".img-thumbnail").on("click", function () {
        var img = $(this).attr("src");
        layer.open({
            title: "<a href='" + img + "' target='_blank'>查看大图</a>",
            type: 1,
            skin: 'layui-layer-demo', //样式类名
            closeBtn: 0, //不显示关闭按钮
            move: 'layui-layer-content',
            anim: 2,
            resize: false,
            area: ['50%', '50%'],
            shadeClose: true, //开启遮罩关闭
            content: '<div id="zc_datu"><span></span><img class="zc_img" src=' + img + ' alt="完蛋~图片没有找到"></div>'
        });
        var num = 0;
        $("#zc_datu>span").on("click", function () {
            num++;
            $(this).parent("#zc_datu").parent(".layui-layer-content").rotate(90 * num);
        });
    });


    // 商户管理商品详情的删除按钮
    function zc_S() {
        $(" a.zc_sc_btn").on("click", function () {
            $(this).parent("div").parent("li").remove();
        });
    };

    zc_S();

    // 添加图片按钮
    $(".zc_addition").on("click", function () {
        //初始化file_name
        var file_name = 'carousel1';

        if ($(this).siblings('li').length) {
            var i = $(this).siblings("li:last").attr("itemid");
            i = parseInt(i);
        }

        if (typeof($(this).attr('file_name_prefix')) != 'undefined' && $(this).attr('file_name_prefix') != '') {
            file_name = $(this).attr('file_name_prefix');
        }

        var index = 0;

        if (index <= i) {
            index = parseInt(i + 1);
        }

        var Li = '<li class="zc_details_item_' + index + '" itemid="' + index + '">' +
            '<div class="warp_pic_item">' +
            '<img id="imgShow_list_' + file_name + '_' + index + '" src="' + $_G.Public + '/images/zc_default.jpg" class="zc_shop_img_show"  alt="图片加载错误"/ style="cursor: pointer;">' +
            '<a href="javascript:" class="zc_sc_btn"></a>' +
            '</div>' +
            '<div class="zc_imgStum">上传文件</div>' +
            '<input type="file" id="up_img_list_' + file_name + '_' + index + '" name="' + file_name + '_' + index + '" class="zc_np_pic"/>' +
            '</li>';

        $(this).parent("#warp").append(Li);
        new uploadPreview({
            UpBtn: "up_img_list_" + file_name + '_' + index + "",
            DivShow: "warp_pic_item",
            ImgShow: "imgShow_list_" + file_name + '_' + index + "",
            Width: 210,
            Height: 210
        });

        $(".zc_shop_img_show").on("click", function () {
            var img = $(this).attr("src");
            layer.open({
                title: "<a href='" + img + "' target='_blank'>查看大图</a>",
                type: 1,
                skin: 'layui-layer-demo', //样式类名
                closeBtn: 0, //不显示关闭按钮
                move: 'layui-layer-content',
                anim: 2,
                resize: false,
                area: ['660px', '705px'],
                shadeClose: true, //开启遮罩关闭
                content: '<div id="zc_datu"><span></span><img class="zc_img" src=' + img + ' alt="完蛋~图片没有找到" ></div>'
            });
            var num = 0;
            $("#zc_datu>span").on("click", function () {
                num++;
                $(".zc_img").rotate(90 * num);
            });
        });

        zc_S();


    });

    // 添加图片按钮
    $(".zc_addition2").on("click", function () {
        var Li = '<li class="zc_details_item_' + Math.random() + '">' +
            '<div class="warp_pic">' +
            '<img id="imgShow_list_' + Math.random() + '" src="' + $_G.Public + '/images/zc_default.jpg" class="zc_shop_img"  alt="图片加载错误"/ style="cursor: pointer;">' +
            '<a href="javascript:" class="zc_sc_btn"></a>' +
            '</div>' +
            '<div class="zc_imgStum2">上传文件</div>' +
            '<input type="hidden" class="mutilimg" name="' + $(this).attr('name') + '" value="" />' +
            '</li>';

        $(this).parent("#warp").append(Li);
    });


    // 店铺信息提交按钮
    //[提交按钮+修改/删除按钮]通用确认取消提示功能
    if ($('.zc_message').length) {
        if ($('.zc_message').find("input[type='submit']").length) {
            $('.zc_message').find("input[type='submit']").click(function () {
                return confirmWin();
            });
        }
        /*
         * button submit基本上为搜索表单提交,在此暂不启用确认提示功能
        if ($('.zc_message').find("button[type='submit']").length){
            $('.zc_message').find("button[type='submit']").click(function(){
                return confirmWin();
            });
        }
        */
        $('.zc_message').find('a').click(function () {
            var href = $(this).attr('href');
            if (href.indexOf('Del') != -1 || href.indexOf('Delete') != -1 || href.indexOf('UnBind') != -1) {
                return confirmWin();
            }
        });
    }
    ;

    var submit_success = true; //全局submit成功与否状态
    function submit_dd(current_pattern) {
        submit_success = false;

        var exists = false;
        $.each(pattern_list, function (key, val) {
            if (current_pattern == val) {
                exists = true;
            }
        });
        if (!exists) {
            pattern_list.push(current_pattern);
        }

        $("input[type='submit']").prop("disabled", true).css("background", "#ddd");
        $("button[type='submit']").prop("disabled", true).css("background", "#ddd");
    };

    function submit_ae(current_pattern) {
        submit_success = true;

        $.each(pattern_list, function (key, val) {
            if (current_pattern == val) {
                pattern_list.splice(key, 1);
            }
        });


        if (submit_success) {
            $("input[type='submit']").prop("disabled", false).css("background", "#00A0E9");
            $("button[type='submit']").prop("disabled", false).css("background", "#00A0E9");
        }
    };
    // 页面电子邮箱验证
    $("input.zc_if_email").keyup(function () {
        var email = $(this).val();
        var reg = /\w+[@]{1}\w+[.]\w+/;
        var sib = $(this).siblings("span.zc_if_alt");
        var sibtxt = $(this).siblings("span.zc_if_alt").text();
        var mag = sibtxt == "" ? "请输入正确的电子邮箱地址" : sibtxt;
        if (!reg.test(email) && $(this).val() != "") {
            if (sib.html() == "" || typeof(sib.html()) == "undefined") {
                $.inputMsg($(this), 'bottom', mag, 40);
                //sib.show().html("请输入正确的电子邮箱地址");
            } else if (sib.html() != "") {
                $.inputMsg($(this), 'bottom', sibtxt, 40);
                //sib.show();
            }
            submit_dd();
        } else {
            $("#input_msg").remove();
            submit_ae('zc_if_email');
            //sib.hide();
        }
        ;
        if (email == "") {
            submit_ae('zc_if_email');
        }
    });

    // 页面验证金额
    $("input.zc_if_price").keyup(function () {
        var price = $(this).val();
        var reg = /^[0-9]+\d*\.{0,1}\d{0,4}$/;
        var sib = $(this).siblings("span.zc_if_alt");
        var sibtxt = $(this).siblings("span.zc_if_alt").text();
        var mag = sibtxt == "" ? "请输入纯数字或者用小数点隔开，小数点后面不超过四位数字，不包含中文或英文," : sibtxt;
        if (!reg.test(price) && $(this).val() != "") {
            if (sib.html() == "" || typeof(sib.html()) == "undefined") {
                $.inputMsg($(this), 'bottom', mag, 40);
                //sib.show().html("请输入纯数字或者用小数点隔开，小数点后面不超过四位数字，不包含中文或英文,");
            } else if (sib.html() != "") {
                $.inputMsg($(this), 'bottom', sibtxt, 40);
                //sib.show();
            }
            submit_dd('zc_if_price');
        } else {
            $("#input_msg").remove();
            submit_ae('zc_if_price');
            //sib.hide();
        }
        ;
        if (price == "") {
            submit_ae('zc_if_price');
        }
    });


    // 页面验证纯数字 0-9
    $("input.zc_if_number").keyup(function () {
        var number = $(this).val();
        var sib = $(this).siblings("span.zc_if_alt");
        var sibtxt = $(this).siblings("span.zc_if_alt").text();
        var mag = sibtxt == "" ? "请输入纯数字，不包含中文或英文及特殊符号" : sibtxt;
        if (!/^[0-9]*$/.test(number) && $(this).val() != "") {
            if (sib.html() == "" || typeof(sib.html()) == "undefined") {
                $.inputMsg($(this), 'bottom', mag, 40);
                //sib.show().html("请输入纯数字，不包含中文或英文及特殊符号");
            } else if (sib.html() != "") {
                $.inputMsg($(this), 'bottom', sibtxt, 40);
                //sib.show();
            }
            submit_dd('zc_if_number');
        } else {
            //sib.hide();
            $("#input_msg").remove();
            submit_ae('zc_if_number');
        }
        ;
        if (number == "") {
            submit_ae('zc_if_number');
        }
    });

    // 页面验证纯数字 1-9

    $("input.zc_if_figure").keyup(function () {
        var figure = $(this).val();
        var sib = $(this).siblings("span.zc_if_alt");
        var sibtxt = $(this).siblings("span.zc_if_alt").text();
        var mag = sibtxt == "" ? "请输入纯数字，不包含中文或英文及特殊符号或0开头" : sibtxt;
        if (!/^[1-9]\d*$/.test(figure) && $(this).val() != "") {
            if (sib.html() == "" || typeof(sib.html()) == "undefined") {
                $.inputMsg($(this), 'bottom', mag, 40);
                //sib.show().html("请输入纯数字，不包含中文或英文及特殊符号或0");
            } else if (sib.html() != "") {
                $.inputMsg($(this), 'bottom', sibtxt, 40);
                //sib.show();
            }
            submit_dd('zc_if_figure');
        } else {
            $("#input_msg").remove();
            submit_ae('zc_if_figure');
            // sib.hide();
        }
        if (figure == "") {
            submit_ae('zc_if_figure');
        }
    });

    // 页面验证输入是否汉字
    $("input.zc_if_name").keyup(function () {
        var name = $(this).val();
        var pattern = /^[\u4e00-\u9fa5]+$/;
        var sib = $(this).siblings("span.zc_if_alt");
        var sibtxt = $(this).siblings("span.zc_if_alt").text();
        var mag = sibtxt == "" ? "请输入汉字，不包含数字英文及特殊符号" : sibtxt;
        if (!pattern.test(name) && $(this).val() != "") {
            if (sib.html() == "" || typeof(sib.html()) == "undefined") {
                $.inputMsg($(this), 'bottom', mag, 40);
                //sib.show().html("请输入汉字，不包含数字英文及特殊符号");
            } else if (sib.html() != "") {
                $.inputMsg($(this), 'bottom', sibtxt, 40);
                //sib.show();
            }
            submit_dd('zc_if_name');
        } else {
            //sib.hide('zc_if_name');
            $("#input_msg").remove();
            submit_ae("zc_if_name");
        }
        if (name == "") {
            submit_ae("zc_if_name");
        }
        ;
    });


    // 页面验证身份证
    $("input.zc_if_identity").keyup(function () {
        var identity = $(this).val();
        var if_ident = /^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}([0-9]|X)$/;
        ;
        var sib = $(this).siblings("span.zc_if_alt");
        var sibtxt = $(this).siblings("span.zc_if_alt").text();
        var mag = sibtxt == "" ? "请输入正确的身份证号，大陆身份证18位" : sibtxt;
        if (!if_ident.test(identity) && $(this).val() != "") {
            if (sib.html() == "" || typeof(sib.html()) == "undefined") {
                $.inputMsg($(this), 'bottom', mag, 40);
                //sib.show().html("请输入正确的身份证号，大陆身份证18位")
            } else if (sib.html() != "") {
                $.inputMsg($(this), 'bottom', sibtxt, 40);
                //sib.show();
            }
            submit_dd('zc_if_identity');
        } else {
            $("#input_msg").remove();
            //sib.hide();
            submit_ae('zc_if_identity');
        }
        ;
        if (identity == "") {
            submit_ae('zc_if_identity');
        }
    });


    // 页面验证QQ号
    $("input.zc_if_qq").keyup(function () {
        var qq = $(this).val();
        var if_qq = /^[1-9][0-9]{4,}$/;
        var sib = $(this).siblings("span.zc_if_alt");
        var sibtxt = $(this).siblings("span.zc_if_alt").text();
        var mag = sibtxt == "" ? "请输入5-10位QQ号" : sibtxt;
        if (!if_qq.test(qq) && $(this).val() != "") {
            if (sib.html() == "" || typeof(sib.html()) == "undefined") {
                $.inputMsg($(this), 'bottom', mag, 40);
                //sib.show().html("请输入5-10位QQ号");
            } else if (sib.html() != "") {
                $.inputMsg($(this), 'bottom', sibtxt, 40);
                //sib.show();
            }
            submit_dd('zc_if_qq');
        } else {
            $("#input_msg").remove();
            //sib.hide();
            submit_ae('zc_if_qq');
        }
        ;
        if (qq == "") {
            submit_ae('zc_if_qq');
        }
    });


    // 页面验证固话或者手机号
    $("input.zc_if_fixed").keyup(function () {
        var mobile = $.trim($(this).val());
        var isMobile = /^(((13[0-9]{1})|(15[0-9]{1})|(18[0-9]{1})|(17[0-9]{1})|(14[0-9]{1}))+\d{8})$/;  //手机正则
        //var isPhone = /^0\d{2,3}(\-)?\d{7,8}(-\d{1,6})?$/;  //固话正则
        var isPhone = /^(0[0-9]{2,3}(-)?)?([0-9]{7,8})+(-[0-9]{1,4})?$/;
        var sib = $(this).siblings("span.zc_if_alt");
        var sibtxt = $(this).siblings("span.zc_if_alt").text();
        var mag = sibtxt == "" ? "请输入正确的手机号或者固话" : sibtxt;

        if (!isMobile.test(mobile) && !isPhone.test(mobile)) {
            if (sib.html() == "" || typeof(sib.html()) == "undefined") {
                $.inputMsg($(this), 'bottom', mag, 40);
            } else if (sib.html() != "") {
                $.inputMsg($(this), 'bottom', sibtxt, 40);
            }
            submit_dd('zc_if_fixed');
        } else {
            $("#input_msg").remove();
            submit_ae('zc_if_fixed');
        }

        /*
	    //如果为1开头则验证手机号码  
	    if (mobile.substring(0, 1) == 1) {
			if (!isMobile.exec(mobile) && mobile.length != 11) {
	            	if(sib.html()=="" || typeof(sib.html())=="undefined"){
                        $.inputMsg($(this),'bottom',mag,40);
	            		//sib.show().html("请输入正确的手机号或者固话");
					}else if(sib.html()!=""){
                        $.inputMsg($(this),'bottom',sibtxt,40);
	            		//sib.show();
					}
	          		submit_dd('zc_if_fixed');	
			}else{
	            $("#input_msg").remove();
				//sib.hide();
				submit_ae('zc_if_fixed');
			}
			if(mobile==""){
	            submit_ae('zc_if_fixed');
			}
	    }  
	    //如果为0开头则验证固定电话号码  
		else if (mobile.substring(0, 1) == 0) {
	            if (!isPhone.test(mobile) && $(this).val() != "") {
                    if(sib.html()=="" || typeof(sib.html())=="undefined"){
                        $.inputMsg($(this),'bottom',mag,40);
                        //sib.show().html("请输入正确的手机号或者固话");
                    }else if(sib.html()!=""){
                        $.inputMsg($(this),'bottom',sibtxt,40);
                    	//  sib.show();
                    }
	          		submit_dd('zc_if_fixed')
			}else{
                    $("#input_msg").remove();
	            	//sib.hide();
				submit_ae('zc_if_fixed');
			};
			if(mobile==""){
				submit_ae('zc_if_fixed');
			}
		}
		*/
    });


    // 页面验证非空验证
    $("input.zc_if_empty").blur(function () {
        var empty = $(this).val();
        var span_nema = $(this).prev("span").text();
        var newstr = span_nema.substring(0, span_nema.length - 1);
        if (empty == "") {
            layer.msg('' + newstr + '不能为空', {
                icon: 5,
                skin: 'layer-ext-moon'
            });
            $(this).css("border", "1px solid red");
            submit_dd('zc_if_empty');
        } else {
            $(this).css("border", "1px solid #ddd");
            submit_ae('zc_if_empty');
        }

    });


    // 页面验证网址
    $("input.zc_if_www").keyup(function () {
        var www = $(this).val();
        var reg = /^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/;
        var sib = $(this).siblings("span.zc_if_alt");
        var sibtxt = $(this).siblings("span.zc_if_alt").text();
        var mag = sibtxt == "" ? "请输入正确的网络地址" : sibtxt;
        if (!reg.test(www) && $(this).val() != "") {
            if (sib.html() == "" || typeof(sib.html()) == "undefined") {
                //sib.show().html("请输入正确的网络地址");
                $.inputMsg($(this), 'bottom', mag, 40);
            } else if (sib.html() != "") {
                $.inputMsg($(this), 'bottom', sibtxt, 40);
                //sib.show();
            }
            submit_dd('zc_if_www')
        } else {
            $("#input_msg").remove();
            //sib.hide();
            submit_ae('zc_if_www');
        }
        ;
        if (www == "") {
            submit_ae('zc_if_www');
        }
    });


    // 页面上各种环境的手机验证

    //1,纯手机验证 Class="zc_if_mobile"

    $("input.zc_if_mobile").keyup(function () {
        var c_mobile = $(this).val();
        var reg = /^1[34578]\d{9}$/;
        var sib = $(this).siblings("span.zc_if_alt");
        var sibtxt = $(this).siblings("span.zc_if_alt").text();
        var mag = sibtxt == "" ? "请输入正确的11位手机号" : sibtxt;
        if (!reg.test(c_mobile) && $(this).val() != "") {
            if (sib.html() == "" || typeof(sib.html()) == "undefined") {
                $.inputMsg($(this), 'bottom', mag, 40);
                //sib.show().html("请输入正确的11位手机号");
            } else if (sib.html() != "") {
                $.inputMsg($(this), 'bottom', sibtxt, 40);
                ///sib.show();
            }
            submit_dd('zc_if_mobile')
        } else {
            $("#input_msg").remove();
            //sib.hide();
            submit_ae('zc_if_mobile');
        }
        ;
        if (c_mobile == "") {
            submit_ae('zc_if_mobile');
        }
        ;
    });
    //页面规则验证 单独使用
    $("input.zc_if_regulation").keyup(function () {
        var reg = /^[\u4e00-\u9fa5]+$/;
        var msg = /^[A-Za-z0-9\/+,]+$/;
        var sib = $(this).siblings("span.zc_if_alt");
        var sibtxt = $(this).siblings("span.zc_if_alt").text();
        var mag = sibtxt == "" ? "请输入正确的规则名字,只能输入汉字" : sibtxt;
        if (reg.test($(this).val()) && msg.test($(".zc_if_sundry").val())) {
            $("#input_msg").remove();
            submit_ae('zc_if_regulation');
        } else if (reg.test($(this).val())) {
            $("#input_msg").remove();
        } else {
            if (sib.html() == "" || typeof(sib.html()) == "undefined") {
                $.inputMsg($(this), 'bottom', mag, 40);
            } else if (sib.html() != "") {
                $.inputMsg($(this), 'bottom', sibtxt, 40);
            }
            submit_dd('zc_if_regulation');
        }
        ;
    });
    //页面验证英文加数字加/
    $("input.zc_if_sundry").keyup(function () {
        var reg = /^[A-Za-z0-9\/+,]+$/;
        var msg = /^[\u4e00-\u9fa5]+$/;
        var sib = $(this).siblings("span.zc_if_alt");
        var sibtxt = $(this).siblings("span.zc_if_alt").text();
        var mag = sibtxt == "" ? "请输入正确的规则路径，不包含汉字及'/'除外的特殊符号" : sibtxt;
        if (reg.test($(this).val()) && msg.test($(".zc_if_regulation").val())) {
            $("#input_msg").remove();
            submit_ae('zc_if_sundry');
        } else if (reg.test($(this).val())) {
            $("#input_msg").remove();
        } else {
            if (sib.html() == "" || typeof(sib.html()) == "undefined") {
                $.inputMsg($(this), 'bottom', mag, 40);
            } else if (sib.html() != "") {
                $.inputMsg($(this), 'bottom', sibtxt, 40);
            }
            submit_dd('zc_if_sundry');
        }
        ;
    });
    
});
	