/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Settings_Vtiger_List_Js("Settings_SMSNotifier_List_Js", {
	/**
	 * Function to trigger edit and add new configuration for SMS server
	 */
	triggerEdit: function (event, url) {
		event.stopPropagation();
		var instance = Vtiger_List_Js.getInstance();
		instance.EditRecord(url);
	},
	/**
	 * Function to trigger delete SMS provider Configuration
	 */
	triggerDelete: function (event, url) {
		event.stopPropagation();
		var instance = Vtiger_List_Js.getInstance();
		instance.DeleteRecord(url);
	}

}, {
	/**
	 * Function to show the SMS Provider configuration details for edit and add new
	 */
	EditRecord: function (url) {
		var thisInstance = this;
		app.request.get({url: url}).then(
			function (err, data) {
				if (err) {
					app.helper.showErrorNotification(err);
					return;
				}
				var callback = function (data) {
					var form = jQuery('#smsConfig');
					thisInstance.registerProviderTypeChangeEvent(form);
					thisInstance.registerPhoneFormatPop(form);
					thisInstance.registerSaveConfiguration(form);
                                        thisInstance.copyToClipboard(form);
				}
				var params = {};
				params.cb = callback;
				app.helper.showModal(data, params);
			});
	},
	registerPhoneFormatPop: function (form) {
		form.find('#phoneFormatWarningPop').popover();
	},
	/**
	 * Function to register change event for SMS server Provider Type
	 */
	registerProviderTypeChangeEvent: function (form) {
            var thisInstance = this;
		form.find('.providerType').change(function (e) {
			var currentTarget = jQuery(e.currentTarget);
			var selectedProviderName = currentTarget.val();
			var params = form.serializeFormData();

			params['module'] = app.getModuleName();
			params['parent'] = app.getParentModuleName();
			params['view'] = 'EditAjax';
			params['provider'] = selectedProviderName;
			app.helper.showProgress();
			app.request.get({data: params}).then(function (err, data) {
				app.helper.hideProgress();
				jQuery('#provider').html(data);
				if (jQuery(data).find('select').hasClass('select2')) {
					vtUtils.applyFieldElementsView(jQuery('#provider'));
				}
                                thisInstance.copyToClipboard(form);
			});

		});
	},
	/**
	 * Function to save the SMS Server Configuration Details from edit and Add new configuration 
	 */
	registerSaveConfiguration: function (form) {
		var thisInstance = this;
		jQuery('#smsConfig').vtValidate({
			submitHandler: function () {
				var params = form.serializeFormData();
				params['module'] = app.getModuleName();
				params['parent'] = app.getParentModuleName();
				params['action'] = 'SaveAjax';
				app.helper.showProgress();
				app.request.post({data: params}).then(
					function (err, data) {
						app.helper.hideProgress();
						if (data) {
							app.helper.hideModal();
						}
						thisInstance.loadListViewRecords();
					});
				return false;
			}
		});
	},
	/**
	 * Function to delete Configuration for SMS Provider
	 */
	DeleteRecord: function (url) {
		var thisInstance = this;
		var message = app.vtranslate('LBL_DELETE_CONFIRMATION');
		app.helper.showConfirmationBox({'message': message}).then(
			function (e) {
				app.request.post({url: url}).then(
					function (err, data) {
						app.helper.showSuccessNotification(app.vtranslate('JS_RECORD_DELETED_SUCCESSFULLY'));
						thisInstance.loadListViewRecords();
					});
			});
	},
        
        copyToClipboard : function(form) {
            if(jQuery('.copyToClipboard', form).length > 0) {
                jQuery('.copyToClipboard', form).on('click', function(e){
                    var currentTarget = jQuery(e.currentTarget);
                    currentTarget.closest('.input-group').find('input').select();
                    var success = document.execCommand("copy");
                    if(success) {
                        app.helper.showSuccessNotification({message : app.vtranslate('JS_COPIED_SUCCESSFULLY')});
                    }
                });
            }
        },
    
	/**
	 * Function to register all the events
	 */
	registerEvents: function () {
		this.initializePaginationEvents();
	}
})

