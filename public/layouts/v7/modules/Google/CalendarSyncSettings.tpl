{*<!--
/*********************************************************************************
 ** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *********************************************************************************/
-->*}
{strip}
<div class="modal-dialog modal-lg googleSettings" style="min-width: 800px;">
    <div class="modal-content" >
        {assign var=HEADER_TITLE value={vtranslate('LBL_FIELD_MAPPING', $MODULE)}}
        {include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE=$HEADER_TITLE}
    <form class="form-horizontal" name="calendarsyncsettings">
        <input type="hidden" name="module" value="{$MODULENAME}" />
        <input type="hidden" name="action" value="SaveSettings" />
        <input type="hidden" name="sourcemodule" value="{$SOURCE_MODULE}" />
        <div class="modal-body">
            <div id="mappingTable">
                <table  class="table table-bordered">
                    <thead>
                        <tr>
                            <td><b>{vtranslate('APPTITLE',$MODULENAME)}</b></td>
                            <td><b>{vtranslate('EXTENTIONNAME',$MODULENAME)}</b></td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{vtranslate('Subject',$SOURCE_MODULE)}</td>
                            <td>{vtranslate('Event Title',$MODULENAME)}</td>
                        </tr>
                        <tr>
                            <td>{vtranslate('Start Date & Time',$SOURCE_MODULE)}</td>
                            <td>{vtranslate('From Date & Time',$MODULENAME)}</td>
                        </tr>
                        <tr>
                            <td>{vtranslate('End Date & Time',$SOURCE_MODULE)}</td>
                            <td>{vtranslate('Until Date & Time',$MODULENAME)}</td>
                        </tr>
                        <tr>
                            <td>{vtranslate('Description',$SOURCE_MODULE)}</td>
                            <td>{vtranslate('Description',$MODULENAME)}</td>
                        </tr>
                        <tr>
                            <td>{vtranslate('Location',$SOURCE_MODULE)}</td>
                            <td>{vtranslate('Where',$MODULENAME)}</td>
                        </tr>
                        <tr>
                            <td>{vtranslate('Status',$SOURCE_MODULE)}</td>
                            <td>{vtranslate('Planned',$MODULENAME)}</td>
                        </tr>
                        <tr>
                            <td>{vtranslate('Activity Type',$SOURCE_MODULE)}</td>
                            <td>{vtranslate('Meeting',$MODULENAME)}</td>
                        </tr>
                        <tr>
                            <td>{vtranslate('Visibility',$SOURCE_MODULE)}</td>
                            <td>{vtranslate('Privacy',$MODULENAME)}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </form>
    <div class="modal-footer ">
        <center>
            <a href="#" class="cancelLink" type="reset" data-dismiss="modal">{vtranslate('LBL_CANCEL', $MODULE)}</a>
        </center>
    </div>
</div>
</div>
{/strip}