$(document).ready(function(){

	if($("._map").length>0){
		var layer = $("#m_layer").val();
		//var userid = $("#m_userid").val()
		$("._map").map({"layer":layer});
	}
	$("#m_btn").click(function(){
		var layer = $("#m_layer").val();
		var userid = $("#m_userid").val();
		$("._map").map({"layer":layer,"userid":userid});
	});
	$("#m_mymap").click(function(){
		var layer = $("#m_layer").val();
		//var userid = "my"
		$("._map").map({"layer":layer});
	});
	$("#top_mymap").click(function(){
		var layer = $("#m_layer").val();
		str = $(".map_son_tb th").eq(0).html().trim();
		if(str!="" && str !='admin'){
			$("._map").map({"layer":layer,"topuserid":str});
		}
	})
	
	
	
	if($("._tree").length>0){
		var layer = $("#t_layer").val();
		//var userid = $("#m_userid").val()
		$("._tree").tree({"layer":layer});
	};
	$("#t_btn").click(function(){
		var layer = $("#t_layer").val();
		var userid = $("#t_userid").val();
		$("._tree").tree({"layer":layer,"userid":userid});
	});
	$("#t_mymap").click(function(){
		var layer = $("#t_layer").val();
		$("._tree").tree({"layer":layer});
	});
	
	
})