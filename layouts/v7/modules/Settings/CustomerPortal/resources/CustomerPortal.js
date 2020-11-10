/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
Vtiger.Class('Settings_Customer_Portal_Js', {}, {
	//This will store the CustomerPortal Form
	customerPortalForm: false,
	//store the class name for customer portal module row
	rowClass: 'portalModuleRow',
	init: function () {
		this.addComponents();
	},
	addComponents: function () {
		this.addModuleSpecificComponent('Index', 'Vtiger', app.getParentModuleName());
	},
	/**
	 * Function to get the customerPortal form
	 */
	getForm: function () {
		if (this.customerPortalForm == false) {
			this.customerPortalForm = jQuery('#customerPortalForm');
		}
		return this.customerPortalForm;
	},
	/**
	 * Function to regiser the event to make the portal modules list sortable
	 */
	makeModulesListSortable: function () {
		var thisInstance = this;
		var modulesTable = jQuery('#portalModulesTable');
		modulesTable.sortable({
			'containment': modulesTable,
			'items': 'li:not(".unsortable")',
			'revert': true,
			'tolerance': 'pointer',
			'dealy': '3000',
			'helper': function (e, ui) {
				//while dragging helper elements td element will take width as contents width
				//so we are explicity saying that it has to be same width so that element will not
				//look like distrubed
				jQuery('#savePortalInfo').trigger('change');
				ui.children().each(function (index, element) {
					element = jQuery(element);
					element.width(element.width());
				})
				return ui;
			},
		});
	},
	/**
	 * Function which will update sequence numbers of portal modules list by order
	 */
	updatePortalModulesListByOrder: function () {
		var form = this.getForm();
		jQuery('li.portalModuleRow', form).each(function (index, domElement) {
			var portalModuleRow = jQuery(domElement);
			var tabId = portalModuleRow.data('id');
			var sequenceEle = portalModuleRow.find('[name="portalModulesInfo['+tabId+'][sequence]"]');
			var expectedRowSequence = (index+1);
			var actualRowSequence = sequenceEle.val();
			if (expectedRowSequence != actualRowSequence) {
				return sequenceEle.val(expectedRowSequence);
			}
		});
	},
	/*
	 * function to save the customer portal settings
	 * @params: form - customer portal form.
	 */
	saveCustomerPortal: function (form) {
		var aDeferred = jQuery.Deferred();

		app.helper.showProgress();
		var params = this.sanitizeFormData(form);

		params['module'] = app.getModuleName();
		params['parent'] = app.getParentModuleName();
		params['action'] = 'Save';
		app.request.post({data: params}).then(function (error, data) {
			app.helper.hideProgress();
			if (data) {
				aDeferred.resolve(data);
			} else {
				aDeferred.reject(error);
			}
		}
		);
		return aDeferred.promise();
	},
	sanitizeFormData: function (form) {
		var formData = form.serializeFormData();
		var modules = jQuery('input.enabledModules');
		var enabledModules = {};
		jQuery.each(modules, function (i, module) {
			if (jQuery(module).is(":checked")) {
				enabledModules[jQuery(module).attr('name')] = 1;
			}
			else {
				enabledModules[jQuery(module).attr('name')] = 0;
			}
		});

		formData['enableModules'] = JSON.stringify(enabledModules);
		var portalModules = jQuery('ul#portalModulesTable li.portalModuleRow');

		var selectedFields = {};
		var relatedModuleInfo = {};
		var recordsVisible = {};
		var recordPermissionsInfo = {};

		var isAllMandatoryFieldsSelected = function (mandatoryFields, selectedFields) {
			var containsAllMandatory = true;
			jQuery.each(mandatoryFields, function (i, field) {
				if (!selectedFields.hasOwnProperty(field)) {
					containsAllMandatory = false;
				}
			});
			return containsAllMandatory;
		}

		var returnFormData = true;
		var message = '';
		jQuery.each(portalModules, function (index, element) {
                    if(!jQuery(element).find('.enabledModules').is(':checked')){
                        return;
                    }
			var mandatoryFields = [];
			var list = element.attributes;
			var moduleName = list['data-module'].value;
			if (moduleName != 'Dashboard') {
				var moduleFields = jQuery('input[name="availableFields_'+moduleName+'"]').val();
				if (typeof moduleFields != 'undefined') {
					var allFields = JSON.parse(moduleFields);
					jQuery.each(allFields, function (i, fields) {
						if (fields.mandatory)
							mandatoryFields.push(fields.fieldname);
					});

					var fieldInfo = jQuery('input[name="selectedFields_'+moduleName+'"]').val();
					if (fieldInfo != 'null' && isAllMandatoryFieldsSelected(mandatoryFields, JSON.parse(fieldInfo))) {
						selectedFields[moduleName] = fieldInfo;
					} else {
						returnFormData = false;
						message = app.vtranslate('JS_MANDATORY_FIELDS_MISSING');
					}

					var relModuleInfo = jQuery('input[name="relatedModules_'+moduleName+'"]').val();
					if (typeof relModuleInfo != 'undefined') {
						relatedModuleInfo[moduleName] = relModuleInfo;
					}
					var recordVisible = jQuery('.portal-record-privilege').find('input[name="recordvisible_'+moduleName+'"]').serialize();
					if (typeof recordVisible != 'undefined') {
						recordsVisible[moduleName] = recordVisible.split('=')[1];
					}
					var recordPermissions = jQuery('input[name="recordPermissions_'+moduleName+'"]').val();
					if (typeof recordPermissions !== 'undefined') {
						recordPermissionsInfo[moduleName] = JSON.parse(recordPermissions);
					}
				}
			}
		});

		if (!returnFormData) {
			app.helper.showErrorNotification({"message": message});
			return false
		}

		var activeWidgets = {};
		var defaultWidgets = JSON.parse(jQuery('input[name="defaultWidgets"]').val());

		var defaultWidgetModules = ['HelpDesk', 'Faq', 'Documents'];
		var widgetsInfo = jQuery("input.widgetsInfo");
		jQuery.each(widgetsInfo, function (index, widget) {
			var element = jQuery(widget);
			if (element.is(":checked")) {
				activeWidgets[element.attr('id')] = 1;
			}
			else {
				activeWidgets[element.attr('id')] = 0;
			}
		});
		defaultWidgetModules.forEach(function (module) {
			if (activeWidgets[module] === undefined)
				activeWidgets[module] = parseInt(defaultWidgets.widgets[module]);
		});

		if (widgetsInfo.length === 0) {
			activeWidgets = defaultWidgets.widgets;
		}
		formData['moduleFieldsInfo'] = selectedFields;
		formData['relatedModuleList'] = relatedModuleInfo;
		formData['recordsVisible'] = recordsVisible;
		formData['activeWidgets'] = JSON.stringify(activeWidgets);
		formData['recordPermissions'] = recordPermissionsInfo;
		return formData;
	},
	activateNavPills: function () {
		var thisInstance = this;
		jQuery('#portalModulesTable li').on('click', function (e) {
			var previousTab = jQuery("li[class='portalModuleRow cp-tabs ui-sortable-handle active']").data('module');
			var currentTarget = jQuery(e.currentTarget);
			var targetModule = jQuery(currentTarget).data('module');
			if (typeof previousTab == 'undefined') {
				previousTab = 'Dashboard';
				jQuery('.portalModuleRow[data-module="'+previousTab+'"]').addClass('bgColor')
			}

			if (previousTab != targetModule) {
				jQuery('.portalModuleRow[data-module="'+previousTab+'"]').addClass('bgColor');
				jQuery('.portalModuleRow').removeClass('active');
				jQuery(currentTarget).removeClass('bgColor');
				jQuery(currentTarget).addClass('active');
				var params = {
					"parent": "Settings",
					"module": "CustomerPortal",
					"targetModule": targetModule,
					"view": "EditAjax"
				};
				if (jQuery('#moduleData_'+targetModule).length > 0) {
					if (targetModule != 'Dashboard') {
						jQuery('#fieldContent_'+targetModule).removeClass('hide');
						jQuery('#fieldContent_'+targetModule).addClass('show');
						if (previousTab == 'Dashboard') {
							jQuery('#dashboardContent').removeClass('show');
							jQuery('#dashboardContent').addClass('hide');
						} else {
							jQuery('#fieldContent_'+previousTab).removeClass('show');
							jQuery('#fieldContent_'+previousTab).addClass('hide');
							thisInstance.registerRecordPermissionsEvent();
							thisInstance.registerDisableAddFieldsEvent(thisInstance.getForm(), targetModule);
							thisInstance.registerEnableAddFieldsEvent(thisInstance.getForm(), targetModule);
						}
					} else {
						jQuery('#dashboardContent').removeClass('hide');
						jQuery('#dashboardContent').addClass('show');
						jQuery('#fieldContent_'+previousTab).removeClass('show');
						jQuery('#fieldContent_'+previousTab).addClass('hide');
					}
				} else {
					app.request.post({data: params}).then(function (error, data) {
						if (targetModule != 'Dashboard') {
							jQuery('#fieldContent_'+targetModule).removeClass('hide');
							jQuery('#fieldContent_'+targetModule).addClass('show');

							if (previousTab == 'Dashboard') {
								jQuery('#dashboardContent').removeClass('show');
								jQuery('#dashboardContent').addClass('hide');
							} else {
								jQuery('#fieldContent_'+previousTab).removeClass('show');
								jQuery('#fieldContent_'+previousTab).addClass('hide');
							}
							jQuery('#fieldContent_'+targetModule).html(data);
						} else {
							jQuery('#dashboardContent').removeClass('hide');
							jQuery('#dashboardContent').addClass('show');
							jQuery('#fieldContent_'+previousTab).removeClass('show');
							jQuery('#fieldContent_'+previousTab).addClass('hide');
						}

						vtUtils.showSelect2ElementView(jQuery('#addField_'+targetModule), {
							_maximumSelectionSize: 7,
							dropdownCss: {
								'z-index': 0
							}
						});
						thisInstance.updateFieldInfo(targetModule);
						thisInstance.registerAddFieldAction();
						thisInstance.registerDeleteField(targetModule);
						thisInstance.registerRelatedModuleInfoEvent(targetModule);
						thisInstance.registerRecordPermissionsEvent();
						thisInstance.registerDisableAddFieldsEvent(thisInstance.getForm(), targetModule);
						thisInstance.registerEnableAddFieldsEvent(thisInstance.getForm(), targetModule);
					});
				}
			}
		});
		thisInstance.registerFieldsToggler();
	},
	updateFieldInfo: function (targetModule) {
		var thisInstance = this;
		var allowedModules = ['HelpDesk', 'Assets'];
		if (targetModule != 'Dashboard') {
			var defaultFields = JSON.parse(jQuery('input[name="availableFields_'+targetModule+'"]').val());
			var selectedFieldss = JSON.parse(jQuery('input[name="selectedFields_'+targetModule+'"]').val());
			var selectedFields = [];
			if (selectedFieldss) {
				for (var x in selectedFieldss) {
					selectedFields.push(x);
				}
				var alreadySelectedFields = {};
				jQuery.each(selectedFieldss, function (i, field) {
					alreadySelectedFields[i] = selectedFieldss[i];
				});
			}
			jQuery.each(defaultFields, function (index, value) {
				var fieldStatusValue = 0;
				if (jQuery.inArray(value['fieldname'], selectedFields) === -1) {
					if (value['mandatory'] && value['iseditable'])
						jQuery('select#addField_'+targetModule).append('<option  value="'+value['fieldname']+'##'+value['fieldlabel']+'">'+value['fieldlabel']+'*</option>');
					else
						jQuery('select#addField_'+targetModule).append('<option  value="'+value['fieldname']+'##'+value['fieldlabel']+'">'+value['fieldlabel']+'</option>');
				} else {
					fieldStatusValue = parseInt(alreadySelectedFields[value['fieldname']]);
					var divElement = '';
					if (value['mandatory']) {
						if (allowedModules.indexOf(targetModule) >= 0) {
							if (value['iseditable']) {
								if (fieldStatusValue > 0) {
									divElement = '<div id="'+targetModule+'_'+value['fieldname']+'" class="col-sm-12 portal-fieldInfo-wrapper"><div class="col-sm-2 portal-fieldInfo-sliderWrapper"><div class="portal-fields-switch portal-fields-switchOn" name="'+value['fieldname']+'" id="'+value['fieldname']+'_'+targetModule+'"></div></div><div class="col-sm-8 portal-fieldName-wrapper"><input type="hidden" name="'+targetModule+'_fieldStatus" value="'+fieldStatusValue+'" id='+value['fieldname']+'>'+value['fieldlabel']+'<span class="redColor">*</span></div><div class="col-sm-2 "><span class="pull-right deleteField" data-label="'+value['fieldlabel']+'" data-name="'+value['fieldname']+'"><button type="button" class="btn btn-sm portal-deletefield" disabled="disabled"><i class="fa fa-times disabled"></i></button></span></div></div></div>';
								} else {
									divElement = '<div id="'+targetModule+'_'+value['fieldname']+'" class="col-sm-12 portal-fieldInfo-wrapper"><div class="col-sm-2 portal-fieldInfo-sliderWrapper"><div class="portal-fields-switch portal-fields" name="'+value['fieldname']+'" id="'+value['fieldname']+'_'+targetModule+'"></div></div><div class="col-sm-8 portal-fieldName-wrapper"><input type="hidden" name="'+targetModule+'_fieldStatus" value="'+fieldStatusValue+'" id='+value['fieldname']+'>'+value['fieldlabel']+'<span class="redColor">*</span></div><div class="col-sm-2 "><span class="pull-right deleteField" data-label="'+value['fieldlabel']+'" data-name="'+value['fieldname']+'"><button type="button" class="btn btn-sm portal-deletefield" disabled="disabled"><i class="fa fa-times disabled"></i></button></span></div></div></div>';
								}
							} else {
								divElement = '<div id="'+targetModule+'_'+value['fieldname']+'" class="col-sm-12 portal-fieldInfo-wrapper"><div class="col-sm-2 portal-fieldInfo-sliderWrapper switch-disabled"><div class="portal-fields-switch portal-fields" name="'+value['fieldname']+'" id="'+value['fieldname']+'_'+targetModule+'" disabled></div></div><div class="col-sm-8 portal-fieldName-wrapper"><input type="hidden" name="'+targetModule+'_fieldStatus" value="'+fieldStatusValue+'" id='+value['fieldname']+'>'+value['fieldlabel']+'<span class="redColor">*</span></div><div class="col-sm-2 "><span class="pull-right deleteField" data-label="'+value['fieldlabel']+'" data-name="'+value['fieldname']+'"><button type="button" class="btn btn-sm portal-deletefield" disabled="disabled"><i class="fa fa-times disabled"></i></button></span></div></div></div>';
							}
						} else {
							fieldStatusValue = 0;
							divElement = '<div id="'+targetModule+'_'+value['fieldname']+'" class="col-sm-12 portal-fieldInfo-wrapper"><div class="col-sm-2 portal-fieldInfo-sliderWrapper switch-disabled"><div class="portal-fields-switch" name="'+value['fieldname']+'" id="'+value['fieldname']+'_'+targetModule+'" disabled></div></div><div class="col-sm-8  portal-fieldName-wrapper"><input type="hidden" name="'+targetModule+'_fieldStatus" value="'+fieldStatusValue+'" id='+value['fieldname']+'>'+value['fieldlabel']+'<span class="redColor">*</span></div><div class="col-sm-2 "><span class="pull-right deleteField" data-label="'+value['fieldlabel']+'" data-name="'+value['fieldname']+'"><button class="btn btn-sm portal-deletefield" disabled="disabled"><i class="fa fa-times"></i></button></span></div></div></div>';
						}
					}
					else {
						if (value['iseditable'] && allowedModules.indexOf(targetModule) >= 0) {
							if (fieldStatusValue) {
								divElement = '<div id="'+targetModule+'_'+value['fieldname']+'" class="col-sm-12 portal-fieldInfo-wrapper"><div class="col-sm-2 portal-fieldInfo-sliderWrapper"><div class="portal-fields-switch portal-fields-switchOn" name="'+value['fieldname']+'" id="'+value['fieldname']+'_'+targetModule+'"></div></div><div class="col-sm-8 portal-fieldName-wrapper"><input type="hidden" name="'+targetModule+'_fieldStatus" value="'+fieldStatusValue+'" id='+value['fieldname']+'>'+value['fieldlabel']+'</div><div class="col-sm-2 "><span class="pull-right deleteField" data-label="'+value['fieldlabel']+'" data-name="'+value['fieldname']+'"><button class="btn btn-sm portal-deletefield"><i class="fa fa-times"></i></button></span></div></div></div>';
							} else {
								divElement = '<div id="'+targetModule+'_'+value['fieldname']+'" class="col-sm-12 portal-fieldInfo-wrapper"><div class="col-sm-2 portal-fieldInfo-sliderWrapper"><div class="portal-fields-switch" name="'+value['fieldname']+'" id="'+value['fieldname']+'_'+targetModule+'"></div></div><div class="col-sm-8 portal-fieldName-wrapper"><input type="hidden" name="'+targetModule+'_fieldStatus" value="'+fieldStatusValue+'" id='+value['fieldname']+'>'+value['fieldlabel']+'</div><div class="col-sm-2 "><span class="pull-right deleteField" data-label="'+value['fieldlabel']+'" data-name="'+value['fieldname']+'"><button class="btn btn-sm portal-deletefield"><i class="fa fa-times"></i></button></span></div></div></div>';
							}
						} else {
							fieldStatusValue = 0;
							divElement = '<div id="'+targetModule+'_'+value['fieldname']+'" class="col-sm-12 portal-fieldInfo-wrapper"><div class="col-sm-2 portal-fieldInfo-sliderWrapper switch-disabled"><div class="portal-fields-switch" name="'+value['fieldname']+'" id="'+value['fieldname']+'_'+targetModule+'" disabled></div></div><div class="col-sm-8 portal-fieldName-wrapper"><input type="hidden" name="'+targetModule+'_fieldStatus" value="'+fieldStatusValue+'" id='+value['fieldname']+'>'+value['fieldlabel']+'</div><div class="col-sm-2 "><span class="pull-right deleteField" data-label="'+value['fieldlabel']+'" data-name="'+value['fieldname']+'"><button class="btn btn-sm portal-deletefield"><i class="fa fa-times"></i></button></span></div></div></div>';
						}

					}
					jQuery('#fieldRows_'+targetModule).append(divElement);
				}
			});
		}
	},
	registerAddFieldAction: function () {
		var thisInstance = this;
		var allowedModules = ['HelpDesk', 'Assets'];
		var currentModule = jQuery("li.portalModuleRow.active").data('module');
		jQuery('#addField_'+currentModule).select2({
			placeholder: app.vtranslate("JS_ADD_FIELD")
		});
		jQuery('#addFieldButton_'+currentModule).on('click', function (e) {
			e.preventDefault();
			var publishFields = jQuery('#addField_'+currentModule).val();
			var defaultFields = JSON.parse(jQuery('input[name="availableFields_'+currentModule+'"]').val());
			if (publishFields != undefined) {
				var publishedFields = {};
				for (var i = 0; i < publishFields.length; i++) {
					var fieldInfo = publishFields[i].split('##');
					jQuery.each(defaultFields, function (index, value) {
						var sliderValue = 0;
						if (value['fieldname'] == fieldInfo[0]) {
							if (value['mandatory']) {
								sliderValue = value['iseditable'];
								if (allowedModules.indexOf(currentModule) >= 0) {
									if (value['iseditable']) {
										var divElement = '<div id="'+currentModule+'_'+value['fieldname']+'" class="col-sm-12 portal-fieldInfo-wrapper"><div class="col-sm-2 portal-fieldInfo-sliderWrapper"><div class="portal-fields-switch portal-fields-switchOn" name="'+value['fieldname']+'" id="'+currentModule+'_'+value['fieldname']+'"></div></div><div class="col-sm-8" style="padding-top: 5px; padding-left: 15px; padding-right: 0px;"><input type="hidden" name="'+currentModule+'_fieldStatus" value="'+sliderValue+'" id='+value['fieldname']+'>'+value['fieldlabel']+'<span class="redColor">*</span></div><div class="col-sm-2 "><span class="pull-right deleteField" data-label="'+value['fieldlabel']+'" data-name="'+value['fieldname']+'"><button class="btn btn-sm portal-deletefield" disabled><i class="fa fa-times"></i></button></span></div></div></div>';
									} else {
										sliderValue = 0;
										var divElement = '<div id="'+currentModule+'_'+value['fieldname']+'" class="col-sm-12 portal-fieldInfo-wrapper" style=""><div class="col-sm-2 portal-fieldInfo-sliderWrapper switch-disabled"><div class="portal-fields-switch" name="'+value['fieldname']+'" id="'+currentModule+'_'+value['fieldname']+'" disabled></div></div><div class="col-sm-8" style="padding-top: 5px; padding-left: 15px; padding-right: 0px;"><input type="hidden" name="'+currentModule+'_fieldStatus" value="'+sliderValue+'" id='+value['fieldname']+'>'+value['fieldlabel']+'<span class="redColor">*</span></div><div class="col-sm-2 "><span class="pull-right deleteField" data-label="'+value['fieldlabel']+'" data-name="'+value['fieldname']+'"><button class="btn btn-sm portal-deletefield" disabled><i class="fa fa-times"></i></button></span></div></div></div>';
									}
								} else {
									sliderValue = 0;
									var divElement = '<div id="'+currentModule+'_'+value['fieldname']+'" class="col-sm-12 portal-fieldInfo-wrapper"><div class="col-sm-2 portal-fieldInfo-sliderWrapper switch-disabled"><div class="portal-fields-switch" name="'+value['fieldname']+'" id="'+currentModule+'_'+value['fieldname']+'" disabled></div></div><div class="col-sm-8" style="padding-top: 5px; padding-left: 15px; padding-right: 0px;"><input type="hidden" name="'+currentModule+'_fieldStatus" value="'+sliderValue+'" id='+value['fieldname']+'>'+value['fieldlabel']+'</div><div class="col-sm-2 "><span class="pull-right deleteField" data-label="'+value['fieldlabel']+'" data-name="'+value['fieldname']+'"><button class="btn btn-sm portal-deletefield" disabled><i class="fa fa-times"></i></button></span></div></div></div>';
								}
							} else {
								if (value['iseditable'] && allowedModules.indexOf(currentModule) >= 0) {
									sliderValue = 0;
									var divElement = '<div id="'+currentModule+'_'+value['fieldname']+'" class="col-sm-12 portal-fieldInfo-wrapper"><div class="col-sm-2 portal-fieldInfo-sliderWrapper"><div class="portal-fields-switch" name="'+value['fieldname']+'" id="'+currentModule+'_'+value['fieldname']+'" ></div></div><div class="col-sm-8" style="padding-top: 5px; padding-left: 15px; padding-right: 0px;"><input type="hidden" name="'+currentModule+'_fieldStatus" value="'+sliderValue+'" id='+value['fieldname']+'>'+value['fieldlabel']+'</div><div class="col-sm-2 "><span class="pull-right deleteField" data-label="'+value['fieldlabel']+'" data-name="'+value['fieldname']+'"><button class="btn btn-sm portal-deletefield"><i class="fa fa-times"></i></button></span></div></div></div>';
								}
								else {
									sliderValue = 0;
									var divElement = '<div id="'+currentModule+'_'+value['fieldname']+'" class="col-sm-12 portal-fieldInfo-wrapper"><div class="col-sm-2 portal-fieldInfo-sliderWrapper switch-disabled"><div class="portal-fields-switch" name="'+value['fieldname']+'" id="'+currentModule+'_'+value['fieldname']+'" disabled></div></div><div class="col-sm-8" style="padding-top: 5px; padding-left: 15px; padding-right: 0px;"><input type="hidden" name="'+currentModule+'_fieldStatus" value="'+sliderValue+'" id='+value['fieldname']+'_'+currentModule+'>'+value['fieldlabel']+'</div><div class="col-sm-2 "><span class="pull-right deleteField" data-label="'+value['fieldlabel']+'" data-name="'+value['fieldname']+'"><button class="btn btn-sm portal-deletefield"><i class="fa fa-times"></i></button></span></div></div></div>';
								}
							}
							jQuery('#fieldRows_'+currentModule).append(divElement);
							publishedFields[value['fieldname']] = sliderValue;
						}
					});
				}
				jQuery("#addField_"+currentModule).select2("val", "");
				jQuery("#addFieldButton_"+currentModule).attr("disabled", "disabled");
				thisInstance.registerUpdateSelectionEvent(publishedFields, currentModule);
			}

		});
	},
	registerUpdateSelectionEvent: function (latestselectedFields, currentModule) {
		var defaultFields = JSON.parse(jQuery('input[name="availableFields_'+currentModule+'"]').val());
		var fields = JSON.parse(jQuery('input[name="selectedFields_'+currentModule+'"]').val());

		var selectedFields = $.extend({}, fields, latestselectedFields);
		jQuery('select#addField_'+currentModule+' option').remove();

		var finalSelectedFields = [];
		for (var x in selectedFields) {
			finalSelectedFields.push(x);
		}
		jQuery.each(defaultFields, function (index, value) {
			if (jQuery.inArray(value['fieldname'], finalSelectedFields) === -1) {
				if (value['mandatory'] && value['iseditable'])
					jQuery('select#addField_'+currentModule).append('<option  value="'+value['fieldname']+'##'+value['fieldlabel']+'">'+value['fieldlabel']+'*</option>');
				else
					jQuery('select#addField_'+currentModule).append('<option  value="'+value['fieldname']+'##'+value['fieldlabel']+'">'+value['fieldlabel']+'</option>');
			}
		});
		jQuery('input[name="selectedFields_'+currentModule+'"]').val(JSON.stringify(selectedFields));
	},
	registerDeleteField: function (module) {
		jQuery('#fieldRows_'+module).on('click', '.deleteField', function (e) {
			e.preventDefault();
			jQuery('#savePortalInfo').trigger('change');
			var currentTarget = jQuery(e.currentTarget);
			var currentName = currentTarget.attr('data-name');
			var element = jQuery(currentTarget).parents('div#'+module+'_'+currentName);
			var deletedColumn = currentName;
			var deletedColumnLabel = currentTarget.attr('data-label');
			var availableFields = JSON.parse(jQuery('input[name="availableFields_'+module+'"]').val());
			var mandatoryFields = [];
			jQuery.each(availableFields, function (index, fields) {
				if (fields.mandatory) {
					mandatoryFields.push(fields.fieldname);
				}
			});
			if (mandatoryFields.indexOf(currentName) >= 0) {
				return false;
			} else {
				jQuery(element).remove();
			}
			var selectedFields = JSON.parse(jQuery('input[name="selectedFields_'+module+'"]').val());
			var fields = {};
			for (var x in selectedFields) {
				if (x != currentName) {
					fields[x] = selectedFields[x];
				}
			}

			jQuery('select#addField_'+module).append('<option value="'+deletedColumn+'">'+deletedColumnLabel+'</option>');
			jQuery('input[name="selectedFields_'+module+'"]').val(JSON.stringify(fields));

		});
	},
	registerSaveFunction: function () {
		var thisInstance = this;
		jQuery('#savePortalInfo').on('click', function (e) {
			e.preventDefault();
			var form = thisInstance.getForm();
			var renewalPeriod = form.find('[name=renewalPeriod]').val();
			//update the sequence of customer portal modules
			thisInstance.updatePortalModulesListByOrder();
			//save the customer portal settings
			if (form.valid()) {
				thisInstance.saveCustomerPortal(form).then(
						function (data) {
							if (data['success']) {
								var saveElement = jQuery("#savePortalInfo");
								saveElement.attr('disabled', 'disabled');
								app.helper.showSuccessNotification({"message": app.vtranslate('JS_PORTAL_INFO_SAVED')});
							}
						},
						function (error) {
							//TODO: Handle Error
						}
				);
			} else {
				jQuery('html, body').animate({
					scrollTop: form.closest("#listViewContent").offset().top
				}, 1000);
			}
		});
	},
	registerRelatedModuleInfoEvent: function (module) {
		jQuery("input.relmoduleinfo_"+module).change('checkbox', function (e) {
			var checkBox = jQuery(e.currentTarget);
			if (checkBox.is(":checked") == true) {
				checkBox.attr('value', 1);
			} else {
				checkBox.attr('value', 0);
			}

			var x = jQuery('input.relmoduleinfo_'+module);

			var list = [];
			jQuery.each(x, function (i, cb) {
				var o = {};
				if (checkBox.data('relmodule') == cb.name && checkBox.is(":checked") == true) {
					cb.value = 1;
				} else if (checkBox.data('relmodule') == x && checkBox.is(":checked") == false) {
					cb.value = 0;
				}
				o.name = cb.name;
				o.value = cb.value;
				list.push(o);
			});
			jQuery('input[name="relatedModules_'+module+'"]').val(JSON.stringify(list))
		});
	},
	registerEventForAddCustomModule: function () {
		var thisInstance = this;
		jQuery("#addToPortalMenu").on('click', function () {
			var moduleValues = jQuery("select[name='custommodules']").val();
			var customRelModules = JSON.parse(jQuery('#customRelModules').val());
			var customModule = moduleValues.split('::');
			jQuery("select[name='custommodules'] option").remove();

			var tempModules = {}
			for (var tabid in customRelModules) {
				if (tabid != customModule[0]) {
					tempModules[tabid] = customRelModules[tabid];
					jQuery("select[name='custommodules']").append('<option  value="'+tabid+'::'+customRelModules[tabid]+'::16">'+customRelModules[tabid]+'</option>');
				}
			}

			var li = "<li class='portalModuleRow bgColor' style='border-color: #ddd; border-image: none; border-style: solid; border-width: 0 0 1px 1px;' data-id='"+customModule[0]+"' data-sequence='"+customModule[2]+"' data-module='"+customModule[1]+"'><input type='hidden' name='portalModulesInfo["+customModule[0]+"][sequence]' value='"+customModule[2]+"' /><a href='javascript:void(0);' class='cp-modules'><img class='drag-portal-module' src='layouts/v7/resources/Images/drag.png' border='0' title='Drag And Drop To Reorder Portal Menu In Customer Portal'/>&nbsp;&nbsp;<input class='enabledModules portal-module-name' name="+customModule[0]+" type='checkbox' value='0'/>&nbsp;&nbsp;"+customModule[1]+"</a></li>";
			jQuery('ul#portalModulesTable').append(li);
			jQuery('#customRelModules').val(JSON.stringify(tempModules));

			jQuery("div.portal-dashboard").append("<div id='fieldContent_"+customModule[1]+"' class='hide'>"+customModule[1]+"</div>");

			thisInstance.activateNavPills();
			if (jQuery("select[name='custommodules'] option").length > 0) {
				jQuery("select[name='custommodules']").select2().trigger('change');
			} else {
				jQuery("div#customModules").addClass('hide');
			}
		})
	},
	registerFieldsToggler: function () {
		jQuery('.portal-dashboard').on('click', '.portal-fields-switch', function (e) {
			jQuery('#savePortalInfo').trigger('change');
			var currentModule = jQuery("li.portalModuleRow.active").data('module');
			var allowedModules = ['HelpDesk', 'Assets'];
			var moduleStatus = allowedModules.indexOf(currentModule) !== -1;
			var selectedFields = JSON.parse(jQuery('input[name="selectedFields_'+currentModule+'"]').val());
			var element = jQuery(e.currentTarget);
			var fieldName = element.attr('name');
			var editableStatus = jQuery(element).attr('disabled') == 'disabled' ? false : true;
			//check for if field is editable and compute condition along with moduleStatus
			var switchable = editableStatus && moduleStatus;
			if (switchable) {
				jQuery(element).toggleClass('portal-fields-switchOn');
				selectedFields[fieldName] = selectedFields[fieldName] == 0 ? 1 : 0;
				jQuery('input[name="selectedFields_'+currentModule+'"]').val(JSON.stringify(selectedFields));
				jQuery('input[name="'+currentModule+'_fieldStatus"][id="'+fieldName+'"]').val(selectedFields[fieldName]);
			}
			var lengthOfWritableFields = jQuery('input[name="'+currentModule+'_fieldStatus"][value="1"]').length;
			if (!lengthOfWritableFields) {
				changeRecordPermissionsEvent(true, currentModule);
			} else {
				changeRecordPermissionsEvent(false, currentModule);
			}
		});
		changeRecordPermissionsEvent = function (disableAll, currentTab) {
			if (disableAll) {
				jQuery('#recordPrivilege_'+currentTab).find('input[type="checkbox"]').attr("disabled", "disabled").val(0).removeAttr('checked');
				var permissionsArray = [];
				var createPermission = {create: "0"};
				var editPermission = {edit: "0"};
				permissionsArray.push(createPermission);
				permissionsArray.push(editPermission);
				jQuery('input[name="recordPermissions_'+currentTab+'"]').val(JSON.stringify(permissionsArray));
			}
			else {
				var lengthOfDisabledPermissions = jQuery('#recordPrivilege_'+currentTab+' .recordpermissions').attr('disabled');
				if (lengthOfDisabledPermissions !== undefined && lengthOfDisabledPermissions.length > 0) {
					jQuery('#recordPrivilege_'+currentTab).find('input[type="checkbox"]').removeAttr('disabled').val(1).prop('checked', 'checked');
					var permissionsArray = [];
					var createPermission = {create: "1"};
					var editPermission = {edit: "1"};
					permissionsArray.push(createPermission);
					permissionsArray.push(editPermission);
					jQuery('input[name="recordPermissions_'+currentTab+'"]').val(JSON.stringify(permissionsArray));
				}
			}
		}
	},
	registerRecordPermissionsEvent: function () {
		jQuery("input.recordpermissions").change('checkbox', function (e) {
			var currentTab = jQuery("li.portalModuleRow.active").data('module');
			var checkBox = jQuery(e.currentTarget);
			if (checkBox.is(":checked")) {
				checkBox.attr('value', 1);
			} else {
				checkBox.attr('value', 0);
			}
			var list = [];
			var x = jQuery("input.recordpermissions");
			jQuery.each(x, function (i, cb) {
				var o = {};
				o[cb.name] = cb.value;
				list.push(o);
			});
			jQuery('input[name="recordPermissions_'+currentTab+'"]').val(JSON.stringify(list));
		});
		var permissionsElements = jQuery("input.recordpermissions");
		var alreadySavedPermissions = [];
		var currentTab = jQuery("li.portalModuleRow.active").data('module');
		jQuery.each(permissionsElements, function (i, element) {
			var o = {};
			var name = jQuery(element).attr("name");
			var value = jQuery(element).attr("value");
			o[name] = value;
			alreadySavedPermissions.push(o);
			if (!parseInt(value) && jQuery('input[name="'+currentTab+'_fieldStatus"][value="1"]').length < 1) {
				jQuery('.recordpermissions[name="'+name+'"]').attr("disabled", "disabled");
			}
		});
		jQuery('input[name="recordPermissions_'+currentTab+'"]').val(JSON.stringify(alreadySavedPermissions));
	},
	registerFormChangeEvent: function (form) {
		jQuery(form).change(function (e) {
			var saveElement = jQuery("#savePortalInfo");
			if (saveElement.attr('disabled')) {
				jQuery(saveElement).removeAttr('disabled');
			} else {
				return false;
			}
		});
	},
	registerEnableAddFieldsEvent: function (form, targetModule) {
		var thisInstance = this;
		if (form === undefined) {
			form = thisInstance.getForm();
		}
		jQuery(form).find('#moduleData_'+targetModule).on('change', function (e) {
			if (typeof e.val !== 'undefined') {
				if (e.val.length > 0) {
					jQuery("#addFieldButton_"+targetModule).removeAttr("disabled");
				} else {
					jQuery("#addFieldButton_"+targetModule).attr("disabled", "disabled");
				}
			}
		});
	},
	registerDisableAddFieldsEvent: function (form, targetModule) {

		var thisInstance = this;
		if (form === undefined) {
			form = thisInstance.getForm();
		}
		jQuery("#addFieldButton_"+targetModule).attr("disabled", "disabled");
	},
	registerEvents: function (e) {
		jQuery("[rel='tooltip']").tooltip({placement: 'right', 'container': 'body'});
		var thisInstance = this;
		thisInstance.activateNavPills();
		thisInstance.registerSaveFunction();
		var form = thisInstance.getForm();
		thisInstance.registerFormChangeEvent(form);
		form.vtValidate();
		//register all select2 Elements
		vtUtils.showSelect2ElementView(form.find('select.select2'), {
			maximumSelectionSize: 7,
			dropdownCss: {
				'z-index': 100000
			}
		});
		jQuery('#portalAnnouncement[name="announcement"]').bind('keyup', function () {
			jQuery('#savePortalInfo').trigger('change');
		});
		jQuery('#portalAnnouncement[name="announcement"]').bind('input propertychange', function () {
			jQuery('#savePortalInfo').trigger('change');
		});
		jQuery('input[type="text"][name="renewalPeriod"]').bind('input propertychange', function () {
			jQuery('#savePortalInfo').trigger('change');
		});
		thisInstance.registerEventForAddCustomModule();
		//To make customer portal modules list sortable
		thisInstance.makeModulesListSortable();

		vtUtils.showSelect2ElementView(jQuery('#shortcuts'), {
			placeholder: app.vtranslate("JS_SELECT_SHORTCUT"),
			_maximumSelectionSize: 7,
			dropdownCss: {
				'z-index': 0
			}
		});
	}
});

Settings_Customer_Portal_Js('Settings_CustomerPortal_Index_Js', {}, {});