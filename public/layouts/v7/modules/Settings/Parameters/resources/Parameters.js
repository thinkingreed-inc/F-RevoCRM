/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
Vtiger.Class('Settings_Parameters_Js', {
	
	//holds the current instance
	currentInstance : false,

	isLabelChange : false,
	
	/**
	 * This function used to triggerAdd Currency
	 */
	triggerAdd : function(event) {
		event.stopPropagation();
		var instance = Settings_Parameters_Js.currentInstance;
		instance.showEditView();
	},
	
	/**
	 * This function used to trigger Edit Currency
	 */
	triggerEdit : function(event, id) {
		event.stopPropagation();
		var instance = Settings_Parameters_Js.currentInstance;
        instance.showEditView(id);
	},
	
	/**
	 * This function used to trigger Delete Currency
	 */
	triggerDelete : function(event, id) {
		event.stopPropagation();
         
		var currentTarget = jQuery(event.currentTarget);
		var currentTrEle = currentTarget.closest('tr'); 
		var instance = Settings_Parameters_Js.currentInstance;

        var params = {};
		params['module'] = app.getModuleName();
		params['parent'] = app.getParentModuleName();
		params['action'] = 'DeleteAjax';
		params['record'] = id;

        app.helper.showConfirmationBox({'message' : app.vtranslate('JS_ARE_YOU_SURE_YOU_WANT_TO_DELETE')}).then(function(){
            app.request.post({"data":params}).then(function(error, data){
                if(error == null){
					instance.loadListViewContents();
                    var successfullSaveMessage = app.vtranslate('JS_PARAMETER_DELETED_SUEESSFULLY');
                    app.helper.showSuccessNotification({'message':successfullSaveMessage});
                } else {
                    app.helper.showErrorNotification({'message' : error.message});
                }
            });
        })
	}
	
}, {
	
	//constructor
	init : function() {
		Settings_Parameters_Js.currentInstance = this;
	},
	
	/*
	 * function to show editView for Add/Edit Currency
	 * @params: id - currencyId
	 */
	showEditView : function(id) {
      
		var thisInstance = this;
		var aDeferred = jQuery.Deferred();
		var params = {};
		params['module'] = app.getModuleName();
		params['parent'] = app.getParentModuleName();
		params['view'] = 'EditAjax';
		params['record'] = id;
		app.request.post({ "data": params }).then(
			function (err, data) {
				if (err === null) {
					app.helper.showModal(data);
					var form = jQuery('#editCurrency');
					var record = form.find('[name="record"]').val();

					form.submit(function (e) {
						e.preventDefault();
					});

					var params = {
						submitHandler: function (form) {
							var form = jQuery(form);
							thisInstance.saveParameter(form);
						}
					};

					form.vtValidate(params);
				} else {
					aDeferred.reject(err);
				}
			}
		);
		return aDeferred.promise();
	},

	/**
	 * This function will save the currency details
	 */
	saveParameter : function(form) {
		var thisInstance = this;
		var data = form.serializeFormData();
		data['module'] = app.getModuleName();
		data['parent'] = app.getParentModuleName();
		data['action'] = 'SaveAjax';

		if(!/^[a-zA-Z0-9._]+$/.test(data['key'])) {
			alert(app.vtranslate('LBL_KEY_ERROR'));
			return ;
		}
		app.helper.showProgress();
		app.request.post({"data":data}).then(
			function(err,data) {
				if(err === null) {
                    app.helper.hideModal();
                    var successfullSaveMessage = app.vtranslate('JS_PARAMETER_SAVED');
                    app.helper.showSuccessNotification({'message':successfullSaveMessage});
					thisInstance.loadListViewContents();
					app.helper.hideProgress();
				}else {
					app.helper.showErrorNotification({'message' : err.message});
					app.helper.hideProgress();
				}
			}
		);
	},
	
	/**
	 * This function will load the listView contents after Add/Edit currency
	 */
	loadListViewContents : function() {
		var thisInstance = this;
		var params = {};
		params['module'] = app.getModuleName();
		params['parent'] = app.getParentModuleName();
		params['view'] = 'List';
		
		app.request.post({"data":params}).then(
			function(err,data) {
                if(err === null) {
                    //replace the new list view contents
                    jQuery('#listViewContent').html(data);
                    thisInstance.registerRowClick();
                }
			}
		);
	},

	/**
	 * This function will delete the currency and save the transferCurrency details
	 */
	deleteParameter : function(id, transferCurrencyEle, currentTrEle) {
		var params = {};
		params['module'] = app.getModuleName();
		params['parent'] = app.getParentModuleName();
		params['action'] = 'DeleteAjax';
		params['record'] = id;

		app.request.post({"data":params}).then(
			function(err,data) {
                if(err === null){
                    app.helper.hideModal();
                    var successfullSaveMessage = app.vtranslate('JS_PARAMETER_DELETED_SUEESSFULLY');
                    app.helper.showSuccessNotification({'message':successfullSaveMessage});
                    currentTrEle.fadeOut('slow').remove();
                }else {
					app.helper.showErrorNotification({'message' : err.message});
				}
		});
	},
	
    registerRowClick : function() {
		var thisInstance = this;
		jQuery('.listViewEntries').on('click',function(e) {
			var row = jQuery(e.currentTarget);
			if(row.find('.fa-pencil').length <= 0) {
				return;
			} 
			thisInstance.showEditView(row.data('id'));
		})  
	},
		
    registerEvents : function() {
        this.registerRowClick();
    }
	
});