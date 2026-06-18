/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Vtiger_Edit_Js("Faq_Edit_Js", {} ,{

	/**
	 * Function to register Jodit editor for description field
	 */
	registerEventForJoditEditor : function(form){
		form = form || this.getForm();
		var joditContentElement = form.find('[name="question"]');
		this.addFieldJoditEditor(joditContentElement);
		var joditContentElement = form.find('[name="faq_answer"]');
		this.addFieldJoditEditor(joditContentElement);
	},

	registerEvents : function() {
        this.registerEventForJoditEditor();
        this._super();
	}
});


