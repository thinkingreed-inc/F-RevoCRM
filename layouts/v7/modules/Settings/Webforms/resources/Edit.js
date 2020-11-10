/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Settings_Vtiger_Edit_Js('Settings_Webforms_Edit_Js', {}, {
	
	duplicateWebformNames : {},
	
	targetFieldsTable : false,
	/**
	 * Function to get source module fields table
	 */
	getSourceModuleFieldTable : function() {
		var editViewForm = this.getForm();
		if(this.targetFieldsTable == false){
			this.targetFieldsTable = editViewForm.find('[name="targetModuleFields"]');
		}
		return this.targetFieldsTable;
	},
	
	targetModule : false,
	
	/**
	 * Function to set target module
	 */
	setTargetModule : function(targetModuleName){
		this.targetModule = targetModuleName;
	},
	
	/**
	 * Function to render selected field UI
	 */
	displaySelectedField : function(selectedField){
		var editViewForm = this.getForm();
		var targetFieldsTable = this.getSourceModuleFieldTable();
		var selectedFieldOption = editViewForm.find('option[value="'+selectedField+'"]');
		var selectedFieldInfo = selectedFieldOption.data('fieldInfo');
		var selectedOptionLabel = selectedFieldInfo.label;
		var selectedOptionName = selectedFieldInfo.name;
		var selectedOptionType = selectedFieldInfo.type;
		var isCustomField = selectedFieldInfo.customField;
		var moduleName = app.getModuleName();
		var fieldInstance = Vtiger_Field_Js.getInstance(selectedFieldInfo,moduleName);
		var fieldMandatoryStatus = selectedFieldOption.data('mandatory');
		var UI = fieldInstance.getUiTypeSpecificHtml();
		var UI = jQuery(UI);
		var addOnElementExist = UI.find('.add-on');
		var parentInputPrepend = addOnElementExist.closest('.input-prepend');
		
		if((parentInputPrepend.length > 0) && (selectedOptionType != "reference")){
			parentInputPrepend.find('.add-on').addClass('overWriteAddOnStyles');
		}

		var webFormTargetFieldStructure = '<tr data-name="'+selectedOptionName+'" data-type="'+selectedOptionType+'" data-mandatory-field="'+fieldMandatoryStatus+'" class="listViewEntries">'+
											'<td class="textAlignCenter">'+
								'<input type="hidden" class="sequenceNumber" name="selectedFieldsData['+selectedField+'][sequence]" value=""/>'+
								'<input type="hidden" value="0" name="selectedFieldsData['+selectedField+'][required]"/>'+
								'<input type="checkbox" value="1" class="markRequired mandatoryField" name="selectedFieldsData['+selectedField+'][required]"/></td>';
		
		webFormTargetFieldStructure+= '<td class="textAlignCenter">'+
										'<input type="hidden" value="0" name="selectedFieldsData['+selectedField+'][hidden]"/>'+
										'<input type="checkbox" value="1" class="markRequired hiddenField" name="selectedFieldsData['+selectedField+'][hidden]"/>'+
										'</td>'+
										'<td class="fieldLabel" data-label="'+selectedOptionLabel+'">'+selectedOptionLabel+'</td>'+
										'<td class="fieldValue" data-name="fieldUI_'+selectedOptionName+'"></td>';
				
		if(isCustomField){
			webFormTargetFieldStructure+=	'<td>'+app.vtranslate('JS_LABEL')+":"+selectedOptionLabel;
		} else {
			webFormTargetFieldStructure+=	'<td>'+selectedField;
		}
		
		webFormTargetFieldStructure+=	'<div class="pull-right actions">'+
										'<span class="actionImages"><a class="removeTargetModuleField"><i class="icon-remove-sign"></i></a></span></div></td></tr>';

        targetFieldsTable.append(webFormTargetFieldStructure);
        var fieldUIContainer = targetFieldsTable.find('[data-name="fieldUI_'+selectedOptionName+'"]');
		fieldUIContainer.html(UI);
		
		vtUtils.applyFieldElementsView(fieldUIContainer);
        
		if(selectedOptionType == 'reference'){
			this.registerAutoCompleteFields(editViewForm);
            this.appendPopupReferenceModuleName(fieldUIContainer.find('[name="popupReferenceModule"]'));
        }
	},
	
	/**
	 * Function to register event for onchange event for 
	 * select2 element fro adding and removing fields
	 */
	registerOnChangeEventForSelect2 : function(){
		var thisInstance = this;
		var editViewForm = this.getForm();
		var fieldsTable = this.getSourceModuleFieldTable();
		jQuery('#fieldsList').on('change',function(e){
			var element = jQuery(e.currentTarget);
			//To handle the options that are removed from select2
			if(typeof e.removed != "undefined"){
				var removedFieldObject = e.removed;
				var removedFieldName = removedFieldObject.id; 
				var removedFieldLabel = removedFieldObject.text; 
				var selectedFieldOption = editViewForm.find('option[value="'+removedFieldName+'"]');
				var fieldMandatoryStatus = selectedFieldOption.data('mandatory');
				//To handle the mandatory option that are removed using backspace from select2
				if(fieldMandatoryStatus){
					var existingOptions = element.select2("data");
					var params = {
						'id' : removedFieldName,
						'text' : removedFieldLabel
					}
					existingOptions.push(params);
					//By setting data attribute select2 mandatory options are added back to select2
					element.select2("data",existingOptions);
					thisInstance.triggerLockMandatoryFieldOptions();
				} else {
					//Remove the row with respect to option that are removed from select2
					var selectedFieldInfo = selectedFieldOption.data('fieldInfo');
					var removeRowName = selectedFieldInfo.name;
					fieldsTable.find('tr[data-name="'+removeRowName+'"]').find('.removeTargetModuleField').trigger('click');
					if(element.val().length == 1){
						jQuery('#saveFieldsOrder').attr('disabled',true);
					}
				}
			} else if(typeof e.added != "undefined"){
				//To add the row according to option that is selected from select2
				var addedFieldObject = e.added;
				var addedFieldName = addedFieldObject.id;
				thisInstance.displaySelectedField(addedFieldName);
				thisInstance.registerEventToHandleOnChangeOfOverrideValue();
			}
		})
	},
	
	/**
	 * Function to register event for making field as required
	 */
	registerEventForMarkRequiredField : function(){
		var thisInstance = this;
		this.getSourceModuleFieldTable().on('change','.markRequired',function(e){
			var message = app.vtranslate('JS_MANDATORY_FIELDS_WITHOUT_OVERRIDE_VALUE_CANT_BE_HIDDEN');
			var element = jQuery(e.currentTarget);
			var elementRow = element.closest('tr');
			var fieldName= elementRow.data('name');
			var fieldType = elementRow.data('type');
			if(fieldType == "multipicklist"){
				fieldName = fieldName+'[]';
			}
			var fieldValue = jQuery.trim(elementRow.find('[name="'+fieldName+'"]').val());
			var isMandatory = element.closest('tr').data('mandatoryField');
			if(isMandatory){
				if(element.hasClass('mandatoryField')){
					element.attr('checked',true);
				}else if(element.hasClass('hiddenField')){
					if(fieldValue == ''){
						element.attr('checked',false);
						thisInstance.showErrorMessage(message);
					}
				}
				e.preventDefault();
				return;
			}else{
				if(element.hasClass('mandatoryField')){
					if(element.is(':checked')){
						var hiddenOption = elementRow.find('.hiddenField');
						if(hiddenOption.is(':checked')){
							if(fieldValue == ''){
								element.attr('checked',false);
								thisInstance.showErrorMessage(message);
								e.preventDefault();
								return;
							}
						}
					}
				}else if(element.hasClass('hiddenField')){
					var mandatoryOption = elementRow.find('.mandatoryField');
					if(mandatoryOption.is(':checked') && fieldValue == ''){
						element.attr('checked',false);
						thisInstance.showErrorMessage(message);
						e.preventDefault();
						return;
					}
				}
			}
		})
	},
	
	/**
	 * Function to show error messages
	 */
	showErrorMessage : function(message){
        app.helper.showErrorNotification({"message":message});
	},
	
	/**
	 * Function to handle target module remove field action
	 */
	registerEventForRemoveTargetModuleField : function(){
		var thisInstance = this;
		var sourceModuleContainer = this.getSourceModuleFieldTable();
		sourceModuleContainer.on('click','.removeTargetModuleField',function(e){
			var element = jQuery(e.currentTarget);
			var containerRow = element.closest('tr');
			var removedFieldLabel = containerRow.find('td.fieldLabel').text();
			var selectElement = sourceModuleContainer.find('#fieldsList');
			//x click removes element in select2 automatically (2 actions below is not required)
			//var select2Element = vtUtils.showSelect2ElementView(selectElement);
			//select2Element.find('li.select2-search-choice').find('div:contains('+removedFieldLabel+')').closest('li').remove();
			selectElement.find('option:contains('+removedFieldLabel+')').removeAttr('selected');
			if(selectElement.val().length == 1){
				jQuery('#saveFieldsOrder').attr('disabled',true);
			}
			thisInstance.triggerLockMandatoryFieldOptions();
			containerRow.remove();
		})
	},
	
	/**
	 * Function to lock mandatory option in select2
	 */
	lockMandatoryOptionInSelect2 : function(mandatoryFieldLabel){
		var sourceModuleContainer = this.getSourceModuleFieldTable();
		var fieldsListSelect2Element = sourceModuleContainer.find('#s2id_fieldsList');
		fieldsListSelect2Element.find('.select2-search-choice div:contains("'+mandatoryFieldLabel+'")').closest('li').find('a').remove();
	},
	
	/**
	 * Function to trigger lock mandatory field options in edit mode
	 */
	triggerLockMandatoryFieldOptions : function(){
		var editViewForm = this.getForm();
		var selectedOptions = editViewForm.find('#fieldsList option:selected');
		for(var i=0;i<selectedOptions.length;i++){
			var selectedOption = jQuery(selectedOptions[i]);
			var selectedFieldInfo = jQuery(selectedOption).data('fieldInfo');
			var mandatoryStatus = selectedOption.data('mandatory');
			if(mandatoryStatus){
				var selectedFieldLabel = selectedFieldInfo.label;
				this.lockMandatoryOptionInSelect2(selectedFieldLabel);
			}
		}
	},
	
	/**
	 * Function to handle on change of target module
	 */
	registerEventToHandleChangeofTargetModule : function(){
		var thisInstance =this;
		var editViewForm = this.getForm();
		editViewForm.find('[name="targetmodule"]').on('change',function(e){
			var element = jQuery(e.currentTarget);
			var targetModule = element.val();
			var existingTargetModule = thisInstance.targetModule;
			
			if(existingTargetModule == targetModule){
				return;
			}
			
			var params = {
			"module" : app.getModuleName(),
			"parent" : app.getParentModuleName(),
			"view" : "GetSourceModuleFields",
			"sourceModule" : targetModule
			}
            app.helper.showProgress();
			app.request.get({data:params}).then(
				function(error, data){
					if(data){
                        app.helper.hideProgress();
						editViewForm.find('.targetFieldsTableContainer').html(data);
						thisInstance.targetFieldsTable = editViewForm.find('[name="targetModuleFields"]');
						thisInstance.setTargetModule(targetModule);
						thisInstance.registerBasicEvents();
						thisInstance.eventToHandleChangesForReferenceFields();
					}
             });
          })
	},
	
	/**
	 * Function to add floatNone and displayInlineBlock class for
	 * add-on element in a form
	 */
	addExternalStylesForElement : function(){
		var editViewForm = this.getForm();
		var targetModuleFieldsTable = this.getSourceModuleFieldTable();
		var addOnElementExist = editViewForm.find('.add-on');
		var parentInputPrepend = addOnElementExist.closest('.input-prepend');
		if(parentInputPrepend.length > 0 && (!parentInputPrepend.hasClass('input-append'))){
			parentInputPrepend.find('.add-on').addClass('overWriteAddOnStyles');
		}
		targetModuleFieldsTable.find('input.timepicker-default').removeClass('input-small');
		targetModuleFieldsTable.find('textarea').removeClass('input-xxlarge').css('width',"80%");
		targetModuleFieldsTable.find('input.currencyField').css('width',"210px")
	},
	
	/**
	 * Function to register Basic Events
	 */
	registerBasicEvents : function(){
        var editViewForm = this.getForm();
		vtUtils.applyFieldElementsView(editViewForm);
		this.registerOnChangeEventForSelect2();
		this.registerEventForRemoveTargetModuleField();
		this.registerEventForMarkRequiredField();
		this.triggerLockMandatoryFieldOptions();
		this.addExternalStylesForElement();
		//api to support target module fields sortable
		this.makeMenuItemsListSortable();
		this.registerEventForFieldsSaveOrder();
		this.arrangeSelectedChoicesInOrder();
		this.registerEventToHandleOnChangeOfOverrideValue();
		this.registerAutoCompleteFields(editViewForm);
		//Document file fields handling
		this.registerAddFileFieldEvent(editViewForm);
		this.registerFileFieldRemoveEvent(editViewForm);
	},

	fileFieldsLimit: 5,
	/**
	 * Function to handle add file field click event
	 * @param form - Edit view form.
	 */
	registerAddFileFieldEvent: function (form) {
		var self = this;
		var container = jQuery(form);
		container.find('#addFileFieldBtn').click(function (e) {
			e.preventDefault();
			var fileFieldsCount = parseInt(jQuery('#fileFieldsCount').val());
			if (fileFieldsCount >= self.fileFieldsLimit) {
				app.helper.showAlertNotification({message: app.vtranslate('JS_MAX_FILE_FIELDS_LIMIT', self.fileFieldsLimit)});
				return false;
			}
			var fieldIndex = jQuery('#fileFieldNextIndex').val();
			var html = '<tr>\n\
							<td style="vertical-align: middle;">\n\
								<input type="text" class="inputElement nameField" name="file_field['+fieldIndex+'][fieldlabel]" data-rule-required="true">\n\
							</td>\n\
							<td class="textAlignCenter" style="vertical-align: middle;">\n\
								<input type="checkbox" name="file_field['+fieldIndex+'][required]" value="1">\n\
							</td>\n\
							<td class="textAlignCenter" style="vertical-align: middle;">\n\
								<a class="removeFileField" style="color: black;"><i class="fa fa-trash icon-trash"></i></a>\n\
							</td>\n\
						</tr>';
			container.find('table#fileFieldsTable').find('tbody').append(html);
			jQuery('#fileFieldNextIndex').val(parseInt(fieldIndex)+1);
			jQuery('#fileFieldsCount').val(fileFieldsCount+1);
			container.find('.noFileField').addClass('hide');
			self.registerFileFieldRemoveEvent(form);
		});
	},
	/**
	 * Function to handle remove file field click event
	 * @param container - Edit view form.
	 */
	registerFileFieldRemoveEvent: function (container) {
		container = jQuery(container);
		container.find('.removeFileField').off('click');
		container.find('.removeFileField').on('click', function (e) {
			e.preventDefault();
			var fileFieldsCount = parseInt(jQuery('#fileFieldsCount').val());
			jQuery(e.currentTarget).closest('tr').remove();
			jQuery('#fileFieldsCount').val(fileFieldsCount-1);
			if (container.find('#fileFieldsTable').find('tr:visible').length == 1) {
				container.find('.noFileField').removeClass('hide');
			}
		});
	},
	
	/**
	 * Function to handle onchange event of override values
	 */
	registerEventToHandleOnChangeOfOverrideValue : function() {
		var thisInstance = this;
		var container  = this.getSourceModuleFieldTable();
		var fieldRows = container.find('tr.listViewEntries');
		jQuery(fieldRows).each(function(key,value){
			var fieldRow = jQuery(value);
			var fieldName = fieldRow.data('name');
			var fieldType = fieldRow.data('type');
			if(fieldType == "multipicklist"){
				fieldName = fieldName+'[]';
			}
			fieldRow.find('[name="'+fieldName+'"]').on('change',function(e){
				var element = jQuery(e.currentTarget);
				var value = jQuery.trim(element.val());
				var mandatoryField = fieldRow.find('.mandatoryField');
				var hiddenField = fieldRow.find('.hiddenField');
				if((value == "") && (mandatoryField.is(':checked')) && (hiddenField.is(':checked'))){
					hiddenField.attr('checked',false);
					thisInstance.showErrorMessage(app.vtranslate('JS_MANDATORY_FIELDS_WITHOUT_OVERRIDE_VALUE_CANT_BE_HIDDEN'));
					return;
				}
			})
		})
	},
	
	/**
	 * Function to regiser the event to make the menu items list sortable
	 */
	makeMenuItemsListSortable : function() {
		var selectElement = jQuery('#fieldsList');
		var select2Element = app.helper.getSelect2FromSelect(selectElement);
		//TODO : peform the selection operation in context this might break if you have multi select element in menu editor
		//The sorting is only available when Select2 is attached to a hidden input field.
		var select2ChoiceElement = select2Element.find('ul.select2-choices');
		select2ChoiceElement.sortable({
			'containment': select2ChoiceElement,
			start: function() {  },
			update: function() { 

			//If arragments of fileds is completed save field order button should be enabled
			 if(selectElement.val().length > 1){
				 jQuery('#saveFieldsOrder').attr('disabled',false);
			 }
			}
		});
	},
	
	/**
	 * Function to save fields order in a webform
	 */
	registerEventForFieldsSaveOrder : function(){
		var thisInstance = this;
		jQuery('#saveFieldsOrder').on('click',function(e, updateRows){
			if(typeof updateRows == "undefined"){
				updateRows = true;
			}
			var element = jQuery(e.currentTarget);
			var selectElement = jQuery('#fieldsList');
			var orderedSelect2Options = selectElement.select2("data");
			var i = 1;
			for(var j = 0;j < orderedSelect2Options.length;j++){
				var chosenOption = orderedSelect2Options[j];
				var chosenValue = chosenOption.id;
				jQuery('tr[data-name="selectedFieldsData['+chosenValue+'][defaultvalue]"]').find('.sequenceNumber').val(i++);
			}
			if(updateRows){
				thisInstance.arrangeFieldRowsInSequence();
				element.attr("disabled",true);
			}
		})
	},
	
	/**
	 * Function to arrange field rows according to selected sequence
	 */
	arrangeFieldRowsInSequence : function() {
		var selectElement = jQuery('#fieldsList');
		var orderedSelect2Options = selectElement.select2("data");
			
		//Arrange field rows according to selected sequence
		var totalFieldsSelected = orderedSelect2Options.length;
		var selectedFieldRows = jQuery('tr.listViewEntries');
		for(var index=totalFieldsSelected;index>0;index--){
			var rowInSequence = jQuery('[class="sequenceNumber"][value="'+index+'"]',selectedFieldRows).closest('tr');
			rowInSequence.insertAfter(jQuery('[name="targetModuleFields"]').find('[name="fieldHeaders"]'));
		}
	},
	
	/**
	 * Function to arrange selected choices in order
	 */
	arrangeSelectedChoicesInOrder : function(){
		this.arrangeFieldRowsInSequence();
		var selectElement = jQuery('#fieldsList');
		var select2Element = app.helper.getSelect2FromSelect(selectElement);
		var choicesContainer = select2Element.find('ul.select2-choices');
		var choicesList = choicesContainer.find('li.select2-search-choice');
		var selectedOptions = jQuery('tr.listViewEntries');
		for(var index=selectedOptions.length ; index > 0  ; index--) {
			var selectedRow = selectedOptions[index-1];
			var fieldLabel = jQuery(selectedRow).find('.fieldLabel').data('label');
			choicesList.each(function(choiceListIndex,element){
				var liElement = jQuery(element);
				if(liElement.find('div').html() == fieldLabel){
					choicesContainer.prepend(liElement);
					return false;
				}
			});
		}
	},
	
	/**
	 * Function which will register reference field clear event
	 * @params - container <jQuery> - element in which auto complete fields needs to be searched
	 */
	registerClearReferenceSelectionEvent : function(container) {
        container.on('click','.clearReferenceSelection', function(e){
            e.preventDefault();
            var element = jQuery(e.currentTarget);
            var parentTdElement = element.closest('td');
            if(parentTdElement.length == 0){
                parentTdElement = element.closest('.fieldValue');
            }
            var inputElement = parentTdElement.find('.inputElement');
            var fieldName = parentTdElement.find('.sourceField').attr("name");

            parentTdElement.find('.referencefield-wrapper').removeClass('selected');
            inputElement.removeAttr("disabled").removeAttr('readonly');
            inputElement.attr("value","");
            inputElement.val("");
            parentTdElement.find('input[name="'+fieldName+'"]').val("");
            element.addClass('hide');
            element.trigger(Vtiger_Edit_Js.referenceDeSelectionEvent);
        });
    },
	
	/**
	 * Function to register form for validation
	 */
	registerSubmitEvent : function(){
		var editViewForm = this.getForm();
		editViewForm.submit(function(e){
			//Form should submit only once for multiple clicks also
			if(typeof editViewForm.data('submit') != "undefined") {
				return false;
			} else {
				if(editViewForm.validationEngine('validate')) {
					editViewForm.data('submit', 'true');
					var displayElementsInForm = jQuery( "input.referenceFieldDisplay" );
					if(typeof displayElementsInForm != "undefined"){
						var noData;
						if(displayElementsInForm.length > 1){
							jQuery(displayElementsInForm).each(function(key,value){
								var element = jQuery(value);
								var parentRow = element.closest('tr');
								var fieldValue = parentRow.find('.sourceField').val()
								var mandatoryField = parentRow.find('.mandatoryField');
								if(((fieldValue == '') || (fieldValue == 0)) && (mandatoryField.is(':checked'))){
									noData = true;
									return false;
								}
							})
						}else if(displayElementsInForm.length == 1){
							var parentRow = displayElementsInForm.closest('tr');
							var fieldValue = parentRow.find('.sourceField').val()
							var mandatoryField = parentRow.find('.mandatoryField');
							if(((fieldValue == '')  || (fieldValue == 0)) && (mandatoryField.is(':checked'))){
								noData = true;
							}
						}
					}
					if(noData){
                        app.helper.showErrorNotification({"message":app.vtranslate('JS_REFERENCE_FIELDS_CANT_BE_MANDATORY_WITHOUT_OVERRIDE_VALUE')});
						editViewForm.removeData('submit');
						return false;
					}
					//on submit form trigger the recordPreSave event
					var recordPreSaveEvent = jQuery.Event(Vtiger_Edit_Js.recordPreSave);
					editViewForm.trigger(recordPreSaveEvent);
					if(recordPreSaveEvent.isDefaultPrevented()) {
						//If duplicate record validation fails, form should submit again
						editViewForm.removeData('submit');
						return false;
					}
				} else {
					//If validation fails, form should submit again
					editViewForm.removeData('submit');
					// to avoid hiding of error message under the fixed nav bar
					app.formAlignmentAfterValidation(editViewForm);
				}
			}
		})
	},
	
	/**
	 * This function will register before saving any record
	 */
	registerRecordPreSaveEvent : function(form) {
		var thisInstance = this;
        app.event.on(Vtiger_Edit_Js.recordPresaveEvent,function(e) {
            e.preventDefault();
            var displayElementsInForm = jQuery( "input.referenceFieldDisplay" );
            if(typeof displayElementsInForm != "undefined"){
                var noData;
                if(displayElementsInForm.length > 1){
                    jQuery(displayElementsInForm).each(function(key,value){
                        var element = jQuery(value);
                        var parentRow = element.closest('tr');
                        var fieldValue = parentRow.find('.sourceField').val()
                        var mandatoryField = parentRow.find('.mandatoryField');
                        if(((fieldValue == '') || (fieldValue == 0)) && (mandatoryField.is(':checked'))){
                            noData = true;
                            return false;
                        }
                    })
                }else if(displayElementsInForm.length == 1){
                    var parentRow = displayElementsInForm.closest('tr');
                    var fieldValue = parentRow.find('.sourceField').val()
                    var mandatoryField = parentRow.find('.mandatoryField');
                    if(((fieldValue == '')  || (fieldValue == 0)) && (mandatoryField.is(':checked'))){
                        noData = true;
                    }
                }
            }
            if(noData){
                app.helper.showErrorNotification({"message":app.vtranslate('JS_REFERENCE_FIELDS_CANT_BE_MANDATORY_WITHOUT_OVERRIDE_VALUE')});
                return false;
            }
            
			var webformName = jQuery('[name="name"]').val();
			 if(!(webformName in thisInstance.duplicateWebformNames)) {
				thisInstance.checkDuplicate().then(
                function() {
                     thisInstance.duplicateWebformNames[webformName] = true;
                     //clear submit handler to avoid deadlock
                     form.vtValidate({});
                     jQuery('#saveFieldsOrder').trigger('click',[false]);
					 window.onbeforeunload = null;
                     form.submit();
                }, function() {
                    thisInstance.duplicateWebformNames[webformName] = false;
                    thisInstance.showErrorMessage(app.vtranslate('JS_WEBFORM_WITH_THIS_NAME_ALREADY_EXISTS'));
                });
			 } else {
                if(thisInstance.duplicateWebformNames[webformName] == true){
                    //clear submit handler to avoid deadlock
                    form.vtValidate({});
                    jQuery('#saveFieldsOrder').trigger('click',[false]);
					window.onbeforeunload = null;
                    form.submit();
                    return true;
                } else {
                    thisInstance.showErrorMessage(app.vtranslate('JS_WEBFORM_WITH_THIS_NAME_ALREADY_EXISTS'));
                }
            }
        });
	},
	
	checkDuplicate : function(){
		var aDeferred = jQuery.Deferred();
		var webformName = jQuery('[name="name"]').val();
		var recordId = jQuery('[name="record"]').val();
		var params = {
			'module' : app.getModuleName(),
			'parent' : app.getParentModuleName(),
			'action' : 'CheckDuplicate',
			'name'	 : webformName,
			'record' : recordId
		};
		app.request.post({data:params}).then(
			function(e,res) {
                if(res.success) {
					aDeferred.reject(res);
				} else {
					aDeferred.resolve(res);
				}
			}
		);
		return aDeferred.promise();
	},
	
	/**
	 * Function to register form for validation
	 */
//	registerFormForValidation : function() {
//		var editViewForm = this.getForm();
//		editViewForm.submit(function(e){
//			var displayElementsInForm = jQuery( "input.referenceFieldDisplay" );
//			if(typeof displayElementsInForm != "undefined"){
//				var noData;
//				if(displayElementsInForm.length > 1){
//					jQuery(displayElementsInForm).each(function(key,value){
//						var element = jQuery(value);
//						var parentRow = element.closest('tr');
//						var fieldValue = parentRow.find('.sourceField').val()
//						var mandatoryField = parentRow.find('.mandatoryField');
//						if(((fieldValue == '') || (fieldValue == 0)) && (mandatoryField.is(':checked'))){
//							noData = true;
//							return false;
//						}
//					})
//				}else if(displayElementsInForm.length == 1){
//					var parentRow = displayElementsInForm.closest('tr');
//					var fieldValue = parentRow.find('.sourceField').val()
//					var mandatoryField = parentRow.find('.mandatoryField');
//					if(((fieldValue == '')  || (fieldValue == 0)) && (mandatoryField.is(':checked'))){
//						noData = true;
//					}
//				}
//			}
//			if(noData){
//                app.helper.showErrorNotification({"message":app.vtranslate('JS_REFERENCE_FIELDS_CANT_BE_MANDATORY_WITHOUT_OVERRIDE_VALUE')});
//				editViewForm.removeData('submit');
//				return false;
//			}
//		})
//        editViewForm.vtValidate();
//	},
    
    /**
     * Function makes the user list select element mandatory if the roundrobin is checked 
     */
    registerUsersListMandatoryOnRoundrobinChecked : function() {
        var roundrobinCheckboxElement = jQuery('[name="roundrobin"]');
        var userListSelectElement = jQuery('[data-name="roundrobin_userid"]');
        var userListLabelElement = userListSelectElement.closest('td').prev();
        if(!roundrobinCheckboxElement.is(':checked')) {
            userListLabelElement.find('span.redColor').addClass('hide');
            userListSelectElement.addClass('ignore-validation');
        }
        roundrobinCheckboxElement.change(function(){
            if(jQuery(this).is(':checked')){
                userListLabelElement.find('span.redColor').removeClass('hide');
                userListSelectElement.removeClass('ignore-validation');
                userListSelectElement.valid();
            } else{
                userListLabelElement.find('span.redColor').addClass('hide');
                userListSelectElement.addClass('ignore-validation');
                var select2Element = app.helper.getSelect2FromSelect(userListSelectElement);
                select2Element.trigger('Vtiger.Validation.Hide.Messsage')
                .find('.input-error').removeClass('input-error');
            }
        });
    },
	
	/**
	 * Function to append popup reference module names if exist
	 */
	eventToHandleChangesForReferenceFields : function(){
		var thisInstance = this;
		var editViewForm = this.getForm();
		var referenceModule = editViewForm.find('[name="popupReferenceModule"]');
		if(referenceModule.length > 1){
			jQuery(referenceModule).each(function(key,value){
				var element = jQuery(value);
				thisInstance.appendPopupReferenceModuleName(element);
			})
		}else if(referenceModule.length == 1){
			thisInstance.appendPopupReferenceModuleName(referenceModule);
		}
	},
	
	appendPopupReferenceModuleName : function(element){
		var referredModule = element.val();
		var fieldName = element.closest('tr').data('name');
		var referenceName = fieldName.split('[defaultvalue]');
		referenceName = referenceName[0]+'[referenceModule]';
		var html = '<input type="hidden" name="'+referenceName+'" value="'+referredModule+'" class="referenceModuleName"/>'
		element.closest('td').append(html);
		element.closest('td').find('[name="'+fieldName+'_display"]').addClass('referenceFieldDisplay').removeAttr('name');
	},
	
	setReferenceFieldValue : function(container, params) {
		var sourceField = container.find('input[class="sourceField"]').attr('name');
		var fieldElement = container.find('input[name="'+sourceField+'"]');
		var fieldDisplayElement = container.find('.referenceFieldDisplay');
		var popupReferenceModule = container.find('input[name="popupReferenceModule"]').val();

		var selectedName = params.name;
		var id = params.id;

		fieldElement.trigger(Vtiger_Edit_Js.referenceSelectionEvent, {'source_module' : popupReferenceModule, 'record' : id, 'selectedName' : selectedName});
        if(!fieldDisplayElement.length) {
            fieldElement.attr('value',id);
            fieldElement.val(selectedName);
        } else {
            fieldElement.val(id);
            fieldDisplayElement.val(selectedName);
			if(selectedName) {
				fieldDisplayElement.attr('readonly',true);
			}else {
				fieldDisplayElement.attr('readonly',false);
			}
        }
        
		if(selectedName) {
			fieldElement.parent().find('.clearReferenceSelection').removeClass('hide');
			fieldElement.parent().find('.referencefield-wrapper').addClass('selected');
		}else {
			fieldElement.parent().find('.clearReferenceSelection').addClass('hide');
			fieldElement.parent().find('.referencefield-wrapper').removeClass('selected');
		}
    },
	
	/**
	 * Function which will handle the registrations for the elements 
	 */
	registerEvents : function() {
            this._super();
            
		var form = this.getForm();
		this.registerEventToHandleChangeofTargetModule();
		var targetModule = form.find('[name="targetModule"]').val();
		this.setTargetModule(targetModule);
		this.registerUsersListMandatoryOnRoundrobinChecked();
        
		//api to support reference field related actions
		this.referenceModulePopupRegisterEvent(form);
		this.registerClearReferenceSelectionEvent(form);
		this.registerReferenceCreate(form);
		this.eventToHandleChangesForReferenceFields();
        
        //save api's
        this.registerRecordPreSaveEvent(form); 
        
 	    //this.registerSubmitEvent();
	}
})
