/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Vtiger_List_Js("EmailTemplates_List_Js", {
    massDeleteRecords: function (url, instance) {
        var listInstance = Vtiger_List_Js.getInstance();
        if (typeof instance != "undefined") {
            listInstance = instance;
        }
        var validationResult = listInstance.checkListRecordSelected();
        if (validationResult != true) {
            // Compute selected ids, excluded ids values, along with cvid value and pass as url parameters
            var selectedIds = listInstance.readSelectedIds(true);
            var excludedIds = listInstance.readExcludedIds(true);
            var cvId = listInstance.getCurrentCvId();
            var message = app.vtranslate('LBL_MASS_DELETE_CONFIRMATION');

            // warning message for Customer Login Details template
            if (jQuery.inArray("10", JSON.parse(selectedIds)) != -1) {
                var message = app.vtranslate('LBL_CUTOMER_LOGIN_DETAILS_TEMPLATE_DELETE_MESSAGE');
            }

            app.helper.showConfirmationBox({'message': message}).then(
                    function (e) {
                        var deleteURL = url + '&viewname=' + cvId + '&selected_ids=' + selectedIds + '&excluded_ids=' + excludedIds;
                        var listViewInstance = Vtiger_List_Js.getInstance();

                        if (app.getModuleName() == 'Documents') {
                            var defaultparams = listInstance.getDefaultParams();
                            deleteURL += '&folder_id=' + defaultparams['folder_id'] + '&folder_value=' + defaultparams['folder_value'];
                        }
                        deleteURL += "&search_params=" + JSON.stringify(listViewInstance.getListSearchParams());
                        app.helper.showProgress();
                        app.request.post({url: deleteURL}).then(
                                function () {
                                    app.helper.hideProgress();
                                    listInstance.clearList();
                                    listInstance.loadListViewRecords();
                                }
                        );
                    })
        } else {
            listInstance.noRecordSelectedAlert();
        }
    },
    deleteRecord: function (recordId) {
        var listInstance = Vtiger_List_Js.getInstance();
        var message = app.vtranslate('LBL_DELETE_CONFIRMATION');

        // warning message for Customer Login Details template
        if (recordId == "10") {
            var message = app.vtranslate('LBL_CUTOMER_LOGIN_DETAILS_TEMPLATE_DELETE_MESSAGE');
        }

        app.helper.showConfirmationBox({'message': message}).then(
                function (e) {
                    var module = app.getModuleName();
                    var postData = {
                        "module": module,
                        "action": "DeleteAjax",
                        "record": recordId,
                        "parent": app.getParentModuleName()
                    }
                    app.helper.showProgress();
                    app.request.post({data: postData}).then(
                            function (error, data) {
                                app.helper.hideProgress();
                                if (!error) {
                                    var orderBy = jQuery('#orderBy').val();
                                    var sortOrder = jQuery("#sortOrder").val();
                                    var urlParams = {
                                        "viewname": data.viewname,
                                        "orderby": orderBy,
                                        "sortorder": sortOrder
                                    }
                                    jQuery('#recordsCount').val('');
                                    jQuery('#totalPageCount').text('');
                                    listInstance.loadListViewRecords(urlParams).then(function () {
                                        listInstance.updatePagination();
                                    });
                                } else {
                                    app.helper.showErrorNotification({message: error});
                                }
                            },
                            function (error, err) {

                            }
                    );
                },
                function (error, err) {
                }
        );
    }

}, {
    
    registerRowDoubleClickEvent: function () {
        
	},
	
	addIndexComponent : function() {
		this.addModuleSpecificComponent('Index','Vtiger','Settings');
	},
	
    
    /**
     * Function to override function written in Vtiger List.js file to add extra parameter for
     * every page navigation click and sorting
     * @returns {ListAnonym$6.getDefaultParams.params}
     */
    getDefaultParams: function () {
        var container = this.getListViewContainer();
        var pageNumber = container.find('#pageNumber').val();
        var module = "EmailTemplates";
        var parent = app.getParentModuleName();
        var cvId = this.getCurrentCvId();
        var orderBy = container.find('[name="orderBy"]').val();
        var sortOrder = container.find('[name="sortOrder"]').val();
        var appName = container.find('#appName').val();
        var params = {
            'module': module,
            'parent': parent,
            'page': pageNumber,
            'view': "List",
            'viewname': cvId,
            'orderby': orderBy,
            'sortorder': sortOrder,
            'app': appName
        }
        params.search_params = JSON.stringify(this.getListSearchParams());
        params.tag_params = JSON.stringify(this.getListTagParams());
        params.nolistcache = (container.find('#noFilterCache').val() == 1) ? 1 : 0;
        params.starFilterMode = container.find('.starFilter li.active a').data('type');
        params.list_headers = container.find('[name="list_headers"]').val();
        params.tag = container.find('[name="tag"]').val();
        params.viewType = container.find('[name="viewType"]').val();
        return params;
    },
    registerAccordionClickEvent: function () {
        jQuery('.settingsgroup-accordion a[data-parent="#accordion"]').on('click', function (e) {
            var target = jQuery(e.currentTarget);
            var closestItag = target.find('i');

            if (closestItag.hasClass('fa-chevron-right')) {
                closestItag.removeClass('fa-chevron-right').addClass('fa-chevron-down');
            } else {
                closestItag.removeClass('fa-chevron-down').addClass('fa-chevron-right');
            }

            jQuery('.settingsgroup i').not(closestItag).removeClass('fa-chevron-down').addClass('fa-chevron-right');
        });
    },
    /*
     * Function to register the list view delete record click event
     */
    registerDeleteRecordClickEvent: function () {
        jQuery('#page').on('click', '.deleteRecordButton', function(e){
            var elem = jQuery(e.currentTarget);
            var originalDropDownMenu = elem.closest('.dropdown-menu').data('original-menu');
            var parent = app.helper.getDropDownmenuParent(originalDropDownMenu);
            var recordId = parent.closest('tr').data('id');
            EmailTemplates_List_Js.deleteRecord(recordId);
        });
    },
    registerViewType: function () {
        var thisInstance = this;
        var listViewContentDiv = this.getListViewContainer();
        listViewContentDiv.on('click', '.viewType', function (e) {
            var mode = jQuery(e.currentTarget).data('mode');
            //If template view is in thumbnail mode, delete icon should be hided
            if(mode == 'grid'){
                 jQuery('.fa-trash').parents('div.btn-group').addClass('hide');
            } else {
                jQuery('.fa-trash').parents('div.btn-group').removeClass('hide');
            }
                
            listViewContentDiv.find('input[name="viewType"]').val(mode);
            var listViewInstance = Vtiger_List_Js.getInstance();
            var urlParams = thisInstance.getDefaultParams();
            thisInstance.loadListViewRecords(urlParams).then(function () {
                listViewInstance.updatePagination();
            });
        });
    },
    /**
     * Function to show on mouseover and to hide on mouseleave 
     */
    registerThumbnailHoverActionEvent: function () {
        jQuery('#listViewContent').on('mouseover', '.thumbnail, .templateActions', function (e) {
            jQuery(e.currentTarget).find('div').eq(1).removeClass('hide').addClass('templateActions');
        });
        jQuery('#listViewContent').on('mouseleave', '.thumbnail, .templateActions', function (e) {
            jQuery(e.currentTarget).find('div').eq(1).removeClass('templateActions').addClass('hide');
        });
    },
    
    /**
     * Function to create the template or edit the existing template
     */
    registerTemplateEditEvent: function () {
        jQuery('#listViewContent').on('click', '.imageDiv img,.editTemplate', function (e) {
            var templateId = jQuery(e.currentTarget).data('value');
            var redirectUrl = 'index.php?module=EmailTemplates&view=Edit&record='+templateId;
            window.location.href = redirectUrl;
        });
    },
    
    /**
     * Function will duplicate the existing template
     */
    registerTemplateDuplicationEvent: function () {
        jQuery('#listViewContent').on('click', '.templateDuplication', function (e) {
            var templateId = jQuery(e.currentTarget).attr('data-value');
            var redirectUrl = 'index.php?module=EmailTemplates&view=Edit&record='+templateId+'&isDuplicate=true';
            window.location.href = redirectUrl;
        });
    },
    
     loadListViewRecords : function(urlParams) {
        var self = this;
        var aDeferred = jQuery.Deferred();
        var defParams = this.getDefaultParams();
        if(typeof urlParams == "undefined") {
            urlParams = {};
        }
        if(typeof urlParams.search_params == "undefined") {
            urlParams.search_params = JSON.stringify(this.getListSearchParams(false));
        }
        urlParams = jQuery.extend(defParams, urlParams);
        app.helper.showProgress();
		
        app.request.post({data:urlParams}).then(function(err, res){
            aDeferred.resolve(res);
            self.placeListContents(res);
            app.event.trigger('post.listViewFilter.click', jQuery('.searchRow'));
            app.helper.hideProgress();
            self.markSelectedIdsCheckboxes();
            self.registerDynamicListHeaders();
            self.registerDeleteRecordClickEvent();
            self.registerDynamicDropdownPosition();
            self.registerDropdownPosition();//for every ajax request more-drop down in listview
        });
        return aDeferred.promise();
    },
    
    /**
     * Function to preview existing email template
     * @returns {undefined}
     */
    registerPreviewTemplateEvent: function(){
        var thisInstance = this;
        jQuery('#listViewContent').on('click','.previewTemplate',function(e){
            var record = jQuery(e.currentTarget).data('value');
            var params = {
                'module': 'EmailTemplates',
                'view'  : "ListAjax",
                "mode"  : "previewTemplate",
                "record": record
            };
            app.helper.showProgress();
            app.request.post({data: params}).then(function (error, data) {
                app.helper.loadPageContentOverlay(data).then(function(){
                    thisInstance.showTemplateContent(record);
                });
            });
        });  
    },
    
    /**
     * Function to show template content
     * @param {type} record
     * @returns {undefined}
     */
    showTemplateContent: function(record){
        var params={
            "module" : "EmailTemplates",
            "action" : "ShowTemplateContent",
            "mode"   : "getContent",
            "record" : record
        };
        app.request.post({data: params}).then(function(error, data){
            app.helper.hideProgress();
            var templateContent = data.content;
            jQuery('#TemplateIFrame').contents().find('html').html(templateContent);
        });
    },
    
    registerEvents: function () {
        this._super();
        this.registerAccordionClickEvent();
        this.registerViewType();
        this.registerThumbnailHoverActionEvent();
        this.registerTemplateDuplicationEvent();
        this.registerTemplateEditEvent();
        this.registerPreviewTemplateEvent();
        if(window.hasOwnProperty('Settings_Vtiger_Index_Js')){
            var instance = new Settings_Vtiger_Index_Js(); 
            instance.registerBasicSettingsEvents();
        }
        
    }
});
