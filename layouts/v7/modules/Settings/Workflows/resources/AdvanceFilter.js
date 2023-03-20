/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
Vtiger_AdvanceFilter_Js('Workflows_AdvanceFilter_Js',{},{

    validationSupportedFieldConditionMap : {
        'email' : ['e','n'],
        'date' : ['is'],
        'datetime' : ['is']
    },
    //Hols field type for which there is validations always needed
    allConditionValidationNeededFieldList : ['double', 'integer'],

    // comparators which do not have any field Specific UI.
    comparatorsWithNoValueBoxMap : ['has changed','is empty','is not empty', 'is added'],

    getFieldSpecificType : function(fieldSelected) {
        var fieldInfo = fieldSelected.data('fieldinfo');
        var type = fieldInfo.type;
        var workflowModule = jQuery('[name="module_name"]').val();
        var calendarModules = ["Calendar", "Events"];
        if(workflowModule == 'Calendar') {
            if(fieldInfo.name == 'due_date') {
                type = 'date';
            }
        }
        if(calendarModules.includes(workflowModule)){
            if(fieldInfo.name == 'reminder_time'){
                type = 'integer';
            }
        }
        return type;
    },
	
    getModuleName : function() {
        return app.getModuleName();
    },


    /**
	 * Function to add new condition row
	 * @params : condtionGroupElement - group where condtion need to be added
	 * @return : current instance
	 */
    addNewCondition : function(conditionGroupElement){
        var basicElement = jQuery('.basic',conditionGroupElement);
        var newRowElement = basicElement.find('.conditionRow').clone(true,true);
        jQuery('select',newRowElement).addClass('select2');
        var conditionList = jQuery('.conditionList', conditionGroupElement);
        conditionList.append(newRowElement);

        //change in to chosen elements
        vtUtils.showSelect2ElementView(newRowElement.find('select.select2'));
        newRowElement.find('[name="columnname"]').find('optgroup:first option:first').attr('selected','selected').trigger('change');
        return this;
    },

    /**
	 * Function to load condition list for the selected field
     * (overrrided to remove "has changed" condition for related record fields in workflows)
	 * @params : fieldSelect - select element which will represents field list
	 * @return : select element which will represent the condition element
	 */
    loadConditions : function(fieldSelect) {
        var row = fieldSelect.closest('div.conditionRow');
        var conditionSelectElement = row.find('select[name="comparator"]');
        var conditionSelected = conditionSelectElement.val();
        var fieldSelected = fieldSelect.find('option:selected');
        var fieldLabel = fieldSelected.val();
        var match = fieldLabel.match(/\((\w+)\) (\w+)/);
        var fieldSpecificType = this.getFieldSpecificType(fieldSelected)
        var conditionList = this.getConditionListFromType(fieldSpecificType);
        //for none in field name
        if(typeof conditionList == 'undefined') {
            conditionList = {};
            conditionList['none'] = '';
        }
		var options = '';
		for(var key in conditionList) {
			//IE Browser consider the prototype properties also, it should consider has own properties only.
			if(conditionList.hasOwnProperty(key)) {
				var conditionValue = conditionList[key];
				var conditionLabel = this.getConditionLabel(conditionValue);
				if(match != null){
					if(conditionValue != 'has changed'){
						options += '<option value="'+conditionValue+'"';
						if(conditionValue == conditionSelected){
							options += ' selected="selected" ';
						}
						options += '>'+conditionLabel+'</option>';
					}
				}else{
					options += '<option value="'+conditionValue+'"';
					if(conditionValue == conditionSelected){
						options += ' selected="selected" ';
					}
					options += '>'+conditionLabel+'</option>';
				}
			}
		}
        conditionSelectElement.empty().html(options).trigger('change');
        // adding validation to comparator field
        conditionSelectElement.addClass('validate[required]');
        return conditionSelectElement;
    },
    
    /**
	 * Function to retrieve the values of the filter
	 * @return : object
	 */
    getValues : function() {
        var thisInstance = this;
        var filterContainer = this.getFilterContainer();

        var fieldList = new Array('columnname', 'comparator', 'value', 'valuetype', 'column_condition');

        var values = {};
        var columnIndex = 0;
        var conditionGroups = jQuery('.conditionGroup', filterContainer);
        conditionGroups.each(function(index,domElement){
            var groupElement = jQuery(domElement);

            var conditions = jQuery('.conditionList .conditionRow',groupElement);
            if(conditions.length <=0) {
                return true;
            }

            var iterationValues = {};
            conditions.each(function(i, conditionDomElement){
                var rowElement = jQuery(conditionDomElement);
                var fieldSelectElement = jQuery('[name="columnname"]', rowElement);
                var valueSelectElement = jQuery('[data-value="value"]',rowElement);
                //To not send empty fields to server
                if(thisInstance.isEmptyFieldSelected(fieldSelectElement)) {
                    return true;
                }
                var fieldDataInfo = fieldSelectElement.find('option:selected').data('fieldinfo');
                var fieldType = fieldDataInfo.type;
                var rowValues = {};

                // Workflowアクション設定：picklist型処理をString型と同じ処理にするため、条件変更
                // Workflowアクション設定：multipicklist型処理をString型と同じ処理にするため、条件変更
				for(var key in fieldList) {
                    var field = fieldList[key];
                    if(field == 'value'){
                        if((fieldType == 'date' || fieldType == 'datetime') && valueSelectElement.length > 0) {
                            var value = valueSelectElement.val();
                            var dateFormat = app.getDateFormat();
                            var dateFormatParts = dateFormat.split("-");
                            var valueArray = value.split(',');
                            for(i = 0; i < valueArray.length; i++) {
                                var valueParts = valueArray[i].split("-");
                                var dateInstance = new Date(valueParts[dateFormatParts.indexOf('yyyy')], parseInt(valueParts[dateFormatParts.indexOf('mm')]) - 1, valueParts[dateFormatParts.indexOf('dd')]);
                                if(!isNaN(dateInstance.getTime())) {
                                    valueArray[i] = app.getDateInVtigerFormat('yyyy-mm-dd', dateInstance);
                                }
                            }
                            rowValues[field] = valueArray.join(',');
                        } else {
                            rowValues[field] = valueSelectElement.val();
                        }
                    }  else {
                        rowValues[field] = jQuery('[name="'+field+'"]', rowElement).val();
                    }
                }

                if(jQuery('[name="valuetype"]', rowElement).val() == 'false' || (jQuery('[name="valuetype"]', rowElement).length == 0)) {
                    rowValues['valuetype'] = 'rawtext';
                }

                if(index == '0') {
                    rowValues['groupid'] = '0';
                } else {
                    rowValues['groupid'] = '1';
                }

                if(rowElement.is(":last-child")) {
                    rowValues['column_condition'] = '';
                }
                iterationValues[columnIndex] = rowValues;
                columnIndex++;
            });

            if(!jQuery.isEmptyObject(iterationValues)) {
                values[index+1] = {};
                //values[index+1]['columns'] = {};
                values[index+1]['columns'] = iterationValues;
            }
            if(groupElement.find('div.groupCondition').length > 0 && !jQuery.isEmptyObject(values[index+1])) {
                values[index+1]['condition'] = conditionGroups.find('div.groupCondition [name="condition"]').val();
            }
        });
        return values;

    },

    /**
	 * Functiont to get the field specific ui for the selected field
	 * @prarms : fieldSelectElement - select element which will represents field list
	 * @return : jquery object which represents the ui for the field
	 */
    getFieldSpecificUi : function(fieldSelectElement) {
        var fieldSelected = fieldSelectElement.find('option:selected');
        var fieldInfo = fieldSelected.data('fieldinfo');
        if(jQuery.inArray(fieldInfo.comparatorElementVal,this.comparatorsWithNoValueBoxMap) != -1){
            return jQuery('');
        } else {
            return this._super(fieldSelectElement);
        }
    }
});

