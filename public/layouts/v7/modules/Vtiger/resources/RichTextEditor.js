/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *
 * Modified: CKEditor replaced with <rich-text-editor> Web Component (Tiptap)
 *************************************************************************************/
jQuery.Class("Vtiger_RichTextEditor_Js",{},{

	setElement : function(element){
		this.element = element;
		return this;
	},

	getElement : function(){
		return this.element;
	},

	getElementId : function(){
		var element = this.getElement();
		return element.attr('id');
	},

	getRichTextEditorInstanceFromName : function(){
		var element = this.getElement();
		return element.data('richTextEditor');
	},

	getPlainText : function() {
		var rteElement = this.getRichTextEditorInstanceFromName();
		if (rteElement) {
			var tempDiv = document.createElement('div');
			tempDiv.innerHTML = rteElement.getAttribute('value') || '';
			return tempDiv.textContent || tempDiv.innerText || '';
		}
		return '';
	},

	/*
	 * Function to load rich-text-editor web component.
	 * Handles two cases:
	 *   1. TPL直埋め込み: data-target属性を持つ<rich-text-editor>が既にDOM上にある場合、同期だけセットアップ
	 *   2. 動的生成: textareaを隠してWeb Componentを動的に作成
	 */
	loadRichTextEditor : function(element, customConfig){
		this.setElement(element);
		var self = this;

		var elementId = this.getElementId();
		var existingRte = element.data('richTextEditor');

		// Case 1: Already rendered by TPL (find by data-target attribute)
		if (!existingRte) {
			var preRendered = document.querySelector('rich-text-editor[data-target="' + elementId + '"]');
			if (preRendered) {
				element.data('richTextEditor', preRendered);
				// Sync changes back to hidden textarea
				preRendered.addEventListener('change', function(e) {
					if (e.detail && e.detail.target) {
						element.val(e.detail.target.value);
					}
				});
				return;
			}
		}

		// Case 2: Dynamic creation (for templates, workflows, etc.)
		if (existingRte) {
			existingRte.remove();
		}

		var elementName = element.attr('name') || elementId;
		var initialValue = element.val() || '';

		var rteElement = document.createElement('rich-text-editor');
		rteElement.setAttribute('value', initialValue);
		rteElement.setAttribute('name', elementName);

		// customConfigのheight指定をWeb Componentに反映
		if (customConfig && customConfig.height) {
			var h = customConfig.height;
			rteElement.style.height = (typeof h === 'number') ? h + 'px' : h;
		}

		rteElement.addEventListener('change', function(e) {
			if (e.detail && e.detail.target) {
				element.val(e.detail.target.value);
			}
		});

		element.hide();
		element.after(rteElement);
		element.data('richTextEditor', rteElement);
	},

	loadContentsInRichTextEditor : function(contents){
		var rteElement = this.getRichTextEditorInstanceFromName();
		if (rteElement) {
			rteElement.setAttribute('value', contents);
		}
	},

	removeRichTextEditor : function() {
		if (this.getElement()) {
			var rteElement = this.getRichTextEditorInstanceFromName();
			if (rteElement) {
				var currentValue = rteElement.getAttribute('value') || '';
				this.getElement().val(currentValue);
				rteElement.remove();
				this.getElement().removeData('richTextEditor');
				this.getElement().show();
			}
		}
	},

	getData : function() {
		var rteElement = this.getRichTextEditorInstanceFromName();
		if (rteElement) {
			return rteElement.getAttribute('value') || '';
		}
		return this.getElement() ? this.getElement().val() : '';
	}
});
