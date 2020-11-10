/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
if (typeof (Vtiger_Import_Js) == 'undefined') {

    Vtiger_Import_Js = {
        triggerImportAction: function(url) {
            var params = Vtiger_Import_Js.getDefaultParams();
            //Only for contacts and Calendar show landing page.
            if(params.module != 'Contacts' && params.module != 'Calendar') {
                Vtiger_Import_Js.showImportActionStepOne();
                return false;
            }
            params['mode'] = 'landing';
            app.helper.showProgress();
            app.request.get({data: params}).then(function(err, data) {
                app.helper.loadPageContentOverlay(data).then(function() {
                    app.helper.hideProgress();
                    Vtiger_Import_Js.registerEvents();
                });
            });
            return false;
        },
        bactToStep1: function() {
            jQuery('#step2').removeClass('active');
            jQuery('#step1').addClass('active');
            jQuery('#uploadFileContainer').addClass('show');
            jQuery('#importStep2Conatiner').removeClass('show');
            jQuery('#importStep2Conatiner').addClass('hide');

            jQuery('#importStepOneButtonsDiv').removeClass('hide');
            jQuery('#importStepOneButtonsDiv').addClass('show');

            jQuery('#importStepTwoButtonsDiv').removeClass('show');
            jQuery('#importStepTwoButtonsDiv').addClass('hide');

            return false;
        },
        importActionStep2: function() {
			if(Vtiger_Import_Js.validateFilePath()){
				jQuery('#uploadFileContainer').removeClass('show');
				jQuery('#uploadFileContainer').addClass('hide');

				jQuery('#step1').removeClass('active');
				jQuery('#step2').addClass('active');

				jQuery('#importStep2Conatiner').addClass('show');

				jQuery('#importStepTwoButtonsDiv').removeClass('hide');
				jQuery('#importStepTwoButtonsDiv').addClass('show');

				jQuery('#importStepOneButtonsDiv').removeClass('show');
				jQuery('#importStepOneButtonsDiv').addClass('hide');
			}
			return false;
        },
        uploadAndParse: function(auto_merge) {
            if (Vtiger_Import_Js.validateFilePath() && Vtiger_Import_Js.validateMergeCriteria(auto_merge)) {
                jQuery("#auto_merge").val(auto_merge);
                var form = jQuery("form[name='importBasic']");
                var data = new FormData(form[0]);
                var postParams = {
                    data: data,
                    contentType: false,
                    processData: false
                };
                app.helper.showProgress();
                app.request.post(postParams).then(function(err, response) {
                    app.helper.loadPageContentOverlay(response);
					Vtiger_Import_Js.loadDefaultValueWidgetForMappedFields();
                    app.helper.hideProgress();
                });
            }
            return false;
        },
        backToLandingPage: function() {
            Vtiger_Import_Js.triggerImportAction();
            return false;
        },
        sanitizeAndSubmit: function() {
            if (Vtiger_Import_Js.sanitizeFieldMapping() && Vtiger_Import_Js.validateCustomMap()) {
                var formData = jQuery("form[name='importAdvanced']").serialize();
                app.helper.showProgress();
                app.request.post({data: formData}).then(function(err, response) {
                    app.helper.loadPageContentOverlay(response);
                    app.helper.hideProgress();
                    if(!err){
                        if (jQuery('#scheduleImportStatus').length > 0) {
                            app.event.one('post.overlayPageContent.hide', function(container) {
                                clearTimeout(Vtiger_Import_Js.timer);
                                Vtiger_Import_Js.isReloadStatusPageStopped = true;
                            });
                            Vtiger_Import_Js.isReloadStatusPageStopped = false;
                            Vtiger_Import_Js.timer = setTimeout(Vtiger_Import_Js.scheduledImportRunning, 5000);
                        } else {
                            app.helper.showSuccessNotification({message:'Import Completed.'});
                        }
                    }
                });
            }
            return false;
        },
        sanitizeFieldMapping: function() {
            var fieldsList = jQuery('.fieldIdentifier');

            var mappedFields = {};
            var errorMessage;
            var mappedDefaultValues = {};

            for (var i = 0; i < fieldsList.length; ++i) {
                var fieldElement = jQuery(fieldsList.get(i));
                var rowId = jQuery('[name=row_counter]', fieldElement).get(0).value;

                var selectedFieldElement = jQuery('select option:selected', fieldElement);
                var selectedFieldName = selectedFieldElement.val();
                var selectedFieldDefaultValueElement = jQuery('#' + selectedFieldName + '_defaultvalue', fieldElement);
                var defaultValue = '';
                if (selectedFieldDefaultValueElement.attr('type') == 'checkbox') {
                    defaultValue = selectedFieldDefaultValueElement.is(':checked');
                } else {
                    defaultValue = selectedFieldDefaultValueElement.val();
                }
                if (selectedFieldName != '') {
                    if (selectedFieldName in mappedFields) {
                        errorMessage = app.vtranslate('JS_FIELD_MAPPED_MORE_THAN_ONCE') + " " + selectedFieldElement.data('label');
                        app.helper.showErrorNotification({'message': errorMessage});
                        return false;
                    }
                    mappedFields[selectedFieldName] = rowId - 1;
                    if (defaultValue != '') {
                        mappedDefaultValues[selectedFieldName] = defaultValue;
                    }
                }
            }

            var mandatoryFields = JSON.parse(jQuery('#mandatory_fields').val());
            var moduleName = app.getModuleName();
            if (moduleName == 'PurchaseOrder' || moduleName == 'Invoice' || moduleName == 'Quotes' || moduleName == 'SalesOrder') {
                mandatoryFields.hdnTaxType = app.vtranslate('Tax Type');
            }
            var missingMandatoryFields = [];
            for (var mandatoryFieldName in mandatoryFields) {
                if (mandatoryFieldName in mappedFields) {
                    continue;
                } else {
                    missingMandatoryFields.push('"' + mandatoryFields[mandatoryFieldName] + '"');
                }
            }
            if (missingMandatoryFields.length > 0) {
                errorMessage = app.vtranslate('JS_MAP_MANDATORY_FIELDS') + missingMandatoryFields.join(',');
                app.helper.showErrorNotification({'message': errorMessage});
                return false;
            }
            jQuery('#field_mapping').val(JSON.stringify(mappedFields));
            jQuery('#default_values').val(JSON.stringify(mappedDefaultValues));
            return true;
        },
        validateCustomMap: function() {
            var errorMessage;
            var saveMap = jQuery('#save_map').is(':checked');
            if (saveMap) {
                var mapName = jQuery('#save_map_as').val();
                if (jQuery.trim(mapName) == '') {
                    errorMessage = app.vtranslate('JS_MAP_NAME_CAN_NOT_BE_EMPTY');
                    app.helper.showErrorNotification({'message': errorMessage});
                    return false;
                }
                var mapOptions = jQuery('#saved_maps option');
                for (var i = 0; i < mapOptions.length; ++i) {
                    var mapOption = jQuery(mapOptions.get(i));
                    if (mapOption.html() == mapName) {
                        errorMessage = app.vtranslate('JS_MAP_NAME_ALREADY_EXISTS');
                        app.helper.showErrorNotification({'message': errorMessage});
                        return false;
                    }
                }
            }
            return true;
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
        undoImport: function(url) {
            var params = Vtiger_Import_Js.getParamsFromURL(url);
            Vtiger_Import_Js.showOverLayModal(params);
        },
        loadSavedMap: function() {
            var selectedMapElement = jQuery('#saved_maps option:selected');
            var mapId = selectedMapElement.attr('id');
            var fieldsList = jQuery('.fieldIdentifier');
            var deleteMapContainer = jQuery('#delete_map_container');
            fieldsList.each(function(i, element) {
                var fieldElement = jQuery(element);
                jQuery('[name=mapped_fields]', fieldElement).val('');
            });
            if (mapId == -1) {
                deleteMapContainer.hide();
                return;
            }
            deleteMapContainer.show();
            var mappingString = selectedMapElement.val()
            if (mappingString == '')
                return;
            var mappingPairs = mappingString.split('&');
            var mapping = {};
            for (var i = 0; i < mappingPairs.length; ++i) {
                var mappingPair = mappingPairs[i].split('=');
                var header = mappingPair[0];
                header = header.replace(/\/eq\//g, '=');
                header = header.replace(/\/amp\//g, '&amp;');
				mapping[header] = mappingPair[1];
				mapping[i] = mappingPair[1]; /* To make Row based match when there is no header */
            }
            fieldsList.each(function(i, element) {
                var fieldElement = jQuery(element);
                var mappedFields = jQuery('[name=mapped_fields]', fieldElement);
                var rowId = jQuery('[name=row_counter]', fieldElement).get(0).value;
                var headerNameElement = jQuery('[name=header_name]', fieldElement).get(0);
                var headerName = jQuery(headerNameElement).html();
                if (headerName in mapping) {
                    mappedFields.select2("val", mapping[headerName]);
				} else if (rowId-1 in mapping) { /* Row based match when there is no header - but saved map is loaded. */
                	mappedFields.select2("val", mapping[rowId-1]);
				}
                Vtiger_Import_Js.loadDefaultValueWidget(fieldElement.attr('id'));
            });
        },
        deleteMap: function(module) {
            if (confirm(app.vtranslate('LBL_DELETE_CONFIRMATION'))) {
                var selectedMapElement = jQuery('#saved_maps option:selected');
                var mapId = selectedMapElement.attr('id');

                var postData = {
                    "module": module,
                    "view": 'Import',
                    "mode": 'deleteMap',
                    "mapid": mapId
                }

                app.request.post({'data': postData}).then(
                        function(err, data) {
                            jQuery('#savedMapsContainer').html(data);
                            vtUtils.showSelect2ElementView(jQuery('#saved_maps'));
                        });
            }
        },
        validateMergeCriteria: function(auto_merge) {
			if (auto_merge == 1) {
				var selectedOptions = jQuery('#selected_merge_fields option');
				if (selectedOptions.length == 0) {
					var errorMessage = app.vtranslate('JS_PLEASE_SELECT_ONE_FIELD_FOR_MERGE');
					app.helper.showErrorNotification({message: errorMessage});
					return false;
				}
				Vtiger_Import_Js.convertOptionsToJSONArray('#selected_merge_fields', '#merge_fields');
			}
            return true;
        },
        //TODO move to a common file
        convertOptionsToJSONArray: function(objName, targetObjName) {
            var obj = jQuery(objName);
            var arr = [];
            if (typeof (obj) != 'undefined' && obj[0] != '') {
                for (i = 0; i < obj[0].length; ++i) {
                    arr.push(obj[0].options[i].value);
                }
            }
            if (targetObjName != 'undefined') {
                var targetObj = $(targetObjName);
                if (typeof (targetObj) != 'undefined')
                    targetObj.val(JSON.stringify(arr));
            }
            return arr;
        },
        validateFilePath: function() {
            var importFile = jQuery('#import_file');
            var fileFormats = importFile.data('fileFormats');
            var filePath = importFile.val();
            if (jQuery.trim(filePath) == '') {
                var errorMessage = app.vtranslate('JS_IMPORT_FILE_CAN_NOT_BE_EMPTY');
                app.helper.showErrorNotification({message: errorMessage});
                importFile.focus();
                return false;
            }
            if (!Vtiger_Import_Js.uploadFilter("import_file", fileFormats)) {
                return false;
            }
            if (!Vtiger_Import_Js.uploadFileSize("import_file")) {
                return false;
            }
            return true;
        },
        showPopup: function(url) {
            var params = Vtiger_Import_Js.getParamsFromURL(url);
            var popupInstance = Vtiger_Popup_Js.getInstance();
            popupInstance.showPopup(params);
            return false;
        },
        showLastImportedRecords: function(url) {
            this.showPopup(url);
        },
        showSkippedRecords: function(url) {
            this.showPopup(url);
        },
        showFailedImportRecords: function(url) {
            this.showPopup(url);
        },
        loadDefaultValueWidget: function(rowIdentifierId) {
            var affectedRow = jQuery('#' + rowIdentifierId);
            if (typeof affectedRow == 'undefined' || affectedRow == null)
                return;
            var selectedFieldElement = jQuery('[name=mapped_fields]', affectedRow).get(0);
            var selectedFieldName = jQuery(selectedFieldElement).val();
            var defaultValueContainer = jQuery(jQuery('[name=default_value_container]', affectedRow).get(0));
            var allDefaultValuesContainer = jQuery('#defaultValuesElementsContainer');
            if (defaultValueContainer.children.length > 0) {
                var copyOfDefaultValueWidget = jQuery(':first', defaultValueContainer).detach();
                copyOfDefaultValueWidget.appendTo(allDefaultValuesContainer);
            }
            var selectedFieldDefValueContainer = jQuery('#' + selectedFieldName + '_defaultvalue_container', allDefaultValuesContainer);
            var defaultValueWidget = selectedFieldDefValueContainer.detach();
            defaultValueWidget.appendTo(defaultValueContainer);
        },
        loadDefaultValueWidgetForMappedFields: function() {
            var fieldsList = jQuery('.fieldIdentifier');
            fieldsList.each(function(i, element) {
                var fieldElement = jQuery(element);
                var mappedFieldName = jQuery('[name=mapped_fields]', fieldElement).val();
                if (mappedFieldName != '') {
                    Vtiger_Import_Js.loadDefaultValueWidget(fieldElement.attr('id'));
                }
            });

        },
        //TODO: move to a common file
        copySelectedOptions: function(source, destination) {

            var srcObj = jQuery(source);
            var destObj = jQuery(destination);

            if (typeof (srcObj) == 'undefined' || typeof (destObj) == 'undefined')
                return;

            for (i = 0; i < srcObj[0].length; i++) {
                if (srcObj[0].options[i].selected == true) {
                    var rowFound = false;
                    var existingObj = null;
                    for (j = 0; j < destObj[0].length; j++) {
                        if (destObj[0].options[j].value == srcObj[0].options[i].value) {
                            rowFound = true;
                            existingObj = destObj[0].options[j];
                            break;
                        }
                    }

                    if (rowFound != true) {
                        var opt = $('<option selected>');
                        opt.attr('value', srcObj[0].options[i].value);
                        opt.text(srcObj[0].options[i].text);
                        jQuery(destObj[0]).append(opt);
                        srcObj[0].options[i].selected = false;
                        rowFound = false;
                    } else {
                        if (existingObj != null)
                            existingObj.selected = true;
                    }
                }
            }
            return false;
        },
        //TODO move to a common file
        removeSelectedOptions: function(objName) {
            var obj = jQuery(objName);
            if (obj == null || typeof (obj) == 'undefined')
                return;

            for (i = obj[0].options.length - 1; i >= 0; i--) {
                if (obj[0].options[i].selected == true) {
                    obj[0].options[i] = null;
                }
            }
            return false;
        },
        checkFileType: function(e) {
            var filePath = jQuery('#import_file').val();
            if (filePath != '') {
                var fileExtension = filePath.split('.').pop();
                jQuery('#type').val(fileExtension);
                var fileName = e['target']['files'][0]['name'];
                jQuery('#importFileDetails').text(fileName);
                Vtiger_Import_Js.handleFileTypeChange();
            } else {
                jQuery('#importFileDetails').text('');
            }
        },
        handleFileTypeChange: function() {
            var fileType = jQuery('#type').val();
            var delimiterContainer = jQuery('#delimiter_container');
            var hasHeaderContainer = jQuery('#has_header_container');
            if (fileType != 'csv') {
                delimiterContainer.hide();
                hasHeaderContainer.hide();
            } else {
                delimiterContainer.show();
                hasHeaderContainer.show();
            }
        },
        uploadFilter: function(elementId, allowedExtensions) {
            var obj = jQuery('#' + elementId);
            if (obj) {
                var filePath = obj.val();
                var fileParts = filePath.toLowerCase().split('.');
                var fileType = fileParts[fileParts.length - 1];
                var validExtensions = allowedExtensions.toLowerCase().split('|');

                if (validExtensions.indexOf(fileType) < 0) {
                    var errorMessage = app.vtranslate('JS_SELECT_FILE_EXTENSION') + '\n' + validExtensions;
                    app.helper.showErrorNotification({message: errorMessage});
                    obj.focus();
                    return false;
                }
            }
            return true;
        },
        uploadFileSize: function(elementId) {
            var element = jQuery('#' + elementId);
            var importMaxUploadSize = element.closest('td').data('importUploadSize');
            var importMaxUploadSizeInMb = element.closest('td').data('importUploadSizeMb');
            var uploadedFileSize = element.get(0).files[0].size;
            if (uploadedFileSize > importMaxUploadSize) {
                var errorMessage = app.vtranslate('JS_UPLOADED_FILE_SIZE_EXCEEDS') + " " + importMaxUploadSizeInMb + " MB." + app.vtranslate('JS_PLEASE_SPLIT_FILE_AND_IMPORT_AGAIN');
                app.helper.showErrorNotification({message: errorMessage});
                return false;
            }
            return true;
        },
        showOverLayModal: function(params) {
            app.helper.showProgress();
            app.request.get({data: params}).then(function(err, data) {
                app.helper.loadPageContentOverlay(data);
                app.helper.hideProgress();
            });
        },

		timer : 0,
		isReloadStatusPageStopped : false,
        scheduledImportRunning: function() {
			var form = jQuery("#importStatusForm");
			var data = new FormData(form[0]);
			var postParams = {
				data: data,
				contentType: false,
				processData: false
			};
			app.request.post(postParams).then(function(err, response) {
				if(!Vtiger_Import_Js.isReloadStatusPageStopped) {
					app.helper.loadPageContentOverlay(response);
					if (jQuery('#scheduleImportStatus').length > 0) {
						if (!Vtiger_Import_Js.isReloadStatusPageStopped) {
							Vtiger_Import_Js.timer = setTimeout(Vtiger_Import_Js.scheduledImportRunning, 50000);
						}
					}
				}
			});
        },

        googleImportHandler : function() {
            var params = {
                module: 'Google',
                view: 'Setting',
                sourcemodule: app.getModuleName(),
                mode: 'googleImport'
            };
            app.helper.showProgress();
            app.request.get({data: params}).then(function(err, data) {
                app.helper.hideProgress();
                app.helper.hidePageContentOverlay().then(function(){
                    app.helper.loadPageContentOverlay(data).then(function(){
                        var container = jQuery('.googleSettings');
                        var googleSettingInstance = new Google_Settings_Js();
                        googleSettingInstance.registerSettingsEventsForContacts(container);
						
                        Vtiger_Import_Js.registerAuthorizeButton(container);
                        Vtiger_Import_Js.registerSyncNowButton(container, googleSettingInstance);
                    });    
                });
            });
        },
        
        registerImportEvents: function() {
            var importContainer = jQuery('#landingPageDiv');
            importContainer.on('click', '#csvImport', function(e) {
                Vtiger_Import_Js.showImportActionStepOne();
            });

            importContainer.on('click', '#vcfImport', function(e) {
                Vtiger_Import_Js.showImportActionStepOne('vcf');
            });

			importContainer.on('click', '#icsImport', function(e) {
                Vtiger_Import_Js.showImportActionStepOne('ics');
            });
            
            importContainer.on('click', '#googleImport', function(e) {
                Vtiger_Import_Js.googleImportHandler(e);
            });
        },
        registerAuthorizeButton: function(container) {
            container.on('click', '#authorizeButton', function(e) {
                var element = jQuery(e.currentTarget);
                var url = element.data('url');
                var win = window.open(url, '', 'height=600,width=600,channelmode=1');
                //http://stackoverflow.com/questions/1777864/how-to-run-function-of-parent-window-when-child-window-closes 
                window.sync = function() {
                    Vtiger_Import_Js.googleImportHandler();
                };
                window.startSync = function() {};
                win.onunload = function() {};
            });
        },
        registerSyncNowButton: function(container, googleSettingInstance) {
            container.find('#saveSettingsAndImport').on('click', function() {
                googleSettingInstance.validateFieldMappings(container).then(function() {
                    var form = jQuery("form[name='contactsyncsettings']");
                    var fieldMapping = googleSettingInstance.packFieldmappingsForSubmit(container);
                    form.find('#user_field_mapping').val(fieldMapping);
                    var serializedFormData = form.serialize();
                    app.helper.showProgress();
                    app.request.post({data: serializedFormData}).then(function(err, response) {
                        app.helper.hideProgress();
                        app.helper.hideModal();
                        if(err){
                            app.helper.showErrorNotification();
                        }
                        else{
                            var params = {
                                module:'Contacts',
                                view:'Extension',
                                extensionModule:'Google',
                                extensionView:'Index',
                                viewType:'modal'
                            };
                            app.helper.showProgress();
                            app.helper.hidePageContentOverlay().then(function(){
                                app.request.get({data:params}).then(function(err, data){
                                app.helper.hideProgress();
                                    app.helper.loadPageContentOverlay(data).then(function(overlayPageContent){
                                        var overlayContainer = overlayPageContent.find('.data');
                                        var extensionCommonJs = new Vtiger_ExtensionCommon_Js;
                                        extensionCommonJs.getListUrlParams = function() {
                                            var params = {
                                                'module' : app.getModuleName(),
                                                'view' : 'Extension',
                                                'extensionModule' : 'Google',
                                                'extensionView' : 'Index',
                                                'mode' : 'showLogs',
                                                'viewType' : 'modal'
                                            }

                                            return params;
                                        };
                                        extensionCommonJs.registerPaginationEvents(overlayContainer);
										extensionCommonJs.registerLogDetailClickEvent(overlayContainer);
                                    });
                                });
                            });
                        }
                    });
                });

            });
        },
        
        clearSheduledImportData: function() {
            var params = {};
            params['module'] = app.getModuleName();
            params['view'] = 'Import';
            params['mode'] =  'clearCorruptedData';
            Vtiger_Import_Js.showOverLayModal(params);
        },
        cancelImport: function(url) {
            var urlParams = url.slice(url.indexOf('?') + 1).split('&');
            var params = {};
            for (var i = 0; i < urlParams.length; i++) {
                var param = urlParams[i].split('=');
                params[param[0]] = param[1];
            }
            Vtiger_Import_Js.showOverLayModal(params);


        },
        scheduleImport: function(url) {
            var urlParams = url.slice(url.indexOf('?') + 1).split('&');
            var params = {};
            for (var i = 0; i < urlParams.length; i++) {
                var param = urlParams[i].split('=');
                params[param[0]] = param[1];
            }
            Vtiger_Import_Js.showOverLayModal(params);
        },
        showImportActionStepOne: function(format) {
            var params = Vtiger_Import_Js.getDefaultParams();
            params['mode'] = 'importBasicStep';
            if (format == 'vcf') {
                params['fileFormat'] = format;
            } else if (format == 'ics') {
				params['fileFormat'] = format;
			}
            app.helper.showProgress();
            app.request.get({data: params}).then(function(err, data) {
                app.helper.loadPageContentOverlay(data);
                app.helper.hideProgress();
				if (jQuery('#scheduleImportStatus').length > 0) {
					app.event.one('post.overlayPageContent.hide', function(container) {
						clearTimeout(Vtiger_Import_Js.timer);
						Vtiger_Import_Js.isReloadStatusPageStopped = true;
					});

					Vtiger_Import_Js.isReloadStatusPageStopped = false;
					Vtiger_Import_Js.timer = setTimeout(Vtiger_Import_Js.scheduledImportRunning, 5000);
				}
            });
        },
        getDefaultParams: function() {
            var module = window.app.getModuleName();
            var url = "index.php?module=" + module + "&view=Import";
            var urlParams = url.slice(url.indexOf('?') + 1).split('&');

            var params = {};
            for (var i = 0; i < urlParams.length; i++) {
                var param = urlParams[i].split('=');
                params[param[0]] = param[1];
            }
            return params;
        },
        finishUndoOperation: function(){
            Vtiger_Import_Js.loadListRecords();
        },
        loadListRecords : function(){
			var listInstance;
			if(app.getModuleName() == 'Users') {
				listInstance = new Settings_Users_List_Js();
			}else { 
				listInstance = new Vtiger_List_Js();
			}
			
			var params = {'page': '1'};
			listInstance.loadListViewRecords(params);
        },
        
        registerEvents: function() {
            Vtiger_Import_Js.registerImportEvents();
        }
    }
    jQuery(document).ready(function() {
        Vtiger_Import_Js.loadDefaultValueWidgetForMappedFields();
    });
}

