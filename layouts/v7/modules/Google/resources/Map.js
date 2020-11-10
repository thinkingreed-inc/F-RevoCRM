/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/ 
Vtiger.Class("Google_Map_Js", {}, {

	showMap : function(container) {
		var thisInstance = this;
		container = jQuery(container);
		app.helper.showProgress();
		var params = {
			'module' : 'Google',
			'action' : 'MapAjax',
			'mode' : 'getLocation',
			'recordid' : container.find('#record').val(),
			'source_module' : container.find('#source_module').val()
		}
		app.request.post({"data":params}).then(function(error,response){
			var result = JSON.parse(response);
			app.helper.hideProgress();
			var address = result.address;
			container.find('#record_label').val(result.label);
			var location = jQuery.trim((address).replace(/\,/g," "));
			if(location != '' && location != null){
				container.find("#address").html(location);
				container.find('#address').removeClass('hide');
			}else{
				app.helper.hidePopup();
				app.helper.showAlertNotification({message:app.vtranslate('Please add address information to view on map')});
				return false;
			}
			container.find("#mapLink").on('click',function() {
			   window.open(thisInstance.getQueryString(location));
			});
			thisInstance.loadMapScript();
		});
	},

	loadMapScript : function() {
			var API_KEY = 'YOUR_MAP_API_KEY'; // CONFIGURE THIS 

			if (API_KEY == 'YOUR_MAP_API_KEY' && typeof console) console.error("Google Map API Key not configured."); 

			jQuery.getScript("https://maps.google.com/maps/api/js?key=" + API_KEY + "&sensor=true&async=2&callback=initialize", function () {});
	},

	getQueryString : function (address) {
		address = address.replace(/,/g,' ');
		address = address.replace(/ /g,'+');
		return "https://maps.google.com/maps?q=" + address + "&zoom=14&size=512x512&maptype=roadmap&sensor=false";
	}
});

function initialize(){
	geocoder = new google.maps.Geocoder();
	var mapOptions = {
		zoom : 15,
		mapTypeId : google.maps.MapTypeId.ROADMAP,
	};
	map = new google.maps.Map(document.getElementById('map_canvas'), mapOptions);
	var address = jQuery(document.getElementById('address')).text();
	var label = jQuery(document.getElementById('record_label')).val();
	if(geocoder) {
		geocoder.geocode({'address': address}, function(results, status) {
			if(status == google.maps.GeocoderStatus.OK) {
				if(status != google.maps.GeocoderStatus.ZERO_RESULTS) {
					map.setCenter(results[0].geometry.location);
					var infowindow = new google.maps.InfoWindow({
							content : '<b>'+label+'</b><br><br>'+address,
							size : new google.maps.Size(150,50)
						});
					var marker = new google.maps.Marker({
						position : results[0].geometry.location,
						map : map, 
						title : address
					}); 
					google.maps.event.addListener(marker, 'click', function() {
						infowindow.open(map,marker);
					});
				}
			}
		});
  }
}
