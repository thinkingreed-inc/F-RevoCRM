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

	//WebComponentのイベントを登録済みかどうか（document に委譲するため一度だけ登録する）
	componentEventsRegistered : false,

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
		if (event) {
			if (event.preventDefault) event.preventDefault();
			if (event.stopPropagation) event.stopPropagation();
			if (event.stopImmediatePropagation) event.stopImmediatePropagation();
		}
		var instance = Settings_Parameters_Js.currentInstance;
		if (instance) {
			instance.showEditView(id);
		}
		return false;
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
			// 編集アイコンのクリックは registerEditButtonClick が処理する。
			// 行に直接バインドしたこのハンドラは document への委譲より先に走るため、
			// ここで除外しないとダイアログを開く処理が二重に走る。
			if(jQuery(e.target).closest('.parameter-edit-btn').length > 0) {
				return;
			}
			var row = jQuery(e.currentTarget);
			if(row.find('.fa-pencil').length <= 0) {
				return;
			}
			thisInstance.showEditView(row.data('id'));
		})
	},

	/**
	 * 編集アイコンのクリックを登録する
	 * 一覧を再描画しても効くよう document に委譲する
	 */
	registerEditButtonClick : function() {
		var thisInstance = this;
		jQuery(document).off('click.parameterEdit', '.parameter-edit-btn')
			.on('click.parameterEdit', '.parameter-edit-btn', function(e) {
				e.preventDefault();
				// 行クリック側のハンドラと二重に発火させない
				e.stopPropagation();
				thisInstance.showEditView(jQuery(e.currentTarget).data('record-id'));
			});
	},

	/**
	 * 編集ダイアログ（React WebComponent）のイベントを登録する
	 * WebComponent 側は bubbles:true で発火するため document で受ける
	 */
	registerParameterEditComponent : function() {
		if (Settings_Parameters_Js.componentEventsRegistered) {
			return;
		}
		Settings_Parameters_Js.componentEventsRegistered = true;

		var isTarget = function(target) {
			return !!target && target.id === 'parameterEditComponent';
		};

		// 保存成功時: サーバー側の値を反映するため一覧を再読み込みする
		document.addEventListener('save', function(e) {
			if (!isTarget(e.target)) {
				return;
			}
			window.location.reload();
		});

		// キャンセル・閉じる時: is-open を false に戻す
		document.addEventListener('cancel', function(e) {
			if (!isTarget(e.target)) {
				return;
			}
			e.target.setAttribute('is-open', 'false');
		});

		document.addEventListener('open-change', function(e) {
			if (!isTarget(e.target)) {
				return;
			}
			// e.detail が false または { isOpen: false } の両方に対応
			var detail = e.detail;
			if (detail === false || (detail && typeof detail === 'object' && detail.isOpen === false)) {
				e.target.setAttribute('is-open', 'false');
			}
		});
	},

	registerEvents : function() {
		this.registerRowClick();
		this.registerEditButtonClick();
		this.registerParameterEditComponent();
	},

	/**
	 * 編集ダイアログ（React WebComponent）を開く
	 */
	showEditView : function(id) {
		var component = document.getElementById('parameterEditComponent');
		if (!component || !id) {
			return false;
		}
		component.setAttribute('record-id', id);
		component.setAttribute('is-open', 'true');
		return false;
	}

});
