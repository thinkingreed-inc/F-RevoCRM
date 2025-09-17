/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Settings_Users_PreferenceDetail_Js("Settings_Users_Calendar_Js",{},{
    
	/**
	 * register Events for my preference
	 */
	registerEvents : function(){
		this._super();
		Settings_Users_PreferenceEdit_Js.registerChangeEventForCurrencySeparator();
		Settings_Users_PreferenceEdit_Js.registerNameFieldChangeEvent();
		this.registerCalendarSharingTypeChangeEvent(this.getForm());
	},

	registerCalendarSharingTypeChangeEvent: function (url) {
		jQuery('#sharedType').on('change', function () {
			var sharingType = jQuery(this).val();

			var selectedUsersValue = jQuery('#selectedUsersValue');
			var selectedUsersLabel = jQuery('#selectedUsersLabel');

			if (sharingType === 'selectedusers') {
				selectedUsersValue.removeClass('hide');
				selectedUsersLabel.removeClass('hide');
			} else {
				selectedUsersValue.addClass('hide');
				selectedUsersLabel.addClass('hide');
			}
		});
		jQuery('#sharedType').trigger('change');
	},
});