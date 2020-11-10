/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

jQuery.Class('ExtensionStore_ExtensionStore_Js', {}, {
	/**
	 * Function to register events for banner
	 */
	registerEventsForBanner: function () {
		var bxTarget = jQuery('.bxslider');
		var items = jQuery('li', bxTarget);
		if (items.length) {
			bxTarget.bxSlider({
				mode: 'fade',
				auto: true,
				pager: items.length > 1,
				speed: items.length > 1 ? 1500 : 0,
				pause: 3000
			});
		}
	},
	/**
	 * Function to getPromotions from marketplace
	 */
	getPromotions: function () {
		var thisInstance = this;
		var params = {
			'module': 'ExtensionStore',
			'view': 'Listings',
			'mode': 'getPromotions'
		};
		app.request.post({data:params}).then(
			function (err, data) {
				if ((typeof data != 'undefined') && (jQuery(data).find('img').length > 0)) {
					jQuery('.dashboardBanner').append(data);
					thisInstance.registerEventsForBanner();
				} else {
					jQuery('.togglePromotion').addClass('hide');
				}
			},
			function (error) {
			}
		);
	},

	/**
	 * Function to request get promotions from market place based on promotion closed date
	 */
	getPromotionsFromMarketPlace: function (promotionClosedDate) {
		var thisInstance = this;
		if (promotionClosedDate != null) {
			var maxPromotionParams = {
				'module': 'ExtensionStore',
				'action': 'Promotion',
				'mode': 'maxCreatedOn'
			};
			app.request.post({data:maxPromotionParams}).then(
				function (err, data) {
					var date = data['result'];
					var dateObj = new Date(date);
					var closedDate = new Date(promotionClosedDate);
					var dateDiff = ((dateObj.getTime()) - (closedDate.getTime())) / (1000 * 60 * 60 * 24);
					if (dateDiff > 0) {
						thisInstance.getPromotions();
					} else {
						jQuery('.togglePromotion').addClass('hide');
					}
				});
		} else if (promotionClosedDate == null) {
			thisInstance.getPromotions();
		}
	},

	registerEventsForTogglePromotion: function () {
		var thisInstance = this;
		jQuery('.togglePromotion').on('click', function (e) {
			var element = jQuery(e.currentTarget);
			var bannerContainer = jQuery('.banner-container');

			if (element.hasClass('up')) {
				bannerContainer.slideUp();
				element.find('.fa-chevron-up').addClass('hide');
				element.find('.fa-chevron-down').removeClass('hide');
				element.addClass('down').removeClass('up');
			} else if (element.hasClass('down')) {
				if (bannerContainer.find('img').length <= 0) {
					thisInstance.getPromotionsFromMarketPlace(null);
				}
				bannerContainer.slideDown();
				element.find('.fa-chevron-down').addClass('hide');
				element.find('.fa-chevron-up').removeClass('hide');
				element.addClass('up').removeClass('down');
			}
		});
	},

	insertTogglePromotionHtml: function () {
		var toggleHtml = '<div class="btn-group">'+
				'<button class="btn btn-default addButton togglePromotion up">'+
					'<span id="hide" class="fa fa-chevron-up"></span>'+
					'<span id="show" class="fa fa-chevron-down hide"></span>'+
				'</button>'+
				'</div>';
		jQuery('.dashboardHeading').find('.buttonGroups').append(toggleHtml);
	},

	registerEvents: function () {
		var thisInstance = this;
		var moduleName = app.getModuleName();

		if ((moduleName == 'Home')) {
			thisInstance.insertTogglePromotionHtml();
			thisInstance.getPromotionsFromMarketPlace();
			thisInstance.registerEventsForTogglePromotion();
			jQuery('.togglePromotion').trigger('click');
		}
	}
});

jQuery(document).ready(function () {
	var moduleName = app.getModuleName();
	if (moduleName == 'Home') {
		var instance = new ExtensionStore_ExtensionStore_Js();
		instance.registerEvents();
	}
});
