<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ***********************************************************************************/

require_once 'include/events/VTEventHandler.inc';

class RelateEntitesHandler extends VTEventHandler {

	function handleEvent($eventName, $entityData) {
        global $log;
		$log->debug("Entering function RelateEntitesHandler ($eventName)");
		if ($eventName == 'vtiger.entity.beforerelate') {
            $log->debug("Calling function triggerBeforeRelationsHandler ($eventName)");
			$this->triggerBeforeRelationsHandler($entityData);
		} else if ($eventName == 'vtiger.entity.afterrelate') {
            $log->debug("Calling function triggerAfterRelationHandler ($eventName)");
			$this->triggerAfterRelationHandler($entityData);
		}
	}
    
    public function triggerBeforeRelationsHandler($entityData) {
        return true;
    }
    
    public function triggerAfterRelationHandler($entityData) {
        return true;
    }
}