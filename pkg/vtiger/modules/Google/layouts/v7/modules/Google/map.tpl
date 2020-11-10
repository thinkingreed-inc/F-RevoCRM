{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************}

{strip}
	<script type="text/javascript" src="layouts/v7/modules/Google/resources/Map.js"></script>
	<div class="modal-dialog modal-lg mapcontainer">
		<div class="modal-content">
			{include file="ModalHeader.tpl"|vtemplate_path:$SOURCE_MODULE TITLE=vtranslate('LBL_GOOGLE_MAP', $SOURCE_MODULE)}
			<div class="modal-body">
				<input type='hidden' id='record' value='{$RECORD}' />
				<input type='hidden' id='source_module' value='{$SOURCE_MODULE}' />
				<input type="hidden" id="record_label" />
				<div id='mapCanvas'>
					<span id='address' class='hide'></span>&nbsp;&nbsp;
					<i id = 'mapLink' class="fa fa-external-link cursorPointer"></i>
					<br><br>
					<div id="map_canvas" style="min-height: 400px;"></div>
				</div>
			</div>
		</div>
	</div>
{/strip}