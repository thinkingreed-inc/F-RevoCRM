/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
Vtiger_Field_Js("Webforms_Field_Js",{},{})

Vtiger_Field_Js('Webforms_Multipicklist_Field_Js',{},{
	/**
	 * Function to get the pick list values
	 * @return <object> key value pair of options
	 */
	getPickListValues : function() {
		return this.get('picklistvalues');
	},
	
	/**
	 * Function to get the ui
	 * @return - select element and chosen element
	 */
	getUi : function() {
		var html = '<select class="select2 inputElement" multiple name="'+ this.getName() +'[]" style="width:60%">';
		var pickListValues = this.getPickListValues();
		var selectedOption = this.getValue();
		if(selectedOption !== null) {
			var selectedOptionsArray = selectedOption.split(' |##| ');
		} else {
			selectedOptionsArray = {};
		}
		for(var option in pickListValues) {
			html += '<option value="'+option+'" ';
			if(jQuery.inArray(option,selectedOptionsArray) != -1){
				html += ' selected ';
			}
			html += '>'+pickListValues[option]+'</option>';
		}
		html +='</select>';
		var selectContainer = jQuery(html);
		return selectContainer;
	}
});

Vtiger_Field_Js('Webforms_Picklist_Field_Js',{},{

	/**
	 * Function to get the pick list values
	 * @return <object> key value pair of options
	 */
	getPickListValues : function() {
		return this.get('picklistvalues');
	},

	/**
	 * Function to get the ui
	 * @return - select element and chosen element
	 */
	getUi : function() {
		var html = '<select class="select2 inputElement" name="'+ this.getName() +'" style="width:220px">';
		var pickListValues = this.getPickListValues();
		var selectedOption = this.getValue();
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

Vtiger_Field_Js('Webforms_Date_Field_Js',{},{

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
		var html = '<div class="input-append">'+
						'<div class="date">'+
							'<input class="dateField inputElement" style="width:auto;" type="text" name="'+ this.getName() +'"  data-date-format="'+ this.getDateFormat() +'"  value="'+  this.getValue() + '" />'+
							'<span class="add-on"><i class="icon-calendar"></i></span>'+
						'</div>'+
					'</div>';
		var element = jQuery(html);
		return this.addValidationToElement(element);
	}
});

Vtiger_Field_Js('Webforms_Currency_Field_Js',{},{

	/**
	 * get the currency symbol configured for the user
	 */
	getCurrencySymbol : function() {
		return this.get('currency_symbol');
	},

	getUi : function() {
		var html = '<div class="input-prepend">'+
						'<span class="add-on">'+ this.getCurrencySymbol()+'</span>'+
						'<input type="text" name="'+ this.getName() +'" value="'+  this.getValue() + '" class="input-medium inputElement" style="width:210px" data-decimal-separator="'+this.getData().decimalSeparator+'" data-group-separator="'+this.getData().groupSeparator+'"/>'+
					'</div>';
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
		var html = '<div class="input-append row-fluid">'+
									'<input type="number" class="input-medium inputElement" min="0" max="100" name="'+this.getName() +'" value="'+  this.getValue() + '" step="any"/>'+
									'<span class="add-on">%</span>'+
					'</div>';
		var element = jQuery(html);
		return this.addValidationToElement(element);
	}
});

Vtiger_Field_Js('Webforms_Time_Field_Js',{},{

	/**
	 * Function to get the ui
	 * @return - input text field
	 */
	getUi : function() {
		var html = '<div class="input-append time">'+
							'<input class="timepicker-default inputElement" type="text" name="'+ this.getName() +'"  value="'+  this.getValue() + '" />'+
							'<span class="add-on"><i class="icon-time"></i></span>'+
					'</div>';
		var element = jQuery(html);
		return this.addValidationToElement(element);
	}
});


Vtiger_Field_Js('Webforms_Reference_Field_Js',{},{
	
	getReferenceModules : function(){
		return this.get('referencemodules');
	},

	/**
	 * Function to get the ui
	 * @return - input text field
	 */
	getUi : function() {
		var referenceModules = this.getReferenceModules();
        var value = this.getValue();
		var fieldName = this.getName();
        var html = '<div class="referencefield-wrapper';
        if(value){
            html += 'selected';
        } else {
            html += '"';
        }
        html += '">';
        html += '<input name="popupReferenceModule" type="hidden" value="'+referenceModules[0]+'"/>';
        html += '<div class="input-group ">'
        html += '<input name="'+ fieldName +'" type="hidden" value="'+ value + '" class="sourceField" />';
        html += '<input id="'+ fieldName +'_display" name="'+ fieldName +'_display" value="'+ value + '" data-fieldname="'+ fieldName +'" data-fieldtype="reference" type="text" class="marginLeftZero autoComplete inputElement referenceFieldDisplay" placeholder="'+app.vtranslate('JS_TYPE_TO_SEARCH')+'"';
				
        var reset = false;
        if(value){
            html += ' value="'+value+'" disabled="disabled"';
            reset = true;
        }
        html += '/>';
					
        if(reset){
            html += '<a href="#" class="clearReferenceSelection" > x </a>';
        }else {
            html += '<a href="#" class="clearReferenceSelection hide"> x </a>';
	}
        //popup search element
        html += '<span class="input-group-addon relatedPopup cursorPointer" title="'+referenceModules[0]+'">';
        html += '<i class="fa fa-search"></i>';
        html += '</span>';
        
        html += '</div>';
        html += '<span class="createReferenceRecord cursorPointer clearfix">'+
                   '<i class="fa fa-plus"></i>'+
				'</span>';
        html += '</div>'; 
        
         return this.addValidationToElement(html);
    }
});

Vtiger_Field_Js('Webforms_Image_Field_Js',{},{

	/**
	 * Function to get the ui
	 * @return - input text field
	 */
	getUi : function() {
		var html =	'<input class="input-large inputElement" type="text" name="'+ this.getName() +'" readonly />';
		var element = jQuery(html);
		return this.addValidationToElement(element);
	}
});