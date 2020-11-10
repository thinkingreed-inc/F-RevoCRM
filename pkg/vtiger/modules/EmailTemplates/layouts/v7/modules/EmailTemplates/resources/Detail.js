/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Vtiger_Detail_Js("EmailTemplates_Detail_Js",{
    
    /*
	 * function to trigger delete record action
	 * @params: delete record url.
	 */
    deleteRecord : function(deleteRecordActionUrl) {
		var message = app.vtranslate('LBL_DELETE_CONFIRMATION');
        
        // warning message for Customer Login Details template
        if(deleteRecordActionUrl.search('record=10') != -1) { 
            var message = app.vtranslate('LBL_CUTOMER_LOGIN_DETAILS_TEMPLATE_DELETE_MESSAGE');
        }
        
		app.helper.showConfirmationBox({'message' : message}).then(
            function(e) {
				app.request.post({url: deleteRecordActionUrl+'&ajaxDelete=true'}).then(
                    function(error, data){
                        if(!error){
                            window.location.href = data;
                        }else{
                            app.helper.showErrorNotification({message: error});
                        }
                    }
                );
            },
            function(error, err){
            }
		);
	}
    
},{
    /**
    * Function get html content from the record given and append the code to iframe element
    */
    showTemplateContent: function(){
        var record = jQuery('#recordId').val();
        var params={
            "module" : "EmailTemplates",
            "action" : "ShowTemplateContent",
            "mode"   : "getContent",
            "record" : record
        };
        app.request.post({data: params}).then(function(error, data){
            var templateContent = data.content;
            jQuery('#TemplateIFrame').contents().find('html').html(templateContent);
        });
    },
	
	/**
	 * We have to load Settings Index Js but the parent module name will be empty so we are extending this api and passing 
	 * last parameter as settings (This is useful to settings side events like accordion click and settings menu search)
	 */
	addIndexComponent : function() {
		this.addModuleSpecificComponent('Index','Vtiger','Settings');
	},
	
    registerEvents : function() {
        this.registerStarToggle();
        this.showTemplateContent();
    }
});


