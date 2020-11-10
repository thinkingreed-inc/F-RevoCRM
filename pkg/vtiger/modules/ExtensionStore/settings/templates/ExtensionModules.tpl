{*<!--
* 
* Copyright (C) www.vtiger.com. All rights reserved.
* @license Proprietary
*
-->*}
{strip}
    <div class="row-fluid">
        {foreach item=EXTENSION from=$EXTENSIONS_LIST name=extensions}
            {if $EXTENSION->isAlreadyExists()}
                {assign var=EXTENSION_MODULE_MODEL value= $EXTENSION->get('moduleModel')}
            {else}
                {assign var=EXTENSION_MODULE_MODEL value= 'false'}
            {/if}
            <div class="span6">
                <div class="extension_container extensionWidgetContainer">
                    <div class="extension_header">
                            <div class="font-x-x-large boxSizingBorderBox" style="cursor:pointer">{vtranslate($EXTENSION->get('label'), $QUALIFIED_MODULE)}</div>
                            <input type="hidden" name="extensionName" value="{$EXTENSION->get('name')}" />
                            <input type="hidden" name="moduleAction" value="{if ($EXTENSION->isAlreadyExists()) and (!$EXTENSION_MODULE_MODEL->get('trial'))}{if $EXTENSION->isUpgradable()}Upgrade{else}Installed{/if}{else}Install{/if}" />
                            <input type="hidden" name="extensionId" value="{$EXTENSION->get('id')}" />
                    </div>
                    <div>
                        <div class="row-fluid extension_contents">
                            <span class="span8">
                                <div class="row-fluid extensionDescription" style="word-wrap:break-word;">
                                    {assign var=SUMMARY value=$EXTENSION->get('summary')}
                                    {if empty($SUMMARY)}
                                        {assign var=SUMMARY value={$EXTENSION->get('description')|truncate:100}}
                                    {/if}
                                   {$SUMMARY}
                                </div>
                            </span>
                            <span class="span4">
                                {if $EXTENSION->get('thumbnailURL') neq NULL}
                                    {assign var=imageSource value=$EXTENSION->get('thumbnailURL')}
                                {else}
                                    {assign var=imageSource value= vimage_path('unavailable.png')}
                                {/if}     
                                    <img class="thumbnailImage" src="{$imageSource}"/>
                            </span>
                        </div>
                        <div class="extensionInfo">
                            <div class="row-fluid">
                                {assign var=ON_RATINGS value=$EXTENSION->get('avgrating')}
                                <div class="span4"><span class="rating" data-score="{$ON_RATINGS}" data-readonly=true></span><span>{if $EXTENSION->get('avgrating')}&nbsp;({$EXTENSION->get('avgrating')}){/if}</span></div>
                                <div class="span8">
                                    <div class="pull-right">
                                        <button class="btn installExtension addButton" style="margin-right:5px;">{vtranslate('LBL_MORE_DETAILS', $QUALIFIED_MODULE)}</button>
                                        {if $EXTENSION->isVtigerCompatible()}
                                            {if ($EXTENSION->isAlreadyExists()) and (!$EXTENSION_MODULE_MODEL->get('trial'))}
                                                {if ($EXTENSION->isUpgradable())}
                                                    {if $EXTENSION->get('isprotected') && $IS_PRO}
                                                        <button class="oneclickInstallFree btn btn-success margin0px {if ($REGISTRATION_STATUS) and ($PASSWORD_STATUS)}authenticated {else} loginRequired{/if}">
                                                                {vtranslate('LBL_UPGRADE', $QUALIFIED_MODULE)}
                                                        </button>
                                                    {elseif !$EXTENSION->get('isprotected')}
                                                        <button class="oneclickInstallFree btn btn-success margin0px {if ($REGISTRATION_STATUS) and ($PASSWORD_STATUS)}authenticated {else} loginRequired{/if}">
                                                                {vtranslate('LBL_UPGRADE', $QUALIFIED_MODULE)}
                                                        </button>
                                                    {/if}
                                                {else}
                                                    <span class="alert alert-info" style="vertical-align:middle; padding: 5px 10px;">{vtranslate('LBL_INSTALLED', $QUALIFIED_MODULE)}</span>
                                                {/if}
                                            {elseif (($EXTENSION->get('price') eq 'Free') or ($EXTENSION->get('price') eq 0))}
                                                {if $EXTENSION->get('isprotected') && $IS_PRO}
                                                    <button class="oneclickInstallFree btn btn-success {if ($REGISTRATION_STATUS) and ($PASSWORD_STATUS)}authenticated {else} loginRequired{/if}">{vtranslate('LBL_INSTALL', $QUALIFIED_MODULE)}</button>
                                                {elseif !$EXTENSION->get('isprotected')}
                                                    <button class="oneclickInstallFree btn btn-success {if ($REGISTRATION_STATUS) and ($PASSWORD_STATUS)}authenticated {else} loginRequired{/if}">{vtranslate('LBL_INSTALL', $QUALIFIED_MODULE)}</button>
                                                {/if}
                                            {elseif ($IS_PRO)}
                                                {if ($EXTENSION->get('trialdays') gt 0) and ($EXTENSION_MODULE_MODEL eq 'false') and ($EXTENSION->get('isprotected') eq 1)}
                                                    <button class="oneclickInstallPaid btn btn-success {if ($REGISTRATION_STATUS) and ($PASSWORD_STATUS)}authenticated {else} loginRequired{/if}" data-trial=true>{vtranslate('LBL_TRY_IT', $QUALIFIED_MODULE)}</button>
                                                {elseif (($EXTENSION_MODULE_MODEL neq 'false') and ($EXTENSION_MODULE_MODEL->get('trial')))}
                                                    <span class="alert alert-info">{vtranslate('LBL_TRIAL_INSTALLED', $QUALIFIED_MODULE)}</span>&nbsp;&nbsp;
                                                {/if}
                                                 <button class="oneclickInstallPaid btn btn-info {if ($REGISTRATION_STATUS) and ($PASSWORD_STATUS)}authenticated {else} loginRequired{/if}" data-trial=false>{vtranslate('LBL_BUY',$QUALIFIED_MODULE)}${$EXTENSION->get('price')}</button>
                                            {/if}
                                        {else}
                                            <span class="alert alert-error">{vtranslate('LBL_EXTENSION_NOT_COMPATABLE', $QUALIFIED_MODULE)}</span>
                                        {/if}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {if $smarty.foreach.extensions.index % 2 != 0}
            </div>
                <div class="row-fluid">
            {/if}
            {/foreach}
 {if empty($EXTENSIONS_LIST)}
    <table class="emptyRecordsDiv">
        <tbody>
            <tr>
                <td>
                    {vtranslate('LBL_NO_EXTENSIONS_FOUND', $QUALIFIED_MODULE)}
                </td>
            </tr>
        </tbody>
    </table>
{/if}
</div>
{/strip}