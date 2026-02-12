/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Vtiger_List_Js("RecycleBin_List_Js", {
	recordSelectTrackerInstance: false,
	emptyRecycleBin: function (url) {
		var message = app.vtranslate('JS_MSG_EMPTY_RB_CONFIRMATION');
		app.helper.showConfirmationBox({'message': message}).then(
			function (e) {
				var deleteURL = url + '&mode=emptyRecycleBin';
				var instance = new RecycleBin_List_Js();
				app.request.post({url: deleteURL}).then(
						function (error, data) {
							if (!error) {
								instance.recycleBinActionPostOperations(data);
							} else {
								app.helper.showErrorNotification({message: error});
							}
						}
				);
			},
			function (error, err) {
			}
		)
	},
	deleteRecords: function (url) {
		var listInstance = Vtiger_List_Js.getInstance();
		var validationResult = listInstance.checkListRecordSelected();
		if (validationResult != true) {
			var selectedIds = listInstance.readSelectedIds(true);
			var cvId = listInstance.getCurrentCvId();
			var message = app.vtranslate('LBL_MASS_DELETE_CONFIRMATION');
			app.helper.showConfirmationBox({'message': message}).then(
				function (e) {
					var sourceModule = jQuery('#sourceModule').val();
					var deleteURL = url + '&viewname=' + cvId + '&selected_ids=' + selectedIds + '&mode=deleteRecords&sourceModule=' + sourceModule;
					app.helper.showProgress();
					app.request.post({url: deleteURL}).then(
							function (error, data) {
								if (data) {
									app.helper.hideProgress();
									var instance = new RecycleBin_List_Js();
									instance.recycleBinActionPostOperations(data);
								}
							}
					);
				},
				function (error, err) {
				})
		} else {
			listInstance.noRecordSelectedAlert();
		}

	},
	restoreRecords: function (url) {
		var listInstance = Vtiger_List_Js.getInstance();
		var validationResult = listInstance.checkListRecordSelected();
		if (validationResult != true) {
			var selectedIds = listInstance.readSelectedIds(true);
			var excludedIds = listInstance.readExcludedIds(true);
			var cvId = listInstance.getCurrentCvId();
			var message = app.vtranslate('JS_LBL_RESTORE_RECORDS_CONFIRMATION');
			app.helper.showConfirmationBox({'message': message}).then(
				function (e) {
					var sourceModule = jQuery('#sourceModule').val();
					var restoreURL = url + '&viewname=' + cvId + '&selected_ids=' + selectedIds + '&excluded_ids=' + excludedIds + '&mode=restoreRecords&sourceModule=' + sourceModule+"&search_params="+JSON.stringify(listInstance.getListSearchParams());
					app.helper.showProgress();
					app.request.post({url: restoreURL}).then(function (error, data) {
						app.helper.hideProgress();
						if (error === null) {
							jQuery('.vt-notification').remove();
							var moduleLabel = data.modulelabel;
							if (!moduleLabel) {
								moduleLabel = app.vtranslate('SINGLE_' + sourceModule);
							}
							var instance = new RecycleBin_List_Js();
							instance.recycleBinActionPostOperations(data);
							var successNote = app.vtranslate('JS_RECORDS_RESTORED', selectedIdsArray.length, moduleLabel);
							app.helper.showSuccessNotification({'message': successNote});
						} else {
							app.event.trigger('post.save.failed', error);
						}
					});
				},
				function (error, err) {
				})
		} else {
			listInstance.noRecordSelectedAlert();
		}
	},
	/**
	 * Function to convert id into json string
	 * @param <integer> id
	 * @return <string> json string
	 */
	convertToJsonString: function (id) {
		var jsonObject = [];
		jsonObject.push(id);
		return JSON.stringify(jsonObject);
	},
	/**
	 * Function to delete a record
	 */
	deleteRecord: function (recordId) {
		var recordId = RecycleBin_List_Js.convertToJsonString(recordId);
		var listInstance = Vtiger_List_Js.getInstance();
		var message = app.vtranslate('LBL_DELETE_CONFIRMATION');
		var sourceModule = jQuery('#sourceModule').val();
		var cvId = listInstance.getCurrentCvId();
		app.helper.showConfirmationBox({'message': message}).then(
			function (e) {
				var module = app.getModuleName();
				var postData = {
					"module": module,
					"viewname": cvId,
					"selected_ids": recordId,
					"action": "RecycleBinAjax",
					"sourceModule": sourceModule,
					"mode": "deleteRecords"
				}
				app.helper.showProgress();
				app.request.post({data: postData}).then(
					function (error, data) {
						if (data) {
							app.helper.hideProgress();
							var instance = new RecycleBin_List_Js();
							instance.recycleBinActionPostOperations(data);
						}
					}
				);
			},
			function (error, err) {
			});
	},
	restoreAction: function (recordId, restoreExternalFile) {
		var aDeferred = jQuery.Deferred();
		var recordId = RecycleBin_List_Js.convertToJsonString(recordId);
		var listInstance = Vtiger_List_Js.getInstance();
		var sourceModule = jQuery('#sourceModule').val();
		var cvId = listInstance.getCurrentCvId();

		var module = app.getModuleName();
		var postData = {
			"module": module,
			"action": "RecycleBinAjax",
			"viewname": cvId,
			"selected_ids": recordId,
			"mode": "restoreRecords",
			"sourceModule": sourceModule
		}
		if (restoreExternalFile) {
			postData.restoreExternalFiles = true;
		}
		app.helper.showProgress();
		app.request.post({data: postData}).then(
			function (error, data) {
				app.helper.hideProgress();
				if (error === null) {
					jQuery('.vt-notification').remove();
					var instance = new RecycleBin_List_Js();
					instance.recycleBinActionPostOperations(data);
					aDeferred.resolve(data);
				} else {
					app.event.trigger('post.save.failed', error);
					aDeferred.resolve(data);
				}
			}
		);
		return aDeferred.promise();
	},
	/**
	 * Function to restore a record
	 */
	restoreRecord: function (recordId) {
		var message = app.vtranslate('JS_LBL_RESTORE_RECORD_CONFIRMATION');
		app.helper.showConfirmationBox({'message': message}).then(
			function (e) {
				RecycleBin_List_Js.restoreAction(recordId, false);
			},
			function (error, err) {
			});
	}
}, {
	// Overiding the parent function
	registerDynamicListHeaders: function () {
	},
	getRecordSelectTrackerInstance: function () {
		if (RecycleBin_List_Js.recordSelectTrackerInstance === false) {
			RecycleBin_List_Js.recordSelectTrackerInstance = Vtiger_RecordSelectTracker_Js.getInstance();
		}
		return RecycleBin_List_Js.recordSelectTrackerInstance;
	},
	//Fix for empty Recycle bin
	//Change Button State ("Enable or Disable") 
	listViewPostOperation: function () {
		if (!jQuery('#isRecordsDeleted').val()) {
			jQuery(".clearRecycleBin").attr('disabled', 'disabled');
		} else {
			jQuery(".clearRecycleBin").removeAttr('disabled');
		}
	},
	getDefaultParams: function () {
		var pageNumber = jQuery('#pageNumber').val();
		var module = app.getModuleName();
		var parent = app.getParentModuleName();
		var orderBy = jQuery('#orderBy').val();
		var sortOrder = jQuery("#sortOrder").val();
		var params = {
			'module': module,
			'parent': parent,
			'page': pageNumber,
			'view': "List",
			'orderby': orderBy,
			'sortorder': sortOrder,
			'sourceModule': jQuery('#sourceModule').val()
		}
		return params;
	},
	/*
	 * Function to perform the operations after the Empty RecycleBin
	 */
	recycleBinActionPostOperations: function (data) {
		jQuery('#recordsCount').val('');
		jQuery('#totalPageCount').text('');
		var thisInstance = this;
		var listInstance = Vtiger_List_Js.getInstance();
		if (data) {
			var params = thisInstance.getDefaultParams();
			app.request.post({data: params}).then(function (error, data) {
				app.helper.hideModal();
				var listViewContainer = thisInstance.getListViewContainer();
				listViewContainer.html(data);
				vtUtils.applyFieldElementsView(listViewContainer.find('.searchRow'));
				jQuery('#deSelectAllMsg').trigger('click');
				thisInstance.listViewPostOperation();
				thisInstance.updatePagination();
				app.event.trigger('post.listViewFilter.click', listViewContainer);
				listInstance.clearList();
				listInstance.markSelectedIdsCheckboxes();
			});
		}
	},
	getRecordsCount: function () {
		var aDeferred = jQuery.Deferred();
		var count = '';
		var module = app.getModuleName();
		var sourceModule = jQuery('#sourceModule').val();
		var postData = {
			"module": module,
			"sourceModule": sourceModule,
			"view": "ListAjax",
			"mode": "getRecordsCount"
		}
		app.request.post({data: postData}).then(
			function (error, data) {
				jQuery("#recordsCount").val(data['count']);
				aDeferred.resolve(data);
			},
			function (error, err) {
			}
		);

		return aDeferred.promise();
	},
	/**
	 * Function to get Page Jump Params
	 */
	getPageJumpParams: function () {
		var module = app.getModuleName();
		var pageCountParams = {
			'module': module,
			'view': "ListAjax",
			'mode': "getPageCount",
			'sourceModule': jQuery('#sourceModule').val(),
			'search_params': JSON.stringify(this.getListSearchParams())
		}
		return pageCountParams;
	},
	/*
	 * Function to register the list view delete record click event
	 */
	registerDeleteRecordClickEvent: function () {
		var listViewContentDiv = this.getListViewContainer();
		listViewContentDiv.on('click', '.deleteRecordButton', function (e) {
			var elem = jQuery(e.currentTarget);
			var recordId = elem.closest('tr').data('id');
			RecycleBin_List_Js.deleteRecord(recordId);
			e.stopPropagation();
		});
	},
	/*
	 * Function to register the list view restore record click event
	 */
	registerRestoreRecordClickEvent: function () {
		var listViewContentDiv = this.getListViewContainer();
		listViewContentDiv.on('click', '.restoreRecordButton', function (e) {
			var elem = jQuery(e.currentTarget);
			var recordId = elem.closest('tr').data('id');
			RecycleBin_List_Js.restoreRecord(recordId);
			e.stopPropagation();
		});
	},
	registerRowDoubleClickEvent: function () {
		return;
	},
	disableListViewActions: function () {
		jQuery('.recordDependentListActions').find('button').attr('disabled', "disabled");
	},
	enableListViewActions: function () {
		jQuery('.recordDependentListActions').find('button').removeAttr('disabled');
	},
	registerEvents: function () {
		this._super();
		this.registerRestoreRecordClickEvent();
		app.helper.showVerticalScroll(jQuery('.list-menu-content'), {
			setHeight: 500,
			autoExpandScrollbar: true,
			scrollInertia: 200,
			autoHideScrollbar: true
		});

		// Added to overide default list view siderbar event
		jQuery('.list-menu-content').on('click', '.listViewFilter', function (e) {
			e.stopImmediatePropagation();
		});
	}
});