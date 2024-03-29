/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Settings_Vtiger_List_Js("Settings_LoginHistory_List_Js",{},{
    
    
	registerFilterChangeEvent : function() {
		var thisInstance = this;
		jQuery('#usersFilter').on('change',function(e){
			jQuery('#pageNumber').val("1");
			jQuery('#pageToJump').val('1');
			jQuery('#orderBy').val('');
			jQuery("#sortOrder").val('');
			var value = jQuery(e.currentTarget).val();
			
			var params = {
				module : app.getModuleName(),
				parent : app.getParentModuleName(),
				'search_key' : 'user_name',
				'search_value' : value,
				'page' : 1,
                'user_name' :this.options[this.selectedIndex].getAttribute("name")
			};
			
			//Make total number of pages as empty
			jQuery('#totalPageCount').text("");
			thisInstance.loadListViewRecords(params, value);
		});
	},
	
	getDefaultParams : function() {
		var pageNumber = jQuery('#pageNumber').val();
		var module = app.getModuleName();
		var parent = app.getParentModuleName();
		var urlParams = this.getParamsFromURL(location.href);
		var params = {
			'module': module,
			'parent' : parent,
			'page' : pageNumber,
			'view' : "List",
			'user_name' : jQuery('select[id=usersFilter] option:selected').attr('name'),
			'search_key' : urlParams['search_key'],
			'search_value' : urlParams['search_value']
		};

		return params;
	},
	
    loadListViewRecords : function(listParams, selectedValue) {
		var thisInstance = this;
        var aDeferred = jQuery.Deferred();
		var listViewContainer = thisInstance.getListViewContainer();
        var defaultListParams = thisInstance.getDefaultParams();
        var newListParams = jQuery.extend(defaultListParams, listParams);
        app.helper.showProgress();
        
		app.request.get({'data' : newListParams}).then(
			function(err, data){
				app.helper.hideProgress();
				if(err === null) {
					thisInstance.placeListContents(data);
					aDeferred.resolve(data);
					jQuery('.usersListDiv option[value="'+selectedValue+'"]').prop('selected', true);
					vtUtils.showSelect2ElementView(jQuery('#usersFilter'));
					thisInstance.registerFilterChangeEvent();
					thisInstance.updatePagination();

					thisInstance.registerUserToggleEvent();
					thisInstance.registerExportData();
				}else {
					app.helper.showErrorNotification({'message':err.message});
				}
			}
		);
		return aDeferred.promise();
	},
	
	/**
	 * Function to get Page Jump Params
	 */
	getPageJumpParams : function(){
		var module = app.getModuleName();
		var parent = app.getParentModuleName();
		var urlParams = this.getParamsFromURL(location.href);
		var pageJumpParams = {
			'module' : module,
			'parent' : parent,
			'action' : "ListAjax",
			'mode' : "getPageCount",
			'search_key' : urlParams['search_key'],
			'search_value' : urlParams['search_value']
		};
		return pageJumpParams;
	},
	
	pageJumpOnSubmit : function(element) {
		var thisInstance = this;
		
		var currentPageElement = jQuery('#pageNumber');
		var currentPageNumber = parseInt(currentPageElement.val());
		var newPageNumber = parseInt(jQuery('#pageToJump').val());
		var totalPages = parseInt(jQuery('#totalPageCount').text());

		if(newPageNumber > totalPages){
			var message = app.vtranslate('JS_PAGE_NOT_EXIST');
			app.helper.showErrorNotification({'message':message})
			return;
		}

		if(newPageNumber === currentPageNumber){
			var message = app.vtranslate('JS_YOU_ARE_IN_PAGE_NUMBER')+" "+newPageNumber;
			app.helper.showAlertNotification({'message': message});
			return;
		}
		
		var urlParams = thisInstance.getPagingParams();
		var value = jQuery('#usersFilter').val();
		urlParams['page'] = newPageNumber;
		thisInstance.loadListViewRecords(urlParams, value).then(function(data){
			element.closest('.btn-group ').removeClass('open');
		});
	},

	getParamsFromURL: function(url) {
		var urlParams = url.slice(url.indexOf('?') + 1).split('&');
		var params = {};
		for (var i = 0; i < urlParams.length; i++) {
			var param = urlParams[i].split('=');
			params[param[0]] = param[1];
		}
		return params;
	},
	
	initializePaginationEvents : function() {
		var thisInstance = this;
		var paginationObj = this.getComponentInstance('Vtiger_Pagination_Js');
		var listViewContainer = thisInstance.getListViewContainer();
		paginationObj.initialize(listViewContainer);
		
		app.event.on(paginationObj.nextPageButtonClickEventName, function(){
			var pageLimit = listViewContainer.find('#pageLimit').val();
            var noOfEntries = listViewContainer.find('#noOfEntries').val();
            var nextPageExist = listViewContainer.find('#nextPageExist').val();
			var pageNumber = listViewContainer.find('#pageNumber').val();
			var nextPageNumber = parseInt(parseFloat(pageNumber)) + 1;
			
            if(noOfEntries === pageLimit && nextPageExist){
				var urlParams = {};
				listViewContainer.find("#pageNumber").val(nextPageNumber);
				var value = jQuery('#usersFilter').val();
				thisInstance.loadListViewRecords(urlParams, value);
			}
		});
		
		app.event.on(paginationObj.previousPageButtonClickEventName, function(){
			var pageNumber = listViewContainer.find('#pageNumber').val();
			var previousPageNumber = parseInt(parseFloat(pageNumber)) - 1;
			
			if(pageNumber > 1) {
				var urlParams = {};
				listViewContainer.find('#pageNumber').val(previousPageNumber);
				var value = jQuery('#usersFilter').val();
				thisInstance.loadListViewRecords(urlParams, value);
			}
		});
		
		app.event.on(paginationObj.pageJumpButtonClickEventName, function(event, currentEle){
			thisInstance.pageJump();
		});
		
		app.event.on(paginationObj.totalNumOfRecordsButtonClickEventName, function(event, currentEle){
			thisInstance.totalNumOfRecords(currentEle);
		});
		
		app.event.on(paginationObj.pageJumpSubmitButtonClickEvent, function(event, currentEle){
			thisInstance.pageJumpOnSubmit(currentEle);
		});
	},
	
	 registerUserToggleEvent : function() {
		jQuery('#frUsers, #portalUsers').on('click', function(e) {
			var currentEle = jQuery(e.currentTarget);
			//If it is already selected then you dont need to load again
			if(currentEle.hasClass('btn-primary')) {
				return;
			}

			app.helper.showProgress();
			if(currentEle.attr('id') === 'frUsers') {
				jQuery('#portalUsers').removeClass('btn-primary');
			}else {
				jQuery('#frUsers').removeClass('btn-primary');
			}
			currentEle.addClass('btn-primary');

			var url = 'index.php?module=LoginHistory&parent=Settings&view=List&search_key=is_portal&operator=e&search_value='+currentEle.data('searchvalue');
			window.location.href = url;
		});
	},

	registerExportData : function() {
		var thisInstance = this;
		jQuery('#exportData').on('click', function(e){
			var urlParams = thisInstance.getParamsFromURL(location.href);

			urlParams['module'] = 'LoginHistory';
			urlParams['parent'] = 'Settings';
			urlParams['action'] = 'ExportData';
			urlParams['view'] = '';
			urlParams['source_module'] = 'LoginHistory';

			var requestString = jQuery.param(urlParams);
	
			var url = 'index.php?' + requestString;
			window.location.href = url;
		});
	},

	registerEvents : function() {
        this.initializePaginationEvents();
        this.registerFilterChangeEvent();
		this.registerUserToggleEvent();
		this.registerExportData();

	}
    
});