Vtiger_Field_Js('Workflows_Field_Js',{},{

    getUiTypeSpecificHtml : function() {
        var uiTypeModel = this.getUiTypeModel();       
        return uiTypeModel.getUi();
    },

    getModuleName : function() {
        var currentModule = app.getModuleName();
        return currentModule;
    },

    /**
	 * Funtion to get the ui for the field  - generally this will be extend by the child classes to
	 * give ui type specific ui
	 * return <String or Jquery> it can return either plain html or jquery object
	 */
    getUi : function() {
        var html = '<input type="text" class="getPopupUi inputElement" name="'+ this.getName() +'"  /><input type="hidden" name="valuetype" value="'+this.get('workflow_valuetype')+'" />';
        html = jQuery(html);
        html.filter('.getPopupUi').val(app.htmlDecode(this.getValue()));
        return this.addValidationToElement(html);
    }
});

Workflows_Field_Js('Workflows_Text_Field_Js', {}, {
    getUi : function() {
        var html = '<textarea class="getPopupUi textarea inputElement" name="'+this.getName()+'" value="">'+this.getValue()+'</textarea>'+
        '<input type="hidden" name="valuetype" value="'+this.get('workflow_valuetype')+'" />';
        html = jQuery(html);
        return this.addValidationToElement(html);
    }
});

