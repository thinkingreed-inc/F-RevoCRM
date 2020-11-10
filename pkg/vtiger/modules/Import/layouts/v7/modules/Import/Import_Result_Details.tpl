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

<table width="100%" class="table table-borderless">
	<tr>
		<td class = "greenColor">{'LBL_TOTAL_RECORDS_IMPORTED'|@vtranslate:$MODULE}</td>
		<td width="10%">:</td>
		<td class = "greenColor" width="30%">{$IMPORT_RESULT.IMPORTED} / {$IMPORT_RESULT.TOTAL}</td>
	</tr>
	<tr>
		<td class = "greenColor" width="20%">{'LBL_NUMBER_OF_RECORDS_CREATED'|@vtranslate:$MODULE}</td>
		<td width="1%">:</td>
		<td  class = "greenColor" width="60%">{$IMPORT_RESULT.CREATED}
			{if $IMPORT_RESULT['CREATED'] neq '0'}
				{if $FOR_MODULE neq 'Users'}
					&nbsp;&nbsp;&nbsp;&nbsp;<a class="cursorPointer" onclick="return Vtiger_Import_Js.showLastImportedRecords('index.php?module={$MODULE}&for_module={$FOR_MODULE}&view=List&start=1&foruser={$OWNER_ID}&_showContents=0')">{'LBL_DETAILS'|@vtranslate:$MODULE}</a>
				{/if}
			{/if}
		</td>

	</tr>
	{if in_array($FOR_MODULE, $INVENTORY_MODULES) eq FALSE}
		<tr>
			<td>{'LBL_NUMBER_OF_RECORDS_UPDATED'|@vtranslate:$MODULE}</td>
			<td width="10%">:</td>
			<td width="30%">{$IMPORT_RESULT.UPDATED}</td>
		</tr>
		<tr>
			<td>{'LBL_NUMBER_OF_RECORDS_SKIPPED'|@vtranslate:$MODULE}</td>
			<td width="10%">:</td>
			<td width="30%">{$IMPORT_RESULT.SKIPPED}
				{if $IMPORT_RESULT['SKIPPED'] neq '0'}
					&nbsp;&nbsp;&nbsp;&nbsp;<a class="cursorPointer" onclick="return Vtiger_Import_Js.showSkippedRecords('index.php?module={$MODULE}&view=List&mode=getImportDetails&type=skipped&start=1&foruser={$OWNER_ID}&_showContents=0&for_module={$FOR_MODULE}')">{'LBL_DETAILS'|@vtranslate:$MODULE}</a>
				{/if}
			</td>
		</tr>
		<tr>
			<td>{'LBL_NUMBER_OF_RECORDS_MERGED'|@vtranslate:$MODULE}</td>
			<td width="10%">:</td>
			<td width="10%">{$IMPORT_RESULT.MERGED}</td>
		</tr>
	{/if}
	{if $IMPORT_RESULT['FAILED'] neq '0'}
		<tr>
			<td><font color = "red">{'LBL_TOTAL_RECORDS_FAILED'|@vtranslate:$MODULE}</font></td>
			<td width="10%">:</td>
			<td width="30%"><font color = "red">{$IMPORT_RESULT.FAILED} / {$IMPORT_RESULT.TOTAL}</font>
				&nbsp;&nbsp;&nbsp;&nbsp;<a class="cursorPointer" onclick="return Vtiger_Import_Js.showFailedImportRecords('index.php?module={$MODULE}&view=List&mode=getImportDetails&type=failed&start=1&foruser={$OWNER_ID}&_showContents=0&for_module={$FOR_MODULE}')">{'LBL_DETAILS'|@vtranslate:$MODULE}</a>
			</td>
		</tr>
	{/if}
</table>