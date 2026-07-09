<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

/**
 * Events専用 GetRecord API
 *
 * Calendar_GetRecord_Api を継承。
 * Events は内部的に Calendar モジュールとして扱われるため、
 * 追加の実装は不要。
 */
class Events_GetRecord_Api extends Calendar_GetRecord_Api {
    // Calendar_GetRecord_Api をそのまま継承
    // Events は内部的に Calendar モジュールとして扱われるため追加実装不要
}