Vtiger_Date_Field_Js('Workflows_Date_Field_Js',{},{

    /**
	 * Function to get the user date format
	 */
    getDateFormat : function(){
        return this.get('date-format');
    },

    /**
	 * Function to get the ui
	 * @return - input text field
	 */
    getUi : function() {
        var comparatorSelectedOptionVal = this.get('comparatorElementVal');
        var dateSpecificConditions = this.get('dateSpecificConditions');
        if(comparatorSelectedOptionVal.length > 0) {
            if(comparatorSelectedOptionVal == 'between' || comparatorSelectedOptionVal == 'custom'){
                var html = '<div class="date"><input class="dateField inputElement" style="width:auto;" data-calendar-type="range" name="'+ this.getName() +'" data-date-format="'+ this.getDateFormat() +'" type="text" ReadOnly="true" value="'+  this.getValue() + '"></div>';
                var element = jQuery(html);
                return this.addValidationToElement(element);
            } else if(this._specialDateComparator(comparatorSelectedOptionVal)) {
                var html = '<input name="'+ this.getName() +'" type="text" value="'+this.getValue()+'" data-validation-engine="validate[funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" data-validator="[{"name":"PositiveNumber"}]">\n\
							<input type="hidden" name="valuetype" value="'+this.get('workflow_valuetype')+'" />';
                return jQuery(html);
            } else if (comparatorSelectedOptionVal in dateSpecificConditions) {
                var startValue = dateSpecificConditions[comparatorSelectedOptionVal]['startdate'];
                var endValue = dateSpecificConditions[comparatorSelectedOptionVal]['enddate'];
                var html = '<input name="'+ this.getName() +'"  type="text" ReadOnly="true" value="'+  startValue +','+ endValue +'">'
                return jQuery(html);
            } else if(comparatorSelectedOptionVal == 'is today' || comparatorSelectedOptionVal == 'is tomorrow' || comparatorSelectedOptionVal == 'is yesterday') {
            //show nothing
            }else {
                return this._super();
            }
        } else {
            var html = '<input type="text" class="getPopupUi date inputElement" name="'+ this.getName() +'"  data-date-format="'+ this.getDateFormat() +'"  value="'+  this.getValue() + '" />'+ 
            '<input type="hidden" name="valuetype" value="'+this.get('workflow_valuetype')+'" />' 
            var element = jQuery(html); 
            return this.addValidationToElement(element);
        }
    },

    _specialDateComparator : function(comp) {
        var specialComparators = ['less than days ago', 'more than days ago', 'in less than', 'in more than', 'days ago', 'days later', 'less than days later', 'more than days later'];
        for(var index in specialComparators) {
            if(comp == specialComparators[index]) {
                return true;
            }
        }
        return false;
    }
});

Vtiger_Date_Field_Js('Workflows_Datetime_Field_Js',{},{
    /**
	 * Function to get the user date format
	 */
    getDateFormat : function(){
        return this.get('date-format');
    },

    /**
	 * Function to get the ui
	 * @return - input text field
	 */
    getUi : function() {
        var comparatorSelectedOptionVal = this.get('comparatorElementVal');
        if(this._specialDateTimeComparator(comparatorSelectedOptionVal)) {
            var html = '<input name="'+ this.getName() +'" class="inputElement" type="text" value="'+this.getValue()+'" data-validator="[{name:PositiveNumber}]"><input type="hidden" name="valuetype" value="'+this.get('workflow_valuetype')+'" />';
            var element = jQuery(html);
        } else if(comparatorSelectedOptionVal == 'is today' || comparatorSelectedOptionVal == 'is tomorrow' || comparatorSelectedOptionVal == 'is yesterday') {
        // show nothing
        } else {
            var html = '<input type="text" class="getPopupUi datetime inputElement" name="'+ this.getName() +'"  data-date-format="'+ this.getDateFormat() +'"  value="'+  this.getValue() + '" />'+
            '<input type="hidden" name="valuetype" value="'+this.get('workflow_valuetype')+'" />'
            var element = jQuery(html);
        }
        return element;
    },

    _specialDateTimeComparator : function(comp) {
        var specialComparators = ['less than hours before', 'less than hours later', 'more than hours later', 'more than hours before', 'less than days ago', 'less than days later', 'more than days ago', 'more than days later', 'days ago', 'days later'];
        for(var index in specialComparators) {
            if(comp == specialComparators[index]) {
                return true;
            }
        }
        return false;
    }

});

