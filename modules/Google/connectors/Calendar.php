<?php
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

vimport('~~/modules/WSAPP/synclib/connectors/TargetConnector.php');
vimport('~~/libraries/google-api-php-client/src/Google/Client.php');
vimport('~~/libraries/google-api-php-client/src/Google/Service/Calendar.php');

Class Google_Calendar_Connector extends WSAPP_TargetConnector {
    
    const maxBatchRequestCount = 50;

    protected $apiConnection;
    protected $totalRecords;
    protected $maxResults = 100;
    protected $createdRecords;
    
    protected $client;
    protected $service;

    protected $eventCalendarFieldMappingTableName = 'vtiger_google_event_calendar_mapping';
    protected $calendars;

    public function __construct($oauth2Connection) {
        $this->apiConnection = $oauth2Connection;
        $this->client = new Google_Client();
        $this->client->setClientId($oauth2Connection->getClientId());
        $this->client->setClientSecret($oauth2Connection->getClientSecret());
        $this->client->setRedirectUri($oauth2Connection->getRedirectUri());
        $this->client->setScopes($oauth2Connection->getScope());
        $this->client->setAccessType($oauth2Connection->getAccessType());
        $this->client->setApprovalPrompt($oauth2Connection->getApprovalPrompt());
        try {
            $this->client->setAccesstoken($oauth2Connection->getAccessToken());
        } catch(Exception $e) {} //suppressing invalid access-token exception
        $this->service = new Google_Service_Calendar($this->client);
    }

    public function getName() {
        return 'GoogleCalendar';
    }

    public function emailLookUp($emailIds) {
        $db = PearDatabase::getInstance();
        $sql = 'SELECT crmid FROM vtiger_emailslookup WHERE setype = "Contacts" AND value IN (' .  generateQuestionMarks($emailIds) . ')';
        $result = $db->pquery($sql,$emailIds);
        $crmIds = array();
        for($i=0;$i<$db->num_rows($result);$i++) {
            $crmIds[] = $db->query_result($result,$i,'crmid');
        }
        return $crmIds;
    }

    /**
     * Tarsform Google Records to Vtiger Records
     * @param <array> $targetRecords 
     * @return <array> tranformed Google Records
     */
    public function transformToSourceRecord($targetRecords, $user = false) {
        $entity = array();
        $calendarArray = array();
        foreach ($targetRecords as $googleRecord) {
            if ($googleRecord->getMode() != WSAPP_SyncRecordModel::WSAPP_DELETE_MODE) {
                if(!$user)
                    $user = Users_Record_Model::getCurrentUserModel();
                $entity = Vtiger_Functions::getMandatoryReferenceFields('Events');
                $entity['assigned_user_id'] = vtws_getWebserviceEntityId('Users', $user->id);
                $entity['subject'] = $googleRecord->getSubject();
                $entity['date_start'] = $googleRecord->getStartDate($user);
                $entity['location'] = $googleRecord->getWhere();
                $entity['time_start'] = $googleRecord->getStartTimeUTC($user);
                $entity['due_date'] = $googleRecord->getEndDate($user);
                $entity['time_end'] = $googleRecord->getEndTimeUTC($user);
                $entity['eventstatus'] = "Planned";
                $entity['activitytype'] = "Meeting";
                $entity['description'] = $googleRecord->getDescription();
                $entity['duration_hours'] = '00:00';
                $entity['visibility'] = $googleRecord->getVisibility($user);
                if (empty($entity['subject'])) {
                    $entity['subject'] = 'Google Event';
                }
                $attendees = $googleRecord->getAttendees();
                $entity['contactidlist'] = '';
                if(count($attendees)) {
                    $contactIds = $this->emailLookUp($attendees);
                    if(count($contactIds)) {
                        $entity['contactidlist'] = implode(';', $contactIds);
                    }
                }
            }

            $calendar = $this->getSynchronizeController()->getSourceRecordModel($entity);

            $calendar = $this->performBasicTransformations($googleRecord, $calendar);
            $calendar = $this->performBasicTransformationsToSourceRecords($calendar, $googleRecord);
            $calendarArray[] = $calendar;
        }

        return $calendarArray;
    }

    /**
     * Pull the events from google
     * @param <object> $SyncState
     * @return <array> google Records
     */
    public function pull($SyncState, $user = false) {
        try {
            return $this->getCalendar($SyncState, $user);
        } catch (Exception $e) {
            return array();
        }
    }
    
    /**
     * Function to convert datetime to RFC 3339 timestamp
     * @param <String> $date
     * @return <DateTime>
     */
    function googleFormat($date) {
        $datTime = new DateTime($date);
        $timeZone = new DateTimeZone('UTC');
        $datTime->setTimezone($timeZone);
        $googleFormat = $datTime->format('Y-m-d\TH:i:s\Z');
        return $googleFormat;
    }

    /**
     * Pull the events from google
     * @param <object> $SyncState
     * @return <array> google Records
     */
    public function getCalendar($SyncState, $user = false) {
        if($this->apiConnection->isTokenExpired()) {
            $this->apiConnection->refreshToken();
            $this->client->setAccessToken($this->apiConnection->getAccessToken());
            $this->service = new Google_Service_Calendar($this->client);
        }
        $query = array(
            'maxResults' => $this->maxResults,
            'orderBy' => 'updated',
            'singleEvents' => true,
        );
        
        if (Google_Utils_Helper::getSyncTime('Calendar', $user)) {
            $query['updatedMin'] = $this->googleFormat(Google_Utils_Helper::getSyncTime('Calendar', $user));
            //shows deleted by default
        }
        
        $calendarId = Google_Utils_Helper::getSelectedCalendarForUser($user);
        if(!isset($this->calendars)) {
            $this->calendars = $this->pullCalendars(true);
        }
        if(!in_array($calendarId, $this->calendars)) {
            $calendarId = 'primary';
        }

        try {
            $feed = $this->service->events->listEvents($calendarId,$query);
        } catch (Exception $e) {
            if($e->getCode() == 410) {
                $query['showDeleted'] = false;
                $feed = $this->service->events->listEvents($calendarId,$query);
            }
        }
        
        $calendarRecords = array();
        if($feed) {
            $calendarRecords = $feed->getItems();
            if($feed->getNextPageToken()) $this->totalRecords = $this->maxResults + 1;
        }
        
        if (count($calendarRecords) > 0) {
            $maxModifiedTime = date('Y-m-d H:i:s', strtotime(Google_Contacts_Model::vtigerFormat(end($calendarRecords)->getUpdated())) + 1);
        }

        $googleRecords = array();
        $googleEventIds = array();
        foreach ($calendarRecords as $i => $calendar) {
            $recordModel = Google_Calendar_Model::getInstanceFromValues(array('entity' => $calendar));
            $deleted = false;
            if ($calendar->getStatus() == 'cancelled') {
                $deleted = true;
            }
            if (!$deleted) {
                $recordModel->setType($this->getSynchronizeController()->getSourceType())->setMode(WSAPP_SyncRecordModel::WSAPP_UPDATE_MODE);
            } else {
                $recordModel->setType($this->getSynchronizeController()->getSourceType())->setMode(WSAPP_SyncRecordModel::WSAPP_DELETE_MODE);
            }
            $googleRecords[$calendar->getId()] = $recordModel;
            $googleEventIds[] = $calendar->getId();
        }
        $this->createdRecords = count($googleRecords);
        if (isset($maxModifiedTime)) {
            Google_Utils_Helper::updateSyncTime('Calendar', $maxModifiedTime, $user);
        } else {
            Google_Utils_Helper::updateSyncTime('Calendar', false, $user);
        }
        if(count($googleEventIds)) {
            $this->putGoogleEventCalendarMap($googleEventIds, $calendarId, $user);
        }
        return $googleRecords;
    }

    protected function putGoogleEventCalendarMap($event_ids, $calendar_id, $user) {
        if(is_array($event_ids) && count($event_ids)) {
            $db = PearDatabase::getInstance();
            $user_id = $user->getId();
            $sql = 'INSERT INTO vtiger_google_event_calendar_mapping (event_id, calendar_id, user_id) VALUES ';
            $sqlParams = array();
            foreach($event_ids as $event_id) {
                $sql .= '(?, ?, ?),';
                $sqlParams[] = $event_id;
                $sqlParams[] = $calendar_id;
                $sqlParams[] = $user_id;
            }
            $sql = substr_replace($sql, "", -1);
            $db->pquery('DELETE FROM vtiger_google_event_calendar_mapping WHERE event_id IN ('.generateQuestionMarks($event_ids).')',$event_ids);
            $db->pquery($sql,$sqlParams);
        }
    }

    protected function getGoogleEventCalendarMap($user) {
        $db = PearDatabase::getInstance();
        $map = array();
        $sql = 'SELECT event_id, calendar_id FROM vtiger_google_event_calendar_mapping WHERE user_id = ?';
        $res = $db->pquery($sql, array($user->getId()));
        $num_of_rows = $db->num_rows($res);
        for($i=0;$i<$num_of_rows;$i++) {
            $event_id = $db->query_result($res, $i, 'event_id');
            $calendar_id = $db->query_result($res, $i, 'calendar_id');
            $map[$event_id] = $calendar_id;
        }
        return $map;
    }

    /**
     * Push the vtiger records to google
     * @param <array> $records vtiger records to be pushed to google
     * @return <array> pushed records
     */
    public function push($records,$user) {
        //TODO : use batch requests        
        $calendarId = Google_Utils_Helper::getSelectedCalendarForUser($user);
        if(!isset($this->calendars)) {
            try {
                $this->calendars = $this->pullCalendars(true);
            } catch (Exception $e) {
                return $records;
            }
        }
        if(!in_array($calendarId, $this->calendars)) {
            $calendarId = 'primary';
        }

        $eventCalendarMap = $this->getGoogleEventCalendarMap($user);
        $newEventIds = array();
        foreach ($records as $record) {
            $entity = $record->get('entity');
            $eventCalendarId = 'primary';
            if($this->apiConnection->isTokenExpired()) {
                $this->apiConnection->refreshToken();
                $this->client->setAccessToken($this->apiConnection->getAccessToken());
                $this->service = new Google_Service_Calendar($this->client);
            }
            try {
                if ($record->getMode() == WSAPP_SyncRecordModel::WSAPP_UPDATE_MODE) {
                    if(array_key_exists($entity->getId(), $eventCalendarMap)) {
                        $eventCalendarId = $eventCalendarMap[$entity->getId()];
                    }
                    $newEntity = $this->service->events->update($eventCalendarId,$entity->getId(),$entity);
                    $record->set('entity', $newEntity);
                } else if ($record->getMode() == WSAPP_SyncRecordModel::WSAPP_DELETE_MODE) {
                    $record->set('entity', $entity);
                    if(array_key_exists($entity->getId(), $eventCalendarMap)) {
                        $eventCalendarId = $eventCalendarMap[$entity->getId()];
                    }
                    $newEntity = $this->service->events->delete($eventCalendarId,$entity->getId());
                } else {
                    $newEntity = $this->service->events->insert($calendarId,$entity);
                    $newEventIds[] = $newEntity->getId();
                    $record->set('entity', $newEntity);
                }
                
            } catch (Exception $e) {
                continue;
            }
        }
        if(count($newEventIds)) {
            $this->putGoogleEventCalendarMap($newEventIds, $calendarId, $user);
        }
        return $records;
    }

    /**
     * Tarsform  Vtiger Records to Google Records
     * @param <array> $vtEvents 
     * @return <array> tranformed vtiger Records
     */
    public function transformToTargetRecord($vtEvents, $user) {
        $records = array();
        foreach ($vtEvents as $vtEvent) {
            $newEvent = new Google_Service_Calendar_Event();

            if ($vtEvent->getMode() == WSAPP_SyncRecordModel::WSAPP_DELETE_MODE) {
                $newEvent->setId($vtEvent->get('_id'));
            } elseif($vtEvent->getMode() == WSAPP_SyncRecordModel::WSAPP_UPDATE_MODE && $vtEvent->get('_id')) {
                if($this->apiConnection->isTokenExpired()) {
                    $this->apiConnection->refreshToken();
                    try {
                        $this->client->setAccessToken($this->apiConnection->getAccessToken());
                    } catch(Exception $e) {}//suppressing invalid access-token exception if access revoked
                    $this->service = new Google_Service_Calendar($this->client);
                }
                try {
                    $calendarId = 'primary';
                    $eventCalendarMap = $this->getGoogleEventCalendarMap($user);
                    if(array_key_exists($vtEvent->get('_id'), $eventCalendarMap)) {
                        $calendarId = $eventCalendarMap[$vtEvent->get('_id')];
                    }
                    $newEvent = $this->service->events->get($calendarId, $vtEvent->get('_id'));
                } catch (Exception $e) {
                    continue;
                }
            }
            
            $newEvent->setSummary($vtEvent->get('subject'));
            $newEvent->setLocation($vtEvent->get('location'));
            $newEvent->setDescription($vtEvent->get('description'));
            $newEvent->setVisibility(strtolower($vtEvent->get('visibility')));
            
            $startDate = $vtEvent->get('date_start');
            $startTime = $vtEvent->get('time_start');
            $endDate = $vtEvent->get('due_date');
            $endTime = $vtEvent->get('time_end');
            if (empty($endTime)) {
                $endTime = "00:00";
            }
            $start = new Google_Service_Calendar_EventDateTime();
            $start->setDateTime($this->googleFormat($startDate . ' ' . $startTime));
            $newEvent->setStart($start);
            
            $end = new Google_Service_Calendar_EventDateTime();
            $end->setDateTime($this->googleFormat($endDate. ' ' .$endTime)); 
            $newEvent->setEnd($end);
            
			/**
			 * Commenting out adding attendees in google
            //attendees
            $googleAttendees = array();
            $newEvent->setAttendees($googleAttendees);
            $attendees = $vtEvent->get('attendees');
            if(isset($attendees)) {
                foreach($attendees as $attendee) {
                    if(!empty($attendee['email'])) {
                        $eventAttendee = new Google_Service_Calendar_EventAttendee();
                        $eventAttendee->setEmail($attendee['email']);
                        $googleAttendees[] = $eventAttendee;
                    }
                }
                if(count($googleAttendees)) $newEvent->setAttendees($googleAttendees);
            }
			*/

            $recordModel = Google_Calendar_Model::getInstanceFromValues(array('entity' => $newEvent));
            $recordModel->setType($this->getSynchronizeController()->getSourceType())->setMode($vtEvent->getMode())->setSyncIdentificationKey($vtEvent->get('_syncidentificationkey'));
            $recordModel = $this->performBasicTransformations($vtEvent, $recordModel);
            $recordModel = $this->performBasicTransformationsToTargetRecords($recordModel, $vtEvent);
            $records[] = $recordModel;
        }
        return $records;
    }

    /**
     * returns if more records exits or not
     * @return <boolean> true or false
     */
    public function moreRecordsExits() {
        return ($this->totalRecords - $this->createdRecords > 0) ? true : false;
    }

    public function pullCalendars($list=false) {
        $calendarList = $this->service->calendarList->listCalendarList();
        $allCalendarsItems = array();
        while(true) {
            $calendarItems = $calendarList->getItems();
            if(is_array($calendarItems))
                $allCalendarsItems = array_merge($allCalendarsItems, $calendarItems);

            $pageToken = $calendarList->getNextPageToken();
            if ($pageToken) {
                $optParams = array('pageToken' => $pageToken);
                $calendarList = $this->service->calendarList->listCalendarList($optParams);
            } else {
                break;
            }
        }
        $calendars = array();
        if($list) {
            foreach($allCalendarsItems as $calendarItem) {
                if(!$calendarItem->getPrimary())
                    $calendars[] = $calendarItem->getId();
                else
                    $calendars[] = 'primary';
            }
            return $calendars;
        }
        foreach($allCalendarsItems as $calendarItem) {
            $calendars[] = array(
                'id' => $calendarItem->getId(),
                'summary' => $calendarItem->getSummary(),
                'primary' => $calendarItem->getPrimary()
            );
        }
        return $calendars;
    }

}
?>

