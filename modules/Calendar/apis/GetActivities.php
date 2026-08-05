<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

/**
 * Calendar GetActivities API
 *
 * 親レコード（Accounts, Contacts, Potentials等）に関連するカレンダーアクティビティを取得するAPI
 *
 * Usage:
 *   GET ?module=Calendar&api=GetActivities&parent_module=Accounts&parent_id=123
 *
 * Parameters:
 *   - parent_module (required): 親モジュール名 (Accounts, Contacts, Potentials等)
 *   - parent_id (required): 親レコードID
 *   - page (optional): ページ番号 (default: 1)
 *   - limit (optional): 取得件数 (default: 10, max: 100)
 *   - mode (optional): 'upcoming', 'overdue', or empty for all
 *
 * Response:
 *   {
 *     "success": true,
 *     "activities": [...],
 *     "hasMore": false,
 *     "page": 1,
 *     "limit": 10,
 *     "totalCount": 5
 *   }
 */
class Calendar_GetActivities_Api extends Vtiger_Api_Controller {

    function loginRequired() {
        return true;
    }

    function requiresPermission(Vtiger_Request $request) {
        // DetailView permission on parent module and record
        return array(
            array('module_parameter' => 'parent_module', 'action' => 'DetailView', 'record_parameter' => 'parent_id')
        );
    }

    function checkPermission(Vtiger_Request $request) {
        global $log;
        if ($log) {
            $log->info("Calendar_GetActivities_Api checkPermission: parent_module=" . $request->get('parent_module') . ", parent_id=" . $request->get('parent_id'));
        }

        // Execute parent permission check (includes login check)
        parent::checkPermission($request);

        return true;
    }

    /**
     * Get calendar activities related to a parent record (Accounts, Contacts, Potentials)
     */
    protected function processApi(Vtiger_Request $request) {
        try {
            // Validate required parameters
            $parentModule = $request->get('parent_module');
            $parentId = $request->get('parent_id');

            if (empty($parentModule)) {
                throw new Exception('parent_module is required');
            }

            if (empty($parentId)) {
                throw new Exception('parent_id is required');
            }

            // Validate parent_module format (alphanumeric and underscore only)
            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $parentModule)) {
                throw new Exception('Invalid parent_module format');
            }

            // Validate parent_id is numeric
            if (!is_numeric($parentId)) {
                throw new Exception('parent_id must be numeric');
            }

            // Get optional parameters
            $page = (int)$request->get('page', 1);
            $limit = (int)$request->get('limit', 10);
            $mode = $request->get('mode', ''); // 'upcoming', 'overdue', or empty for all

            // Validate page and limit
            if ($page < 1) {
                $page = 1;
            }

            if ($limit < 1) {
                $limit = 10;
            }

            // Max limit is 100
            if ($limit > 100) {
                $limit = 100;
            }

            // Validate mode
            if (!in_array($mode, array('', 'upcoming', 'overdue'))) {
                throw new Exception("Invalid mode. Must be 'upcoming', 'overdue', or empty");
            }

            // Validate parent module exists
            $parentModuleModel = Vtiger_Module_Model::getInstance($parentModule);
            if (empty($parentModuleModel)) {
                throw new Exception("Module '$parentModule' not found");
            }

            // Validate parent record exists
            $parentRecordModel = Vtiger_Record_Model::getInstanceById($parentId, $parentModule);
            if (empty($parentRecordModel)) {
                throw new Exception("Record '$parentId' not found in module '$parentModule'");
            }

            // Create paging model
            $pagingModel = new Vtiger_Paging_Model();
            $pagingModel->set('page', $page);
            $pagingModel->set('limit', $limit);

            // Get activities
            if ($parentModule === 'Dailyreports') {
                // 日報は担当者・期間・日付で活動を検索する特殊処理
                $user = $parentRecordModel->get('assigned_user_id');
                $reportsterm = $parentRecordModel->get('reportsterm');
                $reportsdate = $parentRecordModel->get('ReportsDate');
                $activities = $parentModuleModel->getCalendarActivitiesHistory($mode, $pagingModel, $user, $reportsterm, $reportsdate);
            } else {
                // Pass 'all' to retrieve all activities for the parent record regardless of assigned user
                $activities = $parentModuleModel->getCalendarActivities($mode, $pagingModel, 'all', $parentId);
            }

            // Format activities for API response
            $formattedActivities = array();
            foreach ($activities as $activityModel) {
                $formattedActivities[] = $this->formatActivity($activityModel);
            }

            // Calculate total count (approximate based on hasMore flag)
            $hasMore = $pagingModel->get('nextPageExists', false);

            // Build response
            $result = array(
                'success' => true,
                'activities' => $formattedActivities,
                'hasMore' => $hasMore,
                'page' => $page,
                'limit' => $limit,
                'totalCount' => count($formattedActivities) + (($page - 1) * $limit)
            );

