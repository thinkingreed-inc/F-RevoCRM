{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}

<center>{'LBL_LOADING_PLEASE_WAIT'|vtranslate}...</center>

<form class="form-horizontal" name="step7" method="post" action="?module=Users&action=Login">
	<input type=hidden name="username" value="admin" >
	<input type=hidden name="password" value="{$PASSWORD}" >
</form>
<script type="text/javascript">
	jQuery(function () { /* Delay to let page load complete */
		setTimeout(function () {
			jQuery('form[name="step7"]').submit();
		}, 150);
	});
</script>
