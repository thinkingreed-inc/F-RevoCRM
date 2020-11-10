{*<!--
* 
* Copyright (C) www.vtiger.com. All rights reserved.
* @license Proprietary
*
-->*}
{strip}
<div class='modelContainer'>
	<div class="modal-header contentsBackground">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                {if $ERROR}
                        <input type="hidden" name="installationStatus" value="error" />
                        <h3 style="color: red">{vtranslate('LBL_INSTALLATION_FAILED', $QUALIFIED_MODULE)}</h3>
                {else}
                        <input type="hidden" name="installationStatus" value="success" />
                        <h3 style="color:green;">{vtranslate('LBL_SUCCESSFULL_INSTALLATION', $QUALIFIED_MODULE)}</h3>
                {/if}
	</div>
        <div class="modal-body" id="installationLog">
                {if $ERROR}
                    <p style="color:red;">{vtranslate($ERROR_MESSAGE, $QUALIFIED_MODULE)}</p>
                {else}
                <div class="row-fluid">
                        <span class="font-x-x-large">{vtranslate('LBL_INSTALLATION_LOG', $QUALIFIED_MODULE)}</span>
                </div>
                    <div id="extensionInstallationInfo" class="backgroundImageNone" style="background-color: white;padding: 2%;">
                            {if $MODULE_ACTION eq "Upgrade"}
                                    {$MODULE_PACKAGE->update($TARGET_MODULE_INSTANCE, $MODULE_FILE_NAME)}
                            {else}
                                    {$MODULE_PACKAGE->import($MODULE_FILE_NAME, 'false')}
                            {/if}
                            {assign var=UNLINK_RESULT value={unlink($MODULE_FILE_NAME)}}
                    </div>
                {/if}
            </div>
	<div class="modal-footer">
		<span class="pull-right">
                    <button class="btn btn-success" id="importCompleted" onclick="location.reload()">{vtranslate('LBL_OK', $QUALIFIED_MODULE)}</button>
		</span>
	</div>
</div>
{/strip}