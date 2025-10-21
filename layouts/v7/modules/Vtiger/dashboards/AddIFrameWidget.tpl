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
            {assign var=HEADER_TITLE value={vtranslate('LBL_ADD_IFRAME_WIDGET', $MODULE)}}
            {include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE=$HEADER_TITLE}
            <form class="form-horizontal addIFrameWidgetForm" method="POST">
                <div class="admonition admonition-warning" style="margin: 15px; background-color: #fffbea;">
                    <div class="admonition-icon">
                        <i class="fa fa-exclamation-triangle"></i>
                    </div>
                    <div class="admonition-content">
                        <p>iframeウィジェットを利用の際は、信頼できるHTTPS対応のサイトを使用し、スマートフォンなどでの表示崩れにもご注意ください。<br>不正なサイトの埋め込みは、セキュリティリスクにつながる可能性があります。</p>
                    </div>
                </div>
                <div class="row" style="padding:10px;">
                    <label class="fieldLabel col-lg-4">
                        <label class="pull-right">{vtranslate('LBL_IFRAME_NAME', $MODULE)}<span class="redColor">*</span></label>
                    </label>
                    <div class="fieldValue col-lg-6">
                        <input type="text" name="iframeWidgetTitle" class="inputElement" data-rule-required="true" placeholder="" />
                    </div>
                </div>
                <div class="row" style="padding:10px;">
                    <label class="fieldLabel col-lg-4">
                        <label class="pull-right">URL<span class="redColor">*</span></label>
                    </label>
                    <div class="fieldValue col-lg-6">
                        <input type="text" name="iframeWidgetUrl" class="inputElement" data-rule-required="true" />
                    </div>
                </div>
                
                {include file='ModalFooter.tpl'|@vtemplate_path:$MODULE}
            </form>
        </div>
	</div>
{/strip}

