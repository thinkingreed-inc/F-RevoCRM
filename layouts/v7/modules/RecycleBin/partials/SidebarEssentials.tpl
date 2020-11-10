{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}
<div class="sidebar-menu sidebar-menu-full">
    <div class="module-filters" id="module-filters">
        <div class="sidebar-container lists-menu-container">
            <h5 class="sidebar-header"> {vtranslate('LBL_MODULES', 'Settings:$MODULE')} </h5>
            <hr>
            <div>
                <input class="search-list" type="text" placeholder="Search for Modules">
            </div>
            <div class="list-menu-content">
                <div class="list-group">   
                    <ul class="lists-menu" style="list-style-type: none; padding-left: 0px;">
                        {if $MODULE_LIST|@count gt 0}
                            {foreach item=MODULEMODEL from=$MODULE_LIST}
                                <li style="font-size:12px;" class='listViewFilter {if $MODULEMODEL->getName() eq $SOURCE_MODULE}active{/if} '>
                                    <a class="filterName" href="index.php?module=RecycleBin&view=List&sourceModule={$MODULEMODEL->getName()}" >{vtranslate($MODULEMODEL->getName(), $MODULEMODEL->getName())}</a>
                                </li>
                            {/foreach}
                        {/if}
                    </ul>
                 </div>
                <div class="list-group hide noLists">
                    <h6 class="lists-header"><center> {vtranslate('LBL_NO')} {vtranslate('LBL_MODULES', 'Settings:$MODULE')} {vtranslate('LBL_FOUND')} ... </center></h6>
                </div>
            </div>
        </div>
    </div>
</div>