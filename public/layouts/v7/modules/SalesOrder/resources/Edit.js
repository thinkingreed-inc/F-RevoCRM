/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Inventory_Edit_Js("SalesOrder_Edit_Js",{},{
    
    
    /**
	 * Function to get popup params
	 */
	getPopUpParams : function(container) {
		var params = this._super(container);
        var sourceFieldElement = jQuery('input[class="sourceField"]',container);
		if(!sourceFieldElement.length) {
			sourceFieldElement = jQuery('input.sourceField',container);
		}

		if(sourceFieldElement.attr('name') == 'contact_id' || sourceFieldElement.attr('name') == 'potential_id') {
			var form = this.getForm();
			var parentIdElement  = form.find('[name="account_id"]');
			if(parentIdElement.length > 0 && parentIdElement.val().length > 0 && parentIdElement.val() != 0) {
				var closestContainer = parentIdElement.closest('td');
				params['related_parent_id'] = parentIdElement.val();
				params['related_parent_module'] = closestContainer.find('[name="popupReferenceModule"]').val();
			} else if(sourceFieldElement.attr('name') == 'potential_id') {
				parentIdElement  = form.find('[name="contact_id"]');
				if(parentIdElement.length > 0 && parentIdElement.val().length > 0) {
					closestContainer = parentIdElement.closest('td');
					params['related_parent_id'] = parentIdElement.val();
					params['related_parent_module'] = closestContainer.find('[name="popupReferenceModule"]').val();
				}
			}
        }
        return params;
    },
    
    /**
	 * Function to register event for enabling recurrence
	 * When recurrence is enabled some of the fields need
	 * to be check for mandatory validation
	 */
	registerEventForEnablingRecurrence : function(){
		var thisInstance = this;
		var form = this.getForm();
		var enableRecurrenceField = form.find('[name="enable_recurring"]');
		var recurringBlock = form.find('[data-block="Recurring Invoice Information"]');
		var validationToggleFields = recurringBlock.find('input, select, textarea')
			.filter('[name]')
			.not('[name="enable_recurring"]');

		var requiredOnEnableFields = ['recurring_frequency','start_period','payment_duration','invoicestatus','invoice_day','payment_due_type','payment_due_month','payment_due_day'];
		validationToggleFields.each(function() {
			var el = jQuery(this);
			var name = el.attr('name');
			var isRequired = el.attr('data-rule-required') === 'true'
				|| requiredOnEnableFields.indexOf(name) !== -1;
			el.data('original-required', isRequired);
		});
		// 必須の * は繰り返し有効・周期・支払い期限の指定方法に合わせて付け外しするため、テンプレートが付けた * は外す
		recurringBlock.find('td.fieldLabel span.redColor').remove();

		enableRecurrenceField.on('change',function(e){
			var element = jQuery(e.currentTarget);
			var addValidation = element.is(':checked');
			thisInstance.AddOrRemoveRequiredValidation(validationToggleFields, addValidation);
			thisInstance.updateScheduleFields();
		});
		thisInstance.AddOrRemoveRequiredValidation(validationToggleFields, enableRecurrenceField.is(':checked'));

		form.find('[name="recurring_frequency"], [name="payment_due_type"]').on('change', function() {
			thisInstance.updateScheduleFields();
		});
		thisInstance.updateScheduleFields();
	},

	/**
	 * 請求日は月単位の周期のときだけ入力できる
	 * 支払い期限の指定方法が「日数で指定」なら支払い期限（日数）、「日付で指定」なら支払い月・支払い日を表示する
	 */
	updateScheduleFields : function() {
		var form = this.getForm();
		var isEnabled = form.find('[name="enable_recurring"]').is(':checked');
		var monthBasedFrequencies = ['Monthly','Quarterly','Half-Yearly','Yearly'];
		var isMonthBased = monthBasedFrequencies.indexOf(form.find('[name="recurring_frequency"]').val()) !== -1;
		var isFixedDueDate = form.find('[name="payment_due_type"]').val() === 'Fixed Date';
		var daysAfterFields = form.find('[name="payment_duration"]');
		var fixedDateFields = form.find('[name="payment_due_month"], [name="payment_due_day"]');

		this.AddOrRemoveRequiredValidation(form.find('[name="invoice_day"]'), isEnabled && isMonthBased);
		this.AddOrRemoveRequiredValidation(daysAfterFields, isEnabled && !isFixedDueDate);
		this.AddOrRemoveRequiredValidation(fixedDateFields, isEnabled && isFixedDueDate);
		this.toggleFieldCells(daysAfterFields, !isFixedDueDate);
		this.toggleFieldCells(fixedDateFields, isFixedDueDate);
		this.updateRequiredMarks();
	},

	/**
	 * 繰り返し請求情報で、いま入力が必須の項目の見出しに * を付ける
	 * 繰り返し有効のときに必須となる項目のうち、入力できる状態のものが対象。
	 */
	updateRequiredMarks : function() {
		var form = this.getForm();
		var isEnabled = form.find('[name="enable_recurring"]').is(':checked');
		form.find('[data-block="Recurring Invoice Information"]').find('input, select').filter('[name]').each(function() {
			var field = jQuery(this);
			if (field.data('original-required') !== true) {
				return;
			}
			var label = field.closest('td.fieldValue').prev('td.fieldLabel');
			var mark = label.find('.recurringRequiredMark');
			var isRequired = isEnabled && !field.is(':disabled');
			if (isRequired && !mark.length) {
				label.prepend('<span class="redColor recurringRequiredMark">* </span>');
			} else if (!isRequired) {
				mark.remove();
			}
		});
	},

	/**
	 * 項目の見出しと入力欄のセルを表示・非表示にする
	 * 行のセルがすべて非表示になると、その行は高さがなくなり詰まって見える。
	 */
	toggleFieldCells : function(fields, show) {
		fields.each(function() {
			var valueCell = jQuery(this).closest('td');
			valueCell.toggle(show);
			valueCell.prev('td').toggle(show);
		});
	},

	AddOrRemoveRequiredValidation : function(dependentFieldsForValidation, addValidation) {
		jQuery(dependentFieldsForValidation).each(function(key,value){
			var relatedField = jQuery(value);
			var isOriginallyRequired = relatedField.data('original-required') === true;
			if(addValidation) {
				if(relatedField.is("select")) {
					relatedField.attr('disabled',false);
				}else {
					relatedField.removeAttr('disabled');
				}
				if(isOriginallyRequired) {
					relatedField.removeClass('ignore-validation').data('rule-required', true);
				}
			} else if(!addValidation) {
				relatedField.addClass('ignore-validation').removeAttr('data-rule-required');
				if(relatedField.is("select")) {
					relatedField.attr('disabled',true).trigger("change");
					var select2Element = app.helper.getSelect2FromSelect(relatedField);
					select2Element.trigger('Vtiger.Validation.Hide.Messsage');
					select2Element.find('a').removeClass('input-error');
				}else {
					relatedField.attr('disabled','disabled').trigger('Vtiger.Validation.Hide.Messsage').removeClass('input-error');
				}
			}
		});
	},
    
    /**
	 * Function to search module names
	 */
	searchModuleNames : function(params) {
        var aDeferred = jQuery.Deferred();
		if(typeof params.module == 'undefined') {
			params.module = app.getModuleName();
		}
		if(typeof params.action == 'undefined') {
			params.action = 'BasicAjax';
		}
		
		if(typeof params.base_record == 'undefined') {
			var record = jQuery('[name="record"]');
			var recordId = app.getRecordId();
			if(record.length) {
				params.base_record = record.val();
			} else if(recordId) {
				params.base_record = recordId;
			} else if(app.view() == 'List') {
				var editRecordId = jQuery('#listview-table').find('tr.listViewEntries.edited').data('id');
				if(editRecordId) {
					params.base_record = editRecordId;
				}
			}
		}
        
        // Added for overlay edit as the module is different
        if(params.search_module == 'Products' || params.search_module == 'Services') {
            params.module = 'SalesOrder';
        }

		app.request.get({'data':params}).then(
			function(error, data){
                if(error == null) {
                    aDeferred.resolve(data);
                }
			},
			function(error){
				aDeferred.reject();
			}
		)
		return aDeferred.promise();
    },
    
    /**
	 * Function which will register event for Reference Fields Selection
	 */
	registerReferenceSelectionEvent : function(container) {
		this._super(container);
		var self = this;
		
		jQuery('input[name="account_id"]', container).on(Vtiger_Edit_Js.referenceSelectionEvent, function(e, data){
			self.referenceSelectionEventHandler(data, container);
		});
	},
        registerBasicEvents: function(container){
            this._super(container);
            this.registerEventForEnablingRecurrence();
            this.registerForTogglingBillingandShippingAddress();
            this.registerEventForCopyAddress();
        },
    
});