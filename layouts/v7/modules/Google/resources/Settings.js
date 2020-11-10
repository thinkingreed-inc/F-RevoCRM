/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/  

Vtiger.Class("Google_Settings_Js", {
    
    postSettingsLoad : "Google.Settings.load"
    
}, {
    
    getListContainer : function() {
        var container = jQuery('.listViewPageDiv');
        if(app.getParentModuleName() === 'Settings') {
            container = jQuery('.settingsPageDiv');
        }
        return container;
    },
    
    registerAuthorizeButton : function() {
        var container = this.getListContainer();
        container.on('click', '#authorizeButton', function(e){
                var element = jQuery(e.currentTarget);
                var url = element.data('url');
                if(url){
                    var win=window.open(url,'','height=600,width=600,channelmode=1');
                    //http://stackoverflow.com/questions/1777864/how-to-run-function-of-parent-window-when-child-window-closes 
                    window.sync = function() {
                        var settingsUrl = jQuery('[name=settingsPage]').val();
                        var params = {
                            url : settingsUrl
                        }
                        app.helper.showProgress();
                        app.request.pjax(params).then(function(error, data){
                            app.helper.hideProgress();
                            if(data) {
                                container.html(data);
                                app.event.trigger(Google_Settings_Js.postSettingsLoad, container);
                            }
                        });
                     }
                     
                     window.startSync = function() {
                     }
                     
                     win.onunload = function() {
                     }
                }
        });
    },
    
    registerFieldMappingClickEvent : function() {
        var thisInstance = this;
        jQuery('a#syncSetting').on('click',function(e) {
			var syncModule = jQuery(e.currentTarget).data('syncModule');
                var params = {
                    module : 'Google',
                    view : 'Setting',
                    sourcemodule : syncModule
                }
                app.helper.showProgress();
                app.request.post({data: params}).then(function(error, data) {
                    app.helper.hideProgress();
                    var callBackFunction = function() {
                        var container = jQuery('.googleSettings');
                        thisInstance.registerSettingsEventsForContacts(container);
                    }
                    var modalData = {
                        cb: callBackFunction
                    };
                    app.helper.showModal(data, modalData);
                });
        });
    },
    
    packFieldmappingsForSubmit : function(container) {
        var rows = container.find('div#googlesyncfieldmapping').find('table > tbody > tr');
        var mapping = {};
        jQuery.each(rows,function(index,row) {
            var tr = jQuery(row);
            var vtiger_field_name = tr.find('.vtiger_field_name').not('.select2-container').val();
            var google_field_name = tr.find('.google_field_name').val();
            var googleTypeElement = tr.find('.google-type').not('.select2-container');
            var google_field_type = '';
            var google_custom_label = '';
            if(googleTypeElement.length) {
                google_field_type = googleTypeElement.val();
                var customLabelElement = tr.find('.google-custom-label');
                if(google_field_type == 'custom' && customLabelElement.length) {
                    google_custom_label = customLabelElement.val();
                }
            }
            var map = {};
            map['vtiger_field_name'] = vtiger_field_name;
            map['google_field_name'] = google_field_name;
            map['google_field_type'] = google_field_type;
            map['google_custom_label'] = google_custom_label;
            mapping[index] = map;
        });
        return JSON.stringify(mapping);
    },
    
     validateFieldMappings : function(container) {
        var aDeferred = jQuery.Deferred();
        
		var customFieldLabels = jQuery('input.google-custom-label',container).filter('input:text').filter(function() {
            if(jQuery(this).val() == "") {
                return jQuery(this).css('visibility') == 'visible';
            }
        });
		
        if(customFieldLabels.length) {
			customFieldLabels.valid();
            aDeferred.reject();
        } else {
            aDeferred.resolve();
        }
        return aDeferred.promise();
    },
    
    registerSettingsEventsForContacts : function(container) {
        this.registerAddCustomFieldMappingEvent(container);
        this.registerDeleteCustomFieldMappingEvent(container);
        this.registerSaveSettingsEvent(container);
        this.registerVtigerFieldSelectOnChangeEvent(container);
        this.registerGoogleTypeChangeEvent(container);
		app.helper.showVerticalScroll(container.find('#googlesyncfieldmapping'), {'setHeight' : '400px'});

        jQuery('select.vtiger_field_name',container).trigger('change');
        jQuery('select.google-type',container).trigger('change');
    },
    
     registerSaveSettingsEvent : function(container) {
        var thisInstance = this;
		if(container.find('[name="contactsyncsettings"]').length) {
			container.find('[name="contactsyncsettings"]').vtValidate();
		
			container.find('button#save_syncsetting').on('click',function() {
				thisInstance.validateFieldMappings(container).then(function() {
					var form = container.find('form[name="contactsyncsettings"]');
					var fieldMapping = thisInstance.packFieldmappingsForSubmit(container);
					form.find('#user_field_mapping').val(fieldMapping);
					var serializedFormData = form.serialize();
					app.request.post({ data : serializedFormData}).then(function(data) {
						app.helper.hideModal();
					});
				});
			});
		}
    },
    
    registerAddCustomFieldMappingEvent : function(container) {
        var thisInstance = this;
        jQuery('.addCustomFieldMapping',container).on('click',function(e) {
            var currentSelectionElement = jQuery(this);
            var googleFields = JSON.parse(container.find('input#google_fields').val());
            var selectionType = currentSelectionElement.data('type');
            var vtigerFields = currentSelectionElement.data('vtigerfields');
            
            var vtigerFieldSelectElement = '<select class="vtiger_field_name col-sm-12" data-category="'+selectionType+'">';
            if(!Object.keys(vtigerFields).length) {
                alert(app.vtranslate('JS_SUITABLE_VTIGER_FIELD_NOT_AVAILABLE_FOR_MAPPING'));
                return;
            }
            
            var customMapElements = jQuery('select.vtiger_field_name[data-category="'+selectionType+'"]');
            var mappedCustomFields = [];
            jQuery.each(customMapElements,function(i,elem) {
                    mappedCustomFields.push(jQuery(elem).val());
            });
            var numberOfOptions = 0;
            jQuery.each(vtigerFields,function(fieldname,fieldLabel) {
                if(jQuery.inArray(fieldname,mappedCustomFields) === -1) {
                    numberOfOptions++;
                    var option = '<option value="'+fieldname+'">'+fieldLabel+'</option>';
                    vtigerFieldSelectElement += option;
                }
            });
            if(numberOfOptions == 0) {
                alert(app.vtranslate('JS_SUITABLE_VTIGER_FIELD_NOT_AVAILABLE_FOR_MAPPING'));
                return;
            }
            
            vtigerFieldSelectElement += '</select>';
            var googleTypeSelectElement = '';
            if(selectionType != 'custom') {
                googleTypeSelectElement = '<input type="hidden" class="google_field_name" value="'+ googleFields[selectionType]['name'] +'" />\n\
                                               <select class="google-type col-sm-5" data-category="'+selectionType+'">';
                
                var allCategorizedSelects = jQuery('select.google-type[data-category="'+selectionType+'"]');
                var selectedValues = [];

                jQuery.each(allCategorizedSelects, function(i, selectElement){
                    if(jQuery(selectElement).val() !== 'custom') {
                        selectedValues.push(jQuery(selectElement).val());
                    }
                });
                jQuery.each(googleFields[selectionType]['types'],function(index,fieldtype) {
                    if(jQuery.inArray(fieldtype, selectedValues) === -1) {
                        var option = '<option value="'+fieldtype+'">'+app.vtranslate(selectionType)+' ('+app.vtranslate(fieldtype)+')'+'</option>';
                        googleTypeSelectElement += option;
                    }
                });
                googleTypeSelectElement += '</select>\n\
                                 &nbsp;&nbsp;<input type="text" class="google-custom-label inputElement" style="visibility:hidden;width:40%" data-rule-required="true" />';
            } else {
                googleTypeSelectElement = '<input type="hidden" class="google_field_name" value="'+ googleFields[selectionType]['name'] +'" />';
                googleTypeSelectElement += '<input type="hidden" class="google-type" value="'+selectionType+'" />';
                googleTypeSelectElement += '<input type="text" class="google-custom-label inputElement" style="width:40%" data-rule-required="true" />';
            }
            var tabRow = '<tr>\n\
                            <td>' + vtigerFieldSelectElement + '</td>\n\
                            <td>' + googleTypeSelectElement + '<a class="deleteCustomMapping marginTop7px pull-right"><i title="Delete" class="fa fa-trash"></i></a></td>\n\
                          </tr>';
            var tbodyElement = container.find('div#googlesyncfieldmapping').find('table > tbody');
            tbodyElement.append(tabRow);
            var lastRow = container.find('div#googlesyncfieldmapping').find('table > tbody > tr').filter(':last');
            vtUtils.showSelect2ElementView(lastRow.find('select'));
            thisInstance.registerDeleteCustomFieldMappingEvent(lastRow);
            thisInstance.registerVtigerFieldSelectOnChangeEvent(container,lastRow.find('select.vtiger_field_name'));
            thisInstance.registerGoogleTypeChangeEvent(container,lastRow.find('select.google-type'));
            lastRow.find('select.vtiger_field_name').trigger('change');
            lastRow.find('select.google-type').trigger('change');
            
        });
    },
    
    registerDeleteCustomFieldMappingEvent : function(container) {
        jQuery('.deleteCustomMapping',container).on('click',function() {
            var currentRow = jQuery(this).closest('tr');
            var currentCategory = currentRow.find('select.vtiger_field_name').data('category');
            currentRow.remove();
            jQuery('select.vtiger_field_name[data-category="'+currentCategory+'"]').trigger('change');
            jQuery('select.google-type[data-category="'+currentCategory+'"]').trigger('change');
        });
    },
    
    updateSelectElement : function(allValuesMap, selectedValues, element) {
        var prevSelectedValues = element.val();
        element.html('');
        for(var value in allValuesMap) {
           element.append(jQuery('<option></option>').attr('value', value).text(app.vtranslate(allValuesMap[value])));
        }
        for(var index in selectedValues) {
            if (jQuery.inArray(selectedValues[index], [prevSelectedValues]) === -1) {
                var strInputString = selectedValues[index].replace(/'/g, "\\'");
                element.find("option[value='"+strInputString+"']").remove();
            }
        }
        if(prevSelectedValues) {
            element.select2("val", prevSelectedValues);
        }
   },
    
    removeOptionFromSelectList : function(selectElement,optionValue,category) {
        var sourceSelectElement = jQuery(selectElement);
        var categorisedSelectElements = jQuery('select.vtiger_field_name[data-category="'+category+'"]');
        jQuery.each(categorisedSelectElements,function(index,categorisedSelectElement) {
            var currentSelectElement = jQuery(categorisedSelectElement);
            if(!currentSelectElement.is(sourceSelectElement)) {
                var optionElement = currentSelectElement.find('option[value="'+optionValue+'"]');
                if(optionElement.length) {
                    optionElement.remove();
                    currentSelectElement.select2();
                }
            }
        });
    },
    
    registerVtigerFieldSelectOnChangeEvent : function(container,selectElement) {
        var thisInstance = this;
        if(typeof selectElement === 'undefined') {
            selectElement = jQuery('select.vtiger_field_name',container);   
        }
        selectElement.on('change', function(e){
            var element = jQuery(e.currentTarget);
            var category = element.data('category');
            
            var allCategorizedSelects = jQuery('select.vtiger_field_name[data-category="'+category+'"]');
            var selectedValues = [];
            
            jQuery.each(allCategorizedSelects, function(i, selectElement){
                selectedValues.push($(selectElement).val());
            });
            
            jQuery.each(allCategorizedSelects, function(i, selectElement){
                if(e.currentTarget !== selectElement || allCategorizedSelects.length == 1) {
                    var allCategoryFieldLabelValues = jQuery('li.addCustomFieldMapping[data-type="'+category+'"]').data('vtigerfields');
                    thisInstance.updateSelectElement(allCategoryFieldLabelValues, selectedValues, jQuery(selectElement));
                }
            });
        });
    },
    
    registerGoogleTypeChangeEvent : function(container,selectElement) {
        var thisInstance = this;
        
        if(typeof selectElement === 'undefined') {
            selectElement = jQuery('select.google-type',container);
        }

        selectElement.on('change',function(e) {
            var element = jQuery(e.currentTarget);
            var category = element.data('category');
            
            var currentTarget = element;
            var val = currentTarget.val();
            if(val == 'custom') {
                currentTarget.closest('td').find('input.google-custom-label').css('visibility','visible');
            } else {
                currentTarget.closest('td').find('input.google-custom-label').css('visibility','hidden');
            }
            
            var allCategorizedSelects = jQuery('select.google-type[data-category="'+category+'"]');
            var selectedValues = [];
            
            jQuery.each(allCategorizedSelects, function(i, selectElement){
                if(jQuery(selectElement).val() !== 'custom') {
                    selectedValues.push(jQuery(selectElement).val());
                }
            });

            var googleFields = JSON.parse(container.find('input#google_fields').val());
            var allValues = {};
            jQuery.each(googleFields[category]['types'],function(index,value) {
                allValues[value] = app.vtranslate(category)+' ('+app.vtranslate(value)+')';
            });
            
            jQuery.each(allCategorizedSelects, function(i, selectElement){
                var allCategoryFieldLabelValues = allValues;
                thisInstance.updateSelectElement(allCategoryFieldLabelValues, selectedValues, jQuery(selectElement));
            });
        });
        
    },
    
    registerBasicEvents : function() {
        this.registerFieldMappingClickEvent();
        vtUtils.applyFieldElementsView(this.getListContainer());
    },
    
    registerEvents : function() {
        var thisInstance = this;
        this.registerAuthorizeButton();
        app.event.on(Google_Settings_Js.postSettingsLoad,function(){
            thisInstance.registerBasicEvents();
        });
    }
});