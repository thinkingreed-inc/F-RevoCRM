{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
*
 ********************************************************************************/
-->*}
{strip}
    <div class="table-actions">
        {* 並べ替えはこのハンドルからだけ行えるようにする（行のどこでも掴めると誤操作になる）。
           List.js の sortable に handle として渡している *}
        <span class="listViewDragHandle" title="{vtranslate('LBL_DRAG',$QUALIFIED_MODULE)}">
            <img src="{vimage_path('drag.png')}" class="alignTop" alt="{vtranslate('LBL_DRAG',$QUALIFIED_MODULE)}" />
        </span>
        <span>
            {foreach item=RECORD_LINK from=$LISTVIEW_ENTRY->getRecordLinks()}
                {assign var="RECORD_LINK_URL" value=$RECORD_LINK->getUrl()}
                {* リンクが複数あるため、アイコンは固定せず各リンクの指定を使う *}
                {assign var="RECORD_LINK_ICON" value=$RECORD_LINK->getIcon()}
                <a {if stripos($RECORD_LINK_URL, 'javascript:')===0} onclick="{$RECORD_LINK_URL|substr:strlen("javascript:")};if(event.stopPropagation){ldelim}event.stopPropagation();{rdelim}else{ldelim}event.cancelBubble=true;{rdelim}" {else} href='{$RECORD_LINK_URL}' {/if}>
                    <i class="fa {if $RECORD_LINK_ICON neq ''}{$RECORD_LINK_ICON|escape}{else}fa-pencil{/if}" title="{vtranslate($RECORD_LINK->getLabel(), $QUALIFIED_MODULE)}"></i>
                </a>&nbsp;
            {/foreach}
        </span>
    </div>
{/strip}        