            return $this->sendSuccess($result);

        } catch (Exception $e) {
            error_log("Calendar_GetActivities_Api Error: " . $e->getMessage());
            return $this->sendError('Failed to retrieve activities: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Format activity model to API response format
     * @param Vtiger_Record_Model $activityModel
     * @return array
     */
    private function formatActivity($activityModel) {
        try {
            $activityId = $activityModel->getId();
            $activityType = $activityModel->get('activitytype');
            $subject = $activityModel->get('subject');
            // Status can be in different fields depending on which Module's getCalendarActivities is used:
            // - Accounts_Module_Model uses 'status' (from CASE WHEN in SQL)
            // - Vtiger_Module_Model uses 'eventstatus' or 'taskstatus' directly from vtiger_activity
            $status = $activityModel->get('status') ?: $activityModel->get('eventstatus') ?: $activityModel->get('taskstatus') ?: '';
            $dateStart = $activityModel->get('date_start');
            $timeStart = $activityModel->get('time_start');
            $dueDate = $activityModel->get('due_date');
            $timeEnd = $activityModel->get('time_end');
            $description = $activityModel->get('description');
            $smownerId = $activityModel->get('smownerid');

            // Get assigned user information
            $assignedTo = null;
            if (!empty($smownerId)) {
                $ownerName = getOwnerName($smownerId);
                $assignedTo = array(
                    'id' => $smownerId,
                    'name' => $ownerName
                );
            }

            // Determine status field name based on activity type
            $statusField = ($activityType === 'Task') ? 'taskstatus' : 'eventstatus';

            // Get status picklist options
            $statusOptions = $this->getStatusOptions($statusField);

            // Check edit permission
            $canEdit = isPermitted('Calendar', 'EditView', $activityId) === 'yes';

            // Build detail view URL
            $detailViewUrl = 'index.php?module=Calendar&view=Detail&record=' . $activityId;

            // Build activity data
            $activityData = array(
                'id' => $activityId,
                'subject' => decode_html($subject),
                'activityType' => $activityType,
                'status' => $status,
                'statusField' => $statusField,
                'statusOptions' => $statusOptions,
                'canEdit' => $canEdit,
                'dateStart' => $dateStart,
                'timeStart' => $timeStart,
                'dueDate' => $dueDate,
                'timeEnd' => $timeEnd,
                'assignedTo' => $assignedTo,
                'description' => decode_html($description),
                'detailViewUrl' => $detailViewUrl
            );

            // Add priority if available
            $priority = $activityModel->get('taskpriority');
            if (!empty($priority)) {
                $activityData['priority'] = $priority;
            }

            // Add location if available (for Events)
            $location = $activityModel->get('location');
            if (!empty($location)) {
                $activityData['location'] = decode_html($location);
            }

            // Add common memo if available
            $commonMemo = $activityModel->get('common_memo');
            if (!empty($commonMemo)) {
                $activityData['commonMemo'] = decode_html($commonMemo);
            }

            return $activityData;

        } catch (Exception $e) {
            error_log("Calendar_GetActivities_Api formatting error for activity {$activityModel->getId()}: " . $e->getMessage());
            // Return minimal data if formatting fails
            return array(
                'id' => $activityModel->getId(),
                'subject' => 'Error loading activity',
                'activityType' => '',
                'status' => '',
                'statusField' => '',
                'statusOptions' => array(),
                'canEdit' => false,
                'dateStart' => '',
                'timeStart' => '',
                'dueDate' => '',
                'timeEnd' => '',
                'assignedTo' => null,
                'description' => '',
                'detailViewUrl' => ''
            );
        }
    }

    /**
     * Get picklist options for a status field
     * @param string $fieldName Field name (taskstatus or eventstatus)
     * @return array Array of picklist options with value and label
     */
    private function getStatusOptions($fieldName) {
        try {
            // Get Calendar module model
            $moduleModel = Vtiger_Module_Model::getInstance('Calendar');
            if (empty($moduleModel)) {
                return array();
            }

            // Get field model for the status field
            $fieldModel = $moduleModel->getField($fieldName);
            if (empty($fieldModel)) {
                return array();
            }

            // Get picklist values
            $picklistValues = $fieldModel->getPicklistValues();
            if (empty($picklistValues)) {
                return array();
            }

            // Format as array of objects with value and label
            $statusOptions = array();
            foreach ($picklistValues as $value => $label) {
                $statusOptions[] = array(
                    'value' => $value,
                    'label' => $label
                );
            }

            return $statusOptions;

        } catch (Exception $e) {
            error_log("Calendar_GetActivities_Api: Error getting status options for field $fieldName: " . $e->getMessage());
            return array();
        }
    }
}
