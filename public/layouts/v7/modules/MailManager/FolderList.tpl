{*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************}

{if $FOLDERS}
    {assign var=INBOX_ADDED value=0}
    {assign var=TRASH_ADDED value=0}
    <ul>
        {foreach item=FOLDER from=$FOLDERS}
            {if stripos($FOLDER->name(), 'inbox') !== false && $INBOX_ADDED == 0}
                {assign var=INBOX_ADDED value=1}
                {assign var=INBOX_FOLDER value=$FOLDER->name()}
                <li class="cursorPointer mm_folder mmMainFolder active" data-foldername="{$FOLDER->name()}">
                    <i class="fa fa-inbox fontSize20px"></i>&nbsp;&nbsp;
                    <b>{vtranslate('LBL_INBOX', $MODULE)}</b>
                    <span class="pull-right mmUnreadCountBadge {if !$FOLDER->unreadCount()}hide{/if}">
                       {$FOLDER->unreadCount()} 
                    </span>
                </li>
                <li class="cursorPointer mm_folder mmMainFolder" data-foldername="vt_drafts">
                    <i class="fa fa-floppy-o fontSize20px"></i>&nbsp;&nbsp;
                    <b>{vtranslate('LBL_Drafts', $MODULE)}</b>
                </li>
            {/if}
        {/foreach}
        
        {foreach item=FOLDER from=$FOLDERS}
            {if $FOLDER->isSentFolder()}
                {assign var=SENT_FOLDER value=$FOLDER->name()}
                <li class="cursorPointer mm_folder mmMainFolder" data-foldername="{$FOLDER->name()}">
                    <i class="fa fa-paper-plane fontSize20px"></i>&nbsp;&nbsp;
                    <b>{vtranslate('LBL_SENT', $MODULE)}</b>
                    <span class="pull-right mmUnreadCountBadge {if !$FOLDER->unreadCount()}hide{/if}">
                       {$FOLDER->unreadCount()} 
                    </span>
                </li>
            {/if}
        {/foreach}
        
        {foreach item=FOLDER from=$FOLDERS}
            {if stripos($FOLDER->name(), 'trash') !== false && $TRASH_ADDED == 0}
                {assign var=TRASH_ADDED value=1}
                {assign var=TRASH_FOLDER value=$FOLDER->name()}
                <li class="cursorPointer mm_folder mmMainFolder" data-foldername="{$FOLDER->name()}">
                    <i class="fa fa-trash-o fontSize20px"></i>&nbsp;&nbsp;
                    <b>{vtranslate('LBL_TRASH', $MODULE)}</b>
                    <span class="pull-right mmUnreadCountBadge {if !$FOLDER->unreadCount()}hide{/if}">
                       {$FOLDER->unreadCount()} 
                    </span>
                </li>
            {/if}
        {/foreach}
        <br>
        <span class="padding15px"><b>{vtranslate('LBL_Folders', $MODULE)}</b></span>
        
        {assign var=IGNORE_FOLDERS value=array($INBOX_FOLDER, $SENT_FOLDER, $TRASH_FOLDER)}
        {foreach item=FOLDER from=$FOLDERS}
            {if !in_array($FOLDER->name(), $IGNORE_FOLDERS)}
            <li class="cursorPointer mm_folder mmOtherFolder" data-foldername="{$FOLDER->name()}">
                <b>{$FOLDER->name()}</b>
                <span class="pull-right mmUnreadCountBadge {if !$FOLDER->unreadCount()}hide{/if}">
                   {$FOLDER->unreadCount()} 
                </span>
            </li>
            {/if}
        {/foreach}
    </ul>
{/if}