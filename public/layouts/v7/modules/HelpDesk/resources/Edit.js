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
	 * Function to register event for RichTextEditor for description field
	 */
	registerEventForRichTextEditor : function(){
		var form = this.getForm();
        var rteContentElement = form.find('[name="description"]');
        this.addFieldRichTextEditor(rteContentElement);
		rteContentElement = form.find('[name="solution"]');
        this.addFieldRichTextEditor(rteContentElement);
	},

	registerEvents : function() {
        this.registerEventForRichTextEditor();
        this._super();
	}
});