Vtiger_Currency_Field_Js('Workflows_Currency_Field_Js',{},{

    getUi : function() {
        var html = '<input type="text" class="getPopupUi marginLeftZero currency inputElement" name="'+ this.getName() +'" value="'+  this.getValue() + '"  />'+
        '<input type="hidden" name="valuetype" value="'+this.get('workflow_valuetype')+'" />';
        var element = jQuery(html);
        return this.addValidationToElement(element);
    }

});

Vtiger_Time_Field_Js('Workflows_Time_Field_Js',{},{

    /**
	 * Function to get the ui
	 * @return - input text field
	 */
    getUi : function() {
        var html = '<input type="text" class="getPopupUi time inputElement" name="'+ this.getName() +'"  value="'+  this.getValue() + '" />'+
        '<input type="hidden" name="valuetype" value="'+this.get('workflow_valuetype')+'" />';
        var element = jQuery(html);
        return this.addValidationToElement(element);
    }
});

Vtiger_Field_Js('Vtiger_Percentage_Field_Js',{},{

    /**
	 * Function to get the ui
	 * @return - input percentage field
	 */
    getUi : function() {
        var html = '<input type="text" class="getPopupUi percent inputElement" name="'+ this.getName() +'" value="'+  this.getValue() + '" />'+
        '<input type="hidden" name="valuetype" value="'+this.get('workflow_valuetype')+'" />';
        var element = jQuery(html);
        return this.addValidationToElement(element);
    }
});

Vtiger_Field_Js('Vtiger_Text_Field_Js',{},{

    /**
	 * Function to get the ui
	 * @return - input text field
	 */
    getUi : function() {
        var html = '<input type="text" class="getPopupUi text inputElement" name="'+ this.getName() +'" value="'+  this.getValue() + '" />'+
        '<input type="hidden" name="valuetype" value="'+this.get('workflow_valuetype')+'" />';
        var element = jQuery(html);
        return this.addValidationToElement(element);
    }
});

Vtiger_Field_Js('Vtiger_Boolean_Field_Js',{},{

    /**
	 * Function to get the ui
	 * @return - input text field
	 */
    getUi : function() {
        var html = '<input type="text" class="getPopupUi boolean inputElement" name="'+ this.getName() +'" value="'+  this.getValue() + '" />'+
        '<input type="hidden" name="valuetype" value="'+this.get('workflow_valuetype')+'" />';
        var element = jQuery(html);
        return this.addValidationToElement(element);
    }
});

Vtiger_Owner_Field_Js('Workflows_Owner_Field_Js',{},{

    getUi : function() {
        // Workflowアクション設定：owner型で「項目選択」ができるよう、String型の getUi と同じ処理に変更
        var html = '<input type="text" class="getPopupUi inputElement" name="'+ this.getName() +'"  /><input type="hidden" name="valuetype" value="'+this.get('workflow_valuetype')+'" />';
        html = jQuery(html);
        // デフォルト値をIDではなくユーザー名で表示する
        var pickListValues = this.getPickListValues();
        var pickListValue = this.getValue();
        for(var optGroup in pickListValues){
            var optionGroupValues = pickListValues[optGroup];
            for(var option in optionGroupValues){
                if(option == this.getValue()){
                    pickListValue = optionGroupValues[this.getValue()];
                }
            }
        }
        html.filter('.getPopupUi').val(app.htmlDecode(pickListValue));
        return this.addValidationToElement(html);
    }
});

Vtiger_Owner_Field_Js('Workflows_Ownergroup_Field_Js',{},{
	getUi : function() {
		var html = '<select class="select2 inputElement" name="'+ this.getName() +'" id="field_'+this.getModuleName()+'_'+this.getName()+'">';
		var pickListValues = this.getPickListValues();
		var selectedOption = this.getValue();
        html += '<option value=""></option>';
			for(var option in pickListValues) {
				html += '<option value="'+option+'" ';
				if(option == selectedOption) {
					html += ' selected ';
				}
				html += '>'+pickListValues[option]+'</option>';
			}
		html +='</select>';
		var selectContainer = jQuery(html);
		this.addValidationToElement(selectContainer);
		return selectContainer;
	}
});

