/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Vtiger_Edit_Js("Users_Edit_Js",{},{
	
	
	duplicateCheckCache : {},
    
	/**
	 * Function to register recordpresave event
	 */
	registerRecordPreSaveEvent : function(form){
		var thisInstance = this;
		app.event.on(Vtiger_Edit_Js.recordPresaveEvent, function(e, data) {
			var userName = jQuery('input[name="user_name"]').val();
			var newPassword = jQuery('input[name="user_password"]').val();
			var confirmPassword = jQuery('input[name="confirm_password"]').val();
			var record = jQuery('input[name="record"]').val();
            var firstName = jQuery('input[name="first_name"]').val();
            var lastName = jQuery('input[name="last_name"]').val();
            var specialChars = /[<\>\"\,]/;
            if((specialChars.test(firstName)) || (specialChars.test(lastName))) {
                app.helper.showErrorNotification({message :app.vtranslate('JS_COMMA_NOT_ALLOWED_USERS')});
                e.preventDefault();
                return false;
            }
			var firstName = jQuery('input[name="first_name"]').val();
			var lastName = jQuery('input[name="last_name"]').val();
			if((firstName.indexOf(',') !== -1) || (lastName.indexOf(',') !== -1)) {
                app.helper.showErrorNotification({message :app.vtranslate('JS_COMMA_NOT_ALLOWED_USERS')});
				e.preventDefault();
				return false;
			}
			if(record == ''){
				if(newPassword != confirmPassword){
                    app.helper.showErrorNotification({message :app.vtranslate('JS_REENTER_PASSWORDS')});
					e.preventDefault();
				}else if(!app.helper.checkStrengthPassword(newPassword)) {
                    app.helper.showErrorNotification({message :app.vtranslate('JS_INVALID_STRENGTH_PASSWORDS')});
					e.preventDefault();
				}

                if(!(userName in thisInstance.duplicateCheckCache)) {
                    e.preventDefault();
                    thisInstance.checkDuplicateUser(userName).then(
                        function(data,error){
                            thisInstance.duplicateCheckCache[userName] = data;
                            form.submit();
                        }, 
                        function(data){
                            if(data) {
                                thisInstance.duplicateCheckCache[userName] = data;
                                app.helper.showErrorNotification({message :app.vtranslate('JS_USER_EXISTS')});
                            } 
                        }
                    );
                } else {
                    if(thisInstance.duplicateCheckCache[userName] == true){
                        app.helper.showErrorNotification({message :app.vtranslate('JS_USER_EXISTS')});
                        e.preventDefault();
                    } else {
                        delete thisInstance.duplicateCheckCache[userName];
                        return true;
                    }
                }
            }
        })
	},
	
	checkDuplicateUser: function(userName){
		var aDeferred = jQuery.Deferred();
		var params = {
				'module': app.getModuleName(),
				'action' : "SaveAjax",
				'mode' : 'userExists',
				'user_name' : userName
			}
		app.request.post({data:params}).then(
				function(err,data) {
					if(data){
						aDeferred.resolve(data);
					}else{
						aDeferred.reject(data);
					}
				}
			);
		return aDeferred.promise();
	},
	
	/**
	 * Function load the Jodit editor for signature field in edit view of my preference page.
	 */
	registerSignatureEvent: function(){
		var templateContentElement = jQuery("#Users_editView_fieldName_signature");
		if(templateContentElement.length > 0) {
			var joditInstance = new Vtiger_Jodit_Js();
			// ユーザー署名欄用ツールバー（JoditEditorのデフォルトを上書き）
			// 非表示にしているボタンはコメントアウトで残しています。
			// 再追加する場合は対象行のコメントを解除してください。
			// buttonsMD/SM/XS は指定しない（配列安全マージ処理が自動適用する）
			var customConfig = {
				buttons: [
					'bold', 'italic', 'underline', '|',
					'brush', '|',
					'fontsize', '|',
					'ul', 'ol', '|',
					'image', '|',
					'source'
					// ,'font'      // フォントファミリー（旧CKEditor4から移行時に非表示とした）
					// ,'link'      // リンク挿入（旧CKEditor4から移行時に非表示とした）
					// ,'eraser'    // 書式クリア（旧CKEditor4から移行時に非表示とした）
					// ,'align'     // テキスト配置（旧CKEditor4から移行時に非表示とした）
				]};
			joditInstance.loadJoditEditor(templateContentElement,customConfig);
		}
	},
	
	registerEvents : function() {
        this._super();
		var form = this.getForm();
		this.registerRecordPreSaveEvent(form);
        this.registerSignatureEvent();
        Settings_Users_PreferenceEdit_Js.registerChangeEventForCurrencySeparator();
        
        var instance = new Settings_Vtiger_Index_Js(); 
        instance.registerBasicSettingsEvents();
	}
});

// Actually, Users Module is in Settings. Controller in application.js will check for Settings_Users_Edit_Js 
Users_Edit_Js("Settings_Users_Edit_Js");