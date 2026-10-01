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
            {foreach item=RECORD_LINK from=$LISTVIEW_ENTRY->getRecordLinks()}
                <span>
                {if $RECORD_LINK->getIcon() eq 'icon-pencil' }
                      {* クリックは Parameters.js が行（tr[data-id]）単位で拾うため、ここでは登録しない *}
                      <a href="javascript:void(0);"
                         title='{vtranslate('LBL_EDIT', $MODULE)}'
                         class="parameter-edit-btn">
                      <i class="fa fa-pencil" ></i>
                      </a>
                {/if}
                {* 削除ボタンは非表示（システム変数は画面からの削除を許可しない） *}
                </span>
            {/foreach}
    </div>
{/strip}