AdvanceFilter_Picklist_Field_Js('Workflows_Picklist_Field_Js',{},{

    getUi : function(){
        // Workflowアクション設定：picklist型で「項目選択」ができるよう、String型の getUi と同じ処理に変更
        var html = '<input type="text" class="getPopupUi inputElement" name="'+ this.getName() +'"  /><input type="hidden" name="valuetype" value="'+this.get('workflow_valuetype')+'" />';
        html = jQuery(html);
        html.filter('.getPopupUi').val(app.htmlDecode(this.getValue()));
        return this.addValidationToElement(html);
    }
});

Vtiger_Multipicklist_Field_Js('Workflows_Multipicklist_Field_Js', {}, {

	getUi : function () {
        // Workflowアクション設定：multipicklist型で「項目選択」ができるよう、String型の getUi と同じ処理に変更
        var html = '<input type="text" class="getPopupUi inputElement" name="'+ this.getName() +'" data-value="value"  /><input type="hidden" name="valuetype" value="'+this.get('workflow_valuetype')+'" />';
        html = jQuery(html);
        html.filter('.getPopupUi').val(app.htmlDecode(this.getValue()));
        return this.addValidationToElement(html);
	}
});

Vtiger_Reference_Field_Js("Workflows_Reference_Field_Js",{},{
    getUi : function(){
        var referenceModules = this.getReferenceModules();
        var value = this.getValue();
        var html = '<div class="referencefield-wrapper';
        if(value){
            html += ' selected';
        }
        html += '">';
        html += '<input name="popupReferenceModule" type="hidden" value="'+referenceModules[0]+'"/>';
        html += '<div class="input-group">'; 
        html += '<input class="sourceField" name="'+this.getName()+'" type="hidden" value="'+value+'"/>';
        html += '<span class="clearReferenceSelectionWrapper"><input class="autoComplete inputElement ui-autocomplete-input textOverflowEllipsis" type="text" data-fieldtype="reference" data-fieldmodule="'+this.get('workflow_field_modulename')+'" name="'+this.getName()+'_display" placeholder="Type to Search"';
        var reset = false;
        if(value){
            html += ' value="'+value+'" disabled="disabled"';
            reset = true;
        }
        html += '/>';
        
        if(reset){
            html += '<a href="#" class="clearReferenceSelection"><i class="fa fa-close p-l-8"></i></a>';
        }else {
            html += '<a href="#" class="clearReferenceSelection hide"><i class="fa fa-close p-l-8"></i></a>';
        }
        html += '</span>';
		html += '<div class="referenceLoadingWrapper hide"><svg class="referenceSpinner"><circle cx="20" cy="20" r="8"></circle><circle class="small" cx="20" cy="20" r="5"></circle></svg></div>';
        //popup search element
        html += '<span class="input-group-addon relatedPopup cursorPointer textAlignCenter" title="Select">';
        html += '<i class="fa fa-search p-l-8"></i>';
        html += '</span>';
        
        html += '</div>';
        html += '</div>'; 
        return this.addValidationToElement(html);
    }
});

Workflows_Reference_Field_Js("Workflows_Multireference_Field_Js",{},{});

Workflows_Field_Js('Workflows_Integer_Field_Js',{},{
	getUi : function() {
		if(this.getName() === 'profile_rating') {
			//Special handling for profile_rating field to show dropdown instead of input box as its integer field.
			var html = '<select class="select2 inputElement inlinewidth" name="'+ this.getName() +'" id="field_'+this.getModuleName()+'_'+this.getName()+'">';
			var pickListValues = {1 : 1, 2 : 2, 3 : 3, 4 : 4, 5 : 5};
			var selectedOption = parseInt(this.getValue());
			html += '<option value="">Select an Option</option>';
			for(var option in pickListValues) {
				html += '<option value="'+option+'" ';
				if(option == selectedOption) {
					html += ' selected ';
				}
				html += '>'+option+'</option>';
			}
			html +='</select>';
			var selectContainer = jQuery(html);
			this.addValidationToElement(selectContainer);
			return selectContainer;
		} else {
            var value = app.htmlDecode(this.getValue());
            value = value.replace(/"/g, "&quot;");
            var html = '<input value="'+value+'" type="text" class="getPopupUi inputElement" name="'+ this.getName() +'"  /><input type="hidden" name="valuetype" value="'+this.get('workflow_valuetype')+'" />';
            return this.addValidationToElement(jQuery(html));
		}
	}
});

