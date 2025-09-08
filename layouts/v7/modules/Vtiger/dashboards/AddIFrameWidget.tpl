{*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************}

 {strip}
	<div id="addIFrameWidgetContainer" class='modal-dialog'>
        <div class="modal-content">
            {assign var=HEADER_TITLE value="Add IFrame Widget"}
            {include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE=$HEADER_TITLE}
            <form class="form-horizontal addIFrameWidgetForm" method="POST">
                <div class="row" style="padding:10px;">
                    <label class="fieldLabel col-lg-4">
                        <label class="pull-right">Widget Title<span class="redColor">*</span></label>
                    </label>
                    <div class="fieldValue col-lg-6">
                        <input type="text" name="iframeWidgetTitle" class="inputElement" data-rule-required="true" placeholder="Enter widget title" />
                    </div>
                </div>
                <div class="row" style="padding:10px;">
                    <label class="fieldLabel col-lg-4">
                        <label class="pull-right">URL<span class="redColor">*</span></label>
                    </label>
                    <div class="fieldValue col-lg-6">
                        <input type="text" name="iframeWidgetUrl" class="inputElement" data-rule-required="true" placeholder="https://example.com or /index.php?module=Calendar" />
                    </div>
                </div>
                
                {include file='ModalFooter.tpl'|@vtemplate_path:$MODULE}
            </form>
        </div>
	</div>
{/strip}

