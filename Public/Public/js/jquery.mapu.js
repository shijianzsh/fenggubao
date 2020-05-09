;(function($){
	$.fn.extend({
		"map":function(options){
			options = $.extend({
				line:2,	//軌數
				layer:3,	//層數
				userid:"",	//用戶帳戶
				topuserid:"",	//用戶帳戶
				isreg:1,	//是否允許在系譜圖內注册
				regurl:"",	//注册連接到的URL
				url:"/fund/index.php/index/mapjson/"	//獲取系譜圖json數據的URL地址
			},options);
			
			
			if($(this).length==0) return false;
			
			return this.each(function(){
				if($(this).attr("url")!=undefined && $(this).attr("url")!=""){
					options.url=$(this).attr("url")+"/mapjson/";
				}
				if($(this).attr("regurl")!=undefined && $(this).attr("regurl")!=""){
					options.regurl=$(this).attr("regurl");
				}
				
				rejson = function(userid){	//獲取並填充數據
					$.post(
						options.url,
						{"userid":userid,"layer":options.layer,'topuserid':options.topuserid},
						function(redata){
							if(redata.status==1){
								//obj = redata.data;
								obj = redata.data.sort(function(a, b) { return parseInt(a.plevel) > parseInt(b.plevel) ? 1 : -1;});
								//$(".map_son_tb").find("th,._left,._mid,._right").html("");	//清空表格数据
								$(".map_son_tb").remove();
								_baseP = obj[0].plevel;
								$.each(obj,function(i){
									var yj = $.parseJSON(obj[i].line_money);
									if(yj){
										if(yj['1']){
											var lv = yj['1'];
										}else{
											var lv = 0;
										}
										if(yj['2']){
											var rv = yj['2'];
										}else{
											var rv = 0;
										}
									}else{
										var lv = 0;
										var rv = 0;
									}
									if(i == 0){
										$(".tag").eq(0).html(_writetb({"userid":obj[i].userid,"lval":lv,"rval":rv,"mval":obj[i].reg_money,"is_pass":obj[i].is_pass,"is_boss":obj[i].is_boss,"is_agent":obj[i].is_agent,"level":obj[i].level}));
										
									}else{
										_rowitme = $(".map_ftab").find("th[suserid='"+obj[i].fuserid+"']").parents(".tag").index();
										_rowitme = _rowitme*options.line
										
										_rowitme = _rowitme + parseInt(obj[i].treeplace);
										ob_tab = $(".map_ftab").eq(obj[i].plevel-_baseP).find(".tag").eq(_rowitme-1); //定位到數據將要插入的表格
										
										ob_tab.html(_writetb({"userid":obj[i].userid,"lval":lv,"rval":rv,"mval":obj[i].reg_money,"is_pass":obj[i].is_pass,"is_boss":obj[i].is_boss,"is_agent":obj[i].is_agent,"level":obj[i].level}));
										
									}
								})
								
								_tb_h = $(".map_son_tb").eq(0).height();
								
								$(".tag").each(function(i){
									//alert($(this).html());
									if($(this).html()==""){
										
										
										
										//$(this).parents("table").eq(0).html("<tr><td>注册</td></tr>").css({"height":_tb_h});
										//所在表的索引
										tb_index = $(this).parents(".map_ftab").eq(0).index();
										//所在單元格的索引
										td_index = $(this).index();
										//alert(td_index%2);
										// 找到此單元格對應的父節點
										f_node = Math.ceil((td_index+1)/options.line);
										//父節點的文本內容
										f_node_text = $(".map_ftab").eq(tb_index-1).find("th").eq(f_node-1).text();
										_pass = $(".map_ftab").eq(tb_index-1).find("th").eq(f_node-1).attr('pass');
										//alert(_pass);
										if(options.isreg){
											if(_pass=='0'){	//父节点未开通不能注册
												$(this).html(_writetb({"tabinfo":"empty"}));
												$(this).find(".map_son_tb").css({"height":_tb_h});
											}else{
												if($.trim(f_node_text)=="注册"){
													_writetb({"tabinfo":"reg"});
													$(this).html(_writetb({"tabinfo":"empty"}));
													$(this).find(".map_son_tb").css({"height":_tb_h});
												}else{
													if($.trim(f_node_text)=="空位"){
														$(this).html(_writetb({"tabinfo":"empty"}));
														$(this).find(".map_son_tb").css({"height":_tb_h});
													}else{
														$(this).html(_writetb({"tabinfo":"reg","userid":f_node_text,"lorr":td_index % options.line+1}));
														$(this).find(".map_son_tb").css({"height":_tb_h});

													}
												}
											}
										}else{
											$(this).html(_writetb({"tabinfo":"empty"}));
											$(this).find(".map_son_tb").css({"height":_tb_h});
										}
									}
									
								})
								
								
								
							}
						},
						"json"
					)
				}
				
				
				extobj = $(this);
				pubfo = $(this).attr("pub");
				_writetb = function(parameters){ //返回會員信息的表格
					parameters = $.extend({
						userid:"",	//用戶帳戶
						userimg:"", //用戶圖片
						is_pass:1, //是否開通
						is_boss:0, //是否管理员
						is_agent:0, //是否代理
						lval:"",
						mval:"",
						rval:"",
						tabinfo:"user",
						level:"",
						lorr:0	//左还是右
					},parameters);
					
					switch(parameters.tabinfo){
					case "user": 
						if(parameters.is_pass==0){//level
							_isp = " style=\"color:#F00;\"";
							_isp += " class=\"level0\"" + " pass=\"0\"";
						}else{
							if(parameters.is_boss==1){
								_isp = " style=\"\"";
								_isp += " class=\"manage\"" + " pass=\"1\"";
							}else if(parameters.is_agent==1){
								_isp = " style=\"\"";
								_isp += " class=\"agent\"" + " pass=\"1\"";
							}else{
								_isp = " style=\"\"";
								_isp += " class=\"level"+parameters.level+"\"" + " pass=\"1\"";;
							}
						}
						_str = "<table class=\"map_son_tb\">";	//內層表格
						_str += "<tr><th colspan=\"3\""+_isp+" suserid=\""+ parameters.userid +"\">"+parameters.userid+"</th></tr>";
						_str += "<tr><td>左</td><td>本人</td><td>右</td></tr>";
						_str += "<tr><td class=\"_left\">"+parameters.lval+"</td><td class=\"_mid\">"+parameters.mval+"</td><td class=\"_right\">"+parameters.rval+"</td></tr>";
						_str += "</table>";
						break;	
					case "reg":
						_str = "<table class=\"map_son_tb\">";	//內層表格
						_str += "<tr><th><a href=\""+options.regurl+"fuserid/"+parameters.userid+"/treeplace/"+parameters.lorr+"\" target=\"_blank\">注册</a></th></tr>";
						_str += "</table>";
						break;
					case "empty":
						_str = "<table class=\"map_son_tb\">";	//內層表格
						_str += "<tr><th>空位</th></tr>";
						_str += "</table>";
						break;
					default:
						break;			
					}
					
					return _str;
				}
				
				
				_f_tab_b = "<table class=\"map_ftab\">";	//外层表格
				_f_tab_tr_b = "<tr>"
				_f_tab_td_b = "<td align=\"center\" class=\"tag\">";
				
				
				
				_f_tab_td_e = "</td>";
				_f_tab_tr_e = "</tr>";
				// 修飾圖片
				
				_f_tab_img_a = "<img src=\""+pubfo+"/images/map/t_tree_bottom_l.gif\" height=\"30\"><img src=\""+pubfo+"/images/map/t_tree_line.gif\" width=\"";
				_f_tab_img_b = "%\" height=\"30\"><img src=\""+pubfo+"/images/map/t_tree_top.gif\" height=\"30\"><img src=\""+pubfo+"/images/map/t_tree_line.gif\" width=\"";
				_f_tab_img_c = "%\" height=\"30\"><img src=\""+pubfo+"/images/map/t_tree_bottom_r.gif\" height=\"30\">";
				
				
				_f_tab_e = "</table>";
				
				var obj = $(this);
				
				_line_w_avg = parseInt((100 - 100 / options.line) / 2) ;	//计算修饰线的长度
				
				_html = _f_tab_b + _f_tab_tr_b + _f_tab_td_b + _writetb() + _f_tab_td_e + _f_tab_tr_e + _f_tab_tr_b + _f_tab_td_b + _f_tab_img_a + _line_w_avg + _f_tab_img_b + _line_w_avg + _f_tab_img_c + _f_tab_td_e + _f_tab_tr_e + _f_tab_e;	//第一層 第一單元
				
				for(i=1; i <= options.layer-1; i++){	//循環層數
					_for = Math.pow(options.line,i)
					_avg = parseInt(100/_for);
					_html += _f_tab_b;
					_html += _f_tab_tr_b;
					for(j = 1; j <= _for;  j++){	//循環每層個數
						_html += '<td align="center" width="'+_avg+'%" class=\"tag\">' + _writetb() + _f_tab_td_e
					}
					_html += _f_tab_tr_e;
					
					
					if(i!=options.layer-1){		//循環第個單元的修飾圖
						_html += _f_tab_tr_b;
						//_line_w_avg = parseInt((100-_avg)/2);
						for(j = 1; j <= _for;  j++){
							_html +='<td align="center" width="'+_avg+'%">' + _f_tab_img_a + _line_w_avg + _f_tab_img_b + _line_w_avg + _f_tab_img_c + _f_tab_td_e;
						}
						_html += _f_tab_tr_e
					}

					

					_html += _f_tab_e
				}
				
				obj.html(_html);
				maxwidth = obj.find(".map_ftab").eq(obj.find(".map_ftab").length-1).width();	//頁面最寬表格的寬度
				obj.find(".map_ftab").width(maxwidth);
				
				
				$("body").scrollLeft(maxwidth/2-$("body").width()/2)	//把滾動條滾動到中間
				//_userid = $(".map_son_tb:first th").html();
				
				rejson(options.userid);
				$("._map").off("click",".map_son_tb th");
				$("._map").on("click",".map_son_tb th",function(){
					if($(this).attr('suserid')!=""){
						options.topuserid='';
						rejson($(this).text());
					}
					// if($(this).text()!=""){
					// 	if($(this).text()!="注册"){
					// 		if($(this).text()!="空位"){
					// 			options.topuserid='';
					// 			rejson($(this).text());
					// 		}
					// 	}
					// }
					
				})
				
				
				
				
			});
		}
	});
})(jQuery);