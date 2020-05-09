var bmap = {
	'option' : {
		'lock' : false,
		'container' : 'baidumap',
		/*
		'infoWindow' : {'width' : 250, 'height' : 100, 'title' : ''},
		'point' : {'lng' : lng, 'lat' : lat},
		'point_id' : {'lng_id' : lng_id, 'lat_id' : lat_id},
		'area_id' : {'province_id' : province_id, 'city_id' : city_id, 'district_id' : district_id},
		'address_id' : address_id,
		*/
	},
	'init' : function(option) {
		var $this = this;
		$this.option = $.extend({},$this.option,option);
		$this.option.defaultPoint = new BMap.Point($this.option.point.lng, $this.option.point.lat);
		$this.bgeo = new BMap.Geocoder();
		$this.bmap = new BMap.Map($this.option.container);
		$this.bmap.centerAndZoom($this.option.defaultPoint, 15);
		$this.bmap.enableScrollWheelZoom();
		$this.bmap.enableDragging();
		$this.bmap.enableContinuousZoom();
		$this.bmap.addControl(new BMap.NavigationControl());
		$this.bmap.addControl(new BMap.OverviewMapControl());
		//添加标注
		$this.marker = new BMap.Marker($this.option.defaultPoint);
		$this.marker.setLabel(new BMap.Label('请您移动此标记，选择您的坐标！', {'offset':new BMap.Size(10,-20)}));
		$this.marker.enableDragging();
		$this.bmap.addOverlay($this.marker);
		$this.showPointValue($this.marker.getPosition());
		//拖动地图事件
		$this.bmap.addEventListener("dragging", function() {
			$this.setMarkerCenter();
			$this.option.lock = false;
		});
		//缩入地图事件
		$this.bmap.addEventListener("zoomend", function() {
			$this.setMarkerCenter();
			$this.option.lock = false;
		});
		//拖动标记事件
		$this.marker.addEventListener("dragend", function (e) {
			$this.showPointValue();
			$this.showAddress();
			$this.bmap.panTo(new BMap.Point(e.point.lng, e.point.lat));
			$this.option.lock = false;
			$this.marker.setAnimation(null);
		});
	},
	'searchMapByAddress' : function(address) {
		var $this = this;
		 $this.bgeo.getPoint(address, function (point) {
			if (point) {
				$this.showPointValue();
				$this.showAddress();
				$this.bmap.panTo(point);
				$this.setMarkerCenter();
			}
		});
	},
	'searchMapByPCD' : function(address) {
		var $this = this;
		$this.option.lock = true;
		$this.searchMapByAddress($('#sel-provance').val()+$('#sel-city').val()+$('#sel-area').val());
	},
	'setMarkerCenter' : function() {
		var $this = this;
		var center = $this.bmap.getCenter();
		$this.marker.setPosition(new BMap.Point(center.lng, center.lat));
		$this.showPointValue();
		$this.showAddress();
	},
	'showPointValue' : function() {
		var $this = this;
		var point = $this.marker.getPosition();
		$('#'+$this.option.point_id.lng_id).val(point.lng);
		$('#'+$this.option.point_id.lat_id).val(point.lat);
	},
	'showAddress' : function() {
		var $this = this;
		var point = $this.marker.getPosition();
		$this.bgeo.getLocation(point, function (s) {
			if (s) {
				$('#'+$this.option.address_id).val(s.address);
				
				$('#'+$this.option.area_id.province_id).val(s.addressComponents.province);
				$('#'+$this.option.area_id.city_id).val(s.addressComponents.city);
				$('#'+$this.option.area_id.district_id).val(s.addressComponents.district);
				
				if (!$this.option.lock) {
					//cascdeInit(s.addressComponents.province,s.addressComponents.city,s.addressComponents.district);
				}
			}
		});
	}
};