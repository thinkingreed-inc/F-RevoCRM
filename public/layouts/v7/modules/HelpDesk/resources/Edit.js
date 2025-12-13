/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Vtiger_Edit_Js("HelpDesk_Edit_Js", {} ,{

	/**
	 * Function to register event for ckeditor for description field
	 */
	registerEventForCkEditor : function(){
		var form = this.getForm();
        var ckContentElement = form.find('[name="description"]');
        this.addFieldCkEditor(ckContentElement);
		ckContentElement = form.find('[name="solution"]');
        this.addFieldCkEditor(ckContentElement);
	},

	registerEvents : function() {
        this.registerEventForCkEditor();
        this._super();
	}
});


