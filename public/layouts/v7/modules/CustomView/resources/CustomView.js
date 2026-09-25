/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

jQuery.Class("Vtiger_CustomView_Js",{
},{
	contianer : false,

	advanceFilterInstance : false,

	columnListSelect2Element : false,

	columnSelectElement : false,

	reIntialize : function () {
		this.contianer = false;
		this.columnListSelect2Element = false;
		this.advanceFilterInstance = false;
		this.columnSelectElement = false;
	},

	getContainer : function() {
		if(this.container == false) {
			this.container = jQuery('#filterContainer');
		}
		return this.container
	},

	getColumnListSelect2Element : function() {
		if(this.columnListSelect2Element == false){
			this.columnListSelect2Element = jQuery('#s2id_viewColumnsSelect');
		}
		return this.columnListSelect2Element;
	},

	/**
	 * Function to get the view columns selection element
	 * @return : jQuery object of view columns selection element
	 */
	getColumnSelectElement : function() {
		if(this.columnSelectElement == false) {
			this.columnSelectElement = jQuery('#viewColumnsSelect');
		}
		return this.columnSelectElement;
	},

	/**
	 * Function to regiser the event to make the columns list sortable
	 */
	makeColumnListSortable : function() {
		var select2Element = this.getColumnListSelect2Element();
		//TODO : peform the selection operation in context this might break if you have multi select element in advance filter
		//The sorting is only available when Select2 is attached to a hidden input field.
		var chozenChoiceElement = select2Element.find('ul.select2-choices');
		chozenChoiceElement.sortable({
				'containment': chozenChoiceElement,
				start: function() { },
				update: function() {}
			});
	},

	/**
	 * Function which will arrange the chosen element choices in order
	 */
	arrangeSelectChoicesInOrder : function() {
		var contentsContainer = this.getContainer();
		var chosenElement = this.getColumnListSelect2Element();
		var choicesContainer = chosenElement.find('ul.select2-choices');
		var choicesList = choicesContainer.find('li.select2-search-choice');
		var columnListSelectElement = this.getColumnSelectElement();
		var selectedOptions = columnListSelectElement.find('option:selected');
		var selectedOrder = JSON.parse(jQuery('input[name="columnslist"]', contentsContainer).val());

		for(var index=selectedOrder.length ; index > 0 ; index--) {
			var selectedValue = selectedOrder[index-1];
			var value = selectedValue.replace("'", "&#39;");
			var option = selectedOptions.filter('[value="'+value+'"]');
			choicesList.each(function(choiceListIndex,element){
				var liElement = jQuery(element);
				if(liElement.find('div').text() == option.text()){
					choicesContainer.prepend(liElement);
					return false;
				}
			});
		}
	},

	/**
	 * Function which will get the selected columns with order preserved
	 * @return : array of selected values in order
	 */
	getSelectedColumns : function() {
		var columnListSelectElement = this.getColumnSelectElement();
		var select2Element = this.getColumnListSelect2Element();

		var selectedValuesByOrder = new Array();
		var selectedOptions = columnListSelectElement.find('option:selected');

		var orderedSelect2Options = select2Element.find('li.select2-search-choice').find('div');
		orderedSelect2Options.each(function(index,element){
			var chosenOption = jQuery(element);
			selectedOptions.each(function(optionIndex, domOption){
				var option = jQuery(domOption);
				if(option.html() == chosenOption.html()) {
					selectedValuesByOrder.push(option.val());
					return false;
				}
			});
		});
		return selectedValuesByOrder;
	},

	doOperation : function (url) {
		var aDeferred = new jQuery.Deferred();
		app.helper.showProgress();
		app.request.get({'url':url}).then(function(error,data){
			app.helper.hideProgress();
			aDeferred.resolve(data);
		});

		return aDeferred.promise();
	}, 

	showCreateFilter : function(data){
		var self = this;
		self.reIntialize();
		app.helper.loadPageContentOverlay(data).then(function(data){
			data.find('.data').css('height','100%');
			var Options= {
			autoExpandScrollbar: true,
			scrollInertia: 200,
			autoHideScrollbar: true,

			mouseWheel: {
				enable: true,
				preventDefault: true,
				scrollAmount: 50
			}
		};
			app.helper.showVerticalScroll(jQuery('.customview-content '), Options);
			self.advanceFilterInstance = new Vtiger_AdvanceFilter_Js(data.find('.filterConditionsDiv'));
			self.registerFilterCreateEvents();
		});
	},

	saveFilter : function() {
		var aDeferred = jQuery.Deferred();
		var formElement = jQuery("#CustomView");

		var hasInvalidSort = false;
		var fieldsUsed = [];
		var hasDuplicateSortField = false;
		formElement.find('.sort-condition-row').each(function() {
			var row = jQuery(this);
			var fieldSelect = row.find('.sort-field-select');
			// select2 v3 対応: select2 が適用されている場合は select2('val') で正確な値を取得
			var fieldVal = fieldSelect.data('select2') ? fieldSelect.select2('val') : fieldSelect.val();
			var orderSelect = row.find('.sort-order-select');
			var orderVal = orderSelect.data('select2') ? orderSelect.select2('val') : orderSelect.val();
			if (fieldVal && !orderVal) {
				hasInvalidSort = true;
			}
			if (fieldVal) {
				if (fieldsUsed.indexOf(fieldVal) !== -1) {
					hasDuplicateSortField = true;
				} else {
					fieldsUsed.push(fieldVal);
				}
			}
		});
		if (hasInvalidSort) {
			app.helper.showErrorNotification({message: app.vtranslate('JS_PLEASE_SELECT_SORT_ORDER')});
			aDeferred.reject();
			return aDeferred.promise();
		}
		if (hasDuplicateSortField) {
			app.helper.showErrorNotification({message: app.vtranslate('JS_DUPLICATE_SORT_FIELD_NOT_ALLOWED')});
			aDeferred.reject();
			return aDeferred.promise();
		}

		// Temporarily disable filter value inputs to prevent them from being serialized
		var filterValueInputs = formElement.find('.filterConditionsDiv [data-value="value"]');
		filterValueInputs.each(function() {
			var input = jQuery(this);
			if (input.attr('name')) {
				input.data('temp-name', input.attr('name'));
				input.removeAttr('name');
			}
		});

		var formData = formElement.serializeFormData();

		// Restore name attributes
		filterValueInputs.each(function() {
			var input = jQuery(this);
			if (input.data('temp-name')) {
				input.attr('name', input.data('temp-name'));
				input.removeData('temp-name');
			}
		});

		app.helper.showProgress();

		app.request.post({'data':formData}).then(
			function(error,data){
               if(error === null){
				app.helper.hideProgress();
				window.onbeforeunload = null;
				aDeferred.resolve(data);
				}
				else{
					app.helper.hideProgress();
					aDeferred.reject();
					app.helper.showErrorNotification({'message': app.vtranslate('JS_VIEW_ALREADY_EXISTS')});
				}
			}
		);
		return aDeferred.promise();
	},

	saveAndViewFilter : function(){
		this.saveFilter().then(function (response) {
			if (typeof response != "undefined") {
				app.helper.showSuccessNotification({'message':app.vtranslate('JS_LIST_SAVED')});
				var appName = app.getAppName();
				var url = response['listviewurl']+'&app='+appName;
				window.location.href = url;
			} else {
				app.helper.showErrorNotification({message: app.vtranslate('JS_FAILED_TO_SAVE')});
			}
		});
	},

	isAllUsersSelected : function() {
		var memberList = jQuery('#memberList').val();
		return (memberList != null && (memberList.indexOf('All::Users') != -1)) ? true : false
	},

	registerOnlyAllUsersInSharedList : function(){
		var self = this;
		jQuery('#memberList').on('change',function(e){
			var element = jQuery(e.currentTarget);
			if(self.isAllUsersSelected()){
				element.find('option').not('[value="All::Users"]').prop('disabled',true);
				element.select2('val',['All::Users']);
				element.select2('close');
			}else{
				element.find('option').removeProp('disabled');
			}
		});
	},

	registerOrderbyChangeEvent : function() {
		var form = jQuery('#CustomView');
		var sortContainer = form.find('#sortConditionsContainer');
		if (sortContainer.length === 0) return;

		var sortRowsList = sortContainer.find('#sortRowsList');
		var addFirstContainer = sortContainer.find('#addFirstSortRowContainer');

		var updateRowVisibility = function() {
			var rows = sortRowsList.find('.sort-condition-row');
			if (rows.length === 0) {
				addFirstContainer.removeClass('hide');
			} else {
				addFirstContainer.addClass('hide');
			}

			rows.each(function(index, rowElement) {
				var row = jQuery(rowElement);
				var addBtn = row.find('.addSortRowBtn');
				var deleteBtn = row.find('.deleteSortRowBtn');
				var prefixLabel = row.find('.sort-prefix-label');

				if (prefixLabel.length > 0) {
					var labelTpl = app.vtranslate('LBL_SORT_CONDITION_LABEL');
					prefixLabel.text(labelTpl.replace('%s', index + 1));
				}

				deleteBtn.removeClass('hide');

				if (index === rows.length - 1 && rows.length < 5) {
					addBtn.removeClass('hide');
				} else {
					addBtn.addClass('hide');
				}
			});
		};

		var reindexRows = function() {
			var rows = sortRowsList.find('.sort-condition-row');
			rows.each(function(index, rowElement) {
				var row = jQuery(rowElement);
				var fieldSelect = row.find('.sort-field-select');
				var orderSelect = row.find('.sort-order-select');
				fieldSelect.attr('name', 'sort_conditions[' + index + '][field]');
				orderSelect.attr('name', 'sort_conditions[' + index + '][order]');
			});
			updateRowVisibility();
		};

		// Initialize select2 for existing sort selects
		sortRowsList.find('select.select2').each(function() {
			vtUtils.showSelect2ElementView(jQuery(this));
		});

		updateRowVisibility();

		// select2 の val() 変更が change を再発火させるのを防ぐフラグ
		var isReverting = false;

		sortContainer.on('change', '.sort-field-select', function() {
			if (isReverting) return;

			var changedSelect = jQuery(this);
			var changedVal = changedSelect.val();
			if (!changedVal) return;

			// 同じ項目が他の行で既に選ばれていないかチェック
			var rows = sortRowsList.find('.sort-condition-row');
			var duplicateFound = false;
			rows.each(function() {
				var fieldSelect = jQuery(this).find('.sort-field-select');
				if (fieldSelect[0] === changedSelect[0]) return; // 自分自身はスキップ
				if (fieldSelect.val() === changedVal) {
					duplicateFound = true;
					return false;
				}
			});

			if (duplicateFound) {
				var msg = app.vtranslate('JS_DUPLICATE_SORT_FIELD_NOT_ALLOWED');
				app.helper.showErrorNotification({message: msg});
				// 直前の値（変更前）に戻す
				var prevVal = changedSelect.data('prev-sort-val');
				if (prevVal) {
					isReverting = true;
					// select2 v3 は select2('val') で UI ごと正しく戻す
					if (changedSelect.data('select2')) {
						changedSelect.select2('val', prevVal);
					} else {
						changedSelect.val(prevVal);
					}
					isReverting = false;
				}
			} else {
				// 正常な選択：今の値を「前の値」として保存
				changedSelect.data('prev-sort-val', changedVal);
			}
		});

		// 初期値を記憶しておく（編集時は DB から来た値がそのまま入る）
		sortRowsList.find('.sort-field-select').each(function() {
			var sel = jQuery(this);
			sel.data('prev-sort-val', sel.val());
		});

		sortContainer.on('click', '.addSortRowBtn', function() {
			var rows = sortRowsList.find('.sort-condition-row');
			if (rows.length >= 5) return;

			var selectedFields = [];
			rows.each(function() {
				var val = jQuery(this).find('.sort-field-select').val();
				if (val) selectedFields.push(val);
			});

			var newRow;
			if (rows.length > 0) {
				var lastRow = rows.last();
				newRow = lastRow.clone();
				newRow.find('.select2-container').remove();
				newRow.find('select').each(function() {
					var sel = jQuery(this);
					sel.removeClass('select2-offscreen select2-hidden-accessible');
					sel.removeAttr('id');
					sel.removeData('select2');
					sel.removeData();
				});

				newRow.find('option').prop('selected', false).prop('disabled', false).removeClass('hide');

				// 既存の行で選ばれていない最初の項目を初期値にする
				var nextFieldOpt = null;
				newRow.find('.sort-field-select option').each(function() {
					var optVal = jQuery(this).val();
					if (optVal && selectedFields.indexOf(optVal) === -1 && !nextFieldOpt) {
						nextFieldOpt = optVal;
					}
				});

				if (nextFieldOpt) {
					newRow.find('.sort-field-select option').filter(function() {
						return jQuery(this).val() === nextFieldOpt;
					}).prop('selected', true);
					newRow.find('.sort-field-select').val(nextFieldOpt);
				}
				newRow.find('.sort-order-select option[value="ASC"]').prop('selected', true);
				newRow.find('.sort-order-select').val('ASC');

				lastRow.after(newRow);
			} else {
				var fieldOptionsTemplate = form.find('#sortFieldOptionsTemplate');
				var fieldOptionsHtml = '';
				if (fieldOptionsTemplate.length > 0) {
					fieldOptionsHtml = fieldOptionsTemplate.html();
				} else {
					var fieldSelect = form.find('.columnsSelect');
					fieldSelect.find('optgroup').each(function() {
						var grp = jQuery(this);
						fieldOptionsHtml += '<optgroup label="' + grp.attr('label') + '">';
						grp.find('option').each(function() {
							var opt = jQuery(this);
							var fieldVal = opt.attr('data-field-name') || opt.data('fieldName') || opt.val();
							fieldOptionsHtml += '<option value="' + fieldVal + '">' + opt.text().replace(/\s*\*\s*$/, '') + '</option>';
						});
						fieldOptionsHtml += '</optgroup>';
					});
				}
				var rowHtml = '<div class="sort-condition-row row" style="margin-bottom: 8px;">' +
					'<div class="col-sm-2 col-xs-3" style="padding-top: 6px;">' +
						'<span class="sort-prefix-label text-muted" style="font-weight: bold;">' + app.vtranslate('LBL_SORT_CONDITION_LABEL').replace('%s', 1) + '</span>' +
					'</div>' +
					'<div class="col-sm-5 col-xs-5">' +
						'<select class="select2 form-control sort-field-select">' + fieldOptionsHtml + '</select>' +
					'</div>' +
					'<div class="col-sm-3 col-xs-3">' +
						'<select class="select2 form-control sort-order-select">' +
							'<option value="ASC">' + app.vtranslate('LBL_ASCENDING') + '</option>' +
							'<option value="DESC">' + app.vtranslate('LBL_DESCENDING') + '</option>' +
						'</select>' +
					'</div>' +
					'<div class="col-sm-2 col-xs-1" style="padding-top: 2px;">' +
						'<button type="button" class="btn btn-default addSortRowBtn" title="' + app.vtranslate('LBL_ADD_SORT_ROW') + '"><i class="fa fa-plus"></i></button>' +
						'<button type="button" class="btn btn-default deleteSortRowBtn" title="' + app.vtranslate('LBL_DELETE') + '"><i class="fa fa-trash"></i></button>' +
					'</div>' +
				'</div>';
				newRow = jQuery(rowHtml);
				sortRowsList.append(newRow);
			}

			newRow.find('select').each(function() {
				var selectEl = jQuery(this);
				if (selectEl.hasClass('select2')) {
					vtUtils.showSelect2ElementView(selectEl);
				}
			});

			// 新しい行の初期値を記憶
			newRow.find('.sort-field-select').each(function() {
				var sel = jQuery(this);
				sel.data('prev-sort-val', sel.val());
			});

			reindexRows();
		});

		sortContainer.on('click', '.deleteSortRowBtn', function() {
			var row = jQuery(this).closest('.sort-condition-row');
			row.remove();
			reindexRows();
		});
	},

	/**
	 * Function which will register the select2 elements for columns selection
	 */
	registerSelect2ElementForColumnsSelection : function() {
		var selectElement = this.getColumnSelectElement();
		vtUtils.showSelect2ElementView(selectElement,{maximumSelectionSize: 15});
	},

	registerFilterCreateEvents : function() {
		var self = this;
		self.registerSelect2ElementForColumnsSelection();
		this.arrangeSelectChoicesInOrder();
		this.makeColumnListSortable();
		this.registerToogleShareList();
		this.registerOnlyAllUsersInSharedList();
		this.registerOrderbyChangeEvent();
		var customViewForm = jQuery('#CustomView');

		if(customViewForm.length > 0) {
			customViewForm.vtValidate({
				submitHandler : function(form){
					var form = jQuery(form); 
						  var selectElement = form.find('#viewColumnsSelect'); 
						  var mandatoryFieldsList = JSON.parse(jQuery('#mandatoryFieldsList').val()); 
						  var selectedOptions = selectElement.val();
						  var mandatoryFieldsMissing = true; 
						  for(var i=0; i<selectedOptions.length; i++) { 
						if(jQuery.inArray(selectedOptions[i], mandatoryFieldsList) >= 0) { 
							mandatoryFieldsMissing = false; 
								  break; 
						} 
					} 
						  if(mandatoryFieldsMissing){ 
						app.helper.showErrorNotification({message: app.vtranslate('Select atleast one mandatory value.')}); 
							  return false; 
					} 

					var fieldsUsed = [];
					var hasDuplicateSortField = false;
					form.find('.sort-condition-row').each(function() {
						var row = jQuery(this);
						var fieldSelect = row.find('.sort-field-select');
						// select2 v3 対応: select2 が適用されている場合は select2('val') で正確な値を取得
						var fieldVal = fieldSelect.data('select2') ? fieldSelect.select2('val') : fieldSelect.val();
						if (fieldVal) {
							if (fieldsUsed.indexOf(fieldVal) !== -1) {
								hasDuplicateSortField = true;
							} else {
								fieldsUsed.push(fieldVal);
							}
						}
					});
					if (hasDuplicateSortField) {
						app.helper.showErrorNotification({message: app.vtranslate('JS_DUPLICATE_SORT_FIELD_NOT_ALLOWED')});
						return false;
					} 
					//handled advanced filters saved values.
					var advfilterlist = self.advanceFilterInstance.getValues();
					jQuery('#advfilterlist').val(JSON.stringify(advfilterlist));

					var selectValueElements = self.getColumnSelectElement().select2('data');
					var selectedValues = [];
					for(i=0; i<selectValueElements.length; i++) {
						selectedValues.push(selectValueElements[i].id);
					}
					var selectValues = JSON.stringify(selectedValues);
					jQuery('input[name="columnslist"]', self.getContainer()).val(selectValues);
					var allUsersStatusEle = jQuery('#allUsersStatusValue');
					if(self.isAllUsersSelected() && (jQuery('[data-toogle-members]').is(":checked"))){
						allUsersStatusEle.val(allUsersStatusEle.data('public'));
					}else{
						allUsersStatusEle.val(allUsersStatusEle.data('private'));
					}
					self.saveAndViewFilter();
					return false;
				}
			});
		}
	},

	registerToogleShareList : function() {
		jQuery('[data-toogle-members]').on('change',function(e){
			var element = jQuery(e.currentTarget);
			if(element.is(':checked')){
				jQuery('#memberList').addClass('fadeInx').data('rule-required',true);                
			}
			else {
				jQuery('#memberList').removeClass('fadeInx').data('rule-required',false);
			}
		});
	},

	registerEvents : function() {
		var self = this;
		jQuery(document).on('post.CreateFilter.click',function(e,params){
			self.doOperation(params.url).then(function(data){
				self.showCreateFilter(data);
				var form = jQuery('#CustomView');
				app.helper.registerLeavePageWithoutSubmit(form);
				app.helper.registerModalDismissWithoutSubmit(form);
			})
		});

		jQuery(document).on('post.DeleteFilter.click',function(e,params){
			var target = jQuery(e.target);
			app.helper.showConfirmationBox({'message': app.vtranslate('LBL_LIST_DELETE_CONFIRMATION')}).then(
				function(){
					app.helper.showProgress();
					app.request.post({'url':params.url}).then(function(){
						app.helper.hideProgress();
						target.trigger('post.DeletedFilter');
						// moduleFiltersId is Default All Filter Id
						var moduleFiltersId = jQuery('.module-filters input[name=allCvId]').val();
							jQuery(".listViewFilter ").find('.filterName').each(function(key, ele){
								var filterId = jQuery(ele).data('filter-id');
								if(filterId == moduleFiltersId){
									jQuery(ele).trigger('click');
									return false;
								}
							});
					});
				},
				function(){
				}
			);
		});

		jQuery(document).on('post.ToggleDefault.click',function(e,params){
			var target = jQuery(e.target);
			var url = target.data('url');
			var currentValue = target.data('isDefault');
			var params = {};
			params.url = url;
			params.data = {};
			if(currentValue) {
				params.data.setdefault = '0';
			}else{
				params.data.setdefault = '1';
			}
			app.request.post(params).then(function(error,data){
				target.trigger('post.ToggleDefault.saved',data);
			})
		});
	},

});