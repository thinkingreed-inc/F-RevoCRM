-- MySQL dump 10.13  Distrib 8.0.43, for Linux (x86_64)
--
-- Host: localhost    Database: fr_remicck
-- ------------------------------------------------------
-- Server version	8.0.43

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `com_vtiger_migrations`
--

DROP TABLE IF EXISTS `com_vtiger_migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `com_vtiger_migrations` (
  `migration_name` varchar(255) NOT NULL,
  `executed_at` datetime NOT NULL,
  PRIMARY KEY (`migration_name`),
  KEY `idx_executed_at` (`executed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `com_vtiger_migrations`
--

LOCK TABLES `com_vtiger_migrations` WRITE;
/*!40000 ALTER TABLE `com_vtiger_migrations` DISABLE KEYS */;
INSERT INTO `com_vtiger_migrations` VALUES ('Migration20251020154523_AlterUserLockTableAllowNullLockTime','2026-07-01 16:57:04'),('Migration20251020154809_AddPrimaryKeyToUserLockTable','2026-07-01 16:57:05'),('Migration20251028162441_AddImportSystemVariable','2026-07-01 16:57:05'),('Migration20251106184202_AddLoginTypeToLoginhistory','2026-07-01 16:57:05'),('Migration20251111191427_UpdateModuleNameFieldLength','2026-07-01 16:57:06'),('Migration20251127120006_FixDuplicateHelpdeskAssetsRelation','2026-07-01 16:57:06'),('Migration20251213070441_AddEditreadonlydisplay','2026-07-01 16:57:06'),('Migration20251213164742_AddChangeScheduleConfirmFlagForParameter','2026-07-01 16:57:06'),('Migration20260116155919_CalendarRememberFeedSelection','2026-07-01 16:57:06'),('Migration20260119191046_AddCommonMemoFieldToEvent','2026-07-01 16:57:06'),('Migration20260119191046_AddSendMail','2026-07-01 16:57:06'),('Migration20260205065656_FixWorkflowTaskSerialization','2026-07-01 16:57:07'),('Migration20260327101100_UpdateFieldTablename','2026-07-01 16:57:07'),('Migration20250930102426_AddColumnToImportQueue','2026-07-01 16:57:10'),('Migration20251014141127_AddIframeWidget','2026-07-01 16:57:10'),('Migration20260515014512_UpdateTextareaColumn','2026-07-01 16:57:10'),('Migration20260518161627_FixErrorReportingForPhp85','2026-07-01 16:57:10'),('Migration20260528000000_FixFkConstraintsForMysql84','2026-07-01 16:57:11'),('Migration20260601082359_FixDuplicateProductsPurchaseorderRelation','2026-07-01 16:57:11'),('Migration20260604171138_MoveLogoToPublic','2026-07-01 16:57:11'),('Migration20260615103558_ChangeMailscannerUniqueidFormat','2026-07-01 16:57:11'),('Migration20260615112644_FixHelpdeskDocumentsEntitynameFieldname','2026-07-01 16:57:11');
/*!40000 ALTER TABLE `com_vtiger_migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `com_vtiger_workflow_activatedonce`
--

DROP TABLE IF EXISTS `com_vtiger_workflow_activatedonce`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `com_vtiger_workflow_activatedonce` (
  `workflow_id` int NOT NULL,
  `entity_id` int NOT NULL,
  PRIMARY KEY (`workflow_id`,`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `com_vtiger_workflow_activatedonce`
--

LOCK TABLES `com_vtiger_workflow_activatedonce` WRITE;
/*!40000 ALTER TABLE `com_vtiger_workflow_activatedonce` DISABLE KEYS */;
/*!40000 ALTER TABLE `com_vtiger_workflow_activatedonce` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `com_vtiger_workflow_tasktypes`
--

DROP TABLE IF EXISTS `com_vtiger_workflow_tasktypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `com_vtiger_workflow_tasktypes` (
  `id` int NOT NULL,
  `tasktypename` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `classname` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `classpath` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `templatepath` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `modules` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sourcemodule` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `com_vtiger_workflow_tasktypes`
--

LOCK TABLES `com_vtiger_workflow_tasktypes` WRITE;
/*!40000 ALTER TABLE `com_vtiger_workflow_tasktypes` DISABLE KEYS */;
INSERT INTO `com_vtiger_workflow_tasktypes` VALUES (1,'VTEmailTask','Send Mail','VTEmailTask','modules/com_vtiger_workflow/tasks/VTEmailTask.inc','modules/Settings/Workflows/Tasks/VTEmailTask.tpl','{\"include\":[],\"exclude\":[]}',''),(2,'VTEntityMethodTask','Invoke Custom Function','VTEntityMethodTask','modules/com_vtiger_workflow/tasks/VTEntityMethodTask.inc','modules/Settings/Workflows/Tasks/VTEntityMethodTask.tpl','{\"include\":[],\"exclude\":[]}',''),(3,'VTCreateTodoTask','Create Todo','VTCreateTodoTask','modules/com_vtiger_workflow/tasks/VTCreateTodoTask.inc','modules/Settings/Workflows/Tasks/VTCreateTodoTask.tpl','{\"include\":[\"Leads\",\"Accounts\",\"Potentials\",\"Contacts\",\"HelpDesk\",\"Campaigns\",\"Quotes\",\"PurchaseOrder\",\"SalesOrder\",\"Invoice\",\"Project\"],\"exclude\":[\"Calendar\",\"FAQ\",\"Events\"]}',''),(4,'VTCreateEventTask','Create Event','VTCreateEventTask','modules/com_vtiger_workflow/tasks/VTCreateEventTask.inc','modules/Settings/Workflows/Tasks/VTCreateEventTask.tpl','{\"include\":[\"Leads\",\"Accounts\",\"Potentials\",\"Contacts\",\"HelpDesk\",\"Campaigns\",\"Project\"],\"exclude\":[\"Calendar\",\"FAQ\",\"Events\"]}',''),(5,'VTUpdateFieldsTask','Update Fields','VTUpdateFieldsTask','modules/com_vtiger_workflow/tasks/VTUpdateFieldsTask.inc','modules/Settings/Workflows/Tasks/VTUpdateFieldsTask.tpl','{\"include\":[],\"exclude\":[]}',''),(6,'VTCreateEntityTask','Create Entity','VTCreateEntityTask','modules/com_vtiger_workflow/tasks/VTCreateEntityTask.inc','modules/Settings/Workflows/Tasks/VTCreateEntityTask.tpl','{\"include\":[],\"exclude\":[]}',''),(7,'VTSMSTask','SMS Task','VTSMSTask','modules/com_vtiger_workflow/tasks/VTSMSTask.inc','modules/Settings/Workflows/Tasks/VTSMSTask.tpl','{\"include\":[],\"exclude\":[]}','SMSNotifier');
/*!40000 ALTER TABLE `com_vtiger_workflow_tasktypes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `com_vtiger_workflow_tasktypes_seq`
--

DROP TABLE IF EXISTS `com_vtiger_workflow_tasktypes_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `com_vtiger_workflow_tasktypes_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `com_vtiger_workflow_tasktypes_seq`
--

LOCK TABLES `com_vtiger_workflow_tasktypes_seq` WRITE;
/*!40000 ALTER TABLE `com_vtiger_workflow_tasktypes_seq` DISABLE KEYS */;
INSERT INTO `com_vtiger_workflow_tasktypes_seq` VALUES (7);
/*!40000 ALTER TABLE `com_vtiger_workflow_tasktypes_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `com_vtiger_workflows`
--

DROP TABLE IF EXISTS `com_vtiger_workflows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `com_vtiger_workflows` (
  `workflow_id` int NOT NULL AUTO_INCREMENT,
  `module_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `summary` varchar(400) COLLATE utf8mb4_general_ci NOT NULL,
  `test` text COLLATE utf8mb4_general_ci NOT NULL,
  `execution_condition` int NOT NULL,
  `defaultworkflow` int DEFAULT NULL,
  `type` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `filtersavedinnew` int DEFAULT NULL,
  `schtypeid` int DEFAULT NULL,
  `schdayofmonth` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `schdayofweek` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `schannualdates` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `schtime` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nexttrigger_time` datetime DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `workflowname` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`workflow_id`),
  UNIQUE KEY `com_vtiger_workflows_idx` (`workflow_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `com_vtiger_workflows`
--

LOCK TABLES `com_vtiger_workflows` WRITE;
/*!40000 ALTER TABLE `com_vtiger_workflows` DISABLE KEYS */;
INSERT INTO `com_vtiger_workflows` VALUES (1,'Invoice','LBL_WORKFLOW_TO_UPDATE_SALES_MANAGEMENT','[{\"fieldname\":\"subject\",\"operation\":\"does not contain\",\"value\":\"`!`\"}]',3,1,'basic',5,NULL,NULL,NULL,NULL,NULL,NULL,1,'LBL_WORKFLOW_TO_UPDATE_SALES_MANAGEMENT'),(2,'Contacts','LBL_A_WORKFLOW_SENDS_EMAIL_TO_NOTIFICATION_PERSON_CHARGE_TURNED_ON','[{\"fieldname\":\"notify_owner\",\"operation\":\"is\",\"value\":\"true:boolean\"}]',2,1,'basic',5,NULL,NULL,NULL,NULL,NULL,NULL,1,'LBL_A_WORKFLOW_SENDS_EMAIL_TO_NOTIFICATION_PERSON_CHARGE_TURNED_ON'),(3,'Contacts','LBL_A_WORKFLOW_SENDS_EMAIL_TO_PORTALUSER_IS_ON','[{\"fieldname\":\"portal\",\"operation\":\"is\",\"value\":\"1\",\"valuetype\":\"rawtext\",\"joincondition\":\"and\",\"groupjoin\":\"and\",\"groupid\":\"0\"},{\"fieldname\":\"email\",\"operation\":\"is not empty\",\"value\":\"\",\"valuetype\":\"rawtext\",\"joincondition\":\"\",\"groupjoin\":\"and\",\"groupid\":\"0\"}]',3,1,'basic',6,NULL,NULL,NULL,NULL,NULL,NULL,1,'LBL_A_WORKFLOW_SENDS_EMAIL_TO_PORTALUSER_IS_ON'),(4,'Contacts','LBL_WORKFLOW_CUSTOMER_CONTACT_IS_CREATED_OR_UPDATED','',3,1,'basic',5,NULL,NULL,NULL,NULL,NULL,NULL,1,'LBL_WORKFLOW_CUSTOMER_CONTACT_IS_CREATED_OR_UPDATED'),(5,'Events','LBL_WORKFLOW_FOR_ACTIVITIES_WHEN_NOTIFICATIONS_ARE_ON','[{\"fieldname\":\"sendnotification\",\"operation\":\"is\",\"value\":\"true:boolean\"}]',3,1,'basic',5,NULL,NULL,NULL,NULL,NULL,NULL,1,'LBL_WORKFLOW_FOR_ACTIVITIES_WHEN_NOTIFICATIONS_ARE_ON'),(6,'Calendar','LBL_TODO_WORKFLOW_WHEN_NOTIFICATIONS_ARE_ON','[{\"fieldname\":\"sendnotification\",\"operation\":\"is\",\"value\":\"true:boolean\"}]',3,1,'basic',5,NULL,NULL,NULL,NULL,NULL,NULL,1,'LBL_TODO_WORKFLOW_WHEN_NOTIFICATIONS_ARE_ON'),(7,'Potentials','LBL_WORKFLOW_TO_UPDATE_FORECAST_AMOUNTS','',3,1,'basic',5,NULL,NULL,NULL,NULL,NULL,NULL,1,'LBL_WORKFLOW_TO_UPDATE_FORECAST_AMOUNTS'),(8,'Events','LBL_WORKFLOW_FOR_ACTIVITIES_WHEN_NOTIFICATIONS_ARE_ON','[{\"fieldname\":\"sendnotification\",\"operation\":\"is\",\"value\":\"true:boolean\"}]',3,1,'basic',5,NULL,NULL,NULL,NULL,NULL,NULL,1,'LBL_WORKFLOW_FOR_ACTIVITIES_WHEN_NOTIFICATIONS_ARE_ON'),(9,'Calendar','LBL_TODO_WORKFLOW_WHEN_NOTIFICATIONS_ARE_ON','[{\"fieldname\":\"sendnotification\",\"operation\":\"is\",\"value\":\"true:boolean\"}]',3,1,'basic',5,NULL,NULL,NULL,NULL,NULL,NULL,1,'LBL_TODO_WORKFLOW_WHEN_NOTIFICATIONS_ARE_ON'),(10,'HelpDesk','LBL_A_WORKFLOW_TO_SEND_AN_EMAIL_WHEN_A_COMMENT_IS_ADDED','[{\"fieldname\":\"_VT_add_comment\",\"operation\":\"is added\",\"value\":\"\",\"valuetype\":\"rawtext\",\"joincondition\":\"\",\"groupjoin\":\"and\",\"groupid\":\"0\"},{\"fieldname\":\"from_portal\",\"operation\":\"is\",\"value\":\"0\",\"valuetype\":\"rawtext\",\"joincondition\":\"\",\"groupjoin\":\"and\",\"groupid\":\"0\"},{\"fieldname\":\"(parent_id : (Accounts) emailoptout)\",\"operation\":\"is\",\"value\":\"0\",\"valuetype\":\"rawtext\",\"joincondition\":\"and\",\"groupjoin\":\"and\",\"groupid\":\"0\"}]',3,1,'basic',6,NULL,NULL,NULL,NULL,NULL,NULL,1,'LBL_A_WORKFLOW_TO_SEND_AN_EMAIL_WHEN_A_COMMENT_IS_ADDED'),(12,'HelpDesk','LBL_TASK_SEND_EMAIL_WHEN_COMMENTED_FROM_PORTAL','[{\"fieldname\":\"_VT_add_comment\",\"operation\":\"is added\",\"value\":\"\",\"valuetype\":\"rawtext\",\"joincondition\":\"\",\"groupjoin\":\"and\",\"groupid\":\"0\"},{\"fieldname\":\"from_portal\",\"operation\":\"is\",\"value\":\"1\",\"valuetype\":\"rawtext\",\"joincondition\":\"\",\"groupjoin\":\"and\",\"groupid\":\"0\"}]',3,1,'basic',6,NULL,NULL,NULL,NULL,NULL,NULL,1,'LBL_TASK_SEND_EMAIL_WHEN_COMMENTED_FROM_PORTAL'),(13,'HelpDesk','LBL_TASK_SEND_EMAIL_TO_A_CUSTOMER_REPRESENTATIVE_WHEN_A_COMMENT_IS_ADDED','[{\"fieldname\":\"(contact_id : (Contacts) portal)\",\"operation\":\"is\",\"value\":\"0\",\"valuetype\":\"rawtext\",\"joincondition\":\"and\",\"groupjoin\":\"and\",\"groupid\":\"0\"},{\"fieldname\":\"_VT_add_comment\",\"operation\":\"is added\",\"value\":\"\",\"valuetype\":\"rawtext\",\"joincondition\":\"\",\"groupjoin\":\"and\",\"groupid\":\"0\"},{\"fieldname\":\"from_portal\",\"operation\":\"is\",\"value\":\"0\",\"valuetype\":\"rawtext\",\"joincondition\":\"\",\"groupjoin\":\"and\",\"groupid\":\"0\"},{\"fieldname\":\"(contact_id : (Contacts) emailoptout)\",\"operation\":\"is\",\"value\":\"0\",\"valuetype\":\"rawtext\",\"joincondition\":\"and\",\"groupjoin\":\"and\",\"groupid\":\"0\"}]',3,1,'basic',6,NULL,NULL,NULL,NULL,NULL,NULL,1,'LBL_TASK_SEND_EMAIL_TO_A_CUSTOMER_REPRESENTATIVE_WHEN_A_COMMENT_IS_ADDED'),(14,'HelpDesk','LBL_TASK_SEND_EMAIL_WHEN_COMMENTED','[{\"fieldname\":\"(contact_id : (Contacts) portal)\",\"operation\":\"is\",\"value\":\"1\",\"valuetype\":\"rawtext\",\"joincondition\":\"and\",\"groupjoin\":\"and\",\"groupid\":\"0\"},{\"fieldname\":\"_VT_add_comment\",\"operation\":\"is added\",\"value\":\"\",\"valuetype\":\"rawtext\",\"joincondition\":\"\",\"groupjoin\":\"and\",\"groupid\":\"0\"},{\"fieldname\":\"from_portal\",\"operation\":\"is\",\"value\":\"0\",\"valuetype\":\"rawtext\",\"joincondition\":\"\",\"groupjoin\":\"and\",\"groupid\":\"0\"},{\"fieldname\":\"(contact_id : (Contacts) emailoptout)\",\"operation\":\"is\",\"value\":\"0\",\"valuetype\":\"rawtext\",\"joincondition\":\"and\",\"groupjoin\":\"and\",\"groupid\":\"0\"}]',3,1,'basic',6,NULL,NULL,NULL,NULL,NULL,NULL,1,'LBL_TASK_SEND_EMAIL_WHEN_COMMENTED');
/*!40000 ALTER TABLE `com_vtiger_workflows` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `com_vtiger_workflows_seq`
--

DROP TABLE IF EXISTS `com_vtiger_workflows_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `com_vtiger_workflows_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `com_vtiger_workflows_seq`
--

LOCK TABLES `com_vtiger_workflows_seq` WRITE;
/*!40000 ALTER TABLE `com_vtiger_workflows_seq` DISABLE KEYS */;
INSERT INTO `com_vtiger_workflows_seq` VALUES (14);
/*!40000 ALTER TABLE `com_vtiger_workflows_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `com_vtiger_workflowtask_queue`
--

DROP TABLE IF EXISTS `com_vtiger_workflowtask_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `com_vtiger_workflowtask_queue` (
  `task_id` int DEFAULT NULL,
  `entity_id` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `do_after` int DEFAULT NULL,
  `relatedinfo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `task_contents` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `com_vtiger_workflowtask_queue`
--

LOCK TABLES `com_vtiger_workflowtask_queue` WRITE;
/*!40000 ALTER TABLE `com_vtiger_workflowtask_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `com_vtiger_workflowtask_queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `com_vtiger_workflowtasks`
--

DROP TABLE IF EXISTS `com_vtiger_workflowtasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `com_vtiger_workflowtasks` (
  `task_id` int NOT NULL AUTO_INCREMENT,
  `workflow_id` int DEFAULT NULL,
  `summary` varchar(400) COLLATE utf8mb4_general_ci NOT NULL,
  `task` text COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`task_id`),
  UNIQUE KEY `com_vtiger_workflowtasks_idx` (`task_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `com_vtiger_workflowtasks`
--

LOCK TABLES `com_vtiger_workflowtasks` WRITE;
/*!40000 ALTER TABLE `com_vtiger_workflowtasks` DISABLE KEYS */;
INSERT INTO `com_vtiger_workflowtasks` VALUES (1,1,'','O:18:\"VTEntityMethodTask\":6:{s:18:\"executeImmediately\";b:1;s:10:\"workflowId\";i:1;s:7:\"summary\";s:0:\"\";s:6:\"active\";b:0;s:10:\"methodName\";s:15:\"UpdateInventory\";s:2:\"id\";i:1;}'),(2,2,'An contact has been created ','O:11:\"VTEmailTask\":9:{s:18:\"executeImmediately\";s:0:\"\";s:10:\"workflowId\";s:1:\"2\";s:7:\"summary\";s:28:\"An contact has been created \";s:6:\"active\";s:0:\"1\";s:10:\"methodName\";s:11:\"NotifyOwner\";s:9:\"recepient\";s:36:\"$(assigned_user_id : (Users) email1)\";s:7:\"subject\";s:26:\"Regarding Contact Creation\";s:7:\"content\";s:305:\"An Contact has been assigned to you on F-RevoCRM<br>Details of Contact are :<br><br>Contact Id:<b>$contact_no</b><br>LastName:<b>$lastname</b><br>FirstName:<b>$firstname</b><br>Lead Source:<b>$leadsource</b><br>Department:<b>$department</b><br>Description:<b>$description</b><br><br><br>Thank You<br>Admin\";s:2:\"id\";s:1:\"2\";}'),(3,3,'Email Customer Portal Login Details','O:18:\"VTEntityMethodTask\":6:{s:18:\"executeImmediately\";b:1;s:10:\"workflowId\";i:3;s:7:\"summary\";s:35:\"Email Customer Portal Login Details\";s:6:\"active\";b:0;s:10:\"methodName\";s:22:\"SendPortalLoginDetails\";s:2:\"id\";i:3;}'),(4,4,'An contact has been created ','O:11:\"VTEmailTask\":8:{s:18:\"executeImmediately\";s:0:\"\";s:10:\"workflowId\";s:1:\"4\";s:7:\"summary\";s:28:\"An contact has been created \";s:6:\"active\";s:0:\"1\";s:9:\"recepient\";s:36:\"$(assigned_user_id : (Users) email1)\";s:7:\"subject\";s:28:\"Regarding Contact Assignment\";s:7:\"content\";s:384:\"An Contact has been assigned to you on F-RevoCRM<br>Details of Contact are :<br><br>Contact Id:<b>$contact_no</b><br>LastName:<b>$lastname</b><br>FirstName:<b>$firstname</b><br>Lead Source:<b>$leadsource</b><br>Department:<b>$department</b><br>Description:<b>$description</b><br><br><br>And <b>CustomerPortal Login Details</b> is sent to the EmailID :-$email<br><br>Thank You<br>Admin\";s:2:\"id\";s:1:\"4\";}'),(5,5,'LBL_ACTIVITY_NOTIFICATION_EMAIL','O:11:\"VTEmailTask\":8:{s:18:\"executeImmediately\";s:0:\"\";s:10:\"workflowId\";s:1:\"5\";s:7:\"summary\";s:31:\"LBL_ACTIVITY_NOTIFICATION_EMAIL\";s:6:\"active\";s:0:\"1\";s:9:\"recepient\";s:36:\"$(assigned_user_id : (Users) email1)\";s:7:\"subject\";s:30:\"LBL_ANNOUNCEMENT_OF_ACTIVITIES\";s:7:\"content\";s:40:\"LBL_ACTIVITY_NOTIFICATION_FROM_F-RevoCRM\";s:2:\"id\";s:1:\"5\";}'),(6,6,'LBL_TODO_NOTIFICATION_EMAIL','O:11:\"VTEmailTask\":8:{s:18:\"executeImmediately\";s:0:\"\";s:10:\"workflowId\";s:1:\"6\";s:7:\"summary\";s:27:\"LBL_TODO_NOTIFICATION_EMAIL\";s:6:\"active\";s:0:\"1\";s:9:\"recepient\";s:36:\"$(assigned_user_id : (Users) email1)\";s:7:\"subject\";s:15:\"LBL_TODO_NOTICE\";s:7:\"content\";s:36:\"LBL_TODO_NOTIFICATION_FROM_F-REVOCRM\";s:2:\"id\";s:1:\"6\";}'),(7,7,'LBL_UPDATE_ESTIMATED_AMOUNT','O:18:\"VTUpdateFieldsTask\":7:{s:18:\"executeImmediately\";b:1;s:43:\"\0VTUpdateFieldsTask\0referenceFieldFocusList\";a:0:{}s:10:\"workflowId\";i:7;s:7:\"summary\";s:27:\"LBL_UPDATE_ESTIMATED_AMOUNT\";s:6:\"active\";b:0;s:19:\"field_value_mapping\";s:95:\"[{\"fieldname\":\"forecast_amount\",\"valuetype\":\"expression\",\"value\":\"amount * probability / 100\"}]\";s:2:\"id\";i:7;}'),(8,8,'Send Notification Email to Record Owner','O:11:\"VTEmailTask\":8:{s:18:\"executeImmediately\";s:0:\"\";s:10:\"workflowId\";s:1:\"8\";s:7:\"summary\";s:39:\"Send Notification Email to Record Owner\";s:6:\"active\";s:0:\"1\";s:9:\"recepient\";s:36:\"$(assigned_user_id : (Users) email1)\";s:7:\"subject\";s:17:\"Event :  $subject\";s:7:\"content\";s:771:\"$(assigned_user_id : (Users) last_name) $(assigned_user_id : (Users) first_name) ,<br/><b>Activity Notification Details:</b><br/>Subject             : $subject<br/>Start date and time : $date_start ($(general : (__VtigerMeta__) usertimezone))<br/>End date and time   : $due_date ($(general : (__VtigerMeta__) usertimezone)) <br/>Status              : $eventstatus <br/>Priority            : $taskpriority <br/>Related To          : $(parent_id : (Leads) lastname) $(parent_id : (Leads) firstname) $(parent_id : (Accounts) accountname) $(parent_id : (Potentials) potentialname) $(parent_id : (HelpDesk) ticket_title) $(parent_id : (Campaigns) campaignname) <br/>Contacts List       : $contact_id <br/>Location            : $location <br/>Description         : $description\";s:2:\"id\";s:1:\"8\";}'),(9,9,'Send Notification Email to Record Owner','O:11:\"VTEmailTask\":8:{s:18:\"executeImmediately\";s:0:\"\";s:10:\"workflowId\";s:1:\"9\";s:7:\"summary\";s:39:\"Send Notification Email to Record Owner\";s:6:\"active\";s:0:\"1\";s:9:\"recepient\";s:36:\"$(assigned_user_id : (Users) email1)\";s:7:\"subject\";s:16:\"Task :  $subject\";s:7:\"content\";s:689:\"$(assigned_user_id : (Users) last_name) $(assigned_user_id : (Users) first_name) ,<br/><b>Task Notification Details:</b><br/>Subject : $subject<br/>Start date and time : $date_start ($(general : (__VtigerMeta__) usertimezone))<br/>End date and time   : $due_date ($_DATE_FORMAT_) <br/>Status              : $taskstatus <br/>Priority            : $taskpriority <br/>Related To          : $(parent_id : (Leads) lastname) $(parent_id : (Leads) firstname) $(parent_id : (Accounts) accountname) $(parent_id : (Potentials) potentialname) $(parent_id : (HelpDesk) ticket_title) $(parent_id : (Campaigns) campaignname) <br/>Contacts List       : $contact_id <br/>Description         : $description\";s:2:\"id\";s:1:\"9\";}'),(12,11,'LBL_UPDATE_INVENTORY_PRODUCTS','O:18:\"VTEntityMethodTask\":6:{s:18:\"executeImmediately\";b:1;s:10:\"workflowId\";i:11;s:7:\"summary\";s:29:\"LBL_UPDATE_INVENTORY_PRODUCTS\";s:6:\"active\";b:0;s:10:\"methodName\";s:15:\"UpdateInventory\";s:2:\"id\";i:12;}'),(13,12,'LBL_TASK_SEND_EMAIL_WHEN_COMMENTED_FROM_PORTAL','O:11:\"VTEmailTask\":9:{s:18:\"executeImmediately\";s:1:\"0\";s:7:\"summary\";s:46:\"LBL_TASK_SEND_EMAIL_WHEN_COMMENTED_FROM_PORTAL\";s:6:\"active\";s:0:\"1\";s:2:\"id\";s:2:\"13\";s:10:\"workflowId\";s:2:\"12\";s:9:\"fromEmail\";s:112:\"$(contact_id : (Contacts) lastname) $(contact_id : (Contacts) firstname)&lt;$(contact_id : (Contacts) email)&gt;\";s:9:\"recepient\";s:37:\",$(assigned_user_id : (Users) email1)\";s:7:\"subject\";s:92:\"Respond to Ticket ID## $(general : (__VtigerMeta__) recordId) ## in Customer Portal - URGENT\";s:7:\"content\";s:325:\"Dear $(assigned_user_id : (Users) last_name) $(assigned_user_id : (Users) first_name),<br><br>\n								Customer has provided the following additional information to your reply:<br><br>\n								<b>$lastComment</b><br><br>\n								Kindly respond to above ticket at the earliest.<br><br>\n								Regards<br>Support Administrator\";}'),(14,13,'LBL_TASK_SEND_EMAIL_TO_A_CUSTOMER_REPRESENTATIVE_WHEN_A_COMMENT_IS_ADDED','O:11:\"VTEmailTask\":9:{s:18:\"executeImmediately\";s:1:\"0\";s:7:\"summary\";s:72:\"LBL_TASK_SEND_EMAIL_TO_A_CUSTOMER_REPRESENTATIVE_WHEN_A_COMMENT_IS_ADDED\";s:6:\"active\";s:0:\"1\";s:2:\"id\";s:2:\"14\";s:10:\"workflowId\";s:2:\"13\";s:9:\"fromEmail\";s:93:\"$(general : (__VtigerMeta__) supportName)&lt;$(general : (__VtigerMeta__) supportEmailId)&gt;\";s:9:\"recepient\";s:33:\",$(contact_id : (Contacts) email)\";s:7:\"subject\";s:79:\"$ticket_no [ Ticket Id : $(general : (__VtigerMeta__) recordId) ] $ticket_title\";s:7:\"content\";s:514:\"Dear $(contact_id : (Contacts) lastname) $(contact_id : (Contacts) firstname),<br><br>\n							The Ticket is replied the details are :<br><br>\n							Ticket No : $ticket_no<br>\n							Status : $ticketstatus<br>\n							Category : $ticketcategories<br>\n							Severity : $ticketseverities<br>\n							Priority : $ticketpriorities<br><br>\n							Description : <br>$description<br><br>\n							Solution : <br>$solution<br>\n							The comments are : <br>\n							$allComments<br><br>\n							Regards<br>Support Administrator\";}'),(15,14,'LBL_TASK_SEND_EMAIL_WHEN_COMMENTED','O:11:\"VTEmailTask\":9:{s:18:\"executeImmediately\";s:1:\"0\";s:7:\"summary\";s:34:\"LBL_TASK_SEND_EMAIL_WHEN_COMMENTED\";s:6:\"active\";s:0:\"1\";s:2:\"id\";s:2:\"15\";s:10:\"workflowId\";s:2:\"14\";s:9:\"fromEmail\";s:93:\"$(general : (__VtigerMeta__) supportName)&lt;$(general : (__VtigerMeta__) supportEmailId)&gt;\";s:9:\"recepient\";s:33:\",$(contact_id : (Contacts) email)\";s:7:\"subject\";s:79:\"$ticket_no [ Ticket Id : $(general : (__VtigerMeta__) recordId) ] $ticket_title\";s:7:\"content\";s:547:\"Ticket No : $ticket_no<br>\n										Ticket Id : $(general : (__VtigerMeta__) recordId)<br>\n										Subject : $ticket_title<br><br>\n										Dear $(contact_id : (Contacts) lastname) $(contact_id : (Contacts) firstname),<br><br>\n										There is a reply to <b>$ticket_title</b> in the \"Customer Portal\" at VTiger.\n										You can use the following link to view the replies made:<br>\n										<a href=\"$(general : (__VtigerMeta__) portaldetailviewurl)\">Ticket Details</a><br><br>\n										Thanks<br>$(general : (__VtigerMeta__) supportName)\";}'),(16,10,'LBL_A_WORKFLOW_TO_SEND_AN_EMAIL_WHEN_A_COMMENT_IS_ADDED','O:11:\"VTEmailTask\":9:{s:18:\"executeImmediately\";s:1:\"0\";s:7:\"summary\";s:55:\"LBL_A_WORKFLOW_TO_SEND_AN_EMAIL_WHEN_A_COMMENT_IS_ADDED\";s:6:\"active\";s:0:\"1\";s:2:\"id\";s:2:\"16\";s:10:\"workflowId\";s:2:\"10\";s:9:\"fromEmail\";s:93:\"$(general : (__VtigerMeta__) supportName)&lt;$(general : (__VtigerMeta__) supportEmailId)&gt;\";s:9:\"recepient\";s:34:\",$(parent_id : (Accounts) email1),\";s:7:\"subject\";s:79:\"$ticket_no [ Ticket Id : $(general : (__VtigerMeta__) recordId) ] $ticket_title\";s:7:\"content\";s:589:\"Ticket ID : $(general : (__VtigerMeta__) recordId)<br>Ticket Title : $ticket_title<br><br>\n								Dear $(parent_id : (Accounts) accountname),<br><br>\n								The Ticket is replied the details are :<br><br>\n								Ticket No : $ticket_no<br>\n								Status : $ticketstatus<br>\n								Category : $ticketcategories<br>\n								Severity : $ticketseverities<br>\n								Priority : $ticketpriorities<br><br>\n								Description : <br>$description<br><br>\n								Solution : <br>$solution<br>\n								The comments are : <br>\n								$allComments<br><br>\n								Regards<br>Support Administrator\";}');
/*!40000 ALTER TABLE `com_vtiger_workflowtasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `com_vtiger_workflowtasks_entitymethod`
--

DROP TABLE IF EXISTS `com_vtiger_workflowtasks_entitymethod`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `com_vtiger_workflowtasks_entitymethod` (
  `workflowtasks_entitymethod_id` int NOT NULL,
  `module_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `method_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `function_path` varchar(400) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `function_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`workflowtasks_entitymethod_id`),
  UNIQUE KEY `com_vtiger_workflowtasks_entitymethod_idx` (`workflowtasks_entitymethod_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `com_vtiger_workflowtasks_entitymethod`
--

LOCK TABLES `com_vtiger_workflowtasks_entitymethod` WRITE;
/*!40000 ALTER TABLE `com_vtiger_workflowtasks_entitymethod` DISABLE KEYS */;
INSERT INTO `com_vtiger_workflowtasks_entitymethod` VALUES (1,'SalesOrder','UpdateInventory','include/InventoryHandler.php','handleInventoryProductRel'),(2,'Invoice','UpdateInventory','include/InventoryHandler.php','handleInventoryProductRel'),(3,'Contacts','SendPortalLoginDetails','modules/Contacts/ContactsHandler.php','Contacts_sendCustomerPortalLoginDetails'),(4,'HelpDesk','NotifyOnPortalTicketCreation','modules/HelpDesk/HelpDeskHandler.php','HelpDesk_nofifyOnPortalTicketCreation'),(5,'HelpDesk','NotifyOnPortalTicketComment','modules/HelpDesk/HelpDeskHandler.php','HelpDesk_notifyOnPortalTicketComment'),(6,'HelpDesk','NotifyOwnerOnTicketChange','modules/HelpDesk/HelpDeskHandler.php','HelpDesk_notifyOwnerOnTicketChange'),(7,'HelpDesk','NotifyParentOnTicketChange','modules/HelpDesk/HelpDeskHandler.php','HelpDesk_notifyParentOnTicketChange'),(8,'ModComments','CustomerCommentFromPortal','modules/ModComments/ModCommentsHandler.php','CustomerCommentFromPortal'),(9,'ModComments','TicketOwnerComments','modules/ModComments/ModCommentsHandler.php','TicketOwnerComments'),(10,'PurchaseOrder','UpdateInventory','include/InventoryHandler.php','handleInventoryProductRel');
/*!40000 ALTER TABLE `com_vtiger_workflowtasks_entitymethod` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `com_vtiger_workflowtasks_entitymethod_seq`
--

DROP TABLE IF EXISTS `com_vtiger_workflowtasks_entitymethod_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `com_vtiger_workflowtasks_entitymethod_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `com_vtiger_workflowtasks_entitymethod_seq`
--

LOCK TABLES `com_vtiger_workflowtasks_entitymethod_seq` WRITE;
/*!40000 ALTER TABLE `com_vtiger_workflowtasks_entitymethod_seq` DISABLE KEYS */;
INSERT INTO `com_vtiger_workflowtasks_entitymethod_seq` VALUES (10);
/*!40000 ALTER TABLE `com_vtiger_workflowtasks_entitymethod_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `com_vtiger_workflowtasks_seq`
--

DROP TABLE IF EXISTS `com_vtiger_workflowtasks_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `com_vtiger_workflowtasks_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `com_vtiger_workflowtasks_seq`
--

LOCK TABLES `com_vtiger_workflowtasks_seq` WRITE;
/*!40000 ALTER TABLE `com_vtiger_workflowtasks_seq` DISABLE KEYS */;
INSERT INTO `com_vtiger_workflowtasks_seq` VALUES (16);
/*!40000 ALTER TABLE `com_vtiger_workflowtasks_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `com_vtiger_workflowtemplates`
--

DROP TABLE IF EXISTS `com_vtiger_workflowtemplates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `com_vtiger_workflowtemplates` (
  `template_id` int NOT NULL AUTO_INCREMENT,
  `module_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `title` varchar(400) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `template` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `com_vtiger_workflowtemplates`
--

LOCK TABLES `com_vtiger_workflowtemplates` WRITE;
/*!40000 ALTER TABLE `com_vtiger_workflowtemplates` DISABLE KEYS */;
/*!40000 ALTER TABLE `com_vtiger_workflowtemplates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_account`
--

DROP TABLE IF EXISTS `vtiger_account`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_account` (
  `accountid` int NOT NULL,
  `account_no` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `accountname` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `parentid` int DEFAULT '0',
  `account_type` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `industry` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `annualrevenue` decimal(25,8) DEFAULT NULL,
  `rating` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ownership` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `siccode` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tickersymbol` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `otherphone` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email1` varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email2` varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `website` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fax` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `employees` int DEFAULT '0',
  `emailoptout` varchar(3) COLLATE utf8mb4_general_ci DEFAULT '0',
  `notify_owner` varchar(3) COLLATE utf8mb4_general_ci DEFAULT '0',
  `isconvertedfromlead` varchar(3) COLLATE utf8mb4_general_ci DEFAULT '0',
  `tags` varchar(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `smcreatorid` int DEFAULT NULL,
  `smownerid` int DEFAULT NULL,
  `modifiedby` int DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_general_ci,
  `createdtime` datetime DEFAULT NULL,
  `modifiedtime` datetime DEFAULT NULL,
  `viewedtime` datetime DEFAULT NULL,
  `deleted` int DEFAULT NULL,
  `label` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `smgroupid` int DEFAULT NULL,
  `source` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `last_action_date` date DEFAULT NULL,
  PRIMARY KEY (`accountid`),
  KEY `account_account_type_idx` (`account_type`),
  KEY `email_idx` (`email1`,`email2`),
  KEY `idx_info` (`deleted`,`modifiedtime`),
  KEY `idx_label` (`deleted`,`label`),
  KEY `idx_owner` (`deleted`,`smownerid`),
  CONSTRAINT `fk_1_vtiger_account` FOREIGN KEY (`accountid`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_account`
--

LOCK TABLES `vtiger_account` WRITE;
/*!40000 ALTER TABLE `vtiger_account` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_account` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_accountbillads`
--

DROP TABLE IF EXISTS `vtiger_accountbillads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_accountbillads` (
  `accountaddressid` int NOT NULL,
  `bill_city` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bill_code` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bill_country` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bill_state` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bill_street` text COLLATE utf8mb4_general_ci,
  `bill_pobox` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`accountaddressid`),
  CONSTRAINT `fk_1_vtiger_accountbillads` FOREIGN KEY (`accountaddressid`) REFERENCES `vtiger_account` (`accountid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_accountbillads`
--

LOCK TABLES `vtiger_accountbillads` WRITE;
/*!40000 ALTER TABLE `vtiger_accountbillads` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_accountbillads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_accountrating`
--

DROP TABLE IF EXISTS `vtiger_accountrating`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_accountrating` (
  `accountratingid` int NOT NULL AUTO_INCREMENT,
  `rating` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `sortorderid` int NOT NULL DEFAULT '0',
  `presence` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`accountratingid`),
  UNIQUE KEY `accountrating_rating_idx` (`rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_accountrating`
--

LOCK TABLES `vtiger_accountrating` WRITE;
/*!40000 ALTER TABLE `vtiger_accountrating` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_accountrating` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_accountscf`
--

DROP TABLE IF EXISTS `vtiger_accountscf`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_accountscf` (
  `accountid` int NOT NULL,
  PRIMARY KEY (`accountid`),
  CONSTRAINT `fk_1_vtiger_accountscf` FOREIGN KEY (`accountid`) REFERENCES `vtiger_account` (`accountid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_accountscf`
--

LOCK TABLES `vtiger_accountscf` WRITE;
/*!40000 ALTER TABLE `vtiger_accountscf` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_accountscf` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_accountshipads`
--

DROP TABLE IF EXISTS `vtiger_accountshipads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_accountshipads` (
  `accountaddressid` int NOT NULL,
  `ship_city` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ship_code` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ship_country` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ship_state` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ship_pobox` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ship_street` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`accountaddressid`),
  CONSTRAINT `fk_1_vtiger_accountshipads` FOREIGN KEY (`accountaddressid`) REFERENCES `vtiger_account` (`accountid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_accountshipads`
--

LOCK TABLES `vtiger_accountshipads` WRITE;
/*!40000 ALTER TABLE `vtiger_accountshipads` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_accountshipads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_accounttype`
--

DROP TABLE IF EXISTS `vtiger_accounttype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_accounttype` (
  `accounttypeid` int NOT NULL AUTO_INCREMENT,
  `accounttype` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT NULL,
  `color` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`accounttypeid`),
  UNIQUE KEY `accounttype_accounttype_idx` (`accounttype`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_accounttype`
--

LOCK TABLES `vtiger_accounttype` WRITE;
/*!40000 ALTER TABLE `vtiger_accounttype` DISABLE KEYS */;
INSERT INTO `vtiger_accounttype` VALUES (2,'Analyst',1,2,1,NULL),(3,'Competitor',1,3,2,NULL),(4,'Customer',1,4,3,NULL),(5,'Integrator',1,5,4,NULL),(6,'Investor',1,6,5,NULL),(7,'Partner',1,7,6,NULL),(8,'Press',1,8,7,NULL),(9,'Prospect',1,9,8,NULL),(10,'Reseller',1,10,9,NULL),(11,'Other',1,11,10,NULL);
/*!40000 ALTER TABLE `vtiger_accounttype` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_accounttype_seq`
--

DROP TABLE IF EXISTS `vtiger_accounttype_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_accounttype_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_accounttype_seq`
--

LOCK TABLES `vtiger_accounttype_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_accounttype_seq` DISABLE KEYS */;
INSERT INTO `vtiger_accounttype_seq` VALUES (11);
/*!40000 ALTER TABLE `vtiger_accounttype_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_actionmapping`
--

DROP TABLE IF EXISTS `vtiger_actionmapping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_actionmapping` (
  `actionid` int NOT NULL,
  `actionname` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `securitycheck` int DEFAULT NULL,
  PRIMARY KEY (`actionid`,`actionname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_actionmapping`
--

LOCK TABLES `vtiger_actionmapping` WRITE;
/*!40000 ALTER TABLE `vtiger_actionmapping` DISABLE KEYS */;
INSERT INTO `vtiger_actionmapping` VALUES (0,'Save',0),(0,'SavePriceBook',1),(0,'SaveVendor',1),(1,'DetailViewAjax',1),(1,'EditView',0),(1,'PriceBookEditView',1),(1,'QuickCreate',1),(1,'VendorEditView',1),(2,'Delete',0),(2,'DeletePriceBook',1),(2,'DeleteVendor',1),(3,'index',0),(3,'Popup',1),(4,'DetailView',0),(4,'PriceBookDetailView',1),(4,'TagCloud',1),(4,'VendorDetailView',1),(5,'Import',0),(6,'Export',0),(7,'CreateView',0),(8,'Merge',0),(9,'ConvertLead',0),(10,'DuplicatesHandling',0),(11,'Print',0);
/*!40000 ALTER TABLE `vtiger_actionmapping` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_activity`
--

DROP TABLE IF EXISTS `vtiger_activity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_activity` (
  `activityid` int NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `semodule` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `activitytype` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `date_start` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `time_start` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `time_end` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sendnotification` varchar(3) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `duration_hours` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `duration_minutes` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `eventstatus` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `priority` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `location` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notime` varchar(3) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `visibility` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'all',
  `recurringtype` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tags` varchar(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `allday` tinyint(1) DEFAULT '0',
  `invitee_parentid` int DEFAULT '0',
  `smcreatorid` int DEFAULT NULL,
  `smownerid` int DEFAULT NULL,
  `modifiedby` int DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_general_ci,
  `createdtime` datetime DEFAULT NULL,
  `modifiedtime` datetime DEFAULT NULL,
  `viewedtime` datetime DEFAULT NULL,
  `deleted` int DEFAULT NULL,
  `label` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `smgroupid` int DEFAULT NULL,
  `source` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `common_memo` text COLLATE utf8mb4_general_ci,
  `send_mail` varchar(3) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`activityid`),
  KEY `activity_activityid_subject_idx` (`activityid`,`subject`),
  KEY `activity_activitytype_date_start_idx` (`activitytype`,`date_start`),
  KEY `activity_date_start_due_date_idx` (`date_start`,`due_date`),
  KEY `activity_date_start_time_start_idx` (`date_start`,`time_start`),
  KEY `activity_eventstatus_idx` (`eventstatus`),
  KEY `activity_status_idx` (`status`),
  KEY `invitee_parentid` (`invitee_parentid`),
  KEY `idx_info` (`deleted`,`modifiedtime`),
  KEY `idx_label` (`deleted`,`label`),
  KEY `idx_owner` (`deleted`,`smownerid`),
  CONSTRAINT `fk_1_vtiger_activity` FOREIGN KEY (`activityid`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_activity`
--

LOCK TABLES `vtiger_activity` WRITE;
/*!40000 ALTER TABLE `vtiger_activity` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_activity` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_activity_recurring_info`
--

DROP TABLE IF EXISTS `vtiger_activity_recurring_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_activity_recurring_info` (
  `activityid` int NOT NULL,
  `recurrenceid` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_activity_recurring_info`
--

LOCK TABLES `vtiger_activity_recurring_info` WRITE;
/*!40000 ALTER TABLE `vtiger_activity_recurring_info` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_activity_recurring_info` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_activity_reminder`
--

DROP TABLE IF EXISTS `vtiger_activity_reminder`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_activity_reminder` (
  `activity_id` int NOT NULL,
  `reminder_time` int NOT NULL,
  `reminder_sent` int NOT NULL,
  `recurringid` int NOT NULL,
  PRIMARY KEY (`activity_id`,`recurringid`),
  CONSTRAINT `fk_activityid_vtiger_activity_reminder` FOREIGN KEY (`activity_id`) REFERENCES `vtiger_activity` (`activityid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_activity_reminder`
--

LOCK TABLES `vtiger_activity_reminder` WRITE;
/*!40000 ALTER TABLE `vtiger_activity_reminder` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_activity_reminder` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_activity_reminder_popup`
--

DROP TABLE IF EXISTS `vtiger_activity_reminder_popup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_activity_reminder_popup` (
  `reminderid` int NOT NULL AUTO_INCREMENT,
  `semodule` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `recordid` int NOT NULL,
  `date_start` date NOT NULL,
  `time_start` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `status` int NOT NULL,
  PRIMARY KEY (`reminderid`),
  KEY `reminder_popup` (`status`,`date_start`,`time_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_activity_reminder_popup`
--

LOCK TABLES `vtiger_activity_reminder_popup` WRITE;
/*!40000 ALTER TABLE `vtiger_activity_reminder_popup` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_activity_reminder_popup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_activity_view`
--

DROP TABLE IF EXISTS `vtiger_activity_view`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_activity_view` (
  `activity_viewid` int NOT NULL AUTO_INCREMENT,
  `activity_view` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `sortorderid` int NOT NULL DEFAULT '0',
  `presence` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`activity_viewid`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_activity_view`
--

LOCK TABLES `vtiger_activity_view` WRITE;
/*!40000 ALTER TABLE `vtiger_activity_view` DISABLE KEYS */;
INSERT INTO `vtiger_activity_view` VALUES (1,'Today',0,1),(2,'This Week',1,1),(3,'This Month',2,1),(4,'This Year',3,1),(5,'Agenda',4,1);
/*!40000 ALTER TABLE `vtiger_activity_view` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_activity_view_seq`
--

DROP TABLE IF EXISTS `vtiger_activity_view_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_activity_view_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_activity_view_seq`
--

LOCK TABLES `vtiger_activity_view_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_activity_view_seq` DISABLE KEYS */;
INSERT INTO `vtiger_activity_view_seq` VALUES (5);
/*!40000 ALTER TABLE `vtiger_activity_view_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_activitycf`
--

DROP TABLE IF EXISTS `vtiger_activitycf`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_activitycf` (
  `activityid` int NOT NULL,
  PRIMARY KEY (`activityid`),
  CONSTRAINT `fk_activityid_vtiger_activitycf` FOREIGN KEY (`activityid`) REFERENCES `vtiger_activity` (`activityid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_activitycf`
--

LOCK TABLES `vtiger_activitycf` WRITE;
/*!40000 ALTER TABLE `vtiger_activitycf` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_activitycf` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_activityproductrel`
--

DROP TABLE IF EXISTS `vtiger_activityproductrel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_activityproductrel` (
  `activityid` int NOT NULL,
  `productid` int NOT NULL,
  PRIMARY KEY (`activityid`,`productid`),
  KEY `activityproductrel_activityid_idx` (`activityid`),
  KEY `activityproductrel_productid_idx` (`productid`),
  CONSTRAINT `fk_2_vtiger_activityproductrel` FOREIGN KEY (`productid`) REFERENCES `vtiger_products` (`productid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_activityproductrel`
--

LOCK TABLES `vtiger_activityproductrel` WRITE;
/*!40000 ALTER TABLE `vtiger_activityproductrel` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_activityproductrel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_activitytype`
--

DROP TABLE IF EXISTS `vtiger_activitytype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_activitytype` (
  `activitytypeid` int NOT NULL AUTO_INCREMENT,
  `activitytype` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT NULL,
  `color` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`activitytypeid`),
  UNIQUE KEY `activitytype_activitytype_idx` (`activitytype`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_activitytype`
--

LOCK TABLES `vtiger_activitytype` WRITE;
/*!40000 ALTER TABLE `vtiger_activitytype` DISABLE KEYS */;
INSERT INTO `vtiger_activitytype` VALUES (1,'Call',0,12,0,NULL),(2,'Meeting',0,13,1,NULL),(3,'Mobile Call',0,295,1,NULL);
/*!40000 ALTER TABLE `vtiger_activitytype` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_activitytype_seq`
--

DROP TABLE IF EXISTS `vtiger_activitytype_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_activitytype_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_activitytype_seq`
--

LOCK TABLES `vtiger_activitytype_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_activitytype_seq` DISABLE KEYS */;
INSERT INTO `vtiger_activitytype_seq` VALUES (3);
/*!40000 ALTER TABLE `vtiger_activitytype_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_announcement`
--

DROP TABLE IF EXISTS `vtiger_announcement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_announcement` (
  `creatorid` int NOT NULL,
  `announcement` text COLLATE utf8mb4_general_ci,
  `title` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`creatorid`),
  KEY `announcement_creatorid_idx` (`creatorid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_announcement`
--

LOCK TABLES `vtiger_announcement` WRITE;
/*!40000 ALTER TABLE `vtiger_announcement` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_announcement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_app2tab`
--

DROP TABLE IF EXISTS `vtiger_app2tab`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_app2tab` (
  `tabid` int DEFAULT NULL,
  `appname` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sequence` int DEFAULT NULL,
  `visible` int DEFAULT '1',
  KEY `vtiger_app2tab_fk_tab` (`tabid`),
  CONSTRAINT `vtiger_app2tab_fk_tab` FOREIGN KEY (`tabid`) REFERENCES `vtiger_tab` (`tabid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_app2tab`
--

LOCK TABLES `vtiger_app2tab` WRITE;
/*!40000 ALTER TABLE `vtiger_app2tab` DISABLE KEYS */;
INSERT INTO `vtiger_app2tab` VALUES (34,'SUPPORT',3,1),(35,'INVENTORY',2,1),(38,'TOOLS',4,1),(47,'TOOLS',1,1),(2,'SALES',1,1),(20,'SALES',2,1),(4,'SALES',6,1),(6,'SALES',7,1),(14,'SALES',3,1),(35,'SALES',4,1),(4,'MARKETING',3,1),(6,'MARKETING',4,1),(7,'MARKETING',2,1),(26,'MARKETING',1,1),(4,'INVENTORY',8,1),(6,'INVENTORY',9,1),(22,'INVENTORY',5,1),(23,'INVENTORY',4,1),(14,'INVENTORY',1,1),(18,'INVENTORY',7,1),(19,'INVENTORY',3,1),(21,'INVENTORY',6,1),(4,'SUPPORT',6,1),(6,'SUPPORT',7,1),(13,'SUPPORT',1,1),(15,'SUPPORT',2,1),(43,'SUPPORT',4,1),(41,'PROJECT',1,1),(40,'PROJECT',2,1),(39,'PROJECT',3,1),(4,'PROJECT',4,1),(6,'PROJECT',5,1),(42,'SALES',5,1),(42,'SUPPORT',5,1),(24,'TOOLS',2,1),(27,'TOOLS',3,1),(48,'TOOLS',5,1),(49,'SALES',8,1);
/*!40000 ALTER TABLE `vtiger_app2tab` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_assets`
--

DROP TABLE IF EXISTS `vtiger_assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_assets` (
  `assetsid` int NOT NULL,
  `asset_no` varchar(30) NOT NULL,
  `account` int DEFAULT NULL,
  `product` int NOT NULL,
  `serialnumber` varchar(200) DEFAULT NULL,
  `datesold` date DEFAULT NULL,
  `dateinservice` date DEFAULT NULL,
  `assetstatus` varchar(200) DEFAULT 'In Service',
  `tagnumber` varchar(300) DEFAULT NULL,
  `invoiceid` int DEFAULT NULL,
  `shippingmethod` varchar(200) DEFAULT NULL,
  `shippingtrackingnumber` varchar(200) DEFAULT NULL,
  `assetname` varchar(100) DEFAULT NULL,
  `contact` int DEFAULT NULL,
  `tags` varchar(1) DEFAULT NULL,
  `smcreatorid` int DEFAULT NULL,
  `smownerid` int DEFAULT NULL,
  `modifiedby` int DEFAULT NULL,
  `description` longtext,
  `createdtime` datetime DEFAULT NULL,
  `modifiedtime` datetime DEFAULT NULL,
  `viewedtime` datetime DEFAULT NULL,
  `deleted` int DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `smgroupid` int DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`assetsid`),
  KEY `product` (`product`),
  KEY `invoiceid` (`invoiceid`),
  KEY `account` (`account`),
  KEY `contact` (`contact`),
  KEY `idx_info` (`deleted`,`modifiedtime`),
  KEY `idx_label` (`deleted`,`label`),
  KEY `idx_owner` (`deleted`,`smownerid`),
  CONSTRAINT `fk_1_vtiger_assets` FOREIGN KEY (`assetsid`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_assets`
--

LOCK TABLES `vtiger_assets` WRITE;
/*!40000 ALTER TABLE `vtiger_assets` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_assets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_assetscf`
--

DROP TABLE IF EXISTS `vtiger_assetscf`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_assetscf` (
  `assetsid` int NOT NULL,
  PRIMARY KEY (`assetsid`),
  CONSTRAINT `fk_assetsid_vtiger_assetscf` FOREIGN KEY (`assetsid`) REFERENCES `vtiger_assets` (`assetsid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_assetscf`
--

LOCK TABLES `vtiger_assetscf` WRITE;
/*!40000 ALTER TABLE `vtiger_assetscf` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_assetscf` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_assetstatus`
--

DROP TABLE IF EXISTS `vtiger_assetstatus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_assetstatus` (
  `assetstatusid` int NOT NULL AUTO_INCREMENT,
  `assetstatus` varchar(200) NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT '0',
  `color` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`assetstatusid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_assetstatus`
--

LOCK TABLES `vtiger_assetstatus` WRITE;
/*!40000 ALTER TABLE `vtiger_assetstatus` DISABLE KEYS */;
INSERT INTO `vtiger_assetstatus` VALUES (1,'In Service',1,286,1,NULL),(2,'Out-of-service',1,287,2,NULL);
/*!40000 ALTER TABLE `vtiger_assetstatus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_assetstatus_seq`
--

DROP TABLE IF EXISTS `vtiger_assetstatus_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_assetstatus_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_assetstatus_seq`
--

LOCK TABLES `vtiger_assetstatus_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_assetstatus_seq` DISABLE KEYS */;
INSERT INTO `vtiger_assetstatus_seq` VALUES (2);
/*!40000 ALTER TABLE `vtiger_assetstatus_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_asterisk`
--

DROP TABLE IF EXISTS `vtiger_asterisk`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_asterisk` (
  `server` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `port` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `username` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `version` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_asterisk`
--

LOCK TABLES `vtiger_asterisk` WRITE;
/*!40000 ALTER TABLE `vtiger_asterisk` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_asterisk` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_asteriskextensions`
--

DROP TABLE IF EXISTS `vtiger_asteriskextensions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_asteriskextensions` (
  `userid` int DEFAULT NULL,
  `asterisk_extension` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `use_asterisk` varchar(3) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_asteriskextensions`
--

LOCK TABLES `vtiger_asteriskextensions` WRITE;
/*!40000 ALTER TABLE `vtiger_asteriskextensions` DISABLE KEYS */;
INSERT INTO `vtiger_asteriskextensions` VALUES (1,NULL,NULL);
/*!40000 ALTER TABLE `vtiger_asteriskextensions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_asteriskincomingcalls`
--

DROP TABLE IF EXISTS `vtiger_asteriskincomingcalls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_asteriskincomingcalls` (
  `from_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `from_name` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `to_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `callertype` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `flag` int DEFAULT NULL,
  `timer` int DEFAULT NULL,
  `refuid` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_asteriskincomingcalls`
--

LOCK TABLES `vtiger_asteriskincomingcalls` WRITE;
/*!40000 ALTER TABLE `vtiger_asteriskincomingcalls` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_asteriskincomingcalls` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_asteriskincomingevents`
--

DROP TABLE IF EXISTS `vtiger_asteriskincomingevents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_asteriskincomingevents` (
  `uid` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `channel` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `from_number` bigint DEFAULT NULL,
  `from_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `to_number` bigint DEFAULT NULL,
  `callertype` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `timer` int DEFAULT NULL,
  `flag` varchar(3) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pbxrecordid` int DEFAULT NULL,
  `relcrmid` int DEFAULT NULL,
  PRIMARY KEY (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_asteriskincomingevents`
--

LOCK TABLES `vtiger_asteriskincomingevents` WRITE;
/*!40000 ALTER TABLE `vtiger_asteriskincomingevents` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_asteriskincomingevents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_attachments`
--

DROP TABLE IF EXISTS `vtiger_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_attachments` (
  `attachmentsid` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `path` text COLLATE utf8mb4_general_ci,
  `storedname` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `subject` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`attachmentsid`),
  KEY `attachments_attachmentsid_idx` (`attachmentsid`),
  CONSTRAINT `fk_1_vtiger_attachments` FOREIGN KEY (`attachmentsid`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_attachments`
--

LOCK TABLES `vtiger_attachments` WRITE;
/*!40000 ALTER TABLE `vtiger_attachments` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_attachments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_attachmentsfolder`
--

DROP TABLE IF EXISTS `vtiger_attachmentsfolder`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_attachmentsfolder` (
  `folderid` int NOT NULL AUTO_INCREMENT,
  `foldername` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(250) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `createdby` int NOT NULL,
  `sequence` int DEFAULT NULL,
  PRIMARY KEY (`folderid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_attachmentsfolder`
--

LOCK TABLES `vtiger_attachmentsfolder` WRITE;
/*!40000 ALTER TABLE `vtiger_attachmentsfolder` DISABLE KEYS */;
INSERT INTO `vtiger_attachmentsfolder` VALUES (1,'Default','This is a Default Folder',1,1);
/*!40000 ALTER TABLE `vtiger_attachmentsfolder` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_attachmentsfolder_seq`
--

DROP TABLE IF EXISTS `vtiger_attachmentsfolder_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_attachmentsfolder_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_attachmentsfolder_seq`
--

LOCK TABLES `vtiger_attachmentsfolder_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_attachmentsfolder_seq` DISABLE KEYS */;
INSERT INTO `vtiger_attachmentsfolder_seq` VALUES (1);
/*!40000 ALTER TABLE `vtiger_attachmentsfolder_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_audit_trial`
--

DROP TABLE IF EXISTS `vtiger_audit_trial`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_audit_trial` (
  `auditid` int NOT NULL,
  `userid` int DEFAULT NULL,
  `module` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `recordid` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `actiondate` datetime DEFAULT NULL,
  PRIMARY KEY (`auditid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_audit_trial`
--

LOCK TABLES `vtiger_audit_trial` WRITE;
/*!40000 ALTER TABLE `vtiger_audit_trial` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_audit_trial` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_blocks`
--

DROP TABLE IF EXISTS `vtiger_blocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_blocks` (
  `blockid` int NOT NULL,
  `tabid` int NOT NULL,
  `blocklabel` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `sequence` int DEFAULT NULL,
  `show_title` int DEFAULT NULL,
  `visible` int NOT NULL DEFAULT '0',
  `create_view` int NOT NULL DEFAULT '0',
  `edit_view` int NOT NULL DEFAULT '0',
  `detail_view` int NOT NULL DEFAULT '0',
  `display_status` int NOT NULL DEFAULT '1',
  `iscustom` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`blockid`),
  KEY `block_tabid_idx` (`tabid`),
  CONSTRAINT `fk_1_vtiger_blocks` FOREIGN KEY (`tabid`) REFERENCES `vtiger_tab` (`tabid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_blocks`
--

LOCK TABLES `vtiger_blocks` WRITE;
/*!40000 ALTER TABLE `vtiger_blocks` DISABLE KEYS */;
INSERT INTO `vtiger_blocks` VALUES (1,2,'LBL_OPPORTUNITY_INFORMATION',1,0,0,0,0,0,1,0),(2,2,'LBL_CUSTOM_INFORMATION',2,0,0,0,0,0,1,0),(3,2,'LBL_DESCRIPTION_INFORMATION',3,0,0,0,0,0,1,0),(4,4,'LBL_CONTACT_INFORMATION',1,0,0,0,0,0,1,0),(5,4,'LBL_CUSTOM_INFORMATION',2,0,0,0,0,0,1,0),(6,4,'LBL_CUSTOMER_PORTAL_INFORMATION',3,0,0,0,0,0,1,0),(7,4,'LBL_ADDRESS_INFORMATION',4,0,0,0,0,0,1,0),(8,4,'LBL_DESCRIPTION_INFORMATION',5,0,0,0,0,0,1,0),(9,6,'LBL_ACCOUNT_INFORMATION',1,0,0,0,0,0,1,0),(10,6,'LBL_CUSTOM_INFORMATION',2,0,0,0,0,0,1,0),(11,6,'LBL_ADDRESS_INFORMATION',3,0,0,0,0,0,1,0),(12,6,'LBL_DESCRIPTION_INFORMATION',4,0,0,0,0,0,1,0),(13,7,'LBL_LEAD_INFORMATION',1,0,0,0,0,0,1,0),(14,7,'LBL_CUSTOM_INFORMATION',2,0,0,0,0,0,1,0),(15,7,'LBL_ADDRESS_INFORMATION',3,0,0,0,0,0,1,0),(16,7,'LBL_DESCRIPTION_INFORMATION',4,0,0,0,0,0,1,0),(17,8,'LBL_NOTE_INFORMATION',1,0,0,0,0,0,1,0),(18,8,'LBL_FILE_INFORMATION',3,1,0,0,0,0,1,0),(19,9,'LBL_TASK_INFORMATION',1,0,0,0,0,0,1,0),(20,9,'LBL_DESCRIPTION_INFORMATION',3,1,0,0,0,0,1,0),(21,10,'LBL_EMAIL_INFORMATION',1,0,0,0,0,0,1,0),(22,10,'Emails_Block1',2,1,0,0,0,0,1,0),(23,10,'Emails_Block2',3,1,0,0,0,0,1,0),(24,10,'Emails_Block3',4,1,0,0,0,0,1,0),(25,13,'LBL_TICKET_INFORMATION',1,0,0,0,0,0,1,0),(26,13,'',2,1,0,0,0,0,1,0),(27,13,'LBL_CUSTOM_INFORMATION',3,0,0,0,0,0,1,0),(28,13,'LBL_DESCRIPTION_INFORMATION',4,0,0,0,0,0,1,0),(29,13,'LBL_TICKET_RESOLUTION',5,0,0,1,0,0,1,0),(30,13,'LBL_COMMENTS',6,0,0,1,0,0,1,0),(31,14,'LBL_PRODUCT_INFORMATION',1,0,0,0,0,0,1,0),(32,14,'LBL_PRICING_INFORMATION',2,0,0,0,0,0,1,0),(33,14,'LBL_STOCK_INFORMATION',3,0,0,0,0,0,1,0),(34,14,'LBL_CUSTOM_INFORMATION',4,0,0,0,0,0,1,0),(35,14,'LBL_IMAGE_INFORMATION',5,0,0,0,0,0,1,0),(36,14,'LBL_DESCRIPTION_INFORMATION',6,0,0,0,0,0,1,0),(37,15,'LBL_FAQ_INFORMATION',1,0,0,0,0,0,1,0),(38,15,'LBL_COMMENT_INFORMATION',4,0,0,1,0,0,1,0),(39,16,'LBL_EVENT_INFORMATION',1,0,0,0,0,0,1,0),(40,16,'LBL_REMINDER_INFORMATION',2,0,0,0,0,0,1,0),(41,16,'LBL_DESCRIPTION_INFORMATION',5,0,0,0,0,0,1,0),(42,18,'LBL_VENDOR_INFORMATION',1,0,0,0,0,0,1,0),(43,18,'LBL_CUSTOM_INFORMATION',2,0,0,0,0,0,1,0),(44,18,'LBL_VENDOR_ADDRESS_INFORMATION',3,0,0,0,0,0,1,0),(45,18,'LBL_DESCRIPTION_INFORMATION',4,0,0,0,0,0,1,0),(46,19,'LBL_PRICEBOOK_INFORMATION',1,0,0,0,0,0,1,0),(47,19,'LBL_CUSTOM_INFORMATION',2,0,0,0,0,0,1,0),(48,19,'LBL_DESCRIPTION_INFORMATION',3,0,0,0,0,0,1,0),(49,20,'LBL_QUOTE_INFORMATION',1,0,0,0,0,0,1,0),(50,20,'LBL_CUSTOM_INFORMATION',2,0,0,0,0,0,1,0),(51,20,'LBL_ADDRESS_INFORMATION',3,0,0,0,0,0,1,0),(52,20,'LBL_RELATED_PRODUCTS',4,0,0,0,0,0,1,0),(53,20,'LBL_TERMS_INFORMATION',5,0,0,0,0,0,1,0),(54,20,'LBL_DESCRIPTION_INFORMATION',6,0,0,0,0,0,1,0),(55,21,'LBL_PO_INFORMATION',1,0,0,0,0,0,1,0),(56,21,'LBL_CUSTOM_INFORMATION',2,0,0,0,0,0,1,0),(57,21,'LBL_ADDRESS_INFORMATION',3,0,0,0,0,0,1,0),(58,21,'LBL_RELATED_PRODUCTS',4,0,0,0,0,0,1,0),(59,21,'LBL_TERMS_INFORMATION',5,0,0,0,0,0,1,0),(60,21,'LBL_DESCRIPTION_INFORMATION',6,0,0,0,0,0,1,0),(61,22,'LBL_SO_INFORMATION',1,0,0,0,0,0,1,0),(62,22,'LBL_CUSTOM_INFORMATION',3,0,0,0,0,0,1,0),(63,22,'LBL_ADDRESS_INFORMATION',4,0,0,0,0,0,1,0),(64,22,'LBL_RELATED_PRODUCTS',5,0,0,0,0,0,1,0),(65,22,'LBL_TERMS_INFORMATION',6,0,0,0,0,0,1,0),(66,22,'LBL_DESCRIPTION_INFORMATION',7,0,0,0,0,0,1,0),(67,23,'LBL_INVOICE_INFORMATION',1,0,0,0,0,0,1,0),(68,23,'LBL_CUSTOM_INFORMATION',2,0,0,0,0,0,1,0),(69,23,'LBL_ADDRESS_INFORMATION',3,0,0,0,0,0,1,0),(70,23,'LBL_RELATED_PRODUCTS',4,0,0,0,0,0,1,0),(71,23,'LBL_TERMS_INFORMATION',5,0,0,0,0,0,1,0),(72,23,'LBL_DESCRIPTION_INFORMATION',6,0,0,0,0,0,1,0),(73,4,'LBL_IMAGE_INFORMATION',6,0,0,0,0,0,1,0),(74,26,'LBL_CAMPAIGN_INFORMATION',1,0,0,0,0,0,1,0),(75,26,'LBL_CUSTOM_INFORMATION',2,0,0,0,0,0,1,0),(76,26,'LBL_EXPECTATIONS_AND_ACTUALS',3,0,0,0,0,0,1,0),(77,29,'LBL_USERLOGIN_ROLE',1,0,0,0,0,0,1,0),(78,29,'LBL_CURRENCY_CONFIGURATION',3,0,0,0,0,0,1,0),(79,29,'LBL_MORE_INFORMATION',4,0,0,0,0,0,1,0),(80,29,'LBL_ADDRESS_INFORMATION',5,0,0,0,0,0,1,0),(81,26,'LBL_DESCRIPTION_INFORMATION',4,0,0,0,0,0,1,0),(82,29,'LBL_USER_IMAGE_INFORMATION',5,0,0,0,0,0,1,0),(83,29,'LBL_USER_ADV_OPTIONS',6,0,0,0,0,0,1,0),(84,8,'LBL_DESCRIPTION',2,0,0,0,0,0,1,0),(85,22,'Recurring Invoice Information',2,0,0,0,0,0,1,0),(86,9,'LBL_CUSTOM_INFORMATION',4,0,0,0,0,0,1,0),(87,16,'LBL_CUSTOM_INFORMATION',6,0,0,0,0,0,1,0),(88,34,'LBL_SERVICE_CONTRACT_INFORMATION',1,0,0,0,0,0,1,0),(89,34,'LBL_CUSTOM_INFORMATION',2,0,0,0,0,0,1,0),(90,35,'LBL_SERVICE_INFORMATION',1,0,0,0,0,0,1,0),(91,35,'LBL_PRICING_INFORMATION',2,0,0,0,0,0,1,0),(92,35,'LBL_CUSTOM_INFORMATION',3,0,0,0,0,0,1,0),(93,35,'LBL_DESCRIPTION_INFORMATION',4,0,0,0,0,0,1,0),(94,37,'LBL_MODCOMMENTS_INFORMATION',1,0,0,0,0,0,1,0),(95,37,'LBL_OTHER_INFORMATION',2,0,0,0,0,0,1,0),(96,37,'LBL_CUSTOM_INFORMATION',3,0,0,0,0,0,1,0),(97,39,'LBL_PROJECT_MILESTONE_INFORMATION',1,0,0,0,0,0,1,0),(98,39,'LBL_CUSTOM_INFORMATION',2,0,0,0,0,0,1,0),(99,39,'LBL_DESCRIPTION_INFORMATION',3,0,0,0,0,0,1,0),(100,40,'LBL_PROJECT_TASK_INFORMATION',1,0,0,0,0,0,1,0),(101,40,'LBL_CUSTOM_INFORMATION',2,0,0,0,0,0,1,0),(102,40,'LBL_DESCRIPTION_INFORMATION',3,0,0,0,0,0,1,0),(103,41,'LBL_PROJECT_INFORMATION',1,0,0,0,0,0,1,0),(104,41,'LBL_CUSTOM_INFORMATION',2,0,0,0,0,0,1,0),(105,41,'LBL_DESCRIPTION_INFORMATION',3,0,0,0,0,0,1,0),(106,42,'LBL_SMSNOTIFIER_INFORMATION',1,0,0,0,0,0,1,0),(107,42,'LBL_CUSTOM_INFORMATION',2,0,0,0,0,0,1,0),(108,42,'StatusInformation',3,0,0,0,0,0,1,0),(109,43,'LBL_ASSET_INFORMATION',1,0,0,0,0,0,1,0),(110,43,'LBL_CUSTOM_INFORMATION',2,0,0,0,0,0,1,0),(111,43,'LBL_DESCRIPTION_INFORMATION',3,0,0,0,0,0,1,0),(112,23,'LBL_ITEM_DETAILS',5,0,0,0,0,0,1,0),(113,22,'LBL_ITEM_DETAILS',5,0,0,0,0,0,1,0),(114,21,'LBL_ITEM_DETAILS',5,0,0,0,0,0,1,0),(115,20,'LBL_ITEM_DETAILS',5,0,0,0,0,0,1,0),(116,16,'LBL_RECURRENCE_INFORMATION',3,0,0,0,0,0,1,0),(117,29,'LBL_CALENDAR_SETTINGS',2,0,0,0,0,0,1,0),(118,16,'LBL_RELATED_TO',4,0,0,0,0,0,1,0),(119,9,'LBL_REMINDER_INFORMATION',2,0,0,0,0,0,1,0),(120,49,'LBL_DAYILYREPORTS_INFORMATION',1,0,0,0,0,0,1,0);
/*!40000 ALTER TABLE `vtiger_blocks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_blocks_seq`
--

DROP TABLE IF EXISTS `vtiger_blocks_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_blocks_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_blocks_seq`
--

LOCK TABLES `vtiger_blocks_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_blocks_seq` DISABLE KEYS */;
INSERT INTO `vtiger_blocks_seq` VALUES (120);
/*!40000 ALTER TABLE `vtiger_blocks_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_calendar_default_activitytypes`
--

DROP TABLE IF EXISTS `vtiger_calendar_default_activitytypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_calendar_default_activitytypes` (
  `id` int NOT NULL,
  `module` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fieldname` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `defaultcolor` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `isdefault` int DEFAULT '1',
  `conditions` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_calendar_default_activitytypes`
--

LOCK TABLES `vtiger_calendar_default_activitytypes` WRITE;
/*!40000 ALTER TABLE `vtiger_calendar_default_activitytypes` DISABLE KEYS */;
INSERT INTO `vtiger_calendar_default_activitytypes` VALUES (1,'Events','[\"date_start\",\"due_date\"]','#17309A',1,''),(2,'Calendar','[\"date_start\",\"due_date\"]','#3A87AD',1,''),(3,'Potentials','[\"closingdate\"]','#AA6705',1,''),(4,'Contacts','[\"support_end_date\"]','#953B39',1,''),(5,'Contacts','[\"birthday\"]','#545252',1,''),(6,'Invoice','[\"duedate\"]','#87865D',1,''),(7,'Project','[\"startdate\",\"targetenddate\"]','#C71585',1,''),(8,'ProjectTask','[\"startdate\",\"enddate\"]','#006400',1,'');
/*!40000 ALTER TABLE `vtiger_calendar_default_activitytypes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_calendar_default_activitytypes_seq`
--

DROP TABLE IF EXISTS `vtiger_calendar_default_activitytypes_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_calendar_default_activitytypes_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_calendar_default_activitytypes_seq`
--

LOCK TABLES `vtiger_calendar_default_activitytypes_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_calendar_default_activitytypes_seq` DISABLE KEYS */;
INSERT INTO `vtiger_calendar_default_activitytypes_seq` VALUES (8);
/*!40000 ALTER TABLE `vtiger_calendar_default_activitytypes_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_calendar_user_activitytypes`
--

DROP TABLE IF EXISTS `vtiger_calendar_user_activitytypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_calendar_user_activitytypes` (
  `id` int NOT NULL,
  `defaultid` int DEFAULT NULL,
  `userid` int DEFAULT NULL,
  `color` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `visible` int DEFAULT '1',
  `is_own` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_calendar_user_activitytypes`
--

LOCK TABLES `vtiger_calendar_user_activitytypes` WRITE;
/*!40000 ALTER TABLE `vtiger_calendar_user_activitytypes` DISABLE KEYS */;
INSERT INTO `vtiger_calendar_user_activitytypes` VALUES (1,1,1,'#17309A',1,1),(2,2,1,'#3A87AD',1,1),(3,3,1,'#AA6705',1,1),(4,4,1,'#953B39',1,1),(5,5,1,'#545252',1,1),(6,6,1,'#87865D',1,1),(7,7,1,'#C71585',1,1),(8,8,1,'#006400',1,1);
/*!40000 ALTER TABLE `vtiger_calendar_user_activitytypes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_calendar_user_activitytypes_seq`
--

DROP TABLE IF EXISTS `vtiger_calendar_user_activitytypes_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_calendar_user_activitytypes_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_calendar_user_activitytypes_seq`
--

LOCK TABLES `vtiger_calendar_user_activitytypes_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_calendar_user_activitytypes_seq` DISABLE KEYS */;
INSERT INTO `vtiger_calendar_user_activitytypes_seq` VALUES (8);
/*!40000 ALTER TABLE `vtiger_calendar_user_activitytypes_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_calendarsharedtype`
--

DROP TABLE IF EXISTS `vtiger_calendarsharedtype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_calendarsharedtype` (
  `calendarsharedtypeid` int NOT NULL AUTO_INCREMENT,
  `calendarsharedtype` varchar(200) NOT NULL,
  `sortorderid` int DEFAULT NULL,
  `presence` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`calendarsharedtypeid`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_calendarsharedtype`
--

LOCK TABLES `vtiger_calendarsharedtype` WRITE;
/*!40000 ALTER TABLE `vtiger_calendarsharedtype` DISABLE KEYS */;
INSERT INTO `vtiger_calendarsharedtype` VALUES (1,'public',0,1),(2,'private',1,1),(3,'seletedusers',2,1);
/*!40000 ALTER TABLE `vtiger_calendarsharedtype` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_calendarsharedtype_seq`
--

DROP TABLE IF EXISTS `vtiger_calendarsharedtype_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_calendarsharedtype_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_calendarsharedtype_seq`
--

LOCK TABLES `vtiger_calendarsharedtype_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_calendarsharedtype_seq` DISABLE KEYS */;
INSERT INTO `vtiger_calendarsharedtype_seq` VALUES (3);
/*!40000 ALTER TABLE `vtiger_calendarsharedtype_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_callduration`
--

DROP TABLE IF EXISTS `vtiger_callduration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_callduration` (
  `calldurationid` int NOT NULL AUTO_INCREMENT,
  `callduration` varchar(200) NOT NULL,
  `sortorderid` int DEFAULT NULL,
  `presence` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`calldurationid`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_callduration`
--

LOCK TABLES `vtiger_callduration` WRITE;
/*!40000 ALTER TABLE `vtiger_callduration` DISABLE KEYS */;
INSERT INTO `vtiger_callduration` VALUES (1,'5',0,1),(2,'10',1,1),(3,'30',2,1),(4,'60',3,1),(5,'120',4,1);
/*!40000 ALTER TABLE `vtiger_callduration` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_callduration_seq`
--

DROP TABLE IF EXISTS `vtiger_callduration_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_callduration_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_callduration_seq`
--

LOCK TABLES `vtiger_callduration_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_callduration_seq` DISABLE KEYS */;
INSERT INTO `vtiger_callduration_seq` VALUES (5);
/*!40000 ALTER TABLE `vtiger_callduration_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_campaign`
--

DROP TABLE IF EXISTS `vtiger_campaign`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_campaign` (
  `campaign_no` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `campaignname` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `campaigntype` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `campaignstatus` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `expectedrevenue` decimal(25,8) DEFAULT NULL,
  `budgetcost` decimal(25,8) DEFAULT NULL,
  `actualcost` decimal(25,8) DEFAULT NULL,
  `expectedresponse` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `numsent` decimal(11,0) DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `sponsor` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `targetaudience` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `targetsize` int DEFAULT NULL,
  `expectedresponsecount` int DEFAULT NULL,
  `expectedsalescount` int DEFAULT NULL,
  `expectedroi` decimal(25,8) DEFAULT NULL,
  `actualresponsecount` int DEFAULT NULL,
  `actualsalescount` int DEFAULT NULL,
  `actualroi` decimal(25,8) DEFAULT NULL,
  `campaignid` int NOT NULL,
  `closingdate` date DEFAULT NULL,
  `tags` varchar(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `smcreatorid` int DEFAULT NULL,
  `smownerid` int DEFAULT NULL,
  `modifiedby` int DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_general_ci,
  `createdtime` datetime DEFAULT NULL,
  `modifiedtime` datetime DEFAULT NULL,
  `viewedtime` datetime DEFAULT NULL,
  `deleted` int DEFAULT NULL,
  `label` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `smgroupid` int DEFAULT NULL,
  `source` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`campaignid`),
  KEY `campaign_campaignstatus_idx` (`campaignstatus`),
  KEY `campaign_campaignname_idx` (`campaignname`),
  KEY `campaign_campaignid_idx` (`campaignid`),
  KEY `idx_info` (`deleted`,`modifiedtime`),
  KEY `idx_label` (`deleted`,`label`),
  KEY `idx_owner` (`deleted`,`smownerid`),
  CONSTRAINT `fk_crmid_vtiger_campaign` FOREIGN KEY (`campaignid`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_campaign`
--

LOCK TABLES `vtiger_campaign` WRITE;
/*!40000 ALTER TABLE `vtiger_campaign` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_campaign` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_campaignaccountrel`
--

DROP TABLE IF EXISTS `vtiger_campaignaccountrel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_campaignaccountrel` (
  `campaignid` int DEFAULT NULL,
  `accountid` int DEFAULT NULL,
  `campaignrelstatusid` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_campaignaccountrel`
--

LOCK TABLES `vtiger_campaignaccountrel` WRITE;
/*!40000 ALTER TABLE `vtiger_campaignaccountrel` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_campaignaccountrel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_campaigncontrel`
--

DROP TABLE IF EXISTS `vtiger_campaigncontrel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_campaigncontrel` (
  `campaignid` int NOT NULL,
  `contactid` int NOT NULL,
  `campaignrelstatusid` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`campaignid`,`contactid`,`campaignrelstatusid`),
  KEY `campaigncontrel_contractid_idx` (`contactid`),
  CONSTRAINT `fk_2_vtiger_campaigncontrel` FOREIGN KEY (`contactid`) REFERENCES `vtiger_contactdetails` (`contactid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_campaigncontrel`
--

LOCK TABLES `vtiger_campaigncontrel` WRITE;
/*!40000 ALTER TABLE `vtiger_campaigncontrel` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_campaigncontrel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_campaignleadrel`
--

DROP TABLE IF EXISTS `vtiger_campaignleadrel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_campaignleadrel` (
  `campaignid` int NOT NULL,
  `leadid` int NOT NULL,
  `campaignrelstatusid` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`campaignid`,`leadid`,`campaignrelstatusid`),
  KEY `campaignleadrel_leadid_campaignid_idx` (`leadid`,`campaignid`),
  CONSTRAINT `fk_2_vtiger_campaignleadrel` FOREIGN KEY (`leadid`) REFERENCES `vtiger_leaddetails` (`leadid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_campaignleadrel`
--

LOCK TABLES `vtiger_campaignleadrel` WRITE;
/*!40000 ALTER TABLE `vtiger_campaignleadrel` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_campaignleadrel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_campaignrelstatus`
--

DROP TABLE IF EXISTS `vtiger_campaignrelstatus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_campaignrelstatus` (
  `campaignrelstatusid` int DEFAULT NULL,
  `campaignrelstatus` varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sortorderid` int DEFAULT NULL,
  `presence` int DEFAULT NULL,
  `color` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_campaignrelstatus`
--

LOCK TABLES `vtiger_campaignrelstatus` WRITE;
/*!40000 ALTER TABLE `vtiger_campaignrelstatus` DISABLE KEYS */;
INSERT INTO `vtiger_campaignrelstatus` VALUES (2,'Contacted - Successful',1,1,NULL),(3,'Contacted - Unsuccessful',2,1,NULL),(4,'Contacted - Never Contact Again',3,1,NULL);
/*!40000 ALTER TABLE `vtiger_campaignrelstatus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_campaignrelstatus_seq`
--

DROP TABLE IF EXISTS `vtiger_campaignrelstatus_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_campaignrelstatus_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_campaignrelstatus_seq`
--

LOCK TABLES `vtiger_campaignrelstatus_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_campaignrelstatus_seq` DISABLE KEYS */;
INSERT INTO `vtiger_campaignrelstatus_seq` VALUES (4);
/*!40000 ALTER TABLE `vtiger_campaignrelstatus_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_campaignscf`
--

DROP TABLE IF EXISTS `vtiger_campaignscf`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_campaignscf` (
  `campaignid` int NOT NULL,
  PRIMARY KEY (`campaignid`),
  CONSTRAINT `fk_1_vtiger_campaignscf` FOREIGN KEY (`campaignid`) REFERENCES `vtiger_campaign` (`campaignid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_campaignscf`
--

LOCK TABLES `vtiger_campaignscf` WRITE;
/*!40000 ALTER TABLE `vtiger_campaignscf` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_campaignscf` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_campaignstatus`
--

DROP TABLE IF EXISTS `vtiger_campaignstatus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_campaignstatus` (
  `campaignstatusid` int NOT NULL AUTO_INCREMENT,
  `campaignstatus` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT NULL,
  `color` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`campaignstatusid`),
  KEY `campaignstatus_campaignstatus_idx` (`campaignstatus`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_campaignstatus`
--

LOCK TABLES `vtiger_campaignstatus` WRITE;
/*!40000 ALTER TABLE `vtiger_campaignstatus` DISABLE KEYS */;
INSERT INTO `vtiger_campaignstatus` VALUES (2,'Planning',1,15,1,NULL),(3,'Active',1,16,2,NULL),(4,'Inactive',1,17,3,NULL),(5,'Completed',1,18,4,NULL),(6,'Cancelled',1,19,5,NULL);
/*!40000 ALTER TABLE `vtiger_campaignstatus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_campaignstatus_seq`
--

DROP TABLE IF EXISTS `vtiger_campaignstatus_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_campaignstatus_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_campaignstatus_seq`
--

LOCK TABLES `vtiger_campaignstatus_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_campaignstatus_seq` DISABLE KEYS */;
INSERT INTO `vtiger_campaignstatus_seq` VALUES (6);
/*!40000 ALTER TABLE `vtiger_campaignstatus_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_campaigntype`
--

DROP TABLE IF EXISTS `vtiger_campaigntype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_campaigntype` (
  `campaigntypeid` int NOT NULL AUTO_INCREMENT,
  `campaigntype` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT NULL,
  `color` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`campaigntypeid`),
  UNIQUE KEY `campaigntype_campaigntype_idx` (`campaigntype`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_campaigntype`
--

LOCK TABLES `vtiger_campaigntype` WRITE;
/*!40000 ALTER TABLE `vtiger_campaigntype` DISABLE KEYS */;
INSERT INTO `vtiger_campaigntype` VALUES (2,'Conference',1,21,1,NULL),(3,'Webinar',1,22,2,NULL),(4,'Trade Show',1,23,3,NULL),(5,'Public Relations',1,24,4,NULL),(6,'Partners',1,25,5,NULL),(7,'Referral Program',1,26,6,NULL),(8,'Advertisement',1,27,7,NULL),(9,'Banner Ads',1,28,8,NULL),(10,'Direct Mail',1,29,9,NULL),(11,'Email',1,30,10,NULL),(12,'Telemarketing',1,31,11,NULL),(13,'Others',1,32,12,NULL);
/*!40000 ALTER TABLE `vtiger_campaigntype` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_campaigntype_seq`
--

DROP TABLE IF EXISTS `vtiger_campaigntype_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_campaigntype_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_campaigntype_seq`
--

LOCK TABLES `vtiger_campaigntype_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_campaigntype_seq` DISABLE KEYS */;
INSERT INTO `vtiger_campaigntype_seq` VALUES (13);
/*!40000 ALTER TABLE `vtiger_campaigntype_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_carrier`
--

DROP TABLE IF EXISTS `vtiger_carrier`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_carrier` (
  `carrierid` int NOT NULL AUTO_INCREMENT,
  `carrier` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT NULL,
  `color` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`carrierid`),
  UNIQUE KEY `carrier_carrier_idx` (`carrier`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_carrier`
--

LOCK TABLES `vtiger_carrier` WRITE;
/*!40000 ALTER TABLE `vtiger_carrier` DISABLE KEYS */;
INSERT INTO `vtiger_carrier` VALUES (1,'FedEx',1,33,0,NULL),(2,'UPS',1,34,1,NULL),(3,'USPS',1,35,2,NULL),(4,'DHL',1,36,3,NULL),(5,'BlueDart',1,37,4,NULL);
/*!40000 ALTER TABLE `vtiger_carrier` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_carrier_seq`
--

DROP TABLE IF EXISTS `vtiger_carrier_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_carrier_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_carrier_seq`
--

LOCK TABLES `vtiger_carrier_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_carrier_seq` DISABLE KEYS */;
INSERT INTO `vtiger_carrier_seq` VALUES (5);
/*!40000 ALTER TABLE `vtiger_carrier_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_cntactivityrel`
--

DROP TABLE IF EXISTS `vtiger_cntactivityrel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_cntactivityrel` (
  `contactid` int NOT NULL,
  `activityid` int NOT NULL,
  PRIMARY KEY (`contactid`,`activityid`),
  KEY `cntactivityrel_contactid_idx` (`contactid`),
  KEY `cntactivityrel_activityid_idx` (`activityid`),
  CONSTRAINT `fk_2_vtiger_cntactivityrel` FOREIGN KEY (`contactid`) REFERENCES `vtiger_contactdetails` (`contactid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_cntactivityrel`
--

LOCK TABLES `vtiger_cntactivityrel` WRITE;
/*!40000 ALTER TABLE `vtiger_cntactivityrel` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_cntactivityrel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_contactaddress`
--

DROP TABLE IF EXISTS `vtiger_contactaddress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_contactaddress` (
  `contactaddressid` int NOT NULL,
  `mailingcity` varchar(40) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mailingstreet` text COLLATE utf8mb4_general_ci,
  `mailingcountry` varchar(40) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `othercountry` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mailingstate` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mailingpobox` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `othercity` varchar(40) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `otherstate` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mailingzip` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `otherzip` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `otherstreet` text COLLATE utf8mb4_general_ci,
  `otherpobox` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`contactaddressid`),
  CONSTRAINT `fk_1_vtiger_contactaddress` FOREIGN KEY (`contactaddressid`) REFERENCES `vtiger_contactdetails` (`contactid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_contactaddress`
--

LOCK TABLES `vtiger_contactaddress` WRITE;
/*!40000 ALTER TABLE `vtiger_contactaddress` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_contactaddress` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_contactdetails`
--

DROP TABLE IF EXISTS `vtiger_contactdetails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_contactdetails` (
  `contactid` int NOT NULL,
  `contact_no` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `accountid` int DEFAULT NULL,
  `salutation` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `firstname` varchar(40) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lastname` varchar(80) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mobile` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `title` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `department` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fax` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reportsto` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `training` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `usertype` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contacttype` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `otheremail` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `secondaryemail` varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `donotcall` varchar(3) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `emailoptout` varchar(3) COLLATE utf8mb4_general_ci DEFAULT '0',
  `imagename` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reference` varchar(3) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notify_owner` varchar(3) COLLATE utf8mb4_general_ci DEFAULT '0',
  `isconvertedfromlead` varchar(3) COLLATE utf8mb4_general_ci DEFAULT '0',
  `tags` varchar(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `smcreatorid` int DEFAULT NULL,
  `smownerid` int DEFAULT NULL,
  `modifiedby` int DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_general_ci,
  `createdtime` datetime DEFAULT NULL,
  `modifiedtime` datetime DEFAULT NULL,
  `viewedtime` datetime DEFAULT NULL,
  `deleted` int DEFAULT NULL,
  `label` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `smgroupid` int DEFAULT NULL,
  `source` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `last_action_date` date DEFAULT NULL,
  PRIMARY KEY (`contactid`),
  KEY `contactdetails_accountid_idx` (`accountid`),
  KEY `email_idx` (`email`),
  KEY `idx_info` (`deleted`,`modifiedtime`),
  KEY `idx_label` (`deleted`,`label`),
  KEY `idx_owner` (`deleted`,`smownerid`),
  CONSTRAINT `fk_1_vtiger_contactdetails` FOREIGN KEY (`contactid`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_contactdetails`
--

LOCK TABLES `vtiger_contactdetails` WRITE;
/*!40000 ALTER TABLE `vtiger_contactdetails` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_contactdetails` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_contactscf`
--

DROP TABLE IF EXISTS `vtiger_contactscf`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_contactscf` (
  `contactid` int NOT NULL,
  PRIMARY KEY (`contactid`),
  CONSTRAINT `fk_1_vtiger_contactscf` FOREIGN KEY (`contactid`) REFERENCES `vtiger_contactdetails` (`contactid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_contactscf`
--

LOCK TABLES `vtiger_contactscf` WRITE;
/*!40000 ALTER TABLE `vtiger_contactscf` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_contactscf` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_contactsubdetails`
--

DROP TABLE IF EXISTS `vtiger_contactsubdetails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_contactsubdetails` (
  `contactsubscriptionid` int NOT NULL,
  `homephone` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `otherphone` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assistant` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assistantphone` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `laststayintouchrequest` int DEFAULT '0',
  `laststayintouchsavedate` int DEFAULT '0',
  `leadsource` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`contactsubscriptionid`),
  CONSTRAINT `fk_1_vtiger_contactsubdetails` FOREIGN KEY (`contactsubscriptionid`) REFERENCES `vtiger_contactdetails` (`contactid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_contactsubdetails`
--

LOCK TABLES `vtiger_contactsubdetails` WRITE;
/*!40000 ALTER TABLE `vtiger_contactsubdetails` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_contactsubdetails` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_contpotentialrel`
--

DROP TABLE IF EXISTS `vtiger_contpotentialrel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_contpotentialrel` (
  `contactid` int NOT NULL,
  `potentialid` int NOT NULL,
  PRIMARY KEY (`contactid`,`potentialid`),
  KEY `contpotentialrel_potentialid_idx` (`potentialid`),
  KEY `contpotentialrel_contactid_idx` (`contactid`),
  CONSTRAINT `fk_2_vtiger_contpotentialrel` FOREIGN KEY (`potentialid`) REFERENCES `vtiger_potential` (`potentialid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_contpotentialrel`
--

LOCK TABLES `vtiger_contpotentialrel` WRITE;
/*!40000 ALTER TABLE `vtiger_contpotentialrel` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_contpotentialrel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_contract_priority`
--

DROP TABLE IF EXISTS `vtiger_contract_priority`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_contract_priority` (
  `contract_priorityid` int NOT NULL AUTO_INCREMENT,
  `contract_priority` varchar(200) NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT '0',
  `color` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`contract_priorityid`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_contract_priority`
--

LOCK TABLES `vtiger_contract_priority` WRITE;
/*!40000 ALTER TABLE `vtiger_contract_priority` DISABLE KEYS */;
INSERT INTO `vtiger_contract_priority` VALUES (1,'Low',1,220,1,NULL),(2,'Normal',1,221,2,NULL),(3,'High',1,222,3,NULL);
/*!40000 ALTER TABLE `vtiger_contract_priority` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_contract_priority_seq`
--

DROP TABLE IF EXISTS `vtiger_contract_priority_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_contract_priority_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_contract_priority_seq`
--

LOCK TABLES `vtiger_contract_priority_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_contract_priority_seq` DISABLE KEYS */;
INSERT INTO `vtiger_contract_priority_seq` VALUES (3);
/*!40000 ALTER TABLE `vtiger_contract_priority_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_contract_status`
--

DROP TABLE IF EXISTS `vtiger_contract_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_contract_status` (
  `contract_statusid` int NOT NULL AUTO_INCREMENT,
  `contract_status` varchar(200) NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT '0',
  `color` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`contract_statusid`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_contract_status`
--

LOCK TABLES `vtiger_contract_status` WRITE;
/*!40000 ALTER TABLE `vtiger_contract_status` DISABLE KEYS */;
INSERT INTO `vtiger_contract_status` VALUES (1,'Undefined',1,214,1,NULL),(2,'In Planning',1,215,2,NULL),(3,'In Progress',1,216,3,NULL),(4,'On Hold',1,217,4,NULL),(5,'Complete',0,218,5,NULL),(6,'Archived',1,219,6,NULL);
/*!40000 ALTER TABLE `vtiger_contract_status` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_contract_status_seq`
--

DROP TABLE IF EXISTS `vtiger_contract_status_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_contract_status_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_contract_status_seq`
--

LOCK TABLES `vtiger_contract_status_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_contract_status_seq` DISABLE KEYS */;
INSERT INTO `vtiger_contract_status_seq` VALUES (6);
/*!40000 ALTER TABLE `vtiger_contract_status_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_contract_type`
--

DROP TABLE IF EXISTS `vtiger_contract_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_contract_type` (
  `contract_typeid` int NOT NULL AUTO_INCREMENT,
  `contract_type` varchar(200) NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT '0',
  `color` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`contract_typeid`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_contract_type`
--

LOCK TABLES `vtiger_contract_type` WRITE;
/*!40000 ALTER TABLE `vtiger_contract_type` DISABLE KEYS */;
INSERT INTO `vtiger_contract_type` VALUES (1,'Support',1,223,1,NULL),(2,'Services',1,224,2,NULL),(3,'Administrative',1,225,3,NULL);
/*!40000 ALTER TABLE `vtiger_contract_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_contract_type_seq`
--

DROP TABLE IF EXISTS `vtiger_contract_type_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_contract_type_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_contract_type_seq`
--

LOCK TABLES `vtiger_contract_type_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_contract_type_seq` DISABLE KEYS */;
INSERT INTO `vtiger_contract_type_seq` VALUES (3);
/*!40000 ALTER TABLE `vtiger_contract_type_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_convertleadmapping`
--

DROP TABLE IF EXISTS `vtiger_convertleadmapping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_convertleadmapping` (
  `cfmid` int NOT NULL AUTO_INCREMENT,
  `leadfid` int NOT NULL,
  `accountfid` int DEFAULT NULL,
  `contactfid` int DEFAULT NULL,
  `potentialfid` int DEFAULT NULL,
  `editable` int DEFAULT '1',
  PRIMARY KEY (`cfmid`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_convertleadmapping`
--

LOCK TABLES `vtiger_convertleadmapping` WRITE;
/*!40000 ALTER TABLE `vtiger_convertleadmapping` DISABLE KEYS */;
INSERT INTO `vtiger_convertleadmapping` VALUES (1,43,1,0,110,0),(2,49,14,0,0,1),(3,40,3,69,0,1),(4,44,5,77,0,1),(5,52,13,0,0,1),(6,46,9,80,0,0),(7,48,4,0,0,1),(8,61,26,98,0,1),(9,60,30,0,0,1),(10,62,32,104,0,1),(11,63,28,100,0,1),(12,59,24,96,0,1),(13,64,34,106,0,1),(14,61,27,0,0,1),(15,60,31,0,0,1),(16,62,33,0,0,1),(17,63,29,0,0,1),(18,59,25,0,0,1),(19,64,35,0,0,1),(20,65,36,109,125,1),(21,37,0,66,0,1),(22,38,0,67,0,0),(23,41,0,70,0,0),(24,42,0,71,0,1),(25,45,0,76,0,1),(26,55,0,83,0,1),(27,47,0,74,117,1),(28,50,0,0,0,1),(29,53,10,0,0,1),(30,51,17,0,0,1);
/*!40000 ALTER TABLE `vtiger_convertleadmapping` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_convertpotentialmapping`
--

DROP TABLE IF EXISTS `vtiger_convertpotentialmapping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_convertpotentialmapping` (
  `cfmid` int NOT NULL AUTO_INCREMENT,
  `potentialfid` int NOT NULL,
  `projectfid` int DEFAULT NULL,
  `editable` int DEFAULT '1',
  PRIMARY KEY (`cfmid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_convertpotentialmapping`
--

LOCK TABLES `vtiger_convertpotentialmapping` WRITE;
/*!40000 ALTER TABLE `vtiger_convertpotentialmapping` DISABLE KEYS */;
INSERT INTO `vtiger_convertpotentialmapping` VALUES (1,110,609,NULL),(2,125,625,NULL);
/*!40000 ALTER TABLE `vtiger_convertpotentialmapping` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_crmentity`
--

DROP TABLE IF EXISTS `vtiger_crmentity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_crmentity` (
  `crmid` int NOT NULL,
  `smcreatorid` int NOT NULL DEFAULT '0',
  `smownerid` int NOT NULL DEFAULT '0',
  `modifiedby` int NOT NULL DEFAULT '0',
  `setype` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_general_ci,
  `createdtime` datetime NOT NULL,
  `modifiedtime` datetime NOT NULL,
  `viewedtime` datetime DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `version` int NOT NULL DEFAULT '0',
  `presence` int DEFAULT '1',
  `deleted` int NOT NULL DEFAULT '0',
  `smgroupid` int DEFAULT NULL,
  `source` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `label` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`crmid`),
  KEY `crmentity_smcreatorid_idx` (`smcreatorid`),
  KEY `crmentity_modifiedby_idx` (`modifiedby`),
  KEY `crmentity_deleted_idx` (`deleted`),
  KEY `crm_ownerid_del_setype_idx` (`smownerid`,`deleted`,`setype`),
  KEY `vtiger_crmentity_labelidx` (`label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_crmentity`
--

LOCK TABLES `vtiger_crmentity` WRITE;
/*!40000 ALTER TABLE `vtiger_crmentity` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_crmentity` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_crmentity_seq`
--

DROP TABLE IF EXISTS `vtiger_crmentity_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_crmentity_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_crmentity_seq`
--

LOCK TABLES `vtiger_crmentity_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_crmentity_seq` DISABLE KEYS */;
INSERT INTO `vtiger_crmentity_seq` VALUES (1);
/*!40000 ALTER TABLE `vtiger_crmentity_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_crmentity_user_field`
--

DROP TABLE IF EXISTS `vtiger_crmentity_user_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_crmentity_user_field` (
  `recordid` int NOT NULL,
  `userid` int NOT NULL,
  `starred` varchar(100) DEFAULT NULL,
  KEY `record_user_idx` (`recordid`,`userid`),
  CONSTRAINT `fk_vtiger_crmentity_user_field_recordid` FOREIGN KEY (`recordid`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_crmentity_user_field`
--

LOCK TABLES `vtiger_crmentity_user_field` WRITE;
/*!40000 ALTER TABLE `vtiger_crmentity_user_field` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_crmentity_user_field` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_crmentityrel`
--

DROP TABLE IF EXISTS `vtiger_crmentityrel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_crmentityrel` (
  `crmid` int NOT NULL,
  `module` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `relcrmid` int NOT NULL,
  `relmodule` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  KEY `crmid_idx` (`crmid`),
  KEY `relcrmid_idx` (`relcrmid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_crmentityrel`
--

LOCK TABLES `vtiger_crmentityrel` WRITE;
/*!40000 ALTER TABLE `vtiger_crmentityrel` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_crmentityrel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_crmsetup`
--

DROP TABLE IF EXISTS `vtiger_crmsetup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_crmsetup` (
  `userid` int DEFAULT NULL,
  `setup_status` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_crmsetup`
--

LOCK TABLES `vtiger_crmsetup` WRITE;
/*!40000 ALTER TABLE `vtiger_crmsetup` DISABLE KEYS */;
INSERT INTO `vtiger_crmsetup` VALUES (1,1);
/*!40000 ALTER TABLE `vtiger_crmsetup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_cron_task`
--

DROP TABLE IF EXISTS `vtiger_cron_task`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_cron_task` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `handler_file` varchar(100) DEFAULT NULL,
  `frequency` int DEFAULT NULL,
  `laststart` int unsigned DEFAULT NULL,
  `lastend` int unsigned DEFAULT NULL,
  `status` int DEFAULT NULL,
  `module` varchar(100) DEFAULT NULL,
  `sequence` int DEFAULT NULL,
  `description` text,
  `retry_timeout` int DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `handler_file` (`handler_file`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_cron_task`
--

LOCK TABLES `vtiger_cron_task` WRITE;
/*!40000 ALTER TABLE `vtiger_cron_task` DISABLE KEYS */;
INSERT INTO `vtiger_cron_task` VALUES (1,'Workflow','cron/modules/com_vtiger_workflow/com_vtiger_workflow.service',900,NULL,NULL,1,'com_vtiger_workflow',1,'Recommended frequency for Workflow is 15 mins',3600),(2,'RecurringInvoice','cron/modules/SalesOrder/RecurringInvoice.service',43200,NULL,NULL,1,'SalesOrder',2,'Recommended frequency for RecurringInvoice is 12 hours',86400),(3,'SendReminder','cron/SendReminder.service',900,NULL,NULL,1,'Calendar',3,'Recommended frequency for SendReminder is 15 mins',3600),(5,'MailScanner','cron/MailScanner.service',900,NULL,NULL,1,'Settings',5,'Recommended frequency for MailScanner is 15 mins',3600),(6,'Scheduled Import','cron/modules/Import/ScheduledImport.service',900,NULL,NULL,1,'Import',6,'Recommended frequency for MailScanner is 15 mins',21600),(7,'ScheduleReports','cron/modules/Reports/ScheduleReports.service',900,NULL,NULL,1,'Reports',7,'Recommended frequency for ScheduleReports is 15 mins',10800);
/*!40000 ALTER TABLE `vtiger_cron_task` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_currencies`
--

DROP TABLE IF EXISTS `vtiger_currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_currencies` (
  `currencyid` int NOT NULL AUTO_INCREMENT,
  `currency_name` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `currency_code` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `currency_symbol` varchar(11) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`currencyid`)
) ENGINE=InnoDB AUTO_INCREMENT=139 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_currencies`
--

LOCK TABLES `vtiger_currencies` WRITE;
/*!40000 ALTER TABLE `vtiger_currencies` DISABLE KEYS */;
INSERT INTO `vtiger_currencies` VALUES (1,'Albania, Leke','ALB','Lek'),(2,'Argentina, Pesos','ARS','$'),(3,'Aruba, Guilders','AWG','ƒ'),(4,'Australia, Dollars','AUD','$'),(5,'Azerbaijan, New Manats','AZN','ман'),(6,'Bahamas, Dollars','BSD','$'),(7,'Bahrain, Dinar','BHD','BD'),(8,'Barbados, Dollars','BBD','$'),(9,'Belarus, Rubles','BYR','p.'),(10,'Belize, Dollars','BZD','BZ$'),(11,'Bermuda, Dollars','BMD','$'),(12,'Bolivia, Bolivianos','BOB','$b'),(13,'China, Yuan Renminbi','CNY','¥'),(14,'Convertible Marka','BAM','KM'),(15,'Botswana, Pulas','BWP','P'),(16,'Bulgaria, Leva','BGN','лв'),(17,'Brazil, Reais','BRL','R$'),(18,'Great Britain Pounds','GBP','£'),(19,'Brunei Darussalam, Dollars','BND','$'),(20,'Canada, Dollars','CAD','$'),(21,'Cayman Islands, Dollars','KYD','$'),(22,'Chile, Pesos','CLP','$'),(23,'Colombia, Pesos','COP','$'),(24,'Costa Rica, Colón','CRC','₡'),(25,'Croatia, Kuna','HRK','kn'),(26,'Cuba, Pesos','CUP','₱'),(27,'Czech Republic, Koruny','CZK','Kč'),(28,'Cyprus, Pounds','CYP','£'),(29,'Denmark, Kroner','DKK','kr'),(30,'Dominican Republic, Pesos','DOP','RD$'),(31,'East Caribbean, Dollars','XCD','$'),(32,'Egypt, Pounds','EGP','E£'),(33,'El Salvador, Colón','SVC','₡'),(34,'England, Pounds','GBP','£'),(35,'Estonia, Krooni','EEK','kr'),(36,'Euro','EUR','€'),(37,'Falkland Islands, Pounds','FKP','£'),(38,'Fiji, Dollars','FJD','$'),(39,'Ghana, Cedis','GHC','¢'),(40,'Gibraltar, Pounds','GIP','£'),(41,'Guatemala, Quetzales','GTQ','Q'),(42,'Guernsey, Pounds','GGP','£'),(43,'Guyana, Dollars','GYD','$'),(44,'Honduras, Lempiras','HNL','L'),(45,'Hong Kong, Dollars','HKD','HK$'),(46,'Hungary, Forint','HUF','Ft'),(47,'Iceland, Krona','ISK','kr'),(48,'India, Rupees','INR','₹'),(49,'Indonesia, Rupiahs','IDR','Rp'),(50,'Iran, Rials','IRR','﷼'),(51,'Isle of Man, Pounds','IMP','£'),(52,'Israel, New Shekels','ILS','₪'),(53,'Jamaica, Dollars','JMD','J$'),(54,'Japan, Yen','JPY','¥'),(55,'Jersey, Pounds','JEP','£'),(56,'Jordan, Dinar','JOD','JOD'),(57,'Kazakhstan, Tenge','KZT','〒'),(58,'Kenya, Shilling','KES','KES'),(59,'Korea (North), Won','KPW','₩'),(60,'Korea (South), Won','KRW','₩'),(61,'Kuwait, Dinar','KWD','KWD'),(62,'Kyrgyzstan, Soms','KGS','лв'),(63,'Laos, Kips','LAK','₭'),(64,'Latvia, Lati','LVL','Ls'),(65,'Lebanon, Pounds','LBP','£'),(66,'Liberia, Dollars','LRD','$'),(67,'Switzerland Francs','CHF','CHF'),(68,'Lithuania, Litai','LTL','Lt'),(69,'MADAGASCAR, Malagasy Ariary','MGA','MGA'),(70,'Macedonia, Denars','MKD','ден'),(71,'Malaysia, Ringgits','MYR','RM'),(72,'Malta, Liri','MTL','₤'),(73,'Mauritius, Rupees','MUR','₨'),(74,'Mexico, Pesos','MXN','$'),(75,'Mongolia, Tugriks','MNT','₮'),(76,'Mozambique, Meticais','MZN','MT'),(77,'Namibia, Dollars','NAD','$'),(78,'Nepal, Rupees','NPR','₨'),(79,'Netherlands Antilles, Guilders','ANG','ƒ'),(80,'New Zealand, Dollars','NZD','$'),(81,'Nicaragua, Cordobas','NIO','C$'),(82,'Nigeria, Nairas','NGN','₦'),(83,'North Korea, Won','KPW','₩'),(84,'Norway, Krone','NOK','kr'),(85,'Oman, Rials','OMR','﷼'),(86,'Pakistan, Rupees','PKR','₨'),(87,'Panama, Balboa','PAB','B/.'),(88,'Paraguay, Guarani','PYG','Gs'),(89,'Peru, Nuevos Soles','PEN','S/.'),(90,'Philippines, Pesos','PHP','Php'),(91,'Poland, Zlotych','PLN','zł'),(92,'Qatar, Rials','QAR','﷼'),(93,'Romania, New Lei','RON','lei'),(94,'Russia, Rubles','RUB','руб'),(95,'Saint Helena, Pounds','SHP','£'),(96,'Saudi Arabia, Riyals','SAR','﷼'),(97,'Serbia, Dinars','RSD','Дин.'),(98,'Seychelles, Rupees','SCR','₨'),(99,'Singapore, Dollars','SGD','$'),(100,'Solomon Islands, Dollars','SBD','$'),(101,'Somalia, Shillings','SOS','S'),(102,'South Africa, Rand','ZAR','R'),(103,'South Korea, Won','KRW','₩'),(104,'Sri Lanka, Rupees','LKR','₨'),(105,'Sweden, Kronor','SEK','kr'),(106,'Switzerland, Francs','CHF','CHF'),(107,'Suriname, Dollars','SRD','$'),(108,'Syria, Pounds','SYP','£'),(109,'Taiwan, New Dollars','TWD','NT$'),(110,'Thailand, Baht','THB','฿'),(111,'Trinidad and Tobago, Dollars','TTD','TT$'),(112,'Turkey, New Lira','TRY','YTL'),(113,'Turkey, Liras','TRL','₤'),(114,'Tuvalu, Dollars','TVD','$'),(115,'Ukraine, Hryvnia','UAH','₴'),(116,'United Arab Emirates, Dirham','AED','AED'),(117,'United Kingdom, Pounds','GBP','£'),(118,'United Republic of Tanzania, Shilling','TZS','TZS'),(119,'USA, Dollars','USD','$'),(120,'Uruguay, Pesos','UYU','$U'),(121,'Uzbekistan, Sums','UZS','лв'),(122,'Venezuela, Bolivares Fuertes','VEF','Bs'),(123,'Vietnam, Dong','VND','₫'),(124,'Zambia, Kwacha','ZMK','ZMK'),(125,'Yemen, Rials','YER','﷼'),(126,'Zimbabwe Dollars','ZWD','Z$'),(127,'Malawi, Kwacha','MWK','MK'),(128,'Tunisian, Dinar','TD','TD'),(129,'Moroccan, Dirham','MAD','DH'),(130,'Iraqi Dinar','IQD','ID'),(131,'Maldivian Ruffiya','MVR','MVR'),(132,'Ugandan Shilling','UGX','Sh'),(133,'Sudanese Pound','SDG','£'),(134,'CFA Franc BCEAO','XOF','CFA'),(135,'CFA Franc BEAC','XAF','CFA'),(136,'Haiti, Gourde','HTG','G'),(137,'Libya, Dinar','LYD','LYD'),(138,'CFP Franc','XPF','F');
/*!40000 ALTER TABLE `vtiger_currencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_currencies_seq`
--

DROP TABLE IF EXISTS `vtiger_currencies_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_currencies_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_currencies_seq`
--

LOCK TABLES `vtiger_currencies_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_currencies_seq` DISABLE KEYS */;
INSERT INTO `vtiger_currencies_seq` VALUES (138);
/*!40000 ALTER TABLE `vtiger_currencies_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_currency`
--

DROP TABLE IF EXISTS `vtiger_currency`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_currency` (
  `currencyid` int NOT NULL AUTO_INCREMENT,
  `currency` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `sortorderid` int NOT NULL DEFAULT '0',
  `presence` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`currencyid`),
  UNIQUE KEY `currency_currency_idx` (`currency`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_currency`
--

LOCK TABLES `vtiger_currency` WRITE;
/*!40000 ALTER TABLE `vtiger_currency` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_currency` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_currency_decimal_separator`
--

DROP TABLE IF EXISTS `vtiger_currency_decimal_separator`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_currency_decimal_separator` (
  `currency_decimal_separatorid` int NOT NULL AUTO_INCREMENT,
  `currency_decimal_separator` varchar(2) COLLATE utf8mb4_general_ci NOT NULL,
  `sortorderid` int NOT NULL DEFAULT '0',
  `presence` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`currency_decimal_separatorid`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_currency_decimal_separator`
--

LOCK TABLES `vtiger_currency_decimal_separator` WRITE;
/*!40000 ALTER TABLE `vtiger_currency_decimal_separator` DISABLE KEYS */;
INSERT INTO `vtiger_currency_decimal_separator` VALUES (1,'.',0,1),(2,',',1,1),(3,'\'',2,1),(4,' ',3,1),(5,'$',4,1);
/*!40000 ALTER TABLE `vtiger_currency_decimal_separator` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_currency_decimal_separator_seq`
--

DROP TABLE IF EXISTS `vtiger_currency_decimal_separator_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_currency_decimal_separator_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_currency_decimal_separator_seq`
--

LOCK TABLES `vtiger_currency_decimal_separator_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_currency_decimal_separator_seq` DISABLE KEYS */;
INSERT INTO `vtiger_currency_decimal_separator_seq` VALUES (5);
/*!40000 ALTER TABLE `vtiger_currency_decimal_separator_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_currency_grouping_pattern`
--

DROP TABLE IF EXISTS `vtiger_currency_grouping_pattern`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_currency_grouping_pattern` (
  `currency_grouping_patternid` int NOT NULL AUTO_INCREMENT,
  `currency_grouping_pattern` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `sortorderid` int NOT NULL DEFAULT '0',
  `presence` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`currency_grouping_patternid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_currency_grouping_pattern`
--

LOCK TABLES `vtiger_currency_grouping_pattern` WRITE;
/*!40000 ALTER TABLE `vtiger_currency_grouping_pattern` DISABLE KEYS */;
INSERT INTO `vtiger_currency_grouping_pattern` VALUES (1,'123,456,789',0,1),(2,'123456789',1,1),(3,'123456,789',2,1),(4,'12,34,56,789',3,1);
/*!40000 ALTER TABLE `vtiger_currency_grouping_pattern` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_currency_grouping_pattern_seq`
--

DROP TABLE IF EXISTS `vtiger_currency_grouping_pattern_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_currency_grouping_pattern_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_currency_grouping_pattern_seq`
--

LOCK TABLES `vtiger_currency_grouping_pattern_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_currency_grouping_pattern_seq` DISABLE KEYS */;
INSERT INTO `vtiger_currency_grouping_pattern_seq` VALUES (4);
/*!40000 ALTER TABLE `vtiger_currency_grouping_pattern_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_currency_grouping_separator`
--

DROP TABLE IF EXISTS `vtiger_currency_grouping_separator`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_currency_grouping_separator` (
  `currency_grouping_separatorid` int NOT NULL AUTO_INCREMENT,
  `currency_grouping_separator` varchar(2) COLLATE utf8mb4_general_ci NOT NULL,
  `sortorderid` int NOT NULL DEFAULT '0',
  `presence` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`currency_grouping_separatorid`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_currency_grouping_separator`
--

LOCK TABLES `vtiger_currency_grouping_separator` WRITE;
/*!40000 ALTER TABLE `vtiger_currency_grouping_separator` DISABLE KEYS */;
INSERT INTO `vtiger_currency_grouping_separator` VALUES (1,',',0,1),(2,'.',1,1),(3,'\'',2,1),(4,' ',3,1),(5,'$',4,1);
/*!40000 ALTER TABLE `vtiger_currency_grouping_separator` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_currency_grouping_separator_seq`
--

DROP TABLE IF EXISTS `vtiger_currency_grouping_separator_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_currency_grouping_separator_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_currency_grouping_separator_seq`
--

LOCK TABLES `vtiger_currency_grouping_separator_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_currency_grouping_separator_seq` DISABLE KEYS */;
INSERT INTO `vtiger_currency_grouping_separator_seq` VALUES (5);
/*!40000 ALTER TABLE `vtiger_currency_grouping_separator_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_currency_info`
--

DROP TABLE IF EXISTS `vtiger_currency_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_currency_info` (
  `id` int NOT NULL AUTO_INCREMENT,
  `currency_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `currency_code` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `currency_symbol` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `conversion_rate` decimal(12,5) DEFAULT NULL,
  `currency_status` varchar(25) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `defaultid` varchar(10) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '0',
  `deleted` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_currency_info`
--

LOCK TABLES `vtiger_currency_info` WRITE;
/*!40000 ALTER TABLE `vtiger_currency_info` DISABLE KEYS */;
INSERT INTO `vtiger_currency_info` VALUES (1,'Japan, Yen','JPY','¥',1.00000,'Active','-11',0);
/*!40000 ALTER TABLE `vtiger_currency_info` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_currency_info_seq`
--

DROP TABLE IF EXISTS `vtiger_currency_info_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_currency_info_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_currency_info_seq`
--

LOCK TABLES `vtiger_currency_info_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_currency_info_seq` DISABLE KEYS */;
INSERT INTO `vtiger_currency_info_seq` VALUES (1);
/*!40000 ALTER TABLE `vtiger_currency_info_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_currency_symbol_placement`
--

DROP TABLE IF EXISTS `vtiger_currency_symbol_placement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_currency_symbol_placement` (
  `currency_symbol_placementid` int NOT NULL AUTO_INCREMENT,
  `currency_symbol_placement` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `sortorderid` int NOT NULL DEFAULT '0',
  `presence` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`currency_symbol_placementid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_currency_symbol_placement`
--

LOCK TABLES `vtiger_currency_symbol_placement` WRITE;
/*!40000 ALTER TABLE `vtiger_currency_symbol_placement` DISABLE KEYS */;
INSERT INTO `vtiger_currency_symbol_placement` VALUES (1,'$1.0',0,1),(2,'1.0$',1,1);
/*!40000 ALTER TABLE `vtiger_currency_symbol_placement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_currency_symbol_placement_seq`
--

DROP TABLE IF EXISTS `vtiger_currency_symbol_placement_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_currency_symbol_placement_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_currency_symbol_placement_seq`
--

LOCK TABLES `vtiger_currency_symbol_placement_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_currency_symbol_placement_seq` DISABLE KEYS */;
INSERT INTO `vtiger_currency_symbol_placement_seq` VALUES (2);
/*!40000 ALTER TABLE `vtiger_currency_symbol_placement_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_customaction`
--

DROP TABLE IF EXISTS `vtiger_customaction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_customaction` (
  `cvid` int NOT NULL,
  `subject` varchar(250) COLLATE utf8mb4_general_ci NOT NULL,
  `module` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `content` text COLLATE utf8mb4_general_ci,
  KEY `customaction_cvid_idx` (`cvid`),
  CONSTRAINT `fk_1_vtiger_customaction` FOREIGN KEY (`cvid`) REFERENCES `vtiger_customview` (`cvid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_customaction`
--

LOCK TABLES `vtiger_customaction` WRITE;
/*!40000 ALTER TABLE `vtiger_customaction` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_customaction` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_customerdetails`
--

DROP TABLE IF EXISTS `vtiger_customerdetails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_customerdetails` (
  `customerid` int NOT NULL,
  `portal` varchar(3) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `support_start_date` date DEFAULT NULL,
  `support_end_date` date DEFAULT NULL,
  PRIMARY KEY (`customerid`),
  CONSTRAINT `fk_1_vtiger_customerdetails` FOREIGN KEY (`customerid`) REFERENCES `vtiger_contactdetails` (`contactid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_customerdetails`
--

LOCK TABLES `vtiger_customerdetails` WRITE;
/*!40000 ALTER TABLE `vtiger_customerdetails` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_customerdetails` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_customerportal_fields`
--

DROP TABLE IF EXISTS `vtiger_customerportal_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_customerportal_fields` (
  `tabid` int NOT NULL,
  `fieldinfo` text,
  `records_visible` int DEFAULT NULL,
  PRIMARY KEY (`tabid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_customerportal_fields`
--

LOCK TABLES `vtiger_customerportal_fields` WRITE;
/*!40000 ALTER TABLE `vtiger_customerportal_fields` DISABLE KEYS */;
INSERT INTO `vtiger_customerportal_fields` VALUES (4,'{\"lastname\":1,\"assigned_user_id\":1}',1),(6,'{\"accountname\":1,\"assigned_user_id\":1}',1),(8,'{\"notes_title\":1,\"assigned_user_id\":1,\"filename\":0}',1),(13,'{\"ticket_title\":1,\"assigned_user_id\":1,\"ticketpriorities\":1,\"ticketstatus\":1,\"description\":1,\"product_id\":1,\"ticketseverities\":1,\"ticketcategories\":1}',1),(14,'{\"productname\":1,\"assigned_user_id\":1}',1),(15,'{\"faqstatus\":1,\"question\":1,\"faq_answer\":1}',1),(20,'{\"subject\":1,\"quotestage\":1,\"account_id\":1,\"assigned_user_id\":1,\"bill_street\":1,\"ship_street\":1}',1),(23,'{\"subject\":1,\"account_id\":1,\"assigned_user_id\":1,\"bill_street\":1,\"ship_street\":1}',1),(34,'{\"subject\":1,\"assigned_user_id\":1}',NULL),(35,'{\"servicename\":1}',1),(39,'{\"projectmilestonename\":1,\"projectid\":1,\"assigned_user_id\":1}',1),(40,'{\"projecttaskname\":1,\"projectid\":1,\"assigned_user_id\":1}',1),(41,'{\"projectname\":1,\"assigned_user_id\":1}',1),(43,'{\"product\":1,\"assigned_user_id\":1,\"serialnumber\":1,\"datesold\":1,\"dateinservice\":1,\"assetstatus\":1,\"assetname\":1,\"account\":1}',1);
/*!40000 ALTER TABLE `vtiger_customerportal_fields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_customerportal_prefs`
--

DROP TABLE IF EXISTS `vtiger_customerportal_prefs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_customerportal_prefs` (
  `tabid` int NOT NULL,
  `prefkey` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `prefvalue` int DEFAULT NULL,
  PRIMARY KEY (`tabid`,`prefkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_customerportal_prefs`
--

LOCK TABLES `vtiger_customerportal_prefs` WRITE;
/*!40000 ALTER TABLE `vtiger_customerportal_prefs` DISABLE KEYS */;
INSERT INTO `vtiger_customerportal_prefs` VALUES (0,'defaultassignee',1),(0,'userid',1),(4,'showrelatedinfo',1),(6,'showrelatedinfo',1),(8,'showrelatedinfo',1),(13,'showrelatedinfo',1),(14,'showrelatedinfo',1),(15,'showrelatedinfo',1),(20,'showrelatedinfo',1),(23,'showrelatedinfo',1),(35,'showrelatedinfo',1),(39,'showrelatedinfo',1),(40,'showrelatedinfo',1),(41,'showrelatedinfo',1),(43,'showrelatedinfo',1);
/*!40000 ALTER TABLE `vtiger_customerportal_prefs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_customerportal_relatedmoduleinfo`
--

DROP TABLE IF EXISTS `vtiger_customerportal_relatedmoduleinfo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_customerportal_relatedmoduleinfo` (
  `tabid` int NOT NULL,
  `relatedmodules` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`tabid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_customerportal_relatedmoduleinfo`
--

LOCK TABLES `vtiger_customerportal_relatedmoduleinfo` WRITE;
/*!40000 ALTER TABLE `vtiger_customerportal_relatedmoduleinfo` DISABLE KEYS */;
INSERT INTO `vtiger_customerportal_relatedmoduleinfo` VALUES (8,'[{\"name\":\"History\",\"value\":1}]'),(13,'[{\"name\":\"History\",\"value\":1},{\"name\":\"ModComments\",\"value\":1},{\"name\":\"Documents\",\"value\":1}]'),(14,'[{\"name\":\"History\",\"value\":1}]'),(20,'[{\"name\":\"History\",\"value\":1}]'),(23,'[{\"name\":\"History\",\"value\":1}]'),(34,'[{\"name\":\"History\",\"value\":1}]'),(35,'[{\"name\":\"History\",\"value\":1}]'),(39,'[{\"name\":\"History\",\"value\":1}]'),(40,'[{\"name\":\"History\",\"value\":1},{\"name\":\"ModComments\",\"value\":1}]'),(41,'[{\"name\":\"History\",\"value\":1},{\"name\":\"ModComments\",\"value\":1},{\"name\":\"ProjectTask\",\"value\":1},{\"name\":\"ProjectMilestone\",\"value\":1},{\"name\":\"Documents\",\"value\":1}]'),(43,'[{\"name\":\"History\",\"value\":1}]');
/*!40000 ALTER TABLE `vtiger_customerportal_relatedmoduleinfo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_customerportal_settings`
--

DROP TABLE IF EXISTS `vtiger_customerportal_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_customerportal_settings` (
  `id` int DEFAULT NULL,
  `url` varchar(250) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `default_assignee` int DEFAULT NULL,
  `support_notification` int DEFAULT NULL,
  `announcement` text COLLATE utf8mb4_general_ci,
  `shortcuts` text COLLATE utf8mb4_general_ci,
  `widgets` text COLLATE utf8mb4_general_ci,
  `charts` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_customerportal_settings`
--

LOCK TABLES `vtiger_customerportal_settings` WRITE;
/*!40000 ALTER TABLE `vtiger_customerportal_settings` DISABLE KEYS */;
INSERT INTO `vtiger_customerportal_settings` VALUES (1,NULL,1,NULL,NULL,'{\"Documents\":{\"LBL_ADD_DOCUMENT\":1},\"HelpDesk\":{\"LBL_CREATE_TICKET\":1,\"LBL_OPEN_TICKETS\":1}}','{\"widgets\":{\"HelpDesk\":1,\"Documents\":1,\"Faq\":1}}','{\"charts\":{\"OpenTicketsByPriority\":1,\"TicketsClosureTimeByPriority\":1}}');
/*!40000 ALTER TABLE `vtiger_customerportal_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_customerportal_tabs`
--

DROP TABLE IF EXISTS `vtiger_customerportal_tabs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_customerportal_tabs` (
  `tabid` int NOT NULL,
  `visible` int DEFAULT '1',
  `sequence` int DEFAULT NULL,
  `createrecord` tinyint(1) NOT NULL DEFAULT '0',
  `editrecord` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`tabid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_customerportal_tabs`
--

LOCK TABLES `vtiger_customerportal_tabs` WRITE;
/*!40000 ALTER TABLE `vtiger_customerportal_tabs` DISABLE KEYS */;
INSERT INTO `vtiger_customerportal_tabs` VALUES (4,0,9,0,1),(6,0,10,0,1),(8,1,8,1,0),(13,1,2,1,1),(14,1,6,0,0),(15,1,3,0,0),(20,1,5,0,0),(23,1,4,0,0),(34,1,25,0,0),(35,1,7,0,0),(39,1,13,0,0),(40,1,12,0,0),(41,1,11,0,0),(43,1,14,0,1);
/*!40000 ALTER TABLE `vtiger_customerportal_tabs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_customview`
--

DROP TABLE IF EXISTS `vtiger_customview`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_customview` (
  `cvid` int NOT NULL,
  `viewname` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `setdefault` int DEFAULT '0',
  `setmetrics` int DEFAULT '0',
  `entitytype` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `status` int DEFAULT '1',
  `userid` int DEFAULT '1',
  PRIMARY KEY (`cvid`),
  KEY `customview_entitytype_idx` (`entitytype`),
  CONSTRAINT `fk_1_vtiger_customview` FOREIGN KEY (`entitytype`) REFERENCES `vtiger_tab` (`name`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_customview`
--

LOCK TABLES `vtiger_customview` WRITE;
/*!40000 ALTER TABLE `vtiger_customview` DISABLE KEYS */;
INSERT INTO `vtiger_customview` VALUES (1,'All',1,0,'Leads',0,1),(2,'Hot Leads',0,1,'Leads',3,1),(3,'This Month Leads',0,0,'Leads',3,1),(4,'All',1,0,'Accounts',0,1),(5,'Prospect Accounts',0,1,'Accounts',3,1),(6,'New This Week',0,0,'Accounts',3,1),(7,'All',1,0,'Contacts',0,1),(8,'Contacts Address',0,0,'Contacts',3,1),(9,'Todays Birthday',0,0,'Contacts',3,1),(10,'All',1,0,'Potentials',0,1),(11,'Potentials Won',0,1,'Potentials',3,1),(12,'Prospecting',0,0,'Potentials',3,1),(13,'All',1,0,'HelpDesk',0,1),(14,'Open Tickets',0,1,'HelpDesk',3,1),(15,'High Prioriy Tickets',0,0,'HelpDesk',3,1),(16,'All',1,0,'Quotes',0,1),(17,'Open Quotes',0,1,'Quotes',3,1),(18,'Rejected Quotes',0,0,'Quotes',3,1),(19,'All',1,0,'Calendar',0,1),(20,'All',1,0,'Emails',0,1),(21,'All',1,0,'Invoice',0,1),(22,'All',1,0,'Documents',0,1),(23,'All',1,0,'PriceBooks',0,1),(24,'All',1,0,'Products',0,1),(25,'All',1,0,'PurchaseOrder',0,1),(26,'All',1,0,'SalesOrder',0,1),(27,'All',1,0,'Vendors',0,1),(28,'All',1,0,'Faq',0,1),(29,'All',1,0,'Campaigns',0,1),(30,'All',1,0,'Webmails',0,1),(31,'Drafted FAQ',0,0,'Faq',3,1),(32,'Published FAQ',0,0,'Faq',3,1),(33,'Open Purchase Orders',0,0,'PurchaseOrder',3,1),(34,'Received Purchase Orders',0,0,'PurchaseOrder',3,1),(35,'Open Invoices',0,0,'Invoice',3,1),(36,'Paid Invoices',0,0,'Invoice',3,1),(37,'Pending Sales Orders',0,0,'SalesOrder',3,1),(38,'All',1,0,'ServiceContracts',0,1),(39,'All',1,0,'Services',0,1),(40,'All',0,0,'ModComments',0,1),(44,'All',0,0,'SMSNotifier',0,1),(45,'All',1,0,'Assets',0,1),(49,'All',1,0,'ProjectMilestone',0,1),(50,'All',1,0,'ProjectTask',0,1),(51,'All',1,0,'Project',0,1),(52,'All',1,0,'Dailyreports',0,1);
/*!40000 ALTER TABLE `vtiger_customview` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_customview_seq`
--

DROP TABLE IF EXISTS `vtiger_customview_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_customview_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_customview_seq`
--

LOCK TABLES `vtiger_customview_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_customview_seq` DISABLE KEYS */;
INSERT INTO `vtiger_customview_seq` VALUES (52);
/*!40000 ALTER TABLE `vtiger_customview_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_cv2group`
--

DROP TABLE IF EXISTS `vtiger_cv2group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_cv2group` (
  `cvid` int NOT NULL,
  `groupid` int NOT NULL,
  KEY `vtiger_cv2group_ibfk_1` (`cvid`),
  KEY `vtiger_groups_ibfk_1` (`groupid`),
  CONSTRAINT `vtiger_customview_ibfk_2` FOREIGN KEY (`cvid`) REFERENCES `vtiger_customview` (`cvid`) ON DELETE CASCADE,
  CONSTRAINT `vtiger_groups_ibfk_1` FOREIGN KEY (`groupid`) REFERENCES `vtiger_groups` (`groupid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_cv2group`
--

LOCK TABLES `vtiger_cv2group` WRITE;
/*!40000 ALTER TABLE `vtiger_cv2group` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_cv2group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_cv2users`
--

DROP TABLE IF EXISTS `vtiger_cv2users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_cv2users` (
  `cvid` int NOT NULL,
  `userid` int NOT NULL,
  KEY `vtiger_cv2users_ibfk_1` (`cvid`),
  KEY `vtiger_users_ibfk_1` (`userid`),
  CONSTRAINT `vtiger_customview_ibfk_1` FOREIGN KEY (`cvid`) REFERENCES `vtiger_customview` (`cvid`) ON DELETE CASCADE,
  CONSTRAINT `vtiger_users_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `vtiger_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_cv2users`
--

LOCK TABLES `vtiger_cv2users` WRITE;
/*!40000 ALTER TABLE `vtiger_cv2users` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_cv2users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_cvadvfilter`
--

DROP TABLE IF EXISTS `vtiger_cvadvfilter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_cvadvfilter` (
  `cvid` int NOT NULL,
  `columnindex` int NOT NULL,
  `columnname` varchar(250) COLLATE utf8mb4_general_ci DEFAULT '',
  `comparator` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `value` varchar(512) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `groupid` int DEFAULT '1',
  `column_condition` varchar(255) COLLATE utf8mb4_general_ci DEFAULT 'and',
  PRIMARY KEY (`cvid`,`columnindex`),
  KEY `cvadvfilter_cvid_idx` (`cvid`),
  CONSTRAINT `fk_1_vtiger_cvadvfilter` FOREIGN KEY (`cvid`) REFERENCES `vtiger_customview` (`cvid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_cvadvfilter`
--

LOCK TABLES `vtiger_cvadvfilter` WRITE;
/*!40000 ALTER TABLE `vtiger_cvadvfilter` DISABLE KEYS */;
INSERT INTO `vtiger_cvadvfilter` VALUES (2,0,'vtiger_leaddetails:leadstatus:leadstatus:Leads_Lead_Status:V','e','Hot',1,'and'),(5,0,'vtiger_account:account_type:accounttype:Accounts_Type:V','e','Prospect',1,'and'),(11,0,'vtiger_potential:sales_stage:sales_stage:Potentials_Sales_Stage:V','e','Closed Won',1,'and'),(12,0,'vtiger_potential:sales_stage:sales_stage:Potentials_Sales_Stage:V','e','Prospecting',1,'and'),(14,0,'vtiger_troubletickets:status:ticketstatus:HelpDesk_Status:V','n','Closed',1,'and'),(15,0,'vtiger_troubletickets:priority:ticketpriorities:HelpDesk_Priority:V','e','High',1,'and'),(17,0,'vtiger_quotes:quotestage:quotestage:Quotes_Quote_Stage:V','n','Accepted',1,'and'),(17,1,'vtiger_quotes:quotestage:quotestage:Quotes_Quote_Stage:V','n','Rejected',1,'and'),(18,0,'vtiger_quotes:quotestage:quotestage:Quotes_Quote_Stage:V','e','Rejected',1,'and'),(31,0,'vtiger_faq:status:faqstatus:Faq_Status:V','e','Draft',1,'and'),(32,0,'vtiger_faq:status:faqstatus:Faq_Status:V','e','Published',1,'and'),(33,0,'vtiger_purchaseorder:postatus:postatus:PurchaseOrder_Status:V','e','Created, Approved, Delivered',1,'and'),(34,0,'vtiger_purchaseorder:postatus:postatus:PurchaseOrder_Status:V','e','Received Shipment',1,'and'),(35,0,'vtiger_invoice:invoicestatus:invoicestatus:Invoice_Status:V','e','Created, Approved, Sent',1,'and'),(36,0,'vtiger_invoice:invoicestatus:invoicestatus:Invoice_Status:V','e','Paid',1,'and'),(37,0,'vtiger_salesorder:sostatus:sostatus:SalesOrder_Status:V','e','Created, Approved',1,'and');
/*!40000 ALTER TABLE `vtiger_cvadvfilter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_cvadvfilter_grouping`
--

DROP TABLE IF EXISTS `vtiger_cvadvfilter_grouping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_cvadvfilter_grouping` (
  `groupid` int NOT NULL,
  `cvid` int NOT NULL,
  `group_condition` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `condition_expression` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`groupid`,`cvid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_cvadvfilter_grouping`
--

LOCK TABLES `vtiger_cvadvfilter_grouping` WRITE;
/*!40000 ALTER TABLE `vtiger_cvadvfilter_grouping` DISABLE KEYS */;
INSERT INTO `vtiger_cvadvfilter_grouping` VALUES (1,2,'',''),(1,5,'',''),(1,11,'',''),(1,12,'',''),(1,14,'',''),(1,15,'',''),(1,17,'',''),(1,18,'',''),(1,31,'',''),(1,32,'',''),(1,33,'',''),(1,34,'',''),(1,35,'',''),(1,36,'',''),(1,37,'','');
/*!40000 ALTER TABLE `vtiger_cvadvfilter_grouping` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_cvcolumnlist`
--

DROP TABLE IF EXISTS `vtiger_cvcolumnlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_cvcolumnlist` (
  `cvid` int NOT NULL,
  `columnindex` int NOT NULL,
  `columnname` varchar(250) COLLATE utf8mb4_general_ci DEFAULT '',
  PRIMARY KEY (`cvid`,`columnindex`),
  KEY `cvcolumnlist_columnindex_idx` (`columnindex`),
  KEY `cvcolumnlist_cvid_idx` (`cvid`),
  CONSTRAINT `fk_1_vtiger_cvcolumnlist` FOREIGN KEY (`cvid`) REFERENCES `vtiger_customview` (`cvid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_cvcolumnlist`
--

LOCK TABLES `vtiger_cvcolumnlist` WRITE;
/*!40000 ALTER TABLE `vtiger_cvcolumnlist` DISABLE KEYS */;
INSERT INTO `vtiger_cvcolumnlist` VALUES (1,1,'vtiger_leaddetails:lastname:lastname:Leads_Last_Name:V'),(1,2,'vtiger_leaddetails:firstname:firstname:Leads_First_Name:V'),(1,3,'vtiger_leaddetails:company:company:Leads_Company:V'),(1,4,'vtiger_leadaddress:phone:phone:Leads_Phone:V'),(1,5,'vtiger_leadsubdetails:website:website:Leads_Website:V'),(1,6,'vtiger_leaddetails:email:email:Leads_Email:V'),(1,7,'vtiger_crmentity:smownerid:assigned_user_id:Leads_Assigned_To:V'),(2,0,'vtiger_leaddetails:lastname:lastname:Leads_Last_Name:V'),(2,1,'vtiger_leaddetails:firstname:firstname:Leads_First_Name:V'),(2,2,'vtiger_leaddetails:company:company:Leads_Company:V'),(2,3,'vtiger_leaddetails:leadsource:leadsource:Leads_Lead_Source:V'),(2,4,'vtiger_leadsubdetails:website:website:Leads_Website:V'),(2,5,'vtiger_leaddetails:email:email:Leads_Email:E'),(3,0,'vtiger_leaddetails:lastname:lastname:Leads_Last_Name:V'),(3,1,'vtiger_leaddetails:firstname:firstname:Leads_First_Name:V'),(3,2,'vtiger_leaddetails:company:company:Leads_Company:V'),(3,3,'vtiger_leaddetails:leadsource:leadsource:Leads_Lead_Source:V'),(3,4,'vtiger_leadsubdetails:website:website:Leads_Website:V'),(3,5,'vtiger_leaddetails:email:email:Leads_Email:E'),(4,1,'vtiger_account:accountname:accountname:Accounts_Account_Name:V'),(4,2,'vtiger_accountbillads:bill_city:bill_city:Accounts_City:V'),(4,3,'vtiger_account:website:website:Accounts_Website:V'),(4,4,'vtiger_account:phone:phone:Accounts_Phone:V'),(4,5,'vtiger_crmentity:smownerid:assigned_user_id:Accounts_Assigned_To:V'),(5,0,'vtiger_account:accountname:accountname:Accounts_Account_Name:V'),(5,1,'vtiger_account:phone:phone:Accounts_Phone:V'),(5,2,'vtiger_account:website:website:Accounts_Website:V'),(5,3,'vtiger_account:rating:rating:Accounts_Rating:V'),(5,4,'vtiger_crmentity:smownerid:assigned_user_id:Accounts_Assigned_To:V'),(6,0,'vtiger_account:accountname:accountname:Accounts_Account_Name:V'),(6,1,'vtiger_account:phone:phone:Accounts_Phone:V'),(6,2,'vtiger_account:website:website:Accounts_Website:V'),(6,3,'vtiger_accountbillads:bill_city:bill_city:Accounts_City:V'),(6,4,'vtiger_crmentity:smownerid:assigned_user_id:Accounts_Assigned_To:V'),(7,1,'vtiger_contactdetails:lastname:lastname:Contacts_Last_Name:V'),(7,2,'vtiger_contactdetails:firstname:firstname:Contacts_First_Name:V'),(7,3,'vtiger_contactdetails:title:title:Contacts_Title:V'),(7,4,'vtiger_contactdetails:accountid:account_id:Contacts_Account_Name:V'),(7,5,'vtiger_contactdetails:email:email:Contacts_Email:V'),(7,6,'vtiger_contactdetails:phone:phone:Contacts_Office_Phone:V'),(7,7,'vtiger_crmentity:smownerid:assigned_user_id:Contacts_Assigned_To:V'),(8,0,'vtiger_contactdetails:lastname:lastname:Contacts_Last_Name:V'),(8,1,'vtiger_contactdetails:firstname:firstname:Contacts_First_Name:V'),(8,2,'vtiger_contactaddress:mailingstreet:mailingstreet:Contacts_Mailing_Street:V'),(8,3,'vtiger_contactaddress:mailingcity:mailingcity:Contacts_Mailing_City:V'),(8,4,'vtiger_contactaddress:mailingstate:mailingstate:Contacts_Mailing_State:V'),(8,5,'vtiger_contactaddress:mailingzip:mailingzip:Contacts_Mailing_Zip:V'),(8,6,'vtiger_contactaddress:mailingcountry:mailingcountry:Contacts_Mailing_Country:V'),(9,0,'vtiger_contactdetails:lastname:lastname:Contacts_Last_Name:V'),(9,1,'vtiger_contactdetails:firstname:firstname:Contacts_First_Name:V'),(9,2,'vtiger_contactdetails:title:title:Contacts_Title:V'),(9,3,'vtiger_contactdetails:accountid:account_id:Contacts_Account_Name:I'),(9,4,'vtiger_contactdetails:email:email:Contacts_Email:E'),(9,5,'vtiger_contactsubdetails:otherphone:otherphone:Contacts_Phone:V'),(9,6,'vtiger_crmentity:smownerid:assigned_user_id:Contacts_Assigned_To:V'),(10,1,'vtiger_potential:potentialname:potentialname:Potentials_Potential_Name:V'),(10,2,'vtiger_potential:related_to:related_to:Potentials_Related_To:V'),(10,3,'vtiger_potential:sales_stage:sales_stage:Potentials_Sales_Stage:V'),(10,4,'vtiger_potential:leadsource:leadsource:Potentials_Lead_Source:V'),(10,5,'vtiger_potential:closingdate:closingdate:Potentials_Expected_Close_Date:D'),(10,6,'vtiger_potential:amount:amount:Potentials_Amount:N'),(10,7,'vtiger_crmentity:smownerid:assigned_user_id:Potentials_Assigned_To:V'),(10,8,'vtiger_potential:contact_id:contact_id:Potentials_Contact_Name:V'),(11,0,'vtiger_potential:potentialname:potentialname:Potentials_Potential_Name:V'),(11,1,'vtiger_potential:related_to:related_to:Potentials_Related_To:V'),(11,2,'vtiger_potential:amount:amount:Potentials_Amount:N'),(11,3,'vtiger_potential:closingdate:closingdate:Potentials_Expected_Close_Date:D'),(11,4,'vtiger_crmentity:smownerid:assigned_user_id:Potentials_Assigned_To:V'),(11,5,'vtiger_potential:contact_id:contact_id:Potentials_Contact_Name:V'),(12,0,'vtiger_potential:potentialname:potentialname:Potentials_Potential_Name:V'),(12,1,'vtiger_potential:related_to:related_to:Potentials_Related_To:V'),(12,2,'vtiger_potential:amount:amount:Potentials_Amount:N'),(12,3,'vtiger_potential:leadsource:leadsource:Potentials_Lead_Source:V'),(12,4,'vtiger_potential:closingdate:closingdate:Potentials_Expected_Close_Date:D'),(12,5,'vtiger_crmentity:smownerid:assigned_user_id:Potentials_Assigned_To:V'),(12,6,'vtiger_potential:contact_id:contact_id:Potentials_Contact_Name:V'),(13,1,'vtiger_troubletickets:title:ticket_title:HelpDesk_Title:V'),(13,2,'vtiger_troubletickets:parent_id:parent_id:HelpDesk_Related_To:V'),(13,3,'vtiger_troubletickets:status:ticketstatus:HelpDesk_Status:V'),(13,4,'vtiger_troubletickets:priority:ticketpriorities:HelpDesk_Priority:V'),(13,5,'vtiger_crmentity:smownerid:assigned_user_id:HelpDesk_Assigned_To:V'),(13,6,'vtiger_troubletickets:contact_id:contact_id:HelpDesk_Contact_Name:V'),(14,0,'vtiger_troubletickets:title:ticket_title:HelpDesk_Title:V'),(14,1,'vtiger_troubletickets:parent_id:parent_id:HelpDesk_Related_To:I'),(14,2,'vtiger_troubletickets:priority:ticketpriorities:HelpDesk_Priority:V'),(14,3,'vtiger_troubletickets:product_id:product_id:HelpDesk_Product_Name:I'),(14,4,'vtiger_crmentity:smownerid:assigned_user_id:HelpDesk_Assigned_To:V'),(14,5,'vtiger_troubletickets:contact_id:contact_id:HelpDesk_Contact_Name:V'),(15,0,'vtiger_troubletickets:title:ticket_title:HelpDesk_Title:V'),(15,1,'vtiger_troubletickets:parent_id:parent_id:HelpDesk_Related_To:I'),(15,2,'vtiger_troubletickets:status:ticketstatus:HelpDesk_Status:V'),(15,3,'vtiger_troubletickets:product_id:product_id:HelpDesk_Product_Name:I'),(15,4,'vtiger_crmentity:smownerid:assigned_user_id:HelpDesk_Assigned_To:V'),(15,5,'vtiger_troubletickets:contact_id:contact_id:HelpDesk_Contact_Name:V'),(16,1,'vtiger_quotes:subject:subject:Quotes_Subject:V'),(16,2,'vtiger_quotes:quotestage:quotestage:Quotes_Quote_Stage:V'),(16,3,'vtiger_quotes:potentialid:potential_id:Quotes_Potential_Name:V'),(16,4,'vtiger_quotes:accountid:account_id:Quotes_Account_Name:V'),(16,5,'vtiger_quotes:total:hdnGrandTotal:Quotes_Total:N'),(16,6,'vtiger_crmentity:smownerid:assigned_user_id:Quotes_Assigned_To:V'),(17,0,'vtiger_quotes:subject:subject:Quotes_Subject:V'),(17,1,'vtiger_quotes:quotestage:quotestage:Quotes_Quote_Stage:V'),(17,2,'vtiger_quotes:potentialid:potential_id:Quotes_Potential_Name:I'),(17,3,'vtiger_quotes:accountid:account_id:Quotes_Account_Name:I'),(17,4,'vtiger_quotes:validtill:validtill:Quotes_Valid_Till:D'),(17,5,'vtiger_crmentity:smownerid:assigned_user_id:Quotes_Assigned_To:V'),(18,0,'vtiger_quotes:subject:subject:Quotes_Subject:V'),(18,1,'vtiger_quotes:potentialid:potential_id:Quotes_Potential_Name:I'),(18,2,'vtiger_quotes:accountid:account_id:Quotes_Account_Name:I'),(18,3,'vtiger_quotes:validtill:validtill:Quotes_Valid_Till:D'),(18,4,'vtiger_crmentity:smownerid:assigned_user_id:Quotes_Assigned_To:V'),(19,0,'vtiger_activity:status:taskstatus:Calendar_Status:V'),(19,1,'vtiger_activity:activitytype:activitytype:Calendar_Activity_Type:V'),(19,2,'vtiger_activity:subject:subject:Calendar_Subject:V'),(19,3,'vtiger_seactivityrel:crmid:parent_id:Calendar_Related_to:V'),(19,4,'vtiger_activity:date_start:date_start:Calendar_Start_Date_&_Time:DT'),(19,5,'vtiger_activity:due_date:due_date:Calendar_Due_Date:D'),(19,6,'vtiger_crmentity:smownerid:assigned_user_id:Calendar_Assigned_To:V'),(20,0,'vtiger_activity:subject:subject:Emails_Subject:V'),(20,1,'vtiger_emaildetails:to_email:saved_toid:Emails_To:V'),(20,2,'vtiger_activity:date_start:date_start:Emails_Date_Sent:D'),(21,1,'vtiger_invoice:subject:subject:Invoice_Subject:V'),(21,2,'vtiger_invoice:salesorderid:salesorder_id:Invoice_Sales_Order:V'),(21,3,'vtiger_invoice:invoicestatus:invoicestatus:Invoice_Status:V'),(21,4,'vtiger_invoice:total:hdnGrandTotal:Invoice_Total:N'),(21,5,'vtiger_crmentity:smownerid:assigned_user_id:Invoice_Assigned_To:V'),(22,1,'vtiger_notes:title:notes_title:Documents_Title:V'),(22,2,'vtiger_notes:filename:filename:Documents_File_Name:V'),(22,3,'vtiger_crmentity:modifiedtime:modifiedtime:Documents_Modified_Time:DT'),(22,4,'vtiger_crmentity:smownerid:assigned_user_id:Documents_Assigned_To:V'),(23,1,'vtiger_pricebook:bookname:bookname:PriceBooks_Price_Book_Name:V'),(23,2,'vtiger_pricebook:active:active:PriceBooks_Active:C'),(23,3,'vtiger_pricebook:currency_id:currency_id:PriceBooks_Currency:V'),(24,1,'vtiger_products:productname:productname:Products_Product_Name:V'),(24,2,'vtiger_products:productcode:productcode:Products_Part_Number:V'),(24,3,'vtiger_products:commissionrate:commissionrate:Products_Commission_Rate:N'),(24,4,'vtiger_products:qtyinstock:qtyinstock:Products_Quantity_In_Stock:V'),(24,5,'vtiger_products:qty_per_unit:qty_per_unit:Products_Qty/Unit:N'),(24,6,'vtiger_products:unit_price:unit_price:Products_Unit_Price:N'),(25,1,'vtiger_purchaseorder:subject:subject:PurchaseOrder_Subject:V'),(25,2,'vtiger_purchaseorder:vendorid:vendor_id:PurchaseOrder_Vendor_Name:V'),(25,3,'vtiger_purchaseorder:tracking_no:tracking_no:PurchaseOrder_Tracking_Number:V'),(25,4,'vtiger_purchaseorder:total:hdnGrandTotal:PurchaseOrder_Total:N'),(25,5,'vtiger_crmentity:smownerid:assigned_user_id:PurchaseOrder_Assigned_To:V'),(26,1,'vtiger_salesorder:subject:subject:SalesOrder_Subject:V'),(26,2,'vtiger_salesorder:accountid:account_id:SalesOrder_Account_Name:V'),(26,3,'vtiger_salesorder:quoteid:quote_id:SalesOrder_Quote_Name:V'),(26,4,'vtiger_salesorder:total:hdnGrandTotal:SalesOrder_Total:N'),(26,5,'vtiger_crmentity:smownerid:assigned_user_id:SalesOrder_Assigned_To:V'),(27,1,'vtiger_vendor:vendorname:vendorname:Vendors_Vendor_Name:V'),(27,2,'vtiger_vendor:phone:phone:Vendors_Phone:V'),(27,3,'vtiger_vendor:email:email:Vendors_Email:V'),(27,4,'vtiger_vendor:category:category:Vendors_Category:V'),(27,5,'vtiger_crmentity:smownerid:assigned_user_id:Vendors_Assigned_To:V'),(28,1,'vtiger_faq:question:question:Faq_Question:V'),(28,2,'vtiger_faq:category:faqcategories:Faq_Category:V'),(28,3,'vtiger_faq:product_id:product_id:Faq_Product_Name:V'),(28,4,'vtiger_crmentity:createdtime:createdtime:Faq_Created_Time:DT'),(28,5,'vtiger_crmentity:modifiedtime:modifiedtime:Faq_Modified_Time:DT'),(29,1,'vtiger_campaign:campaignname:campaignname:Campaigns_Campaign_Name:V'),(29,2,'vtiger_campaign:campaigntype:campaigntype:Campaigns_Campaign_Type:V'),(29,3,'vtiger_campaign:campaignstatus:campaignstatus:Campaigns_Campaign_Status:V'),(29,4,'vtiger_campaign:expectedrevenue:expectedrevenue:Campaigns_Expected_Revenue:N'),(29,5,'vtiger_campaign:closingdate:closingdate:Campaigns_Expected_Close_Date:D'),(29,6,'vtiger_crmentity:smownerid:assigned_user_id:Campaigns_Assigned_To:V'),(30,0,'subject:subject:subject:Subject:V'),(30,1,'from:fromname:fromname:From:N'),(30,2,'to:tpname:toname:To:N'),(30,3,'body:body:body:Body:V'),(31,0,'vtiger_faq:question:question:Faq_Question:V'),(31,1,'vtiger_faq:status:faqstatus:Faq_Status:V'),(31,2,'vtiger_faq:product_id:product_id:Faq_Product_Name:I'),(31,3,'vtiger_faq:category:faqcategories:Faq_Category:V'),(31,4,'vtiger_crmentity:createdtime:createdtime:Faq_Created_Time:DT'),(32,0,'vtiger_faq:question:question:Faq_Question:V'),(32,1,'vtiger_faq:answer:faq_answer:Faq_Answer:V'),(32,2,'vtiger_faq:status:faqstatus:Faq_Status:V'),(32,3,'vtiger_faq:product_id:product_id:Faq_Product_Name:I'),(32,4,'vtiger_faq:category:faqcategories:Faq_Category:V'),(32,5,'vtiger_crmentity:createdtime:createdtime:Faq_Created_Time:DT'),(33,0,'vtiger_purchaseorder:subject:subject:PurchaseOrder_Subject:V'),(33,1,'vtiger_purchaseorder:postatus:postatus:PurchaseOrder_Status:V'),(33,2,'vtiger_purchaseorder:vendorid:vendor_id:PurchaseOrder_Vendor_Name:I'),(33,3,'vtiger_crmentity:smownerid:assigned_user_id:PurchaseOrder_Assigned_To:V'),(33,4,'vtiger_purchaseorder:duedate:duedate:PurchaseOrder_Due_Date:V'),(34,0,'vtiger_purchaseorder:subject:subject:PurchaseOrder_Subject:V'),(34,1,'vtiger_purchaseorder:vendorid:vendor_id:PurchaseOrder_Vendor_Name:I'),(34,2,'vtiger_crmentity:smownerid:assigned_user_id:PurchaseOrder_Assigned_To:V'),(34,3,'vtiger_purchaseorder:postatus:postatus:PurchaseOrder_Status:V'),(34,4,'vtiger_purchaseorder:carrier:carrier:PurchaseOrder_Carrier:V'),(34,5,'vtiger_poshipads:ship_street:ship_street:PurchaseOrder_Shipping_Address:V'),(35,0,'vtiger_invoice:invoice_no:invoice_no:Invoice_Invoice_No:V'),(35,1,'vtiger_invoice:subject:subject:Invoice_Subject:V'),(35,2,'vtiger_invoice:accountid:account_id:Invoice_Account_Name:I'),(35,3,'vtiger_invoice:salesorderid:salesorder_id:Invoice_Sales_Order:I'),(35,4,'vtiger_invoice:invoicestatus:invoicestatus:Invoice_Status:V'),(35,5,'vtiger_crmentity:smownerid:assigned_user_id:Invoice_Assigned_To:V'),(35,6,'vtiger_crmentity:createdtime:createdtime:Invoice_Created_Time:DT'),(36,0,'vtiger_invoice:invoice_no:invoice_no:Invoice_Invoice_No:V'),(36,1,'vtiger_invoice:subject:subject:Invoice_Subject:V'),(36,2,'vtiger_invoice:accountid:account_id:Invoice_Account_Name:I'),(36,3,'vtiger_invoice:salesorderid:salesorder_id:Invoice_Sales_Order:I'),(36,4,'vtiger_invoice:invoicestatus:invoicestatus:Invoice_Status:V'),(36,5,'vtiger_invoiceshipads:ship_street:ship_street:Invoice_Shipping_Address:V'),(36,6,'vtiger_crmentity:smownerid:assigned_user_id:Invoice_Assigned_To:V'),(37,0,'vtiger_salesorder:subject:subject:SalesOrder_Subject:V'),(37,1,'vtiger_salesorder:accountid:account_id:SalesOrder_Account_Name:I'),(37,2,'vtiger_salesorder:sostatus:sostatus:SalesOrder_Status:V'),(37,3,'vtiger_crmentity:smownerid:assigned_user_id:SalesOrder_Assigned_To:V'),(37,4,'vtiger_soshipads:ship_street:ship_street:SalesOrder_Shipping_Address:V'),(37,5,'vtiger_salesorder:carrier:carrier:SalesOrder_Carrier:V'),(38,1,'vtiger_servicecontracts:subject:subject:ServiceContracts_Subject:V'),(38,2,'vtiger_servicecontracts:sc_related_to:sc_related_to:ServiceContracts_Related_to:V'),(38,3,'vtiger_crmentity:smownerid:assigned_user_id:ServiceContracts_Assigned_To:V'),(38,4,'vtiger_servicecontracts:start_date:start_date:ServiceContracts_Start_Date:D'),(38,5,'vtiger_servicecontracts:due_date:due_date:ServiceContracts_Due_date:D'),(38,7,'vtiger_servicecontracts:progress:progress:ServiceContracts_Progress:N'),(38,8,'vtiger_servicecontracts:contract_status:contract_status:ServiceContracts_Status:V'),(39,1,'vtiger_service:servicename:servicename:Services_Service_Name:V'),(39,2,'vtiger_service:service_usageunit:service_usageunit:Services_Usage_Unit:V'),(39,3,'vtiger_service:unit_price:unit_price:Services_Price:N'),(39,4,'vtiger_service:qty_per_unit:qty_per_unit:Services_No_of_Units:N'),(39,5,'vtiger_service:servicecategory:servicecategory:Services_Service_Category:V'),(39,6,'vtiger_crmentity:smownerid:assigned_user_id:Services_Owner:I'),(40,0,'vtiger_modcomments:commentcontent:commentcontent:ModComments_Comment:V'),(40,1,'vtiger_modcomments:related_to:related_to:ModComments_Related_To:V'),(40,2,'vtiger_crmentity:modifiedtime:modifiedtime:ModComments_Modified_Time:DT'),(40,3,'vtiger_crmentity:smownerid:assigned_user_id:ModComments_Assigned_To:V'),(44,0,'vtiger_smsnotifier:message:message:SMSNotifier_message:V'),(44,2,'vtiger_crmentity:smownerid:assigned_user_id:SMSNotifier_Assigned_To:V'),(44,3,'vtiger_crmentity:createdtime:createdtime:SMSNotifier_Created_Time:DT'),(44,4,'vtiger_crmentity:modifiedtime:modifiedtime:SMSNotifier_Modified_Time:DT'),(45,1,'vtiger_assets:assetname:assetname:Assets_Asset_Name:V'),(45,2,'vtiger_assets:account:account:Assets_Customer_Name:V'),(45,3,'vtiger_assets:product:product:Assets_Product_Name:V'),(49,0,'vtiger_projectmilestone:projectmilestonename:projectmilestonename:ProjectMilestone_Project_Milestone_Name:V'),(49,1,'vtiger_projectmilestone:projectmilestonedate:projectmilestonedate:ProjectMilestone_Milestone_Date:D'),(49,3,'vtiger_crmentity:description:description:ProjectMilestone_description:V'),(49,4,'vtiger_crmentity:createdtime:createdtime:ProjectMilestone_Created_Time:DT'),(49,5,'vtiger_crmentity:modifiedtime:modifiedtime:ProjectMilestone_Modified_Time:DT'),(50,2,'vtiger_projecttask:projecttaskname:projecttaskname:ProjectTask_Project_Task_Name:V'),(50,3,'vtiger_projecttask:projectid:projectid:ProjectTask_Related_to:V'),(50,4,'vtiger_projecttask:projecttaskpriority:projecttaskpriority:ProjectTask_Priority:V'),(50,5,'vtiger_projecttask:projecttaskprogress:projecttaskprogress:ProjectTask_Progress:V'),(50,6,'vtiger_projecttask:projecttaskhours:projecttaskhours:ProjectTask_Worked_Hours:V'),(50,7,'vtiger_projecttask:startdate:startdate:ProjectTask_Start_Date:D'),(50,8,'vtiger_projecttask:enddate:enddate:ProjectTask_End_Date:D'),(50,9,'vtiger_crmentity:smownerid:assigned_user_id:ProjectTask_Assigned_To:V'),(51,0,'vtiger_project:projectname:projectname:Project_Project_Name:V'),(51,1,'vtiger_project:linktoaccountscontacts:linktoaccountscontacts:Project_Related_to:V'),(51,2,'vtiger_project:startdate:startdate:Project_Start_Date:D'),(51,3,'vtiger_project:targetenddate:targetenddate:Project_Target_End_Date:D'),(51,4,'vtiger_project:actualenddate:actualenddate:Project_Actual_End_Date:D'),(51,5,'vtiger_project:targetbudget:targetbudget:Project_Target_Budget:V'),(51,6,'vtiger_project:progress:progress:Project_Progress:V'),(51,7,'vtiger_project:projectstatus:projectstatus:Project_Status:V'),(51,8,'vtiger_crmentity:smownerid:assigned_user_id:Project_Assigned_To:V'),(52,1,'vtiger_dailyreports:dailyreportsstatus:dailyreportsstatus:Dailyreports_LBL_STATUS:V'),(52,2,'vtiger_dailyreports:reportsdate:ReportsDate:Dailyreports_LBL_SUBMISSION_DATE:D'),(52,3,'vtiger_crmentity:smownerid:assigned_user_id:Dailyreports_Assigned_To:V'),(52,4,'vtiger_dailyreports:reports_to_id:reports_to_id:Dailyreports_LBL_WHERE_TO_SUBMIT:V');
/*!40000 ALTER TABLE `vtiger_cvcolumnlist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_cvstdfilter`
--

DROP TABLE IF EXISTS `vtiger_cvstdfilter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_cvstdfilter` (
  `cvid` int NOT NULL,
  `columnname` varchar(250) COLLATE utf8mb4_general_ci DEFAULT '',
  `stdfilter` varchar(250) COLLATE utf8mb4_general_ci DEFAULT '',
  `startdate` date DEFAULT NULL,
  `enddate` date DEFAULT NULL,
  KEY `cvstdfilter_cvid_idx` (`cvid`),
  CONSTRAINT `fk_1_vtiger_cvstdfilter` FOREIGN KEY (`cvid`) REFERENCES `vtiger_customview` (`cvid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_cvstdfilter`
--

LOCK TABLES `vtiger_cvstdfilter` WRITE;
/*!40000 ALTER TABLE `vtiger_cvstdfilter` DISABLE KEYS */;
INSERT INTO `vtiger_cvstdfilter` VALUES (3,'vtiger_crmentity:modifiedtime:modifiedtime:Leads_Modified_Time','thismonth','2005-06-01','2005-06-30'),(6,'vtiger_crmentity:createdtime:createdtime:Accounts_Created_Time','thisweek','2005-06-19','2005-06-25'),(9,'vtiger_contactsubdetails:birthday:birthday:Contacts_Birthdate','today','2005-06-25','2005-06-25');
/*!40000 ALTER TABLE `vtiger_cvstdfilter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_dailyreports`
--

DROP TABLE IF EXISTS `vtiger_dailyreports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_dailyreports` (
  `dailyreportsid` int NOT NULL,
  `dailyreportsname` varchar(100) DEFAULT NULL,
  `reportsterm` varchar(100) DEFAULT NULL,
  `reportsdate` date DEFAULT NULL,
  `dailyreportsstatus` varchar(100) DEFAULT NULL,
  `reports_to_id` int DEFAULT NULL,
  `dailyreportscomment` longtext,
  `tags` varchar(1) DEFAULT NULL,
  `smcreatorid` int DEFAULT NULL,
  `smownerid` int DEFAULT NULL,
  `modifiedby` int DEFAULT NULL,
  `description` longtext,
  `createdtime` datetime DEFAULT NULL,
  `modifiedtime` datetime DEFAULT NULL,
  `viewedtime` datetime DEFAULT NULL,
  `deleted` int DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `smgroupid` int DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`dailyreportsid`),
  KEY `reports_to_id` (`reports_to_id`),
  KEY `idx_info` (`deleted`,`modifiedtime`),
  KEY `idx_label` (`deleted`,`label`),
  KEY `idx_owner` (`deleted`,`smownerid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_dailyreports`
--

LOCK TABLES `vtiger_dailyreports` WRITE;
/*!40000 ALTER TABLE `vtiger_dailyreports` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_dailyreports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_dailyreportscf`
--

DROP TABLE IF EXISTS `vtiger_dailyreportscf`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_dailyreportscf` (
  `dailyreportsid` int NOT NULL,
  PRIMARY KEY (`dailyreportsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_dailyreportscf`
--

LOCK TABLES `vtiger_dailyreportscf` WRITE;
/*!40000 ALTER TABLE `vtiger_dailyreportscf` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_dailyreportscf` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_dailyreportsstatus`
--

DROP TABLE IF EXISTS `vtiger_dailyreportsstatus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_dailyreportsstatus` (
  `dailyreportsstatusid` int NOT NULL AUTO_INCREMENT,
  `dailyreportsstatus` varchar(200) NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT '0',
  `color` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`dailyreportsstatusid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_dailyreportsstatus`
--

LOCK TABLES `vtiger_dailyreportsstatus` WRITE;
/*!40000 ALTER TABLE `vtiger_dailyreportsstatus` DISABLE KEYS */;
INSERT INTO `vtiger_dailyreportsstatus` VALUES (1,'LBL_SUBMITTING',1,307,1,NULL),(2,'LBL_APPROVED',1,308,2,NULL);
/*!40000 ALTER TABLE `vtiger_dailyreportsstatus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_dailyreportsstatus_seq`
--

DROP TABLE IF EXISTS `vtiger_dailyreportsstatus_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_dailyreportsstatus_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_dailyreportsstatus_seq`
--

LOCK TABLES `vtiger_dailyreportsstatus_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_dailyreportsstatus_seq` DISABLE KEYS */;
INSERT INTO `vtiger_dailyreportsstatus_seq` VALUES (2);
/*!40000 ALTER TABLE `vtiger_dailyreportsstatus_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_dashboard_tabs`
--

DROP TABLE IF EXISTS `vtiger_dashboard_tabs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_dashboard_tabs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tabname` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `isdefault` int DEFAULT '0',
  `sequence` int DEFAULT '2',
  `appname` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `modulename` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `userid` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tabname` (`tabname`,`userid`),
  KEY `vtiger_dashboard_tabs_ibfk_1` (`userid`),
  CONSTRAINT `vtiger_dashboard_tabs_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `vtiger_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_dashboard_tabs`
--

LOCK TABLES `vtiger_dashboard_tabs` WRITE;
/*!40000 ALTER TABLE `vtiger_dashboard_tabs` DISABLE KEYS */;
INSERT INTO `vtiger_dashboard_tabs` VALUES (1,'Default',0,2,NULL,NULL,1),(2,'My Dashboard',1,1,'','',1);
/*!40000 ALTER TABLE `vtiger_dashboard_tabs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_datashare_grp2grp`
--

DROP TABLE IF EXISTS `vtiger_datashare_grp2grp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_datashare_grp2grp` (
  `shareid` int NOT NULL,
  `share_groupid` int DEFAULT NULL,
  `to_groupid` int DEFAULT NULL,
  `permission` int DEFAULT NULL,
  PRIMARY KEY (`shareid`),
  KEY `datashare_grp2grp_share_groupid_idx` (`share_groupid`),
  KEY `datashare_grp2grp_to_groupid_idx` (`to_groupid`),
  CONSTRAINT `fk_3_vtiger_datashare_grp2grp` FOREIGN KEY (`to_groupid`) REFERENCES `vtiger_groups` (`groupid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_datashare_grp2grp`
--

LOCK TABLES `vtiger_datashare_grp2grp` WRITE;
/*!40000 ALTER TABLE `vtiger_datashare_grp2grp` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_datashare_grp2grp` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_datashare_grp2role`
--

DROP TABLE IF EXISTS `vtiger_datashare_grp2role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_datashare_grp2role` (
  `shareid` int NOT NULL,
  `share_groupid` int DEFAULT NULL,
  `to_roleid` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `permission` int DEFAULT NULL,
  PRIMARY KEY (`shareid`),
  KEY `idx_datashare_grp2role_share_groupid` (`share_groupid`),
  KEY `idx_datashare_grp2role_to_roleid` (`to_roleid`),
  CONSTRAINT `fk_3_vtiger_datashare_grp2role` FOREIGN KEY (`to_roleid`) REFERENCES `vtiger_role` (`roleid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_datashare_grp2role`
--

LOCK TABLES `vtiger_datashare_grp2role` WRITE;
/*!40000 ALTER TABLE `vtiger_datashare_grp2role` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_datashare_grp2role` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_datashare_grp2rs`
--

DROP TABLE IF EXISTS `vtiger_datashare_grp2rs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_datashare_grp2rs` (
  `shareid` int NOT NULL,
  `share_groupid` int DEFAULT NULL,
  `to_roleandsubid` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `permission` int DEFAULT NULL,
  PRIMARY KEY (`shareid`),
  KEY `datashare_grp2rs_share_groupid_idx` (`share_groupid`),
  KEY `datashare_grp2rs_to_roleandsubid_idx` (`to_roleandsubid`),
  CONSTRAINT `fk_3_vtiger_datashare_grp2rs` FOREIGN KEY (`to_roleandsubid`) REFERENCES `vtiger_role` (`roleid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_datashare_grp2rs`
--

LOCK TABLES `vtiger_datashare_grp2rs` WRITE;
/*!40000 ALTER TABLE `vtiger_datashare_grp2rs` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_datashare_grp2rs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_datashare_module_rel`
--

DROP TABLE IF EXISTS `vtiger_datashare_module_rel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_datashare_module_rel` (
  `shareid` int NOT NULL,
  `tabid` int NOT NULL,
  `relationtype` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`shareid`),
  KEY `idx_datashare_module_rel_tabid` (`tabid`),
  CONSTRAINT `fk_1_vtiger_datashare_module_rel` FOREIGN KEY (`tabid`) REFERENCES `vtiger_tab` (`tabid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_datashare_module_rel`
--

LOCK TABLES `vtiger_datashare_module_rel` WRITE;
/*!40000 ALTER TABLE `vtiger_datashare_module_rel` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_datashare_module_rel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_datashare_relatedmodule_permission`
--

DROP TABLE IF EXISTS `vtiger_datashare_relatedmodule_permission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_datashare_relatedmodule_permission` (
  `shareid` int NOT NULL,
  `datashare_relatedmodule_id` int NOT NULL,
  `permission` int DEFAULT NULL,
  PRIMARY KEY (`shareid`,`datashare_relatedmodule_id`),
  KEY `datashare_relatedmodule_permission_shareid_permissions_idx` (`shareid`,`permission`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_datashare_relatedmodule_permission`
--

LOCK TABLES `vtiger_datashare_relatedmodule_permission` WRITE;
/*!40000 ALTER TABLE `vtiger_datashare_relatedmodule_permission` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_datashare_relatedmodule_permission` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_datashare_relatedmodules`
--

DROP TABLE IF EXISTS `vtiger_datashare_relatedmodules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_datashare_relatedmodules` (
  `datashare_relatedmodule_id` int NOT NULL,
  `tabid` int DEFAULT NULL,
  `relatedto_tabid` int DEFAULT NULL,
  PRIMARY KEY (`datashare_relatedmodule_id`),
  KEY `datashare_relatedmodules_tabid_idx` (`tabid`),
  KEY `datashare_relatedmodules_relatedto_tabid_idx` (`relatedto_tabid`),
  CONSTRAINT `fk_2_vtiger_datashare_relatedmodules` FOREIGN KEY (`tabid`) REFERENCES `vtiger_tab` (`tabid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_datashare_relatedmodules`
--

LOCK TABLES `vtiger_datashare_relatedmodules` WRITE;
/*!40000 ALTER TABLE `vtiger_datashare_relatedmodules` DISABLE KEYS */;
INSERT INTO `vtiger_datashare_relatedmodules` VALUES (1,6,2),(2,6,13),(3,6,20),(4,6,22),(5,6,23),(6,2,20),(7,2,22),(8,20,22),(9,22,23);
/*!40000 ALTER TABLE `vtiger_datashare_relatedmodules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_datashare_relatedmodules_seq`
--

DROP TABLE IF EXISTS `vtiger_datashare_relatedmodules_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_datashare_relatedmodules_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_datashare_relatedmodules_seq`
--

LOCK TABLES `vtiger_datashare_relatedmodules_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_datashare_relatedmodules_seq` DISABLE KEYS */;
INSERT INTO `vtiger_datashare_relatedmodules_seq` VALUES (9);
/*!40000 ALTER TABLE `vtiger_datashare_relatedmodules_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_datashare_role2group`
--

DROP TABLE IF EXISTS `vtiger_datashare_role2group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_datashare_role2group` (
  `shareid` int NOT NULL,
  `share_roleid` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `to_groupid` int DEFAULT NULL,
  `permission` int DEFAULT NULL,
  PRIMARY KEY (`shareid`),
  KEY `idx_datashare_role2group_share_roleid` (`share_roleid`),
  KEY `idx_datashare_role2group_to_groupid` (`to_groupid`),
  CONSTRAINT `fk_3_vtiger_datashare_role2group` FOREIGN KEY (`share_roleid`) REFERENCES `vtiger_role` (`roleid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_datashare_role2group`
--

LOCK TABLES `vtiger_datashare_role2group` WRITE;
/*!40000 ALTER TABLE `vtiger_datashare_role2group` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_datashare_role2group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_datashare_role2role`
--

DROP TABLE IF EXISTS `vtiger_datashare_role2role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_datashare_role2role` (
  `shareid` int NOT NULL,
  `share_roleid` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `to_roleid` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `permission` int DEFAULT NULL,
  PRIMARY KEY (`shareid`),
  KEY `datashare_role2role_share_roleid_idx` (`share_roleid`),
  KEY `datashare_role2role_to_roleid_idx` (`to_roleid`),
  CONSTRAINT `fk_3_vtiger_datashare_role2role` FOREIGN KEY (`to_roleid`) REFERENCES `vtiger_role` (`roleid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_datashare_role2role`
--

LOCK TABLES `vtiger_datashare_role2role` WRITE;
/*!40000 ALTER TABLE `vtiger_datashare_role2role` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_datashare_role2role` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_datashare_role2rs`
--

DROP TABLE IF EXISTS `vtiger_datashare_role2rs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_datashare_role2rs` (
  `shareid` int NOT NULL,
  `share_roleid` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `to_roleandsubid` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `permission` int DEFAULT NULL,
  PRIMARY KEY (`shareid`),
  KEY `datashare_role2s_share_roleid_idx` (`share_roleid`),
  KEY `datashare_role2s_to_roleandsubid_idx` (`to_roleandsubid`),
  CONSTRAINT `fk_3_vtiger_datashare_role2rs` FOREIGN KEY (`to_roleandsubid`) REFERENCES `vtiger_role` (`roleid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_datashare_role2rs`
--

LOCK TABLES `vtiger_datashare_role2rs` WRITE;
/*!40000 ALTER TABLE `vtiger_datashare_role2rs` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_datashare_role2rs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_datashare_rs2grp`
--

DROP TABLE IF EXISTS `vtiger_datashare_rs2grp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_datashare_rs2grp` (
  `shareid` int NOT NULL,
  `share_roleandsubid` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `to_groupid` int DEFAULT NULL,
  `permission` int DEFAULT NULL,
  PRIMARY KEY (`shareid`),
  KEY `datashare_rs2grp_share_roleandsubid_idx` (`share_roleandsubid`),
  KEY `datashare_rs2grp_to_groupid_idx` (`to_groupid`),
  CONSTRAINT `fk_3_vtiger_datashare_rs2grp` FOREIGN KEY (`share_roleandsubid`) REFERENCES `vtiger_role` (`roleid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_datashare_rs2grp`
--

LOCK TABLES `vtiger_datashare_rs2grp` WRITE;
/*!40000 ALTER TABLE `vtiger_datashare_rs2grp` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_datashare_rs2grp` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_datashare_rs2role`
--

DROP TABLE IF EXISTS `vtiger_datashare_rs2role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_datashare_rs2role` (
  `shareid` int NOT NULL,
  `share_roleandsubid` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `to_roleid` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `permission` int DEFAULT NULL,
  PRIMARY KEY (`shareid`),
  KEY `datashare_rs2role_share_roleandsubid_idx` (`share_roleandsubid`),
  KEY `datashare_rs2role_to_roleid_idx` (`to_roleid`),
  CONSTRAINT `fk_3_vtiger_datashare_rs2role` FOREIGN KEY (`to_roleid`) REFERENCES `vtiger_role` (`roleid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_datashare_rs2role`
--

LOCK TABLES `vtiger_datashare_rs2role` WRITE;
/*!40000 ALTER TABLE `vtiger_datashare_rs2role` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_datashare_rs2role` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_datashare_rs2rs`
--

DROP TABLE IF EXISTS `vtiger_datashare_rs2rs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_datashare_rs2rs` (
  `shareid` int NOT NULL,
  `share_roleandsubid` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `to_roleandsubid` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `permission` int DEFAULT NULL,
  PRIMARY KEY (`shareid`),
  KEY `datashare_rs2rs_share_roleandsubid_idx` (`share_roleandsubid`),
  KEY `idx_datashare_rs2rs_to_roleandsubid_idx` (`to_roleandsubid`),
  CONSTRAINT `fk_3_vtiger_datashare_rs2rs` FOREIGN KEY (`to_roleandsubid`) REFERENCES `vtiger_role` (`roleid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_datashare_rs2rs`
--

LOCK TABLES `vtiger_datashare_rs2rs` WRITE;
/*!40000 ALTER TABLE `vtiger_datashare_rs2rs` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_datashare_rs2rs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_date_format`
--

DROP TABLE IF EXISTS `vtiger_date_format`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_date_format` (
  `date_formatid` int NOT NULL AUTO_INCREMENT,
  `date_format` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `sortorderid` int NOT NULL DEFAULT '0',
  `presence` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`date_formatid`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_date_format`
--

LOCK TABLES `vtiger_date_format` WRITE;
/*!40000 ALTER TABLE `vtiger_date_format` DISABLE KEYS */;
INSERT INTO `vtiger_date_format` VALUES (1,'dd-mm-yyyy',0,1),(2,'mm-dd-yyyy',1,1),(3,'yyyy-mm-dd',2,1),(4,'dd.mm.yyyy',3,1),(5,'dd/mm/yyyy',4,1);
/*!40000 ALTER TABLE `vtiger_date_format` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_date_format_seq`
--

DROP TABLE IF EXISTS `vtiger_date_format_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_date_format_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_date_format_seq`
--

LOCK TABLES `vtiger_date_format_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_date_format_seq` DISABLE KEYS */;
INSERT INTO `vtiger_date_format_seq` VALUES (3);
/*!40000 ALTER TABLE `vtiger_date_format_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_dayoftheweek`
--

DROP TABLE IF EXISTS `vtiger_dayoftheweek`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_dayoftheweek` (
  `dayoftheweekid` int NOT NULL AUTO_INCREMENT,
  `dayoftheweek` varchar(200) NOT NULL,
  `sortorderid` int DEFAULT NULL,
  `presence` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`dayoftheweekid`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_dayoftheweek`
--

LOCK TABLES `vtiger_dayoftheweek` WRITE;
/*!40000 ALTER TABLE `vtiger_dayoftheweek` DISABLE KEYS */;
INSERT INTO `vtiger_dayoftheweek` VALUES (1,'Sunday',0,1),(2,'Monday',1,1),(3,'Tuesday',2,1),(4,'Wednesday',3,1),(5,'Thursday',4,1),(6,'Friday',5,1),(7,'Saturday',6,1);
/*!40000 ALTER TABLE `vtiger_dayoftheweek` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_dayoftheweek_seq`
--

DROP TABLE IF EXISTS `vtiger_dayoftheweek_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_dayoftheweek_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_dayoftheweek_seq`
--

LOCK TABLES `vtiger_dayoftheweek_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_dayoftheweek_seq` DISABLE KEYS */;
INSERT INTO `vtiger_dayoftheweek_seq` VALUES (7);
/*!40000 ALTER TABLE `vtiger_dayoftheweek_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_def_org_field`
--

DROP TABLE IF EXISTS `vtiger_def_org_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_def_org_field` (
  `tabid` int DEFAULT NULL,
  `fieldid` int NOT NULL,
  `visible` int DEFAULT NULL,
  `readonly` int DEFAULT NULL,
  PRIMARY KEY (`fieldid`),
  KEY `def_org_field_tabid_fieldid_idx` (`tabid`,`fieldid`),
  KEY `def_org_field_tabid_idx` (`tabid`),
  KEY `def_org_field_visible_fieldid_idx` (`visible`,`fieldid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_def_org_field`
--

LOCK TABLES `vtiger_def_org_field` WRITE;
/*!40000 ALTER TABLE `vtiger_def_org_field` DISABLE KEYS */;
INSERT INTO `vtiger_def_org_field` VALUES (6,1,0,0),(6,2,0,0),(6,3,0,0),(6,4,0,0),(6,5,0,0),(6,6,0,0),(6,7,0,0),(6,8,0,0),(6,9,0,0),(6,10,0,0),(6,11,0,0),(6,12,0,0),(6,13,0,0),(6,14,0,0),(6,15,0,0),(6,16,0,0),(6,17,0,0),(6,18,0,0),(6,19,0,0),(6,20,0,0),(6,21,0,0),(6,22,0,0),(6,23,0,0),(6,24,0,0),(6,25,0,0),(6,26,0,0),(6,27,0,0),(6,28,0,0),(6,29,0,0),(6,30,0,0),(6,31,0,0),(6,32,0,0),(6,33,0,0),(6,34,0,0),(6,35,0,0),(6,36,0,0),(7,37,0,0),(7,38,0,0),(7,39,0,0),(7,40,0,0),(7,41,0,0),(7,42,0,0),(7,43,0,0),(7,44,0,0),(7,45,0,0),(7,46,0,0),(7,47,0,0),(7,48,0,0),(7,49,0,0),(7,50,0,0),(7,51,0,0),(7,52,0,0),(7,53,0,0),(7,54,0,0),(7,55,0,0),(7,56,0,0),(7,57,0,0),(7,58,0,0),(7,59,0,0),(7,60,0,0),(7,61,0,0),(7,62,0,0),(7,63,0,0),(7,64,0,0),(7,65,0,0),(4,66,0,0),(4,67,0,0),(4,68,0,0),(4,69,0,0),(4,70,0,0),(4,71,0,0),(4,72,0,0),(4,73,0,0),(4,74,0,0),(4,75,0,0),(4,76,0,0),(4,77,0,0),(4,78,0,0),(4,79,0,0),(4,80,0,0),(4,81,0,0),(4,82,0,0),(4,83,0,0),(4,84,0,0),(4,85,0,0),(4,86,0,0),(4,87,0,0),(4,88,0,0),(4,89,0,0),(4,90,0,0),(4,91,0,0),(4,92,0,0),(4,93,0,0),(4,94,0,0),(4,95,0,0),(4,96,0,0),(4,97,0,0),(4,98,0,0),(4,99,0,0),(4,100,0,0),(4,101,0,0),(4,102,0,0),(4,103,0,0),(4,104,0,0),(4,105,0,0),(4,106,0,0),(4,107,0,0),(4,108,0,0),(4,109,0,0),(2,110,0,0),(2,111,0,0),(2,112,0,0),(2,113,0,0),(2,114,0,0),(2,115,0,0),(2,116,0,0),(2,117,0,0),(2,118,0,0),(2,119,0,0),(2,120,0,0),(2,121,0,0),(2,122,0,0),(2,123,0,0),(2,124,0,0),(2,125,0,0),(26,126,0,0),(26,127,0,0),(26,128,0,0),(26,129,0,0),(26,130,0,0),(26,131,0,0),(26,132,0,0),(26,133,0,0),(26,134,0,0),(26,135,0,0),(26,136,0,0),(26,137,0,0),(26,138,0,0),(26,139,0,0),(26,140,0,0),(26,141,0,0),(26,142,0,0),(26,143,0,0),(26,144,0,0),(26,145,0,0),(26,146,0,0),(26,147,0,0),(26,148,0,0),(26,149,0,0),(26,150,0,0),(4,151,0,0),(6,152,0,0),(7,153,0,0),(26,154,0,0),(13,155,0,0),(13,156,0,0),(13,157,0,0),(13,158,0,0),(13,159,0,0),(13,160,0,0),(13,161,0,0),(13,162,0,0),(13,163,0,0),(13,164,0,0),(13,165,0,0),(13,166,0,0),(13,167,0,0),(13,168,0,0),(13,169,0,0),(13,170,0,0),(13,171,0,0),(13,172,0,0),(13,173,0,0),(14,174,0,0),(14,175,0,0),(14,176,0,0),(14,177,0,0),(14,178,0,0),(14,179,0,0),(14,180,0,0),(14,181,0,0),(14,182,0,0),(14,183,0,0),(14,184,0,0),(14,185,0,0),(14,186,0,0),(14,187,0,0),(14,188,0,0),(14,189,0,0),(14,190,0,0),(14,191,0,0),(14,192,0,0),(14,193,0,0),(14,194,0,0),(14,195,0,0),(14,196,0,0),(14,197,0,0),(14,198,0,0),(14,199,0,0),(14,200,0,0),(14,201,0,0),(14,202,0,0),(14,203,0,0),(14,204,0,0),(8,205,0,0),(8,206,0,0),(8,207,0,0),(8,208,0,0),(8,209,0,0),(8,210,0,0),(8,211,0,0),(8,212,0,0),(8,213,0,0),(8,214,0,0),(8,215,0,0),(8,216,0,0),(8,217,0,0),(8,218,0,0),(8,219,0,0),(10,220,0,0),(10,221,0,0),(10,222,0,0),(10,223,0,0),(10,224,0,0),(10,225,0,0),(10,226,0,0),(10,227,0,0),(10,228,0,0),(10,229,0,0),(10,230,0,0),(10,231,0,0),(9,232,0,0),(9,233,0,0),(9,234,0,0),(9,235,0,0),(9,236,0,0),(9,237,0,0),(9,238,0,0),(9,239,0,0),(9,240,0,0),(9,241,0,0),(9,242,0,0),(9,243,0,0),(9,244,0,0),(9,245,0,0),(9,246,0,0),(9,247,0,0),(9,248,0,0),(9,249,0,0),(9,250,0,0),(9,251,0,0),(9,252,0,0),(9,253,0,0),(9,254,0,0),(9,255,0,0),(16,256,0,0),(16,257,0,0),(16,258,0,0),(16,259,0,0),(16,260,0,0),(16,261,0,0),(16,262,0,0),(16,263,0,0),(16,264,0,0),(16,265,0,0),(16,266,0,0),(16,267,0,0),(16,268,0,0),(16,269,0,0),(16,270,0,0),(16,271,0,0),(16,272,0,0),(16,273,0,0),(16,274,0,0),(16,275,0,0),(16,276,0,0),(16,277,0,0),(16,278,0,0),(15,279,0,0),(15,280,0,0),(15,281,0,0),(15,282,0,0),(15,283,0,0),(15,284,0,0),(15,285,0,0),(15,286,0,0),(15,287,0,0),(15,288,0,0),(18,289,0,0),(18,290,0,0),(18,291,0,0),(18,292,0,0),(18,293,0,0),(18,294,0,0),(18,295,0,0),(18,296,0,0),(18,297,0,0),(18,298,0,0),(18,299,0,0),(18,300,0,0),(18,301,0,0),(18,302,0,0),(18,303,0,0),(18,304,0,0),(18,305,0,0),(19,306,0,0),(19,307,0,0),(19,308,0,0),(19,309,0,0),(19,310,0,0),(19,311,0,0),(19,312,0,0),(19,313,0,0),(20,314,0,0),(20,315,0,0),(20,316,0,0),(20,317,0,0),(20,318,0,0),(20,319,0,0),(20,320,0,0),(20,321,0,0),(20,322,0,0),(20,323,0,0),(20,324,0,0),(20,325,0,0),(20,326,0,0),(20,327,0,0),(20,328,0,0),(20,329,0,0),(20,330,0,0),(20,331,0,0),(20,332,0,0),(20,333,0,0),(20,334,0,0),(20,335,0,0),(20,336,0,0),(20,337,0,0),(20,338,0,0),(20,339,0,0),(20,340,0,0),(20,341,0,0),(20,342,0,0),(20,343,0,0),(20,344,0,0),(20,345,0,0),(20,346,0,0),(20,347,0,0),(20,348,0,0),(20,349,0,0),(20,350,0,0),(21,351,0,0),(21,352,0,0),(21,353,0,0),(21,354,0,0),(21,355,0,0),(21,356,0,0),(21,357,0,0),(21,358,0,0),(21,359,0,0),(21,360,0,0),(21,361,0,0),(21,362,0,0),(21,363,0,0),(21,364,0,0),(21,365,0,0),(21,366,0,0),(21,367,0,0),(21,368,0,0),(21,369,0,0),(21,370,0,0),(21,371,0,0),(21,372,0,0),(21,373,0,0),(21,374,0,0),(21,375,0,0),(21,376,0,0),(21,377,0,0),(21,378,0,0),(21,379,0,0),(21,380,0,0),(21,381,0,0),(21,382,0,0),(21,383,0,0),(21,384,0,0),(21,385,0,0),(21,386,0,0),(21,387,0,0),(21,388,0,0),(22,389,0,0),(22,390,0,0),(22,391,0,0),(22,392,0,0),(22,393,0,0),(22,394,0,0),(22,395,0,0),(22,396,0,0),(22,397,0,0),(22,398,0,0),(22,399,0,0),(22,400,0,0),(22,401,0,0),(22,402,0,0),(22,403,0,0),(22,404,0,0),(22,405,0,0),(22,406,0,0),(22,407,0,0),(22,408,0,0),(22,409,0,0),(22,410,0,0),(22,411,0,0),(22,412,0,0),(22,413,0,0),(22,414,0,0),(22,415,0,0),(22,416,0,0),(22,417,0,0),(22,418,0,0),(22,419,0,0),(22,420,0,0),(22,421,0,0),(22,422,0,0),(22,423,0,0),(22,424,0,0),(22,425,0,0),(22,426,0,0),(22,427,0,0),(22,428,0,0),(22,429,0,0),(22,430,0,0),(22,431,0,0),(22,432,0,0),(22,433,0,0),(22,434,0,0),(22,435,0,0),(23,436,0,0),(23,437,0,0),(23,438,0,0),(23,439,0,0),(23,440,0,0),(23,441,0,0),(23,442,0,0),(23,443,0,0),(23,444,0,0),(23,445,0,0),(23,446,0,0),(23,447,0,0),(23,448,0,0),(23,449,0,0),(23,450,0,0),(23,451,0,0),(23,452,0,0),(23,453,0,0),(23,454,0,0),(23,455,0,0),(23,456,0,0),(23,457,0,0),(23,458,0,0),(23,459,0,0),(23,460,0,0),(23,461,0,0),(23,462,0,0),(23,463,0,0),(23,464,0,0),(23,465,0,0),(23,466,0,0),(23,467,0,0),(23,468,0,0),(23,469,0,0),(23,470,0,0),(23,471,0,0),(23,472,0,0),(23,473,0,0),(23,474,0,0),(29,475,0,0),(29,479,0,0),(29,480,0,0),(29,482,0,0),(29,489,0,0),(29,490,0,0),(29,491,0,0),(29,492,0,0),(29,493,0,0),(29,495,0,0),(29,496,0,0),(29,497,0,0),(29,498,0,0),(29,499,0,0),(29,504,0,0),(29,505,0,0),(29,506,0,0),(29,507,0,0),(29,515,0,0),(10,520,0,0),(10,521,0,0),(10,522,0,0),(10,523,0,0),(10,524,0,0),(10,525,0,0),(34,526,0,0),(34,527,0,0),(34,528,0,0),(34,529,0,0),(34,530,0,0),(34,531,0,0),(34,532,0,0),(34,533,0,0),(34,534,0,0),(34,535,0,0),(34,536,0,0),(34,537,0,0),(34,538,0,0),(34,539,0,0),(34,540,0,0),(34,541,0,0),(34,542,0,0),(34,543,0,0),(34,544,0,0),(34,545,0,0),(34,546,0,0),(34,547,0,0),(35,548,0,0),(35,549,0,0),(35,550,0,0),(35,551,0,0),(35,552,0,0),(35,553,0,0),(35,554,0,0),(35,555,0,0),(35,556,0,0),(35,557,0,0),(35,558,0,0),(35,559,0,0),(35,560,0,0),(35,561,0,0),(35,562,0,0),(35,563,0,0),(35,564,0,0),(35,565,0,0),(35,566,0,0),(35,567,0,0),(35,568,0,0),(35,569,0,0),(37,570,0,0),(37,571,0,0),(37,572,0,0),(37,573,0,0),(37,574,0,0),(37,575,0,0),(37,576,0,0),(37,577,0,0),(39,578,0,0),(39,579,0,0),(39,580,0,0),(39,581,0,0),(39,582,0,0),(39,583,0,0),(39,584,0,0),(39,585,0,0),(39,586,0,0),(39,587,0,0),(39,588,0,0),(39,589,0,0),(39,590,0,0),(40,591,0,0),(40,592,0,0),(40,593,0,0),(40,594,0,0),(40,595,0,0),(40,596,0,0),(40,597,0,0),(40,598,0,0),(40,599,0,0),(40,600,0,0),(40,601,0,0),(40,602,0,0),(40,603,0,0),(40,604,0,0),(40,605,0,0),(40,606,0,0),(40,607,0,0),(40,608,0,0),(41,609,0,0),(41,610,0,0),(41,611,0,0),(41,612,0,0),(41,613,0,0),(41,614,0,0),(41,615,0,0),(41,616,0,0),(41,617,0,0),(41,618,0,0),(41,619,0,0),(41,620,0,0),(41,621,0,0),(41,622,0,0),(41,623,0,0),(41,624,0,0),(41,625,0,0),(41,626,0,0),(41,627,0,0),(41,628,0,0),(42,629,0,0),(42,630,0,0),(42,631,0,0),(42,632,0,0),(42,633,0,0),(42,634,0,0),(43,635,0,0),(43,636,0,0),(43,637,0,0),(43,638,0,0),(43,639,0,0),(43,640,0,0),(43,641,0,0),(43,642,0,0),(43,643,0,0),(43,644,0,0),(43,645,0,0),(43,646,0,0),(43,647,0,0),(43,648,0,0),(43,649,0,0),(43,650,0,0),(43,651,0,0),(43,652,0,0),(43,653,0,0),(43,654,0,0),(43,655,0,0),(2,656,0,0),(29,657,0,0),(23,658,0,0),(23,659,0,0),(23,660,0,0),(23,661,0,0),(23,662,0,0),(23,663,0,0),(23,664,0,0),(23,665,0,0),(23,666,0,0),(22,667,0,0),(22,668,0,0),(22,669,0,0),(22,670,0,0),(22,671,0,0),(22,672,0,0),(22,673,0,0),(22,674,0,0),(22,675,0,0),(21,676,0,0),(21,677,0,0),(21,678,0,0),(21,679,0,0),(21,680,0,0),(21,681,0,0),(21,682,0,0),(21,683,0,0),(21,684,0,0),(20,685,0,0),(20,686,0,0),(20,687,0,0),(20,688,0,0),(20,689,0,0),(20,690,0,0),(20,691,0,0),(20,692,0,0),(20,693,0,0),(29,694,0,0),(40,695,0,0),(37,696,0,0),(29,697,0,0),(29,698,0,0),(29,699,0,0),(23,700,0,0),(22,701,0,0),(21,702,0,0),(20,703,0,0),(29,704,0,0),(6,705,0,0),(4,706,0,0),(2,707,0,0),(29,708,0,0),(23,709,0,0),(23,710,0,0),(21,711,0,0),(21,712,0,0),(18,713,0,0),(7,714,0,0),(37,715,0,0),(37,716,0,0),(23,717,0,0),(20,718,0,0),(21,719,0,0),(22,720,0,0),(29,721,0,0),(2,722,0,0),(13,723,0,0),(29,724,0,0),(29,725,0,0),(29,726,0,0),(29,727,0,0),(14,753,0,0),(35,754,0,0),(23,755,0,0),(29,756,0,0),(23,757,0,0),(23,758,0,0),(23,759,0,0),(20,760,0,0),(20,761,0,0),(20,762,0,0),(21,763,0,0),(22,764,0,0),(22,765,0,0),(22,766,0,0),(37,767,0,0),(37,768,0,0),(37,769,0,0),(2,770,0,0),(4,771,0,0),(6,772,0,0),(7,773,0,0),(8,774,0,0),(9,775,0,0),(10,776,0,0),(13,777,0,0),(14,778,0,0),(15,779,0,0),(16,780,0,0),(18,781,0,0),(19,782,0,0),(20,783,0,0),(21,784,0,0),(22,785,0,0),(23,786,0,0),(26,787,0,0),(10,788,0,0),(2,789,0,0),(4,790,0,0),(6,791,0,0),(7,792,0,0),(8,793,0,0),(9,794,0,0),(10,795,0,0),(13,796,0,0),(14,797,0,0),(15,798,0,0),(16,799,0,0),(18,800,0,0),(19,801,0,0),(20,802,0,0),(21,803,0,0),(22,804,0,0),(23,805,0,0),(26,806,0,0),(2,807,0,0),(4,808,0,0),(6,809,0,0),(7,810,0,0),(8,811,0,0),(9,812,0,0),(10,813,0,0),(13,814,0,0),(14,815,0,0),(15,816,0,0),(16,817,0,0),(18,818,0,0),(19,819,0,0),(20,820,0,0),(21,821,0,0),(22,822,0,0),(23,823,0,0),(26,824,0,0),(20,825,0,0),(21,826,0,0),(22,827,0,0),(23,828,0,0),(41,829,0,0),(41,830,0,0),(29,831,0,0),(22,832,0,0),(49,833,0,0),(49,834,0,0),(49,835,0,0),(49,836,0,0),(49,837,0,0),(49,838,0,0),(49,839,0,0),(49,840,0,0),(49,841,0,0),(49,842,0,0),(20,843,0,0),(21,844,0,0),(22,845,0,0),(23,846,0,0),(14,847,0,0),(20,848,0,0),(21,849,0,0),(22,850,0,0),(23,851,0,0),(20,852,0,0),(21,853,0,0),(22,854,0,0),(23,855,0,0),(29,856,0,0),(16,857,0,0),(29,858,0,0),(16,859,0,0),(16,860,0,0),(2,861,0,0),(6,862,0,0),(4,863,0,0),(7,864,0,0),(23,865,0,0),(20,866,0,0),(21,867,0,0),(22,868,0,0);
/*!40000 ALTER TABLE `vtiger_def_org_field` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_def_org_share`
--

DROP TABLE IF EXISTS `vtiger_def_org_share`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_def_org_share` (
  `ruleid` int NOT NULL AUTO_INCREMENT,
  `tabid` int NOT NULL,
  `permission` int DEFAULT NULL,
  `editstatus` int DEFAULT NULL,
  PRIMARY KEY (`ruleid`),
  KEY `fk_1_vtiger_def_org_share` (`permission`),
  CONSTRAINT `fk_1_vtiger_def_org_share` FOREIGN KEY (`permission`) REFERENCES `vtiger_org_share_action_mapping` (`share_action_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_def_org_share`
--

LOCK TABLES `vtiger_def_org_share` WRITE;
/*!40000 ALTER TABLE `vtiger_def_org_share` DISABLE KEYS */;
INSERT INTO `vtiger_def_org_share` VALUES (1,2,2,0),(2,4,2,0),(3,6,2,0),(4,7,2,0),(5,9,3,1),(6,13,2,0),(7,16,3,2),(8,20,2,0),(9,21,2,0),(10,22,2,0),(11,23,2,0),(12,26,2,0),(13,8,2,0),(14,14,2,0),(15,34,2,0),(16,35,2,0),(17,37,2,0),(18,39,2,0),(19,40,2,0),(20,41,2,0),(21,42,2,0),(22,43,2,0),(23,18,2,0),(24,10,2,0),(25,48,2,0),(26,49,2,0);
/*!40000 ALTER TABLE `vtiger_def_org_share` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_def_org_share_seq`
--

DROP TABLE IF EXISTS `vtiger_def_org_share_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_def_org_share_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_def_org_share_seq`
--

LOCK TABLES `vtiger_def_org_share_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_def_org_share_seq` DISABLE KEYS */;
INSERT INTO `vtiger_def_org_share_seq` VALUES (26);
/*!40000 ALTER TABLE `vtiger_def_org_share_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_default_record_view`
--

DROP TABLE IF EXISTS `vtiger_default_record_view`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_default_record_view` (
  `default_record_viewid` int NOT NULL AUTO_INCREMENT,
  `default_record_view` varchar(200) NOT NULL,
  `sortorderid` int DEFAULT NULL,
  `presence` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`default_record_viewid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_default_record_view`
--

LOCK TABLES `vtiger_default_record_view` WRITE;
/*!40000 ALTER TABLE `vtiger_default_record_view` DISABLE KEYS */;
INSERT INTO `vtiger_default_record_view` VALUES (1,'Summary',0,1),(2,'Detail',1,1);
/*!40000 ALTER TABLE `vtiger_default_record_view` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_default_record_view_seq`
--

DROP TABLE IF EXISTS `vtiger_default_record_view_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_default_record_view_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_default_record_view_seq`
--

LOCK TABLES `vtiger_default_record_view_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_default_record_view_seq` DISABLE KEYS */;
INSERT INTO `vtiger_default_record_view_seq` VALUES (2);
/*!40000 ALTER TABLE `vtiger_default_record_view_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_defaultactivitytype`
--

DROP TABLE IF EXISTS `vtiger_defaultactivitytype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_defaultactivitytype` (
  `defaultactivitytypeid` int NOT NULL AUTO_INCREMENT,
  `defaultactivitytype` varchar(200) NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT '0',
  PRIMARY KEY (`defaultactivitytypeid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_defaultactivitytype`
--

LOCK TABLES `vtiger_defaultactivitytype` WRITE;
/*!40000 ALTER TABLE `vtiger_defaultactivitytype` DISABLE KEYS */;
INSERT INTO `vtiger_defaultactivitytype` VALUES (1,'Call',1,299,1),(2,'Meeting',1,300,2);
/*!40000 ALTER TABLE `vtiger_defaultactivitytype` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_defaultactivitytype_seq`
--

DROP TABLE IF EXISTS `vtiger_defaultactivitytype_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_defaultactivitytype_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_defaultactivitytype_seq`
--

LOCK TABLES `vtiger_defaultactivitytype_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_defaultactivitytype_seq` DISABLE KEYS */;
INSERT INTO `vtiger_defaultactivitytype_seq` VALUES (2);
/*!40000 ALTER TABLE `vtiger_defaultactivitytype_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_defaultcalendarview`
--

DROP TABLE IF EXISTS `vtiger_defaultcalendarview`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_defaultcalendarview` (
  `defaultcalendarviewid` int NOT NULL AUTO_INCREMENT,
  `defaultcalendarview` varchar(200) NOT NULL,
  `sortorderid` int DEFAULT NULL,
  `presence` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`defaultcalendarviewid`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_defaultcalendarview`
--

LOCK TABLES `vtiger_defaultcalendarview` WRITE;
/*!40000 ALTER TABLE `vtiger_defaultcalendarview` DISABLE KEYS */;
INSERT INTO `vtiger_defaultcalendarview` VALUES (1,'ListView',0,1),(2,'MyCalendar',1,1),(3,'SharedCalendar',2,1);
/*!40000 ALTER TABLE `vtiger_defaultcalendarview` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_defaultcalendarview_seq`
--

DROP TABLE IF EXISTS `vtiger_defaultcalendarview_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_defaultcalendarview_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_defaultcalendarview_seq`
--

LOCK TABLES `vtiger_defaultcalendarview_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_defaultcalendarview_seq` DISABLE KEYS */;
INSERT INTO `vtiger_defaultcalendarview_seq` VALUES (3);
/*!40000 ALTER TABLE `vtiger_defaultcalendarview_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_defaultcv`
--

DROP TABLE IF EXISTS `vtiger_defaultcv`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_defaultcv` (
  `tabid` int NOT NULL,
  `defaultviewname` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `query` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`tabid`),
  CONSTRAINT `fk_1_vtiger_defaultcv` FOREIGN KEY (`tabid`) REFERENCES `vtiger_tab` (`tabid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_defaultcv`
--

LOCK TABLES `vtiger_defaultcv` WRITE;
/*!40000 ALTER TABLE `vtiger_defaultcv` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_defaultcv` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_defaulteventstatus`
--

DROP TABLE IF EXISTS `vtiger_defaulteventstatus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_defaulteventstatus` (
  `defaulteventstatusid` int NOT NULL AUTO_INCREMENT,
  `defaulteventstatus` varchar(200) NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT '0',
  PRIMARY KEY (`defaulteventstatusid`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_defaulteventstatus`
--

LOCK TABLES `vtiger_defaulteventstatus` WRITE;
/*!40000 ALTER TABLE `vtiger_defaulteventstatus` DISABLE KEYS */;
INSERT INTO `vtiger_defaulteventstatus` VALUES (1,'Planned',1,296,1),(2,'Held',1,297,2),(3,'Not Held',1,298,3);
/*!40000 ALTER TABLE `vtiger_defaulteventstatus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_defaulteventstatus_seq`
--

DROP TABLE IF EXISTS `vtiger_defaulteventstatus_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_defaulteventstatus_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_defaulteventstatus_seq`
--

LOCK TABLES `vtiger_defaulteventstatus_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_defaulteventstatus_seq` DISABLE KEYS */;
INSERT INTO `vtiger_defaulteventstatus_seq` VALUES (3);
/*!40000 ALTER TABLE `vtiger_defaulteventstatus_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_defaultlandingpage`
--

DROP TABLE IF EXISTS `vtiger_defaultlandingpage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_defaultlandingpage` (
  `defaultlandingpageid` int NOT NULL AUTO_INCREMENT,
  `defaultlandingpage` varchar(200) NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT '0',
  `color` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`defaultlandingpageid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_defaultlandingpage`
--

LOCK TABLES `vtiger_defaultlandingpage` WRITE;
/*!40000 ALTER TABLE `vtiger_defaultlandingpage` DISABLE KEYS */;
INSERT INTO `vtiger_defaultlandingpage` VALUES (1,'true',1,303,1,NULL),(2,'false',1,304,2,NULL);
/*!40000 ALTER TABLE `vtiger_defaultlandingpage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_defaultlandingpage_seq`
--

DROP TABLE IF EXISTS `vtiger_defaultlandingpage_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_defaultlandingpage_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_defaultlandingpage_seq`
--

LOCK TABLES `vtiger_defaultlandingpage_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_defaultlandingpage_seq` DISABLE KEYS */;
INSERT INTO `vtiger_defaultlandingpage_seq` VALUES (2);
/*!40000 ALTER TABLE `vtiger_defaultlandingpage_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_duration_minutes`
--

DROP TABLE IF EXISTS `vtiger_duration_minutes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_duration_minutes` (
  `minutesid` int NOT NULL AUTO_INCREMENT,
  `duration_minutes` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `sortorderid` int NOT NULL DEFAULT '0',
  `presence` int NOT NULL DEFAULT '1',
  `color` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`minutesid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_duration_minutes`
--

LOCK TABLES `vtiger_duration_minutes` WRITE;
/*!40000 ALTER TABLE `vtiger_duration_minutes` DISABLE KEYS */;
INSERT INTO `vtiger_duration_minutes` VALUES (1,'00',0,1,NULL),(2,'15',1,1,NULL),(3,'30',2,1,NULL),(4,'45',3,1,NULL);
/*!40000 ALTER TABLE `vtiger_duration_minutes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_duration_minutes_seq`
--

DROP TABLE IF EXISTS `vtiger_duration_minutes_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_duration_minutes_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_duration_minutes_seq`
--

LOCK TABLES `vtiger_duration_minutes_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_duration_minutes_seq` DISABLE KEYS */;
INSERT INTO `vtiger_duration_minutes_seq` VALUES (4);
/*!40000 ALTER TABLE `vtiger_duration_minutes_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_durationhrs`
--

DROP TABLE IF EXISTS `vtiger_durationhrs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_durationhrs` (
  `hrsid` int NOT NULL AUTO_INCREMENT,
  `hrs` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sortorderid` int NOT NULL DEFAULT '0',
  `presence` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`hrsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_durationhrs`
--

LOCK TABLES `vtiger_durationhrs` WRITE;
/*!40000 ALTER TABLE `vtiger_durationhrs` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_durationhrs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_durationmins`
--

DROP TABLE IF EXISTS `vtiger_durationmins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_durationmins` (
  `minsid` int NOT NULL AUTO_INCREMENT,
  `mins` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sortorderid` int NOT NULL DEFAULT '0',
  `presence` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`minsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_durationmins`
--

LOCK TABLES `vtiger_durationmins` WRITE;
/*!40000 ALTER TABLE `vtiger_durationmins` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_durationmins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_email_access`
--

DROP TABLE IF EXISTS `vtiger_email_access`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_email_access` (
  `crmid` int DEFAULT NULL,
  `mailid` int DEFAULT NULL,
  `accessdate` date DEFAULT NULL,
  `accesstime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_email_access`
--

LOCK TABLES `vtiger_email_access` WRITE;
/*!40000 ALTER TABLE `vtiger_email_access` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_email_access` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_email_track`
--

DROP TABLE IF EXISTS `vtiger_email_track`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_email_track` (
  `crmid` int DEFAULT NULL,
  `mailid` int DEFAULT NULL,
  `access_count` int DEFAULT NULL,
  `click_count` int NOT NULL DEFAULT '0',
  UNIQUE KEY `link_tabidtype_idx` (`crmid`,`mailid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_email_track`
--

LOCK TABLES `vtiger_email_track` WRITE;
/*!40000 ALTER TABLE `vtiger_email_track` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_email_track` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_emaildetails`
--

DROP TABLE IF EXISTS `vtiger_emaildetails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_emaildetails` (
  `emailid` int NOT NULL,
  `from_email` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `to_email` text COLLATE utf8mb4_general_ci NOT NULL,
  `cc_email` text COLLATE utf8mb4_general_ci NOT NULL,
  `bcc_email` text COLLATE utf8mb4_general_ci NOT NULL,
  `assigned_user_email` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `idlists` text COLLATE utf8mb4_general_ci NOT NULL,
  `email_flag` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`emailid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_emaildetails`
--

LOCK TABLES `vtiger_emaildetails` WRITE;
/*!40000 ALTER TABLE `vtiger_emaildetails` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_emaildetails` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_emails_recipientprefs`
--

DROP TABLE IF EXISTS `vtiger_emails_recipientprefs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_emails_recipientprefs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tabid` int NOT NULL,
  `prefs` varchar(255) DEFAULT NULL,
  `userid` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_emails_recipientprefs`
--

LOCK TABLES `vtiger_emails_recipientprefs` WRITE;
/*!40000 ALTER TABLE `vtiger_emails_recipientprefs` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_emails_recipientprefs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_emailslookup`
--

DROP TABLE IF EXISTS `vtiger_emailslookup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_emailslookup` (
  `crmid` int DEFAULT NULL,
  `setype` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `value` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fieldid` int DEFAULT NULL,
  UNIQUE KEY `emailslookup_crmid_setype_fieldname_uk` (`crmid`,`setype`,`fieldid`),
  KEY `emailslookup_fieldid_setype_idx` (`fieldid`,`setype`),
  CONSTRAINT `emailslookup_crmid_fk` FOREIGN KEY (`crmid`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_emailslookup`
--

LOCK TABLES `vtiger_emailslookup` WRITE;
/*!40000 ALTER TABLE `vtiger_emailslookup` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_emailslookup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_emailtemplates`
--

DROP TABLE IF EXISTS `vtiger_emailtemplates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_emailtemplates` (
  `foldername` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `templatename` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `templatepath` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `subject` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `body` text COLLATE utf8mb4_general_ci,
  `deleted` int NOT NULL DEFAULT '0',
  `templateid` int NOT NULL AUTO_INCREMENT,
  `systemtemplate` int NOT NULL DEFAULT '0',
  `module` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`templateid`),
  KEY `emailtemplates_foldernamd_templatename_subject_idx` (`foldername`,`templatename`,`subject`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_emailtemplates`
--

LOCK TABLES `vtiger_emailtemplates` WRITE;
/*!40000 ALTER TABLE `vtiger_emailtemplates` DISABLE KEYS */;
INSERT INTO `vtiger_emailtemplates` VALUES ('Public','Announcement for Release','','Announcement for Release','Announcement of a release','Hello!   <br />\n	<br />\n	On behalf of the vtiger team,  I am pleased to announce the release of vtiger crm4.2 . This is a feature packed release including the mass email template handling, custom view feature, vtiger_reports feature and a host of other utilities. vtiger runs on all platforms.    <br />\n        <br />\n	Notable Features of vtiger are :   <br />\n	<br />\n	-Email Client Integration    <br />\n	-Trouble Ticket Integration   <br />\n	-Invoice Management Integration   <br />\n	-Reports Integration   <br />\n	-Portal Integration   <br />\n	-Enhanced Word Plugin Support   <br />\n	-Custom View Integration   <br />\n	<br />\n	Known Issues:   <br />\n	-ABCD   <br />\n	-EFGH   <br />\n	-IJKL   <br />\n	-MNOP   <br />\n	-QRST',0,1,0,'Contacts'),('Public','Pending Invoices','','Invoices Pending','Payment Due','name <br />\nstreet, <br />\ncity, <br />\nstate, <br />\n zip) <br />\n  <br />\n Dear <br />\n <br />\n Please check the following invoices that are yet to be paid by you: <br />\n <br />\n No. Date      Amount <br />\n 1   1/1/01    $4000 <br />\n 2   2/2//01   $5000 <br />\n 3   3/3/01    $10000 <br />\n 4   7/4/01    $23560 <br />\n <br />\n Kindly let us know if there are any issues that you feel are pending to be discussed. <br />\n We will be more than happy to give you a call. <br />\n We would like to continue our business with you.',0,2,0,'Contacts'),('Public','Acceptance Proposal','','Acceptance Proposal','Acceptance of Proposal',' Dear <br />\n <br />\nYour proposal on the project XYZW has been reviewed by us <br />\nand is acceptable in its entirety. <br />\n <br />\nWe are eagerly looking forward to this project <br />\nand are pleased about having the opportunity to work <br />\ntogether. We look forward to a long standing relationship <br />\nwith your esteemed firm. <br />\n<br />\nI would like to take this opportunity to invite you <br />\nto a game of golf on Wednesday morning 9am at the <br />\nCuff Links Ground. We will be waiting for you in the <br />\nExecutive Lounge. <br />\n<br />\nLooking forward to seeing you there.',0,3,0,'Contacts'),('Public','Goods received acknowledgement','','Goods received acknowledgement','Acknowledged Receipt of Goods',' The undersigned hereby acknowledges receipt and delivery of the goods. <br />\nThe undersigned will release the payment subject to the goods being discovered not satisfactory. <br />\n<br />\nSigned under seal this <date>',0,4,0,'Contacts'),('Public','Accept Order','','Accept Order','Acknowledgement/Acceptance of Order',' Dear <br />\n         We are in receipt of your order as contained in the <br />\n   purchase order form.We consider this to be final and binding on both sides. <br />\nIf there be any exceptions noted, we shall consider them <br />\nonly if the objection is received within ten days of receipt of <br />\nthis notice. <br />\n <br />\nThank you for your patronage.',0,5,0,'Contacts'),('Public','Address Change','','Change of Address','Address Change','Dear <br />\n <br />\nWe are relocating our office to <br />\n11111,XYZDEF Cross, <br />\nUVWWX Circle <br />\nThe telephone number for this new location is (101) 1212-1328. <br />\n<br />\nOur Manufacturing Division will continue operations <br />\nat 3250 Lovedale Square Avenue, in Frankfurt. <br />\n<br />\nWe hope to keep in touch with you all. <br />\nPlease update your addressbooks.',0,6,0,'Contacts'),('Public','Follow Up','','Follow Up','Follow Up of meeting','Dear <br />\n<br />\nThank you for extending us the opportunity to meet with <br />\nyou and members of your staff. <br />\n<br />\nI know that John Doe serviced your account <br />\nfor many years and made many friends at your firm. He has personally <br />\ndiscussed with me the deep relationship that he had with your firm. <br />\nWhile his presence will be missed, I can promise that we will <br />\ncontinue to provide the fine service that was accorded by <br />\nJohn to your firm. <br />\n<br />\nI was genuinely touched to receive such fine hospitality. <br />\n<br />\nThank you once again.',0,7,0,'Contacts'),('Public','Target Crossed!','','Target Crossed!','Fantastic Sales Spree!','Congratulations! <br />\n<br />\nThe numbers are in and I am proud to inform you that our <br />\ntotal sales for the previous quarter <br />\namounts to $100,000,00.00!. This is the first time <br />\nwe have exceeded the target by almost 30%. <br />\nWe have also beat the previous quarter record by a <br />\nwhopping 75%! <br />\n<br />\nLet us meet at Smoking Joe for a drink in the evening! <br />\n\nC you all there guys!',0,8,0,'Contacts'),('Public','Thanks Note','','Thanks Note','Note of thanks','Dear <br />\n<br />\nThank you for your confidence in our ability to serve you. <br />\nWe are glad to be given the chance to serve you.I look <br />\nforward to establishing a long term partnership with you. <br />\nConsider me as a friend. <br />\nShould any need arise,please do give us a call.',0,9,0,'Contacts'),('Public','Customer Login Details','','LBL_CUSTOMER_PORTAL_LOGIN_INFORMATION','Send Portal login details to customer','    <html>\n        <head>\n                <title></title>\n        </head>\n        <body class=\"scayt-enabled\"><!-- <center> -->\n        <table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"borderGrey\" style=\"margin-left:0px;\" width=\"600px\">\n                <tbody>\n                        <tr>\n                                <td colspan=\"6\"><!-- Begin Pre header --><!-- // End Pre header \\ --></td>\n                        </tr>\n                        <tr style=\"height:50px;\">\n                                <td colspan=\"6\" style=\"\n                        font-family: Helvetica,Verdana,sans-serif\">\n                                <div style=\"margin-bottom:10px;color: rgb(34, 34, 34); font-family: arial, sans-serif; font-size: 14px; background-color: rgb(255, 255, 255);\"><br />\n                                $contact_name$ {vtranslate(\'LBL_TO_YOU\')}</div>\n\n                                <div style=\"margin-top:20px;margin-bottom:20px; color: rgb(34, 34, 34); font-family: arial, sans-serif; font-size: 14px; background-color: rgb(255, 255, 255);\">{vtranslate(\'LBL_THANK_YOU_ALWAYS_USING_OUR_SERVICES\')}<br />\n                                <br />\n                                {vtranslate(\'LBL_INVITATION_EMAIL_TO_OUR_CUSTOMER_PORTAL_BODY\')}<br />\n                                <div style=\"margin-top:10px;color: rgb(34, 34, 34); font-family: arial, sans-serif; font-size: 14px; background-color: rgb(255, 255, 255);\">$URL$ </div>\n\n                                <div style=\"margin-top:20px;color: rgb(34, 34, 34); font-family: arial, sans-serif; font-size: 14px; background-color: rgb(255, 255, 255);\">Your Username: $login_name$</div>\n\n                                <div style=\"margin-bottom:20px;color: rgb(34, 34, 34); font-family: arial, sans-serif; font-size: 14px; background-color: rgb(255, 255, 255);\">Your Password: $password$</div>\n\n                                <div class=\"gmail_extra\" style=\"margin-top:10px;color: rgb(34, 34, 34); font-family: arial, sans-serif; font-size: 14px; background-color: rgb(255, 255, 255);\"><br />\n                                </div>\n                                </td>\n                        </tr>\n                        <tr>\n                                <td colspan=\"6\" style=\"font-family: Helvetica,Verdana,sans-serif;font-size: 11px;color: #666666;\">\n                                <table border=\"0\" cellpadding=\"4\" cellspacing=\"0\" width=\"100%\">\n                                        <tbody><!--copy right data-->\n                                                <tr>\n                                                        <td style=\"\n                                    padding-left: 0px;\n                                    padding-right: 0px;\n                                    width:350px\" valign=\"top\">\n                                                        <div style=\"margin-top:20px;\"><em>Powered By <a href=\"f-revocrm.jp\">F-RevoCRM</a></em>\n                                                        <div></div>\n                                                        </div>\n                                                        </td>\n                                                </tr>\n                                                <!--subscribers links-->\n                                        </tbody>\n                                </table>\n                                </td>\n                        </tr>\n                </tbody>\n        </table>\n        <!-- </center> --></body>\n    </html>',0,10,0,'Contacts'),('Public','Support end notification before a week','','VtigerCRM Support Notification','Send Notification mail to customer before a week of support end date','<table width=\"700\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: normal; text-decoration: none; background-color: rgb(122, 122, 254);\">\n        <tr>\n            <td> </td>\n            <td> </td>\n            <td> </td>\n        </tr>\n        <tr>\n            <td> </td>\n            <td> </td>\n            <td> </td>\n        </tr>\n        <tr>\n            <td> </td>\n            <td> </td>\n            <td> </td>\n        </tr>\n        <tr>\n            <td width=\"50\"> </td>\n            <td>\n            <table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">\n                    <tr>\n                        <td>\n                        <table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" style=\"background-color: rgb(27, 77, 140); font-family: Arial,Helvetica,sans-serif; font-size: 14px; color: rgb(255, 255, 255); font-weight: normal; line-height: 25px;\">\n                                <tr>\n                                    <td align=\"center\" rowspan=\"4\">$logo$</td>\n                                    <td align=\"center\"> </td>\n                                </tr>\n                                <tr>\n                                    <td align=\"left\" style=\"background-color: rgb(27, 77, 140); font-family: Arial,Helvetica,sans-serif; font-size: 24px; color: rgb(255, 255, 255); font-weight: bolder; line-height: 35px;\">vtiger CRM </td>\n                                </tr>\n                                <tr>\n                                    <td align=\"right\" style=\"padding-right: 100px;\">The honest Open Source CRM </td>\n                                </tr>\n                                <tr>\n                                    <td> </td>\n                                </tr>\n                        </table>\n                        </td>\n                    </tr>\n                    <tr>\n                        <td>\n                        <table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" style=\"font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: normal; color: rgb(0, 0, 0); background-color: rgb(255, 255, 255);\">\n                                <tr>\n                                    <td valign=\"top\">\n                                    <table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" border=\"0\">\n                                            <tr>\n                                                <td align=\"right\" style=\"font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: rgb(66, 66, 253);\"> </td>\n                                            </tr>\n                                            <tr>\n                                                <td> </td>\n                                            </tr>\n                                            <tr>\n                                                <td style=\"font-family: Arial,Helvetica,sans-serif; font-size: 14px; color: rgb(22, 72, 134); font-weight: bolder; line-height: 15px;\">Dear $contacts-lastname$, </td>\n                                            </tr>\n                                            <tr>\n                                                <td style=\"font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: normal; text-align: justify; line-height: 20px;\">This is just a notification mail regarding your support end.<br /><span style=\"font-weight: bold;\">Priority:</span> Urgent<br />Your Support is going to expire on next week<br />Please contact support@vtiger.com.<br /><br /><br /></td>\n                                            </tr>\n                                            <tr>\n                                                <td align=\"center\"><br /></td>\n                                            </tr>\n                                            <tr>\n                                                <td align=\"right\"><strong style=\"padding: 2px; font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: bold;\"><br /><br />Sincerly</strong></td>\n                                            </tr>\n                                            <tr>\n                                                <td align=\"right\" style=\"font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: normal; line-height: 20px;\">Support Team </td>\n                                            </tr>\n                                            <tr>\n                                                <td align=\"right\"><a style=\"font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: rgb(66, 66, 253);\" href=\"http://www.vtiger.com\">www.vtiger.com</a></td>\n                                            </tr>\n                                            <tr>\n                                                <td> </td>\n                                            </tr>\n                                    </table>\n                                    </td>\n                                    <td width=\"1%\" valign=\"top\"> </td>\n                                </tr>\n                        </table>\n                        </td>\n                    </tr>\n                    <tr>\n                        <td>\n                        <table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" border=\"0\" style=\"font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(255, 255, 255); font-weight: normal; line-height: 15px; background-color: rgb(51, 51, 51);\">\n                                <tr>\n                                    <td align=\"center\">Shree Narayana Complex, No 11 Sarathy Nagar, Vijaya Nagar , Velachery, Chennai - 600 042 India </td>\n                                </tr>\n                                <tr>\n                                    <td align=\"center\">Telephone No: +91 - 44 - 4202 - 1990     Toll Free No: +1 877 788 4437</td>\n                                </tr>\n                                <tr>\n                                    <td align=\"center\">Email Id: <a style=\"font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: rgb(255, 255, 255);\" href=\"mailto:info@vtiger.com\">info@vtiger.com</a></td>\n                                </tr>\n                        </table>\n                        </td>\n                    </tr>\n            </table>\n            </td>\n            <td width=\"50\"> </td>\n        </tr>\n        <tr>\n            <td> </td>\n            <td> </td>\n            <td> </td>\n        </tr>\n        <tr>\n            <td> </td>\n            <td> </td>\n            <td> </td>\n        </tr>\n        <tr>\n            <td> </td>\n            <td> </td>\n            <td> </td>\n        </tr>\n</table>',0,11,0,'Contacts'),('Public','Support end notification before a month','','VtigerCRM Support Notification','Send Notification mail to customer before a month of support end date','<table width=\"700\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: normal; text-decoration: none; background-color: rgb(122, 122, 254);\">\n        <tr>\n            <td> </td>\n            <td> </td>\n            <td> </td>\n        </tr>\n        <tr>\n            <td> </td>\n            <td> </td>\n            <td> </td>\n        </tr>\n        <tr>\n            <td> </td>\n            <td> </td>\n            <td> </td>\n        </tr>\n        <tr>\n            <td width=\"50\"> </td>\n            <td>\n            <table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">\n                    <tr>\n                        <td>\n                        <table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" style=\"background-color: rgb(27, 77, 140); font-family: Arial,Helvetica,sans-serif; font-size: 14px; color: rgb(255, 255, 255); font-weight: normal; line-height: 25px;\">\n                                <tr>\n                                    <td align=\"center\" rowspan=\"4\">$logo$</td>\n                                    <td align=\"center\"> </td>\n                                </tr>\n                                <tr>\n                                    <td align=\"left\" style=\"background-color: rgb(27, 77, 140); font-family: Arial,Helvetica,sans-serif; font-size: 24px; color: rgb(255, 255, 255); font-weight: bolder; line-height: 35px;\">vtiger CRM </td>\n                                </tr>\n                                <tr>\n                                    <td align=\"right\" style=\"padding-right: 100px;\">The honest Open Source CRM </td>\n                                </tr>\n                                <tr>\n                                    <td> </td>\n                                </tr>\n                        </table>\n                        </td>\n                    </tr>\n                    <tr>\n                        <td>\n                        <table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" style=\"font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: normal; color: rgb(0, 0, 0); background-color: rgb(255, 255, 255);\">\n                                <tr>\n                                    <td valign=\"top\">\n                                    <table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" border=\"0\">\n                                            <tr>\n                                                <td align=\"right\" style=\"font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: rgb(66, 66, 253);\"> </td>\n                                            </tr>\n                                            <tr>\n                                                <td> </td>\n                                            </tr>\n                                            <tr>\n                                                <td style=\"font-family: Arial,Helvetica,sans-serif; font-size: 14px; color: rgb(22, 72, 134); font-weight: bolder; line-height: 15px;\">Dear $contacts-lastname$, </td>\n                                            </tr>\n                                            <tr>\n                                                <td style=\"font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: normal; text-align: justify; line-height: 20px;\">This is just a notification mail regarding your support end.<br /><span style=\"font-weight: bold;\">Priority:</span> Normal<br />Your Support is going to expire on next month.<br />Please contact support@vtiger.com<br /><br /><br /></td>\n                                            </tr>\n                                            <tr>\n                                                <td align=\"center\"><br /></td>\n                                            </tr>\n                                            <tr>\n                                                <td align=\"right\"><strong style=\"padding: 2px; font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: bold;\"><br /><br />Sincerly</strong></td>\n                                            </tr>\n                                            <tr>\n                                                <td align=\"right\" style=\"font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: normal; line-height: 20px;\">Support Team </td>\n                                            </tr>\n                                            <tr>\n                                                <td align=\"right\"><a href=\"http://www.vtiger.com\" style=\"font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: rgb(66, 66, 253);\">www.vtiger.com</a></td>\n                                            </tr>\n                                            <tr>\n                                                <td> </td>\n                                            </tr>\n                                    </table>\n                                    </td>\n                                    <td width=\"1%\" valign=\"top\"> </td>\n                                </tr>\n                        </table>\n                        </td>\n                    </tr>\n                    <tr>\n                        <td>\n                        <table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" border=\"0\" style=\"font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(255, 255, 255); font-weight: normal; line-height: 15px; background-color: rgb(51, 51, 51);\">\n                                <tr>\n                                    <td align=\"center\">Shree Narayana Complex, No 11 Sarathy Nagar, Vijaya Nagar , Velachery, Chennai - 600 042 India </td>\n                                </tr>\n                                <tr>\n                                    <td align=\"center\">Telephone No: +91 - 44 - 4202 - 1990     Toll Free No: +1 877 788 4437</td>\n                                </tr>\n                                <tr>\n                                    <td align=\"center\">Email Id: <a href=\"mailto:info@vtiger.com\" style=\"font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: rgb(255, 255, 255);\">info@vtiger.com</a></td>\n                                </tr>\n                        </table>\n                        </td>\n                    </tr>\n            </table>\n            </td>\n            <td width=\"50\"> </td>\n        </tr>\n        <tr>\n            <td> </td>\n            <td> </td>\n            <td> </td>\n        </tr>\n        <tr>\n            <td> </td>\n            <td> </td>\n            <td> </td>\n        </tr>\n        <tr>\n            <td> </td>\n            <td> </td>\n            <td> </td>\n        </tr>\n</table>',0,12,0,'Contacts'),('Public','Activity Reminder',NULL,'Reminder','Reminder','<p>This is a reminder notification for the Activity<br/>詳細  :<br/> \n							&nbsp; タイトル : $events-subject$<br/> \n							&nbsp; 開始日時 : $events-date_start$<br/>\n							&nbsp; 終了日時 : $events-due_date$<br/> \n							&nbsp; ステータス : $events-eventstatus$<br/> \n							&nbsp; 場所 : $events-location$<br/> \n							&nbsp; 詳細 : $events-description$<br/><br/> \n							<p/>',0,14,1,'Events'),('Public','ToDo Reminder',NULL,'Activity Reminder','Reminder','<p>This is a reminder notification for the Activity<br/>詳細  :<br/>\n								&nbsp; タイトル : $calendar-subject$<br/>\n								&nbsp; 開始日時 : $calendar-date_start$<br/>\n								&nbsp; 完了日 : $calendar-due_date$<br/>\n								&nbsp; ステータス : $calendar-status$<br/>\n								&nbsp; 場所 : $calendar-location$<br/>\n								&nbsp; 詳細 : $calendar-description$<br/><br/>\n								<p/>',0,15,1,'Events'),('Public','Invite Users',NULL,'Invitation','Invite Users','<p>$invitee_name$,<br/><br/>活動に参加者として招待されました。<br/><br/>詳細  :<br/>\n								&nbsp; タイトル : $events-subject$<br/>\n								&nbsp; 開始日時 : $events-date_start$<br/> \n								&nbsp; 終了日時 : $events-due_date$<br/>\n								&nbsp; ステータス : $events-eventstatus$<br/>\n								&nbsp; 優先度 : $events-priority$<br/>\n								&nbsp; 関連 : $events-crmid$<br/>\n								&nbsp; 顧客担当者 : $events-contactid$<br/>\n								&nbsp; 場所 : $events-location$<br/>\n								&nbsp; 詳細 : $events-description$<br/><br/>\n								宜しくお願いします。,<br/>\n								$current_user_name$\n								<p/>',0,16,1,'Events');
/*!40000 ALTER TABLE `vtiger_emailtemplates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_emailtemplates_seq`
--

DROP TABLE IF EXISTS `vtiger_emailtemplates_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_emailtemplates_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_emailtemplates_seq`
--

LOCK TABLES `vtiger_emailtemplates_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_emailtemplates_seq` DISABLE KEYS */;
INSERT INTO `vtiger_emailtemplates_seq` VALUES (16);
/*!40000 ALTER TABLE `vtiger_emailtemplates_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_entityname`
--

DROP TABLE IF EXISTS `vtiger_entityname`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_entityname` (
  `tabid` int NOT NULL,
  `modulename` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tablename` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `fieldname` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `entityidfield` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `entityidcolumn` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`tabid`),
  KEY `entityname_tabid_idx` (`tabid`),
  CONSTRAINT `fk_1_vtiger_entityname` FOREIGN KEY (`tabid`) REFERENCES `vtiger_tab` (`tabid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_entityname`
--

LOCK TABLES `vtiger_entityname` WRITE;
/*!40000 ALTER TABLE `vtiger_entityname` DISABLE KEYS */;
INSERT INTO `vtiger_entityname` VALUES (2,'Potentials','vtiger_potential','potentialname','potentialid','potential_id'),(4,'Contacts','vtiger_contactdetails','lastname,firstname','contactid','contact_id'),(6,'Accounts','vtiger_account','accountname','accountid','account_id'),(7,'Leads','vtiger_leaddetails','lastname,firstname','leadid','leadid'),(8,'Documents','vtiger_notes','notes_title','notesid','notesid'),(9,'Calendar','vtiger_activity','subject','activityid','activityid'),(10,'Emails','vtiger_activity','subject','activityid','activityid'),(13,'HelpDesk','vtiger_troubletickets','ticket_title','ticketid','ticketid'),(14,'Products','vtiger_products','productname','productid','product_id'),(15,'Faq','vtiger_faq','question','id','id'),(18,'Vendors','vtiger_vendor','vendorname','vendorid','vendor_id'),(19,'PriceBooks','vtiger_pricebook','bookname','pricebookid','pricebookid'),(20,'Quotes','vtiger_quotes','subject','quoteid','quote_id'),(21,'PurchaseOrder','vtiger_purchaseorder','subject','purchaseorderid','purchaseorderid'),(22,'SalesOrder','vtiger_salesorder','subject','salesorderid','salesorder_id'),(23,'Invoice','vtiger_invoice','subject','invoiceid','invoiceid'),(26,'Campaigns','vtiger_campaign','campaignname','campaignid','campaignid'),(29,'Users','vtiger_users','last_name,first_name','id','id'),(34,'ServiceContracts','vtiger_servicecontracts','subject','servicecontractsid','servicecontractsid'),(35,'Services','vtiger_service','servicename','serviceid','serviceid'),(37,'ModComments','vtiger_modcomments','commentcontent','modcommentsid','modcommentsid'),(39,'ProjectMilestone','vtiger_projectmilestone','projectmilestonename','projectmilestoneid','projectmilestoneid'),(40,'ProjectTask','vtiger_projecttask','projecttaskname','projecttaskid','projecttaskid'),(41,'Project','vtiger_project','projectname','projectid','projectid'),(42,'SMSNotifier','vtiger_smsnotifier','message','smsnotifierid','smsnotifierid'),(43,'Assets','vtiger_assets','assetname','assetsid','assetsid'),(49,'Dailyreports','vtiger_dailyreports','dailyreportsname','dailyreportsid','dailyreportsid');
/*!40000 ALTER TABLE `vtiger_entityname` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_eventhandler_module`
--

DROP TABLE IF EXISTS `vtiger_eventhandler_module`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_eventhandler_module` (
  `eventhandler_module_id` int NOT NULL AUTO_INCREMENT,
  `module_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `handler_class` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`eventhandler_module_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_eventhandler_module`
--

LOCK TABLES `vtiger_eventhandler_module` WRITE;
/*!40000 ALTER TABLE `vtiger_eventhandler_module` DISABLE KEYS */;
INSERT INTO `vtiger_eventhandler_module` VALUES (1,'ModTracker','ModTrackerHandler'),(2,'ServiceContracts','ServiceContractsHandler'),(4,'Invoice','InvoiceHandler'),(5,'PurchaseOrder','PurchaseOrderHandler');
/*!40000 ALTER TABLE `vtiger_eventhandler_module` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_eventhandler_module_seq`
--

DROP TABLE IF EXISTS `vtiger_eventhandler_module_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_eventhandler_module_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_eventhandler_module_seq`
--

LOCK TABLES `vtiger_eventhandler_module_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_eventhandler_module_seq` DISABLE KEYS */;
INSERT INTO `vtiger_eventhandler_module_seq` VALUES (5);
/*!40000 ALTER TABLE `vtiger_eventhandler_module_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_eventhandlers`
--

DROP TABLE IF EXISTS `vtiger_eventhandlers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_eventhandlers` (
  `eventhandler_id` int NOT NULL AUTO_INCREMENT,
  `event_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `handler_path` varchar(400) COLLATE utf8mb4_general_ci NOT NULL,
  `handler_class` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `cond` text COLLATE utf8mb4_general_ci NOT NULL,
  `is_active` int NOT NULL,
  `dependent_on` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '[]',
  PRIMARY KEY (`eventhandler_id`,`event_name`,`handler_class`),
  UNIQUE KEY `eventhandler_idx` (`eventhandler_id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_eventhandlers`
--

LOCK TABLES `vtiger_eventhandlers` WRITE;
/*!40000 ALTER TABLE `vtiger_eventhandlers` DISABLE KEYS */;
INSERT INTO `vtiger_eventhandlers` VALUES (1,'vtiger.entity.aftersave','modules/SalesOrder/RecurringInvoiceHandler.php','RecurringInvoiceHandler','',1,'[]'),(2,'vtiger.entity.beforesave','data/VTEntityDelta.php','VTEntityDelta','',1,'[]'),(3,'vtiger.entity.aftersave','data/VTEntityDelta.php','VTEntityDelta','',1,'[]'),(4,'vtiger.entity.aftersave','modules/com_vtiger_workflow/VTEventHandler.inc','VTWorkflowEventHandler','',1,'[\"VTEntityDelta\"]'),(5,'vtiger.entity.afterrestore','modules/com_vtiger_workflow/VTEventHandler.inc','VTWorkflowEventHandler','',1,'[]'),(6,'vtiger.entity.aftersave.final','modules/HelpDesk/HelpDeskHandler.php','HelpDeskHandler','',1,'[]'),(7,'vtiger.entity.aftersave.final','modules/ModTracker/ModTrackerHandler.php','ModTrackerHandler','',1,'[]'),(8,'vtiger.entity.beforedelete','modules/ModTracker/ModTrackerHandler.php','ModTrackerHandler','',1,'[]'),(9,'vtiger.entity.afterrestore','modules/ModTracker/ModTrackerHandler.php','ModTrackerHandler','',1,'[]'),(10,'vtiger.entity.aftersave','modules/WSAPP/WorkFlowHandlers/WSAPPAssignToTracker.php','WSAPPAssignToTracker','',1,'[\"VTEntityDelta\"]'),(11,'vtiger.entity.beforesave','modules/ServiceContracts/ServiceContractsHandler.php','ServiceContractsHandler','',1,'[]'),(12,'vtiger.entity.aftersave','modules/ServiceContracts/ServiceContractsHandler.php','ServiceContractsHandler','',1,'[]'),(14,'vtiger.entity.aftersave','modules/Invoice/InvoiceHandler.php','InvoiceHandler','',1,'[]'),(15,'vtiger.entity.aftersave','modules/PurchaseOrder/PurchaseOrderHandler.php','PurchaseOrderHandler','',1,'[]'),(16,'vtiger.entity.aftersave','modules/ModComments/ModCommentsHandler.php','ModCommentsHandler','',1,'[]'),(17,'vtiger.picklist.afterrename','modules/Settings/Picklist/handlers/PickListHandler.php','PickListHandler','',1,'[]'),(18,'vtiger.picklist.afterdelete','modules/Settings/Picklist/handlers/PickListHandler.php','PickListHandler','',1,'[]'),(19,'vtiger.entity.aftersave','modules/Vtiger/handlers/EmailLookupHandler.php','EmailLookupHandler','',1,'[\"VTEntityDelta\"]'),(20,'vtiger.entity.afterdelete','modules/Vtiger/handlers/EmailLookupHandler.php','EmailLookupHandler','',1,'[]'),(21,'vtiger.entity.afterrestore','modules/Vtiger/handlers/EmailLookupHandler.php','EmailLookupHandler','',1,'[]'),(22,'vtiger.batchevent.save','modules/Vtiger/handlers/EmailLookupHandler.php','EmailLookupBatchHandler','',1,'[]'),(23,'vtiger.lead.convertlead','modules/Leads/handlers/LeadHandler.php','LeadHandler','',1,'[]'),(24,'vtiger.entity.beforesave','modules/Vtiger/handlers/CheckDuplicateHandler.php','CheckDuplicateHandler','',1,'[]'),(25,'vtiger.entity.beforerestore','modules/Vtiger/handlers/CheckDuplicateHandler.php','CheckDuplicateHandler','',1,'[]'),(26,'vtiger.entity.aftersave','modules/Vtiger/handlers/FollowRecordHandler.php','FollowRecordHandler','',1,'[]'),(27,'vtiger.field.afterdelete','modules/Vtiger/handlers/FieldEventHandler.php','FieldEventHandler','',1,'[]'),(28,'vtiger.entity.beforerelate','modules/Vtiger/handlers/RelateEntitesHandler.php','RelateEntitesHandler','',1,'[]'),(29,'vtiger.entity.afterrelate','modules/Vtiger/handlers/RelateEntitesHandler.php','RelateEntitesHandler','',1,'[]');
/*!40000 ALTER TABLE `vtiger_eventhandlers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_eventhandlers_seq`
--

DROP TABLE IF EXISTS `vtiger_eventhandlers_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_eventhandlers_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_eventhandlers_seq`
--

LOCK TABLES `vtiger_eventhandlers_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_eventhandlers_seq` DISABLE KEYS */;
INSERT INTO `vtiger_eventhandlers_seq` VALUES (29);
/*!40000 ALTER TABLE `vtiger_eventhandlers_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_eventstatus`
--

DROP TABLE IF EXISTS `vtiger_eventstatus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_eventstatus` (
  `eventstatusid` int NOT NULL AUTO_INCREMENT,
  `eventstatus` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT NULL,
  `color` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`eventstatusid`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_eventstatus`
--

LOCK TABLES `vtiger_eventstatus` WRITE;
/*!40000 ALTER TABLE `vtiger_eventstatus` DISABLE KEYS */;
INSERT INTO `vtiger_eventstatus` VALUES (1,'Planned',0,38,0,NULL),(2,'Held',0,39,1,NULL),(3,'Not Held',0,40,2,NULL);
/*!40000 ALTER TABLE `vtiger_eventstatus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_eventstatus_seq`
--

DROP TABLE IF EXISTS `vtiger_eventstatus_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_eventstatus_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_eventstatus_seq`
--

LOCK TABLES `vtiger_eventstatus_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_eventstatus_seq` DISABLE KEYS */;
INSERT INTO `vtiger_eventstatus_seq` VALUES (3);
/*!40000 ALTER TABLE `vtiger_eventstatus_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_expectedresponse`
--

DROP TABLE IF EXISTS `vtiger_expectedresponse`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_expectedresponse` (
  `expectedresponseid` int NOT NULL AUTO_INCREMENT,
  `expectedresponse` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT NULL,
  `color` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`expectedresponseid`),
  UNIQUE KEY `CampaignExpRes_UK01` (`expectedresponse`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_expectedresponse`
--

LOCK TABLES `vtiger_expectedresponse` WRITE;
/*!40000 ALTER TABLE `vtiger_expectedresponse` DISABLE KEYS */;
INSERT INTO `vtiger_expectedresponse` VALUES (2,'Excellent',1,42,1,NULL),(3,'Good',1,43,2,NULL),(4,'Average',1,44,3,NULL),(5,'Poor',1,45,4,NULL);
/*!40000 ALTER TABLE `vtiger_expectedresponse` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_expectedresponse_seq`
--

DROP TABLE IF EXISTS `vtiger_expectedresponse_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_expectedresponse_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_expectedresponse_seq`
--

LOCK TABLES `vtiger_expectedresponse_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_expectedresponse_seq` DISABLE KEYS */;
INSERT INTO `vtiger_expectedresponse_seq` VALUES (5);
/*!40000 ALTER TABLE `vtiger_expectedresponse_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_faq`
--

DROP TABLE IF EXISTS `vtiger_faq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_faq` (
  `id` int NOT NULL AUTO_INCREMENT,
  `faq_no` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `product_id` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `question` longtext COLLATE utf8mb4_general_ci,
  `answer` longtext COLLATE utf8mb4_general_ci,
  `category` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `status` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `tags` varchar(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `smcreatorid` int DEFAULT NULL,
  `smownerid` int DEFAULT NULL,
  `modifiedby` int DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_general_ci,
  `createdtime` datetime DEFAULT NULL,
  `modifiedtime` datetime DEFAULT NULL,
  `viewedtime` datetime DEFAULT NULL,
  `deleted` int DEFAULT NULL,
  `label` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `smgroupid` int DEFAULT NULL,
  `source` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `faq_id_idx` (`id`),
  KEY `idx_info` (`deleted`,`modifiedtime`),
  KEY `idx_label` (`deleted`,`label`),
  KEY `idx_owner` (`deleted`,`smownerid`),
  CONSTRAINT `fk_1_vtiger_faq` FOREIGN KEY (`id`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_faq`
--

LOCK TABLES `vtiger_faq` WRITE;
/*!40000 ALTER TABLE `vtiger_faq` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_faq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_faqcategories`
--

DROP TABLE IF EXISTS `vtiger_faqcategories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_faqcategories` (
  `faqcategories_id` int NOT NULL AUTO_INCREMENT,
  `faqcategories` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT NULL,
  `color` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`faqcategories_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_faqcategories`
--

LOCK TABLES `vtiger_faqcategories` WRITE;
/*!40000 ALTER TABLE `vtiger_faqcategories` DISABLE KEYS */;
INSERT INTO `vtiger_faqcategories` VALUES (1,'General',1,46,0,NULL);
/*!40000 ALTER TABLE `vtiger_faqcategories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_faqcategories_seq`
--

DROP TABLE IF EXISTS `vtiger_faqcategories_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_faqcategories_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_faqcategories_seq`
--

LOCK TABLES `vtiger_faqcategories_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_faqcategories_seq` DISABLE KEYS */;
INSERT INTO `vtiger_faqcategories_seq` VALUES (1);
/*!40000 ALTER TABLE `vtiger_faqcategories_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_faqcf`
--

DROP TABLE IF EXISTS `vtiger_faqcf`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_faqcf` (
  `faqid` int NOT NULL,
  PRIMARY KEY (`faqid`),
  CONSTRAINT `fk_1_vtiger_faqcf` FOREIGN KEY (`faqid`) REFERENCES `vtiger_faq` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_faqcf`
--

LOCK TABLES `vtiger_faqcf` WRITE;
/*!40000 ALTER TABLE `vtiger_faqcf` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_faqcf` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_faqcomments`
--

DROP TABLE IF EXISTS `vtiger_faqcomments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_faqcomments` (
  `commentid` int NOT NULL AUTO_INCREMENT,
  `faqid` int DEFAULT NULL,
  `comments` text COLLATE utf8mb4_general_ci,
  `createdtime` datetime NOT NULL,
  PRIMARY KEY (`commentid`),
  KEY `faqcomments_faqid_idx` (`faqid`),
  CONSTRAINT `fk_1_vtiger_faqcomments` FOREIGN KEY (`faqid`) REFERENCES `vtiger_faq` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_faqcomments`
--

LOCK TABLES `vtiger_faqcomments` WRITE;
/*!40000 ALTER TABLE `vtiger_faqcomments` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_faqcomments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_faqstatus`
--

DROP TABLE IF EXISTS `vtiger_faqstatus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_faqstatus` (
  `faqstatus_id` int NOT NULL AUTO_INCREMENT,
  `faqstatus` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT NULL,
  `color` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`faqstatus_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_faqstatus`
--

LOCK TABLES `vtiger_faqstatus` WRITE;
/*!40000 ALTER TABLE `vtiger_faqstatus` DISABLE KEYS */;
INSERT INTO `vtiger_faqstatus` VALUES (1,'Draft',0,47,0,NULL),(2,'Reviewed',0,48,1,NULL),(3,'Published',0,49,2,NULL),(4,'Obsolete',0,50,3,NULL);
/*!40000 ALTER TABLE `vtiger_faqstatus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_faqstatus_seq`
--

DROP TABLE IF EXISTS `vtiger_faqstatus_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_faqstatus_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_faqstatus_seq`
--

LOCK TABLES `vtiger_faqstatus_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_faqstatus_seq` DISABLE KEYS */;
INSERT INTO `vtiger_faqstatus_seq` VALUES (4);
/*!40000 ALTER TABLE `vtiger_faqstatus_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_feedback`
--

DROP TABLE IF EXISTS `vtiger_feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_feedback` (
  `userid` int DEFAULT NULL,
  `dontshow` varchar(19) COLLATE utf8mb4_general_ci DEFAULT 'false'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_feedback`
--

LOCK TABLES `vtiger_feedback` WRITE;
/*!40000 ALTER TABLE `vtiger_feedback` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_field`
--

DROP TABLE IF EXISTS `vtiger_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_field` (
  `tabid` int NOT NULL,
  `fieldid` int NOT NULL AUTO_INCREMENT,
  `columnname` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tablename` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `generatedtype` int NOT NULL DEFAULT '0',
  `uitype` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `fieldname` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fieldlabel` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `readonly` int NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `defaultvalue` text COLLATE utf8mb4_general_ci,
  `maximumlength` int DEFAULT NULL,
  `sequence` int DEFAULT NULL,
  `block` int DEFAULT NULL,
  `displaytype` int DEFAULT NULL,
  `typeofdata` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `quickcreate` int NOT NULL DEFAULT '1',
  `quickcreatesequence` int DEFAULT NULL,
  `info_type` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `masseditable` int NOT NULL DEFAULT '1',
  `helpinfo` text COLLATE utf8mb4_general_ci,
  `summaryfield` int NOT NULL DEFAULT '0',
  `headerfield` int DEFAULT '0',
  `isunique` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`fieldid`),
  KEY `field_tabid_idx` (`tabid`),
  KEY `field_fieldname_idx` (`fieldname`),
  KEY `field_block_idx` (`block`),
  KEY `field_displaytype_idx` (`displaytype`),
  CONSTRAINT `fk_1_vtiger_field` FOREIGN KEY (`tabid`) REFERENCES `vtiger_tab` (`tabid`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=869 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_field`
--

LOCK TABLES `vtiger_field` WRITE;
/*!40000 ALTER TABLE `vtiger_field` DISABLE KEYS */;
INSERT INTO `vtiger_field` VALUES (6,1,'accountname','vtiger_account',1,'2','accountname','Account Name',1,0,'',100,1,9,1,'V~M',0,1,'BAS',1,NULL,1,0,1),(6,2,'account_no','vtiger_account',1,'4','account_no','Account No',1,0,'',100,2,9,1,'V~O',3,NULL,'BAS',0,NULL,0,0,0),(6,3,'phone','vtiger_account',1,'11','phone','Phone',1,2,'',100,4,9,1,'V~O',2,2,'BAS',1,NULL,0,1,0),(6,4,'website','vtiger_account',1,'17','website','Website',1,2,'',100,3,9,1,'V~O',2,3,'BAS',1,NULL,0,1,0),(6,5,'fax','vtiger_account',1,'11','fax','Fax',1,2,'',100,6,9,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(6,6,'tickersymbol','vtiger_account',1,'1','tickersymbol','Ticker Symbol',1,2,'',100,5,9,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(6,7,'otherphone','vtiger_account',1,'11','otherphone','Other Phone',1,2,'',100,8,9,1,'V~O',1,NULL,'ADV',1,NULL,0,0,0),(6,8,'parentid','vtiger_account',1,'51','account_id','Member Of',1,2,'',100,7,9,1,'I~O',1,NULL,'BAS',0,NULL,0,0,0),(6,9,'email1','vtiger_account',1,'13','email1','Email',1,2,'',100,10,9,1,'E~O',1,NULL,'BAS',1,NULL,0,1,0),(6,10,'employees','vtiger_account',1,'7','employees','Employees',1,2,'',100,9,9,1,'I~O',1,NULL,'ADV',1,NULL,0,0,0),(6,11,'email2','vtiger_account',1,'13','email2','Other Email',1,2,'',100,11,9,1,'E~O',1,NULL,'ADV',1,NULL,0,0,0),(6,12,'ownership','vtiger_account',1,'1','ownership','Ownership',1,2,'',100,12,9,1,'V~O',1,NULL,'ADV',1,NULL,0,0,0),(6,13,'rating','vtiger_account',1,'15','rating','Rating',1,2,'',100,14,9,1,'V~O',1,NULL,'ADV',1,NULL,0,0,0),(6,14,'industry','vtiger_account',1,'15','industry','industry',1,2,'',100,13,9,1,'V~O',1,NULL,'ADV',1,NULL,0,0,0),(6,15,'siccode','vtiger_account',1,'1','siccode','SIC Code',1,2,'',100,16,9,1,'V~O',1,NULL,'ADV',1,NULL,0,0,0),(6,16,'account_type','vtiger_account',1,'15','accounttype','Type',1,2,'',100,15,9,1,'V~O',1,NULL,'ADV',1,NULL,0,0,0),(6,17,'annualrevenue','vtiger_account',1,'71','annual_revenue','Annual Revenue',1,2,'',100,18,9,1,'N~O',1,NULL,'ADV',1,NULL,0,0,0),(6,18,'emailoptout','vtiger_account',1,'56','emailoptout','Email Opt Out',1,0,'',100,17,9,1,'C~O',1,NULL,'ADV',1,NULL,0,0,0),(6,19,'notify_owner','vtiger_account',1,'56','notify_owner','Notify Owner',1,2,'',10,20,9,1,'C~O',1,NULL,'ADV',1,NULL,0,0,0),(6,20,'smownerid','vtiger_account',1,'53','assigned_user_id','Assigned To',1,0,'',100,19,9,1,'V~M',0,4,'BAS',1,NULL,1,0,0),(6,21,'createdtime','vtiger_account',1,'70','createdtime','Created Time',1,0,'',100,22,9,2,'DT~O',3,NULL,'BAS',0,NULL,0,0,0),(6,22,'modifiedtime','vtiger_account',1,'70','modifiedtime','Modified Time',1,0,'',100,21,9,2,'DT~O',3,NULL,'BAS',0,NULL,0,0,0),(6,23,'modifiedby','vtiger_account',1,'52','modifiedby','Last Modified By',1,0,'',100,23,9,3,'V~O',3,NULL,'BAS',0,NULL,0,0,0),(6,24,'bill_street','vtiger_accountbillads',1,'21','bill_street','Billing Address',1,2,'',100,9,11,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(6,25,'ship_street','vtiger_accountshipads',1,'21','ship_street','Shipping Address',1,2,'',100,10,11,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(6,26,'bill_city','vtiger_accountbillads',1,'1','bill_city','Billing City',1,2,'',100,7,11,1,'V~O',1,NULL,'BAS',1,NULL,1,0,0),(6,27,'ship_city','vtiger_accountshipads',1,'1','ship_city','Shipping City',1,2,'',100,8,11,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(6,28,'bill_state','vtiger_accountbillads',1,'1','bill_state','Billing State',1,2,'',100,5,11,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(6,29,'ship_state','vtiger_accountshipads',1,'1','ship_state','Shipping State',1,2,'',100,6,11,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(6,30,'bill_code','vtiger_accountbillads',1,'1','bill_code','Billing Code',1,2,'',100,3,11,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(6,31,'ship_code','vtiger_accountshipads',1,'1','ship_code','Shipping Code',1,2,'',100,4,11,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(6,32,'bill_country','vtiger_accountbillads',1,'1','bill_country','Billing Country',1,1,'',100,1,11,1,'V~O',1,NULL,'BAS',1,NULL,1,0,0),(6,33,'ship_country','vtiger_accountshipads',1,'1','ship_country','Shipping Country',1,1,'',100,2,11,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(6,34,'bill_pobox','vtiger_accountbillads',1,'1','bill_pobox','Billing Po Box',1,1,'',100,11,11,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(6,35,'ship_pobox','vtiger_accountshipads',1,'1','ship_pobox','Shipping Po Box',1,1,'',100,12,11,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(6,36,'description','vtiger_account',1,'19','description','Description',1,2,'',100,1,12,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(7,37,'salutation','vtiger_leaddetails',1,'55','salutationtype','Salutation',1,1,'',100,1,13,3,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(7,38,'firstname','vtiger_leaddetails',1,'55','firstname','First Name',1,0,'',100,4,13,1,'V~O',2,2,'BAS',1,NULL,1,0,0),(7,39,'lead_no','vtiger_leaddetails',1,'4','lead_no','Lead No',1,0,'',100,3,13,1,'V~O',3,NULL,'BAS',0,NULL,0,0,0),(7,40,'phone','vtiger_leadaddress',1,'11','phone','Phone',1,2,'',100,5,13,1,'V~O',2,4,'BAS',1,NULL,0,1,0),(7,41,'lastname','vtiger_leaddetails',1,'255','lastname','Last Name',1,0,'',100,2,13,1,'V~M',0,1,'BAS',1,NULL,1,0,0),(7,42,'mobile','vtiger_leadaddress',1,'11','mobile','Mobile',1,2,'',100,7,13,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(7,43,'company','vtiger_leaddetails',1,'2','company','Company',1,2,'',100,6,13,1,'V~O',2,3,'BAS',1,NULL,1,0,0),(7,44,'fax','vtiger_leadaddress',1,'11','fax','Fax',1,2,'',100,9,13,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(7,45,'designation','vtiger_leaddetails',1,'1','designation','Designation',1,2,'',100,8,13,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(7,46,'email','vtiger_leaddetails',1,'13','email','Email',1,2,'',100,11,13,1,'E~O',2,5,'BAS',1,NULL,0,1,0),(7,47,'leadsource','vtiger_leaddetails',1,'15','leadsource','Lead Source',1,2,'',100,10,13,1,'V~O',1,NULL,'BAS',1,NULL,1,0,0),(7,48,'website','vtiger_leadsubdetails',1,'17','website','Website',1,2,'',100,13,13,1,'V~O',1,NULL,'ADV',1,NULL,1,0,0),(7,49,'industry','vtiger_leaddetails',1,'15','industry','Industry',1,2,'',100,12,13,1,'V~O',1,NULL,'ADV',1,NULL,0,0,0),(7,50,'leadstatus','vtiger_leaddetails',1,'15','leadstatus','Lead Status',1,2,'',100,15,13,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(7,51,'annualrevenue','vtiger_leaddetails',1,'71','annualrevenue','Annual Revenue',1,2,'',100,14,13,1,'N~O',1,NULL,'ADV',1,NULL,0,0,0),(7,52,'rating','vtiger_leaddetails',1,'15','rating','Rating',1,2,'',100,17,13,1,'V~O',1,NULL,'ADV',1,NULL,0,0,0),(7,53,'noofemployees','vtiger_leaddetails',1,'1','noofemployees','No Of Employees',1,2,'',100,16,13,1,'I~O',1,NULL,'ADV',1,NULL,0,0,0),(7,54,'smownerid','vtiger_leaddetails',1,'53','assigned_user_id','Assigned To',1,0,'',100,19,13,1,'V~M',0,6,'BAS',1,NULL,1,0,0),(7,55,'secondaryemail','vtiger_leaddetails',1,'13','secondaryemail','Secondary Email',1,2,'',100,18,13,1,'E~O',1,NULL,'ADV',1,NULL,0,0,0),(7,56,'createdtime','vtiger_leaddetails',1,'70','createdtime','Created Time',1,0,'',100,21,13,2,'DT~O',3,NULL,'BAS',0,NULL,0,0,0),(7,57,'modifiedtime','vtiger_leaddetails',1,'70','modifiedtime','Modified Time',1,0,'',100,20,13,2,'DT~O',3,NULL,'BAS',0,NULL,0,0,0),(7,58,'modifiedby','vtiger_leaddetails',1,'52','modifiedby','Last Modified By',1,0,'',100,23,13,3,'V~O',3,NULL,'BAS',0,NULL,0,0,0),(7,59,'lane','vtiger_leadaddress',1,'21','lane','Street',1,2,'',100,5,15,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(7,60,'code','vtiger_leadaddress',1,'1','code','Postal Code',1,2,'',100,2,15,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(7,61,'city','vtiger_leadaddress',1,'1','city','City',1,2,'',100,4,15,1,'V~O',1,NULL,'BAS',1,NULL,1,0,0),(7,62,'country','vtiger_leadaddress',1,'1','country','Country',1,1,'',100,1,15,1,'V~O',1,NULL,'BAS',1,NULL,1,0,0),(7,63,'state','vtiger_leadaddress',1,'1','state','State',1,2,'',100,3,15,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(7,64,'pobox','vtiger_leadaddress',1,'1','pobox','Po Box',1,1,'',100,6,15,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(7,65,'description','vtiger_leaddetails',1,'19','description','Description',1,2,'',100,1,16,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(4,66,'salutation','vtiger_contactdetails',1,'55','salutationtype','Salutation',1,1,'',100,1,4,3,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(4,67,'firstname','vtiger_contactdetails',1,'55','firstname','First Name',1,0,'',100,4,4,1,'V~O',2,2,'BAS',1,NULL,1,0,0),(4,68,'contact_no','vtiger_contactdetails',1,'4','contact_no','Contact Id',1,0,'',100,3,4,1,'V~O',3,NULL,'BAS',0,NULL,0,0,0),(4,69,'phone','vtiger_contactdetails',1,'11','phone','Office Phone',1,2,'',100,5,4,1,'V~O',2,4,'BAS',1,NULL,0,1,0),(4,70,'lastname','vtiger_contactdetails',1,'255','lastname','Last Name',1,0,'',100,2,4,1,'V~M',0,1,'BAS',1,NULL,1,0,0),(4,71,'mobile','vtiger_contactdetails',1,'11','mobile','Mobile',1,2,'',100,7,4,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(4,72,'accountid','vtiger_contactdetails',1,'51','account_id','Account Name',1,0,'',100,6,4,1,'I~O',2,3,'BAS',1,NULL,1,0,0),(4,73,'homephone','vtiger_contactsubdetails',1,'11','homephone','Home Phone',1,2,'',100,9,4,1,'V~O',1,NULL,'ADV',1,NULL,0,0,0),(4,74,'leadsource','vtiger_contactsubdetails',1,'15','leadsource','Lead Source',1,2,'',100,8,4,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(4,75,'otherphone','vtiger_contactsubdetails',1,'11','otherphone','Other Phone',1,2,'',100,11,4,1,'V~O',1,NULL,'ADV',1,NULL,0,0,0),(4,76,'title','vtiger_contactdetails',1,'1','title','Title',1,2,'',100,10,4,1,'V~O',1,NULL,'BAS',1,NULL,1,0,0),(4,77,'fax','vtiger_contactdetails',1,'11','fax','Fax',1,2,'',100,13,4,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(4,78,'department','vtiger_contactdetails',1,'1','department','Department',1,2,'',100,12,4,1,'V~O',1,NULL,'ADV',1,NULL,0,0,0),(4,79,'birthday','vtiger_contactsubdetails',1,'5','birthday','Birthdate',1,2,'',100,16,4,1,'D~O',1,NULL,'ADV',1,NULL,0,0,0),(4,80,'email','vtiger_contactdetails',1,'13','email','Email',1,2,'',100,15,4,1,'E~O',2,5,'BAS',1,NULL,0,1,0),(4,81,'reportsto','vtiger_contactdetails',1,'57','contact_id','Reports To',1,2,'',100,18,4,1,'V~O',1,NULL,'ADV',0,NULL,0,0,0),(4,82,'assistant','vtiger_contactsubdetails',1,'1','assistant','Assistant',1,2,'',100,17,4,1,'V~O',1,NULL,'ADV',1,NULL,0,0,0),(4,83,'secondaryemail','vtiger_contactdetails',1,'13','secondaryemail','Secondary Email',1,2,'',100,20,4,1,'E~O',1,NULL,'ADV',1,NULL,0,0,0),(4,84,'assistantphone','vtiger_contactsubdetails',1,'11','assistantphone','Assistant Phone',1,2,'',100,19,4,1,'V~O',1,NULL,'ADV',1,NULL,0,0,0),(4,85,'donotcall','vtiger_contactdetails',1,'56','donotcall','Do Not Call',1,2,'',100,22,4,1,'C~O',1,NULL,'ADV',1,NULL,0,0,0),(4,86,'emailoptout','vtiger_contactdetails',1,'56','emailoptout','Email Opt Out',1,0,'',100,21,4,1,'C~O',1,NULL,'ADV',1,NULL,0,0,0),(4,87,'smownerid','vtiger_contactdetails',1,'53','assigned_user_id','Assigned To',1,0,'',100,24,4,1,'V~M',0,6,'BAS',1,NULL,1,0,0),(4,88,'reference','vtiger_contactdetails',1,'56','reference','Reference',1,2,'',10,23,4,1,'C~O',1,NULL,'ADV',1,NULL,0,0,0),(4,89,'notify_owner','vtiger_contactdetails',1,'56','notify_owner','Notify Owner',1,2,'',10,26,4,1,'C~O',1,NULL,'ADV',1,NULL,0,0,0),(4,90,'createdtime','vtiger_contactdetails',1,'70','createdtime','Created Time',1,0,'',100,25,4,2,'DT~O',3,NULL,'BAS',0,NULL,0,0,0),(4,91,'modifiedtime','vtiger_contactdetails',1,'70','modifiedtime','Modified Time',1,0,'',100,27,4,2,'DT~O',3,NULL,'BAS',0,NULL,0,0,0),(4,92,'modifiedby','vtiger_contactdetails',1,'52','modifiedby','Last Modified By',1,0,'',100,28,4,3,'V~O',3,NULL,'BAS',0,NULL,0,0,0),(4,93,'portal','vtiger_customerdetails',1,'56','portal','Portal User',1,2,'',100,1,6,1,'C~O',1,NULL,'ADV',1,NULL,0,0,0),(4,94,'support_start_date','vtiger_customerdetails',1,'5','support_start_date','Support Start Date',1,2,'',100,2,6,1,'D~O',1,NULL,'ADV',1,NULL,0,0,0),(4,95,'support_end_date','vtiger_customerdetails',1,'5','support_end_date','Support End Date',1,2,'',100,3,6,1,'D~O~OTH~GE~support_start_date~Support Start Date',1,NULL,'ADV',1,NULL,0,0,0),(4,96,'mailingstreet','vtiger_contactaddress',1,'21','mailingstreet','Mailing Street',1,2,'',100,9,7,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(4,97,'otherstreet','vtiger_contactaddress',1,'21','otherstreet','Other Street',1,2,'',100,10,7,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(4,98,'mailingcity','vtiger_contactaddress',1,'1','mailingcity','Mailing City',1,2,'',100,7,7,1,'V~O',1,NULL,'BAS',1,NULL,1,0,0),(4,99,'othercity','vtiger_contactaddress',1,'1','othercity','Other City',1,2,'',100,8,7,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(4,100,'mailingstate','vtiger_contactaddress',1,'1','mailingstate','Mailing State',1,2,'',100,5,7,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(4,101,'otherstate','vtiger_contactaddress',1,'1','otherstate','Other State',1,2,'',100,6,7,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(4,102,'mailingzip','vtiger_contactaddress',1,'1','mailingzip','Mailing Zip',1,2,'',100,3,7,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(4,103,'otherzip','vtiger_contactaddress',1,'1','otherzip','Other Zip',1,2,'',100,4,7,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(4,104,'mailingcountry','vtiger_contactaddress',1,'1','mailingcountry','Mailing Country',1,1,'',100,1,7,1,'V~O',1,NULL,'BAS',1,NULL,1,0,0),(4,105,'othercountry','vtiger_contactaddress',1,'1','othercountry','Other Country',1,1,'',100,2,7,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(4,106,'mailingpobox','vtiger_contactaddress',1,'1','mailingpobox','Mailing Po Box',1,1,'',100,11,7,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(4,107,'otherpobox','vtiger_contactaddress',1,'1','otherpobox','Other Po Box',1,1,'',100,12,7,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(4,108,'imagename','vtiger_contactdetails',1,'69','imagename','Contact Image',1,2,'',100,1,73,1,'V~O',3,NULL,'ADV',0,NULL,0,0,0),(4,109,'description','vtiger_contactdetails',1,'19','description','Description',1,2,'',100,1,8,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(2,110,'potentialname','vtiger_potential',1,'2','potentialname','Potential Name',1,0,'',100,1,1,1,'V~M',0,1,'BAS',1,NULL,1,0,0),(2,111,'potential_no','vtiger_potential',1,'4','potential_no','Potential No',1,0,'',100,2,1,1,'V~O',3,NULL,'BAS',0,NULL,0,0,0),(2,112,'amount','vtiger_potential',1,'71','amount','Amount',1,2,'',100,5,1,1,'N~O',2,5,'BAS',1,NULL,0,1,0),(2,113,'related_to','vtiger_potential',1,'10','related_to','Related To',1,0,'',100,3,1,1,'V~O',0,2,'BAS',1,NULL,0,1,0),(2,114,'closingdate','vtiger_potential',1,'23','closingdate','Expected Close Date',1,2,'',100,8,1,1,'D~M',2,3,'BAS',1,NULL,1,0,0),(2,115,'potentialtype','vtiger_potential',1,'15','opportunity_type','Type',1,2,'',100,7,1,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(2,116,'nextstep','vtiger_potential',1,'1','nextstep','Next Step',1,2,'',100,10,1,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(2,117,'leadsource','vtiger_potential',1,'15','leadsource','Lead Source',1,2,'',100,9,1,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(2,118,'sales_stage','vtiger_potential',1,'15','sales_stage','Sales Stage',1,2,'',100,12,1,1,'V~M',2,4,'BAS',1,NULL,0,1,0),(2,119,'smownerid','vtiger_potential',1,'53','assigned_user_id','Assigned To',1,2,'',100,11,1,1,'V~M',0,6,'BAS',1,NULL,1,0,0),(2,120,'probability','vtiger_potential',1,'9','probability','Probability',1,2,'',100,14,1,1,'N~O',1,NULL,'BAS',1,NULL,0,0,0),(2,121,'campaignid','vtiger_potential',1,'58','campaignid','Campaign Source',1,2,'',100,13,1,1,'N~O',1,NULL,'BAS',1,NULL,0,0,0),(2,122,'createdtime','vtiger_potential',1,'70','createdtime','Created Time',1,0,'',100,16,1,2,'DT~O',3,NULL,'BAS',0,NULL,0,0,0),(2,123,'modifiedtime','vtiger_potential',1,'70','modifiedtime','Modified Time',1,0,'',100,15,1,2,'DT~O',3,NULL,'BAS',0,NULL,0,0,0),(2,124,'modifiedby','vtiger_potential',1,'52','modifiedby','Last Modified By',1,0,'',100,17,1,3,'V~O',3,NULL,'BAS',0,NULL,0,0,0),(2,125,'description','vtiger_potential',1,'19','description','Description',1,2,'',100,1,3,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(26,126,'campaignname','vtiger_campaign',1,'2','campaignname','Campaign Name',1,0,'',100,1,74,1,'V~M',0,1,'BAS',1,NULL,1,0,0),(26,127,'campaign_no','vtiger_campaign',1,'4','campaign_no','Campaign No',1,0,'',100,2,74,1,'V~O',3,NULL,'BAS',0,NULL,0,0,0),(26,128,'campaigntype','vtiger_campaign',1,'15','campaigntype','Campaign Type',1,2,'',100,5,74,1,'V~O',2,3,'BAS',1,NULL,1,0,0),(26,129,'product_id','vtiger_campaign',1,'59','product_id','Product',1,2,'',100,6,74,1,'I~O',2,5,'BAS',1,NULL,0,0,0),(26,130,'campaignstatus','vtiger_campaign',1,'15','campaignstatus','Campaign Status',1,2,'',100,4,74,1,'V~O',2,6,'BAS',1,NULL,1,0,0),(26,131,'closingdate','vtiger_campaign',1,'23','closingdate','Expected Close Date',1,2,'',100,8,74,1,'D~M',2,2,'BAS',1,NULL,1,0,0),(26,132,'smownerid','vtiger_campaign',1,'53','assigned_user_id','Assigned To',1,0,'',100,3,74,1,'V~M',0,7,'BAS',1,NULL,1,0,0),(26,133,'numsent','vtiger_campaign',1,'9','numsent','Num Sent',1,2,'',100,12,74,1,'N~O',1,NULL,'BAS',1,NULL,0,0,0),(26,134,'sponsor','vtiger_campaign',1,'1','sponsor','Sponsor',1,2,'',100,9,74,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(26,135,'targetaudience','vtiger_campaign',1,'1','targetaudience','Target Audience',1,2,'',100,7,74,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(26,136,'targetsize','vtiger_campaign',1,'1','targetsize','TargetSize',1,2,'',100,10,74,1,'I~O',1,NULL,'BAS',1,NULL,0,0,0),(26,137,'createdtime','vtiger_campaign',1,'70','createdtime','Created Time',1,0,'',100,11,74,2,'DT~O',3,NULL,'BAS',0,NULL,0,0,0),(26,138,'modifiedtime','vtiger_campaign',1,'70','modifiedtime','Modified Time',1,0,'',100,13,74,2,'DT~O',3,NULL,'BAS',0,NULL,0,0,0),(26,139,'modifiedby','vtiger_campaign',1,'52','modifiedby','Last Modified By',1,0,'',100,16,74,3,'V~O',3,NULL,'BAS',0,NULL,0,0,0),(26,140,'expectedresponse','vtiger_campaign',1,'15','expectedresponse','Expected Response',1,2,'',100,3,76,1,'V~O',2,4,'BAS',1,NULL,0,0,0),(26,141,'expectedrevenue','vtiger_campaign',1,'71','expectedrevenue','Expected Revenue',1,2,'',100,4,76,1,'N~O',1,NULL,'BAS',1,NULL,1,0,0),(26,142,'budgetcost','vtiger_campaign',1,'71','budgetcost','Budget Cost',1,2,'',100,1,76,1,'N~O',1,NULL,'BAS',1,NULL,0,0,0),(26,143,'actualcost','vtiger_campaign',1,'71','actualcost','Actual Cost',1,2,'',100,2,76,1,'N~O',1,NULL,'BAS',1,NULL,0,0,0),(26,144,'expectedresponsecount','vtiger_campaign',1,'1','expectedresponsecount','Expected Response Count',1,2,'',100,7,76,1,'I~O',1,NULL,'BAS',1,NULL,0,0,0),(26,145,'expectedsalescount','vtiger_campaign',1,'1','expectedsalescount','Expected Sales Count',1,2,'',100,5,76,1,'I~O',1,NULL,'BAS',1,NULL,0,0,0),(26,146,'expectedroi','vtiger_campaign',1,'71','expectedroi','Expected ROI',1,2,'',100,9,76,1,'N~O',1,NULL,'BAS',1,NULL,0,0,0),(26,147,'actualresponsecount','vtiger_campaign',1,'1','actualresponsecount','Actual Response Count',1,2,'',100,8,76,1,'I~O',1,NULL,'BAS',1,NULL,0,0,0),(26,148,'actualsalescount','vtiger_campaign',1,'1','actualsalescount','Actual Sales Count',1,2,'',100,6,76,1,'I~O',1,NULL,'BAS',1,NULL,0,0,0),(26,149,'actualroi','vtiger_campaign',1,'71','actualroi','Actual ROI',1,2,'',100,10,76,1,'N~O',1,NULL,'BAS',1,NULL,0,0,0),(26,150,'description','vtiger_campaign',1,'19','description','Description',1,2,'',100,1,81,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(4,151,'campaignrelstatus','vtiger_campaignrelstatus',1,'16','campaignrelstatus','Status',1,0,'0',100,1,NULL,1,'V~O',1,NULL,'BAS',0,NULL,0,0,0),(6,152,'campaignrelstatus','vtiger_campaignrelstatus',1,'16','campaignrelstatus','Status',1,0,'0',100,1,NULL,1,'V~O',1,NULL,'BAS',0,NULL,0,0,0),(7,153,'campaignrelstatus','vtiger_campaignrelstatus',1,'16','campaignrelstatus','Status',1,0,'0',100,1,NULL,1,'V~O',1,NULL,'BAS',0,NULL,0,0,0),(26,154,'campaignrelstatus','vtiger_campaignrelstatus',1,'16','campaignrelstatus','Status',1,0,'0',100,1,NULL,1,'V~O',1,NULL,'BAS',0,NULL,0,0,0),(13,155,'ticket_no','vtiger_troubletickets',1,'4','ticket_no','Ticket No',1,0,'',100,14,25,1,'V~O',3,NULL,'BAS',0,NULL,1,0,0),(13,156,'smownerid','vtiger_troubletickets',1,'53','assigned_user_id','Assigned To',1,0,'',100,5,25,1,'V~M',0,4,'BAS',1,NULL,1,0,0),(13,157,'parent_id','vtiger_troubletickets',1,'10','parent_id','Related To',1,0,'',100,2,25,1,'I~O',1,NULL,'BAS',1,NULL,1,0,0),(13,158,'priority','vtiger_troubletickets',1,'15','ticketpriorities','Priority',1,2,'',100,7,25,1,'V~M',2,3,'BAS',1,NULL,0,1,0),(13,159,'product_id','vtiger_troubletickets',1,'59','product_id','Product Name',1,2,'',100,6,25,1,'I~O',1,NULL,'BAS',1,NULL,0,0,0),(13,160,'severity','vtiger_troubletickets',1,'15','ticketseverities','Severity',1,2,'',100,9,25,1,'V~O',1,NULL,'BAS',1,NULL,1,0,0),(13,161,'status','vtiger_troubletickets',1,'15','ticketstatus','Status',1,2,'',100,8,25,1,'V~M',1,2,'BAS',1,NULL,1,0,0),(13,162,'category','vtiger_troubletickets',1,'15','ticketcategories','Category',1,2,'',100,11,25,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(13,163,'update_log','vtiger_troubletickets',1,'19','update_log','Update History',1,1,'',100,12,25,3,'V~O',1,NULL,'BAS',0,NULL,0,0,0),(13,164,'hours','vtiger_troubletickets',1,'1','hours','Hours',1,2,'',100,10,25,1,'N~O',1,NULL,'BAS',1,'This gives the estimated hours for the Ticket.<br>When the same ticket is added to a Service Contract,based on the Tracking Unit of the Service Contract,Used units is updated whenever a ticket is Closed.',0,0,0),(13,165,'days','vtiger_troubletickets',1,'1','days','Days',1,2,'',100,11,25,1,'N~O',1,NULL,'BAS',1,'This gives the estimated days for the Ticket.<br>When the same ticket is added to a Service Contract,based on the Tracking Unit of the Service Contract,Used units is updated whenever a ticket is Closed.',0,0,0),(13,166,'createdtime','vtiger_troubletickets',1,'70','createdtime','Created Time',1,0,'',100,10,25,2,'DT~O',3,NULL,'BAS',0,NULL,0,0,0),(13,167,'modifiedtime','vtiger_troubletickets',1,'70','modifiedtime','Modified Time',1,0,'',100,13,25,2,'DT~O',3,NULL,'BAS',0,NULL,0,0,0),(13,168,'from_portal','vtiger_ticketcf',1,'56','from_portal','From Portal',1,0,'',100,14,25,3,'C~O',3,NULL,'BAS',0,NULL,0,0,0),(13,169,'modifiedby','vtiger_troubletickets',1,'52','modifiedby','Last Modified By',1,0,'',100,17,25,3,'V~O',3,NULL,'BAS',0,NULL,0,0,0),(13,170,'title','vtiger_troubletickets',1,'22','ticket_title','Title',1,0,'',100,1,25,1,'V~M',0,1,'BAS',1,NULL,1,0,0),(13,171,'description','vtiger_troubletickets',1,'19','description','Description',1,2,'',100,1,28,1,'V~O',2,4,'BAS',1,NULL,1,0,0),(13,172,'solution','vtiger_troubletickets',1,'19','solution','Solution',1,0,'',100,1,29,1,'V~O',3,NULL,'BAS',0,NULL,0,0,0),(13,173,'comments','vtiger_ticketcomments',1,'19','comments','Add Comment',1,1,'',100,1,30,1,'V~O',3,NULL,'BAS',0,NULL,0,0,0),(14,174,'productname','vtiger_products',1,'2','productname','Product Name',1,0,'',100,1,31,1,'V~M',0,1,'BAS',1,NULL,1,0,0),(14,175,'product_no','vtiger_products',1,'4','product_no','Product No',1,0,'',100,2,31,1,'V~O',3,NULL,'BAS',0,NULL,0,1,0),(14,176,'productcode','vtiger_products',1,'1','productcode','Part Number',1,2,'',100,4,31,1,'V~O',1,NULL,'BAS',1,NULL,1,0,0),(14,177,'discontinued','vtiger_products',1,'56','discontinued','Product Active',1,2,'1',100,3,31,1,'V~O',2,2,'BAS',1,NULL,0,1,0),(14,178,'manufacturer','vtiger_products',1,'15','manufacturer','Manufacturer',1,2,'',100,6,31,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(14,179,'productcategory','vtiger_products',1,'15','productcategory','Product Category',1,2,'',100,6,31,1,'V~O',1,NULL,'BAS',1,NULL,0,1,0),(14,180,'sales_start_date','vtiger_products',1,'5','sales_start_date','Sales Start Date',1,2,'',100,5,31,1,'D~O',1,NULL,'BAS',1,NULL,0,0,0),(14,181,'sales_end_date','vtiger_products',1,'5','sales_end_date','Sales End Date',1,2,'',100,8,31,1,'D~O~OTH~GE~sales_start_date~Sales Start Date',1,NULL,'BAS',1,NULL,0,0,0),(14,182,'start_date','vtiger_products',1,'5','start_date','Support Start Date',1,2,'',100,7,31,1,'D~O',1,NULL,'BAS',1,NULL,0,0,0),(14,183,'expiry_date','vtiger_products',1,'5','expiry_date','Support Expiry Date',1,2,'',100,10,31,1,'D~O~OTH~GE~start_date~Start Date',1,NULL,'BAS',1,NULL,0,0,0),(14,184,'website','vtiger_products',1,'17','website','Website',1,2,'',100,14,31,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(14,185,'vendor_id','vtiger_products',1,'75','vendor_id','Vendor Name',1,2,'',100,13,31,1,'I~O',1,NULL,'BAS',1,NULL,0,0,0),(14,186,'mfr_part_no','vtiger_products',1,'1','mfr_part_no','Mfr PartNo',1,2,'',100,16,31,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(14,187,'vendor_part_no','vtiger_products',1,'1','vendor_part_no','Vendor PartNo',1,2,'',100,15,31,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(14,188,'serialno','vtiger_products',1,'1','serial_no','Serial No',1,2,'',100,18,31,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(14,189,'productsheet','vtiger_products',1,'1','productsheet','Product Sheet',1,2,'',100,17,31,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(14,190,'glacct','vtiger_products',1,'15','glacct','GL Account',1,2,'',100,20,31,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(14,191,'createdtime','vtiger_products',1,'70','createdtime','Created Time',1,0,'',100,19,31,2,'DT~O',3,NULL,'BAS',0,NULL,0,0,0),(14,192,'modifiedtime','vtiger_products',1,'70','modifiedtime','Modified Time',1,0,'',100,21,31,2,'DT~O',3,NULL,'BAS',0,NULL,0,0,0),(14,193,'modifiedby','vtiger_products',1,'52','modifiedby','Last Modified By',1,0,'',100,22,31,3,'V~O',3,NULL,'BAS',0,NULL,0,0,0),(14,194,'unit_price','vtiger_products',1,'72','unit_price','Unit Price',1,0,'',100,1,32,1,'N~O',2,3,'BAS',0,NULL,1,0,0),(14,195,'commissionrate','vtiger_products',1,'9','commissionrate','Commission Rate',1,2,'',100,2,32,1,'N~O',1,NULL,'BAS',1,NULL,1,0,0),(14,196,'taxclass','vtiger_products',1,'83','taxclass','Taxes',1,2,'',100,4,32,1,'V~O',2,NULL,'BAS',1,NULL,0,0,0),(14,197,'usageunit','vtiger_products',1,'15','usageunit','Usage Unit',1,2,'',100,1,33,1,'V~O',1,NULL,'ADV',1,NULL,0,0,0),(14,198,'qty_per_unit','vtiger_products',1,'1','qty_per_unit','Qty/Unit',1,2,'',100,2,33,1,'N~O',1,NULL,'ADV',1,NULL,1,0,0),(14,199,'qtyinstock','vtiger_products',1,'1','qtyinstock','Qty In Stock',1,2,'',100,3,33,1,'NN~O',0,4,'ADV',1,NULL,0,1,0),(14,200,'reorderlevel','vtiger_products',1,'1','reorderlevel','Reorder Level',1,2,'',100,4,33,1,'I~O',1,NULL,'ADV',1,NULL,0,0,0),(14,201,'smownerid','vtiger_products',1,'53','assigned_user_id','Handler',1,0,'',100,5,33,1,'V~M',0,5,'BAS',1,NULL,0,0,0),(14,202,'qtyindemand','vtiger_products',1,'1','qtyindemand','Qty In Demand',1,2,'',100,6,33,1,'I~O',1,NULL,'ADV',1,NULL,0,0,0),(14,203,'imagename','vtiger_products',1,'69','imagename','Product Image',1,2,'',100,1,35,1,'V~O',3,NULL,'ADV',0,NULL,0,0,0),(14,204,'description','vtiger_products',1,'19','description','Description',1,2,'',100,1,36,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(8,205,'title','vtiger_notes',1,'2','notes_title','Title',1,0,'',100,1,17,1,'V~M',0,1,'BAS',1,NULL,1,0,0),(8,206,'createdtime','vtiger_notes',1,'70','createdtime','Created Time',1,0,'',100,5,17,2,'DT~O',3,NULL,'BAS',0,NULL,0,0,0),(8,207,'modifiedtime','vtiger_notes',1,'70','modifiedtime','Modified Time',1,0,'',100,6,17,2,'DT~O',3,NULL,'BAS',0,NULL,1,0,0),(8,208,'filename','vtiger_notes',1,'28','filename','File Name',1,2,'',100,3,18,1,'V~O',0,NULL,'BAS',0,NULL,1,0,0),(8,209,'smownerid','vtiger_notes',1,'53','assigned_user_id','Assigned To',1,0,'',100,4,17,1,'V~M',0,3,'BAS',1,NULL,1,0,0),(8,210,'notecontent','vtiger_notes',1,'19','notecontent','Note',1,2,'',100,1,84,1,'V~O',1,NULL,'BAS',0,NULL,0,0,0),(8,211,'filetype','vtiger_notes',1,'1','filetype','File Type',1,2,NULL,100,5,18,2,'V~O',3,NULL,'BAS',0,NULL,0,0,0),(8,212,'filesize','vtiger_notes',1,'1','filesize','File Size',1,2,NULL,100,4,18,2,'I~O',3,NULL,'BAS',0,NULL,0,0,0),(8,213,'filelocationtype','vtiger_notes',1,'27','filelocationtype','Download Type',1,0,NULL,100,1,18,1,'V~O',0,NULL,'BAS',0,NULL,0,0,0),(8,214,'fileversion','vtiger_notes',1,'1','fileversion','Version',1,2,NULL,100,6,18,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(8,215,'filestatus','vtiger_notes',1,'56','filestatus','Active',1,2,'1',100,2,18,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(8,216,'filedownloadcount','vtiger_notes',1,'1','filedownloadcount','Download Count',1,2,NULL,100,7,18,2,'I~O',3,NULL,'BAS',0,NULL,0,0,0),(8,217,'folderid','vtiger_notes',1,'26','folderid','Folder Name',1,2,'',100,2,17,1,'V~O',2,2,'BAS',1,NULL,1,0,0),(8,218,'note_no','vtiger_notes',1,'4','note_no','Document No',1,0,'',100,3,17,1,'V~O',3,NULL,'BAS',0,NULL,0,0,0),(8,219,'modifiedby','vtiger_notes',1,'52','modifiedby','Last Modified By',1,0,'',100,12,17,3,'V~O',3,NULL,'BAS',0,NULL,0,0,0),(10,220,'date_start','vtiger_activity',1,'6','date_start','Date & Time Sent',1,0,'',100,1,21,1,'DT~M~time_start~Time Start',1,NULL,'BAS',1,NULL,0,0,0),(10,221,'semodule','vtiger_activity',1,'2','parent_type','Sales Enity Module',1,0,'',100,2,21,3,'',1,NULL,'BAS',1,NULL,0,0,0),(10,222,'activitytype','vtiger_activity',1,'2','activitytype','Activtiy Type',1,0,'',100,3,21,3,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(10,223,'smownerid','vtiger_activity',1,'53','assigned_user_id','Assigned To',1,0,'',100,5,21,1,'V~M',1,NULL,'BAS',1,NULL,0,0,0),(10,224,'subject','vtiger_activity',1,'2','subject','Subject',1,0,'',100,1,23,1,'V~M',1,NULL,'BAS',1,NULL,0,0,0),(10,225,'name','vtiger_attachments',1,'61','filename','Attachment',1,0,'',100,2,23,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(10,226,'description','vtiger_activity',1,'19','description','Description',1,0,'',100,1,24,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(10,227,'time_start','vtiger_activity',1,'2','time_start','Time Start',1,0,'',100,9,23,1,'T~O',1,NULL,'BAS',1,NULL,0,0,0),(10,228,'createdtime','vtiger_activity',1,'70','createdtime','Created Time',1,0,'',100,10,22,1,'DT~O',3,NULL,'BAS',0,NULL,0,0,0),(10,229,'modifiedtime','vtiger_activity',1,'70','modifiedtime','Modified Time',1,0,'',100,11,21,2,'DT~O',3,NULL,'BAS',0,NULL,0,0,0),(10,230,'access_count','vtiger_email_track',1,'25','access_count','Access Count',1,0,'0',100,6,21,3,'I~O',1,NULL,'BAS',0,NULL,0,0,0),(10,231,'modifiedby','vtiger_activity',1,'52','modifiedby','Last Modified By',1,0,'',100,12,21,3,'V~O',3,NULL,'BAS',0,NULL,0,0,0),(9,232,'subject','vtiger_activity',1,'2','subject','Subject',1,0,'',100,1,19,1,'V~M',0,1,'BAS',1,NULL,1,0,0),(9,233,'smownerid','vtiger_activity',1,'53','assigned_user_id','Assigned To',1,0,'',100,2,19,1,'V~M',0,4,'BAS',1,NULL,1,0,0),(9,234,'date_start','vtiger_activity',1,'6','date_start','Start Date & Time',1,0,'',100,3,19,1,'DT~M~time_start',0,2,'BAS',1,NULL,1,0,0),(9,235,'time_start','vtiger_activity',1,'2','time_start','Time Start',1,0,'',100,4,19,3,'T~M',1,NULL,'BAS',1,NULL,1,0,0),(9,236,'time_end','vtiger_activity',1,'2','time_end','End Time',1,0,'',100,4,19,3,'T~O',1,NULL,'BAS',1,NULL,1,0,0),(9,237,'due_date','vtiger_activity',1,'23','due_date','Due Date',1,0,'',100,5,19,1,'D~M~OTH~GE~date_start~Start Date & Time',1,NULL,'BAS',1,NULL,1,0,0),(9,238,'crmid','vtiger_seactivityrel',1,'66','parent_id','Related To',1,0,'',100,7,19,1,'I~O',1,NULL,'BAS',1,NULL,1,0,0),(9,239,'contactid','vtiger_cntactivityrel',1,'57','contact_id','Contact Name',1,0,'',100,8,19,1,'I~O',1,NULL,'BAS',1,NULL,0,0,0),(9,240,'status','vtiger_activity',1,'15','taskstatus','Status',1,0,'',100,8,19,1,'V~M',0,3,'BAS',1,NULL,0,0,0),(9,241,'eventstatus','vtiger_activity',1,'15','eventstatus','Status',1,0,'',100,9,19,3,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(9,242,'priority','vtiger_activity',1,'15','taskpriority','Priority',1,0,'',100,10,19,1,'V~O',2,NULL,'BAS',1,NULL,0,0,0),(9,243,'sendnotification','vtiger_activity',1,'56','sendnotification','Send Notification',1,0,'',100,11,19,1,'C~O',1,NULL,'BAS',1,NULL,0,0,0),(9,244,'createdtime','vtiger_activity',1,'70','createdtime','Created Time',1,0,'',100,14,19,2,'DT~O',3,NULL,'BAS',0,NULL,0,0,0),(9,245,'modifiedtime','vtiger_activity',1,'70','modifiedtime','Modified Time',1,0,'',100,15,19,2,'DT~O',3,NULL,'BAS',0,NULL,0,0,0),(9,246,'activitytype','vtiger_activity',1,'15','activitytype','Activity Type',1,0,'',100,16,19,3,'V~O',1,NULL,'BAS',1,NULL,1,0,0),(9,247,'visibility','vtiger_activity',1,'16','visibility','Visibility',1,0,'Private',100,17,19,1,'V~O',2,NULL,'BAS',1,NULL,0,0,0),(9,248,'description','vtiger_activity',1,'19','description','Description',1,0,'',100,1,20,1,'V~O',1,NULL,'BAS',1,NULL,1,0,0),(9,249,'duration_hours','vtiger_activity',1,'63','duration_hours','Duration',1,0,'',100,17,19,3,'T~O',1,NULL,'BAS',1,NULL,0,0,0),(9,250,'duration_minutes','vtiger_activity',1,'16','duration_minutes','Duration Minutes',1,0,'',100,18,19,3,'T~O',1,NULL,'BAS',1,NULL,0,0,0),(9,251,'location','vtiger_activity',1,'1','location','Location',1,0,'',100,19,19,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(9,252,'reminder_time','vtiger_activity_reminder',1,'30','reminder_time','Send Reminder',1,0,'',100,1,119,1,'I~O',1,NULL,'BAS',1,NULL,0,0,0),(9,253,'recurringtype','vtiger_activity',1,'16','recurringtype','Recurrence',1,0,'',100,6,19,3,'O~O',1,NULL,'BAS',1,NULL,0,0,0),(9,254,'notime','vtiger_activity',1,'56','notime','No Time',1,0,'',100,20,19,3,'C~O',1,NULL,'BAS',1,NULL,0,0,0),(9,255,'modifiedby','vtiger_activity',1,'52','modifiedby','Last Modified By',1,0,'',100,22,19,3,'V~O',3,NULL,'BAS',0,NULL,0,0,0),(16,256,'subject','vtiger_activity',1,'2','subject','Subject',1,0,'',100,1,39,1,'V~M',0,1,'BAS',1,NULL,1,0,0),(16,257,'smownerid','vtiger_activity',1,'53','assigned_user_id','Assigned To',1,0,'',100,2,39,1,'V~M',0,6,'BAS',1,NULL,1,0,0),(16,258,'date_start','vtiger_activity',1,'6','date_start','Start Date & Time',1,0,'',100,3,39,1,'DT~M~time_start',0,2,'BAS',1,NULL,1,0,0),(16,259,'time_start','vtiger_activity',1,'2','time_start','Time Start',1,0,'',100,4,39,3,'T~M',1,NULL,'BAS',1,NULL,1,0,0),(16,260,'due_date','vtiger_activity',1,'23','due_date','End Date',1,0,'',100,5,39,1,'D~M~OTH~GE~date_start~Start Date & Time',0,5,'BAS',1,NULL,1,0,0),(16,261,'time_end','vtiger_activity',1,'2','time_end','End Time',1,0,'',100,5,39,3,'T~M',1,NULL,'BAS',1,NULL,1,0,0),(16,262,'recurringtype','vtiger_activity',1,'16','recurringtype','Recurrence',1,0,'',100,6,116,1,'O~O',1,NULL,'BAS',1,NULL,0,0,0),(16,263,'duration_hours','vtiger_activity',1,'63','duration_hours','Duration',1,0,'',100,7,39,3,'I~M',1,NULL,'BAS',1,NULL,0,0,0),(16,264,'duration_minutes','vtiger_activity',1,'16','duration_minutes','Duration Minutes',1,0,'',100,8,39,3,'O~O',1,NULL,'BAS',1,NULL,0,0,0),(16,265,'crmid','vtiger_seactivityrel',1,'66','parent_id','Related To',1,0,'',100,9,118,1,'I~O',2,NULL,'BAS',1,NULL,1,0,0),(16,266,'eventstatus','vtiger_activity',1,'15','eventstatus','Status',1,0,'',100,10,39,1,'V~M',0,3,'BAS',1,NULL,0,0,0),(16,267,'sendnotification','vtiger_activity',1,'56','sendnotification','Send Notification',1,0,'',100,11,39,1,'C~O',2,NULL,'BAS',1,NULL,0,0,0),(16,268,'activitytype','vtiger_activity',1,'15','activitytype','Activity Type',1,0,'',100,12,39,1,'V~M',0,4,'BAS',1,NULL,1,0,0),(16,269,'location','vtiger_activity',1,'1','location','Location',1,0,'',100,13,39,1,'V~O',2,NULL,'BAS',1,NULL,0,0,0),(16,270,'createdtime','vtiger_activity',1,'70','createdtime','Created Time',1,0,'',100,14,39,2,'DT~O',3,NULL,'BAS',0,NULL,0,0,0),(16,271,'modifiedtime','vtiger_activity',1,'70','modifiedtime','Modified Time',1,0,'',100,15,39,2,'DT~O',3,NULL,'BAS',0,NULL,0,0,0),(16,272,'priority','vtiger_activity',1,'15','taskpriority','Priority',1,0,'',100,16,39,1,'V~O',2,NULL,'BAS',1,NULL,0,0,0),(16,273,'notime','vtiger_activity',1,'56','notime','No Time',1,0,'',100,17,39,3,'C~O',1,NULL,'BAS',1,NULL,0,0,0),(16,274,'visibility','vtiger_activity',1,'16','visibility','Visibility',1,0,'',100,18,39,1,'V~O',2,NULL,'BAS',1,NULL,0,0,0),(16,275,'modifiedby','vtiger_activity',1,'52','modifiedby','Last Modified By',1,0,'',100,22,39,3,'V~O',3,NULL,'BAS',0,NULL,0,0,0),(16,276,'description','vtiger_activity',1,'19','description','Description',1,0,'',100,1,41,1,'V~O',2,NULL,'BAS',1,NULL,1,0,0),(16,277,'reminder_time','vtiger_activity_reminder',1,'30','reminder_time','Send Reminder',1,0,'',100,1,40,1,'I~O',2,NULL,'BAS',1,NULL,0,0,0),(16,278,'contactid','vtiger_cntactivityrel',1,'57','contact_id','Contact Name',1,0,'',100,1,118,1,'I~O',2,NULL,'BAS',1,NULL,0,0,0),(15,279,'product_id','vtiger_faq',1,'59','product_id','Product Name',1,2,'',100,1,37,1,'I~O',3,NULL,'BAS',1,NULL,1,0,0),(15,280,'faq_no','vtiger_faq',1,'4','faq_no','Faq No',1,0,'',100,2,37,1,'V~O',3,NULL,'BAS',0,NULL,0,0,0),(15,281,'category','vtiger_faq',1,'15','faqcategories','Category',1,2,'',100,4,37,1,'V~O',3,NULL,'BAS',1,NULL,1,0,0),(15,282,'status','vtiger_faq',1,'15','faqstatus','Status',1,2,'',100,3,37,1,'V~M',3,NULL,'BAS',1,NULL,0,0,0),(15,283,'question','vtiger_faq',1,'20','question','Question',1,2,'',100,7,37,1,'V~M',3,NULL,'BAS',1,NULL,1,0,0),(15,284,'answer','vtiger_faq',1,'20','faq_answer','Answer',1,2,'',100,8,37,1,'V~M',3,NULL,'BAS',1,NULL,0,0,0),(15,285,'comments','vtiger_faqcomments',1,'19','comments','Add Comment',1,1,'',100,1,38,1,'V~O',3,NULL,'BAS',0,NULL,0,0,0),(15,286,'createdtime','vtiger_faq',1,'70','createdtime','Created Time',1,0,'',100,5,37,2,'DT~O',3,NULL,'BAS',0,NULL,1,0,0),(15,287,'modifiedtime','vtiger_faq',1,'70','modifiedtime','Modified Time',1,0,'',100,6,37,2,'DT~O',3,NULL,'BAS',0,NULL,1,0,0),(15,288,'modifiedby','vtiger_faq',1,'52','modifiedby','Last Modified By',1,0,'',100,7,37,3,'V~O',3,NULL,'BAS',0,NULL,0,0,0),(18,289,'vendorname','vtiger_vendor',1,'2','vendorname','Vendor Name',1,0,'',100,1,42,1,'V~M',0,1,'BAS',1,NULL,1,0,0),(18,290,'vendor_no','vtiger_vendor',1,'4','vendor_no','Vendor No',1,0,'',100,2,42,1,'V~O',3,NULL,'BAS',0,NULL,0,0,0),(18,291,'phone','vtiger_vendor',1,'1','phone','Phone',1,2,'',100,4,42,1,'V~O',2,2,'BAS',1,NULL,0,1,0),(18,292,'email','vtiger_vendor',1,'13','email','Email',1,2,'',100,3,42,1,'E~O',2,3,'BAS',1,NULL,0,1,0),(18,293,'website','vtiger_vendor',1,'17','website','Website',1,2,'',100,6,42,1,'V~O',1,NULL,'BAS',1,NULL,0,1,0),(18,294,'glacct','vtiger_vendor',1,'15','glacct','GL Account',1,2,'',100,5,42,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(18,295,'category','vtiger_vendor',1,'1','category','Category',1,2,'',100,8,42,1,'V~O',1,NULL,'BAS',1,NULL,1,0,0),(18,296,'createdtime','vtiger_vendor',1,'70','createdtime','Created Time',1,0,'',100,7,42,2,'DT~O',3,NULL,'BAS',0,NULL,0,0,0),(18,297,'modifiedtime','vtiger_vendor',1,'70','modifiedtime','Modified Time',1,0,'',100,9,42,2,'DT~O',3,NULL,'BAS',0,NULL,0,0,0),(18,298,'modifiedby','vtiger_vendor',1,'52','modifiedby','Last Modified By',1,0,'',100,12,42,3,'V~O',3,NULL,'BAS',0,NULL,0,0,0),(18,299,'street','vtiger_vendor',1,'21','street','Street',1,2,'',100,5,44,1,'V~O',1,NULL,'ADV',1,NULL,0,0,0),(18,300,'pobox','vtiger_vendor',1,'1','pobox','Po Box',1,1,'',100,6,44,1,'V~O',1,NULL,'ADV',1,NULL,0,0,0),(18,301,'city','vtiger_vendor',1,'1','city','City',1,2,'',100,4,44,1,'V~O',1,NULL,'ADV',1,NULL,0,0,0),(18,302,'state','vtiger_vendor',1,'1','state','State',1,2,'',100,3,44,1,'V~O',1,NULL,'ADV',1,NULL,0,0,0),(18,303,'postalcode','vtiger_vendor',1,'1','postalcode','Postal Code',1,2,'',100,2,44,1,'V~O',1,NULL,'ADV',1,NULL,0,0,0),(18,304,'country','vtiger_vendor',1,'1','country','Country',1,1,'',100,1,44,1,'V~O',1,NULL,'ADV',1,NULL,0,0,0),(18,305,'description','vtiger_vendor',1,'19','description','Description',1,2,'',100,1,45,1,'V~O',1,NULL,'ADV',1,NULL,0,0,0),(19,306,'bookname','vtiger_pricebook',1,'2','bookname','Price Book Name',1,0,'',100,1,46,1,'V~M',0,1,'BAS',1,NULL,1,0,0),(19,307,'pricebook_no','vtiger_pricebook',1,'4','pricebook_no','PriceBook No',1,0,'',100,3,46,1,'V~O',3,NULL,'BAS',0,NULL,0,0,0),(19,308,'active','vtiger_pricebook',1,'56','active','Active',1,2,'1',100,2,46,1,'C~O',2,2,'BAS',1,NULL,1,0,0),(19,309,'createdtime','vtiger_pricebook',1,'70','createdtime','Created Time',1,0,'',100,4,46,2,'DT~O',3,NULL,'BAS',0,NULL,0,0,0),(19,310,'modifiedtime','vtiger_pricebook',1,'70','modifiedtime','Modified Time',1,0,'',100,5,46,2,'DT~O',3,NULL,'BAS',0,NULL,0,0,0),(19,311,'currency_id','vtiger_pricebook',1,'117','currency_id','Currency',1,0,'',100,5,46,1,'I~M',0,3,'BAS',0,NULL,0,0,0),(19,312,'modifiedby','vtiger_pricebook',1,'52','modifiedby','Last Modified By',1,0,'',100,7,46,3,'V~O',3,NULL,'BAS',0,NULL,0,0,0),(19,313,'description','vtiger_pricebook',1,'19','description','Description',1,2,'',100,1,48,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(20,314,'quote_no','vtiger_quotes',1,'4','quote_no','Quote No',1,0,'',100,3,49,1,'V~O',3,NULL,'BAS',0,NULL,1,0,0),(20,315,'subject','vtiger_quotes',1,'2','subject','Subject',1,0,'',100,1,49,1,'V~M',1,NULL,'BAS',1,NULL,1,0,0),(20,316,'potentialid','vtiger_quotes',1,'76','potential_id','Potential Name',1,2,'',100,2,49,1,'I~O',3,NULL,'BAS',1,NULL,1,0,0),(20,317,'quotestage','vtiger_quotes',1,'15','quotestage','Quote Stage',1,2,'',100,4,49,1,'V~M',3,NULL,'BAS',1,NULL,0,1,0),(20,318,'validtill','vtiger_quotes',1,'5','validtill','Valid Till',1,2,'',100,5,49,1,'D~O',3,NULL,'BAS',1,NULL,0,0,0),(20,319,'contactid','vtiger_quotes',1,'57','contact_id','Contact Name',1,2,'',100,6,49,1,'V~O',3,NULL,'BAS',1,NULL,0,1,0),(20,320,'carrier','vtiger_quotes',1,'15','carrier','Carrier',1,2,'',100,8,49,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(20,321,'subtotal','vtiger_quotes',1,'72','hdnSubTotal','Sub Total',1,2,'',100,9,49,3,'N~O',3,NULL,'BAS',1,NULL,0,0,0),(20,322,'shipping','vtiger_quotes',1,'1','shipping','Shipping',1,2,'',100,10,49,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(20,323,'inventorymanager','vtiger_quotes',1,'77','assigned_user_id1','Inventory Manager',1,2,'',100,11,49,1,'I~O',3,NULL,'BAS',1,NULL,0,0,0),(20,324,'adjustment','vtiger_quotes',1,'72','txtAdjustment','Adjustment',1,2,'',100,20,49,3,'NN~O',3,NULL,'BAS',1,NULL,0,0,0),(20,325,'total','vtiger_quotes',1,'72','hdnGrandTotal','Total',1,2,'',100,14,49,3,'N~O',3,NULL,'BAS',1,NULL,0,1,0),(20,326,'taxtype','vtiger_quotes',1,'16','hdnTaxType','Tax Type',1,2,'',100,14,49,3,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(20,327,'discount_percent','vtiger_quotes',1,'1','hdnDiscountPercent','Discount Percent',1,2,'',100,14,115,5,'N~O',3,NULL,'BAS',1,NULL,0,0,0),(20,328,'discount_amount','vtiger_quotes',1,'72','hdnDiscountAmount','Discount Amount',1,2,'',100,14,115,5,'N~O',3,NULL,'BAS',1,NULL,0,0,0),(20,329,'s_h_amount','vtiger_quotes',1,'72','hdnS_H_Amount','S&H Amount',1,2,'',100,14,49,3,'N~O',3,NULL,'BAS',1,NULL,0,0,0),(20,330,'accountid','vtiger_quotes',1,'73','account_id','Account Name',1,2,'',100,16,49,1,'I~M',3,NULL,'BAS',1,NULL,0,1,0),(20,331,'smownerid','vtiger_quotes',1,'53','assigned_user_id','Assigned To',1,0,'',100,17,49,1,'V~M',3,NULL,'BAS',1,NULL,1,0,0),(20,332,'createdtime','vtiger_quotes',1,'70','createdtime','Created Time',1,0,'',100,18,49,2,'DT~O',3,NULL,'BAS',0,NULL,0,0,0),(20,333,'modifiedtime','vtiger_quotes',1,'70','modifiedtime','Modified Time',1,0,'',100,19,49,2,'DT~O',3,NULL,'BAS',0,NULL,0,0,0),(20,334,'modifiedby','vtiger_quotes',1,'52','modifiedby','Last Modified By',1,0,'',100,22,49,3,'V~O',3,NULL,'BAS',0,NULL,0,0,0),(20,335,'currency_id','vtiger_quotes',1,'117','currency_id','Currency',1,2,'1',100,20,49,3,'I~O',3,NULL,'BAS',1,NULL,0,0,0),(20,336,'conversion_rate','vtiger_quotes',1,'1','conversion_rate','Conversion Rate',1,2,'1',100,21,49,3,'N~O',3,NULL,'BAS',1,NULL,0,0,0),(20,337,'bill_street','vtiger_quotesbillads',1,'24','bill_street','Billing Address',1,2,'',100,9,51,1,'V~M',3,NULL,'BAS',1,NULL,0,0,0),(20,338,'ship_street','vtiger_quotesshipads',1,'24','ship_street','Shipping Address',1,2,'',100,10,51,1,'V~M',3,NULL,'BAS',1,NULL,0,0,0),(20,339,'bill_city','vtiger_quotesbillads',1,'1','bill_city','Billing City',1,2,'',100,7,51,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(20,340,'ship_city','vtiger_quotesshipads',1,'1','ship_city','Shipping City',1,2,'',100,8,51,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(20,341,'bill_state','vtiger_quotesbillads',1,'1','bill_state','Billing State',1,2,'',100,5,51,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(20,342,'ship_state','vtiger_quotesshipads',1,'1','ship_state','Shipping State',1,2,'',100,6,51,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(20,343,'bill_code','vtiger_quotesbillads',1,'1','bill_code','Billing Code',1,2,'',100,3,51,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(20,344,'ship_code','vtiger_quotesshipads',1,'1','ship_code','Shipping Code',1,2,'',100,4,51,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(20,345,'bill_country','vtiger_quotesbillads',1,'1','bill_country','Billing Country',1,1,'',100,1,51,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(20,346,'ship_country','vtiger_quotesshipads',1,'1','ship_country','Shipping Country',1,1,'',100,2,51,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(20,347,'bill_pobox','vtiger_quotesbillads',1,'1','bill_pobox','Billing Po Box',1,1,'',100,11,51,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(20,348,'ship_pobox','vtiger_quotesshipads',1,'1','ship_pobox','Shipping Po Box',1,1,'',100,12,51,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(20,349,'description','vtiger_quotes',1,'19','description','Description',1,2,'',100,1,54,1,'V~O',3,NULL,'ADV',1,NULL,0,0,0),(20,350,'terms_conditions','vtiger_quotes',1,'19','terms_conditions','Terms & Conditions',1,2,'',100,1,53,1,'V~O',3,NULL,'ADV',1,NULL,0,0,0),(21,351,'purchaseorder_no','vtiger_purchaseorder',1,'4','purchaseorder_no','PurchaseOrder No',1,0,'',100,2,55,1,'V~O',3,NULL,'BAS',0,NULL,1,0,0),(21,352,'subject','vtiger_purchaseorder',1,'2','subject','Subject',1,0,'',100,1,55,1,'V~M',3,NULL,'BAS',1,NULL,1,0,0),(21,353,'vendorid','vtiger_purchaseorder',1,'81','vendor_id','Vendor Name',1,0,'',100,3,55,1,'I~M',3,NULL,'BAS',1,NULL,1,0,0),(21,354,'requisition_no','vtiger_purchaseorder',1,'1','requisition_no','Requisition No',1,2,'',100,4,55,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(21,355,'tracking_no','vtiger_purchaseorder',1,'1','tracking_no','Tracking Number',1,2,'',100,5,55,1,'V~O',3,NULL,'BAS',1,NULL,1,0,0),(21,356,'contactid','vtiger_purchaseorder',1,'57','contact_id','Contact Name',1,2,'',100,6,55,1,'I~O',3,NULL,'BAS',1,NULL,0,1,0),(21,357,'duedate','vtiger_purchaseorder',1,'5','duedate','Due Date',1,2,'',100,7,55,1,'D~O',3,NULL,'BAS',1,NULL,0,0,0),(21,358,'carrier','vtiger_purchaseorder',1,'15','carrier','Carrier',1,2,'',100,8,55,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(21,359,'adjustment','vtiger_purchaseorder',1,'72','txtAdjustment','Adjustment',1,2,'',100,10,55,3,'NN~O',3,NULL,'BAS',1,NULL,0,0,0),(21,360,'salescommission','vtiger_purchaseorder',1,'1','salescommission','Sales Commission',1,2,'',100,11,55,1,'N~O',3,NULL,'BAS',1,NULL,0,0,0),(21,361,'exciseduty','vtiger_purchaseorder',1,'1','exciseduty','Excise Duty',1,2,'',100,12,55,1,'N~O',3,NULL,'BAS',1,NULL,0,0,0),(21,362,'total','vtiger_purchaseorder',1,'72','hdnGrandTotal','Total',1,2,'',100,13,55,3,'N~O',3,NULL,'BAS',1,NULL,1,0,0),(21,363,'subtotal','vtiger_purchaseorder',1,'72','hdnSubTotal','Sub Total',1,2,'',100,14,55,3,'N~O',3,NULL,'BAS',1,NULL,0,0,0),(21,364,'taxtype','vtiger_purchaseorder',1,'16','hdnTaxType','Tax Type',1,2,'',100,14,55,3,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(21,365,'discount_percent','vtiger_purchaseorder',1,'1','hdnDiscountPercent','Discount Percent',1,2,'',100,14,114,5,'N~O',3,NULL,'BAS',1,NULL,0,0,0),(21,366,'discount_amount','vtiger_purchaseorder',1,'72','hdnDiscountAmount','Discount Amount',1,0,'',100,14,114,5,'N~O',3,NULL,'BAS',1,NULL,0,0,0),(21,367,'s_h_amount','vtiger_purchaseorder',1,'72','hdnS_H_Amount','S&H Amount',1,2,'',100,14,55,3,'N~O',3,NULL,'BAS',1,NULL,0,0,0),(21,368,'postatus','vtiger_purchaseorder',1,'15','postatus','Status',1,2,'',100,15,55,1,'V~M',3,NULL,'BAS',1,NULL,0,1,0),(21,369,'smownerid','vtiger_purchaseorder',1,'53','assigned_user_id','Assigned To',1,0,'',100,16,55,1,'V~M',3,NULL,'BAS',1,NULL,0,1,0),(21,370,'createdtime','vtiger_purchaseorder',1,'70','createdtime','Created Time',1,0,'',100,17,55,2,'DT~O',3,NULL,'BAS',0,NULL,0,0,0),(21,371,'modifiedtime','vtiger_purchaseorder',1,'70','modifiedtime','Modified Time',1,0,'',100,18,55,2,'DT~O',3,NULL,'BAS',0,NULL,0,0,0),(21,372,'modifiedby','vtiger_purchaseorder',1,'52','modifiedby','Last Modified By',1,0,'',100,22,55,3,'V~O',3,NULL,'BAS',0,NULL,0,0,0),(21,373,'currency_id','vtiger_purchaseorder',1,'117','currency_id','Currency',1,2,'1',100,19,55,3,'I~O',3,NULL,'BAS',1,NULL,0,0,0),(21,374,'conversion_rate','vtiger_purchaseorder',1,'1','conversion_rate','Conversion Rate',1,2,'1',100,20,55,3,'N~O',3,NULL,'BAS',1,NULL,0,0,0),(21,375,'bill_street','vtiger_pobillads',1,'24','bill_street','Billing Address',1,2,'',100,9,57,1,'V~M',3,NULL,'BAS',1,NULL,0,0,0),(21,376,'ship_street','vtiger_poshipads',1,'24','ship_street','Shipping Address',1,2,'',100,10,57,1,'V~M',3,NULL,'BAS',1,NULL,0,0,0),(21,377,'bill_city','vtiger_pobillads',1,'1','bill_city','Billing City',1,2,'',100,7,57,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(21,378,'ship_city','vtiger_poshipads',1,'1','ship_city','Shipping City',1,2,'',100,8,57,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(21,379,'bill_state','vtiger_pobillads',1,'1','bill_state','Billing State',1,2,'',100,5,57,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(21,380,'ship_state','vtiger_poshipads',1,'1','ship_state','Shipping State',1,2,'',100,6,57,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(21,381,'bill_code','vtiger_pobillads',1,'1','bill_code','Billing Code',1,2,'',100,3,57,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(21,382,'ship_code','vtiger_poshipads',1,'1','ship_code','Shipping Code',1,2,'',100,4,57,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(21,383,'bill_country','vtiger_pobillads',1,'1','bill_country','Billing Country',1,1,'',100,1,57,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(21,384,'ship_country','vtiger_poshipads',1,'1','ship_country','Shipping Country',1,1,'',100,2,57,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(21,385,'bill_pobox','vtiger_pobillads',1,'1','bill_pobox','Billing Po Box',1,1,'',100,11,57,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(21,386,'ship_pobox','vtiger_poshipads',1,'1','ship_pobox','Shipping Po Box',1,1,'',100,12,57,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(21,387,'description','vtiger_purchaseorder',1,'19','description','Description',1,2,'',100,1,60,1,'V~O',3,NULL,'ADV',1,NULL,0,0,0),(21,388,'terms_conditions','vtiger_purchaseorder',1,'19','terms_conditions','Terms & Conditions',1,2,'',100,1,59,1,'V~O',3,NULL,'ADV',1,NULL,0,0,0),(22,389,'salesorder_no','vtiger_salesorder',1,'4','salesorder_no','SalesOrder No',1,0,'',100,4,61,1,'V~O',3,NULL,'BAS',0,NULL,1,0,0),(22,390,'subject','vtiger_salesorder',1,'2','subject','Subject',1,0,'',100,1,61,1,'V~M',3,NULL,'BAS',1,NULL,1,0,0),(22,391,'potentialid','vtiger_salesorder',1,'76','potential_id','Potential Name',1,2,'',100,2,61,1,'I~O',3,NULL,'BAS',1,NULL,0,0,0),(22,392,'customerno','vtiger_salesorder',1,'1','customerno','Customer No',1,2,'',100,3,61,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(22,393,'quoteid','vtiger_salesorder',1,'78','quote_id','Quote Name',1,2,'',100,5,61,1,'I~O',3,NULL,'BAS',0,NULL,1,0,0),(22,394,'purchaseorder','vtiger_salesorder',1,'1','vtiger_purchaseorder','Purchase Order',1,2,'',100,5,61,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(22,395,'contactid','vtiger_salesorder',1,'57','contact_id','Contact Name',1,2,'',100,6,61,1,'I~O',3,NULL,'BAS',1,NULL,0,1,0),(22,396,'duedate','vtiger_salesorder',1,'5','duedate','Due Date',1,2,'',100,8,61,1,'D~O',3,NULL,'BAS',1,NULL,0,0,0),(22,397,'carrier','vtiger_salesorder',1,'15','carrier','Carrier',1,2,'',100,9,61,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(22,398,'pending','vtiger_salesorder',1,'1','pending','Pending',1,2,'',100,10,61,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(22,399,'sostatus','vtiger_salesorder',1,'15','sostatus','Status',1,2,'',100,11,61,1,'V~M',3,NULL,'BAS',1,NULL,0,1,0),(22,400,'adjustment','vtiger_salesorder',1,'72','txtAdjustment','Adjustment',1,2,'',100,12,61,3,'NN~O',3,NULL,'BAS',1,NULL,0,0,0),(22,401,'salescommission','vtiger_salesorder',1,'1','salescommission','Sales Commission',1,2,'',100,13,61,1,'N~O',3,NULL,'BAS',1,NULL,0,0,0),(22,402,'exciseduty','vtiger_salesorder',1,'1','exciseduty','Excise Duty',1,2,'',100,13,61,1,'N~O',3,NULL,'BAS',1,NULL,0,0,0),(22,403,'total','vtiger_salesorder',1,'72','hdnGrandTotal','Total',1,2,'',100,14,61,3,'N~O',3,NULL,'BAS',1,NULL,1,0,0),(22,404,'subtotal','vtiger_salesorder',1,'72','hdnSubTotal','Sub Total',1,2,'',100,15,61,3,'N~O',3,NULL,'BAS',1,NULL,0,0,0),(22,405,'taxtype','vtiger_salesorder',1,'16','hdnTaxType','Tax Type',1,2,'',100,15,61,3,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(22,406,'discount_percent','vtiger_salesorder',1,'1','hdnDiscountPercent','Discount Percent',1,2,'',100,15,113,5,'N~O',3,NULL,'BAS',1,NULL,0,0,0),(22,407,'discount_amount','vtiger_salesorder',1,'72','hdnDiscountAmount','Discount Amount',1,0,'',100,15,113,5,'N~O',3,NULL,'BAS',1,NULL,0,0,0),(22,408,'s_h_amount','vtiger_salesorder',1,'72','hdnS_H_Amount','S&H Amount',1,2,'',100,15,61,3,'N~O',3,NULL,'BAS',1,NULL,0,0,0),(22,409,'accountid','vtiger_salesorder',1,'73','account_id','Account Name',1,2,'',100,16,61,1,'I~M',3,NULL,'BAS',1,NULL,0,1,0),(22,410,'smownerid','vtiger_salesorder',1,'53','assigned_user_id','Assigned To',1,0,'',100,17,61,1,'V~M',3,NULL,'BAS',1,NULL,0,1,0),(22,411,'createdtime','vtiger_salesorder',1,'70','createdtime','Created Time',1,0,'',100,18,61,2,'DT~O',3,NULL,'BAS',0,NULL,0,0,0),(22,412,'modifiedtime','vtiger_salesorder',1,'70','modifiedtime','Modified Time',1,0,'',100,19,61,2,'DT~O',3,NULL,'BAS',0,NULL,0,0,0),(22,413,'modifiedby','vtiger_salesorder',1,'52','modifiedby','Last Modified By',1,0,'',100,22,61,3,'V~O',3,NULL,'BAS',0,NULL,0,0,0),(22,414,'currency_id','vtiger_salesorder',1,'117','currency_id','Currency',1,2,'1',100,20,61,3,'I~O',3,NULL,'BAS',1,NULL,0,0,0),(22,415,'conversion_rate','vtiger_salesorder',1,'1','conversion_rate','Conversion Rate',1,2,'1',100,21,61,3,'N~O',3,NULL,'BAS',1,NULL,0,0,0),(22,416,'bill_street','vtiger_sobillads',1,'24','bill_street','Billing Address',1,2,'',100,9,63,1,'V~M',3,NULL,'BAS',1,NULL,0,0,0),(22,417,'ship_street','vtiger_soshipads',1,'24','ship_street','Shipping Address',1,2,'',100,10,63,1,'V~M',3,NULL,'BAS',1,NULL,0,0,0),(22,418,'bill_city','vtiger_sobillads',1,'1','bill_city','Billing City',1,2,'',100,7,63,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(22,419,'ship_city','vtiger_soshipads',1,'1','ship_city','Shipping City',1,2,'',100,8,63,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(22,420,'bill_state','vtiger_sobillads',1,'1','bill_state','Billing State',1,2,'',100,5,63,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(22,421,'ship_state','vtiger_soshipads',1,'1','ship_state','Shipping State',1,2,'',100,6,63,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(22,422,'bill_code','vtiger_sobillads',1,'1','bill_code','Billing Code',1,2,'',100,3,63,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(22,423,'ship_code','vtiger_soshipads',1,'1','ship_code','Shipping Code',1,2,'',100,4,63,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(22,424,'bill_country','vtiger_sobillads',1,'1','bill_country','Billing Country',1,1,'',100,1,63,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(22,425,'ship_country','vtiger_soshipads',1,'1','ship_country','Shipping Country',1,1,'',100,2,63,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(22,426,'bill_pobox','vtiger_sobillads',1,'1','bill_pobox','Billing Po Box',1,1,'',100,11,63,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(22,427,'ship_pobox','vtiger_soshipads',1,'1','ship_pobox','Shipping Po Box',1,1,'',100,12,63,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(22,428,'description','vtiger_salesorder',1,'19','description','Description',1,2,'',100,1,66,1,'V~O',3,NULL,'ADV',1,NULL,0,0,0),(22,429,'terms_conditions','vtiger_salesorder',1,'19','terms_conditions','Terms & Conditions',1,2,'',100,1,65,1,'V~O',3,NULL,'ADV',1,NULL,0,0,0),(22,430,'enable_recurring','vtiger_salesorder',1,'56','enable_recurring','Enable Recurring',1,0,'',100,1,85,1,'C~O',3,NULL,'BAS',0,NULL,0,0,0),(22,431,'recurring_frequency','vtiger_invoice_recurring_info',1,'16','recurring_frequency','Frequency',1,0,'',100,2,85,1,'V~O',3,NULL,'BAS',0,NULL,0,0,0),(22,432,'start_period','vtiger_invoice_recurring_info',1,'5','start_period','Start Period',1,0,'',100,3,85,1,'D~O',3,NULL,'BAS',0,NULL,0,0,0),(22,433,'end_period','vtiger_invoice_recurring_info',1,'5','end_period','End Period',1,0,'',100,4,85,1,'D~O~OTH~G~start_period~Start Period',3,NULL,'BAS',0,NULL,0,0,0),(22,434,'payment_duration','vtiger_invoice_recurring_info',1,'16','payment_duration','Payment Duration',1,0,'',100,5,85,1,'V~O',3,NULL,'BAS',0,NULL,0,0,0),(22,435,'invoice_status','vtiger_invoice_recurring_info',1,'15','invoicestatus','Invoice Status',1,0,'',100,6,85,1,'V~M',3,NULL,'BAS',0,NULL,0,0,0),(23,436,'subject','vtiger_invoice',1,'2','subject','Subject',1,0,'',100,1,67,1,'V~M',3,NULL,'BAS',1,NULL,1,0,0),(23,437,'salesorderid','vtiger_invoice',1,'80','salesorder_id','Sales Order',1,2,'',100,2,67,1,'I~O',3,NULL,'BAS',0,NULL,1,0,0),(23,438,'customerno','vtiger_invoice',1,'1','customerno','Customer No',1,2,'',100,3,67,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(23,439,'contactid','vtiger_invoice',1,'57','contact_id','Contact Name',1,2,'',100,4,67,1,'I~O',3,NULL,'BAS',1,NULL,0,1,0),(23,440,'invoicedate','vtiger_invoice',1,'5','invoicedate','Invoice Date',1,2,'',100,5,67,1,'D~O',3,NULL,'BAS',1,NULL,0,0,0),(23,441,'duedate','vtiger_invoice',1,'5','duedate','Due Date',1,2,'',100,6,67,1,'D~O',3,NULL,'BAS',1,NULL,0,0,0),(23,442,'purchaseorder','vtiger_invoice',1,'1','vtiger_purchaseorder','Purchase Order',1,2,'',100,8,67,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(23,443,'adjustment','vtiger_invoice',1,'72','txtAdjustment','Adjustment',1,2,'',100,9,67,3,'NN~O',3,NULL,'BAS',1,NULL,0,0,0),(23,444,'salescommission','vtiger_invoice',1,'1','salescommission','Sales Commission',1,2,'',10,13,67,1,'N~O',3,NULL,'BAS',1,NULL,0,0,0),(23,445,'exciseduty','vtiger_invoice',1,'1','exciseduty','Excise Duty',1,2,'',100,11,67,1,'N~O',3,NULL,'BAS',1,NULL,0,0,0),(23,446,'subtotal','vtiger_invoice',1,'72','hdnSubTotal','Sub Total',1,2,'',100,12,67,3,'N~O',3,NULL,'BAS',1,NULL,0,0,0),(23,447,'total','vtiger_invoice',1,'72','hdnGrandTotal','Total',1,2,'',100,13,67,3,'N~O',3,NULL,'BAS',1,NULL,1,0,0),(23,448,'taxtype','vtiger_invoice',1,'16','hdnTaxType','Tax Type',1,2,'',100,13,67,3,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(23,449,'discount_percent','vtiger_invoice',1,'1','hdnDiscountPercent','Discount Percent',1,2,'',100,13,112,5,'N~O',3,NULL,'BAS',1,NULL,0,0,0),(23,450,'discount_amount','vtiger_invoice',1,'72','hdnDiscountAmount','Discount Amount',1,2,'',100,13,112,5,'N~O',3,NULL,'BAS',1,NULL,0,0,0),(23,451,'s_h_amount','vtiger_invoice',1,'72','hdnS_H_Amount','S&H Amount',1,2,'',100,14,69,3,'N~O',3,NULL,'BAS',1,NULL,0,0,0),(23,452,'accountid','vtiger_invoice',1,'73','account_id','Account Name',1,2,'',100,14,67,1,'I~M',3,NULL,'BAS',1,NULL,0,1,0),(23,453,'invoicestatus','vtiger_invoice',1,'15','invoicestatus','Status',1,2,'',100,15,67,1,'V~O',3,NULL,'BAS',1,NULL,0,1,0),(23,454,'smownerid','vtiger_invoice',1,'53','assigned_user_id','Assigned To',1,0,'',100,16,67,1,'V~M',3,NULL,'BAS',1,NULL,0,1,0),(23,455,'createdtime','vtiger_invoice',1,'70','createdtime','Created Time',1,0,'',100,17,67,2,'DT~O',3,NULL,'BAS',0,NULL,0,0,0),(23,456,'modifiedtime','vtiger_invoice',1,'70','modifiedtime','Modified Time',1,0,'',100,18,67,2,'DT~O',3,NULL,'BAS',0,NULL,0,0,0),(23,457,'modifiedby','vtiger_invoice',1,'52','modifiedby','Last Modified By',1,0,'',100,22,67,3,'V~O',3,NULL,'BAS',0,NULL,0,0,0),(23,458,'currency_id','vtiger_invoice',1,'117','currency_id','Currency',1,2,'1',100,19,67,3,'I~O',3,NULL,'BAS',1,NULL,0,0,0),(23,459,'conversion_rate','vtiger_invoice',1,'1','conversion_rate','Conversion Rate',1,2,'1',100,20,67,3,'N~O',3,NULL,'BAS',1,NULL,0,0,0),(23,460,'bill_street','vtiger_invoicebillads',1,'24','bill_street','Billing Address',1,2,'',100,9,69,1,'V~M',3,NULL,'BAS',1,NULL,0,0,0),(23,461,'ship_street','vtiger_invoiceshipads',1,'24','ship_street','Shipping Address',1,2,'',100,10,69,1,'V~M',3,NULL,'BAS',1,NULL,0,0,0),(23,462,'bill_city','vtiger_invoicebillads',1,'1','bill_city','Billing City',1,2,'',100,7,69,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(23,463,'ship_city','vtiger_invoiceshipads',1,'1','ship_city','Shipping City',1,2,'',100,8,69,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(23,464,'bill_state','vtiger_invoicebillads',1,'1','bill_state','Billing State',1,2,'',100,5,69,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(23,465,'ship_state','vtiger_invoiceshipads',1,'1','ship_state','Shipping State',1,2,'',100,6,69,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(23,466,'bill_code','vtiger_invoicebillads',1,'1','bill_code','Billing Code',1,2,'',100,3,69,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(23,467,'ship_code','vtiger_invoiceshipads',1,'1','ship_code','Shipping Code',1,2,'',100,4,69,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(23,468,'bill_country','vtiger_invoicebillads',1,'1','bill_country','Billing Country',1,1,'',100,1,69,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(23,469,'ship_country','vtiger_invoiceshipads',1,'1','ship_country','Shipping Country',1,1,'',100,2,69,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(23,470,'bill_pobox','vtiger_invoicebillads',1,'1','bill_pobox','Billing Po Box',1,1,'',100,11,69,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(23,471,'ship_pobox','vtiger_invoiceshipads',1,'1','ship_pobox','Shipping Po Box',1,1,'',100,12,69,1,'V~O',3,NULL,'BAS',1,NULL,0,0,0),(23,472,'description','vtiger_invoice',1,'19','description','Description',1,2,'',100,1,72,1,'V~O',3,NULL,'ADV',1,NULL,0,0,0),(23,473,'terms_conditions','vtiger_invoice',1,'19','terms_conditions','Terms & Conditions',1,2,'',100,1,71,1,'V~O',3,NULL,'ADV',1,NULL,0,0,0),(23,474,'invoice_no','vtiger_invoice',1,'4','invoice_no','Invoice No',1,0,'',100,3,67,1,'V~O',3,NULL,'BAS',0,NULL,1,0,0),(29,475,'user_name','vtiger_users',1,'106','user_name','User Name',1,0,'',11,1,77,1,'V~M',1,NULL,'BAS',1,NULL,0,0,0),(29,476,'is_admin','vtiger_users',1,'156','is_admin','Admin',1,0,'',3,7,77,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(29,477,'user_password','vtiger_users',1,'99','user_password','Password',1,0,'',30,5,77,4,'P~M',1,NULL,'BAS',1,NULL,0,0,0),(29,478,'confirm_password','vtiger_users',1,'99','confirm_password','Confirm Password',1,0,'',30,6,77,4,'P~M',1,NULL,'BAS',1,NULL,0,0,0),(29,479,'first_name','vtiger_users',1,'1','first_name','First Name',1,0,'',30,4,77,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(29,480,'last_name','vtiger_users',1,'2','last_name','Last Name',1,0,'',30,3,77,1,'V~M',1,NULL,'BAS',1,NULL,0,0,0),(29,481,'roleid','vtiger_user2role',1,'98','roleid','Role',1,0,'',200,8,77,1,'V~M',1,NULL,'BAS',1,NULL,0,0,0),(29,482,'email1','vtiger_users',1,'104','email1','Email',1,0,'',100,2,77,1,'E~M',1,NULL,'BAS',1,NULL,0,0,0),(29,483,'status','vtiger_users',1,'115','status','Status',1,0,'Active',100,10,77,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(29,484,'activity_view','vtiger_users',1,'16','activity_view','Default Activity View',1,0,'',100,6,117,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(29,485,'lead_view','vtiger_users',1,'16','lead_view','Default Lead View',1,0,'',100,9,77,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(29,486,'hour_format','vtiger_users',1,'16','hour_format','Calendar Hour Format',1,0,'24',100,4,117,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(29,487,'end_hour','vtiger_users',1,'116','end_hour','Day ends at',1,0,'',100,11,77,3,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(29,488,'start_hour','vtiger_users',1,'16','start_hour','Day starts at',1,0,'',100,2,117,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(29,489,'is_owner','vtiger_users',1,'1','is_owner','Account Owner',0,2,'0',100,12,77,5,'V~O',0,1,'BAS',0,NULL,0,0,0),(29,490,'title','vtiger_users',1,'1','title','Title',1,0,'',50,1,79,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(29,491,'phone_work','vtiger_users',1,'11','phone_work','Office Phone',1,0,'',50,5,79,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(29,492,'department','vtiger_users',1,'1','department','Department',1,0,'',50,3,79,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(29,493,'phone_mobile','vtiger_users',1,'11','phone_mobile','Mobile',1,0,'',50,7,79,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(29,494,'reports_to_id','vtiger_users',1,'101','reports_to_id','Reports To',1,0,'',50,8,79,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(29,495,'phone_other','vtiger_users',1,'11','phone_other','Other Phone',1,0,'',50,11,79,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(29,496,'email2','vtiger_users',1,'13','email2','Other Email',1,0,'',100,4,79,1,'E~O',1,NULL,'BAS',1,NULL,0,0,0),(29,497,'phone_fax','vtiger_users',1,'11','phone_fax','Fax',1,0,'',50,2,79,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(29,498,'secondaryemail','vtiger_users',1,'13','secondaryemail','Secondary Email',1,0,'',100,6,79,1,'E~O',1,NULL,'BAS',1,NULL,0,0,0),(29,499,'phone_home','vtiger_users',1,'11','phone_home','Home Phone',1,0,'',50,9,79,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(29,500,'date_format','vtiger_users',1,'16','date_format','Date Format',1,0,'',30,3,117,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(29,501,'signature','vtiger_users',1,'21','signature','Signature',1,0,'',250,13,79,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(29,502,'description','vtiger_users',1,'21','description','Documents',1,0,'',250,14,79,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(29,503,'address_street','vtiger_users',1,'21','address_street','Street Address',1,0,'',250,5,80,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(29,504,'address_city','vtiger_users',1,'1','address_city','City',1,0,'',100,4,80,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(29,505,'address_state','vtiger_users',1,'1','address_state','State',1,0,'',100,3,80,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(29,506,'address_postalcode','vtiger_users',1,'1','address_postalcode','Postal Code',1,0,'',100,2,80,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(29,507,'address_country','vtiger_users',1,'1','address_country','Country',1,0,'',100,1,80,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(29,508,'accesskey','vtiger_users',1,'3','accesskey','Webservice Access Key',1,0,'',100,2,83,2,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(29,509,'time_zone','vtiger_users',1,'16','time_zone','Time Zone',1,0,'',200,5,117,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(29,510,'currency_id','vtiger_users',1,'117','currency_id','Currency',1,0,'',100,1,78,1,'I~O',1,NULL,'BAS',1,NULL,0,0,0),(29,511,'currency_grouping_pattern','vtiger_users',1,'16','currency_grouping_pattern','Digit Grouping Pattern',1,0,'',100,2,78,1,'V~O',1,NULL,'BAS',1,'<b>Currency - Digit Grouping Pattern</b> <br/><br/>This pattern specifies the format in which the currency separator will be placed.',0,0,0),(29,512,'currency_decimal_separator','vtiger_users',1,'16','currency_decimal_separator','Decimal Separator',1,0,'.',2,3,78,1,'V~O',1,NULL,'BAS',1,'<b>Currency - Decimal Separator</b> <br/><br/>Decimal separator specifies the separator to be used to separate the fractional values from the whole number part. <br/><b>Eg:</b> <br/>. => 123.45 <br/>, => 123,45 <br/>\' => 123\'45 <br/>  => 123 45 <br/>$ => 123$45 <br/>',0,0,0),(29,513,'currency_grouping_separator','vtiger_users',1,'16','currency_grouping_separator','Digit Grouping Separator',1,0,',',2,4,78,1,'V~O',1,NULL,'BAS',1,'<b>Currency - Grouping Separator</b> <br/><br/>Grouping separator specifies the separator to be used to group the whole number part into hundreds, thousands etc. <br/><b>Eg:</b> <br/>. => 123.456.789 <br/>, => 123,456,789 <br/>\' => 123\'456\'789 <br/>  => 123 456 789 <br/>$ => 123$456$789 <br/>',0,0,0),(29,514,'currency_symbol_placement','vtiger_users',1,'16','currency_symbol_placement','Symbol Placement',1,0,'',20,5,78,1,'V~O',1,NULL,'BAS',1,'<b>Currency - Symbol Placement</b> <br/><br/>Symbol Placement allows you to configure the position of the currency symbol with respect to the currency value.<br/><b>Eg:</b> <br/>$1.0 => $123,456,789.50 <br/>1.0$ => 123,456,789.50$ <br/>',0,0,0),(29,515,'imagename','vtiger_users',1,'105','imagename','User Image',1,0,'',250,10,82,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(29,516,'internal_mailer','vtiger_users',1,'56','internal_mailer','INTERNAL_MAIL_COMPOSER',1,0,'',50,15,79,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(29,517,'theme','vtiger_users',1,'31','theme','Theme',1,0,'softed',100,16,79,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(29,518,'language','vtiger_users',1,'32','language','Language',1,0,'ja_jp',100,17,79,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(29,519,'reminder_interval','vtiger_users',1,'16','reminder_interval','Reminder Interval',1,0,'',100,11,117,1,'V~O',1,NULL,'BAS',1,NULL,0,0,0),(10,520,'from_email','vtiger_emaildetails',1,'12','from_email','From',1,2,'',100,1,21,3,'V~M',3,NULL,'BAS',0,NULL,0,0,0),(10,521,'to_email','vtiger_emaildetails',1,'8','saved_toid','To',1,2,'',100,2,21,1,'V~M',3,NULL,'BAS',0,NULL,0,0,0),(10,522,'cc_email','vtiger_emaildetails',1,'8','ccmail','CC',1,2,'',1000,3,21,1,'V~O',3,NULL,'BAS',0,NULL,0,0,0),(10,523,'bcc_email','vtiger_emaildetails',1,'8','bccmail','BCC',1,2,'',1000,4,21,1,'V~O',3,NULL,'BAS',0,NULL,0,0,0),(10,524,'idlists','vtiger_emaildetails',1,'357','parent_id','Parent ID',1,2,'',1000,5,21,1,'V~O',3,NULL,'BAS',0,NULL,0,0,0),(10,525,'email_flag','vtiger_emaildetails',1,'16','email_flag','Email Flag',1,2,'',1000,6,21,3,'V~O',3,NULL,'BAS',0,NULL,0,0,0),(34,526,'smownerid','vtiger_servicecontracts',1,'53','assigned_user_id','Assigned To',1,2,'',100,4,88,1,'V~M',2,2,'BAS',1,'',1,0,0),(34,527,'createdtime','vtiger_servicecontracts',1,'70','createdtime','Created Time',1,0,'',100,17,88,2,'DT~O',3,0,'BAS',0,'',0,0,0),(34,528,'modifiedtime','vtiger_servicecontracts',1,'70','modifiedtime','Modified Time',1,0,'',100,18,88,2,'DT~O',3,0,'BAS',0,'',0,0,0),(34,529,'start_date','vtiger_servicecontracts',1,'5','start_date','Start Date',1,2,'',100,7,88,1,'D~O',2,4,'BAS',1,'',0,0,0),(34,530,'end_date','vtiger_servicecontracts',1,'5','end_date','End Date',1,2,'',100,11,88,2,'D~O',3,0,'BAS',0,'',0,0,0),(34,531,'sc_related_to','vtiger_servicecontracts',1,'10','sc_related_to','Related to',1,2,'',100,3,88,1,'V~O',2,6,'BAS',1,'',0,0,0),(34,532,'tracking_unit','vtiger_servicecontracts',1,'15','tracking_unit','Tracking Unit',1,2,'',100,6,88,1,'V~O',2,7,'BAS',1,'',0,0,0),(34,533,'total_units','vtiger_servicecontracts',1,'7','total_units','Total Units',1,2,'',100,8,88,1,'NN~O',2,8,'BAS',1,'',1,0,0),(34,534,'used_units','vtiger_servicecontracts',1,'7','used_units','Used Units',1,2,'',100,10,88,1,'NN~O',2,9,'BAS',1,'',1,0,0),(34,535,'subject','vtiger_servicecontracts',1,'1','subject','Subject',1,0,'',100,1,88,1,'V~M',0,1,'BAS',1,'',1,0,0),(34,536,'due_date','vtiger_servicecontracts',1,'23','due_date','Due date',1,2,'',100,9,88,1,'D~O',2,5,'BAS',1,'',0,0,0),(34,537,'planned_duration','vtiger_servicecontracts',1,'1','planned_duration','Planned Duration',1,2,'',100,13,88,2,'V~O',3,0,'BAS',0,'',0,0,0),(34,538,'actual_duration','vtiger_servicecontracts',1,'1','actual_duration','Actual Duration',1,2,'',100,15,88,2,'V~O',3,0,'BAS',0,'',0,0,0),(34,539,'contract_status','vtiger_servicecontracts',1,'15','contract_status','Status',1,2,'',100,12,88,1,'V~O',1,0,'BAS',1,'',0,0,0),(34,540,'priority','vtiger_servicecontracts',1,'15','contract_priority','Priority',1,2,'',100,14,88,1,'V~O',1,0,'BAS',1,'',0,0,0),(34,541,'contract_type','vtiger_servicecontracts',1,'15','contract_type','Type',1,2,'',100,5,88,1,'V~O',1,0,'BAS',1,'',0,0,0),(34,542,'progress','vtiger_servicecontracts',1,'9','progress','Progress',1,2,'',100,16,88,2,'N~O~2~2',3,3,'BAS',0,'',0,0,0),(34,543,'contract_no','vtiger_servicecontracts',1,'4','contract_no','Contract No',1,0,'',100,2,88,1,'V~O',3,0,'BAS',0,'',1,0,0),(34,544,'modifiedby','vtiger_servicecontracts',1,'52','modifiedby','Last Modified By',1,0,'',100,17,88,3,'V~O',3,0,'BAS',0,'',0,0,0),(34,545,'source','vtiger_servicecontracts',1,'1','source','Source',1,2,'',100,19,88,2,'V~O',3,10,'BAS',0,'',0,0,0),(34,546,'starred','vtiger_crmentity_user_field',1,'56','starred','starred',1,2,'',100,20,88,6,'C~O',3,11,'BAS',0,'',0,0,0),(34,547,'tags','vtiger_servicecontracts',1,'1','tags','tags',1,2,'',100,21,88,6,'V~O',3,12,'BAS',0,'',0,0,0),(35,548,'servicename','vtiger_service',1,'2','servicename','Service Name',1,0,'',100,1,90,1,'V~M',0,1,'BAS',1,'',1,0,0),(35,549,'service_no','vtiger_service',1,'4','service_no','Service No',1,0,'',100,2,90,1,'V~O',3,0,'BAS',0,'',1,0,0),(35,550,'discontinued','vtiger_service',1,'56','discontinued','Service Active',1,2,'1',100,4,90,1,'V~O',2,3,'BAS',1,'',0,0,0),(35,551,'sales_start_date','vtiger_service',1,'5','sales_start_date','Sales Start Date',1,2,'',100,9,90,1,'D~O',1,0,'BAS',1,'',0,0,0),(35,552,'sales_end_date','vtiger_service',1,'5','sales_end_date','Sales End Date',1,2,'',100,10,90,1,'D~O~OTH~GE~sales_start_date~Sales Start Date',1,0,'BAS',1,'',0,0,0),(35,553,'start_date','vtiger_service',1,'5','start_date','Support Start Date',1,2,'',100,11,90,1,'D~O',1,0,'BAS',1,'',0,0,0),(35,554,'expiry_date','vtiger_service',1,'5','expiry_date','Support Expiry Date',1,2,'',100,12,90,1,'D~O~OTH~GE~start_date~Start Date',1,0,'BAS',1,'',0,0,0),(35,555,'website','vtiger_service',1,'17','website','Website',1,2,'',100,6,90,1,'V~O',1,0,'BAS',1,'',0,0,0),(35,556,'createdtime','vtiger_service',1,'70','createdtime','Created Time',1,0,'',100,13,90,2,'DT~O',3,0,'BAS',0,'',0,0,0),(35,557,'modifiedtime','vtiger_service',1,'70','modifiedtime','Modified Time',1,0,'',100,14,90,2,'DT~O',3,0,'BAS',0,'',0,0,0),(35,558,'modifiedby','vtiger_service',1,'52','modifiedby','Last Modified By',1,0,'',100,16,90,3,'V~O',3,0,'BAS',0,'',0,0,0),(35,559,'service_usageunit','vtiger_service',1,'15','service_usageunit','Usage Unit',1,2,'',100,3,90,1,'V~O',1,0,'BAS',1,'',0,0,0),(35,560,'qty_per_unit','vtiger_service',1,'1','qty_per_unit','No of Units',1,2,'',100,5,90,1,'N~O',1,0,'BAS',1,'',1,0,0),(35,561,'smownerid','vtiger_service',1,'53','assigned_user_id','Owner',1,0,'',100,8,90,1,'I~O',1,0,'BAS',1,'',0,0,0),(35,562,'servicecategory','vtiger_service',1,'15','servicecategory','Service Category',1,2,'',100,7,90,1,'V~O',1,0,'BAS',1,'',0,0,0),(35,563,'unit_price','vtiger_service',1,'72','unit_price','Price',1,0,'',100,1,91,1,'N~O',2,2,'BAS',0,'',1,0,0),(35,564,'taxclass','vtiger_service',1,'83','taxclass','Taxes',1,2,'',100,4,91,1,'V~O',2,0,'BAS',1,'',0,0,0),(35,565,'commissionrate','vtiger_service',1,'9','commissionrate','Commission Rate',1,2,'',100,2,91,1,'N~O',1,0,'BAS',1,'',1,0,0),(35,566,'description','vtiger_service',1,'19','description','Description',1,2,'',100,1,93,1,'V~O',1,0,'BAS',1,'',0,0,0),(35,567,'source','vtiger_service',1,'1','source','Source',1,2,'',100,17,90,2,'V~O',3,4,'BAS',0,'',0,0,0),(35,568,'starred','vtiger_crmentity_user_field',1,'56','starred','starred',1,2,'',100,18,90,6,'C~O',3,5,'BAS',0,'',0,0,0),(35,569,'tags','vtiger_service',1,'1','tags','tags',1,2,'',100,19,90,6,'V~O',3,6,'BAS',0,'',0,0,0),(37,570,'commentcontent','vtiger_modcomments',1,'19','commentcontent','Comment',1,0,'',100,4,94,1,'V~M',0,4,'BAS',2,'',1,0,0),(37,571,'smownerid','vtiger_modcomments',1,'53','assigned_user_id','Assigned To',1,0,'',100,1,95,1,'V~M',0,1,'BAS',2,'',1,0,0),(37,572,'createdtime','vtiger_modcomments',1,'70','createdtime','Created Time',1,0,'',100,5,95,2,'DT~O',0,2,'BAS',0,'',0,0,0),(37,573,'modifiedtime','vtiger_modcomments',1,'70','modifiedtime','Modified Time',1,0,'',100,6,95,2,'DT~O',0,3,'BAS',0,'',0,0,0),(37,574,'related_to','vtiger_modcomments',1,'10','related_to','Related To',1,2,'',100,2,95,1,'V~M',2,5,'BAS',2,'',0,0,0),(37,575,'smcreatorid','vtiger_modcomments',1,'52','creator','Creator',1,2,'',100,4,95,2,'V~O',1,0,'BAS',1,'',0,0,0),(37,576,'parent_comments','vtiger_modcomments',1,'10','parent_comments','Related To Comments',1,2,'',100,7,95,1,'V~O',1,0,'BAS',1,'',0,0,0),(37,577,'source','vtiger_modcomments',1,'1','source','Source',1,2,'',100,5,94,2,'V~O',3,6,'BAS',0,'',0,0,0),(39,578,'projectmilestonename','vtiger_projectmilestone',1,'2','projectmilestonename','Project Milestone Name',1,2,'',100,1,97,1,'V~M',0,1,'BAS',1,'',1,0,0),(39,579,'projectmilestonedate','vtiger_projectmilestone',1,'5','projectmilestonedate','Milestone Date',1,2,'',100,5,97,1,'D~O',0,3,'BAS',1,'',1,0,0),(39,580,'projectid','vtiger_projectmilestone',1,'10','projectid','Related to',1,0,'',100,4,97,1,'V~M',0,4,'BAS',1,'',0,0,0),(39,581,'projectmilestonetype','vtiger_projectmilestone',1,'15','projectmilestonetype','Type',1,2,'',100,7,97,1,'V~O',1,0,'BAS',1,'',1,0,0),(39,582,'smownerid','vtiger_projectmilestone',1,'53','assigned_user_id','Assigned To',1,2,'',100,6,97,1,'V~M',0,2,'BAS',1,'',0,0,0),(39,583,'projectmilestone_no','vtiger_projectmilestone',2,'4','projectmilestone_no','Project Milestone No',1,0,'',100,2,97,1,'V~O',3,4,'BAS',0,'',0,0,0),(39,584,'createdtime','vtiger_projectmilestone',1,'70','createdtime','Created Time',1,2,'',100,1,98,2,'DT~O',1,0,'BAS',1,'',0,0,0),(39,585,'modifiedtime','vtiger_projectmilestone',1,'70','modifiedtime','Modified Time',1,2,'',100,2,98,2,'DT~O',1,0,'BAS',1,'',0,0,0),(39,586,'modifiedby','vtiger_projectmilestone',1,'52','modifiedby','Last Modified By',1,0,'',100,3,98,3,'V~O',3,0,'BAS',0,'',0,0,0),(39,587,'description','vtiger_projectmilestone',1,'19','description','Description',1,2,'',100,1,99,1,'V~O',1,0,'BAS',1,'',0,0,0),(39,588,'source','vtiger_projectmilestone',1,'1','source','Source',1,2,'',100,8,97,2,'V~O',3,5,'BAS',0,'',0,0,0),(39,589,'starred','vtiger_crmentity_user_field',1,'56','starred','starred',1,2,'',100,9,97,6,'C~O',3,6,'BAS',0,'',0,0,0),(39,590,'tags','vtiger_projectmilestone',1,'1','tags','tags',1,2,'',100,10,97,6,'V~O',3,7,'BAS',0,'',0,0,0),(40,591,'projecttaskname','vtiger_projecttask',1,'2','projecttaskname','Project Task Name',1,2,'',100,1,100,1,'V~M',0,1,'BAS',1,'',1,0,0),(40,592,'projecttasktype','vtiger_projecttask',1,'15','projecttasktype','Type',1,2,'',100,4,100,1,'V~O',1,0,'BAS',1,'',1,0,0),(40,593,'projecttaskpriority','vtiger_projecttask',1,'15','projecttaskpriority','Priority',1,2,'',100,3,100,1,'V~O',1,0,'BAS',1,'',0,0,0),(40,594,'projectid','vtiger_projecttask',1,'10','projectid','Related to',1,0,'',100,6,100,1,'V~M',0,5,'BAS',1,'',0,0,0),(40,595,'smownerid','vtiger_projecttask',1,'53','assigned_user_id','Assigned To',1,2,'',100,7,100,1,'V~M',0,2,'BAS',1,'',1,0,0),(40,596,'projecttasknumber','vtiger_projecttask',1,'7','projecttasknumber','Project Task Number',1,2,'',100,5,100,1,'I~O',1,0,'BAS',1,'',0,0,0),(40,597,'projecttask_no','vtiger_projecttask',2,'4','projecttask_no','Project Task No',1,0,'',100,2,100,1,'V~O',3,4,'BAS',0,'',0,0,0),(40,598,'projecttaskprogress','vtiger_projecttask',1,'15','projecttaskprogress','Progress',1,2,'',100,1,101,1,'V~O',1,0,'BAS',1,'',1,0,0),(40,599,'projecttaskhours','vtiger_projecttask',1,'7','projecttaskhours','Worked Hours',1,2,'',100,2,101,1,'V~O',1,0,'BAS',1,'',0,0,0),(40,600,'startdate','vtiger_projecttask',1,'5','startdate','Start Date',1,2,'',100,3,101,1,'D~O',0,3,'BAS',1,'',1,0,0),(40,601,'enddate','vtiger_projecttask',1,'5','enddate','End Date',1,2,'',100,4,101,1,'D~O~OTH~GE~startdate~Start Date',0,0,'BAS',1,'',1,0,0),(40,602,'createdtime','vtiger_projecttask',1,'70','createdtime','Created Time',1,2,'',100,5,101,2,'DT~O',1,0,'BAS',1,'',0,0,0),(40,603,'modifiedtime','vtiger_projecttask',1,'70','modifiedtime','Modified Time',1,2,'',100,6,101,2,'DT~O',1,0,'BAS',1,'',0,0,0),(40,604,'modifiedby','vtiger_projecttask',1,'52','modifiedby','Last Modified By',1,0,'',100,7,101,3,'V~O',3,0,'BAS',0,'',0,0,0),(40,605,'description','vtiger_projecttask',1,'19','description','description',1,2,'',100,1,102,1,'V~O',1,0,'BAS',1,'',0,0,0),(40,606,'source','vtiger_projecttask',1,'1','source','Source',1,2,'',100,8,100,2,'V~O',3,6,'BAS',0,'',0,0,0),(40,607,'starred','vtiger_crmentity_user_field',1,'56','starred','starred',1,2,'',100,9,100,6,'C~O',3,7,'BAS',0,'',0,0,0),(40,608,'tags','vtiger_projecttask',1,'1','tags','tags',1,2,'',100,10,100,6,'V~O',3,8,'BAS',0,'',0,0,0),(41,609,'projectname','vtiger_project',1,'2','projectname','Project Name',1,2,'',100,1,103,1,'V~M',0,1,'BAS',1,'',1,0,0),(41,610,'startdate','vtiger_project',1,'23','startdate','Start Date',1,2,'',100,3,103,1,'D~O',0,3,'BAS',1,'',1,0,0),(41,611,'targetenddate','vtiger_project',1,'23','targetenddate','Target End Date',1,2,'',100,5,103,1,'D~O~OTH~GE~startdate~Start Date',0,4,'BAS',1,'',1,0,0),(41,612,'actualenddate','vtiger_project',1,'23','actualenddate','Actual End Date',1,2,'',100,6,103,1,'D~O~OTH~GE~startdate~Start Date',1,0,'BAS',1,'',0,0,0),(41,613,'projectstatus','vtiger_project',1,'15','projectstatus','Status',1,2,'',100,7,103,1,'V~O',1,0,'BAS',1,'',1,0,0),(41,614,'projecttype','vtiger_project',1,'15','projecttype','Type',1,2,'',100,8,103,1,'V~O',1,0,'BAS',1,'',1,0,0),(41,615,'linktoaccountscontacts','vtiger_project',1,'10','linktoaccountscontacts','Related to',1,2,'',100,9,103,1,'V~O',1,0,'BAS',1,'',0,1,0),(41,616,'smownerid','vtiger_project',1,'53','assigned_user_id','Assigned To',1,2,'',100,4,103,1,'V~M',0,2,'BAS',1,'',1,0,0),(41,617,'project_no','vtiger_project',2,'4','project_no','Project No',1,0,'',100,2,103,1,'V~O',3,0,'BAS',0,'',0,0,0),(41,618,'targetbudget','vtiger_project',1,'7','targetbudget','Target Budget',1,2,'',100,1,104,1,'V~O',1,0,'BAS',1,'',0,0,0),(41,619,'projecturl','vtiger_project',1,'17','projecturl','Project Url',1,2,'',100,2,104,1,'V~O',1,0,'BAS',1,'',0,0,0),(41,620,'projectpriority','vtiger_project',1,'15','projectpriority','Priority',1,2,'',100,3,104,1,'V~O',1,0,'BAS',1,'',0,0,0),(41,621,'progress','vtiger_project',1,'15','progress','Progress',1,2,'',100,4,104,1,'V~O',1,0,'BAS',1,'',0,0,0),(41,622,'createdtime','vtiger_project',1,'70','createdtime','Created Time',1,2,'',100,5,104,2,'DT~O',1,0,'BAS',1,'',0,0,0),(41,623,'modifiedtime','vtiger_project',1,'70','modifiedtime','Modified Time',1,2,'',100,6,104,2,'DT~O',1,0,'BAS',1,'',0,0,0),(41,624,'modifiedby','vtiger_project',1,'52','modifiedby','Last Modified By',1,0,'',100,7,104,3,'V~O',3,0,'BAS',0,'',0,0,0),(41,625,'description','vtiger_project',1,'19','description','Description',1,2,'',100,1,105,1,'V~O',1,0,'BAS',1,'',0,0,0),(41,626,'source','vtiger_project',1,'1','source','Source',1,2,'',100,10,103,2,'V~O',3,5,'BAS',0,'',0,0,0),(41,627,'starred','vtiger_crmentity_user_field',1,'56','starred','starred',1,2,'',100,11,103,6,'C~O',3,6,'BAS',0,'',0,0,0),(41,628,'tags','vtiger_project',1,'1','tags','tags',1,2,'',100,12,103,6,'V~O',3,7,'BAS',0,'',0,0,0),(42,629,'smownerid','vtiger_crmentity',1,'53','assigned_user_id','Assigned To',1,0,'',100,2,106,1,'V~M',1,0,'BAS',1,'',1,0,0),(42,630,'createdtime','vtiger_crmentity',1,'70','createdtime','Created Time',1,0,'',100,5,106,2,'DT~O',1,0,'BAS',0,'',0,0,0),(42,631,'modifiedtime','vtiger_crmentity',1,'70','modifiedtime','Modified Time',1,0,'',100,6,106,2,'DT~O',1,0,'BAS',0,'',0,0,0),(42,632,'message','vtiger_smsnotifier',1,'21','message','message',1,0,'',100,1,106,1,'V~M',1,0,'BAS',1,'',1,0,0),(42,633,'modifiedby','vtiger_crmentity',1,'52','modifiedby','Last Modified By',1,0,'',100,7,106,3,'V~O',1,0,'BAS',0,'',0,0,0),(42,634,'source','vtiger_crmentity',1,'1','source','Source',1,2,'',100,8,106,2,'V~O',3,1,'BAS',0,'',0,0,0),(43,635,'asset_no','vtiger_assets',1,'4','asset_no','Asset No',1,0,'',100,2,109,1,'V~O',3,0,'BAS',0,'\n					',1,0,0),(43,636,'product','vtiger_assets',1,'10','product','Product Name',1,2,'',100,3,109,1,'V~M',0,3,'BAS',1,'\n					',1,0,0),(43,637,'serialnumber','vtiger_assets',1,'2','serialnumber','Serial Number',1,2,'',100,4,109,1,'V~M',0,5,'BAS',1,'\n					',0,0,0),(43,638,'datesold','vtiger_assets',1,'5','datesold','Date Sold',1,2,'',100,5,109,1,'D~M~OTH~GE~datesold~Date Sold',0,0,'BAS',1,'\n					',0,0,0),(43,639,'dateinservice','vtiger_assets',1,'5','dateinservice','Date in Service',1,2,'',100,6,109,1,'D~M~OTH~GE~dateinservice~Date in Service',0,4,'BAS',1,'\n					',0,0,0),(43,640,'assetstatus','vtiger_assets',1,'15','assetstatus','Status',1,2,'',100,7,109,1,'V~M',0,0,'BAS',1,'\n					',0,0,0),(43,641,'tagnumber','vtiger_assets',1,'2','tagnumber','Tag Number',1,2,'',100,8,109,1,'V~O',1,0,'BAS',1,'\n					',0,0,0),(43,642,'invoiceid','vtiger_assets',1,'10','invoiceid','Invoice Name',1,2,'',100,9,109,1,'V~O',1,0,'BAS',1,'\n					',0,0,0),(43,643,'shippingmethod','vtiger_assets',1,'2','shippingmethod','Shipping Method',1,2,'',100,10,109,1,'V~O',1,0,'BAS',1,'\n					',0,0,0),(43,644,'shippingtrackingnumber','vtiger_assets',1,'2','shippingtrackingnumber','Shipping Tracking Number',1,2,'',100,11,109,1,'V~O',1,0,'BAS',1,'\n					',0,0,0),(43,645,'smownerid','vtiger_assets',1,'53','assigned_user_id','Assigned To',1,2,'',100,4,109,1,'V~M',0,2,'BAS',1,'\n					',0,0,0),(43,646,'assetname','vtiger_assets',1,'1','assetname','Asset Name',1,0,'',100,12,109,1,'V~M',0,6,'BAS',1,'\n					',1,0,0),(43,647,'account','vtiger_assets',1,'10','account','Customer Name',1,2,'',100,13,109,1,'V~M',0,0,'BAS',1,'\n					',1,0,0),(43,648,'contact','vtiger_assets',1,'10','contact','Contact Name',1,2,'',100,14,109,1,'V~O',0,0,'BAS',1,'\n					',0,0,0),(43,649,'createdtime','vtiger_assets',1,'70','createdtime','Created Time',1,0,'',100,14,109,2,'DT~O',3,0,'BAS',0,'\n					',0,0,0),(43,650,'modifiedtime','vtiger_assets',1,'70','modifiedtime','Modified Time',1,0,'',100,15,109,2,'DT~O',3,0,'BAS',0,'\n					',0,0,0),(43,651,'modifiedby','vtiger_assets',1,'52','modifiedby','Last Modified By',1,0,'',100,16,109,3,'V~O',3,0,'BAS',0,'\n					',0,0,0),(43,652,'description','vtiger_assets',1,'19','description','Notes',1,2,'',100,1,111,1,'V~O',1,0,'BAS',1,'\n					',0,0,0),(43,653,'source','vtiger_assets',1,'1','source','Source',1,2,'',100,17,109,2,'V~O',3,7,'BAS',0,'',0,0,0),(43,654,'starred','vtiger_crmentity_user_field',1,'56','starred','starred',1,2,'',100,18,109,6,'C~O',3,8,'BAS',0,'',0,0,0),(43,655,'tags','vtiger_assets',1,'1','tags','tags',1,2,'',100,19,109,6,'V~O',3,9,'BAS',0,'',0,0,0),(2,656,'forecast_amount','vtiger_potential',1,'71','forecast_amount','Forecast Amount',1,2,'',100,18,1,1,'N~O',1,0,'BAS',0,'',0,0,0),(29,657,'no_of_currency_decimals','vtiger_users',1,'16','no_of_currency_decimals','Number Of Currency Decimals',1,2,'0',100,6,78,1,'V~O',1,0,'BAS',1,'<b>Currency - Number of Decimal places</b> <br/><br/>Number of decimal places specifies how many number of decimals will be shown after decimal separator.<br/><b>Eg:</b> 123.00',0,0,0),(23,658,'productid','vtiger_inventoryproductrel',1,'10','productid','Item Name',0,2,'',100,1,112,5,'V~M',1,0,'BAS',0,'',0,0,0),(23,659,'quantity','vtiger_inventoryproductrel',1,'7','quantity','Quantity',0,2,'',100,2,112,5,'N~O',1,0,'BAS',0,'',0,0,0),(23,660,'listprice','vtiger_inventoryproductrel',1,'71','listprice','List Price',0,2,'',100,3,112,5,'N~O',1,0,'BAS',0,'',0,0,0),(23,661,'comment','vtiger_inventoryproductrel',1,'19','comment','Item Comment',0,2,'',100,4,112,5,'V~O',1,0,'BAS',0,'',0,0,0),(23,662,'discount_amount','vtiger_inventoryproductrel',1,'71','discount_amount','Item Discount Amount',0,2,'',100,5,112,5,'N~O',1,0,'BAS',0,'',0,0,0),(23,663,'discount_percent','vtiger_inventoryproductrel',1,'7','discount_percent','Item Discount Percent',0,2,'',100,6,112,5,'V~O',1,0,'BAS',0,'',0,0,0),(23,664,'tax1','vtiger_inventoryproductrel',1,'83','tax1','VAT',0,2,'',100,7,112,5,'V~O',1,0,'BAS',0,'',0,0,0),(23,665,'tax2','vtiger_inventoryproductrel',1,'83','tax2','Sales',0,2,'',100,8,112,5,'V~O',1,0,'BAS',0,'',0,0,0),(23,666,'tax3','vtiger_inventoryproductrel',1,'83','tax3','Service',0,2,'',100,9,112,5,'V~O',1,0,'BAS',0,'',0,0,0),(22,667,'productid','vtiger_inventoryproductrel',1,'10','productid','Item Name',0,2,'',100,1,113,5,'V~M',1,0,'BAS',0,'',0,0,0),(22,668,'quantity','vtiger_inventoryproductrel',1,'7','quantity','Quantity',0,2,'',100,2,113,5,'N~O',1,0,'BAS',0,'',0,0,0),(22,669,'listprice','vtiger_inventoryproductrel',1,'71','listprice','List Price',0,2,'',100,3,113,5,'N~O',1,0,'BAS',0,'',0,0,0),(22,670,'comment','vtiger_inventoryproductrel',1,'19','comment','Item Comment',0,2,'',100,4,113,5,'V~O',1,0,'BAS',0,'',0,0,0),(22,671,'discount_amount','vtiger_inventoryproductrel',1,'71','discount_amount','Item Discount Amount',0,2,'',100,5,113,5,'N~O',1,0,'BAS',0,'',0,0,0),(22,672,'discount_percent','vtiger_inventoryproductrel',1,'7','discount_percent','Item Discount Percent',0,2,'',100,6,113,5,'V~O',1,0,'BAS',0,'',0,0,0),(22,673,'tax1','vtiger_inventoryproductrel',1,'83','tax1','VAT',0,2,'',100,7,113,5,'V~O',1,0,'BAS',0,'',0,0,0),(22,674,'tax2','vtiger_inventoryproductrel',1,'83','tax2','Sales',0,2,'',100,8,113,5,'V~O',1,0,'BAS',0,'',0,0,0),(22,675,'tax3','vtiger_inventoryproductrel',1,'83','tax3','Service',0,2,'',100,9,113,5,'V~O',1,0,'BAS',0,'',0,0,0),(21,676,'productid','vtiger_inventoryproductrel',1,'10','productid','Item Name',0,2,'',100,1,114,5,'V~M',1,0,'BAS',0,'',0,0,0),(21,677,'quantity','vtiger_inventoryproductrel',1,'7','quantity','Quantity',0,2,'',100,2,114,5,'N~O',1,0,'BAS',0,'',0,0,0),(21,678,'listprice','vtiger_inventoryproductrel',1,'71','listprice','List Price',0,2,'',100,3,114,5,'N~O',1,0,'BAS',0,'',0,0,0),(21,679,'comment','vtiger_inventoryproductrel',1,'19','comment','Item Comment',0,2,'',100,4,114,5,'V~O',1,0,'BAS',0,'',0,0,0),(21,680,'discount_amount','vtiger_inventoryproductrel',1,'71','discount_amount','Item Discount Amount',0,2,'',100,5,114,5,'N~O',1,0,'BAS',0,'',0,0,0),(21,681,'discount_percent','vtiger_inventoryproductrel',1,'7','discount_percent','Item Discount Percent',0,2,'',100,6,114,5,'V~O',1,0,'BAS',0,'',0,0,0),(21,682,'tax1','vtiger_inventoryproductrel',1,'83','tax1','VAT',0,2,'',100,7,114,5,'V~O',1,0,'BAS',0,'',0,0,0),(21,683,'tax2','vtiger_inventoryproductrel',1,'83','tax2','Sales',0,2,'',100,8,114,5,'V~O',1,0,'BAS',0,'',0,0,0),(21,684,'tax3','vtiger_inventoryproductrel',1,'83','tax3','Service',0,2,'',100,9,114,5,'V~O',1,0,'BAS',0,'',0,0,0),(20,685,'productid','vtiger_inventoryproductrel',1,'10','productid','Item Name',0,2,'',100,1,115,5,'V~M',1,0,'BAS',0,'',0,0,0),(20,686,'quantity','vtiger_inventoryproductrel',1,'7','quantity','Quantity',0,2,'',100,2,115,5,'N~O',1,0,'BAS',0,'',0,0,0),(20,687,'listprice','vtiger_inventoryproductrel',1,'71','listprice','List Price',0,2,'',100,3,115,5,'N~O',1,0,'BAS',0,'',0,0,0),(20,688,'comment','vtiger_inventoryproductrel',1,'19','comment','Item Comment',0,2,'',100,4,115,5,'V~O',1,0,'BAS',0,'',0,0,0),(20,689,'discount_amount','vtiger_inventoryproductrel',1,'71','discount_amount','Item Discount Amount',0,2,'',100,5,115,5,'N~O',1,0,'BAS',0,'',0,0,0),(20,690,'discount_percent','vtiger_inventoryproductrel',1,'7','discount_percent','Item Discount Percent',0,2,'',100,6,115,5,'V~O',1,0,'BAS',0,'',0,0,0),(20,691,'tax1','vtiger_inventoryproductrel',1,'83','tax1','VAT',0,2,'',100,7,115,5,'V~O',1,0,'BAS',0,'',0,0,0),(20,692,'tax2','vtiger_inventoryproductrel',1,'83','tax2','Sales',0,2,'',100,8,115,5,'V~O',1,0,'BAS',0,'',0,0,0),(20,693,'tax3','vtiger_inventoryproductrel',1,'83','tax3','Service',0,2,'',100,9,115,5,'V~O',1,0,'BAS',0,'',0,0,0),(29,694,'truncate_trailing_zeros','vtiger_users',1,'56','truncate_trailing_zeros','Truncate Trailing Zeros',1,2,'0',100,7,78,1,'V~O',1,0,'BAS',1,'<b> Truncate Trailing Zeros </b> <br/><br/>It truncated trailing 0s in any of Currency, Decimal and Percentage Field types<br/><br/><b>Ex:</b><br/>If value is 89.00000 then <br/>decimal and Percentage fields were shows 89<br/>currency field type - shows 89.00<br/>',0,0,0),(40,695,'projecttaskstatus','vtiger_projecttask',1,'15','projecttaskstatus','Status',1,2,'',100,11,100,1,'V~O',0,9,'BAS',1,'',0,0,0),(37,696,'customer','vtiger_modcomments',1,'10','customer','Customer',1,2,'',100,6,94,3,'V~O',1,0,'BAS',1,'',0,0,0),(29,697,'dayoftheweek','vtiger_users',1,'16','dayoftheweek','Starting Day of the week',1,2,'Monday',100,1,117,1,'V~O',1,0,'BAS',1,'',0,0,0),(29,698,'callduration','vtiger_users',1,'16','callduration','Default Call Duration',1,2,'30',100,7,117,1,'V~O',1,0,'BAS',1,'',0,0,0),(29,699,'othereventduration','vtiger_users',1,'16','othereventduration','Other Event Duration',1,2,'30',100,8,117,1,'V~O',1,0,'BAS',1,'',0,0,0),(23,700,'pre_tax_total','vtiger_invoice',1,'72','pre_tax_total','Pre Tax Total',1,2,'',100,23,67,3,'N~O',1,0,'BAS',1,'',0,0,0),(22,701,'pre_tax_total','vtiger_salesorder',1,'72','pre_tax_total','Pre Tax Total',1,2,'',100,23,61,3,'N~O',1,0,'BAS',1,'',0,0,0),(21,702,'pre_tax_total','vtiger_purchaseorder',1,'72','pre_tax_total','Pre Tax Total',1,2,'',100,23,55,3,'N~O',1,0,'BAS',1,'',0,0,0),(20,703,'pre_tax_total','vtiger_quotes',1,'72','pre_tax_total','Pre Tax Total',1,2,'',100,23,49,3,'N~O',1,0,'BAS',1,'',0,0,0),(29,704,'calendarsharedtype','vtiger_users',1,'16','calendarsharedtype','Calendar Shared Type',1,2,'Public',100,12,117,3,'V~O',1,0,'BAS',1,'',0,0,0),(6,705,'isconvertedfromlead','vtiger_account',1,'56','isconvertedfromlead','Is Converted From Lead',1,2,'no',100,24,9,1,'C~O',1,0,'BAS',1,'',0,0,0),(4,706,'isconvertedfromlead','vtiger_contactdetails',1,'56','isconvertedfromlead','Is Converted From Lead',1,2,'no',100,29,4,1,'C~O',1,0,'BAS',1,'',0,0,0),(2,707,'isconvertedfromlead','vtiger_potential',1,'56','isconvertedfromlead','Is Converted From Lead',1,2,'no',100,19,1,1,'C~O',1,0,'BAS',1,'',0,0,0),(29,708,'default_record_view','vtiger_users',1,'16','default_record_view','Default Record View',1,2,'Summary',100,18,79,1,'V~O',1,0,'BAS',1,'',0,0,0),(23,709,'received','vtiger_invoice',1,'72','received','Received',1,2,'0',100,24,67,3,'N~O',1,0,'BAS',1,'',0,0,0),(23,710,'balance','vtiger_invoice',1,'72','balance','Balance',1,2,'0',100,25,67,3,'N~O',1,0,'BAS',1,'',0,0,0),(21,711,'paid','vtiger_purchaseorder',1,'72','paid','Paid',1,2,'0',100,24,55,3,'N~O',1,0,'BAS',1,'',0,0,0),(21,712,'balance','vtiger_purchaseorder',1,'72','balance','Balance',1,2,'0',100,25,55,3,'N~O',1,0,'BAS',1,'',0,0,0),(18,713,'smownerid','vtiger_vendor',1,'53','assigned_user_id','Assigned To',1,2,'',100,13,42,1,'V~M',1,0,'BAS',1,'',0,0,0),(7,714,'emailoptout','vtiger_leaddetails',1,'56','emailoptout','Email Opt Out',1,0,'',100,24,13,1,'C~O',1,0,'BAS',1,'',0,0,0),(37,715,'userid','vtiger_modcomments',1,'10','userid','UserId',1,2,'',100,7,94,3,'V~O',1,0,'BAS',1,'',0,0,0),(37,716,'reasontoedit','vtiger_modcomments',1,'19','reasontoedit','ReasonToEdit',1,2,'',100,8,94,1,'V~O',1,0,'BAS',1,'',0,0,0),(23,717,'s_h_percent','vtiger_invoice',1,'1','hdnS_H_Percent','S&H Percent',0,2,'',100,10,112,5,'N~O',0,1,'BAS',0,'',0,0,0),(20,718,'s_h_percent','vtiger_quotes',1,'1','hdnS_H_Percent','S&H Percent',0,2,'',100,10,115,5,'N~O',0,1,'BAS',0,'',0,0,0),(21,719,'s_h_percent','vtiger_purchaseorder',1,'1','hdnS_H_Percent','S&H Percent',0,2,'',100,10,114,5,'N~O',0,1,'BAS',0,'',0,0,0),(22,720,'s_h_percent','vtiger_salesorder',1,'1','hdnS_H_Percent','S&H Percent',0,2,'',100,10,113,5,'N~O',0,1,'BAS',0,'',0,0,0),(29,721,'leftpanelhide','vtiger_users',1,'56','leftpanelhide','Left Panel Hide',1,2,'0',100,19,79,1,'V~O',1,0,'BAS',1,'',0,0,0),(2,722,'contact_id','vtiger_potential',1,'10','contact_id','Contact Name',1,2,'',100,4,1,1,'V~O',1,0,'BAS',1,'',1,0,0),(13,723,'contact_id','vtiger_troubletickets',1,'10','contact_id','Contact Name',1,2,'',100,3,25,1,'V~O',1,0,'BAS',1,'',1,0,0),(29,724,'rowheight','vtiger_users',1,'16','rowheight','Row Height',1,2,'medium',100,20,79,1,'V~O',1,0,'BAS',1,'',0,0,0),(29,725,'defaulteventstatus','vtiger_users',1,'15','defaulteventstatus','Default Event Status',1,2,'Planned',100,9,117,1,'V~O',1,0,'BAS',1,'',0,0,0),(29,726,'defaultactivitytype','vtiger_users',1,'15','defaultactivitytype','Default Activity Type',1,2,'Call',100,10,117,1,'V~O',1,0,'BAS',1,'',0,0,0),(29,727,'hidecompletedevents','vtiger_users',1,'56','hidecompletedevents','LBL_HIDE_COMPLETED_EVENTS',1,2,'0',100,13,117,1,'C~O',1,0,'BAS',1,'',0,0,0),(14,753,'purchase_cost','vtiger_products',1,'71','purchase_cost','Purchase Cost',1,0,'',100,5,32,1,'N~O',1,0,'BAS',1,'',0,0,0),(35,754,'purchase_cost','vtiger_service',1,'71','purchase_cost','Purchase Cost',1,0,'',100,5,91,1,'N~O',1,0,'BAS',1,'',0,0,0),(23,755,'potential_id','vtiger_invoice',1,'10','potential_id','Potential Name',1,2,'',100,26,67,1,'I~O',1,0,'BAS',1,'',0,0,0),(29,756,'defaultcalendarview','vtiger_users',1,'16','defaultcalendarview','Default Calendar View',1,0,'MyCalendar',100,14,117,1,'V~O',1,0,'BAS',1,'',0,0,0),(23,757,'image','vtiger_inventoryproductrel',1,'56','image','Image',0,1,'',100,14,112,5,'V~O',1,0,'BAS',0,'',0,0,0),(23,758,'purchase_cost','vtiger_inventoryproductrel',1,'71','purchase_cost','Purchase Cost',0,1,'',100,15,112,5,'N~O',1,0,'BAS',0,'',0,0,0),(23,759,'margin','vtiger_inventoryproductrel',1,'71','margin','Margin',0,1,'',100,16,112,5,'N~O',1,0,'BAS',0,'',0,0,0),(20,760,'image','vtiger_inventoryproductrel',1,'56','image','Image',0,1,'',100,15,115,5,'V~O',1,0,'BAS',0,'',0,0,0),(20,761,'purchase_cost','vtiger_inventoryproductrel',1,'71','purchase_cost','Purchase Cost',0,1,'',100,16,115,5,'N~O',1,0,'BAS',0,'',0,0,0),(20,762,'margin','vtiger_inventoryproductrel',1,'71','margin','Margin',0,1,'',100,17,115,5,'N~O',1,0,'BAS',0,'',0,0,0),(21,763,'image','vtiger_inventoryproductrel',1,'56','image','Image',0,1,'',100,15,114,5,'V~O',1,0,'BAS',0,'',0,0,0),(22,764,'image','vtiger_inventoryproductrel',1,'56','image','Image',0,1,'',100,16,113,5,'V~O',1,0,'BAS',0,'',0,0,0),(22,765,'purchase_cost','vtiger_inventoryproductrel',1,'71','purchase_cost','Purchase Cost',0,1,'',100,17,113,5,'N~O',1,0,'BAS',0,'',0,0,0),(22,766,'margin','vtiger_inventoryproductrel',1,'71','margin','Margin',0,1,'',100,18,113,5,'N~O',1,0,'BAS',0,'',0,0,0),(37,767,'is_private','vtiger_modcomments',1,'7','is_private','Is Private',1,2,'',100,9,94,1,'I~O',1,0,'BAS',1,'',0,0,0),(37,768,'filename','vtiger_modcomments',1,'61','filename','Attachment',1,0,'',100,10,94,1,'V~O',1,0,'BAS',1,'',0,0,0),(37,769,'related_email_id','vtiger_modcomments',1,'1','related_email_id','Related Email Id',1,2,'0',100,11,94,1,'I~O',1,0,'BAS',1,'',0,0,0),(2,770,'source','vtiger_potential',1,'1','source','Source',1,2,'',100,20,1,2,'V~O',3,7,'BAS',0,'',0,0,0),(4,771,'source','vtiger_contactdetails',1,'1','source','Source',1,2,'',100,30,4,2,'V~O',3,7,'BAS',0,'',0,0,0),(6,772,'source','vtiger_account',1,'1','source','Source',1,2,'',100,25,9,2,'V~O',3,5,'BAS',0,'',0,0,0),(7,773,'source','vtiger_leaddetails',1,'1','source','Source',1,2,'',100,25,13,2,'V~O',3,7,'BAS',0,'',0,0,0),(8,774,'source','vtiger_notes',1,'1','source','Source',1,2,'',100,13,17,2,'V~O',3,4,'BAS',0,'',0,0,0),(9,775,'source','vtiger_activity',1,'1','source','Source',1,2,'',100,23,19,2,'V~O',3,5,'BAS',0,'',0,0,0),(10,776,'source','vtiger_activity',1,'1','source','Source',1,2,'',100,13,21,2,'V~O',3,1,'BAS',0,'',0,0,0),(13,777,'source','vtiger_troubletickets',1,'1','source','Source',1,2,'',100,18,25,2,'V~O',3,5,'BAS',0,'',0,0,0),(14,778,'source','vtiger_products',1,'1','source','Source',1,2,'',100,23,31,2,'V~O',3,6,'BAS',0,'',0,0,0),(15,779,'source','vtiger_faq',1,'1','source','Source',1,2,'',100,9,37,2,'V~O',3,1,'BAS',0,'',0,0,0),(16,780,'source','vtiger_activity',1,'1','source','Source',1,2,'',100,23,39,2,'V~O',3,7,'BAS',0,'',0,0,0),(18,781,'source','vtiger_vendor',1,'1','source','Source',1,2,'',100,14,42,2,'V~O',3,4,'BAS',0,'',0,0,0),(19,782,'source','vtiger_pricebook',1,'1','source','Source',1,2,'',100,8,46,2,'V~O',3,4,'BAS',0,'',0,0,0),(20,783,'source','vtiger_quotes',1,'1','source','Source',1,2,'',100,24,49,2,'V~O',3,2,'BAS',0,'',0,0,0),(21,784,'source','vtiger_purchaseorder',1,'1','source','Source',1,2,'',100,26,55,2,'V~O',3,2,'BAS',0,'',0,0,0),(22,785,'source','vtiger_salesorder',1,'1','source','Source',1,2,'',100,24,61,2,'V~O',3,2,'BAS',0,'',0,0,0),(23,786,'source','vtiger_invoice',1,'1','source','Source',1,2,'',100,27,67,2,'V~O',3,2,'BAS',0,'',0,0,0),(26,787,'source','vtiger_campaign',1,'1','source','Source',1,2,'',100,17,74,2,'V~O',3,8,'BAS',0,'',0,0,0),(10,788,'click_count','vtiger_email_track',1,'25','click_count','Click Count',1,2,'0',100,14,21,3,'I~O',0,2,'BAS',0,'',0,0,0),(2,789,'starred','vtiger_crmentity_user_field',1,'56','starred','starred',1,2,'',100,21,1,6,'C~O',3,8,'BAS',0,'',0,0,0),(4,790,'starred','vtiger_crmentity_user_field',1,'56','starred','starred',1,2,'',100,31,4,6,'C~O',3,8,'BAS',0,'',0,0,0),(6,791,'starred','vtiger_crmentity_user_field',1,'56','starred','starred',1,2,'',100,26,9,6,'C~O',3,6,'BAS',0,'',0,0,0),(7,792,'starred','vtiger_crmentity_user_field',1,'56','starred','starred',1,2,'',100,26,13,6,'C~O',3,8,'BAS',0,'',0,0,0),(8,793,'starred','vtiger_crmentity_user_field',1,'56','starred','starred',1,2,'',100,14,17,6,'C~O',3,5,'BAS',0,'',0,0,0),(9,794,'starred','vtiger_crmentity_user_field',1,'56','starred','starred',1,2,'',100,24,19,6,'C~O',3,6,'BAS',0,'',0,0,0),(10,795,'starred','vtiger_crmentity_user_field',1,'56','starred','starred',1,2,'',100,15,21,6,'C~O',3,3,'BAS',0,'',0,0,0),(13,796,'starred','vtiger_crmentity_user_field',1,'56','starred','starred',1,2,'',100,19,25,6,'C~O',3,6,'BAS',0,'',0,0,0),(14,797,'starred','vtiger_crmentity_user_field',1,'56','starred','starred',1,2,'',100,24,31,6,'C~O',3,7,'BAS',0,'',0,0,0),(15,798,'starred','vtiger_crmentity_user_field',1,'56','starred','starred',1,2,'',100,10,37,6,'C~O',3,2,'BAS',0,'',0,0,0),(16,799,'starred','vtiger_crmentity_user_field',1,'56','starred','starred',1,2,'',100,24,39,6,'C~O',3,8,'BAS',0,'',0,0,0),(18,800,'starred','vtiger_crmentity_user_field',1,'56','starred','starred',1,2,'',100,15,42,6,'C~O',3,5,'BAS',0,'',0,0,0),(19,801,'starred','vtiger_crmentity_user_field',1,'56','starred','starred',1,2,'',100,9,46,6,'C~O',3,5,'BAS',0,'',0,0,0),(20,802,'starred','vtiger_crmentity_user_field',1,'56','starred','starred',1,2,'',100,25,49,6,'C~O',3,3,'BAS',0,'',0,0,0),(21,803,'starred','vtiger_crmentity_user_field',1,'56','starred','starred',1,2,'',100,27,55,6,'C~O',3,3,'BAS',0,'',0,0,0),(22,804,'starred','vtiger_crmentity_user_field',1,'56','starred','starred',1,2,'',100,25,61,6,'C~O',3,3,'BAS',0,'',0,0,0),(23,805,'starred','vtiger_crmentity_user_field',1,'56','starred','starred',1,2,'',100,28,67,6,'C~O',3,3,'BAS',0,'',0,0,0),(26,806,'starred','vtiger_crmentity_user_field',1,'56','starred','starred',1,2,'',100,18,74,6,'C~O',3,9,'BAS',0,'',0,0,0),(2,807,'tags','vtiger_potential',1,'1','tags','tags',1,2,'',100,22,1,6,'V~O',3,9,'BAS',0,'',0,0,0),(4,808,'tags','vtiger_contactdetails',1,'1','tags','tags',1,2,'',100,32,4,6,'V~O',3,9,'BAS',0,'',0,0,0),(6,809,'tags','vtiger_account',1,'1','tags','tags',1,2,'',100,27,9,6,'V~O',3,7,'BAS',0,'',0,0,0),(7,810,'tags','vtiger_leaddetails',1,'1','tags','tags',1,2,'',100,27,13,6,'V~O',3,9,'BAS',0,'',0,0,0),(8,811,'tags','vtiger_notes',1,'1','tags','tags',1,2,'',100,15,17,6,'V~O',3,6,'BAS',0,'',0,0,0),(9,812,'tags','vtiger_activity',1,'1','tags','tags',1,2,'',100,25,19,6,'V~O',3,7,'BAS',0,'',0,0,0),(10,813,'tags','vtiger_activity',1,'1','tags','tags',1,2,'',100,16,21,6,'V~O',3,4,'BAS',0,'',0,0,0),(13,814,'tags','vtiger_troubletickets',1,'1','tags','tags',1,2,'',100,20,25,6,'V~O',3,7,'BAS',0,'',0,0,0),(14,815,'tags','vtiger_products',1,'1','tags','tags',1,2,'',100,25,31,6,'V~O',3,8,'BAS',0,'',0,0,0),(15,816,'tags','vtiger_faq',1,'1','tags','tags',1,2,'',100,11,37,6,'V~O',3,3,'BAS',0,'',0,0,0),(16,817,'tags','vtiger_activity',1,'1','tags','tags',1,2,'',100,25,39,6,'V~O',3,9,'BAS',0,'',0,0,0),(18,818,'tags','vtiger_vendor',1,'1','tags','tags',1,2,'',100,16,42,6,'V~O',3,6,'BAS',0,'',0,0,0),(19,819,'tags','vtiger_pricebook',1,'1','tags','tags',1,2,'',100,10,46,6,'V~O',3,6,'BAS',0,'',0,0,0),(20,820,'tags','vtiger_quotes',1,'1','tags','tags',1,2,'',100,26,49,6,'V~O',3,4,'BAS',0,'',0,0,0),(21,821,'tags','vtiger_purchaseorder',1,'1','tags','tags',1,2,'',100,28,55,6,'V~O',3,4,'BAS',0,'',0,0,0),(22,822,'tags','vtiger_salesorder',1,'1','tags','tags',1,2,'',100,26,61,6,'V~O',3,4,'BAS',0,'',0,0,0),(23,823,'tags','vtiger_invoice',1,'1','tags','tags',1,2,'',100,29,67,6,'V~O',3,4,'BAS',0,'',0,0,0),(26,824,'tags','vtiger_campaign',1,'1','tags','tags',1,2,'',100,19,74,6,'V~O',3,10,'BAS',0,'',0,0,0),(20,825,'region_id','vtiger_quotes',1,'16','region_id','Tax Region',0,2,'',100,18,115,5,'N~O',1,0,'BAS',0,'',0,0,0),(21,826,'region_id','vtiger_purchaseorder',1,'16','region_id','Tax Region',0,2,'',100,16,114,5,'N~O',1,0,'BAS',0,'',0,0,0),(22,827,'region_id','vtiger_salesorder',1,'16','region_id','Tax Region',0,2,'',100,19,113,5,'N~O',1,0,'BAS',0,'',0,0,0),(23,828,'region_id','vtiger_invoice',1,'16','region_id','Tax Region',0,2,'',100,17,112,5,'N~O',1,0,'BAS',0,'',0,0,0),(41,829,'isconvertedfrompotential','vtiger_project',1,'56','isconvertedfrompotential','Is Converted From Opportunity',1,2,'',100,13,103,1,'C~O',1,0,'BAS',1,'',0,0,0),(41,830,'potentialid','vtiger_project',1,'10','potentialid','Potential Name',1,2,'',100,14,103,1,'I~O',1,0,'BAS',1,'',0,0,0),(29,831,'defaultlandingpage','vtiger_users',1,'32','defaultlandingpage','Default Landing Page',1,0,'Home',100,21,79,1,'V~O',1,0,'BAS',1,'',0,0,0),(22,832,'last_recurring_date','vtiger_invoice_recurring_info',1,'5','last_recurring_date','Next Invoice Date',1,2,'',100,7,85,2,'D~O',1,0,'BAS',1,'',0,0,0),(49,833,'dailyreportsname','vtiger_dailyreports',1,'2','dailyreportsname','LBL_SUBJECT',1,2,'',100,1,120,1,'V~M',1,0,'BAS',1,'',1,0,0),(49,834,'reportsterm','vtiger_dailyreports',1,'15','reportsterm','LBL_DAILY_WEEKLY_REPORT',1,2,'Day',100,2,120,1,'V~M',1,0,'BAS',1,'',1,0,0),(49,835,'reportsdate','vtiger_dailyreports',1,'23','ReportsDate','LBL_SUBMISSION_DATE',1,2,'',100,3,120,1,'D~M',1,0,'BAS',1,'',1,0,0),(49,836,'dailyreportsstatus','vtiger_dailyreports',1,'15','dailyreportsstatus','LBL_STATUS',1,2,'',100,4,120,1,'V~M',1,0,'BAS',1,'',1,0,0),(49,837,'reports_to_id','vtiger_dailyreports',1,'10','reports_to_id','LBL_WHERE_TO_SUBMIT',1,2,'',100,5,120,1,'V~M',1,0,'BAS',1,'',1,0,0),(49,838,'smownerid','vtiger_dailyreports',1,'53','assigned_user_id','Assigned To',1,2,'',100,6,120,1,'V~M',0,1,'BAS',0,'',1,0,0),(49,839,'dailyreportscomment','vtiger_dailyreports',1,'21','dailyreportscomment','LBL_COMMENT',1,2,'',100,7,120,1,'V~O',1,0,'BAS',1,'',1,0,0),(49,840,'createdtime','vtiger_dailyreports',1,'70','createdtime','LBL_CREATE_DATETIME',1,2,'',100,8,120,2,'DT~O',0,2,'BAS',0,'',0,0,0),(49,841,'modifiedtime','vtiger_dailyreports',1,'70','modifiedtime','LBL_UPDATE_DATETIME',1,2,'',100,9,120,2,'DT~O',0,3,'BAS',0,'',0,0,0),(49,842,'tags','vtiger_dailyreports',1,'1','tags','tags',1,2,'',100,10,120,6,'V~O',3,4,'BAS',0,'',0,0,0),(20,843,'usageunit','vtiger_inventoryproductrel',1,'1','usageunit','LBL_USAGE_UNIT',0,2,'',100,19,115,5,'V~O~LE~200',1,0,'BAS',0,'',0,0,0),(21,844,'usageunit','vtiger_inventoryproductrel',1,'1','usageunit','LBL_USAGE_UNIT',0,2,'',100,17,114,5,'V~O~LE~200',1,0,'BAS',0,'',0,0,0),(22,845,'usageunit','vtiger_inventoryproductrel',1,'1','usageunit','LBL_USAGE_UNIT',0,2,'',100,20,113,5,'V~O~LE~200',1,0,'BAS',0,'',0,0,0),(23,846,'usageunit','vtiger_inventoryproductrel',1,'1','usageunit','LBL_USAGE_UNIT',0,2,'',100,18,112,5,'V~O~LE~200',1,0,'BAS',0,'',0,0,0),(14,847,'reducedtaxrate','vtiger_products',1,'56','reducedtaxrate','Reduced TaxRate',1,2,'0',100,6,32,1,'C~O',1,0,'BAS',1,'',0,0,0),(20,848,'reducedtaxrate','vtiger_inventoryproductrel',1,'56','reducedtaxrate','LBL_ELIGIBLE_FOR_REDUCED_TAX_RATE',1,2,'',100,20,115,5,'C~O',1,0,'BAS',1,'',0,0,0),(21,849,'reducedtaxrate','vtiger_inventoryproductrel',1,'56','reducedtaxrate','LBL_ELIGIBLE_FOR_REDUCED_TAX_RATE',1,2,'',100,18,114,5,'C~O',1,0,'BAS',1,'',0,0,0),(22,850,'reducedtaxrate','vtiger_inventoryproductrel',1,'56','reducedtaxrate','LBL_ELIGIBLE_FOR_REDUCED_TAX_RATE',1,2,'',100,21,113,5,'C~O',1,0,'BAS',1,'',0,0,0),(23,851,'reducedtaxrate','vtiger_inventoryproductrel',1,'56','reducedtaxrate','LBL_ELIGIBLE_FOR_REDUCED_TAX_RATE',1,2,'',100,19,112,5,'C~O',1,0,'BAS',1,'',0,0,0),(20,852,'reducedtaxrate','vtiger_inventoryproductrel',1,'56','reducedtaxrate','LBL_ELIGIBLE_FOR_REDUCED_TAX_RATE',1,2,'',100,21,115,5,'C~O',1,0,'BAS',1,'',0,0,0),(21,853,'reducedtaxrate','vtiger_inventoryproductrel',1,'56','reducedtaxrate','LBL_ELIGIBLE_FOR_REDUCED_TAX_RATE',1,2,'',100,19,114,5,'C~O',1,0,'BAS',1,'',0,0,0),(22,854,'reducedtaxrate','vtiger_inventoryproductrel',1,'56','reducedtaxrate','LBL_ELIGIBLE_FOR_REDUCED_TAX_RATE',1,2,'',100,22,113,5,'C~O',1,0,'BAS',1,'',0,0,0),(23,855,'reducedtaxrate','vtiger_inventoryproductrel',1,'56','reducedtaxrate','LBL_ELIGIBLE_FOR_REDUCED_TAX_RATE',1,2,'',100,20,112,5,'C~O',1,0,'BAS',1,'',0,0,0),(29,856,'userlabel','vtiger_users',1,'1','userlabel','User Label',1,2,'',100,22,79,3,'V~O',1,0,'BAS',1,'',0,0,0),(16,857,'smcreatorid','vtiger_activity',1,'52','creator','LBL_CREATOR',1,2,'',100,26,39,2,'V~O',0,10,'BAS',0,'',0,0,0),(29,858,'sharedcalendartodoview','vtiger_users',1,'16','sharedcalendartodoview','Shared Calendar Todo View',1,0,'Hidden',100,15,117,1,'V~O',1,0,'BAS',1,'',0,0,0),(16,859,'common_memo','vtiger_activity',1,'19','common_memo','Common Memo',1,2,'',100,2,41,1,'V~O',2,11,'BAS',1,'',0,0,0),(16,860,'send_mail','vtiger_activity',1,'56','send_mail','LBL_SEND_MAIL',1,0,'',100,27,39,1,'C~O',2,12,'BAS',1,'',0,0,0),(2,861,'last_action_date','vtiger_potential',1,'5','last_action_date','last_action_date',1,2,'',100,23,1,1,'D~O',2,10,'BAS',1,'',1,0,0),(6,862,'last_action_date','vtiger_account',1,'5','last_action_date','last_action_date',1,2,'',100,28,9,1,'D~O',2,8,'BAS',1,'',1,0,0),(4,863,'last_action_date','vtiger_contactdetails',1,'5','last_action_date','last_action_date',1,2,'',100,33,4,1,'D~O',2,10,'BAS',1,'',1,0,0),(7,864,'last_action_date','vtiger_leaddetails',1,'5','last_action_date','last_action_date',1,2,'',100,28,13,1,'D~O',2,10,'BAS',1,'',1,0,0),(23,865,'tax4','vtiger_inventoryproductrel',1,'83','tax4','LBL_CONSUMPTION_TAX',0,2,'',100,21,112,5,'V~O',1,0,'BAS',0,'',0,0,0),(20,866,'tax4','vtiger_inventoryproductrel',1,'83','tax4','LBL_CONSUMPTION_TAX',0,2,'',100,22,115,5,'V~O',1,0,'BAS',0,'',0,0,0),(21,867,'tax4','vtiger_inventoryproductrel',1,'83','tax4','LBL_CONSUMPTION_TAX',0,2,'',100,20,114,5,'V~O',1,0,'BAS',0,'',0,0,0),(22,868,'tax4','vtiger_inventoryproductrel',1,'83','tax4','LBL_CONSUMPTION_TAX',0,2,'',100,23,113,5,'V~O',1,0,'BAS',0,'',0,0,0);
/*!40000 ALTER TABLE `vtiger_field` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_field_seq`
--

DROP TABLE IF EXISTS `vtiger_field_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_field_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_field_seq`
--

LOCK TABLES `vtiger_field_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_field_seq` DISABLE KEYS */;
INSERT INTO `vtiger_field_seq` VALUES (868);
/*!40000 ALTER TABLE `vtiger_field_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_fieldmodulerel`
--

DROP TABLE IF EXISTS `vtiger_fieldmodulerel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_fieldmodulerel` (
  `fieldid` int NOT NULL,
  `module` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `relmodule` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `status` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sequence` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_fieldmodulerel`
--

LOCK TABLES `vtiger_fieldmodulerel` WRITE;
/*!40000 ALTER TABLE `vtiger_fieldmodulerel` DISABLE KEYS */;
INSERT INTO `vtiger_fieldmodulerel` VALUES (113,'Potentials','Accounts',NULL,0),(531,'ServiceContracts','Contacts',NULL,NULL),(531,'ServiceContracts','Accounts',NULL,NULL),(574,'ModComments','Leads',NULL,NULL),(574,'ModComments','Contacts',NULL,NULL),(574,'ModComments','Accounts',NULL,NULL),(576,'ModComments','ModComments',NULL,NULL),(574,'ModComments','Potentials',NULL,NULL),(580,'ProjectMilestone','Project',NULL,NULL),(594,'ProjectTask','Project',NULL,NULL),(574,'ModComments','ProjectTask',NULL,NULL),(615,'Project','Accounts',NULL,NULL),(615,'Project','Contacts',NULL,NULL),(574,'ModComments','Project',NULL,NULL),(636,'Assets','Products',NULL,NULL),(642,'Assets','Invoice',NULL,NULL),(647,'Assets','Accounts',NULL,NULL),(648,'Assets','Contacts',NULL,NULL),(658,'Invoice','Products',NULL,NULL),(658,'Invoice','Services',NULL,NULL),(667,'SalesOrder','Products',NULL,NULL),(667,'SalesOrder','Services',NULL,NULL),(676,'PurchaseOrder','Products',NULL,NULL),(676,'PurchaseOrder','Services',NULL,NULL),(685,'Quotes','Products',NULL,NULL),(685,'Quotes','Services',NULL,NULL),(696,'ModComments','Contacts',NULL,NULL),(574,'ModComments','HelpDesk',NULL,NULL),(574,'ModComments','Faq',NULL,NULL),(722,'Potentials','Contacts',NULL,NULL),(157,'HelpDesk','Accounts',NULL,NULL),(723,'HelpDesk','Contacts',NULL,NULL),(238,'Accounts','Calendar',NULL,NULL),(238,'Leads','Calendar',NULL,NULL),(238,'HelpDesk','Calendar',NULL,NULL),(238,'Campaigns','Calendar',NULL,NULL),(238,'Potentials','Calendar',NULL,NULL),(238,'PurchaseOrder','Calendar',NULL,NULL),(238,'SalesOrder','Calendar',NULL,NULL),(238,'Quotes','Calendar',NULL,NULL),(238,'Invoice','Calendar',NULL,NULL),(239,'Contacts','Calendar',NULL,NULL),(755,'Invoice','Potentials',NULL,NULL),(574,'ModComments','Invoice',NULL,NULL),(574,'ModComments','Quotes',NULL,NULL),(574,'ModComments','PurchaseOrder',NULL,NULL),(574,'ModComments','SalesOrder',NULL,NULL),(830,'Project','Potentials',NULL,NULL),(837,'Dailyreports','Users',NULL,NULL),(838,'Dailyreports','Users',NULL,NULL),(574,'ModComments','Dailyreports',NULL,NULL),(72,'Contacts','Accounts',NULL,NULL),(129,'Campaigns','Products',NULL,NULL),(159,'HelpDesk','Products',NULL,NULL),(279,'Faq','Products',NULL,NULL),(316,'Quotes','Potentials',NULL,NULL),(319,'Quotes','Contacts',NULL,NULL),(330,'Quotes','Accounts',NULL,NULL),(356,'PurchaseOrder','Contacts',NULL,NULL),(452,'Invoice','Accounts',NULL,NULL);
/*!40000 ALTER TABLE `vtiger_fieldmodulerel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_freetagged_objects`
--

DROP TABLE IF EXISTS `vtiger_freetagged_objects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_freetagged_objects` (
  `tag_id` int NOT NULL DEFAULT '0',
  `tagger_id` int NOT NULL DEFAULT '0',
  `object_id` int NOT NULL DEFAULT '0',
  `tagged_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `module` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`tag_id`,`tagger_id`,`object_id`),
  KEY `freetagged_objects_tag_id_tagger_id_object_id_idx` (`tag_id`,`tagger_id`,`object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_freetagged_objects`
--

LOCK TABLES `vtiger_freetagged_objects` WRITE;
/*!40000 ALTER TABLE `vtiger_freetagged_objects` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_freetagged_objects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_freetags`
--

DROP TABLE IF EXISTS `vtiger_freetags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_freetags` (
  `id` int NOT NULL,
  `tag` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `raw_tag` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `visibility` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PRIVATE',
  `owner` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_freetags`
--

LOCK TABLES `vtiger_freetags` WRITE;
/*!40000 ALTER TABLE `vtiger_freetags` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_freetags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_freetags_seq`
--

DROP TABLE IF EXISTS `vtiger_freetags_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_freetags_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_freetags_seq`
--

LOCK TABLES `vtiger_freetags_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_freetags_seq` DISABLE KEYS */;
INSERT INTO `vtiger_freetags_seq` VALUES (1);
/*!40000 ALTER TABLE `vtiger_freetags_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_glacct`
--

DROP TABLE IF EXISTS `vtiger_glacct`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_glacct` (
  `glacctid` int NOT NULL AUTO_INCREMENT,
  `glacct` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT NULL,
  `color` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`glacctid`),
  UNIQUE KEY `glacct_glacct_idx` (`glacct`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_glacct`
--

LOCK TABLES `vtiger_glacct` WRITE;
/*!40000 ALTER TABLE `vtiger_glacct` DISABLE KEYS */;
INSERT INTO `vtiger_glacct` VALUES (1,'300-Sales-Software',1,51,0,NULL),(2,'301-Sales-Hardware',1,52,1,NULL),(3,'302-Rental-Income',1,53,2,NULL),(4,'303-Interest-Income',1,54,3,NULL),(5,'304-Sales-Software-Support',1,55,4,NULL),(6,'305-Sales Other',1,56,5,NULL),(7,'306-Internet Sales',1,57,6,NULL),(8,'307-Service-Hardware Labor',1,58,7,NULL),(9,'308-Sales-Books',1,59,8,NULL);
/*!40000 ALTER TABLE `vtiger_glacct` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_glacct_seq`
--

DROP TABLE IF EXISTS `vtiger_glacct_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_glacct_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_glacct_seq`
--

LOCK TABLES `vtiger_glacct_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_glacct_seq` DISABLE KEYS */;
INSERT INTO `vtiger_glacct_seq` VALUES (9);
/*!40000 ALTER TABLE `vtiger_glacct_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_google_event_calendar_mapping`
--

DROP TABLE IF EXISTS `vtiger_google_event_calendar_mapping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_google_event_calendar_mapping` (
  `event_id` varchar(255) DEFAULT NULL,
  `calendar_id` varchar(255) DEFAULT NULL,
  `user_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_google_event_calendar_mapping`
--

LOCK TABLES `vtiger_google_event_calendar_mapping` WRITE;
/*!40000 ALTER TABLE `vtiger_google_event_calendar_mapping` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_google_event_calendar_mapping` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_google_oauth2`
--

DROP TABLE IF EXISTS `vtiger_google_oauth2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_google_oauth2` (
  `service` varchar(20) DEFAULT NULL,
  `access_token` varchar(500) DEFAULT NULL,
  `refresh_token` varchar(500) DEFAULT NULL,
  `userid` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_google_oauth2`
--

LOCK TABLES `vtiger_google_oauth2` WRITE;
/*!40000 ALTER TABLE `vtiger_google_oauth2` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_google_oauth2` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_google_sync_fieldmapping`
--

DROP TABLE IF EXISTS `vtiger_google_sync_fieldmapping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_google_sync_fieldmapping` (
  `vtiger_field` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `google_field` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `google_field_type` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `google_custom_label` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_google_sync_fieldmapping`
--

LOCK TABLES `vtiger_google_sync_fieldmapping` WRITE;
/*!40000 ALTER TABLE `vtiger_google_sync_fieldmapping` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_google_sync_fieldmapping` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_google_sync_settings`
--

DROP TABLE IF EXISTS `vtiger_google_sync_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_google_sync_settings` (
  `user` int DEFAULT NULL,
  `module` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `clientgroup` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `direction` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `enabled` tinyint DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_google_sync_settings`
--

LOCK TABLES `vtiger_google_sync_settings` WRITE;
/*!40000 ALTER TABLE `vtiger_google_sync_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_google_sync_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_group2grouprel`
--

DROP TABLE IF EXISTS `vtiger_group2grouprel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_group2grouprel` (
  `groupid` int NOT NULL,
  `containsgroupid` int NOT NULL,
  PRIMARY KEY (`groupid`,`containsgroupid`),
  CONSTRAINT `fk_2_vtiger_group2grouprel` FOREIGN KEY (`groupid`) REFERENCES `vtiger_groups` (`groupid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_group2grouprel`
--

LOCK TABLES `vtiger_group2grouprel` WRITE;
/*!40000 ALTER TABLE `vtiger_group2grouprel` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_group2grouprel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_group2role`
--

DROP TABLE IF EXISTS `vtiger_group2role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_group2role` (
  `groupid` int NOT NULL,
  `roleid` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`groupid`,`roleid`),
  KEY `fk_2_vtiger_group2role` (`roleid`),
  CONSTRAINT `fk_2_vtiger_group2role` FOREIGN KEY (`roleid`) REFERENCES `vtiger_role` (`roleid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_group2role`
--

LOCK TABLES `vtiger_group2role` WRITE;
/*!40000 ALTER TABLE `vtiger_group2role` DISABLE KEYS */;
INSERT INTO `vtiger_group2role` VALUES (3,'H2'),(4,'H3'),(2,'H4');
/*!40000 ALTER TABLE `vtiger_group2role` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_group2rs`
--

DROP TABLE IF EXISTS `vtiger_group2rs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_group2rs` (
  `groupid` int NOT NULL,
  `roleandsubid` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`groupid`,`roleandsubid`),
  KEY `fk_2_vtiger_group2rs` (`roleandsubid`),
  CONSTRAINT `fk_2_vtiger_group2rs` FOREIGN KEY (`roleandsubid`) REFERENCES `vtiger_role` (`roleid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_group2rs`
--

LOCK TABLES `vtiger_group2rs` WRITE;
/*!40000 ALTER TABLE `vtiger_group2rs` DISABLE KEYS */;
INSERT INTO `vtiger_group2rs` VALUES (3,'H3'),(4,'H3'),(2,'H5');
/*!40000 ALTER TABLE `vtiger_group2rs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_groups`
--

DROP TABLE IF EXISTS `vtiger_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_groups` (
  `groupid` int NOT NULL,
  `groupname` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`groupid`),
  UNIQUE KEY `groups_groupname_idx` (`groupname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_groups`
--

LOCK TABLES `vtiger_groups` WRITE;
/*!40000 ALTER TABLE `vtiger_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_home_layout`
--

DROP TABLE IF EXISTS `vtiger_home_layout`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_home_layout` (
  `userid` int NOT NULL,
  `layout` int NOT NULL DEFAULT '4',
  PRIMARY KEY (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_home_layout`
--

LOCK TABLES `vtiger_home_layout` WRITE;
/*!40000 ALTER TABLE `vtiger_home_layout` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_home_layout` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_homedashbd`
--

DROP TABLE IF EXISTS `vtiger_homedashbd`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_homedashbd` (
  `stuffid` int NOT NULL,
  `dashbdname` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dashbdtype` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`stuffid`),
  KEY `stuff_stuffid_idx` (`stuffid`),
  CONSTRAINT `fk_1_vtiger_homedashbd` FOREIGN KEY (`stuffid`) REFERENCES `vtiger_homestuff` (`stuffid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_homedashbd`
--

LOCK TABLES `vtiger_homedashbd` WRITE;
/*!40000 ALTER TABLE `vtiger_homedashbd` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_homedashbd` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_homedefault`
--

DROP TABLE IF EXISTS `vtiger_homedefault`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_homedefault` (
  `stuffid` int NOT NULL,
  `hometype` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `maxentries` int DEFAULT NULL,
  `setype` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`stuffid`),
  KEY `stuff_stuffid_idx` (`stuffid`),
  CONSTRAINT `fk_1_vtiger_homedefault` FOREIGN KEY (`stuffid`) REFERENCES `vtiger_homestuff` (`stuffid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_homedefault`
--

LOCK TABLES `vtiger_homedefault` WRITE;
/*!40000 ALTER TABLE `vtiger_homedefault` DISABLE KEYS */;
INSERT INTO `vtiger_homedefault` VALUES (1,'ALVT',5,'Accounts'),(2,'HDB',5,'Dashboard'),(3,'PLVT',5,'Potentials'),(4,'QLTQ',5,'Quotes'),(5,'CVLVT',5,'NULL'),(6,'HLT',5,'HelpDesk'),(7,'UA',5,'Calendar'),(8,'GRT',5,'NULL'),(9,'OLTSO',5,'SalesOrder'),(10,'ILTI',5,'Invoice'),(11,'MNL',5,'Leads'),(12,'OLTPO',5,'PurchaseOrder'),(13,'PA',5,'Calendar'),(14,'LTFAQ',5,'Faq');
/*!40000 ALTER TABLE `vtiger_homedefault` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_homemodule`
--

DROP TABLE IF EXISTS `vtiger_homemodule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_homemodule` (
  `stuffid` int NOT NULL,
  `modulename` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `maxentries` int NOT NULL,
  `customviewid` int NOT NULL,
  `setype` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`stuffid`),
  KEY `stuff_stuffid_idx` (`stuffid`),
  CONSTRAINT `fk_1_vtiger_homemodule` FOREIGN KEY (`stuffid`) REFERENCES `vtiger_homestuff` (`stuffid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_homemodule`
--

LOCK TABLES `vtiger_homemodule` WRITE;
/*!40000 ALTER TABLE `vtiger_homemodule` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_homemodule` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_homemoduleflds`
--

DROP TABLE IF EXISTS `vtiger_homemoduleflds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_homemoduleflds` (
  `stuffid` int DEFAULT NULL,
  `fieldname` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  KEY `stuff_stuffid_idx` (`stuffid`),
  CONSTRAINT `fk_1_vtiger_homemoduleflds` FOREIGN KEY (`stuffid`) REFERENCES `vtiger_homemodule` (`stuffid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_homemoduleflds`
--

LOCK TABLES `vtiger_homemoduleflds` WRITE;
/*!40000 ALTER TABLE `vtiger_homemoduleflds` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_homemoduleflds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_homereportchart`
--

DROP TABLE IF EXISTS `vtiger_homereportchart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_homereportchart` (
  `stuffid` int NOT NULL,
  `reportid` int DEFAULT NULL,
  `reportcharttype` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`stuffid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_homereportchart`
--

LOCK TABLES `vtiger_homereportchart` WRITE;
/*!40000 ALTER TABLE `vtiger_homereportchart` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_homereportchart` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_homerss`
--

DROP TABLE IF EXISTS `vtiger_homerss`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_homerss` (
  `stuffid` int NOT NULL,
  `url` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `maxentries` int NOT NULL,
  PRIMARY KEY (`stuffid`),
  KEY `stuff_stuffid_idx` (`stuffid`),
  CONSTRAINT `fk_1_vtiger_homerss` FOREIGN KEY (`stuffid`) REFERENCES `vtiger_homestuff` (`stuffid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_homerss`
--

LOCK TABLES `vtiger_homerss` WRITE;
/*!40000 ALTER TABLE `vtiger_homerss` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_homerss` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_homestuff`
--

DROP TABLE IF EXISTS `vtiger_homestuff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_homestuff` (
  `stuffid` int NOT NULL DEFAULT '0',
  `stuffsequence` int NOT NULL DEFAULT '0',
  `stufftype` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `userid` int NOT NULL,
  `visible` int NOT NULL DEFAULT '0',
  `stufftitle` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`stuffid`),
  KEY `stuff_stuffid_idx` (`stuffid`),
  KEY `fk_1_vtiger_homestuff` (`userid`),
  CONSTRAINT `fk_1_vtiger_homestuff` FOREIGN KEY (`userid`) REFERENCES `vtiger_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_homestuff`
--

LOCK TABLES `vtiger_homestuff` WRITE;
/*!40000 ALTER TABLE `vtiger_homestuff` DISABLE KEYS */;
INSERT INTO `vtiger_homestuff` VALUES (1,1,'Default',1,1,'Top Accounts'),(2,2,'Default',1,1,'Home Page Dashboard'),(3,3,'Default',1,1,'Top Potentials'),(4,4,'Default',1,1,'Top Quotes'),(5,5,'Default',1,1,'Key Metrics'),(6,6,'Default',1,1,'Top Trouble Tickets'),(7,7,'Default',1,1,'Upcoming Activities'),(8,8,'Default',1,1,'My Group Allocation'),(9,9,'Default',1,1,'Top Sales Orders'),(10,10,'Default',1,1,'Top Invoices'),(11,11,'Default',1,1,'My New Leads'),(12,12,'Default',1,1,'Top Purchase Orders'),(13,13,'Default',1,1,'Pending Activities'),(14,14,'Default',1,1,'My Recent FAQs'),(15,15,'Tag Cloud',1,0,'Tag Cloud');
/*!40000 ALTER TABLE `vtiger_homestuff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_homestuff_seq`
--

DROP TABLE IF EXISTS `vtiger_homestuff_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_homestuff_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_homestuff_seq`
--

LOCK TABLES `vtiger_homestuff_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_homestuff_seq` DISABLE KEYS */;
INSERT INTO `vtiger_homestuff_seq` VALUES (15);
/*!40000 ALTER TABLE `vtiger_homestuff_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_hour_format`
--

DROP TABLE IF EXISTS `vtiger_hour_format`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_hour_format` (
  `hour_formatid` int NOT NULL AUTO_INCREMENT,
  `hour_format` varchar(200) NOT NULL,
  `sortorderid` int DEFAULT NULL,
  `presence` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`hour_formatid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_hour_format`
--

LOCK TABLES `vtiger_hour_format` WRITE;
/*!40000 ALTER TABLE `vtiger_hour_format` DISABLE KEYS */;
INSERT INTO `vtiger_hour_format` VALUES (1,'12',0,1),(2,'24',1,1);
/*!40000 ALTER TABLE `vtiger_hour_format` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_hour_format_seq`
--

DROP TABLE IF EXISTS `vtiger_hour_format_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_hour_format_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_hour_format_seq`
--

LOCK TABLES `vtiger_hour_format_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_hour_format_seq` DISABLE KEYS */;
INSERT INTO `vtiger_hour_format_seq` VALUES (2);
/*!40000 ALTER TABLE `vtiger_hour_format_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_import_locks`
--

DROP TABLE IF EXISTS `vtiger_import_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_import_locks` (
  `vtiger_import_lock_id` int NOT NULL,
  `userid` int NOT NULL,
  `tabid` int NOT NULL,
  `importid` int NOT NULL,
  `locked_since` datetime DEFAULT NULL,
  PRIMARY KEY (`vtiger_import_lock_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_import_locks`
--

LOCK TABLES `vtiger_import_locks` WRITE;
/*!40000 ALTER TABLE `vtiger_import_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_import_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_import_maps`
--

DROP TABLE IF EXISTS `vtiger_import_maps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_import_maps` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(36) COLLATE utf8mb4_general_ci NOT NULL,
  `module` varchar(36) COLLATE utf8mb4_general_ci NOT NULL,
  `content` longblob,
  `has_header` int NOT NULL DEFAULT '1',
  `deleted` int NOT NULL DEFAULT '0',
  `date_entered` timestamp NOT NULL,
  `date_modified` datetime DEFAULT NULL,
  `assigned_user_id` varchar(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_published` varchar(3) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'no',
  PRIMARY KEY (`id`),
  KEY `import_maps_assigned_user_id_module_name_deleted_idx` (`assigned_user_id`,`module`,`name`,`deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_import_maps`
--

LOCK TABLES `vtiger_import_maps` WRITE;
/*!40000 ALTER TABLE `vtiger_import_maps` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_import_maps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_import_queue`
--

DROP TABLE IF EXISTS `vtiger_import_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_import_queue` (
  `importid` int NOT NULL,
  `userid` int NOT NULL,
  `tabid` int NOT NULL,
  `field_mapping` text,
  `default_values` text,
  `merge_type` int DEFAULT NULL,
  `merge_fields` text,
  `status` int DEFAULT '0',
  `lineitem_currency_id` int DEFAULT NULL,
  `paging` int DEFAULT '0',
  `time_start` datetime DEFAULT NULL,
  `time_end` datetime DEFAULT NULL,
  PRIMARY KEY (`importid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_import_queue`
--

LOCK TABLES `vtiger_import_queue` WRITE;
/*!40000 ALTER TABLE `vtiger_import_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_import_queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_industry`
--

DROP TABLE IF EXISTS `vtiger_industry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_industry` (
  `industryid` int NOT NULL AUTO_INCREMENT,
  `industry` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT NULL,
  `color` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`industryid`),
  UNIQUE KEY `industry_industry_idx` (`industry`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_industry`
--

LOCK TABLES `vtiger_industry` WRITE;
/*!40000 ALTER TABLE `vtiger_industry` DISABLE KEYS */;
INSERT INTO `vtiger_industry` VALUES (2,'Apparel',1,61,1,NULL),(3,'Banking',1,62,2,NULL),(4,'Biotechnology',1,63,3,NULL),(5,'Chemicals',1,64,4,NULL),(6,'Communications',1,65,5,NULL),(7,'Construction',1,66,6,NULL),(8,'Consulting',1,67,7,NULL),(9,'Education',1,68,8,NULL),(10,'Electronics',1,69,9,NULL),(11,'Energy',1,70,10,NULL),(12,'Engineering',1,71,11,NULL),(13,'Entertainment',1,72,12,NULL),(14,'Environmental',1,73,13,NULL),(15,'Finance',1,74,14,NULL),(16,'Food & Beverage',1,75,15,NULL),(17,'Government',1,76,16,NULL),(18,'Healthcare',1,77,17,NULL),(19,'Hospitality',1,78,18,NULL),(20,'Insurance',1,79,19,NULL),(21,'Machinery',1,80,20,NULL),(22,'Manufacturing',1,81,21,NULL),(23,'Media',1,82,22,NULL),(24,'Not For Profit',1,83,23,NULL),(25,'Recreation',1,84,24,NULL),(26,'Retail',1,85,25,NULL),(27,'Shipping',1,86,26,NULL),(28,'Technology',1,87,27,NULL),(29,'Telecommunications',1,88,28,NULL),(30,'Transportation',1,89,29,NULL),(31,'Utilities',1,90,30,NULL),(32,'Other',1,91,31,NULL);
/*!40000 ALTER TABLE `vtiger_industry` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_industry_seq`
--

DROP TABLE IF EXISTS `vtiger_industry_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_industry_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_industry_seq`
--

LOCK TABLES `vtiger_industry_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_industry_seq` DISABLE KEYS */;
INSERT INTO `vtiger_industry_seq` VALUES (32);
/*!40000 ALTER TABLE `vtiger_industry_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_inventory_tandc`
--

DROP TABLE IF EXISTS `vtiger_inventory_tandc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_inventory_tandc` (
  `id` int NOT NULL,
  `type` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `tandc` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_inventory_tandc`
--

LOCK TABLES `vtiger_inventory_tandc` WRITE;
/*!40000 ALTER TABLE `vtiger_inventory_tandc` DISABLE KEYS */;
INSERT INTO `vtiger_inventory_tandc` VALUES (2,'Invoice',''),(3,'Quotes',''),(4,'PurchaseOrder',''),(5,'SalesOrder','');
/*!40000 ALTER TABLE `vtiger_inventory_tandc` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_inventory_tandc_seq`
--

DROP TABLE IF EXISTS `vtiger_inventory_tandc_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_inventory_tandc_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_inventory_tandc_seq`
--

LOCK TABLES `vtiger_inventory_tandc_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_inventory_tandc_seq` DISABLE KEYS */;
INSERT INTO `vtiger_inventory_tandc_seq` VALUES (5);
/*!40000 ALTER TABLE `vtiger_inventory_tandc_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_inventorycharges`
--

DROP TABLE IF EXISTS `vtiger_inventorycharges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_inventorycharges` (
  `chargeid` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `format` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `type` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `value` decimal(12,5) DEFAULT NULL,
  `regions` text COLLATE utf8mb4_general_ci,
  `istaxable` int NOT NULL DEFAULT '1',
  `taxes` varchar(1024) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`chargeid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_inventorycharges`
--

LOCK TABLES `vtiger_inventorycharges` WRITE;
/*!40000 ALTER TABLE `vtiger_inventorycharges` DISABLE KEYS */;
INSERT INTO `vtiger_inventorycharges` VALUES (1,'Shipping & Handling','Flat','Fixed',0.00000,'[]',1,'[\"1\",\"2\",\"3\"]',0);
/*!40000 ALTER TABLE `vtiger_inventorycharges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_inventorychargesrel`
--

DROP TABLE IF EXISTS `vtiger_inventorychargesrel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_inventorychargesrel` (
  `recordid` int NOT NULL,
  `charges` text COLLATE utf8mb4_general_ci,
  KEY `record_idx` (`recordid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_inventorychargesrel`
--

LOCK TABLES `vtiger_inventorychargesrel` WRITE;
/*!40000 ALTER TABLE `vtiger_inventorychargesrel` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_inventorychargesrel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_inventorynotification`
--

DROP TABLE IF EXISTS `vtiger_inventorynotification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_inventorynotification` (
  `notificationid` int NOT NULL AUTO_INCREMENT,
  `notificationname` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notificationsubject` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notificationbody` text COLLATE utf8mb4_general_ci,
  `label` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`notificationid`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_inventorynotification`
--

LOCK TABLES `vtiger_inventorynotification` WRITE;
/*!40000 ALTER TABLE `vtiger_inventorynotification` DISABLE KEYS */;
INSERT INTO `vtiger_inventorynotification` VALUES (1,'InvoiceNotification','{PRODUCTNAME} Stock Level is Low','Dear {HANDLER},\n\nThe current stock of {PRODUCTNAME} in our warehouse is {CURRENTSTOCK}. Kindly procure required number of units as the stock level is below reorder level {REORDERLEVELVALUE}.\n\nPlease treat this information as Urgent as the invoice is already sent  to the customer.\n\nSeverity: Critical\n\nThanks,\n{CURRENTUSER} ','InvoiceNotificationDescription',NULL),(2,'QuoteNotification','Quote given for {PRODUCTNAME}','Dear {HANDLER},\n\nQuote is generated for {QUOTEQUANTITY} units of {PRODUCTNAME}. The current stock of {PRODUCTNAME} in our warehouse is {CURRENTSTOCK}.\n\nSeverity: Minor\n\nThanks,\n{CURRENTUSER} ','QuoteNotificationDescription',NULL),(3,'SalesOrderNotification','Sales Order generated for {PRODUCTNAME}','Dear {HANDLER},\n\nSalesOrder is generated for {SOQUANTITY} units of {PRODUCTNAME}. The current stock of {PRODUCTNAME} in our warehouse is {CURRENTSTOCK}.\n\nPlease treat this information  with priority as the sales order is already generated.\n\nSeverity: Major\n\nThanks,\n{CURRENTUSER} ','SalesOrderNotificationDescription',NULL);
/*!40000 ALTER TABLE `vtiger_inventorynotification` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_inventorynotification_seq`
--

DROP TABLE IF EXISTS `vtiger_inventorynotification_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_inventorynotification_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_inventorynotification_seq`
--

LOCK TABLES `vtiger_inventorynotification_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_inventorynotification_seq` DISABLE KEYS */;
INSERT INTO `vtiger_inventorynotification_seq` VALUES (3);
/*!40000 ALTER TABLE `vtiger_inventorynotification_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_inventoryproductrel`
--

DROP TABLE IF EXISTS `vtiger_inventoryproductrel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_inventoryproductrel` (
  `id` int DEFAULT NULL,
  `productid` int DEFAULT NULL,
  `sequence_no` int DEFAULT NULL,
  `quantity` decimal(25,3) DEFAULT NULL,
  `listprice` decimal(27,8) DEFAULT NULL,
  `discount_percent` decimal(7,3) DEFAULT NULL,
  `discount_amount` decimal(27,8) DEFAULT NULL,
  `comment` text COLLATE utf8mb4_general_ci,
  `description` text COLLATE utf8mb4_general_ci,
  `incrementondel` int NOT NULL DEFAULT '0',
  `lineitem_id` int NOT NULL AUTO_INCREMENT,
  `tax1` decimal(7,3) DEFAULT NULL,
  `tax2` decimal(7,3) DEFAULT NULL,
  `tax3` decimal(7,3) DEFAULT NULL,
  `image` varchar(2) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `purchase_cost` decimal(27,8) DEFAULT NULL,
  `margin` decimal(27,8) DEFAULT NULL,
  `usageunit` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reducedtaxrate` int DEFAULT NULL,
  `tax4` decimal(7,3) DEFAULT NULL,
  PRIMARY KEY (`lineitem_id`),
  KEY `inventoryproductrel_id_idx` (`id`),
  KEY `inventoryproductrel_productid_idx` (`productid`),
  KEY `productid` (`productid`),
  CONSTRAINT `fk_crmid_vtiger_inventoryproductrel` FOREIGN KEY (`id`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_inventoryproductrel`
--

LOCK TABLES `vtiger_inventoryproductrel` WRITE;
/*!40000 ALTER TABLE `vtiger_inventoryproductrel` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_inventoryproductrel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_inventoryproductrel_seq`
--

DROP TABLE IF EXISTS `vtiger_inventoryproductrel_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_inventoryproductrel_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_inventoryproductrel_seq`
--

LOCK TABLES `vtiger_inventoryproductrel_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_inventoryproductrel_seq` DISABLE KEYS */;
INSERT INTO `vtiger_inventoryproductrel_seq` VALUES (0);
/*!40000 ALTER TABLE `vtiger_inventoryproductrel_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_inventoryshippingrel`
--

DROP TABLE IF EXISTS `vtiger_inventoryshippingrel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_inventoryshippingrel` (
  `id` int DEFAULT NULL,
  `shtax1` decimal(7,3) DEFAULT NULL,
  `shtax2` decimal(7,3) DEFAULT NULL,
  `shtax3` decimal(7,3) DEFAULT NULL,
  KEY `inventoryishippingrel_id_idx` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_inventoryshippingrel`
--

LOCK TABLES `vtiger_inventoryshippingrel` WRITE;
/*!40000 ALTER TABLE `vtiger_inventoryshippingrel` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_inventoryshippingrel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_inventorysubproductrel`
--

DROP TABLE IF EXISTS `vtiger_inventorysubproductrel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_inventorysubproductrel` (
  `id` int NOT NULL,
  `sequence_no` int NOT NULL,
  `productid` int NOT NULL,
  `quantity` int DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_inventorysubproductrel`
--

LOCK TABLES `vtiger_inventorysubproductrel` WRITE;
/*!40000 ALTER TABLE `vtiger_inventorysubproductrel` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_inventorysubproductrel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_inventorytaxinfo`
--

DROP TABLE IF EXISTS `vtiger_inventorytaxinfo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_inventorytaxinfo` (
  `taxid` int NOT NULL,
  `taxname` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `taxlabel` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `percentage` decimal(7,3) DEFAULT NULL,
  `deleted` int DEFAULT NULL,
  `method` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `type` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `compoundon` varchar(400) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `regions` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`taxid`),
  KEY `inventorytaxinfo_taxname_idx` (`taxname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_inventorytaxinfo`
--

LOCK TABLES `vtiger_inventorytaxinfo` WRITE;
/*!40000 ALTER TABLE `vtiger_inventorytaxinfo` DISABLE KEYS */;
INSERT INTO `vtiger_inventorytaxinfo` VALUES (1,'tax1','VAT',4.500,1,'Simple','Fixed','[]','[]'),(2,'tax2','Sales',10.000,1,'Simple','Fixed','[]','[]'),(3,'tax3','Service',12.500,1,'Simple','Fixed','[]','[]'),(4,'tax4','LBL_CONSUMPTION_TAX',10.000,0,'Simple','Fixed','null','null');
/*!40000 ALTER TABLE `vtiger_inventorytaxinfo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_inventorytaxinfo_seq`
--

DROP TABLE IF EXISTS `vtiger_inventorytaxinfo_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_inventorytaxinfo_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_inventorytaxinfo_seq`
--

LOCK TABLES `vtiger_inventorytaxinfo_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_inventorytaxinfo_seq` DISABLE KEYS */;
INSERT INTO `vtiger_inventorytaxinfo_seq` VALUES (4);
/*!40000 ALTER TABLE `vtiger_inventorytaxinfo_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_invitees`
--

DROP TABLE IF EXISTS `vtiger_invitees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_invitees` (
  `activityid` int NOT NULL,
  `inviteeid` int NOT NULL,
  `status` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`activityid`,`inviteeid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_invitees`
--

LOCK TABLES `vtiger_invitees` WRITE;
/*!40000 ALTER TABLE `vtiger_invitees` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_invitees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_invoice`
--

DROP TABLE IF EXISTS `vtiger_invoice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_invoice` (
  `invoiceid` int NOT NULL,
  `subject` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `salesorderid` int DEFAULT NULL,
  `customerno` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contactid` int DEFAULT NULL,
  `notes` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `invoicedate` date DEFAULT NULL,
  `duedate` date DEFAULT NULL,
  `invoiceterms` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `adjustment` decimal(25,8) DEFAULT NULL,
  `salescommission` decimal(25,3) DEFAULT NULL,
  `exciseduty` decimal(25,3) DEFAULT NULL,
  `subtotal` decimal(25,8) DEFAULT NULL,
  `total` decimal(25,8) DEFAULT NULL,
  `taxtype` varchar(25) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `discount_percent` decimal(25,3) DEFAULT NULL,
  `discount_amount` decimal(25,8) DEFAULT NULL,
  `s_h_amount` decimal(25,8) DEFAULT NULL,
  `shipping` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `accountid` int DEFAULT NULL,
  `terms_conditions` text COLLATE utf8mb4_general_ci,
  `purchaseorder` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `invoicestatus` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `invoice_no` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `currency_id` int NOT NULL DEFAULT '1',
  `conversion_rate` decimal(10,3) NOT NULL DEFAULT '1.000',
  `compound_taxes_info` text COLLATE utf8mb4_general_ci,
  `pre_tax_total` decimal(25,8) DEFAULT NULL,
  `received` decimal(25,8) DEFAULT NULL,
  `balance` decimal(25,8) DEFAULT NULL,
  `s_h_percent` decimal(25,8) DEFAULT NULL,
  `potential_id` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tags` varchar(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `region_id` int DEFAULT NULL,
  `smcreatorid` int DEFAULT NULL,
  `smownerid` int DEFAULT NULL,
  `modifiedby` int DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_general_ci,
  `createdtime` datetime DEFAULT NULL,
  `modifiedtime` datetime DEFAULT NULL,
  `viewedtime` datetime DEFAULT NULL,
  `deleted` int DEFAULT NULL,
  `label` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `smgroupid` int DEFAULT NULL,
  `source` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`invoiceid`),
  KEY `invoice_purchaseorderid_idx` (`invoiceid`),
  KEY `fk_2_vtiger_invoice` (`salesorderid`),
  KEY `potential_id` (`potential_id`),
  KEY `idx_info` (`deleted`,`modifiedtime`),
  KEY `idx_label` (`deleted`,`label`),
  KEY `idx_owner` (`deleted`,`smownerid`),
  CONSTRAINT `fk_2_vtiger_invoice` FOREIGN KEY (`salesorderid`) REFERENCES `vtiger_salesorder` (`salesorderid`) ON DELETE CASCADE,
  CONSTRAINT `fk_crmid_vtiger_invoice` FOREIGN KEY (`invoiceid`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_invoice`
--

LOCK TABLES `vtiger_invoice` WRITE;
/*!40000 ALTER TABLE `vtiger_invoice` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_invoice` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_invoice_recurring_info`
--

DROP TABLE IF EXISTS `vtiger_invoice_recurring_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_invoice_recurring_info` (
  `salesorderid` int NOT NULL,
  `recurring_frequency` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `start_period` date DEFAULT NULL,
  `end_period` date DEFAULT NULL,
  `last_recurring_date` date DEFAULT NULL,
  `payment_duration` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `invoice_status` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`salesorderid`),
  CONSTRAINT `fk_salesorderid_vtiger_invoice_recurring_info` FOREIGN KEY (`salesorderid`) REFERENCES `vtiger_salesorder` (`salesorderid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_invoice_recurring_info`
--

LOCK TABLES `vtiger_invoice_recurring_info` WRITE;
/*!40000 ALTER TABLE `vtiger_invoice_recurring_info` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_invoice_recurring_info` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_invoicebillads`
--

DROP TABLE IF EXISTS `vtiger_invoicebillads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_invoicebillads` (
  `invoicebilladdressid` int NOT NULL,
  `bill_city` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bill_code` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bill_country` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bill_state` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bill_street` text COLLATE utf8mb4_general_ci,
  `bill_pobox` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`invoicebilladdressid`),
  CONSTRAINT `fk_1_vtiger_invoicebillads` FOREIGN KEY (`invoicebilladdressid`) REFERENCES `vtiger_invoice` (`invoiceid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_invoicebillads`
--

LOCK TABLES `vtiger_invoicebillads` WRITE;
/*!40000 ALTER TABLE `vtiger_invoicebillads` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_invoicebillads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_invoicecf`
--

DROP TABLE IF EXISTS `vtiger_invoicecf`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_invoicecf` (
  `invoiceid` int NOT NULL,
  PRIMARY KEY (`invoiceid`),
  CONSTRAINT `fk_1_vtiger_invoicecf` FOREIGN KEY (`invoiceid`) REFERENCES `vtiger_invoice` (`invoiceid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_invoicecf`
--

LOCK TABLES `vtiger_invoicecf` WRITE;
/*!40000 ALTER TABLE `vtiger_invoicecf` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_invoicecf` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_invoiceshipads`
--

DROP TABLE IF EXISTS `vtiger_invoiceshipads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_invoiceshipads` (
  `invoiceshipaddressid` int NOT NULL,
  `ship_city` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ship_code` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ship_country` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ship_state` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ship_street` text COLLATE utf8mb4_general_ci,
  `ship_pobox` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`invoiceshipaddressid`),
  CONSTRAINT `fk_1_vtiger_invoiceshipads` FOREIGN KEY (`invoiceshipaddressid`) REFERENCES `vtiger_invoice` (`invoiceid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_invoiceshipads`
--

LOCK TABLES `vtiger_invoiceshipads` WRITE;
/*!40000 ALTER TABLE `vtiger_invoiceshipads` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_invoiceshipads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_invoicestatus`
--

DROP TABLE IF EXISTS `vtiger_invoicestatus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_invoicestatus` (
  `invoicestatusid` int NOT NULL AUTO_INCREMENT,
  `invoicestatus` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT NULL,
  `color` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`invoicestatusid`),
  UNIQUE KEY `invoicestatus_invoiestatus_idx` (`invoicestatus`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_invoicestatus`
--

LOCK TABLES `vtiger_invoicestatus` WRITE;
/*!40000 ALTER TABLE `vtiger_invoicestatus` DISABLE KEYS */;
INSERT INTO `vtiger_invoicestatus` VALUES (1,'AutoCreated',0,92,0,NULL),(2,'Created',0,93,1,NULL),(3,'Approved',0,94,2,NULL),(4,'Sent',0,95,3,NULL),(5,'Credit Invoice',0,96,4,NULL),(6,'Paid',0,97,5,NULL),(7,'Cancel',1,288,1,NULL);
/*!40000 ALTER TABLE `vtiger_invoicestatus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_invoicestatus_seq`
--

DROP TABLE IF EXISTS `vtiger_invoicestatus_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_invoicestatus_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_invoicestatus_seq`
--

LOCK TABLES `vtiger_invoicestatus_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_invoicestatus_seq` DISABLE KEYS */;
INSERT INTO `vtiger_invoicestatus_seq` VALUES (7);
/*!40000 ALTER TABLE `vtiger_invoicestatus_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_invoicestatushistory`
--

DROP TABLE IF EXISTS `vtiger_invoicestatushistory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_invoicestatushistory` (
  `historyid` int NOT NULL AUTO_INCREMENT,
  `invoiceid` int NOT NULL,
  `accountname` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `total` decimal(10,0) DEFAULT NULL,
  `invoicestatus` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lastmodified` datetime DEFAULT NULL,
  PRIMARY KEY (`historyid`),
  KEY `invoicestatushistory_invoiceid_idx` (`invoiceid`),
  CONSTRAINT `fk_1_vtiger_invoicestatushistory` FOREIGN KEY (`invoiceid`) REFERENCES `vtiger_invoice` (`invoiceid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_invoicestatushistory`
--

LOCK TABLES `vtiger_invoicestatushistory` WRITE;
/*!40000 ALTER TABLE `vtiger_invoicestatushistory` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_invoicestatushistory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_language`
--

DROP TABLE IF EXISTS `vtiger_language`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_language` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `prefix` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `label` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lastupdated` datetime DEFAULT NULL,
  `sequence` int DEFAULT NULL,
  `isdefault` int DEFAULT NULL,
  `active` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_language`
--

LOCK TABLES `vtiger_language` WRITE;
/*!40000 ALTER TABLE `vtiger_language` DISABLE KEYS */;
INSERT INTO `vtiger_language` VALUES (1,'English','en_us','US English','2026-07-01 16:55:03',NULL,1,1),(2,'Japanese','ja_jp','日本語','2026-07-01 16:57:36',NULL,0,1);
/*!40000 ALTER TABLE `vtiger_language` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_language_rules`
--

DROP TABLE IF EXISTS `vtiger_language_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_language_rules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `modulename` varchar(100) NOT NULL,
  `before_string` varchar(1000) NOT NULL,
  `after_string` varchar(1000) NOT NULL,
  `language` varchar(10) DEFAULT 'all',
  `sequence` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_language_rules`
--

LOCK TABLES `vtiger_language_rules` WRITE;
/*!40000 ALTER TABLE `vtiger_language_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_language_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_language_seq`
--

DROP TABLE IF EXISTS `vtiger_language_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_language_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_language_seq`
--

LOCK TABLES `vtiger_language_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_language_seq` DISABLE KEYS */;
INSERT INTO `vtiger_language_seq` VALUES (1);
/*!40000 ALTER TABLE `vtiger_language_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_lead_view`
--

DROP TABLE IF EXISTS `vtiger_lead_view`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_lead_view` (
  `lead_viewid` int NOT NULL AUTO_INCREMENT,
  `lead_view` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `sortorderid` int NOT NULL DEFAULT '0',
  `presence` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`lead_viewid`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_lead_view`
--

LOCK TABLES `vtiger_lead_view` WRITE;
/*!40000 ALTER TABLE `vtiger_lead_view` DISABLE KEYS */;
INSERT INTO `vtiger_lead_view` VALUES (1,'Today',0,1),(2,'Last 2 Days',1,1),(3,'Last Week',2,1);
/*!40000 ALTER TABLE `vtiger_lead_view` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_lead_view_seq`
--

DROP TABLE IF EXISTS `vtiger_lead_view_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_lead_view_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_lead_view_seq`
--

LOCK TABLES `vtiger_lead_view_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_lead_view_seq` DISABLE KEYS */;
INSERT INTO `vtiger_lead_view_seq` VALUES (3);
/*!40000 ALTER TABLE `vtiger_lead_view_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_leadaddress`
--

DROP TABLE IF EXISTS `vtiger_leadaddress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_leadaddress` (
  `leadaddressid` int NOT NULL,
  `city` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `code` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `state` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pobox` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `country` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mobile` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fax` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lane` text COLLATE utf8mb4_general_ci,
  `leadaddresstype` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'Billing',
  PRIMARY KEY (`leadaddressid`),
  CONSTRAINT `fk_1_vtiger_leadaddress` FOREIGN KEY (`leadaddressid`) REFERENCES `vtiger_leaddetails` (`leadid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_leadaddress`
--

LOCK TABLES `vtiger_leadaddress` WRITE;
/*!40000 ALTER TABLE `vtiger_leadaddress` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_leadaddress` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_leaddetails`
--

DROP TABLE IF EXISTS `vtiger_leaddetails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_leaddetails` (
  `leadid` int NOT NULL,
  `lead_no` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `interest` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `firstname` varchar(40) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `salutation` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lastname` varchar(80) COLLATE utf8mb4_general_ci NOT NULL,
  `company` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `annualrevenue` decimal(25,8) DEFAULT NULL,
  `industry` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `campaign` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rating` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `leadstatus` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `leadsource` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `converted` int DEFAULT '0',
  `designation` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'SalesMan',
  `licencekeystatus` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `space` varchar(250) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `comments` text COLLATE utf8mb4_general_ci,
  `priority` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `demorequest` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `partnercontact` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `productversion` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `product` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `maildate` date DEFAULT NULL,
  `nextstepdate` date DEFAULT NULL,
  `fundingsituation` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `purpose` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `evaluationstatus` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `transferdate` date DEFAULT NULL,
  `revenuetype` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `noofemployees` int DEFAULT NULL,
  `secondaryemail` varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assignleadchk` int DEFAULT '0',
  `emailoptout` varchar(3) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tags` varchar(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `smcreatorid` int DEFAULT NULL,
  `smownerid` int DEFAULT NULL,
  `modifiedby` int DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_general_ci,
  `createdtime` datetime DEFAULT NULL,
  `modifiedtime` datetime DEFAULT NULL,
  `viewedtime` datetime DEFAULT NULL,
  `deleted` int DEFAULT NULL,
  `label` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `smgroupid` int DEFAULT NULL,
  `source` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `last_action_date` date DEFAULT NULL,
  PRIMARY KEY (`leadid`),
  KEY `leaddetails_converted_leadstatus_idx` (`converted`,`leadstatus`),
  KEY `email_idx` (`email`),
  KEY `idx_info` (`deleted`,`modifiedtime`),
  KEY `idx_label` (`deleted`,`label`),
  KEY `idx_owner` (`deleted`,`smownerid`),
  CONSTRAINT `fk_1_vtiger_leaddetails` FOREIGN KEY (`leadid`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_leaddetails`
--

LOCK TABLES `vtiger_leaddetails` WRITE;
/*!40000 ALTER TABLE `vtiger_leaddetails` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_leaddetails` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_leadscf`
--

DROP TABLE IF EXISTS `vtiger_leadscf`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_leadscf` (
  `leadid` int NOT NULL,
  PRIMARY KEY (`leadid`),
  CONSTRAINT `fk_1_vtiger_leadscf` FOREIGN KEY (`leadid`) REFERENCES `vtiger_leaddetails` (`leadid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_leadscf`
--

LOCK TABLES `vtiger_leadscf` WRITE;
/*!40000 ALTER TABLE `vtiger_leadscf` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_leadscf` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_leadsource`
--

DROP TABLE IF EXISTS `vtiger_leadsource`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_leadsource` (
  `leadsourceid` int NOT NULL AUTO_INCREMENT,
  `leadsource` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT NULL,
  `color` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`leadsourceid`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_leadsource`
--

LOCK TABLES `vtiger_leadsource` WRITE;
/*!40000 ALTER TABLE `vtiger_leadsource` DISABLE KEYS */;
INSERT INTO `vtiger_leadsource` VALUES (2,'Cold Call',1,99,1,NULL),(3,'Existing Customer',1,100,2,NULL),(4,'Self Generated',1,101,3,NULL),(5,'Employee',1,102,4,NULL),(6,'Partner',1,103,5,NULL),(7,'Public Relations',1,104,6,NULL),(8,'Direct Mail',1,105,7,NULL),(9,'Conference',1,106,8,NULL),(10,'Trade Show',1,107,9,NULL),(11,'Web Site',1,108,10,NULL),(12,'Word of mouth',1,109,11,NULL),(13,'Other',1,110,12,NULL);
/*!40000 ALTER TABLE `vtiger_leadsource` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_leadsource_seq`
--

DROP TABLE IF EXISTS `vtiger_leadsource_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_leadsource_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_leadsource_seq`
--

LOCK TABLES `vtiger_leadsource_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_leadsource_seq` DISABLE KEYS */;
INSERT INTO `vtiger_leadsource_seq` VALUES (13);
/*!40000 ALTER TABLE `vtiger_leadsource_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_leadstage`
--

DROP TABLE IF EXISTS `vtiger_leadstage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_leadstage` (
  `leadstageid` int NOT NULL AUTO_INCREMENT,
  `stage` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `sortorderid` int NOT NULL DEFAULT '0',
  `presence` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`leadstageid`),
  UNIQUE KEY `leadstage_stage_idx` (`stage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_leadstage`
--

LOCK TABLES `vtiger_leadstage` WRITE;
/*!40000 ALTER TABLE `vtiger_leadstage` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_leadstage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_leadstatus`
--

DROP TABLE IF EXISTS `vtiger_leadstatus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_leadstatus` (
  `leadstatusid` int NOT NULL AUTO_INCREMENT,
  `leadstatus` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT NULL,
  `color` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`leadstatusid`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_leadstatus`
--

LOCK TABLES `vtiger_leadstatus` WRITE;
/*!40000 ALTER TABLE `vtiger_leadstatus` DISABLE KEYS */;
INSERT INTO `vtiger_leadstatus` VALUES (2,'Attempted to Contact',1,112,1,NULL),(3,'Cold',1,113,2,NULL),(4,'Contact in Future',1,114,3,NULL),(5,'Contacted',1,115,4,NULL),(6,'Hot',1,116,5,NULL),(7,'Junk Lead',1,117,6,NULL),(8,'Lost Lead',1,118,7,NULL),(9,'Not Contacted',1,119,8,NULL),(10,'Pre Qualified',1,120,9,NULL),(11,'Qualified',1,121,10,NULL),(12,'Warm',1,122,11,NULL);
/*!40000 ALTER TABLE `vtiger_leadstatus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_leadstatus_seq`
--

DROP TABLE IF EXISTS `vtiger_leadstatus_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_leadstatus_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_leadstatus_seq`
--

LOCK TABLES `vtiger_leadstatus_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_leadstatus_seq` DISABLE KEYS */;
INSERT INTO `vtiger_leadstatus_seq` VALUES (12);
/*!40000 ALTER TABLE `vtiger_leadstatus_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_leadsubdetails`
--

DROP TABLE IF EXISTS `vtiger_leadsubdetails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_leadsubdetails` (
  `leadsubscriptionid` int NOT NULL,
  `website` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `callornot` int DEFAULT '0',
  `readornot` int DEFAULT '0',
  `empct` int DEFAULT '0',
  PRIMARY KEY (`leadsubscriptionid`),
  CONSTRAINT `fk_1_vtiger_leadsubdetails` FOREIGN KEY (`leadsubscriptionid`) REFERENCES `vtiger_leaddetails` (`leadid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_leadsubdetails`
--

LOCK TABLES `vtiger_leadsubdetails` WRITE;
/*!40000 ALTER TABLE `vtiger_leadsubdetails` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_leadsubdetails` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_links`
--

DROP TABLE IF EXISTS `vtiger_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_links` (
  `linkid` int NOT NULL,
  `tabid` int DEFAULT NULL,
  `linktype` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `linklabel` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `linkurl` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `linkicon` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sequence` int DEFAULT NULL,
  `handler_path` varchar(128) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `handler_class` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `handler` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `parent_link` int DEFAULT NULL,
  PRIMARY KEY (`linkid`),
  KEY `link_tabidtype_idx` (`tabid`,`linktype`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_links`
--

LOCK TABLES `vtiger_links` WRITE;
/*!40000 ALTER TABLE `vtiger_links` DISABLE KEYS */;
INSERT INTO `vtiger_links` VALUES (2,6,'DETAILVIEW','LBL_SHOW_ACCOUNT_HIERARCHY','index.php?module=Accounts&action=AccountHierarchy&accountid=$RECORD$','',0,NULL,NULL,NULL,NULL),(5,7,'DETAILVIEWWIDGET','DetailViewBlockCommentWidget','block://ModComments:modules/ModComments/ModComments.php','',0,NULL,NULL,NULL,NULL),(6,4,'DETAILVIEWWIDGET','DetailViewBlockCommentWidget','block://ModComments:modules/ModComments/ModComments.php','',0,NULL,NULL,NULL,NULL),(7,6,'DETAILVIEWWIDGET','DetailViewBlockCommentWidget','block://ModComments:modules/ModComments/ModComments.php','',0,NULL,NULL,NULL,NULL),(8,2,'DETAILVIEWWIDGET','DetailViewBlockCommentWidget','block://ModComments:modules/ModComments/ModComments.php','',0,NULL,NULL,NULL,NULL),(9,37,'HEADERSCRIPT','ModCommentsCommonHeaderScript','modules/ModComments/ModCommentsCommon.js','',0,NULL,NULL,NULL,NULL),(15,42,'HEADERSCRIPT','SMSNotifierCommonJS','modules/SMSNotifier/SMSNotifierCommon.js','',0,NULL,NULL,NULL,NULL),(16,7,'LISTVIEWBASIC','Send SMS','SMSNotifierCommon.displaySelectWizard(this, \'$MODULE$\');','',0,NULL,NULL,NULL,NULL),(17,7,'DETAILVIEW','Send SMS','javascript:SMSNotifierCommon.displaySelectWizard_DetailView(\'$MODULE$\', \'$RECORD$\');','',0,NULL,NULL,NULL,NULL),(18,4,'LISTVIEWBASIC','Send SMS','SMSNotifierCommon.displaySelectWizard(this, \'$MODULE$\');','',0,NULL,NULL,NULL,NULL),(19,4,'DETAILVIEW','Send SMS','javascript:SMSNotifierCommon.displaySelectWizard_DetailView(\'$MODULE$\', \'$RECORD$\');','',0,NULL,NULL,NULL,NULL),(20,6,'LISTVIEWBASIC','Send SMS','SMSNotifierCommon.displaySelectWizard(this, \'$MODULE$\');','',0,NULL,NULL,NULL,NULL),(21,6,'DETAILVIEW','Send SMS','javascript:SMSNotifierCommon.displaySelectWizard_DetailView(\'$MODULE$\', \'$RECORD$\');','',0,NULL,NULL,NULL,NULL),(32,2,'DASHBOARDWIDGET','History','index.php?module=Potentials&view=ShowWidget&name=History','',1,NULL,NULL,NULL,NULL),(33,2,'DASHBOARDWIDGET','Upcoming Activities','index.php?module=Potentials&view=ShowWidget&name=CalendarActivities','',2,NULL,NULL,NULL,NULL),(34,2,'DASHBOARDWIDGET','Funnel','index.php?module=Potentials&view=ShowWidget&name=GroupedBySalesStage','',3,NULL,NULL,NULL,NULL),(35,2,'DASHBOARDWIDGET','Potentials by Stage','index.php?module=Potentials&view=ShowWidget&name=GroupedBySalesPerson','',4,NULL,NULL,NULL,NULL),(36,2,'DASHBOARDWIDGET','Pipelined Amount','index.php?module=Potentials&view=ShowWidget&name=PipelinedAmountPerSalesPerson','',5,NULL,NULL,NULL,NULL),(37,2,'DASHBOARDWIDGET','Total Revenue','index.php?module=Potentials&view=ShowWidget&name=TotalRevenuePerSalesPerson','',6,NULL,NULL,NULL,NULL),(38,2,'DASHBOARDWIDGET','Top Potentials','index.php?module=Potentials&view=ShowWidget&name=TopPotentials','',7,NULL,NULL,NULL,NULL),(39,2,'DASHBOARDWIDGET','Overdue Activities','index.php?module=Potentials&view=ShowWidget&name=OverdueActivities','',9,NULL,NULL,NULL,NULL),(40,6,'DASHBOARDWIDGET','History','index.php?module=Accounts&view=ShowWidget&name=History','',1,NULL,NULL,NULL,NULL),(41,6,'DASHBOARDWIDGET','Upcoming Activities','index.php?module=Accounts&view=ShowWidget&name=CalendarActivities','',2,NULL,NULL,NULL,NULL),(42,6,'DASHBOARDWIDGET','Overdue Activities','index.php?module=Accounts&view=ShowWidget&name=OverdueActivities','',3,NULL,NULL,NULL,NULL),(43,4,'DASHBOARDWIDGET','History','index.php?module=Contacts&view=ShowWidget&name=History','',1,NULL,NULL,NULL,NULL),(44,4,'DASHBOARDWIDGET','Upcoming Activities','index.php?module=Contacts&view=ShowWidget&name=CalendarActivities','',2,NULL,NULL,NULL,NULL),(45,4,'DASHBOARDWIDGET','Overdue Activities','index.php?module=Contacts&view=ShowWidget&name=OverdueActivities','',3,NULL,NULL,NULL,NULL),(46,7,'DASHBOARDWIDGET','History','index.php?module=Leads&view=ShowWidget&name=History','',1,NULL,NULL,NULL,NULL),(47,7,'DASHBOARDWIDGET','Upcoming Activities','index.php?module=Leads&view=ShowWidget&name=CalendarActivities','',2,NULL,NULL,NULL,NULL),(48,7,'DASHBOARDWIDGET','Leads by Status','index.php?module=Leads&view=ShowWidget&name=LeadsByStatus','',4,NULL,NULL,NULL,NULL),(49,7,'DASHBOARDWIDGET','Leads by Source','index.php?module=Leads&view=ShowWidget&name=LeadsBySource','',5,NULL,NULL,NULL,NULL),(50,7,'DASHBOARDWIDGET','Leads by Industry','index.php?module=Leads&view=ShowWidget&name=LeadsByIndustry','',6,NULL,NULL,NULL,NULL),(51,7,'DASHBOARDWIDGET','Overdue Activities','index.php?module=Leads&view=ShowWidget&name=OverdueActivities','',7,NULL,NULL,NULL,NULL),(52,13,'DASHBOARDWIDGET','Tickets by Status','index.php?module=HelpDesk&view=ShowWidget&name=TicketsByStatus','',1,NULL,NULL,NULL,NULL),(53,13,'DASHBOARDWIDGET','Open Tickets','index.php?module=HelpDesk&view=ShowWidget&name=OpenTickets','',2,NULL,NULL,NULL,NULL),(54,3,'DASHBOARDWIDGET','History','index.php?module=Home&view=ShowWidget&name=History','',1,NULL,NULL,NULL,NULL),(55,3,'DASHBOARDWIDGET','Upcoming Activities','index.php?module=Home&view=ShowWidget&name=CalendarActivities','',2,NULL,NULL,NULL,NULL),(56,3,'DASHBOARDWIDGET','Funnel','index.php?module=Potentials&view=ShowWidget&name=GroupedBySalesStage','',3,NULL,NULL,NULL,NULL),(57,3,'DASHBOARDWIDGET','Potentials by Stage','index.php?module=Potentials&view=ShowWidget&name=GroupedBySalesPerson','',4,NULL,NULL,NULL,NULL),(58,3,'DASHBOARDWIDGET','Pipelined Amount','index.php?module=Potentials&view=ShowWidget&name=PipelinedAmountPerSalesPerson','',5,NULL,NULL,NULL,NULL),(59,3,'DASHBOARDWIDGET','Total Revenue','index.php?module=Potentials&view=ShowWidget&name=TotalRevenuePerSalesPerson','',6,NULL,NULL,NULL,NULL),(60,3,'DASHBOARDWIDGET','Top Potentials','index.php?module=Potentials&view=ShowWidget&name=TopPotentials','',7,NULL,NULL,NULL,NULL),(61,3,'DASHBOARDWIDGET','Leads by Status','index.php?module=Leads&view=ShowWidget&name=LeadsByStatus','',10,NULL,NULL,NULL,NULL),(62,3,'DASHBOARDWIDGET','Leads by Source','index.php?module=Leads&view=ShowWidget&name=LeadsBySource','',11,NULL,NULL,NULL,NULL),(63,3,'DASHBOARDWIDGET','Leads by Industry','index.php?module=Leads&view=ShowWidget&name=LeadsByIndustry','',12,NULL,NULL,NULL,NULL),(64,3,'DASHBOARDWIDGET','Overdue Activities','index.php?module=Home&view=ShowWidget&name=OverdueActivities','',13,NULL,NULL,NULL,NULL),(65,3,'DASHBOARDWIDGET','Tickets by Status','index.php?module=HelpDesk&view=ShowWidget&name=TicketsByStatus','',13,NULL,NULL,NULL,NULL),(66,3,'DASHBOARDWIDGET','Open Tickets','index.php?module=HelpDesk&view=ShowWidget&name=OpenTickets','',14,NULL,NULL,NULL,NULL),(67,13,'DETAILVIEWWIDGET','DetailViewBlockCommentWidget','block://ModComments:modules/ModComments/ModComments.php','',0,NULL,NULL,NULL,NULL),(68,15,'DETAILVIEWWIDGET','DetailViewBlockCommentWidget','block://ModComments:modules/ModComments/ModComments.php','',0,NULL,NULL,NULL,NULL),(96,3,'DASHBOARDWIDGET','Key Metrics','index.php?module=Home&view=ShowWidget&name=KeyMetrics','',0,NULL,NULL,NULL,NULL),(97,3,'DASHBOARDWIDGET','Mini List','index.php?module=Home&view=ShowWidget&name=MiniList','',0,NULL,NULL,NULL,NULL),(98,3,'DASHBOARDWIDGET','Tag Cloud','index.php?module=Home&view=ShowWidget&name=TagCloud','',0,NULL,NULL,NULL,NULL),(99,2,'DASHBOARDWIDGET','Funnel Amount','index.php?module=Potentials&view=ShowWidget&name=FunnelAmount','',10,NULL,NULL,NULL,NULL),(100,3,'DASHBOARDWIDGET','Funnel Amount','index.php?module=Potentials&view=ShowWidget&name=FunnelAmount','',10,NULL,NULL,NULL,NULL),(101,3,'DASHBOARDWIDGET','Notebook','index.php?module=Home&view=ShowWidget&name=Notebook','',0,NULL,NULL,NULL,NULL),(102,25,'LISTVIEWBASIC','LBL_ADD_RECORD','','',0,NULL,NULL,NULL,NULL),(103,25,'LISTVIEWBASIC','LBL_DETAIL_REPORT','javascript:Reports_List_Js.addReport(\"index.php?module=Reports&view=Edit\")','',0,'modules/Reports/models/Module.php','Reports_Module_Model','checkLinkAccess',102),(104,25,'LISTVIEWBASIC','LBL_CHARTS','javascript:Reports_List_Js.addReport(\"index.php?module=Reports&view=ChartEdit\")','',0,'modules/Reports/models/Module.php','Reports_Module_Model','checkLinkAccess',102),(105,25,'LISTVIEWBASIC','LBL_ADD_FOLDER','javascript:Reports_List_Js.triggerAddFolder(\"index.php?module=Reports&view=EditFolder\")','',0,'modules/Reports/models/Module.php','Reports_Module_Model','checkLinkAccess',NULL),(108,40,'DETAILVIEWBASIC','Add Note','index.php?module=Documents&action=EditView&return_module=ProjectTask&return_action=DetailView&return_id=$RECORD$&parent_id=$RECORD$','',0,NULL,NULL,NULL,NULL),(109,40,'DETAILVIEWWIDGET','DetailViewBlockCommentWidget','block://ModComments:modules/ModComments/ModComments.php','',0,NULL,NULL,NULL,NULL),(110,41,'DETAILVIEWBASIC','Add Project Task','index.php?module=ProjectTask&action=EditView&projectid=$RECORD$&return_module=Project&return_action=DetailView&return_id=$RECORD$','',0,NULL,NULL,NULL,NULL),(111,41,'DETAILVIEWBASIC','Add Note','index.php?module=Documents&action=EditView&return_module=Project&return_action=DetailView&return_id=$RECORD$&parent_id=$RECORD$','',1,NULL,NULL,NULL,NULL),(112,41,'DETAILVIEWWIDGET','DetailViewBlockCommentWidget','block://ModComments:modules/ModComments/ModComments.php','',0,NULL,NULL,NULL,NULL),(113,49,'DETAILVIEWBASIC','View History','javascript:ModTrackerCommon.showhistory(\'$RECORD$\')','',0,'modules/ModTracker/ModTracker.php','ModTracker','isViewPermitted',NULL),(114,49,'DETAILVIEWWIDGET','DetailViewBlockCommentWidget','block://ModComments:modules/ModComments/ModComments.php','',0,NULL,NULL,NULL,NULL),(115,3,'DASHBOARDWIDGET','IFrame Widget','index.php?module=Home&view=ShowWidget&name=IFrameWidget','',0,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `vtiger_links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_links_seq`
--

DROP TABLE IF EXISTS `vtiger_links_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_links_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_links_seq`
--

LOCK TABLES `vtiger_links_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_links_seq` DISABLE KEYS */;
INSERT INTO `vtiger_links_seq` VALUES (114);
/*!40000 ALTER TABLE `vtiger_links_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_loginhistory`
--

DROP TABLE IF EXISTS `vtiger_loginhistory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_loginhistory` (
  `login_id` int NOT NULL AUTO_INCREMENT,
  `user_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_ip` varchar(25) COLLATE utf8mb4_general_ci NOT NULL,
  `logout_time` timestamp NULL DEFAULT NULL,
  `login_time` datetime DEFAULT NULL,
  `status` varchar(25) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_portal` tinyint(1) DEFAULT '0',
  `login_type` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`login_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_loginhistory`
--

LOCK TABLES `vtiger_loginhistory` WRITE;
/*!40000 ALTER TABLE `vtiger_loginhistory` DISABLE KEYS */;
INSERT INTO `vtiger_loginhistory` VALUES (1,'admin','127.0.0.1','2026-07-01 07:57:38','2026-07-01 16:57:38','Signed in',0,'password');
/*!40000 ALTER TABLE `vtiger_loginhistory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_mail_accounts`
--

DROP TABLE IF EXISTS `vtiger_mail_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_mail_accounts` (
  `account_id` int NOT NULL,
  `user_id` int NOT NULL,
  `display_name` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mail_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `account_name` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mail_protocol` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mail_username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `mail_password` text COLLATE utf8mb4_general_ci,
  `mail_servername` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `box_refresh` int DEFAULT NULL,
  `mails_per_page` int DEFAULT NULL,
  `ssltype` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sslmeth` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `int_mailer` int DEFAULT '0',
  `status` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `set_default` int DEFAULT NULL,
  `sent_folder` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_mail_accounts`
--

LOCK TABLES `vtiger_mail_accounts` WRITE;
/*!40000 ALTER TABLE `vtiger_mail_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_mail_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_mailer_queue`
--

DROP TABLE IF EXISTS `vtiger_mailer_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_mailer_queue` (
  `id` int NOT NULL,
  `fromname` varchar(100) DEFAULT NULL,
  `fromemail` varchar(100) DEFAULT NULL,
  `mailer` varchar(10) DEFAULT NULL,
  `content_type` varchar(15) DEFAULT NULL,
  `subject` varchar(999) DEFAULT NULL,
  `body` text,
  `relcrmid` int DEFAULT NULL,
  `failed` int NOT NULL DEFAULT '0',
  `failreason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_mailer_queue`
--

LOCK TABLES `vtiger_mailer_queue` WRITE;
/*!40000 ALTER TABLE `vtiger_mailer_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_mailer_queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_mailer_queueattachments`
--

DROP TABLE IF EXISTS `vtiger_mailer_queueattachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_mailer_queueattachments` (
  `id` int DEFAULT NULL,
  `path` text,
  `name` varchar(100) DEFAULT NULL,
  `encoding` varchar(50) DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_mailer_queueattachments`
--

LOCK TABLES `vtiger_mailer_queueattachments` WRITE;
/*!40000 ALTER TABLE `vtiger_mailer_queueattachments` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_mailer_queueattachments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_mailer_queueinfo`
--

DROP TABLE IF EXISTS `vtiger_mailer_queueinfo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_mailer_queueinfo` (
  `id` int DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `type` varchar(7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_mailer_queueinfo`
--

LOCK TABLES `vtiger_mailer_queueinfo` WRITE;
/*!40000 ALTER TABLE `vtiger_mailer_queueinfo` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_mailer_queueinfo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_mailmanager_mailattachments`
--

DROP TABLE IF EXISTS `vtiger_mailmanager_mailattachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_mailmanager_mailattachments` (
  `userid` int DEFAULT NULL,
  `muid` int DEFAULT NULL,
  `aname` varchar(100) DEFAULT NULL,
  `lastsavedtime` int DEFAULT NULL,
  `attachid` int NOT NULL,
  `path` varchar(200) NOT NULL,
  `cid` varchar(100) DEFAULT NULL,
  KEY `userid_muid_idx` (`userid`,`muid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_mailmanager_mailattachments`
--

LOCK TABLES `vtiger_mailmanager_mailattachments` WRITE;
/*!40000 ALTER TABLE `vtiger_mailmanager_mailattachments` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_mailmanager_mailattachments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_mailmanager_mailrecord`
--

DROP TABLE IF EXISTS `vtiger_mailmanager_mailrecord`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_mailmanager_mailrecord` (
  `userid` int DEFAULT NULL,
  `mfrom` varchar(255) DEFAULT NULL,
  `mto` varchar(255) DEFAULT NULL,
  `mcc` varchar(500) DEFAULT NULL,
  `mbcc` varchar(500) DEFAULT NULL,
  `mdate` varchar(20) DEFAULT NULL,
  `msubject` varchar(500) DEFAULT NULL,
  `mbody` text,
  `mcharset` varchar(10) DEFAULT NULL,
  `misbodyhtml` int DEFAULT NULL,
  `mplainmessage` int DEFAULT NULL,
  `mhtmlmessage` int DEFAULT NULL,
  `muniqueid` varchar(500) DEFAULT NULL,
  `mbodyparsed` int DEFAULT NULL,
  `muid` int DEFAULT NULL,
  `lastsavedtime` int DEFAULT NULL,
  `folder` varchar(250) DEFAULT NULL,
  `mfolder` varchar(250) DEFAULT NULL,
  KEY `userid_lastsavedtime_idx` (`userid`,`lastsavedtime`),
  KEY `userid_muid_idx` (`userid`,`muid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_mailmanager_mailrecord`
--

LOCK TABLES `vtiger_mailmanager_mailrecord` WRITE;
/*!40000 ALTER TABLE `vtiger_mailmanager_mailrecord` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_mailmanager_mailrecord` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_mailmanager_mailrel`
--

DROP TABLE IF EXISTS `vtiger_mailmanager_mailrel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_mailmanager_mailrel` (
  `mailuid` varchar(999) DEFAULT NULL,
  `crmid` int DEFAULT NULL,
  `emailid` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_mailmanager_mailrel`
--

LOCK TABLES `vtiger_mailmanager_mailrel` WRITE;
/*!40000 ALTER TABLE `vtiger_mailmanager_mailrel` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_mailmanager_mailrel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_mailscanner`
--

DROP TABLE IF EXISTS `vtiger_mailscanner`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_mailscanner` (
  `scannerid` int NOT NULL AUTO_INCREMENT,
  `scannername` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `server` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `protocol` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `username` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ssltype` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sslmethod` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `connecturl` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `searchfor` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `markas` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `isvalid` int DEFAULT NULL,
  `scanfrom` varchar(10) COLLATE utf8mb4_general_ci DEFAULT 'ALL',
  `time_zone` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`scannerid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_mailscanner`
--

LOCK TABLES `vtiger_mailscanner` WRITE;
/*!40000 ALTER TABLE `vtiger_mailscanner` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_mailscanner` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_mailscanner_actions`
--

DROP TABLE IF EXISTS `vtiger_mailscanner_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_mailscanner_actions` (
  `actionid` int NOT NULL AUTO_INCREMENT,
  `scannerid` int DEFAULT NULL,
  `actiontype` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `module` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lookup` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sequence` int DEFAULT NULL,
  PRIMARY KEY (`actionid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_mailscanner_actions`
--

LOCK TABLES `vtiger_mailscanner_actions` WRITE;
/*!40000 ALTER TABLE `vtiger_mailscanner_actions` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_mailscanner_actions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_mailscanner_folders`
--

DROP TABLE IF EXISTS `vtiger_mailscanner_folders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_mailscanner_folders` (
  `folderid` int NOT NULL AUTO_INCREMENT,
  `scannerid` int DEFAULT NULL,
  `foldername` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lastscan` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rescan` int DEFAULT NULL,
  `enabled` int DEFAULT NULL,
  PRIMARY KEY (`folderid`),
  KEY `folderid_idx` (`folderid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_mailscanner_folders`
--

LOCK TABLES `vtiger_mailscanner_folders` WRITE;
/*!40000 ALTER TABLE `vtiger_mailscanner_folders` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_mailscanner_folders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_mailscanner_ids`
--

DROP TABLE IF EXISTS `vtiger_mailscanner_ids`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_mailscanner_ids` (
  `scannerid` int DEFAULT NULL,
  `messageid` varchar(512) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `crmid` int DEFAULT NULL,
  `refids` text COLLATE utf8mb4_general_ci,
  KEY `scanner_message_ids_idx` (`scannerid`,`messageid`),
  KEY `messageids_crmid_idx` (`crmid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_mailscanner_ids`
--

LOCK TABLES `vtiger_mailscanner_ids` WRITE;
/*!40000 ALTER TABLE `vtiger_mailscanner_ids` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_mailscanner_ids` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_mailscanner_ruleactions`
--

DROP TABLE IF EXISTS `vtiger_mailscanner_ruleactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_mailscanner_ruleactions` (
  `ruleid` int DEFAULT NULL,
  `actionid` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_mailscanner_ruleactions`
--

LOCK TABLES `vtiger_mailscanner_ruleactions` WRITE;
/*!40000 ALTER TABLE `vtiger_mailscanner_ruleactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_mailscanner_ruleactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_mailscanner_rules`
--

DROP TABLE IF EXISTS `vtiger_mailscanner_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_mailscanner_rules` (
  `ruleid` int NOT NULL AUTO_INCREMENT,
  `scannerid` int DEFAULT NULL,
  `fromaddress` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `toaddress` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `subjectop` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `subject` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bodyop` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `body` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `matchusing` varchar(5) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sequence` int DEFAULT NULL,
  `assigned_to` int DEFAULT NULL,
  `cc` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bcc` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`ruleid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_mailscanner_rules`
--

LOCK TABLES `vtiger_mailscanner_rules` WRITE;
/*!40000 ALTER TABLE `vtiger_mailscanner_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_mailscanner_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_manufacturer`
--

DROP TABLE IF EXISTS `vtiger_manufacturer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_manufacturer` (
  `manufacturerid` int NOT NULL AUTO_INCREMENT,
  `manufacturer` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT NULL,
  `color` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`manufacturerid`),
  UNIQUE KEY `manufacturer_manufacturer_idx` (`manufacturer`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_manufacturer`
--

LOCK TABLES `vtiger_manufacturer` WRITE;
/*!40000 ALTER TABLE `vtiger_manufacturer` DISABLE KEYS */;
INSERT INTO `vtiger_manufacturer` VALUES (2,'examplecampany1',1,124,1,NULL),(3,'examplecampany2',1,125,2,NULL),(4,'examplecampany3',1,126,3,NULL);
/*!40000 ALTER TABLE `vtiger_manufacturer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_manufacturer_seq`
--

DROP TABLE IF EXISTS `vtiger_manufacturer_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_manufacturer_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_manufacturer_seq`
--

LOCK TABLES `vtiger_manufacturer_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_manufacturer_seq` DISABLE KEYS */;
INSERT INTO `vtiger_manufacturer_seq` VALUES (4);
/*!40000 ALTER TABLE `vtiger_manufacturer_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_mobile_alerts`
--

DROP TABLE IF EXISTS `vtiger_mobile_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_mobile_alerts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `handler_path` varchar(500) DEFAULT NULL,
  `handler_class` varchar(50) DEFAULT NULL,
  `sequence` int DEFAULT NULL,
  `deleted` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_mobile_alerts`
--

LOCK TABLES `vtiger_mobile_alerts` WRITE;
/*!40000 ALTER TABLE `vtiger_mobile_alerts` DISABLE KEYS */;
INSERT INTO `vtiger_mobile_alerts` VALUES (1,'modules/Mobile/api/ws/models/alerts/IdleTicketsOfMine.php','Mobile_WS_AlertModel_IdleTicketsOfMine',NULL,0),(2,'modules/Mobile/api/ws/models/alerts/NewTicketOfMine.php','Mobile_WS_AlertModel_NewTicketOfMine',NULL,0),(3,'modules/Mobile/api/ws/models/alerts/PendingTicketsOfMine.php','Mobile_WS_AlertModel_PendingTicketsOfMine',NULL,0),(4,'modules/Mobile/api/ws/models/alerts/PotentialsDueIn5Days.php','Mobile_WS_AlertModel_PotentialsDueIn5Days',NULL,0),(5,'modules/Mobile/api/ws/models/alerts/EventsOfMineToday.php','Mobile_WS_AlertModel_EventsOfMineToday',NULL,0),(6,'modules/Mobile/api/ws/models/alerts/ProjectTasksOfMine.php','Mobile_WS_AlertModel_ProjectTasksOfMine',NULL,0),(7,'modules/Mobile/api/ws/models/alerts/Projects.php','Mobile_WS_AlertModel_Projects',NULL,0);
/*!40000 ALTER TABLE `vtiger_mobile_alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_modcomments`
--

DROP TABLE IF EXISTS `vtiger_modcomments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_modcomments` (
  `modcommentsid` int DEFAULT NULL,
  `commentcontent` text,
  `related_to` int DEFAULT NULL,
  `parent_comments` int DEFAULT NULL,
  `customer` int DEFAULT NULL,
  `userid` int DEFAULT NULL,
  `reasontoedit` text,
  `is_private` int DEFAULT '0',
  `filename` varchar(255) DEFAULT NULL,
  `related_email_id` int DEFAULT NULL,
  `smcreatorid` int DEFAULT NULL,
  `smownerid` int DEFAULT NULL,
  `modifiedby` int DEFAULT NULL,
  `description` longtext,
  `createdtime` datetime DEFAULT NULL,
  `modifiedtime` datetime DEFAULT NULL,
  `viewedtime` datetime DEFAULT NULL,
  `deleted` int DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `smgroupid` int DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL,
  UNIQUE KEY `uq_fk_vtiger_modcomments_modcommentsid` (`modcommentsid`),
  KEY `related_to` (`related_to`),
  KEY `parent_comments` (`parent_comments`),
  KEY `customer` (`customer`),
  KEY `userid` (`userid`),
  KEY `relatedto_idx` (`related_to`),
  KEY `idx_info` (`deleted`,`modifiedtime`),
  KEY `idx_label` (`deleted`,`label`),
  KEY `idx_owner` (`deleted`,`smownerid`),
  CONSTRAINT `fk_crmid_vtiger_modcomments` FOREIGN KEY (`modcommentsid`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_modcomments`
--

LOCK TABLES `vtiger_modcomments` WRITE;
/*!40000 ALTER TABLE `vtiger_modcomments` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_modcomments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_modcommentscf`
--

DROP TABLE IF EXISTS `vtiger_modcommentscf`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_modcommentscf` (
  `modcommentsid` int NOT NULL,
  PRIMARY KEY (`modcommentsid`),
  CONSTRAINT `fk_modcommentsid_vtiger_modcommentscf` FOREIGN KEY (`modcommentsid`) REFERENCES `vtiger_modcomments` (`modcommentsid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_modcommentscf`
--

LOCK TABLES `vtiger_modcommentscf` WRITE;
/*!40000 ALTER TABLE `vtiger_modcommentscf` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_modcommentscf` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_modentity_num`
--

DROP TABLE IF EXISTS `vtiger_modentity_num`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_modentity_num` (
  `num_id` int NOT NULL,
  `semodule` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `prefix` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `start_id` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `cur_id` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `active` varchar(2) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`num_id`),
  UNIQUE KEY `num_idx` (`num_id`),
  KEY `semodule_active_idx` (`semodule`,`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_modentity_num`
--

LOCK TABLES `vtiger_modentity_num` WRITE;
/*!40000 ALTER TABLE `vtiger_modentity_num` DISABLE KEYS */;
INSERT INTO `vtiger_modentity_num` VALUES (1,'Leads','LEA','1','1','1'),(2,'Accounts','ACC','1','1','1'),(3,'Campaigns','CAM','1','1','1'),(4,'Contacts','CON','1','1','1'),(5,'Potentials','POT','1','1','1'),(6,'HelpDesk','TT','1','1','1'),(7,'Quotes','QUO','1','1','1'),(8,'SalesOrder','SO','1','1','1'),(9,'PurchaseOrder','PO','1','1','1'),(10,'Invoice','INV','1','1','1'),(11,'Products','PRO','1','1','1'),(12,'Vendors','VEN','1','1','1'),(13,'PriceBooks','PB','1','1','1'),(14,'Faq','FAQ','1','1','1'),(15,'Documents','DOC','1','1','1'),(16,'ServiceContracts','SERCON','1','1','1'),(17,'Services','SER','1','1','1'),(18,'ProjectMilestone','PM','1','1','1'),(19,'ProjectTask','PT','1','1','1'),(20,'Project','PROJ','1','1','1'),(21,'Assets','ASSET','1','1','1');
/*!40000 ALTER TABLE `vtiger_modentity_num` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_modentity_num_seq`
--

DROP TABLE IF EXISTS `vtiger_modentity_num_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_modentity_num_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_modentity_num_seq`
--

LOCK TABLES `vtiger_modentity_num_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_modentity_num_seq` DISABLE KEYS */;
INSERT INTO `vtiger_modentity_num_seq` VALUES (21);
/*!40000 ALTER TABLE `vtiger_modentity_num_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_modtracker_basic`
--

DROP TABLE IF EXISTS `vtiger_modtracker_basic`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_modtracker_basic` (
  `id` int NOT NULL,
  `crmid` int DEFAULT NULL,
  `module` varchar(50) DEFAULT NULL,
  `whodid` int DEFAULT NULL,
  `changedon` datetime DEFAULT NULL,
  `status` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `crmidx` (`crmid`),
  KEY `idx` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_modtracker_basic`
--

LOCK TABLES `vtiger_modtracker_basic` WRITE;
/*!40000 ALTER TABLE `vtiger_modtracker_basic` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_modtracker_basic` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_modtracker_detail`
--

DROP TABLE IF EXISTS `vtiger_modtracker_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_modtracker_detail` (
  `id` int DEFAULT NULL,
  `fieldname` varchar(100) DEFAULT NULL,
  `prevalue` text,
  `postvalue` text,
  KEY `idx` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_modtracker_detail`
--

LOCK TABLES `vtiger_modtracker_detail` WRITE;
/*!40000 ALTER TABLE `vtiger_modtracker_detail` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_modtracker_detail` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_modtracker_relations`
--

DROP TABLE IF EXISTS `vtiger_modtracker_relations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_modtracker_relations` (
  `id` int NOT NULL,
  `targetmodule` varchar(100) NOT NULL,
  `targetid` int NOT NULL,
  `changedon` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_modtracker_relations`
--

LOCK TABLES `vtiger_modtracker_relations` WRITE;
/*!40000 ALTER TABLE `vtiger_modtracker_relations` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_modtracker_relations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_modtracker_tabs`
--

DROP TABLE IF EXISTS `vtiger_modtracker_tabs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_modtracker_tabs` (
  `tabid` int NOT NULL,
  `visible` int DEFAULT '0',
  PRIMARY KEY (`tabid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_modtracker_tabs`
--

LOCK TABLES `vtiger_modtracker_tabs` WRITE;
/*!40000 ALTER TABLE `vtiger_modtracker_tabs` DISABLE KEYS */;
INSERT INTO `vtiger_modtracker_tabs` VALUES (2,1),(4,1),(6,1),(7,1),(8,1),(9,1),(10,1),(13,1),(14,1),(15,1),(16,1),(18,1),(19,1),(20,1),(21,1),(22,1),(23,1),(26,1),(28,1),(34,1),(35,1),(37,1),(39,1),(40,1),(41,1),(42,1),(43,1),(49,1);
/*!40000 ALTER TABLE `vtiger_modtracker_tabs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_module_dashboard_widgets`
--

DROP TABLE IF EXISTS `vtiger_module_dashboard_widgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_module_dashboard_widgets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `linkid` int DEFAULT NULL,
  `userid` int DEFAULT NULL,
  `filterid` int DEFAULT NULL,
  `title` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `data` text COLLATE utf8mb4_general_ci,
  `position` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reportid` int DEFAULT NULL,
  `dashboardtabid` int DEFAULT NULL,
  `size` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dashboardtabid` (`dashboardtabid`),
  CONSTRAINT `vtiger_module_dashboard_widgets_ibfk_1` FOREIGN KEY (`dashboardtabid`) REFERENCES `vtiger_dashboard_tabs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_module_dashboard_widgets`
--

LOCK TABLES `vtiger_module_dashboard_widgets` WRITE;
/*!40000 ALTER TABLE `vtiger_module_dashboard_widgets` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_module_dashboard_widgets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_no_of_currency_decimals`
--

DROP TABLE IF EXISTS `vtiger_no_of_currency_decimals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_no_of_currency_decimals` (
  `no_of_currency_decimalsid` int NOT NULL AUTO_INCREMENT,
  `no_of_currency_decimals` varchar(200) NOT NULL,
  `sortorderid` int DEFAULT NULL,
  `presence` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`no_of_currency_decimalsid`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_no_of_currency_decimals`
--

LOCK TABLES `vtiger_no_of_currency_decimals` WRITE;
/*!40000 ALTER TABLE `vtiger_no_of_currency_decimals` DISABLE KEYS */;
INSERT INTO `vtiger_no_of_currency_decimals` VALUES (2,'2',2,1),(3,'3',3,1),(4,'4',4,1),(5,'5',5,1),(6,'0',0,1),(7,'1',1,1);
/*!40000 ALTER TABLE `vtiger_no_of_currency_decimals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_no_of_currency_decimals_seq`
--

DROP TABLE IF EXISTS `vtiger_no_of_currency_decimals_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_no_of_currency_decimals_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_no_of_currency_decimals_seq`
--

LOCK TABLES `vtiger_no_of_currency_decimals_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_no_of_currency_decimals_seq` DISABLE KEYS */;
INSERT INTO `vtiger_no_of_currency_decimals_seq` VALUES (7);
/*!40000 ALTER TABLE `vtiger_no_of_currency_decimals_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_notebook_contents`
--

DROP TABLE IF EXISTS `vtiger_notebook_contents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_notebook_contents` (
  `userid` int NOT NULL,
  `notebookid` int NOT NULL,
  `contents` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_notebook_contents`
--

LOCK TABLES `vtiger_notebook_contents` WRITE;
/*!40000 ALTER TABLE `vtiger_notebook_contents` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_notebook_contents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_notes`
--

DROP TABLE IF EXISTS `vtiger_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_notes` (
  `notesid` int NOT NULL,
  `note_no` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `title` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `filename` text COLLATE utf8mb4_general_ci,
  `notecontent` text COLLATE utf8mb4_general_ci,
  `folderid` int NOT NULL DEFAULT '1',
  `filetype` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `filelocationtype` varchar(5) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `filedownloadcount` int DEFAULT NULL,
  `filestatus` int DEFAULT NULL,
  `filesize` int NOT NULL DEFAULT '0',
  `fileversion` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tags` varchar(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `smcreatorid` int DEFAULT NULL,
  `smownerid` int DEFAULT NULL,
  `modifiedby` int DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_general_ci,
  `createdtime` datetime DEFAULT NULL,
  `modifiedtime` datetime DEFAULT NULL,
  `viewedtime` datetime DEFAULT NULL,
  `deleted` int DEFAULT NULL,
  `label` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `smgroupid` int DEFAULT NULL,
  `source` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`notesid`),
  KEY `notes_title_idx` (`title`),
  KEY `notes_notesid_idx` (`notesid`),
  KEY `idx_info` (`deleted`,`modifiedtime`),
  KEY `idx_label` (`deleted`,`label`),
  KEY `idx_owner` (`deleted`,`smownerid`),
  CONSTRAINT `fk_1_vtiger_notes` FOREIGN KEY (`notesid`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_notes`
--

LOCK TABLES `vtiger_notes` WRITE;
/*!40000 ALTER TABLE `vtiger_notes` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_notescf`
--

DROP TABLE IF EXISTS `vtiger_notescf`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_notescf` (
  `notesid` int NOT NULL,
  PRIMARY KEY (`notesid`),
  CONSTRAINT `fk_notesid_vtiger_notescf` FOREIGN KEY (`notesid`) REFERENCES `vtiger_notes` (`notesid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_notescf`
--

LOCK TABLES `vtiger_notescf` WRITE;
/*!40000 ALTER TABLE `vtiger_notescf` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_notescf` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_notificationscheduler`
--

DROP TABLE IF EXISTS `vtiger_notificationscheduler`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_notificationscheduler` (
  `schedulednotificationid` int NOT NULL AUTO_INCREMENT,
  `schedulednotificationname` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `active` int DEFAULT NULL,
  `notificationsubject` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notificationbody` text COLLATE utf8mb4_general_ci,
  `label` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `type` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`schedulednotificationid`),
  UNIQUE KEY `notificationscheduler_schedulednotificationname_idx` (`schedulednotificationname`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_notificationscheduler`
--

LOCK TABLES `vtiger_notificationscheduler` WRITE;
/*!40000 ALTER TABLE `vtiger_notificationscheduler` DISABLE KEYS */;
INSERT INTO `vtiger_notificationscheduler` VALUES (1,'LBL_TASK_NOTIFICATION_DESCRITPION',1,'Task Delay Notification','Tasks delayed beyond 24 hrs ','LBL_TASK_NOTIFICATION',NULL),(2,'LBL_BIG_DEAL_DESCRIPTION',1,'Big Deal notification','Success! A big deal has been won! ','LBL_BIG_DEAL',NULL),(3,'LBL_TICKETS_DESCRIPTION',1,'Pending Tickets notification','Ticket pending please ','LBL_PENDING_TICKETS',NULL),(4,'LBL_MANY_TICKETS_DESCRIPTION',1,'Too many tickets Notification','Too many tickets pending against this entity ','LBL_MANY_TICKETS',NULL),(5,'LBL_START_DESCRIPTION',1,'Support Start Notification','10','LBL_START_NOTIFICATION','select'),(6,'LBL_SUPPORT_DESCRIPTION',1,'Support ending please','11','LBL_SUPPORT_NOTICIATION','select'),(7,'LBL_SUPPORT_DESCRIPTION_MONTH',1,'Support ending please','12','LBL_SUPPORT_NOTICIATION_MONTH','select'),(8,'LBL_ACTIVITY_REMINDER_DESCRIPTION',1,'Activity Reminder Notification','This is a reminder notification for the Activity','LBL_ACTIVITY_NOTIFICATION',NULL);
/*!40000 ALTER TABLE `vtiger_notificationscheduler` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_notificationscheduler_seq`
--

DROP TABLE IF EXISTS `vtiger_notificationscheduler_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_notificationscheduler_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_notificationscheduler_seq`
--

LOCK TABLES `vtiger_notificationscheduler_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_notificationscheduler_seq` DISABLE KEYS */;
INSERT INTO `vtiger_notificationscheduler_seq` VALUES (8);
/*!40000 ALTER TABLE `vtiger_notificationscheduler_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_opportunity_type`
--

DROP TABLE IF EXISTS `vtiger_opportunity_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_opportunity_type` (
  `opptypeid` int NOT NULL AUTO_INCREMENT,
  `opportunity_type` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT NULL,
  `color` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`opptypeid`),
  UNIQUE KEY `opportunity_type_opportunity_type_idx` (`opportunity_type`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_opportunity_type`
--

LOCK TABLES `vtiger_opportunity_type` WRITE;
/*!40000 ALTER TABLE `vtiger_opportunity_type` DISABLE KEYS */;
INSERT INTO `vtiger_opportunity_type` VALUES (2,'Existing Business',1,128,1,NULL),(3,'New Business',1,129,2,NULL);
/*!40000 ALTER TABLE `vtiger_opportunity_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_opportunity_type_seq`
--

DROP TABLE IF EXISTS `vtiger_opportunity_type_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_opportunity_type_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_opportunity_type_seq`
--

LOCK TABLES `vtiger_opportunity_type_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_opportunity_type_seq` DISABLE KEYS */;
INSERT INTO `vtiger_opportunity_type_seq` VALUES (3);
/*!40000 ALTER TABLE `vtiger_opportunity_type_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_opportunitystage`
--

DROP TABLE IF EXISTS `vtiger_opportunitystage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_opportunitystage` (
  `potstageid` int NOT NULL AUTO_INCREMENT,
  `stage` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `sortorderid` int NOT NULL DEFAULT '0',
  `presence` int NOT NULL DEFAULT '1',
  `probability` decimal(3,2) DEFAULT '0.00',
  PRIMARY KEY (`potstageid`),
  UNIQUE KEY `opportunitystage_stage_idx` (`stage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_opportunitystage`
--

LOCK TABLES `vtiger_opportunitystage` WRITE;
/*!40000 ALTER TABLE `vtiger_opportunitystage` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_opportunitystage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_org_share_action2tab`
--

DROP TABLE IF EXISTS `vtiger_org_share_action2tab`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_org_share_action2tab` (
  `share_action_id` int NOT NULL,
  `tabid` int NOT NULL,
  PRIMARY KEY (`share_action_id`,`tabid`),
  KEY `fk_2_vtiger_org_share_action2tab` (`tabid`),
  CONSTRAINT `fk_2_vtiger_org_share_action2tab` FOREIGN KEY (`tabid`) REFERENCES `vtiger_tab` (`tabid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_org_share_action2tab`
--

LOCK TABLES `vtiger_org_share_action2tab` WRITE;
/*!40000 ALTER TABLE `vtiger_org_share_action2tab` DISABLE KEYS */;
INSERT INTO `vtiger_org_share_action2tab` VALUES (0,2),(1,2),(2,2),(3,2),(0,4),(1,4),(2,4),(3,4),(0,6),(1,6),(2,6),(3,6),(0,7),(1,7),(2,7),(3,7),(0,8),(1,8),(2,8),(3,8),(0,9),(1,9),(2,9),(3,9),(0,10),(1,10),(2,10),(3,10),(0,13),(1,13),(2,13),(3,13),(0,14),(1,14),(2,14),(3,14),(0,16),(1,16),(2,16),(3,16),(0,18),(1,18),(2,18),(3,18),(0,20),(1,20),(2,20),(3,20),(0,21),(1,21),(2,21),(3,21),(0,22),(1,22),(2,22),(3,22),(0,23),(1,23),(2,23),(3,23),(0,26),(1,26),(2,26),(3,26),(0,34),(1,34),(2,34),(3,34),(0,35),(1,35),(2,35),(3,35),(0,37),(1,37),(2,37),(3,37),(0,39),(1,39),(2,39),(3,39),(0,40),(1,40),(2,40),(3,40),(0,41),(1,41),(2,41),(3,41),(0,42),(1,42),(2,42),(3,42),(0,43),(1,43),(2,43),(3,43),(0,49),(1,49),(2,49),(3,49);
/*!40000 ALTER TABLE `vtiger_org_share_action2tab` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_org_share_action_mapping`
--

DROP TABLE IF EXISTS `vtiger_org_share_action_mapping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_org_share_action_mapping` (
  `share_action_id` int NOT NULL,
  `share_action_name` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`share_action_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_org_share_action_mapping`
--

LOCK TABLES `vtiger_org_share_action_mapping` WRITE;
/*!40000 ALTER TABLE `vtiger_org_share_action_mapping` DISABLE KEYS */;
INSERT INTO `vtiger_org_share_action_mapping` VALUES (0,'Public: Read Only'),(1,'Public: Read, Create/Edit'),(2,'Public: Read, Create/Edit, Delete'),(3,'Private'),(4,'Hide Details'),(5,'Hide Details and Add Events'),(6,'Show Details'),(7,'Show Details and Add Events');
/*!40000 ALTER TABLE `vtiger_org_share_action_mapping` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_organizationdetails`
--

DROP TABLE IF EXISTS `vtiger_organizationdetails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_organizationdetails` (
  `organization_id` int NOT NULL,
  `organizationname` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `code` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fax` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `website` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `logoname` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `logo` text COLLATE utf8mb4_general_ci,
  `vatid` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`organization_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_organizationdetails`
--

LOCK TABLES `vtiger_organizationdetails` WRITE;
/*!40000 ALTER TABLE `vtiger_organizationdetails` DISABLE KEYS */;
INSERT INTO `vtiger_organizationdetails` VALUES (1,'','','','','','','','','','frevocrm-logo.png',NULL,NULL);
/*!40000 ALTER TABLE `vtiger_organizationdetails` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_organizationdetails_seq`
--

DROP TABLE IF EXISTS `vtiger_organizationdetails_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_organizationdetails_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_organizationdetails_seq`
--

LOCK TABLES `vtiger_organizationdetails_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_organizationdetails_seq` DISABLE KEYS */;
INSERT INTO `vtiger_organizationdetails_seq` VALUES (1);
/*!40000 ALTER TABLE `vtiger_organizationdetails_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_othereventduration`
--

DROP TABLE IF EXISTS `vtiger_othereventduration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_othereventduration` (
  `othereventdurationid` int NOT NULL AUTO_INCREMENT,
  `othereventduration` varchar(200) NOT NULL,
  `sortorderid` int DEFAULT NULL,
  `presence` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`othereventdurationid`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_othereventduration`
--

LOCK TABLES `vtiger_othereventduration` WRITE;
/*!40000 ALTER TABLE `vtiger_othereventduration` DISABLE KEYS */;
INSERT INTO `vtiger_othereventduration` VALUES (1,'5',0,1),(2,'10',1,1),(3,'30',2,1),(4,'60',3,1),(5,'120',4,1);
/*!40000 ALTER TABLE `vtiger_othereventduration` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_othereventduration_seq`
--

DROP TABLE IF EXISTS `vtiger_othereventduration_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_othereventduration_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_othereventduration_seq`
--

LOCK TABLES `vtiger_othereventduration_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_othereventduration_seq` DISABLE KEYS */;
INSERT INTO `vtiger_othereventduration_seq` VALUES (5);
/*!40000 ALTER TABLE `vtiger_othereventduration_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_parameters`
--

DROP TABLE IF EXISTS `vtiger_parameters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_parameters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `key` varchar(200) DEFAULT NULL,
  `value` text,
  `description` text,
  PRIMARY KEY (`id`),
  KEY `key` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_parameters`
--

LOCK TABLES `vtiger_parameters` WRITE;
/*!40000 ALTER TABLE `vtiger_parameters` DISABLE KEYS */;
INSERT INTO `vtiger_parameters` VALUES (1,'FORCE_MULTI_FACTOR_AUTH','false','多要素認証を強制するフラグです。\n強制しない場合もユーザーが多要素認証を設定している場合は、ログイン時に多要素認証が要求されます。\ntrue: 強制する\nfalse: 強制しない'),(2,'USER_LOCK_TIME','30','USER_LOCK_COUNTの回数ログインに失敗した際、ユーザーのログインをロックする時間（分）です。\n設定は半角の正の整数値で行ってください。\n全角数値、数値として認識できない文字列、0以下の値を指定した場合などは、ログインロックは行われません。'),(3,'USER_LOCK_COUNT','5','ログインに失敗した際、ユーザーのログインをロックするまでの回数です。\n設定した回数連続でログインに失敗するとユーザーのログインにロックがかかり、USER_LOCK_TIMEで指定した時間が経過するまでログインできません。\n設定は半角の正の整数値で行ってください。\n全角数値、数値として認識できない文字列、0以下の値を指定した場合などは、ログインロックは行われません。'),(4,'IMPORT_MAX_HISTORY_COUNT','10','ユーザーかつモジュール単位で保持できるインポート履歴の最大件数です。'),(5,'SHOW_SCHEDULE_CONFIRM_FLAG','false','他人のカレンダーを変更する際の確認フラグです。true: 確認する、false: 確認しない'),(6,'CALENDAR_REMEMBER_FEED_SELECTION','false','共有カレンダーにおけるユーザー一覧のチェック状態を制御するフラグです。\ntrue : 組織・役割切替時もチェック状態を記憶し、すべての表示に反映します。\nfalse : マイグループのみチェック状態を記憶し、他の組織・役割では常に全選択とします。');
/*!40000 ALTER TABLE `vtiger_parameters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_parenttab`
--

DROP TABLE IF EXISTS `vtiger_parenttab`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_parenttab` (
  `parenttabid` int NOT NULL,
  `parenttab_label` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `sequence` int NOT NULL,
  `visible` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`parenttabid`),
  KEY `parenttab_parenttabid_parenttabl_label_visible_idx` (`parenttabid`,`parenttab_label`,`visible`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_parenttab`
--

LOCK TABLES `vtiger_parenttab` WRITE;
/*!40000 ALTER TABLE `vtiger_parenttab` DISABLE KEYS */;
INSERT INTO `vtiger_parenttab` VALUES (1,'My Home Page',1,0),(2,'Marketing',2,0),(3,'Sales',3,0),(4,'Support',4,0),(5,'Analytics',5,0),(6,'Inventory',6,0),(7,'Tools',7,0),(8,'Settings',8,0);
/*!40000 ALTER TABLE `vtiger_parenttab` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_parenttabrel`
--

DROP TABLE IF EXISTS `vtiger_parenttabrel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_parenttabrel` (
  `parenttabid` int NOT NULL,
  `tabid` int NOT NULL,
  `sequence` int NOT NULL,
  KEY `parenttabrel_tabid_parenttabid_idx` (`tabid`,`parenttabid`),
  KEY `fk_2_vtiger_parenttabrel` (`parenttabid`),
  CONSTRAINT `fk_1_vtiger_parenttabrel` FOREIGN KEY (`tabid`) REFERENCES `vtiger_tab` (`tabid`) ON DELETE CASCADE,
  CONSTRAINT `fk_2_vtiger_parenttabrel` FOREIGN KEY (`parenttabid`) REFERENCES `vtiger_parenttab` (`parenttabid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_parenttabrel`
--

LOCK TABLES `vtiger_parenttabrel` WRITE;
/*!40000 ALTER TABLE `vtiger_parenttabrel` DISABLE KEYS */;
INSERT INTO `vtiger_parenttabrel` VALUES (1,9,2),(1,28,4),(1,3,1),(3,7,1),(3,6,2),(3,4,3),(3,2,4),(3,20,5),(3,22,6),(3,23,7),(3,19,8),(3,8,9),(4,13,1),(4,15,2),(4,6,3),(4,4,4),(4,8,5),(5,1,2),(5,25,1),(6,14,1),(6,18,2),(6,19,3),(6,21,4),(6,22,5),(6,20,6),(6,23,7),(7,24,1),(7,27,2),(7,8,3),(2,26,1),(2,6,2),(2,4,3),(2,28,4),(4,28,7),(2,7,5),(2,9,6),(4,9,8),(2,8,8),(3,9,11),(7,32,4),(7,32,5),(4,34,9),(4,34,10),(6,35,8),(6,35,9),(7,38,6),(7,38,7),(4,39,11),(4,39,12),(4,40,13),(4,40,14),(4,41,15),(4,41,16),(3,42,12),(3,42,13),(6,43,10),(6,43,11),(7,47,8),(7,47,9),(7,24,10),(7,27,11),(7,47,12),(7,38,13),(7,48,14),(3,49,14);
/*!40000 ALTER TABLE `vtiger_parenttabrel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_payment_duration`
--

DROP TABLE IF EXISTS `vtiger_payment_duration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_payment_duration` (
  `payment_duration_id` int DEFAULT NULL,
  `payment_duration` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sortorderid` int DEFAULT NULL,
  `presence` int DEFAULT NULL,
  `color` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_payment_duration`
--

LOCK TABLES `vtiger_payment_duration` WRITE;
/*!40000 ALTER TABLE `vtiger_payment_duration` DISABLE KEYS */;
INSERT INTO `vtiger_payment_duration` VALUES (1,'Net 30 days',6,1,NULL),(2,'Net 45 days',7,1,NULL),(3,'Net 60 days',8,1,NULL),(4,'Net 01 day',1,1,NULL),(5,'Net 05 days',2,1,NULL),(6,'Net 07 days',3,1,NULL),(7,'Net 10 days',4,1,NULL),(8,'Net 15 days',5,1,NULL);
/*!40000 ALTER TABLE `vtiger_payment_duration` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_payment_duration_seq`
--

DROP TABLE IF EXISTS `vtiger_payment_duration_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_payment_duration_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_payment_duration_seq`
--

LOCK TABLES `vtiger_payment_duration_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_payment_duration_seq` DISABLE KEYS */;
INSERT INTO `vtiger_payment_duration_seq` VALUES (8);
/*!40000 ALTER TABLE `vtiger_payment_duration_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_pdftemplates`
--

DROP TABLE IF EXISTS `vtiger_pdftemplates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_pdftemplates` (
  `foldername` varchar(100) DEFAULT NULL,
  `module` varchar(100) DEFAULT NULL,
  `templatename` varchar(100) DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `description` text,
  `body` longtext,
  `deleted` int NOT NULL DEFAULT '0',
  `systemtemplate` int NOT NULL DEFAULT '0',
  `templateid` int NOT NULL AUTO_INCREMENT,
  `pdffilename` text,
  PRIMARY KEY (`templateid`),
  KEY `pdftemplates_foldernamd_templatename_subject_idx` (`foldername`,`templatename`,`subject`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_pdftemplates`
--

LOCK TABLES `vtiger_pdftemplates` WRITE;
/*!40000 ALTER TABLE `vtiger_pdftemplates` DISABLE KEYS */;
INSERT INTO `vtiger_pdftemplates` VALUES ('','Quotes','LBL_EXPERIENCE_BOOK','','','<html>\n<head>\n	<title></title>\n</head>\n<body>\n<div style=\"text-align: center;\">\n<div></div>\n\n<div style=\"text-align: left;\">\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"width:100%;\">\n	<tbody>\n		<tr>\n			<td style=\"text-align: center;\"><strong style=\"font-size: 20px; text-align: center;\">御見積書</strong></td>\n		</tr>\n		<tr>\n			<td style=\"text-align: right;\"><span style=\"text-align: right;\">$custom-currentdate$&nbsp;</span>$quotes-quote_no$</td>\n		</tr>\n	</tbody>\n</table>\n&nbsp;\n\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"width:100%;\">\n	<tbody>\n		<tr>\n			<td style=\"width: 350px;\">$quotes-accountid:accountname$ 御中<br />\n			<br />\n			<span style=\"font-size:9px;\">下記の通り御見積り申し上げます。<br />何卒ご用命賜りますようお願い申し上げます。</span><br />\n			<br />\n			&nbsp;\n			<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"width:250px;\">\n				<tbody>\n					<tr>\n						<td><span style=\"font-size:11px;\">御見積金額</span></td>\n						<td><span style=\"font-size:11px;\">:</span></td>\n						<td style=\"text-align: right;\"><span style=\"font-size:11px;\">$quotes-pre_tax_total$</span></td>\n						<td><span style=\"font-size:11px;\">-</span></td>\n					</tr>\n					<tr>\n						<td><span style=\"font-size:11px;\">消費税</span></td>\n						<td><span style=\"font-size:11px;\">:</span></td>\n						<td style=\"text-align: right;\"><span style=\"font-size:11px;\">$quotes-tax_totalamount$</span></td>\n						<td><span style=\"font-size:11px;\">-</span></td>\n					</tr>\n					<tr>\n						<td><span style=\"font-size:11px;\">合計金額</span></td>\n						<td><span style=\"font-size:11px;\">:</span></td>\n						<td style=\"text-align: right;\"><span style=\"font-size:11px;\"><strong>$quotes-total$</strong></span></td>\n						<td><span style=\"font-size:11px;\">-</span></td>\n					</tr>\n				</tbody>\n			</table>\n			</td>\n			<td style=\"width: 150px;\"><img alt=\"\" src=\"data:image/png;base64,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\" style=\"text-align: right; width: 200px; height: 40px; float: right;\" />$companydetails-organizationname$<br />\n			<span style=\"font-size:10px;\">$companydetails-code$<br />\n			$companydetails-state$ $companydetails-city$<br />\n			$companydetails-address$<br />\n			TEL: $companydetails-phone$<br />\n			FAX: $companydetails-fax$</span><br />\n			&nbsp;</td>\n		</tr>\n	</tbody>\n</table>\n\n<p></p>\n\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"width: 100%;\">\n	<tbody>\n		<tr>\n			<td><span style=\"font-size: 11px;\">$quotes-subject$</span></td>\n		</tr>\n	</tbody>\n</table>\n&nbsp;\n\n<table align=\"left\" border=\"1\" cellpadding=\"0\" cellspacing=\"0\" style=\"width:100%;\">\n	<tbody>\n		<tr>\n			<td style=\"background-color: rgb(238, 238, 238); width: 60%;\"><span style=\"font-size: 10px;\">項目</span></td>\n			<td style=\"background-color: rgb(238, 238, 238); width: 10%;\"><span style=\"font-size: 10px;\">数量</span></td>\n			<td style=\"background-color: rgb(238, 238, 238); width: 15%;\"><span style=\"font-size: 10px;\">単価</span></td>\n			<td style=\"background-color: rgb(238, 238, 238); width: 15%;\"><span style=\"font-size: 10px;\">ご提供金額</span></td>\n		</tr>\n		<tr>\n			<td colspan=\"4\"><span style=\"font-size: 10px;\">$loop-products$</span></td>\n		</tr>\n		<tr>\n			<td><span style=\"font-size: 10px;\">$quotes-productid$<br />\n			$quotes-comment$</span></td>\n			<td style=\"text-align: right;\"><span style=\"font-size: 10px;\">$quotes-quantity$</span></td>\n			<td style=\"text-align: right;\"><span style=\"font-size: 10px;\">$quotes-listprice$</span></td>\n			<td style=\"text-align: right;\"><span style=\"font-size:10px;\">$quotes-producttotal$</span></td>\n		</tr>\n		<tr>\n			<td colspan=\"4\"><span style=\"font-size: 10px;\">$loop-products$</span></td>\n		</tr>\n		<tr>\n			<td colspan=\"3\" style=\"text-align: right;\"><span style=\"font-size:10px;\">貴社特別値引き</span></td>\n			<td style=\"text-align: right;\"><span style=\"color:#FF0000;\"><span style=\"font-size:10px;\">$quotes-discount_amount$</span></span></td>\n		</tr>\n		<tr>\n			<td colspan=\"3\" style=\"text-align: right;\"><span style=\"font-size: 9px;\">小計</span></td>\n			<td style=\"text-align: right;\"><span style=\"font-size: 9px;\">$quotes-pre_tax_total$</span></td>\n		</tr>\n		<tr>\n			<td colspan=\"3\" style=\"text-align: right;\"><span style=\"font-size: 9px;\">消費税</span></td>\n			<td style=\"text-align: right;\"><span style=\"font-size: 9px;\">$quotes-tax_totalamount$</span></td>\n		</tr>\n		<tr>\n			<td colspan=\"3\" rowspan=\"1\" style=\"text-align: right;\"><span style=\"font-size: 10px;\">合計</span></td>\n			<td style=\"text-align: right;\"><span style=\"font-size:11px;\">$quotes-total$</span></td>\n		</tr>\n	</tbody>\n</table>\n\n<p></p>\n\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"width: 100%;\">\n	<tbody>\n		<tr>\n			<td><span style=\"font-size: 11px;\">備考</span></td>\n		</tr>\n	</tbody>\n</table>\n\n<table border=\"1\" cellpadding=\"0\" cellspacing=\"0\" style=\"width:100%;\">\n	<tbody>\n		<tr>\n			<td style=\"width: 100%;\"><span style=\"font-size:10px;\">$quotes-terms_conditions$</span></td>\n		</tr>\n	</tbody>\n</table>\n<br />\n&nbsp;</div>\n</div>\n</body>\n</html>\n',0,0,1,NULL),('','Invoice','RecurringInvoice','','','<html>\n<head>\n	<title></title>\n</head>\n<body>\n<div style=\"text-align: center;\">\n<div></div>\n\n<div style=\"text-align: left;\">\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"width:100%;\">\n	<tbody>\n		<tr>\n			<td style=\"text-align: center;\"><strong style=\"font-size: 20px; text-align: center;\">御請求書</strong></td>\n		</tr>\n		<tr>\n			<td style=\"text-align: right;\"><span style=\"text-align: right;\">$custom-currentdate$&nbsp;</span>$invoice-invoice_no$</td>\n		</tr>\n	</tbody>\n</table>\n&nbsp;\n\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"width:100%;\">\n	<tbody>\n		<tr>\n			<td style=\"width: 350px;\">\n			<p>$invoice-accountid:accountname$&nbsp;御請求書<br />\n			<br />\n			<span style=\"font-size:9px;\">下記の通り御請求申し上げます。</span></p>\n\n			<p></p>\n\n			<table border=\"1\" cellpadding=\"0\" cellspacing=\"0\" style=\"width:250px;\">\n				<tbody>\n					<tr>\n						<td style=\"width: 75px;\"><span style=\"font-size:11px;\">ご請求金額</span></td>\n						<td style=\"text-align: right;\"><span style=\"font-size:11px;\">$invoice-total$</span></td>\n					</tr>\n					<tr>\n						<td><span style=\"font-size:11px;\">お支払い期限</span></td>\n						<td style=\"text-align: right;\"><span style=\"font-size:11px;\">$invoice-duedate$</span></td>\n					</tr>\n				</tbody>\n			</table>\n			</td>\n			<td style=\"width: 150px;\"><img alt=\"\" src=\"data:image/png;base64,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\" style=\"text-align: right; width: 200px; height: 40px; float: right;\" />$companydetails-organizationname$<br />\n			<span style=\"font-size:10px;\">$companydetails-code$<br />\n			$companydetails-state$ $companydetails-city$<br />\n			$companydetails-address$<br />\n			TEL: $companydetails-phone$<br />\n			FAX: $companydetails-fax$</span><br />\n			&nbsp;</td>\n		</tr>\n	</tbody>\n</table>\n\n<p></p>\n\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"width: 100%;\">\n	<tbody>\n		<tr>\n			<td><span style=\"font-size:11px;\">$invoice-subject$</span></td>\n		</tr>\n	</tbody>\n</table>\n&nbsp;\n\n<table align=\"left\" border=\"1\" cellpadding=\"0\" cellspacing=\"0\" style=\"width:100%;\">\n	<tbody>\n		<tr>\n			<td style=\"background-color: rgb(238, 238, 238); width: 60%;\"><span style=\"font-size: 10px;\">お支払い期限</span></td>\n			<td style=\"background-color: rgb(238, 238, 238); width: 10%;\"><span style=\"font-size: 10px;\">お支払い期限</span></td>\n			<td style=\"background-color: rgb(238, 238, 238); width: 15%;\"><span style=\"font-size: 10px;\">お支払い期限</span></td>\n			<td style=\"background-color: rgb(238, 238, 238); width: 15%;\"><span style=\"font-size: 10px;\">ご請求金額</span></td>\n		</tr>\n		<tr>\n			<td colspan=\"4\"><span style=\"font-size: 10px;\">$loop-products$</span></td>\n		</tr>\n		<tr>\n			<td><span style=\"font-size:11px;\">$invoice-productid$<br />\n			$invoice-comment$</span></td>\n			<td style=\"text-align: right;\"><span style=\"font-size:10px;\">$invoice-quantity$</span></td>\n			<td style=\"text-align: right;\"><span style=\"font-size:10px;\">$invoice-listprice$</span></td>\n			<td style=\"text-align: right;\"><span style=\"font-size:10px;\">$invoice-producttotal$</span></td>\n		</tr>\n		<tr>\n			<td colspan=\"4\"><span style=\"font-size:10px;\">$loop-products$</span></td>\n		</tr>\n		<tr>\n			<td colspan=\"3\" style=\"text-align: right;\"><span style=\"font-size:10px;\">貴社特別値引き</span></td>\n			<td style=\"text-align: right;\"><span style=\"font-size:10px;\">$invoice-discount_amount$</span></td>\n		</tr>\n		<tr>\n			<td colspan=\"3\" style=\"text-align: right;\"><span style=\"font-size:10px;\">小計</span></td>\n			<td style=\"text-align: right;\"><span style=\"font-size:10px;\">$invoice-pre_tax_total$</span></td>\n		</tr>\n		<tr>\n			<td colspan=\"3\" style=\"text-align: right;\"><span style=\"font-size:10px;\">消費税</span></td>\n			<td style=\"text-align: right;\"><span style=\"font-size:10px;\">$invoice-tax_totalamount$</span></td>\n		</tr>\n		<tr>\n			<td colspan=\"3\" rowspan=\"1\" style=\"text-align: right;\"><span style=\"font-size:10px;\">合計</span></td>\n			<td style=\"text-align: right;\"><span style=\"font-size:10px;\">$invoice-total$</span></td>\n		</tr>\n	</tbody>\n</table>\n\n<p></p>\n\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"width: 100%;\">\n	<tbody>\n		<tr>\n			<td><span style=\"font-size: 11px;\">備考</span></td>\n		</tr>\n	</tbody>\n</table>\n\n<table border=\"1\" cellpadding=\"0\" cellspacing=\"0\" style=\"width:100%;\">\n	<tbody>\n		<tr>\n			<td style=\"width: 100%;\"><span style=\"font-size:10px;\">$invoice-terms_conditions$</span></td>\n		</tr>\n	</tbody>\n</table>\n<br />\n&nbsp;</div>\n</div>\n</body>\n</html>\n',0,0,2,NULL),('','SalesOrder','LBL_REQUEST_FOR_ANNOTATION','','','<html>\n<head>\n	<title></title>\n</head>\n<body>\n<div style=\"text-align: center;\">\n<div></div>\n\n<div style=\"text-align: left;\">\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"width:100%;\">\n	<tbody>\n		<tr>\n			<td style=\"text-align: center;\"><strong style=\"font-size: 20px; text-align: center;\">注文請書</strong></td>\n		</tr>\n		<tr>\n			<td style=\"text-align: right;\"><span style=\"text-align: right;\">$custom-currentdate$&nbsp;</span>$salesorder-salesorder_no$</td>\n		</tr>\n	</tbody>\n</table>\n&nbsp;\n\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"width:100%;\">\n	<tbody>\n		<tr>\n			<td style=\"width: 350px;\">$salesorder-accountid:accountname$ 御中<br />\n			<br />\n			<span style=\"font-size:9px;\">この度はご用命いただきまして誠にありがとうございます。<br />下記の内容につきましてご注文を通り承りました。</span><br />\n			<br />\n			&nbsp;\n			<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"width:250px;\">\n				<tbody>\n					<tr>\n						<td><span style=\"font-size:11px;\">御見積番号</span></td>\n						<td></td>\n						<td style=\"text-align: right;\"><span style=\"font-size:11px;\">$salesorder-quoteid:quote_no$</span></td>\n						<td><span style=\"font-size:11px;\">-</span></td>\n					</tr>\n					<tr>\n						<td><span style=\"font-size:11px;\">御見積金額</span></td>\n						<td><span style=\"font-size:11px;\">:</span></td>\n						<td style=\"text-align: right;\"><span style=\"font-size:11px;\">$salesorder-pre_tax_total$</span></td>\n						<td><span style=\"font-size:11px;\">-</span></td>\n					</tr>\n					<tr>\n						<td><span style=\"font-size:11px;\">消費税</span></td>\n						<td><span style=\"font-size:11px;\">:</span></td>\n						<td style=\"text-align: right;\"><span style=\"font-size:11px;\">$salesorder-tax_totalamount$</span></td>\n						<td><span style=\"font-size:11px;\">-</span></td>\n					</tr>\n					<tr>\n						<td><span style=\"font-size:11px;\">合計金額</span></td>\n						<td><span style=\"font-size:11px;\">:</span></td>\n						<td style=\"text-align: right;\"><span style=\"font-size:11px;\">$salesorder-total$</span></td>\n						<td><span style=\"font-size:11px;\">-</span></td>\n					</tr>\n				</tbody>\n			</table>\n			</td>\n			<td style=\"width: 150px;\"><img alt=\"\" src=\"data:image/png;base64,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\" style=\"text-align: right; width: 200px; height: 40px; float: right;\" />$companydetails-organizationname$<br />\n			<span style=\"font-size:10px;\">$companydetails-code$<br />\n			$companydetails-state$ $companydetails-city$<br />\n			$companydetails-address$<br />\n			TEL: $companydetails-phone$<br />\n			FAX: $companydetails-fax$</span><br />\n			&nbsp;</td>\n		</tr>\n	</tbody>\n</table>\n\n<p></p>\n\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"width: 100%;\">\n	<tbody>\n		<tr>\n			<td><span style=\"font-size: 11px;\">$quotes-subject$</span></td>\n		</tr>\n	</tbody>\n</table>\n&nbsp;\n\n<table align=\"left\" border=\"1\" cellpadding=\"0\" cellspacing=\"0\" style=\"width:100%;\">\n	<tbody>\n		<tr>\n			<td style=\"background-color: rgb(238, 238, 238); width: 60%;\"><span style=\"font-size: 10px;\">項目</span></td>\n			<td style=\"background-color: rgb(238, 238, 238); width: 10%;\"><span style=\"font-size: 10px;\">数量</span></td>\n			<td style=\"background-color: rgb(238, 238, 238); width: 15%;\"><span style=\"font-size: 10px;\">単価</span></td>\n			<td style=\"background-color: rgb(238, 238, 238); width: 15%;\"><span style=\"font-size: 10px;\">ご提供金額</span></td>\n		</tr>\n		<tr>\n			<td colspan=\"4\"><span style=\"font-size: 10px;\">$loop-products$</span></td>\n		</tr>\n		<tr>\n			<td><span style=\"font-size:10px;\">$salesorder-productid$<br />\n			$salesorder-comment$</span></td>\n			<td style=\"text-align: right;\"><span style=\"font-size:10px;\">$salesorder-quantity$</span></td>\n			<td style=\"text-align: right;\"><span style=\"font-size:10px;\">$salesorder-listprice$</span></td>\n			<td style=\"text-align: right;\"><span style=\"font-size:10px;\">$salesorder-producttotal$</span></td>\n		</tr>\n		<tr>\n			<td colspan=\"4\"><span style=\"font-size: 10px;\">$loop-products$</span></td>\n		</tr>\n		<tr>\n			<td colspan=\"3\" style=\"text-align: right;\"><span style=\"font-size:10px;\">貴社特別値引き</span></td>\n			<td style=\"text-align: right;\"><span style=\"font-size:10px;\">$salesorder-discount_amount$</span></td>\n		</tr>\n		<tr>\n			<td colspan=\"3\" style=\"text-align: right;\"><span style=\"font-size:10px;\">小計</span></td>\n			<td style=\"text-align: right;\"><span style=\"font-size:10px;\">$salesorder-subtotal$</span></td>\n		</tr>\n		<tr>\n			<td colspan=\"3\" style=\"text-align: right;\"><span style=\"font-size:10px;\">消費税</span></td>\n			<td style=\"text-align: right;\"><span style=\"font-size:10px;\">$salesorder-tax_totalamount$</span></td>\n		</tr>\n		<tr>\n			<td colspan=\"3\" rowspan=\"1\" style=\"text-align: right;\"><span style=\"font-size:10px;\">合計</span></td>\n			<td style=\"text-align: right;\"><span style=\"font-size:10px;\">$salesorder-total$</span></td>\n		</tr>\n	</tbody>\n</table>\n\n<p></p>\n\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"width: 100%;\">\n	<tbody>\n		<tr>\n			<td><span style=\"font-size: 11px;\">備考</span></td>\n		</tr>\n	</tbody>\n</table>\n\n<table border=\"1\" cellpadding=\"0\" cellspacing=\"0\" style=\"width:100%;\">\n	<tbody>\n		<tr>\n			<td style=\"width: 100%;\"><span style=\"font-size:10px;\">$salesorder-terms_conditions$</span></td>\n		</tr>\n	</tbody>\n</table>\n<br />\n&nbsp;</div>\n</div>\n</body>\n</html>\n',0,0,3,NULL),('','PurchaseOrder','LBL_PURCHASE_ORDER','','','<html>\n<head>\n	<title></title>\n</head>\n<body>\n<div style=\"text-align: center;\">\n<div></div>\n\n<div style=\"text-align: left;\">\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"width:100%;\">\n	<tbody>\n		<tr>\n			<td style=\"text-align: center;\"><strong style=\"font-size: 20px; text-align: center;\">発注書</strong></td>\n		</tr>\n		<tr>\n			<td style=\"text-align: right;\"><span style=\"text-align: right;\">$custom-currentdate$&nbsp;</span>$purchaseorder-purchaseorder_no$</td>\n		</tr>\n	</tbody>\n</table>\n&nbsp;\n\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"width:100%;\">\n	<tbody>\n		<tr>\n			<td style=\"width: 350px;\">$purchaseorder-vendorid$&nbsp;御中<br />\n			<br />\n			<span style=\"font-size:9px;\">下記の通り御請求申し上げます。</span><br />\n			<br />\n			&nbsp;\n			<table border=\"1\" cellpadding=\"0\" cellspacing=\"0\" style=\"width:250px;\">\n				<tbody>\n					<tr>\n						<td style=\"width: 75px;\"><span style=\"font-size:11px;\">貴社見積金額</span></td>\n						<td style=\"text-align: right;\"><span style=\"font-size:11px;\">$purchaseorder-total$</span></td>\n					</tr>\n				</tbody>\n			</table>\n			</td>\n			<td style=\"width: 150px;\"><img alt=\"\" src=\"data:image/png;base64,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\" style=\"text-align: right; width: 200px; height: 40px; float: right;\" />$companydetails-organizationname$<br />\n			<span style=\"font-size:10px;\">$companydetails-code$<br />\n			$companydetails-state$ $companydetails-city$<br />\n			$companydetails-address$<br />\n			TEL: $companydetails-phone$<br />\n			FAX: $companydetails-fax$</span><br />\n			&nbsp;</td>\n		</tr>\n	</tbody>\n</table>\n\n<p></p>\n\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"width: 100%;\">\n	<tbody>\n		<tr>\n			<td><span style=\"font-size:11px;\">$purchaseorder-subject$</span></td>\n		</tr>\n	</tbody>\n</table>\n&nbsp;\n\n<table align=\"left\" border=\"1\" cellpadding=\"0\" cellspacing=\"0\" style=\"width:100%;\">\n	<tbody>\n		<tr>\n			<td style=\"background-color: rgb(238, 238, 238); width: 60%;\"><span style=\"font-size: 10px;\">項目</span></td>\n			<td style=\"background-color: rgb(238, 238, 238); width: 10%;\"><span style=\"font-size: 10px;\">数量</span></td>\n			<td style=\"background-color: rgb(238, 238, 238); width: 15%;\"><span style=\"font-size: 10px;\">単価</span></td>\n			<td style=\"background-color: rgb(238, 238, 238); width: 15%;\"><span style=\"font-size: 10px;\">金額</span></td>\n		</tr>\n		<tr>\n			<td colspan=\"4\"><span style=\"font-size: 10px;\">$loop-products$</span></td>\n		</tr>\n		<tr>\n			<td><span style=\"font-size:10px;\">$purchaseorder-productid$<br />\n			$purchaseorder-comment$</span></td>\n			<td style=\"text-align: right;\"><span style=\"font-size:10px;\">$purchaseorder-quantity$</span></td>\n			<td style=\"text-align: right;\"><span style=\"font-size:10px;\">$purchaseorder-listprice$</span></td>\n			<td style=\"text-align: right;\"><span style=\"font-size:10px;\">$purchaseorder-producttotal$</span></td>\n		</tr>\n		<tr>\n			<td colspan=\"4\"><span style=\"font-size: 10px;\">$loop-products$</span></td>\n		</tr>\n		<tr>\n			<td colspan=\"3\" style=\"text-align: right;\"><span style=\"font-size:10px;\">特別値引き</span></td>\n			<td style=\"text-align: right;\"><span style=\"font-size:10px;\">$purchaseorder-discount_amount$</span></td>\n		</tr>\n		<tr>\n			<td colspan=\"3\" style=\"text-align: right;\"><span style=\"font-size:10px;\">小計</span></td>\n			<td style=\"text-align: right;\"><span style=\"font-size:10px;\">$purchaseorder-subtotal$</span></td>\n		</tr>\n		<tr>\n			<td colspan=\"3\" style=\"text-align: right;\"><span style=\"font-size:10px;\">消費税</span></td>\n			<td style=\"text-align: right;\"><span style=\"font-size:10px;\">$purchaseorder-tax_totalamount$</span></td>\n		</tr>\n		<tr>\n			<td colspan=\"3\" rowspan=\"1\" style=\"text-align: right;\"><span style=\"font-size:10px;\">合計</span></td>\n			<td style=\"text-align: right;\"><span style=\"font-size:10px;\">$purchaseorder-total$</span></td>\n		</tr>\n	</tbody>\n</table>\n\n<p></p>\n\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"width: 100%;\">\n	<tbody>\n		<tr>\n			<td><span style=\"font-size: 11px;\">備考</span></td>\n		</tr>\n	</tbody>\n</table>\n\n<table border=\"1\" cellpadding=\"0\" cellspacing=\"0\" style=\"width:100%;\">\n	<tbody>\n		<tr>\n			<td style=\"width: 100%;\"><span style=\"font-size:10px;\">$purchaseorder-terms_conditions$</span></td>\n		</tr>\n	</tbody>\n</table>\n<br />\n&nbsp;</div>\n</div>\n</body>\n</html>\n',0,0,4,NULL);
/*!40000 ALTER TABLE `vtiger_pdftemplates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_pdftemplates_seq`
--

DROP TABLE IF EXISTS `vtiger_pdftemplates_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_pdftemplates_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_pdftemplates_seq`
--

LOCK TABLES `vtiger_pdftemplates_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_pdftemplates_seq` DISABLE KEYS */;
INSERT INTO `vtiger_pdftemplates_seq` VALUES (5);
/*!40000 ALTER TABLE `vtiger_pdftemplates_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_pdftemplatescf`
--

DROP TABLE IF EXISTS `vtiger_pdftemplatescf`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_pdftemplatescf` (
  `templateid` int NOT NULL,
  PRIMARY KEY (`templateid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_pdftemplatescf`
--

LOCK TABLES `vtiger_pdftemplatescf` WRITE;
/*!40000 ALTER TABLE `vtiger_pdftemplatescf` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_pdftemplatescf` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_picklist`
--

DROP TABLE IF EXISTS `vtiger_picklist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_picklist` (
  `picklistid` int NOT NULL AUTO_INCREMENT,
  `name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`picklistid`),
  UNIQUE KEY `picklist_name_idx` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_picklist`
--

LOCK TABLES `vtiger_picklist` WRITE;
/*!40000 ALTER TABLE `vtiger_picklist` DISABLE KEYS */;
INSERT INTO `vtiger_picklist` VALUES (1,'accounttype'),(2,'activitytype'),(45,'assetstatus'),(3,'campaignstatus'),(4,'campaigntype'),(5,'carrier'),(33,'contract_priority'),(32,'contract_status'),(34,'contract_type'),(51,'dailyreportsstatus'),(48,'defaultactivitytype'),(47,'defaulteventstatus'),(49,'defaultlandingpage'),(6,'eventstatus'),(7,'expectedresponse'),(8,'faqcategories'),(9,'faqstatus'),(10,'glacct'),(11,'industry'),(12,'invoicestatus'),(13,'leadsource'),(14,'leadstatus'),(15,'manufacturer'),(16,'opportunity_type'),(17,'postatus'),(18,'productcategory'),(44,'progress'),(37,'projectmilestonetype'),(43,'projectpriority'),(41,'projectstatus'),(39,'projecttaskpriority'),(40,'projecttaskprogress'),(46,'projecttaskstatus'),(38,'projecttasktype'),(42,'projecttype'),(19,'quotestage'),(20,'rating'),(50,'reportsterm'),(21,'sales_stage'),(22,'salutationtype'),(36,'servicecategory'),(35,'service_usageunit'),(23,'sostatus'),(24,'taskpriority'),(25,'taskstatus'),(26,'ticketcategories'),(27,'ticketpriorities'),(28,'ticketseverities'),(29,'ticketstatus'),(31,'tracking_unit'),(30,'usageunit');
/*!40000 ALTER TABLE `vtiger_picklist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_picklist_dependency`
--

DROP TABLE IF EXISTS `vtiger_picklist_dependency`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_picklist_dependency` (
  `id` int NOT NULL,
  `tabid` int NOT NULL,
  `sourcefield` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `targetfield` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sourcevalue` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `targetvalues` text COLLATE utf8mb4_general_ci,
  `criteria` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_picklist_dependency`
--

LOCK TABLES `vtiger_picklist_dependency` WRITE;
/*!40000 ALTER TABLE `vtiger_picklist_dependency` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_picklist_dependency` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_picklist_seq`
--

DROP TABLE IF EXISTS `vtiger_picklist_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_picklist_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_picklist_seq`
--

LOCK TABLES `vtiger_picklist_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_picklist_seq` DISABLE KEYS */;
INSERT INTO `vtiger_picklist_seq` VALUES (51);
/*!40000 ALTER TABLE `vtiger_picklist_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_picklist_transitions`
--

DROP TABLE IF EXISTS `vtiger_picklist_transitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_picklist_transitions` (
  `fieldname` varchar(255) NOT NULL,
  `module` varchar(100) NOT NULL,
  `transition_data` varchar(1000) NOT NULL,
  PRIMARY KEY (`fieldname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_picklist_transitions`
--

LOCK TABLES `vtiger_picklist_transitions` WRITE;
/*!40000 ALTER TABLE `vtiger_picklist_transitions` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_picklist_transitions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_picklistvalues_seq`
--

DROP TABLE IF EXISTS `vtiger_picklistvalues_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_picklistvalues_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_picklistvalues_seq`
--

LOCK TABLES `vtiger_picklistvalues_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_picklistvalues_seq` DISABLE KEYS */;
INSERT INTO `vtiger_picklistvalues_seq` VALUES (308);
/*!40000 ALTER TABLE `vtiger_picklistvalues_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_pobillads`
--

DROP TABLE IF EXISTS `vtiger_pobillads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_pobillads` (
  `pobilladdressid` int NOT NULL,
  `bill_city` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bill_code` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bill_country` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bill_state` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bill_street` text COLLATE utf8mb4_general_ci,
  `bill_pobox` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`pobilladdressid`),
  CONSTRAINT `fk_1_vtiger_pobillads` FOREIGN KEY (`pobilladdressid`) REFERENCES `vtiger_purchaseorder` (`purchaseorderid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_pobillads`
--

LOCK TABLES `vtiger_pobillads` WRITE;
/*!40000 ALTER TABLE `vtiger_pobillads` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_pobillads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_portal`
--

DROP TABLE IF EXISTS `vtiger_portal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_portal` (
  `portalid` int NOT NULL,
  `portalname` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `portalurl` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `sequence` int NOT NULL,
  `setdefault` int NOT NULL DEFAULT '0',
  `createdtime` datetime DEFAULT NULL,
  PRIMARY KEY (`portalid`),
  KEY `portal_portalname_idx` (`portalname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_portal`
--

LOCK TABLES `vtiger_portal` WRITE;
/*!40000 ALTER TABLE `vtiger_portal` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_portal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_portalinfo`
--

DROP TABLE IF EXISTS `vtiger_portalinfo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_portalinfo` (
  `id` int NOT NULL,
  `user_name` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_password` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `type` varchar(5) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cryptmode` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `last_login_time` datetime DEFAULT NULL,
  `login_time` datetime DEFAULT NULL,
  `logout_time` datetime DEFAULT NULL,
  `isactive` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_1_vtiger_portalinfo` FOREIGN KEY (`id`) REFERENCES `vtiger_contactdetails` (`contactid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_portalinfo`
--

LOCK TABLES `vtiger_portalinfo` WRITE;
/*!40000 ALTER TABLE `vtiger_portalinfo` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_portalinfo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_poshipads`
--

DROP TABLE IF EXISTS `vtiger_poshipads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_poshipads` (
  `poshipaddressid` int NOT NULL,
  `ship_city` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ship_code` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ship_country` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ship_state` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ship_street` text COLLATE utf8mb4_general_ci,
  `ship_pobox` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`poshipaddressid`),
  CONSTRAINT `fk_1_vtiger_poshipads` FOREIGN KEY (`poshipaddressid`) REFERENCES `vtiger_purchaseorder` (`purchaseorderid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_poshipads`
--

LOCK TABLES `vtiger_poshipads` WRITE;
/*!40000 ALTER TABLE `vtiger_poshipads` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_poshipads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_postatus`
--

DROP TABLE IF EXISTS `vtiger_postatus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_postatus` (
  `postatusid` int NOT NULL AUTO_INCREMENT,
  `postatus` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT NULL,
  `color` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`postatusid`),
  UNIQUE KEY `postatus_postatus_idx` (`postatus`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_postatus`
--

LOCK TABLES `vtiger_postatus` WRITE;
/*!40000 ALTER TABLE `vtiger_postatus` DISABLE KEYS */;
INSERT INTO `vtiger_postatus` VALUES (1,'Created',0,130,0,NULL),(2,'Approved',0,131,1,NULL),(3,'Delivered',0,132,2,NULL),(4,'Cancelled',0,133,3,NULL),(5,'Received Shipment',0,134,4,NULL);
/*!40000 ALTER TABLE `vtiger_postatus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_postatus_seq`
--

DROP TABLE IF EXISTS `vtiger_postatus_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_postatus_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_postatus_seq`
--

LOCK TABLES `vtiger_postatus_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_postatus_seq` DISABLE KEYS */;
INSERT INTO `vtiger_postatus_seq` VALUES (5);
/*!40000 ALTER TABLE `vtiger_postatus_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_postatushistory`
--

DROP TABLE IF EXISTS `vtiger_postatushistory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_postatushistory` (
  `historyid` int NOT NULL AUTO_INCREMENT,
  `purchaseorderid` int NOT NULL,
  `vendorname` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `total` decimal(10,0) DEFAULT NULL,
  `postatus` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lastmodified` datetime DEFAULT NULL,
  PRIMARY KEY (`historyid`),
  KEY `postatushistory_purchaseorderid_idx` (`purchaseorderid`),
  CONSTRAINT `fk_1_vtiger_postatushistory` FOREIGN KEY (`purchaseorderid`) REFERENCES `vtiger_purchaseorder` (`purchaseorderid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_postatushistory`
--

LOCK TABLES `vtiger_postatushistory` WRITE;
/*!40000 ALTER TABLE `vtiger_postatushistory` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_postatushistory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_potential`
--

DROP TABLE IF EXISTS `vtiger_potential`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_potential` (
  `potentialid` int NOT NULL,
  `potential_no` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `related_to` int DEFAULT NULL,
  `potentialname` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `amount` decimal(25,8) DEFAULT NULL,
  `currency` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `closingdate` date DEFAULT NULL,
  `typeofrevenue` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nextstep` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `private` int DEFAULT '0',
  `probability` decimal(7,3) DEFAULT '0.000',
  `campaignid` int DEFAULT NULL,
  `sales_stage` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `potentialtype` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `leadsource` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `productid` int DEFAULT NULL,
  `productversion` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `quotationref` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `partnercontact` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `remarks` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `runtimefee` int DEFAULT '0',
  `followupdate` date DEFAULT NULL,
  `evaluationstatus` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `forecastcategory` int DEFAULT '0',
  `outcomeanalysis` int DEFAULT '0',
  `forecast_amount` decimal(25,8) DEFAULT NULL,
  `isconvertedfromlead` varchar(3) COLLATE utf8mb4_general_ci DEFAULT '0',
  `contact_id` int DEFAULT NULL,
  `tags` varchar(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `converted` int NOT NULL DEFAULT '0',
  `smcreatorid` int DEFAULT NULL,
  `smownerid` int DEFAULT NULL,
  `modifiedby` int DEFAULT NULL,
  `createdtime` datetime DEFAULT NULL,
  `modifiedtime` datetime DEFAULT NULL,
  `viewedtime` datetime DEFAULT NULL,
  `deleted` int DEFAULT NULL,
  `label` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `smgroupid` int DEFAULT NULL,
  `source` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `last_action_date` date DEFAULT NULL,
  PRIMARY KEY (`potentialid`),
  KEY `potential_relatedto_idx` (`related_to`),
  KEY `potentail_sales_stage_idx` (`sales_stage`),
  KEY `potentail_sales_stage_amount_idx` (`amount`,`sales_stage`),
  KEY `contact_id` (`contact_id`),
  KEY `idx_info` (`deleted`,`modifiedtime`),
  KEY `idx_label` (`deleted`,`label`),
  KEY `idx_owner` (`deleted`,`smownerid`),
  CONSTRAINT `fk_1_vtiger_potential` FOREIGN KEY (`potentialid`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_potential`
--

LOCK TABLES `vtiger_potential` WRITE;
/*!40000 ALTER TABLE `vtiger_potential` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_potential` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_potentialscf`
--

DROP TABLE IF EXISTS `vtiger_potentialscf`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_potentialscf` (
  `potentialid` int NOT NULL,
  PRIMARY KEY (`potentialid`),
  CONSTRAINT `fk_1_vtiger_potentialscf` FOREIGN KEY (`potentialid`) REFERENCES `vtiger_potential` (`potentialid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_potentialscf`
--

LOCK TABLES `vtiger_potentialscf` WRITE;
/*!40000 ALTER TABLE `vtiger_potentialscf` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_potentialscf` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_potstagehistory`
--

DROP TABLE IF EXISTS `vtiger_potstagehistory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_potstagehistory` (
  `historyid` int NOT NULL AUTO_INCREMENT,
  `potentialid` int NOT NULL,
  `amount` decimal(10,0) DEFAULT NULL,
  `stage` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `probability` decimal(7,3) DEFAULT NULL,
  `expectedrevenue` decimal(10,0) DEFAULT NULL,
  `closedate` date DEFAULT NULL,
  `lastmodified` datetime DEFAULT NULL,
  PRIMARY KEY (`historyid`),
  KEY `potstagehistory_potentialid_idx` (`potentialid`),
  CONSTRAINT `fk_1_vtiger_potstagehistory` FOREIGN KEY (`potentialid`) REFERENCES `vtiger_potential` (`potentialid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_potstagehistory`
--

LOCK TABLES `vtiger_potstagehistory` WRITE;
/*!40000 ALTER TABLE `vtiger_potstagehistory` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_potstagehistory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_pricebook`
--

DROP TABLE IF EXISTS `vtiger_pricebook`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_pricebook` (
  `pricebookid` int NOT NULL,
  `pricebook_no` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `bookname` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `active` int DEFAULT NULL,
  `currency_id` int NOT NULL DEFAULT '1',
  `tags` varchar(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `smcreatorid` int DEFAULT NULL,
  `smownerid` int DEFAULT NULL,
  `modifiedby` int DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_general_ci,
  `createdtime` datetime DEFAULT NULL,
  `modifiedtime` datetime DEFAULT NULL,
  `viewedtime` datetime DEFAULT NULL,
  `deleted` int DEFAULT NULL,
  `label` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `smgroupid` int DEFAULT NULL,
  `source` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`pricebookid`),
  KEY `idx_info` (`deleted`,`modifiedtime`),
  KEY `idx_label` (`deleted`,`label`),
  KEY `idx_owner` (`deleted`,`smownerid`),
  CONSTRAINT `fk_1_vtiger_pricebook` FOREIGN KEY (`pricebookid`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_pricebook`
--

LOCK TABLES `vtiger_pricebook` WRITE;
/*!40000 ALTER TABLE `vtiger_pricebook` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_pricebook` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_pricebookcf`
--

DROP TABLE IF EXISTS `vtiger_pricebookcf`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_pricebookcf` (
  `pricebookid` int NOT NULL,
  PRIMARY KEY (`pricebookid`),
  CONSTRAINT `fk_1_vtiger_pricebookcf` FOREIGN KEY (`pricebookid`) REFERENCES `vtiger_pricebook` (`pricebookid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_pricebookcf`
--

LOCK TABLES `vtiger_pricebookcf` WRITE;
/*!40000 ALTER TABLE `vtiger_pricebookcf` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_pricebookcf` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_pricebookproductrel`
--

DROP TABLE IF EXISTS `vtiger_pricebookproductrel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_pricebookproductrel` (
  `pricebookid` int NOT NULL,
  `productid` int NOT NULL,
  `listprice` decimal(27,8) DEFAULT NULL,
  `usedcurrency` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`pricebookid`,`productid`),
  KEY `pricebookproductrel_pricebookid_idx` (`pricebookid`),
  KEY `pricebookproductrel_productid_idx` (`productid`),
  CONSTRAINT `fk_1_vtiger_pricebookproductrel` FOREIGN KEY (`pricebookid`) REFERENCES `vtiger_pricebook` (`pricebookid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_pricebookproductrel`
--

LOCK TABLES `vtiger_pricebookproductrel` WRITE;
/*!40000 ALTER TABLE `vtiger_pricebookproductrel` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_pricebookproductrel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_priority`
--

DROP TABLE IF EXISTS `vtiger_priority`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_priority` (
  `priorityid` int NOT NULL AUTO_INCREMENT,
  `priority` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `sortorderid` int NOT NULL DEFAULT '0',
  `presence` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`priorityid`),
  UNIQUE KEY `priority_priority_idx` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_priority`
--

LOCK TABLES `vtiger_priority` WRITE;
/*!40000 ALTER TABLE `vtiger_priority` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_priority` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_productcategory`
--

DROP TABLE IF EXISTS `vtiger_productcategory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_productcategory` (
  `productcategoryid` int NOT NULL AUTO_INCREMENT,
  `productcategory` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT NULL,
  `color` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`productcategoryid`),
  UNIQUE KEY `productcategory_productcategory_idx` (`productcategory`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_productcategory`
--

LOCK TABLES `vtiger_productcategory` WRITE;
/*!40000 ALTER TABLE `vtiger_productcategory` DISABLE KEYS */;
INSERT INTO `vtiger_productcategory` VALUES (2,'Hardware',1,136,1,NULL),(3,'Software',1,137,2,NULL),(4,'CRM Applications',1,138,3,NULL);
/*!40000 ALTER TABLE `vtiger_productcategory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_productcategory_seq`
--

DROP TABLE IF EXISTS `vtiger_productcategory_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_productcategory_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_productcategory_seq`
--

LOCK TABLES `vtiger_productcategory_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_productcategory_seq` DISABLE KEYS */;
INSERT INTO `vtiger_productcategory_seq` VALUES (4);
/*!40000 ALTER TABLE `vtiger_productcategory_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_productcf`
--

DROP TABLE IF EXISTS `vtiger_productcf`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_productcf` (
  `productid` int NOT NULL,
  PRIMARY KEY (`productid`),
  CONSTRAINT `fk_1_vtiger_productcf` FOREIGN KEY (`productid`) REFERENCES `vtiger_products` (`productid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_productcf`
--

LOCK TABLES `vtiger_productcf` WRITE;
/*!40000 ALTER TABLE `vtiger_productcf` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_productcf` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_productcurrencyrel`
--

DROP TABLE IF EXISTS `vtiger_productcurrencyrel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_productcurrencyrel` (
  `productid` int NOT NULL,
  `currencyid` int NOT NULL,
  `converted_price` decimal(28,8) DEFAULT NULL,
  `actual_price` decimal(28,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_productcurrencyrel`
--

LOCK TABLES `vtiger_productcurrencyrel` WRITE;
/*!40000 ALTER TABLE `vtiger_productcurrencyrel` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_productcurrencyrel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_products`
--

DROP TABLE IF EXISTS `vtiger_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_products` (
  `productid` int NOT NULL,
  `product_no` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `productname` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `productcode` varchar(40) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `productcategory` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `manufacturer` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `qty_per_unit` decimal(11,2) DEFAULT '0.00',
  `unit_price` decimal(25,8) DEFAULT NULL,
  `weight` decimal(11,3) DEFAULT NULL,
  `pack_size` int DEFAULT NULL,
  `sales_start_date` date DEFAULT NULL,
  `sales_end_date` date DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `cost_factor` int DEFAULT NULL,
  `commissionrate` decimal(7,3) DEFAULT NULL,
  `commissionmethod` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `discontinued` int NOT NULL DEFAULT '0',
  `usageunit` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reorderlevel` int DEFAULT NULL,
  `website` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `taxclass` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mfr_part_no` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vendor_part_no` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `serialno` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `qtyinstock` decimal(25,3) DEFAULT NULL,
  `productsheet` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `qtyindemand` int DEFAULT NULL,
  `glacct` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vendor_id` int DEFAULT NULL,
  `imagename` text COLLATE utf8mb4_general_ci,
  `currency_id` int NOT NULL DEFAULT '1',
  `is_subproducts_viewable` int DEFAULT '1',
  `purchase_cost` decimal(27,8) DEFAULT NULL,
  `tags` varchar(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reducedtaxrate` int DEFAULT NULL,
  `smcreatorid` int DEFAULT NULL,
  `smownerid` int DEFAULT NULL,
  `modifiedby` int DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_general_ci,
  `createdtime` datetime DEFAULT NULL,
  `modifiedtime` datetime DEFAULT NULL,
  `viewedtime` datetime DEFAULT NULL,
  `deleted` int DEFAULT NULL,
  `label` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `smgroupid` int DEFAULT NULL,
  `source` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`productid`),
  KEY `idx_info` (`deleted`,`modifiedtime`),
  KEY `idx_label` (`deleted`,`label`),
  KEY `idx_owner` (`deleted`,`smownerid`),
  CONSTRAINT `fk_1_vtiger_products` FOREIGN KEY (`productid`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_products`
--

LOCK TABLES `vtiger_products` WRITE;
/*!40000 ALTER TABLE `vtiger_products` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_producttaxrel`
--

DROP TABLE IF EXISTS `vtiger_producttaxrel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_producttaxrel` (
  `productid` int NOT NULL,
  `taxid` int NOT NULL,
  `taxpercentage` decimal(7,3) DEFAULT NULL,
  `regions` text COLLATE utf8mb4_general_ci,
  KEY `producttaxrel_productid_idx` (`productid`),
  KEY `producttaxrel_taxid_idx` (`taxid`),
  CONSTRAINT `fk_crmid_vtiger_producttaxrel` FOREIGN KEY (`productid`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_producttaxrel`
--

LOCK TABLES `vtiger_producttaxrel` WRITE;
/*!40000 ALTER TABLE `vtiger_producttaxrel` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_producttaxrel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_profile`
--

DROP TABLE IF EXISTS `vtiger_profile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_profile` (
  `profileid` int NOT NULL AUTO_INCREMENT,
  `profilename` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `directly_related_to_role` int DEFAULT '0',
  PRIMARY KEY (`profileid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_profile`
--

LOCK TABLES `vtiger_profile` WRITE;
/*!40000 ALTER TABLE `vtiger_profile` DISABLE KEYS */;
INSERT INTO `vtiger_profile` VALUES (1,'Administrator','Admin Profile',0),(2,'Sales Profile','Profile Related to Sales',0),(3,'Support Profile','Profile Related to Support',0),(4,'Guest Profile','Guest Profile for Test Users',0);
/*!40000 ALTER TABLE `vtiger_profile` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_profile2field`
--

DROP TABLE IF EXISTS `vtiger_profile2field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_profile2field` (
  `profileid` int NOT NULL,
  `tabid` int DEFAULT NULL,
  `fieldid` int NOT NULL,
  `visible` int DEFAULT NULL,
  `readonly` int DEFAULT NULL,
  PRIMARY KEY (`profileid`,`fieldid`),
  KEY `profile2field_profileid_tabid_fieldname_idx` (`profileid`,`tabid`),
  KEY `profile2field_tabid_profileid_idx` (`tabid`,`profileid`),
  KEY `profile2field_visible_profileid_idx` (`visible`,`profileid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_profile2field`
--

LOCK TABLES `vtiger_profile2field` WRITE;
/*!40000 ALTER TABLE `vtiger_profile2field` DISABLE KEYS */;
INSERT INTO `vtiger_profile2field` VALUES (1,6,1,0,0),(1,6,2,0,0),(1,6,3,0,0),(1,6,4,0,0),(1,6,5,0,0),(1,6,6,0,0),(1,6,7,0,0),(1,6,8,0,0),(1,6,9,0,0),(1,6,10,0,0),(1,6,11,0,0),(1,6,12,0,0),(1,6,13,0,0),(1,6,14,0,0),(1,6,15,0,0),(1,6,16,0,0),(1,6,17,0,0),(1,6,18,0,0),(1,6,19,0,0),(1,6,20,0,0),(1,6,21,0,0),(1,6,22,0,0),(1,6,23,0,0),(1,6,24,0,0),(1,6,25,0,0),(1,6,26,0,0),(1,6,27,0,0),(1,6,28,0,0),(1,6,29,0,0),(1,6,30,0,0),(1,6,31,0,0),(1,6,32,0,0),(1,6,33,0,0),(1,6,34,0,0),(1,6,35,0,0),(1,6,36,0,0),(1,7,37,0,0),(1,7,38,0,0),(1,7,39,0,0),(1,7,40,0,0),(1,7,41,0,0),(1,7,42,0,0),(1,7,43,0,0),(1,7,44,0,0),(1,7,45,0,0),(1,7,46,0,0),(1,7,47,0,0),(1,7,48,0,0),(1,7,49,0,0),(1,7,50,0,0),(1,7,51,0,0),(1,7,52,0,0),(1,7,53,0,0),(1,7,54,0,0),(1,7,55,0,0),(1,7,56,0,0),(1,7,57,0,0),(1,7,58,0,0),(1,7,59,0,0),(1,7,60,0,0),(1,7,61,0,0),(1,7,62,0,0),(1,7,63,0,0),(1,7,64,0,0),(1,7,65,0,0),(1,4,66,0,0),(1,4,67,0,0),(1,4,68,0,0),(1,4,69,0,0),(1,4,70,0,0),(1,4,71,0,0),(1,4,72,0,0),(1,4,73,0,0),(1,4,74,0,0),(1,4,75,0,0),(1,4,76,0,0),(1,4,77,0,0),(1,4,78,0,0),(1,4,79,0,0),(1,4,80,0,0),(1,4,81,0,0),(1,4,82,0,0),(1,4,83,0,0),(1,4,84,0,0),(1,4,85,0,0),(1,4,86,0,0),(1,4,87,0,0),(1,4,88,0,0),(1,4,89,0,0),(1,4,90,0,0),(1,4,91,0,0),(1,4,92,0,0),(1,4,93,0,0),(1,4,94,0,0),(1,4,95,0,0),(1,4,96,0,0),(1,4,97,0,0),(1,4,98,0,0),(1,4,99,0,0),(1,4,100,0,0),(1,4,101,0,0),(1,4,102,0,0),(1,4,103,0,0),(1,4,104,0,0),(1,4,105,0,0),(1,4,106,0,0),(1,4,107,0,0),(1,4,108,0,0),(1,4,109,0,0),(1,2,110,0,0),(1,2,111,0,0),(1,2,112,0,0),(1,2,113,0,0),(1,2,114,0,0),(1,2,115,0,0),(1,2,116,0,0),(1,2,117,0,0),(1,2,118,0,0),(1,2,119,0,0),(1,2,120,0,0),(1,2,121,0,0),(1,2,122,0,0),(1,2,123,0,0),(1,2,124,0,0),(1,2,125,0,0),(1,26,126,0,0),(1,26,127,0,0),(1,26,128,0,0),(1,26,129,0,0),(1,26,130,0,0),(1,26,131,0,0),(1,26,132,0,0),(1,26,133,0,0),(1,26,134,0,0),(1,26,135,0,0),(1,26,136,0,0),(1,26,137,0,0),(1,26,138,0,0),(1,26,139,0,0),(1,26,140,0,0),(1,26,141,0,0),(1,26,142,0,0),(1,26,143,0,0),(1,26,144,0,0),(1,26,145,0,0),(1,26,146,0,0),(1,26,147,0,0),(1,26,148,0,0),(1,26,149,0,0),(1,26,150,0,0),(1,4,151,0,0),(1,6,152,0,0),(1,7,153,0,0),(1,26,154,0,0),(1,13,155,0,0),(1,13,156,0,0),(1,13,157,0,0),(1,13,158,0,0),(1,13,159,0,0),(1,13,160,0,0),(1,13,161,0,0),(1,13,162,0,0),(1,13,163,0,0),(1,13,164,0,0),(1,13,165,0,0),(1,13,166,0,0),(1,13,167,0,0),(1,13,168,0,0),(1,13,169,0,0),(1,13,170,0,0),(1,13,171,0,0),(1,13,172,0,0),(1,13,173,0,0),(1,14,174,0,0),(1,14,175,0,0),(1,14,176,0,0),(1,14,177,0,0),(1,14,178,0,0),(1,14,179,0,0),(1,14,180,0,0),(1,14,181,0,0),(1,14,182,0,0),(1,14,183,0,0),(1,14,184,0,0),(1,14,185,0,0),(1,14,186,0,0),(1,14,187,0,0),(1,14,188,0,0),(1,14,189,0,0),(1,14,190,0,0),(1,14,191,0,0),(1,14,192,0,0),(1,14,193,0,0),(1,14,194,0,0),(1,14,195,0,0),(1,14,196,0,0),(1,14,197,0,0),(1,14,198,0,0),(1,14,199,0,0),(1,14,200,0,0),(1,14,201,0,0),(1,14,202,0,0),(1,14,203,0,0),(1,14,204,0,0),(1,8,205,0,0),(1,8,206,0,0),(1,8,207,0,0),(1,8,208,0,0),(1,8,209,0,0),(1,8,210,0,0),(1,8,211,0,0),(1,8,212,0,0),(1,8,213,0,0),(1,8,214,0,0),(1,8,215,0,0),(1,8,216,0,0),(1,8,217,0,0),(1,8,218,0,0),(1,8,219,0,0),(1,10,220,0,0),(1,10,221,0,0),(1,10,222,0,0),(1,10,223,0,0),(1,10,224,0,0),(1,10,225,0,0),(1,10,226,0,0),(1,10,227,0,0),(1,10,228,0,0),(1,10,229,0,0),(1,10,230,0,0),(1,10,231,0,0),(1,9,232,0,0),(1,9,233,0,0),(1,9,234,0,0),(1,9,235,0,0),(1,9,236,0,0),(1,9,237,0,0),(1,9,238,0,0),(1,9,239,0,0),(1,9,240,0,0),(1,9,241,0,0),(1,9,242,0,0),(1,9,243,0,0),(1,9,244,0,0),(1,9,245,0,0),(1,9,246,0,0),(1,9,247,0,0),(1,9,248,0,0),(1,9,249,0,0),(1,9,250,0,0),(1,9,251,0,0),(1,9,252,0,0),(1,9,253,0,0),(1,9,254,0,0),(1,9,255,0,0),(1,16,256,0,0),(1,16,257,0,0),(1,16,258,0,0),(1,16,259,0,0),(1,16,260,0,0),(1,16,261,0,0),(1,16,262,0,0),(1,16,263,0,0),(1,16,264,0,0),(1,16,265,0,0),(1,16,266,0,0),(1,16,267,0,0),(1,16,268,0,0),(1,16,269,0,0),(1,16,270,0,0),(1,16,271,0,0),(1,16,272,0,0),(1,16,273,0,0),(1,16,274,0,0),(1,16,275,0,0),(1,16,276,0,0),(1,16,277,0,0),(1,16,278,0,0),(1,15,279,0,0),(1,15,280,0,0),(1,15,281,0,0),(1,15,282,0,0),(1,15,283,0,0),(1,15,284,0,0),(1,15,285,0,0),(1,15,286,0,0),(1,15,287,0,0),(1,15,288,0,0),(1,18,289,0,0),(1,18,290,0,0),(1,18,291,0,0),(1,18,292,0,0),(1,18,293,0,0),(1,18,294,0,0),(1,18,295,0,0),(1,18,296,0,0),(1,18,297,0,0),(1,18,298,0,0),(1,18,299,0,0),(1,18,300,0,0),(1,18,301,0,0),(1,18,302,0,0),(1,18,303,0,0),(1,18,304,0,0),(1,18,305,0,0),(1,19,306,0,0),(1,19,307,0,0),(1,19,308,0,0),(1,19,309,0,0),(1,19,310,0,0),(1,19,311,0,0),(1,19,312,0,0),(1,19,313,0,0),(1,20,314,0,0),(1,20,315,0,0),(1,20,316,0,0),(1,20,317,0,0),(1,20,318,0,0),(1,20,319,0,0),(1,20,320,0,0),(1,20,321,0,0),(1,20,322,0,0),(1,20,323,0,0),(1,20,324,0,0),(1,20,325,0,0),(1,20,326,0,0),(1,20,327,0,0),(1,20,328,0,0),(1,20,329,0,0),(1,20,330,0,0),(1,20,331,0,0),(1,20,332,0,0),(1,20,333,0,0),(1,20,334,0,0),(1,20,335,0,0),(1,20,336,0,0),(1,20,337,0,0),(1,20,338,0,0),(1,20,339,0,0),(1,20,340,0,0),(1,20,341,0,0),(1,20,342,0,0),(1,20,343,0,0),(1,20,344,0,0),(1,20,345,0,0),(1,20,346,0,0),(1,20,347,0,0),(1,20,348,0,0),(1,20,349,0,0),(1,20,350,0,0),(1,21,351,0,0),(1,21,352,0,0),(1,21,353,0,0),(1,21,354,0,0),(1,21,355,0,0),(1,21,356,0,0),(1,21,357,0,0),(1,21,358,0,0),(1,21,359,0,0),(1,21,360,0,0),(1,21,361,0,0),(1,21,362,0,0),(1,21,363,0,0),(1,21,364,0,0),(1,21,365,0,0),(1,21,366,0,0),(1,21,367,0,0),(1,21,368,0,0),(1,21,369,0,0),(1,21,370,0,0),(1,21,371,0,0),(1,21,372,0,0),(1,21,373,0,0),(1,21,374,0,0),(1,21,375,0,0),(1,21,376,0,0),(1,21,377,0,0),(1,21,378,0,0),(1,21,379,0,0),(1,21,380,0,0),(1,21,381,0,0),(1,21,382,0,0),(1,21,383,0,0),(1,21,384,0,0),(1,21,385,0,0),(1,21,386,0,0),(1,21,387,0,0),(1,21,388,0,0),(1,22,389,0,0),(1,22,390,0,0),(1,22,391,0,0),(1,22,392,0,0),(1,22,393,0,0),(1,22,394,0,0),(1,22,395,0,0),(1,22,396,0,0),(1,22,397,0,0),(1,22,398,0,0),(1,22,399,0,0),(1,22,400,0,0),(1,22,401,0,0),(1,22,402,0,0),(1,22,403,0,0),(1,22,404,0,0),(1,22,405,0,0),(1,22,406,0,0),(1,22,407,0,0),(1,22,408,0,0),(1,22,409,0,0),(1,22,410,0,0),(1,22,411,0,0),(1,22,412,0,0),(1,22,413,0,0),(1,22,414,0,0),(1,22,415,0,0),(1,22,416,0,0),(1,22,417,0,0),(1,22,418,0,0),(1,22,419,0,0),(1,22,420,0,0),(1,22,421,0,0),(1,22,422,0,0),(1,22,423,0,0),(1,22,424,0,0),(1,22,425,0,0),(1,22,426,0,0),(1,22,427,0,0),(1,22,428,0,0),(1,22,429,0,0),(1,22,430,0,0),(1,22,431,0,0),(1,22,432,0,0),(1,22,433,0,0),(1,22,434,0,0),(1,22,435,0,0),(1,23,436,0,0),(1,23,437,0,0),(1,23,438,0,0),(1,23,439,0,0),(1,23,440,0,0),(1,23,441,0,0),(1,23,442,0,0),(1,23,443,0,0),(1,23,444,0,0),(1,23,445,0,0),(1,23,446,0,0),(1,23,447,0,0),(1,23,448,0,0),(1,23,449,0,0),(1,23,450,0,0),(1,23,451,0,0),(1,23,452,0,0),(1,23,453,0,0),(1,23,454,0,0),(1,23,455,0,0),(1,23,456,0,0),(1,23,457,0,0),(1,23,458,0,0),(1,23,459,0,0),(1,23,460,0,0),(1,23,461,0,0),(1,23,462,0,0),(1,23,463,0,0),(1,23,464,0,0),(1,23,465,0,0),(1,23,466,0,0),(1,23,467,0,0),(1,23,468,0,0),(1,23,469,0,0),(1,23,470,0,0),(1,23,471,0,0),(1,23,472,0,0),(1,23,473,0,0),(1,23,474,0,0),(1,29,475,0,0),(1,29,479,0,0),(1,29,480,0,0),(1,29,482,0,0),(1,29,489,0,0),(1,29,490,0,0),(1,29,491,0,0),(1,29,492,0,0),(1,29,493,0,0),(1,29,495,0,0),(1,29,496,0,0),(1,29,497,0,0),(1,29,498,0,0),(1,29,499,0,0),(1,29,504,0,0),(1,29,505,0,0),(1,29,506,0,0),(1,29,507,0,0),(1,29,515,0,0),(1,10,520,0,0),(1,10,521,0,0),(1,10,522,0,0),(1,10,523,0,0),(1,10,524,0,0),(1,10,525,0,0),(1,34,526,0,0),(1,34,527,0,0),(1,34,528,0,0),(1,34,529,0,0),(1,34,530,0,0),(1,34,531,0,0),(1,34,532,0,0),(1,34,533,0,0),(1,34,534,0,0),(1,34,535,0,0),(1,34,536,0,0),(1,34,537,0,0),(1,34,538,0,0),(1,34,539,0,0),(1,34,540,0,0),(1,34,541,0,0),(1,34,542,0,0),(1,34,543,0,0),(1,34,544,0,0),(1,34,545,0,0),(1,34,546,0,0),(1,34,547,0,0),(1,35,548,0,0),(1,35,549,0,0),(1,35,550,0,0),(1,35,551,0,0),(1,35,552,0,0),(1,35,553,0,0),(1,35,554,0,0),(1,35,555,0,0),(1,35,556,0,0),(1,35,557,0,0),(1,35,558,0,0),(1,35,559,0,0),(1,35,560,0,0),(1,35,561,0,0),(1,35,562,0,0),(1,35,563,0,0),(1,35,564,0,0),(1,35,565,0,0),(1,35,566,0,0),(1,35,567,0,0),(1,35,568,0,0),(1,35,569,0,0),(1,37,570,0,0),(1,37,571,0,0),(1,37,572,0,0),(1,37,573,0,0),(1,37,574,0,0),(1,37,575,0,0),(1,37,576,0,0),(1,37,577,0,0),(1,39,578,0,0),(1,39,579,0,0),(1,39,580,0,0),(1,39,581,0,0),(1,39,582,0,0),(1,39,583,0,0),(1,39,584,0,0),(1,39,585,0,0),(1,39,586,0,0),(1,39,587,0,0),(1,39,588,0,0),(1,39,589,0,0),(1,39,590,0,0),(1,40,591,0,0),(1,40,592,0,0),(1,40,593,0,0),(1,40,594,0,0),(1,40,595,0,0),(1,40,596,0,0),(1,40,597,0,0),(1,40,598,0,0),(1,40,599,0,0),(1,40,600,0,0),(1,40,601,0,0),(1,40,602,0,0),(1,40,603,0,0),(1,40,604,0,0),(1,40,605,0,0),(1,40,606,0,0),(1,40,607,0,0),(1,40,608,0,0),(1,41,609,0,0),(1,41,610,0,0),(1,41,611,0,0),(1,41,612,0,0),(1,41,613,0,0),(1,41,614,0,0),(1,41,615,0,0),(1,41,616,0,0),(1,41,617,0,0),(1,41,618,0,0),(1,41,619,0,0),(1,41,620,0,0),(1,41,621,0,0),(1,41,622,0,0),(1,41,623,0,0),(1,41,624,0,0),(1,41,625,0,0),(1,41,626,0,0),(1,41,627,0,0),(1,41,628,0,0),(1,42,629,0,0),(1,42,630,0,0),(1,42,631,0,0),(1,42,632,0,0),(1,42,633,0,0),(1,42,634,0,0),(1,43,635,0,0),(1,43,636,0,0),(1,43,637,0,0),(1,43,638,0,0),(1,43,639,0,0),(1,43,640,0,0),(1,43,641,0,0),(1,43,642,0,0),(1,43,643,0,0),(1,43,644,0,0),(1,43,645,0,0),(1,43,646,0,0),(1,43,647,0,0),(1,43,648,0,0),(1,43,649,0,0),(1,43,650,0,0),(1,43,651,0,0),(1,43,652,0,0),(1,43,653,0,0),(1,43,654,0,0),(1,43,655,0,0),(1,2,656,0,0),(1,29,657,0,0),(1,23,658,0,0),(1,23,659,0,0),(1,23,660,0,0),(1,23,661,0,0),(1,23,662,0,0),(1,23,663,0,0),(1,23,664,0,0),(1,23,665,0,0),(1,23,666,0,0),(1,22,667,0,0),(1,22,668,0,0),(1,22,669,0,0),(1,22,670,0,0),(1,22,671,0,0),(1,22,672,0,0),(1,22,673,0,0),(1,22,674,0,0),(1,22,675,0,0),(1,21,676,0,0),(1,21,677,0,0),(1,21,678,0,0),(1,21,679,0,0),(1,21,680,0,0),(1,21,681,0,0),(1,21,682,0,0),(1,21,683,0,0),(1,21,684,0,0),(1,20,685,0,0),(1,20,686,0,0),(1,20,687,0,0),(1,20,688,0,0),(1,20,689,0,0),(1,20,690,0,0),(1,20,691,0,0),(1,20,692,0,0),(1,20,693,0,0),(1,29,694,0,0),(1,40,695,0,0),(1,37,696,0,0),(1,29,697,0,0),(1,29,698,0,0),(1,29,699,0,0),(1,23,700,0,0),(1,22,701,0,0),(1,21,702,0,0),(1,20,703,0,0),(1,29,704,0,0),(1,6,705,0,0),(1,4,706,0,0),(1,2,707,0,0),(1,29,708,0,0),(1,23,709,0,0),(1,23,710,0,0),(1,21,711,0,0),(1,21,712,0,0),(1,18,713,0,0),(1,7,714,0,0),(1,37,715,0,0),(1,37,716,0,0),(1,23,717,0,0),(1,20,718,0,0),(1,21,719,0,0),(1,22,720,0,0),(1,29,721,0,0),(1,2,722,0,0),(1,13,723,0,0),(1,29,724,0,0),(1,29,725,0,0),(1,29,726,0,0),(1,29,727,0,0),(1,14,753,0,0),(1,35,754,0,0),(1,23,755,0,0),(1,29,756,0,0),(1,23,757,0,0),(1,23,758,0,0),(1,23,759,0,0),(1,20,760,0,0),(1,20,761,0,0),(1,20,762,0,0),(1,21,763,0,0),(1,22,764,0,0),(1,22,765,0,0),(1,22,766,0,0),(1,37,767,0,0),(1,37,768,0,0),(1,37,769,0,0),(1,2,770,0,0),(1,4,771,0,0),(1,6,772,0,0),(1,7,773,0,0),(1,8,774,0,0),(1,9,775,0,0),(1,10,776,0,0),(1,13,777,0,0),(1,14,778,0,0),(1,15,779,0,0),(1,16,780,0,0),(1,18,781,0,0),(1,19,782,0,0),(1,20,783,0,0),(1,21,784,0,0),(1,22,785,0,0),(1,23,786,0,0),(1,26,787,0,0),(1,10,788,0,0),(1,2,789,0,0),(1,4,790,0,0),(1,6,791,0,0),(1,7,792,0,0),(1,8,793,0,0),(1,9,794,0,0),(1,10,795,0,0),(1,13,796,0,0),(1,14,797,0,0),(1,15,798,0,0),(1,16,799,0,0),(1,18,800,0,0),(1,19,801,0,0),(1,20,802,0,0),(1,21,803,0,0),(1,22,804,0,0),(1,23,805,0,0),(1,26,806,0,0),(1,2,807,0,0),(1,4,808,0,0),(1,6,809,0,0),(1,7,810,0,0),(1,8,811,0,0),(1,9,812,0,0),(1,10,813,0,0),(1,13,814,0,0),(1,14,815,0,0),(1,15,816,0,0),(1,16,817,0,0),(1,18,818,0,0),(1,19,819,0,0),(1,20,820,0,0),(1,21,821,0,0),(1,22,822,0,0),(1,23,823,0,0),(1,26,824,0,0),(1,20,825,0,0),(1,21,826,0,0),(1,22,827,0,0),(1,23,828,0,0),(1,41,829,0,0),(1,41,830,0,0),(1,29,831,0,0),(1,22,832,0,0),(1,49,833,0,0),(1,49,834,0,0),(1,49,835,0,0),(1,49,836,0,0),(1,49,837,0,0),(1,49,838,0,0),(1,49,839,0,0),(1,49,840,0,0),(1,49,841,0,0),(1,49,842,0,0),(1,20,843,0,0),(1,21,844,0,0),(1,22,845,0,0),(1,23,846,0,0),(1,14,847,0,0),(1,20,848,0,0),(1,21,849,0,0),(1,22,850,0,0),(1,23,851,0,0),(1,20,852,0,0),(1,21,853,0,0),(1,22,854,0,0),(1,23,855,0,0),(1,29,856,0,0),(1,16,857,0,0),(1,29,858,0,0),(1,16,859,0,0),(1,16,860,0,0),(1,2,861,0,0),(1,6,862,0,0),(1,4,863,0,0),(1,7,864,0,0),(1,23,865,0,0),(1,20,866,0,0),(1,21,867,0,0),(1,22,868,0,0),(2,6,1,0,0),(2,6,2,0,0),(2,6,3,0,0),(2,6,4,0,0),(2,6,5,0,0),(2,6,6,0,0),(2,6,7,0,0),(2,6,8,0,0),(2,6,9,0,0),(2,6,10,0,0),(2,6,11,0,0),(2,6,12,0,0),(2,6,13,0,0),(2,6,14,0,0),(2,6,15,0,0),(2,6,16,0,0),(2,6,17,0,0),(2,6,18,0,0),(2,6,19,0,0),(2,6,20,0,0),(2,6,21,0,0),(2,6,22,0,0),(2,6,23,0,0),(2,6,24,0,0),(2,6,25,0,0),(2,6,26,0,0),(2,6,27,0,0),(2,6,28,0,0),(2,6,29,0,0),(2,6,30,0,0),(2,6,31,0,0),(2,6,32,0,0),(2,6,33,0,0),(2,6,34,0,0),(2,6,35,0,0),(2,6,36,0,0),(2,7,37,0,0),(2,7,38,0,0),(2,7,39,0,0),(2,7,40,0,0),(2,7,41,0,0),(2,7,42,0,0),(2,7,43,0,0),(2,7,44,0,0),(2,7,45,0,0),(2,7,46,0,0),(2,7,47,0,0),(2,7,48,0,0),(2,7,49,0,0),(2,7,50,0,0),(2,7,51,0,0),(2,7,52,0,0),(2,7,53,0,0),(2,7,54,0,0),(2,7,55,0,0),(2,7,56,0,0),(2,7,57,0,0),(2,7,58,0,0),(2,7,59,0,0),(2,7,60,0,0),(2,7,61,0,0),(2,7,62,0,0),(2,7,63,0,0),(2,7,64,0,0),(2,7,65,0,0),(2,4,66,0,0),(2,4,67,0,0),(2,4,68,0,0),(2,4,69,0,0),(2,4,70,0,0),(2,4,71,0,0),(2,4,72,0,0),(2,4,73,0,0),(2,4,74,0,0),(2,4,75,0,0),(2,4,76,0,0),(2,4,77,0,0),(2,4,78,0,0),(2,4,79,0,0),(2,4,80,0,0),(2,4,81,0,0),(2,4,82,0,0),(2,4,83,0,0),(2,4,84,0,0),(2,4,85,0,0),(2,4,86,0,0),(2,4,87,0,0),(2,4,88,0,0),(2,4,89,0,0),(2,4,90,0,0),(2,4,91,0,0),(2,4,92,0,0),(2,4,93,0,0),(2,4,94,0,0),(2,4,95,0,0),(2,4,96,0,0),(2,4,97,0,0),(2,4,98,0,0),(2,4,99,0,0),(2,4,100,0,0),(2,4,101,0,0),(2,4,102,0,0),(2,4,103,0,0),(2,4,104,0,0),(2,4,105,0,0),(2,4,106,0,0),(2,4,107,0,0),(2,4,108,0,0),(2,4,109,0,0),(2,2,110,0,0),(2,2,111,0,0),(2,2,112,0,0),(2,2,113,0,0),(2,2,114,0,0),(2,2,115,0,0),(2,2,116,0,0),(2,2,117,0,0),(2,2,118,0,0),(2,2,119,0,0),(2,2,120,0,0),(2,2,121,0,0),(2,2,122,0,0),(2,2,123,0,0),(2,2,124,0,0),(2,2,125,0,0),(2,26,126,0,0),(2,26,127,0,0),(2,26,128,0,0),(2,26,129,0,0),(2,26,130,0,0),(2,26,131,0,0),(2,26,132,0,0),(2,26,133,0,0),(2,26,134,0,0),(2,26,135,0,0),(2,26,136,0,0),(2,26,137,0,0),(2,26,138,0,0),(2,26,139,0,0),(2,26,140,0,0),(2,26,141,0,0),(2,26,142,0,0),(2,26,143,0,0),(2,26,144,0,0),(2,26,145,0,0),(2,26,146,0,0),(2,26,147,0,0),(2,26,148,0,0),(2,26,149,0,0),(2,26,150,0,0),(2,4,151,0,0),(2,6,152,0,0),(2,7,153,0,0),(2,26,154,0,0),(2,13,155,0,0),(2,13,156,0,0),(2,13,157,0,0),(2,13,158,0,0),(2,13,159,0,0),(2,13,160,0,0),(2,13,161,0,0),(2,13,162,0,0),(2,13,163,0,0),(2,13,164,0,0),(2,13,165,0,0),(2,13,166,0,0),(2,13,167,0,0),(2,13,168,0,0),(2,13,169,0,0),(2,13,170,0,0),(2,13,171,0,0),(2,13,172,0,0),(2,13,173,0,0),(2,14,174,0,0),(2,14,175,0,0),(2,14,176,0,0),(2,14,177,0,0),(2,14,178,0,0),(2,14,179,0,0),(2,14,180,0,0),(2,14,181,0,0),(2,14,182,0,0),(2,14,183,0,0),(2,14,184,0,0),(2,14,185,0,0),(2,14,186,0,0),(2,14,187,0,0),(2,14,188,0,0),(2,14,189,0,0),(2,14,190,0,0),(2,14,191,0,0),(2,14,192,0,0),(2,14,193,0,0),(2,14,194,0,0),(2,14,195,0,0),(2,14,196,0,0),(2,14,197,0,0),(2,14,198,0,0),(2,14,199,0,0),(2,14,200,0,0),(2,14,201,0,0),(2,14,202,0,0),(2,14,203,0,0),(2,14,204,0,0),(2,8,205,0,0),(2,8,206,0,0),(2,8,207,0,0),(2,8,208,0,0),(2,8,209,0,0),(2,8,210,0,0),(2,8,211,0,0),(2,8,212,0,0),(2,8,213,0,0),(2,8,214,0,0),(2,8,215,0,0),(2,8,216,0,0),(2,8,217,0,0),(2,8,218,0,0),(2,8,219,0,0),(2,10,220,0,0),(2,10,221,0,0),(2,10,222,0,0),(2,10,223,0,0),(2,10,224,0,0),(2,10,225,0,0),(2,10,226,0,0),(2,10,227,0,0),(2,10,228,0,0),(2,10,229,0,0),(2,10,230,0,0),(2,10,231,0,0),(2,9,232,0,0),(2,9,233,0,0),(2,9,234,0,0),(2,9,235,0,0),(2,9,236,0,0),(2,9,237,0,0),(2,9,238,0,0),(2,9,239,0,0),(2,9,240,0,0),(2,9,241,0,0),(2,9,242,0,0),(2,9,243,0,0),(2,9,244,0,0),(2,9,245,0,0),(2,9,246,0,0),(2,9,247,0,0),(2,9,248,0,0),(2,9,249,0,0),(2,9,250,0,0),(2,9,251,0,0),(2,9,252,0,0),(2,9,253,0,0),(2,9,254,0,0),(2,9,255,0,0),(2,16,256,0,0),(2,16,257,0,0),(2,16,258,0,0),(2,16,259,0,0),(2,16,260,0,0),(2,16,261,0,0),(2,16,262,0,0),(2,16,263,0,0),(2,16,264,0,0),(2,16,265,0,0),(2,16,266,0,0),(2,16,267,0,0),(2,16,268,0,0),(2,16,269,0,0),(2,16,270,0,0),(2,16,271,0,0),(2,16,272,0,0),(2,16,273,0,0),(2,16,274,0,0),(2,16,275,0,0),(2,16,276,0,0),(2,16,277,0,0),(2,16,278,0,0),(2,15,279,0,0),(2,15,280,0,0),(2,15,281,0,0),(2,15,282,0,0),(2,15,283,0,0),(2,15,284,0,0),(2,15,285,0,0),(2,15,286,0,0),(2,15,287,0,0),(2,15,288,0,0),(2,18,289,0,0),(2,18,290,0,0),(2,18,291,0,0),(2,18,292,0,0),(2,18,293,0,0),(2,18,294,0,0),(2,18,295,0,0),(2,18,296,0,0),(2,18,297,0,0),(2,18,298,0,0),(2,18,299,0,0),(2,18,300,0,0),(2,18,301,0,0),(2,18,302,0,0),(2,18,303,0,0),(2,18,304,0,0),(2,18,305,0,0),(2,19,306,0,0),(2,19,307,0,0),(2,19,308,0,0),(2,19,309,0,0),(2,19,310,0,0),(2,19,311,0,0),(2,19,312,0,0),(2,19,313,0,0),(2,20,314,0,0),(2,20,315,0,0),(2,20,316,0,0),(2,20,317,0,0),(2,20,318,0,0),(2,20,319,0,0),(2,20,320,0,0),(2,20,321,0,0),(2,20,322,0,0),(2,20,323,0,0),(2,20,324,0,0),(2,20,325,0,0),(2,20,326,0,0),(2,20,327,0,0),(2,20,328,0,0),(2,20,329,0,0),(2,20,330,0,0),(2,20,331,0,0),(2,20,332,0,0),(2,20,333,0,0),(2,20,334,0,0),(2,20,335,0,0),(2,20,336,0,0),(2,20,337,0,0),(2,20,338,0,0),(2,20,339,0,0),(2,20,340,0,0),(2,20,341,0,0),(2,20,342,0,0),(2,20,343,0,0),(2,20,344,0,0),(2,20,345,0,0),(2,20,346,0,0),(2,20,347,0,0),(2,20,348,0,0),(2,20,349,0,0),(2,20,350,0,0),(2,21,351,0,0),(2,21,352,0,0),(2,21,353,0,0),(2,21,354,0,0),(2,21,355,0,0),(2,21,356,0,0),(2,21,357,0,0),(2,21,358,0,0),(2,21,359,0,0),(2,21,360,0,0),(2,21,361,0,0),(2,21,362,0,0),(2,21,363,0,0),(2,21,364,0,0),(2,21,365,0,0),(2,21,366,0,0),(2,21,367,0,0),(2,21,368,0,0),(2,21,369,0,0),(2,21,370,0,0),(2,21,371,0,0),(2,21,372,0,0),(2,21,373,0,0),(2,21,374,0,0),(2,21,375,0,0),(2,21,376,0,0),(2,21,377,0,0),(2,21,378,0,0),(2,21,379,0,0),(2,21,380,0,0),(2,21,381,0,0),(2,21,382,0,0),(2,21,383,0,0),(2,21,384,0,0),(2,21,385,0,0),(2,21,386,0,0),(2,21,387,0,0),(2,21,388,0,0),(2,22,389,0,0),(2,22,390,0,0),(2,22,391,0,0),(2,22,392,0,0),(2,22,393,0,0),(2,22,394,0,0),(2,22,395,0,0),(2,22,396,0,0),(2,22,397,0,0),(2,22,398,0,0),(2,22,399,0,0),(2,22,400,0,0),(2,22,401,0,0),(2,22,402,0,0),(2,22,403,0,0),(2,22,404,0,0),(2,22,405,0,0),(2,22,406,0,0),(2,22,407,0,0),(2,22,408,0,0),(2,22,409,0,0),(2,22,410,0,0),(2,22,411,0,0),(2,22,412,0,0),(2,22,413,0,0),(2,22,414,0,0),(2,22,415,0,0),(2,22,416,0,0),(2,22,417,0,0),(2,22,418,0,0),(2,22,419,0,0),(2,22,420,0,0),(2,22,421,0,0),(2,22,422,0,0),(2,22,423,0,0),(2,22,424,0,0),(2,22,425,0,0),(2,22,426,0,0),(2,22,427,0,0),(2,22,428,0,0),(2,22,429,0,0),(2,22,430,0,0),(2,22,431,0,0),(2,22,432,0,0),(2,22,433,0,0),(2,22,434,0,0),(2,22,435,0,0),(2,23,436,0,0),(2,23,437,0,0),(2,23,438,0,0),(2,23,439,0,0),(2,23,440,0,0),(2,23,441,0,0),(2,23,442,0,0),(2,23,443,0,0),(2,23,444,0,0),(2,23,445,0,0),(2,23,446,0,0),(2,23,447,0,0),(2,23,448,0,0),(2,23,449,0,0),(2,23,450,0,0),(2,23,451,0,0),(2,23,452,0,0),(2,23,453,0,0),(2,23,454,0,0),(2,23,455,0,0),(2,23,456,0,0),(2,23,457,0,0),(2,23,458,0,0),(2,23,459,0,0),(2,23,460,0,0),(2,23,461,0,0),(2,23,462,0,0),(2,23,463,0,0),(2,23,464,0,0),(2,23,465,0,0),(2,23,466,0,0),(2,23,467,0,0),(2,23,468,0,0),(2,23,469,0,0),(2,23,470,0,0),(2,23,471,0,0),(2,23,472,0,0),(2,23,473,0,0),(2,23,474,0,0),(2,29,475,0,0),(2,29,479,0,0),(2,29,480,0,0),(2,29,482,0,0),(2,29,489,0,0),(2,29,490,0,0),(2,29,491,0,0),(2,29,492,0,0),(2,29,493,0,0),(2,29,495,0,0),(2,29,496,0,0),(2,29,497,0,0),(2,29,498,0,0),(2,29,499,0,0),(2,29,504,0,0),(2,29,505,0,0),(2,29,506,0,0),(2,29,507,0,0),(2,29,515,0,0),(2,10,520,0,0),(2,10,521,0,0),(2,10,522,0,0),(2,10,523,0,0),(2,10,524,0,0),(2,10,525,0,0),(2,34,526,0,0),(2,34,527,0,0),(2,34,528,0,0),(2,34,529,0,0),(2,34,530,0,0),(2,34,531,0,0),(2,34,532,0,0),(2,34,533,0,0),(2,34,534,0,0),(2,34,535,0,0),(2,34,536,0,0),(2,34,537,0,0),(2,34,538,0,0),(2,34,539,0,0),(2,34,540,0,0),(2,34,541,0,0),(2,34,542,0,0),(2,34,543,0,0),(2,34,544,0,0),(2,34,545,0,0),(2,34,546,0,0),(2,34,547,0,0),(2,35,548,0,0),(2,35,549,0,0),(2,35,550,0,0),(2,35,551,0,0),(2,35,552,0,0),(2,35,553,0,0),(2,35,554,0,0),(2,35,555,0,0),(2,35,556,0,0),(2,35,557,0,0),(2,35,558,0,0),(2,35,559,0,0),(2,35,560,0,0),(2,35,561,0,0),(2,35,562,0,0),(2,35,563,0,0),(2,35,564,0,0),(2,35,565,0,0),(2,35,566,0,0),(2,35,567,0,0),(2,35,568,0,0),(2,35,569,0,0),(2,37,570,0,0),(2,37,571,0,0),(2,37,572,0,0),(2,37,573,0,0),(2,37,574,0,0),(2,37,575,0,0),(2,37,576,0,0),(2,37,577,0,0),(2,39,578,0,0),(2,39,579,0,0),(2,39,580,0,0),(2,39,581,0,0),(2,39,582,0,0),(2,39,583,0,0),(2,39,584,0,0),(2,39,585,0,0),(2,39,586,0,0),(2,39,587,0,0),(2,39,588,0,0),(2,39,589,0,0),(2,39,590,0,0),(2,40,591,0,0),(2,40,592,0,0),(2,40,593,0,0),(2,40,594,0,0),(2,40,595,0,0),(2,40,596,0,0),(2,40,597,0,0),(2,40,598,0,0),(2,40,599,0,0),(2,40,600,0,0),(2,40,601,0,0),(2,40,602,0,0),(2,40,603,0,0),(2,40,604,0,0),(2,40,605,0,0),(2,40,606,0,0),(2,40,607,0,0),(2,40,608,0,0),(2,41,609,0,0),(2,41,610,0,0),(2,41,611,0,0),(2,41,612,0,0),(2,41,613,0,0),(2,41,614,0,0),(2,41,615,0,0),(2,41,616,0,0),(2,41,617,0,0),(2,41,618,0,0),(2,41,619,0,0),(2,41,620,0,0),(2,41,621,0,0),(2,41,622,0,0),(2,41,623,0,0),(2,41,624,0,0),(2,41,625,0,0),(2,41,626,0,0),(2,41,627,0,0),(2,41,628,0,0),(2,42,629,0,0),(2,42,630,0,0),(2,42,631,0,0),(2,42,632,0,0),(2,42,633,0,0),(2,42,634,0,0),(2,43,635,0,0),(2,43,636,0,0),(2,43,637,0,0),(2,43,638,0,0),(2,43,639,0,0),(2,43,640,0,0),(2,43,641,0,0),(2,43,642,0,0),(2,43,643,0,0),(2,43,644,0,0),(2,43,645,0,0),(2,43,646,0,0),(2,43,647,0,0),(2,43,648,0,0),(2,43,649,0,0),(2,43,650,0,0),(2,43,651,0,0),(2,43,652,0,0),(2,43,653,0,0),(2,43,654,0,0),(2,43,655,0,0),(2,2,656,0,0),(2,29,657,0,0),(2,23,658,0,0),(2,23,659,0,0),(2,23,660,0,0),(2,23,661,0,0),(2,23,662,0,0),(2,23,663,0,0),(2,23,664,0,0),(2,23,665,0,0),(2,23,666,0,0),(2,22,667,0,0),(2,22,668,0,0),(2,22,669,0,0),(2,22,670,0,0),(2,22,671,0,0),(2,22,672,0,0),(2,22,673,0,0),(2,22,674,0,0),(2,22,675,0,0),(2,21,676,0,0),(2,21,677,0,0),(2,21,678,0,0),(2,21,679,0,0),(2,21,680,0,0),(2,21,681,0,0),(2,21,682,0,0),(2,21,683,0,0),(2,21,684,0,0),(2,20,685,0,0),(2,20,686,0,0),(2,20,687,0,0),(2,20,688,0,0),(2,20,689,0,0),(2,20,690,0,0),(2,20,691,0,0),(2,20,692,0,0),(2,20,693,0,0),(2,29,694,0,0),(2,40,695,0,0),(2,37,696,0,0),(2,29,697,0,0),(2,29,698,0,0),(2,29,699,0,0),(2,23,700,0,0),(2,22,701,0,0),(2,21,702,0,0),(2,20,703,0,0),(2,29,704,0,0),(2,6,705,0,0),(2,4,706,0,0),(2,2,707,0,0),(2,29,708,0,0),(2,23,709,0,0),(2,23,710,0,0),(2,21,711,0,0),(2,21,712,0,0),(2,18,713,0,0),(2,7,714,0,0),(2,37,715,0,0),(2,37,716,0,0),(2,23,717,0,0),(2,20,718,0,0),(2,21,719,0,0),(2,22,720,0,0),(2,29,721,0,0),(2,2,722,0,0),(2,13,723,0,0),(2,29,724,0,0),(2,29,725,0,0),(2,29,726,0,0),(2,29,727,0,0),(2,14,753,0,0),(2,35,754,0,0),(2,23,755,0,0),(2,29,756,0,0),(2,23,757,0,0),(2,23,758,0,0),(2,23,759,0,0),(2,20,760,0,0),(2,20,761,0,0),(2,20,762,0,0),(2,21,763,0,0),(2,22,764,0,0),(2,22,765,0,0),(2,22,766,0,0),(2,37,767,0,0),(2,37,768,0,0),(2,37,769,0,0),(2,2,770,0,0),(2,4,771,0,0),(2,6,772,0,0),(2,7,773,0,0),(2,8,774,0,0),(2,9,775,0,0),(2,10,776,0,0),(2,13,777,0,0),(2,14,778,0,0),(2,15,779,0,0),(2,16,780,0,0),(2,18,781,0,0),(2,19,782,0,0),(2,20,783,0,0),(2,21,784,0,0),(2,22,785,0,0),(2,23,786,0,0),(2,26,787,0,0),(2,10,788,0,0),(2,2,789,0,0),(2,4,790,0,0),(2,6,791,0,0),(2,7,792,0,0),(2,8,793,0,0),(2,9,794,0,0),(2,10,795,0,0),(2,13,796,0,0),(2,14,797,0,0),(2,15,798,0,0),(2,16,799,0,0),(2,18,800,0,0),(2,19,801,0,0),(2,20,802,0,0),(2,21,803,0,0),(2,22,804,0,0),(2,23,805,0,0),(2,26,806,0,0),(2,2,807,0,0),(2,4,808,0,0),(2,6,809,0,0),(2,7,810,0,0),(2,8,811,0,0),(2,9,812,0,0),(2,10,813,0,0),(2,13,814,0,0),(2,14,815,0,0),(2,15,816,0,0),(2,16,817,0,0),(2,18,818,0,0),(2,19,819,0,0),(2,20,820,0,0),(2,21,821,0,0),(2,22,822,0,0),(2,23,823,0,0),(2,26,824,0,0),(2,20,825,0,0),(2,21,826,0,0),(2,22,827,0,0),(2,23,828,0,0),(2,41,829,0,0),(2,41,830,0,0),(2,29,831,0,0),(2,22,832,0,0),(2,49,833,0,0),(2,49,834,0,0),(2,49,835,0,0),(2,49,836,0,0),(2,49,837,0,0),(2,49,838,0,0),(2,49,839,0,0),(2,49,840,0,0),(2,49,841,0,0),(2,49,842,0,0),(2,20,843,0,0),(2,21,844,0,0),(2,22,845,0,0),(2,23,846,0,0),(2,14,847,0,0),(2,20,848,0,0),(2,21,849,0,0),(2,22,850,0,0),(2,23,851,0,0),(2,20,852,0,0),(2,21,853,0,0),(2,22,854,0,0),(2,23,855,0,0),(2,29,856,0,0),(2,16,857,0,0),(2,29,858,0,0),(2,16,859,0,0),(2,16,860,0,0),(2,2,861,0,0),(2,6,862,0,0),(2,4,863,0,0),(2,7,864,0,0),(2,23,865,0,0),(2,20,866,0,0),(2,21,867,0,0),(2,22,868,0,0),(3,6,1,0,0),(3,6,2,0,0),(3,6,3,0,0),(3,6,4,0,0),(3,6,5,0,0),(3,6,6,0,0),(3,6,7,0,0),(3,6,8,0,0),(3,6,9,0,0),(3,6,10,0,0),(3,6,11,0,0),(3,6,12,0,0),(3,6,13,0,0),(3,6,14,0,0),(3,6,15,0,0),(3,6,16,0,0),(3,6,17,0,0),(3,6,18,0,0),(3,6,19,0,0),(3,6,20,0,0),(3,6,21,0,0),(3,6,22,0,0),(3,6,23,0,0),(3,6,24,0,0),(3,6,25,0,0),(3,6,26,0,0),(3,6,27,0,0),(3,6,28,0,0),(3,6,29,0,0),(3,6,30,0,0),(3,6,31,0,0),(3,6,32,0,0),(3,6,33,0,0),(3,6,34,0,0),(3,6,35,0,0),(3,6,36,0,0),(3,7,37,0,0),(3,7,38,0,0),(3,7,39,0,0),(3,7,40,0,0),(3,7,41,0,0),(3,7,42,0,0),(3,7,43,0,0),(3,7,44,0,0),(3,7,45,0,0),(3,7,46,0,0),(3,7,47,0,0),(3,7,48,0,0),(3,7,49,0,0),(3,7,50,0,0),(3,7,51,0,0),(3,7,52,0,0),(3,7,53,0,0),(3,7,54,0,0),(3,7,55,0,0),(3,7,56,0,0),(3,7,57,0,0),(3,7,58,0,0),(3,7,59,0,0),(3,7,60,0,0),(3,7,61,0,0),(3,7,62,0,0),(3,7,63,0,0),(3,7,64,0,0),(3,7,65,0,0),(3,4,66,0,0),(3,4,67,0,0),(3,4,68,0,0),(3,4,69,0,0),(3,4,70,0,0),(3,4,71,0,0),(3,4,72,0,0),(3,4,73,0,0),(3,4,74,0,0),(3,4,75,0,0),(3,4,76,0,0),(3,4,77,0,0),(3,4,78,0,0),(3,4,79,0,0),(3,4,80,0,0),(3,4,81,0,0),(3,4,82,0,0),(3,4,83,0,0),(3,4,84,0,0),(3,4,85,0,0),(3,4,86,0,0),(3,4,87,0,0),(3,4,88,0,0),(3,4,89,0,0),(3,4,90,0,0),(3,4,91,0,0),(3,4,92,0,0),(3,4,93,0,0),(3,4,94,0,0),(3,4,95,0,0),(3,4,96,0,0),(3,4,97,0,0),(3,4,98,0,0),(3,4,99,0,0),(3,4,100,0,0),(3,4,101,0,0),(3,4,102,0,0),(3,4,103,0,0),(3,4,104,0,0),(3,4,105,0,0),(3,4,106,0,0),(3,4,107,0,0),(3,4,108,0,0),(3,4,109,0,0),(3,2,110,0,0),(3,2,111,0,0),(3,2,112,0,0),(3,2,113,0,0),(3,2,114,0,0),(3,2,115,0,0),(3,2,116,0,0),(3,2,117,0,0),(3,2,118,0,0),(3,2,119,0,0),(3,2,120,0,0),(3,2,121,0,0),(3,2,122,0,0),(3,2,123,0,0),(3,2,124,0,0),(3,2,125,0,0),(3,26,126,0,0),(3,26,127,0,0),(3,26,128,0,0),(3,26,129,0,0),(3,26,130,0,0),(3,26,131,0,0),(3,26,132,0,0),(3,26,133,0,0),(3,26,134,0,0),(3,26,135,0,0),(3,26,136,0,0),(3,26,137,0,0),(3,26,138,0,0),(3,26,139,0,0),(3,26,140,0,0),(3,26,141,0,0),(3,26,142,0,0),(3,26,143,0,0),(3,26,144,0,0),(3,26,145,0,0),(3,26,146,0,0),(3,26,147,0,0),(3,26,148,0,0),(3,26,149,0,0),(3,26,150,0,0),(3,4,151,0,0),(3,6,152,0,0),(3,7,153,0,0),(3,26,154,0,0),(3,13,155,0,0),(3,13,156,0,0),(3,13,157,0,0),(3,13,158,0,0),(3,13,159,0,0),(3,13,160,0,0),(3,13,161,0,0),(3,13,162,0,0),(3,13,163,0,0),(3,13,164,0,0),(3,13,165,0,0),(3,13,166,0,0),(3,13,167,0,0),(3,13,168,0,0),(3,13,169,0,0),(3,13,170,0,0),(3,13,171,0,0),(3,13,172,0,0),(3,13,173,0,0),(3,14,174,0,0),(3,14,175,0,0),(3,14,176,0,0),(3,14,177,0,0),(3,14,178,0,0),(3,14,179,0,0),(3,14,180,0,0),(3,14,181,0,0),(3,14,182,0,0),(3,14,183,0,0),(3,14,184,0,0),(3,14,185,0,0),(3,14,186,0,0),(3,14,187,0,0),(3,14,188,0,0),(3,14,189,0,0),(3,14,190,0,0),(3,14,191,0,0),(3,14,192,0,0),(3,14,193,0,0),(3,14,194,0,0),(3,14,195,0,0),(3,14,196,0,0),(3,14,197,0,0),(3,14,198,0,0),(3,14,199,0,0),(3,14,200,0,0),(3,14,201,0,0),(3,14,202,0,0),(3,14,203,0,0),(3,14,204,0,0),(3,8,205,0,0),(3,8,206,0,0),(3,8,207,0,0),(3,8,208,0,0),(3,8,209,0,0),(3,8,210,0,0),(3,8,211,0,0),(3,8,212,0,0),(3,8,213,0,0),(3,8,214,0,0),(3,8,215,0,0),(3,8,216,0,0),(3,8,217,0,0),(3,8,218,0,0),(3,8,219,0,0),(3,10,220,0,0),(3,10,221,0,0),(3,10,222,0,0),(3,10,223,0,0),(3,10,224,0,0),(3,10,225,0,0),(3,10,226,0,0),(3,10,227,0,0),(3,10,228,0,0),(3,10,229,0,0),(3,10,230,0,0),(3,10,231,0,0),(3,9,232,0,0),(3,9,233,0,0),(3,9,234,0,0),(3,9,235,0,0),(3,9,236,0,0),(3,9,237,0,0),(3,9,238,0,0),(3,9,239,0,0),(3,9,240,0,0),(3,9,241,0,0),(3,9,242,0,0),(3,9,243,0,0),(3,9,244,0,0),(3,9,245,0,0),(3,9,246,0,0),(3,9,247,0,0),(3,9,248,0,0),(3,9,249,0,0),(3,9,250,0,0),(3,9,251,0,0),(3,9,252,0,0),(3,9,253,0,0),(3,9,254,0,0),(3,9,255,0,0),(3,16,256,0,0),(3,16,257,0,0),(3,16,258,0,0),(3,16,259,0,0),(3,16,260,0,0),(3,16,261,0,0),(3,16,262,0,0),(3,16,263,0,0),(3,16,264,0,0),(3,16,265,0,0),(3,16,266,0,0),(3,16,267,0,0),(3,16,268,0,0),(3,16,269,0,0),(3,16,270,0,0),(3,16,271,0,0),(3,16,272,0,0),(3,16,273,0,0),(3,16,274,0,0),(3,16,275,0,0),(3,16,276,0,0),(3,16,277,0,0),(3,16,278,0,0),(3,15,279,0,0),(3,15,280,0,0),(3,15,281,0,0),(3,15,282,0,0),(3,15,283,0,0),(3,15,284,0,0),(3,15,285,0,0),(3,15,286,0,0),(3,15,287,0,0),(3,15,288,0,0),(3,18,289,0,0),(3,18,290,0,0),(3,18,291,0,0),(3,18,292,0,0),(3,18,293,0,0),(3,18,294,0,0),(3,18,295,0,0),(3,18,296,0,0),(3,18,297,0,0),(3,18,298,0,0),(3,18,299,0,0),(3,18,300,0,0),(3,18,301,0,0),(3,18,302,0,0),(3,18,303,0,0),(3,18,304,0,0),(3,18,305,0,0),(3,19,306,0,0),(3,19,307,0,0),(3,19,308,0,0),(3,19,309,0,0),(3,19,310,0,0),(3,19,311,0,0),(3,19,312,0,0),(3,19,313,0,0),(3,20,314,0,0),(3,20,315,0,0),(3,20,316,0,0),(3,20,317,0,0),(3,20,318,0,0),(3,20,319,0,0),(3,20,320,0,0),(3,20,321,0,0),(3,20,322,0,0),(3,20,323,0,0),(3,20,324,0,0),(3,20,325,0,0),(3,20,326,0,0),(3,20,327,0,0),(3,20,328,0,0),(3,20,329,0,0),(3,20,330,0,0),(3,20,331,0,0),(3,20,332,0,0),(3,20,333,0,0),(3,20,334,0,0),(3,20,335,0,0),(3,20,336,0,0),(3,20,337,0,0),(3,20,338,0,0),(3,20,339,0,0),(3,20,340,0,0),(3,20,341,0,0),(3,20,342,0,0),(3,20,343,0,0),(3,20,344,0,0),(3,20,345,0,0),(3,20,346,0,0),(3,20,347,0,0),(3,20,348,0,0),(3,20,349,0,0),(3,20,350,0,0),(3,21,351,0,0),(3,21,352,0,0),(3,21,353,0,0),(3,21,354,0,0),(3,21,355,0,0),(3,21,356,0,0),(3,21,357,0,0),(3,21,358,0,0),(3,21,359,0,0),(3,21,360,0,0),(3,21,361,0,0),(3,21,362,0,0),(3,21,363,0,0),(3,21,364,0,0),(3,21,365,0,0),(3,21,366,0,0),(3,21,367,0,0),(3,21,368,0,0),(3,21,369,0,0),(3,21,370,0,0),(3,21,371,0,0),(3,21,372,0,0),(3,21,373,0,0),(3,21,374,0,0),(3,21,375,0,0),(3,21,376,0,0),(3,21,377,0,0),(3,21,378,0,0),(3,21,379,0,0),(3,21,380,0,0),(3,21,381,0,0),(3,21,382,0,0),(3,21,383,0,0),(3,21,384,0,0),(3,21,385,0,0),(3,21,386,0,0),(3,21,387,0,0),(3,21,388,0,0),(3,22,389,0,0),(3,22,390,0,0),(3,22,391,0,0),(3,22,392,0,0),(3,22,393,0,0),(3,22,394,0,0),(3,22,395,0,0),(3,22,396,0,0),(3,22,397,0,0),(3,22,398,0,0),(3,22,399,0,0),(3,22,400,0,0),(3,22,401,0,0),(3,22,402,0,0),(3,22,403,0,0),(3,22,404,0,0),(3,22,405,0,0),(3,22,406,0,0),(3,22,407,0,0),(3,22,408,0,0),(3,22,409,0,0),(3,22,410,0,0),(3,22,411,0,0),(3,22,412,0,0),(3,22,413,0,0),(3,22,414,0,0),(3,22,415,0,0),(3,22,416,0,0),(3,22,417,0,0),(3,22,418,0,0),(3,22,419,0,0),(3,22,420,0,0),(3,22,421,0,0),(3,22,422,0,0),(3,22,423,0,0),(3,22,424,0,0),(3,22,425,0,0),(3,22,426,0,0),(3,22,427,0,0),(3,22,428,0,0),(3,22,429,0,0),(3,22,430,0,0),(3,22,431,0,0),(3,22,432,0,0),(3,22,433,0,0),(3,22,434,0,0),(3,22,435,0,0),(3,23,436,0,0),(3,23,437,0,0),(3,23,438,0,0),(3,23,439,0,0),(3,23,440,0,0),(3,23,441,0,0),(3,23,442,0,0),(3,23,443,0,0),(3,23,444,0,0),(3,23,445,0,0),(3,23,446,0,0),(3,23,447,0,0),(3,23,448,0,0),(3,23,449,0,0),(3,23,450,0,0),(3,23,451,0,0),(3,23,452,0,0),(3,23,453,0,0),(3,23,454,0,0),(3,23,455,0,0),(3,23,456,0,0),(3,23,457,0,0),(3,23,458,0,0),(3,23,459,0,0),(3,23,460,0,0),(3,23,461,0,0),(3,23,462,0,0),(3,23,463,0,0),(3,23,464,0,0),(3,23,465,0,0),(3,23,466,0,0),(3,23,467,0,0),(3,23,468,0,0),(3,23,469,0,0),(3,23,470,0,0),(3,23,471,0,0),(3,23,472,0,0),(3,23,473,0,0),(3,23,474,0,0),(3,29,475,0,0),(3,29,479,0,0),(3,29,480,0,0),(3,29,482,0,0),(3,29,489,0,0),(3,29,490,0,0),(3,29,491,0,0),(3,29,492,0,0),(3,29,493,0,0),(3,29,495,0,0),(3,29,496,0,0),(3,29,497,0,0),(3,29,498,0,0),(3,29,499,0,0),(3,29,504,0,0),(3,29,505,0,0),(3,29,506,0,0),(3,29,507,0,0),(3,29,515,0,0),(3,10,520,0,0),(3,10,521,0,0),(3,10,522,0,0),(3,10,523,0,0),(3,10,524,0,0),(3,10,525,0,0),(3,34,526,0,0),(3,34,527,0,0),(3,34,528,0,0),(3,34,529,0,0),(3,34,530,0,0),(3,34,531,0,0),(3,34,532,0,0),(3,34,533,0,0),(3,34,534,0,0),(3,34,535,0,0),(3,34,536,0,0),(3,34,537,0,0),(3,34,538,0,0),(3,34,539,0,0),(3,34,540,0,0),(3,34,541,0,0),(3,34,542,0,0),(3,34,543,0,0),(3,34,544,0,0),(3,34,545,0,0),(3,34,546,0,0),(3,34,547,0,0),(3,35,548,0,0),(3,35,549,0,0),(3,35,550,0,0),(3,35,551,0,0),(3,35,552,0,0),(3,35,553,0,0),(3,35,554,0,0),(3,35,555,0,0),(3,35,556,0,0),(3,35,557,0,0),(3,35,558,0,0),(3,35,559,0,0),(3,35,560,0,0),(3,35,561,0,0),(3,35,562,0,0),(3,35,563,0,0),(3,35,564,0,0),(3,35,565,0,0),(3,35,566,0,0),(3,35,567,0,0),(3,35,568,0,0),(3,35,569,0,0),(3,37,570,0,0),(3,37,571,0,0),(3,37,572,0,0),(3,37,573,0,0),(3,37,574,0,0),(3,37,575,0,0),(3,37,576,0,0),(3,37,577,0,0),(3,39,578,0,0),(3,39,579,0,0),(3,39,580,0,0),(3,39,581,0,0),(3,39,582,0,0),(3,39,583,0,0),(3,39,584,0,0),(3,39,585,0,0),(3,39,586,0,0),(3,39,587,0,0),(3,39,588,0,0),(3,39,589,0,0),(3,39,590,0,0),(3,40,591,0,0),(3,40,592,0,0),(3,40,593,0,0),(3,40,594,0,0),(3,40,595,0,0),(3,40,596,0,0),(3,40,597,0,0),(3,40,598,0,0),(3,40,599,0,0),(3,40,600,0,0),(3,40,601,0,0),(3,40,602,0,0),(3,40,603,0,0),(3,40,604,0,0),(3,40,605,0,0),(3,40,606,0,0),(3,40,607,0,0),(3,40,608,0,0),(3,41,609,0,0),(3,41,610,0,0),(3,41,611,0,0),(3,41,612,0,0),(3,41,613,0,0),(3,41,614,0,0),(3,41,615,0,0),(3,41,616,0,0),(3,41,617,0,0),(3,41,618,0,0),(3,41,619,0,0),(3,41,620,0,0),(3,41,621,0,0),(3,41,622,0,0),(3,41,623,0,0),(3,41,624,0,0),(3,41,625,0,0),(3,41,626,0,0),(3,41,627,0,0),(3,41,628,0,0),(3,42,629,0,0),(3,42,630,0,0),(3,42,631,0,0),(3,42,632,0,0),(3,42,633,0,0),(3,42,634,0,0),(3,43,635,0,0),(3,43,636,0,0),(3,43,637,0,0),(3,43,638,0,0),(3,43,639,0,0),(3,43,640,0,0),(3,43,641,0,0),(3,43,642,0,0),(3,43,643,0,0),(3,43,644,0,0),(3,43,645,0,0),(3,43,646,0,0),(3,43,647,0,0),(3,43,648,0,0),(3,43,649,0,0),(3,43,650,0,0),(3,43,651,0,0),(3,43,652,0,0),(3,43,653,0,0),(3,43,654,0,0),(3,43,655,0,0),(3,2,656,0,0),(3,29,657,0,0),(3,23,658,0,0),(3,23,659,0,0),(3,23,660,0,0),(3,23,661,0,0),(3,23,662,0,0),(3,23,663,0,0),(3,23,664,0,0),(3,23,665,0,0),(3,23,666,0,0),(3,22,667,0,0),(3,22,668,0,0),(3,22,669,0,0),(3,22,670,0,0),(3,22,671,0,0),(3,22,672,0,0),(3,22,673,0,0),(3,22,674,0,0),(3,22,675,0,0),(3,21,676,0,0),(3,21,677,0,0),(3,21,678,0,0),(3,21,679,0,0),(3,21,680,0,0),(3,21,681,0,0),(3,21,682,0,0),(3,21,683,0,0),(3,21,684,0,0),(3,20,685,0,0),(3,20,686,0,0),(3,20,687,0,0),(3,20,688,0,0),(3,20,689,0,0),(3,20,690,0,0),(3,20,691,0,0),(3,20,692,0,0),(3,20,693,0,0),(3,29,694,0,0),(3,40,695,0,0),(3,37,696,0,0),(3,29,697,0,0),(3,29,698,0,0),(3,29,699,0,0),(3,23,700,0,0),(3,22,701,0,0),(3,21,702,0,0),(3,20,703,0,0),(3,29,704,0,0),(3,6,705,0,0),(3,4,706,0,0),(3,2,707,0,0),(3,29,708,0,0),(3,23,709,0,0),(3,23,710,0,0),(3,21,711,0,0),(3,21,712,0,0),(3,18,713,0,0),(3,7,714,0,0),(3,37,715,0,0),(3,37,716,0,0),(3,23,717,0,0),(3,20,718,0,0),(3,21,719,0,0),(3,22,720,0,0),(3,29,721,0,0),(3,2,722,0,0),(3,13,723,0,0),(3,29,724,0,0),(3,29,725,0,0),(3,29,726,0,0),(3,29,727,0,0),(3,14,753,0,0),(3,35,754,0,0),(3,23,755,0,0),(3,29,756,0,0),(3,23,757,0,0),(3,23,758,0,0),(3,23,759,0,0),(3,20,760,0,0),(3,20,761,0,0),(3,20,762,0,0),(3,21,763,0,0),(3,22,764,0,0),(3,22,765,0,0),(3,22,766,0,0),(3,37,767,0,0),(3,37,768,0,0),(3,37,769,0,0),(3,2,770,0,0),(3,4,771,0,0),(3,6,772,0,0),(3,7,773,0,0),(3,8,774,0,0),(3,9,775,0,0),(3,10,776,0,0),(3,13,777,0,0),(3,14,778,0,0),(3,15,779,0,0),(3,16,780,0,0),(3,18,781,0,0),(3,19,782,0,0),(3,20,783,0,0),(3,21,784,0,0),(3,22,785,0,0),(3,23,786,0,0),(3,26,787,0,0),(3,10,788,0,0),(3,2,789,0,0),(3,4,790,0,0),(3,6,791,0,0),(3,7,792,0,0),(3,8,793,0,0),(3,9,794,0,0),(3,10,795,0,0),(3,13,796,0,0),(3,14,797,0,0),(3,15,798,0,0),(3,16,799,0,0),(3,18,800,0,0),(3,19,801,0,0),(3,20,802,0,0),(3,21,803,0,0),(3,22,804,0,0),(3,23,805,0,0),(3,26,806,0,0),(3,2,807,0,0),(3,4,808,0,0),(3,6,809,0,0),(3,7,810,0,0),(3,8,811,0,0),(3,9,812,0,0),(3,10,813,0,0),(3,13,814,0,0),(3,14,815,0,0),(3,15,816,0,0),(3,16,817,0,0),(3,18,818,0,0),(3,19,819,0,0),(3,20,820,0,0),(3,21,821,0,0),(3,22,822,0,0),(3,23,823,0,0),(3,26,824,0,0),(3,20,825,0,0),(3,21,826,0,0),(3,22,827,0,0),(3,23,828,0,0),(3,41,829,0,0),(3,41,830,0,0),(3,29,831,0,0),(3,22,832,0,0),(3,49,833,0,0),(3,49,834,0,0),(3,49,835,0,0),(3,49,836,0,0),(3,49,837,0,0),(3,49,838,0,0),(3,49,839,0,0),(3,49,840,0,0),(3,49,841,0,0),(3,49,842,0,0),(3,20,843,0,0),(3,21,844,0,0),(3,22,845,0,0),(3,23,846,0,0),(3,14,847,0,0),(3,20,848,0,0),(3,21,849,0,0),(3,22,850,0,0),(3,23,851,0,0),(3,20,852,0,0),(3,21,853,0,0),(3,22,854,0,0),(3,23,855,0,0),(3,29,856,0,0),(3,16,857,0,0),(3,29,858,0,0),(3,16,859,0,0),(3,16,860,0,0),(3,2,861,0,0),(3,6,862,0,0),(3,4,863,0,0),(3,7,864,0,0),(3,23,865,0,0),(3,20,866,0,0),(3,21,867,0,0),(3,22,868,0,0),(4,6,1,0,0),(4,6,2,0,0),(4,6,3,0,0),(4,6,4,0,0),(4,6,5,0,0),(4,6,6,0,0),(4,6,7,0,0),(4,6,8,0,0),(4,6,9,0,0),(4,6,10,0,0),(4,6,11,0,0),(4,6,12,0,0),(4,6,13,0,0),(4,6,14,0,0),(4,6,15,0,0),(4,6,16,0,0),(4,6,17,0,0),(4,6,18,0,0),(4,6,19,0,0),(4,6,20,0,0),(4,6,21,0,0),(4,6,22,0,0),(4,6,23,0,0),(4,6,24,0,0),(4,6,25,0,0),(4,6,26,0,0),(4,6,27,0,0),(4,6,28,0,0),(4,6,29,0,0),(4,6,30,0,0),(4,6,31,0,0),(4,6,32,0,0),(4,6,33,0,0),(4,6,34,0,0),(4,6,35,0,0),(4,6,36,0,0),(4,7,37,0,0),(4,7,38,0,0),(4,7,39,0,0),(4,7,40,0,0),(4,7,41,0,0),(4,7,42,0,0),(4,7,43,0,0),(4,7,44,0,0),(4,7,45,0,0),(4,7,46,0,0),(4,7,47,0,0),(4,7,48,0,0),(4,7,49,0,0),(4,7,50,0,0),(4,7,51,0,0),(4,7,52,0,0),(4,7,53,0,0),(4,7,54,0,0),(4,7,55,0,0),(4,7,56,0,0),(4,7,57,0,0),(4,7,58,0,0),(4,7,59,0,0),(4,7,60,0,0),(4,7,61,0,0),(4,7,62,0,0),(4,7,63,0,0),(4,7,64,0,0),(4,7,65,0,0),(4,4,66,0,0),(4,4,67,0,0),(4,4,68,0,0),(4,4,69,0,0),(4,4,70,0,0),(4,4,71,0,0),(4,4,72,0,0),(4,4,73,0,0),(4,4,74,0,0),(4,4,75,0,0),(4,4,76,0,0),(4,4,77,0,0),(4,4,78,0,0),(4,4,79,0,0),(4,4,80,0,0),(4,4,81,0,0),(4,4,82,0,0),(4,4,83,0,0),(4,4,84,0,0),(4,4,85,0,0),(4,4,86,0,0),(4,4,87,0,0),(4,4,88,0,0),(4,4,89,0,0),(4,4,90,0,0),(4,4,91,0,0),(4,4,92,0,0),(4,4,93,0,0),(4,4,94,0,0),(4,4,95,0,0),(4,4,96,0,0),(4,4,97,0,0),(4,4,98,0,0),(4,4,99,0,0),(4,4,100,0,0),(4,4,101,0,0),(4,4,102,0,0),(4,4,103,0,0),(4,4,104,0,0),(4,4,105,0,0),(4,4,106,0,0),(4,4,107,0,0),(4,4,108,0,0),(4,4,109,0,0),(4,2,110,0,0),(4,2,111,0,0),(4,2,112,0,0),(4,2,113,0,0),(4,2,114,0,0),(4,2,115,0,0),(4,2,116,0,0),(4,2,117,0,0),(4,2,118,0,0),(4,2,119,0,0),(4,2,120,0,0),(4,2,121,0,0),(4,2,122,0,0),(4,2,123,0,0),(4,2,124,0,0),(4,2,125,0,0),(4,26,126,0,0),(4,26,127,0,0),(4,26,128,0,0),(4,26,129,0,0),(4,26,130,0,0),(4,26,131,0,0),(4,26,132,0,0),(4,26,133,0,0),(4,26,134,0,0),(4,26,135,0,0),(4,26,136,0,0),(4,26,137,0,0),(4,26,138,0,0),(4,26,139,0,0),(4,26,140,0,0),(4,26,141,0,0),(4,26,142,0,0),(4,26,143,0,0),(4,26,144,0,0),(4,26,145,0,0),(4,26,146,0,0),(4,26,147,0,0),(4,26,148,0,0),(4,26,149,0,0),(4,26,150,0,0),(4,4,151,0,0),(4,6,152,0,0),(4,7,153,0,0),(4,26,154,0,0),(4,13,155,0,0),(4,13,156,0,0),(4,13,157,0,0),(4,13,158,0,0),(4,13,159,0,0),(4,13,160,0,0),(4,13,161,0,0),(4,13,162,0,0),(4,13,163,0,0),(4,13,164,0,0),(4,13,165,0,0),(4,13,166,0,0),(4,13,167,0,0),(4,13,168,0,0),(4,13,169,0,0),(4,13,170,0,0),(4,13,171,0,0),(4,13,172,0,0),(4,13,173,0,0),(4,14,174,0,0),(4,14,175,0,0),(4,14,176,0,0),(4,14,177,0,0),(4,14,178,0,0),(4,14,179,0,0),(4,14,180,0,0),(4,14,181,0,0),(4,14,182,0,0),(4,14,183,0,0),(4,14,184,0,0),(4,14,185,0,0),(4,14,186,0,0),(4,14,187,0,0),(4,14,188,0,0),(4,14,189,0,0),(4,14,190,0,0),(4,14,191,0,0),(4,14,192,0,0),(4,14,193,0,0),(4,14,194,0,0),(4,14,195,0,0),(4,14,196,0,0),(4,14,197,0,0),(4,14,198,0,0),(4,14,199,0,0),(4,14,200,0,0),(4,14,201,0,0),(4,14,202,0,0),(4,14,203,0,0),(4,14,204,0,0),(4,8,205,0,0),(4,8,206,0,0),(4,8,207,0,0),(4,8,208,0,0),(4,8,209,0,0),(4,8,210,0,0),(4,8,211,0,0),(4,8,212,0,0),(4,8,213,0,0),(4,8,214,0,0),(4,8,215,0,0),(4,8,216,0,0),(4,8,217,0,0),(4,8,218,0,0),(4,8,219,0,0),(4,10,220,0,0),(4,10,221,0,0),(4,10,222,0,0),(4,10,223,0,0),(4,10,224,0,0),(4,10,225,0,0),(4,10,226,0,0),(4,10,227,0,0),(4,10,228,0,0),(4,10,229,0,0),(4,10,230,0,0),(4,10,231,0,0),(4,9,232,0,0),(4,9,233,0,0),(4,9,234,0,0),(4,9,235,0,0),(4,9,236,0,0),(4,9,237,0,0),(4,9,238,0,0),(4,9,239,0,0),(4,9,240,0,0),(4,9,241,0,0),(4,9,242,0,0),(4,9,243,0,0),(4,9,244,0,0),(4,9,245,0,0),(4,9,246,0,0),(4,9,247,0,0),(4,9,248,0,0),(4,9,249,0,0),(4,9,250,0,0),(4,9,251,0,0),(4,9,252,0,0),(4,9,253,0,0),(4,9,254,0,0),(4,9,255,0,0),(4,16,256,0,0),(4,16,257,0,0),(4,16,258,0,0),(4,16,259,0,0),(4,16,260,0,0),(4,16,261,0,0),(4,16,262,0,0),(4,16,263,0,0),(4,16,264,0,0),(4,16,265,0,0),(4,16,266,0,0),(4,16,267,0,0),(4,16,268,0,0),(4,16,269,0,0),(4,16,270,0,0),(4,16,271,0,0),(4,16,272,0,0),(4,16,273,0,0),(4,16,274,0,0),(4,16,275,0,0),(4,16,276,0,0),(4,16,277,0,0),(4,16,278,0,0),(4,15,279,0,0),(4,15,280,0,0),(4,15,281,0,0),(4,15,282,0,0),(4,15,283,0,0),(4,15,284,0,0),(4,15,285,0,0),(4,15,286,0,0),(4,15,287,0,0),(4,15,288,0,0),(4,18,289,0,0),(4,18,290,0,0),(4,18,291,0,0),(4,18,292,0,0),(4,18,293,0,0),(4,18,294,0,0),(4,18,295,0,0),(4,18,296,0,0),(4,18,297,0,0),(4,18,298,0,0),(4,18,299,0,0),(4,18,300,0,0),(4,18,301,0,0),(4,18,302,0,0),(4,18,303,0,0),(4,18,304,0,0),(4,18,305,0,0),(4,19,306,0,0),(4,19,307,0,0),(4,19,308,0,0),(4,19,309,0,0),(4,19,310,0,0),(4,19,311,0,0),(4,19,312,0,0),(4,19,313,0,0),(4,20,314,0,0),(4,20,315,0,0),(4,20,316,0,0),(4,20,317,0,0),(4,20,318,0,0),(4,20,319,0,0),(4,20,320,0,0),(4,20,321,0,0),(4,20,322,0,0),(4,20,323,0,0),(4,20,324,0,0),(4,20,325,0,0),(4,20,326,0,0),(4,20,327,0,0),(4,20,328,0,0),(4,20,329,0,0),(4,20,330,0,0),(4,20,331,0,0),(4,20,332,0,0),(4,20,333,0,0),(4,20,334,0,0),(4,20,335,0,0),(4,20,336,0,0),(4,20,337,0,0),(4,20,338,0,0),(4,20,339,0,0),(4,20,340,0,0),(4,20,341,0,0),(4,20,342,0,0),(4,20,343,0,0),(4,20,344,0,0),(4,20,345,0,0),(4,20,346,0,0),(4,20,347,0,0),(4,20,348,0,0),(4,20,349,0,0),(4,20,350,0,0),(4,21,351,0,0),(4,21,352,0,0),(4,21,353,0,0),(4,21,354,0,0),(4,21,355,0,0),(4,21,356,0,0),(4,21,357,0,0),(4,21,358,0,0),(4,21,359,0,0),(4,21,360,0,0),(4,21,361,0,0),(4,21,362,0,0),(4,21,363,0,0),(4,21,364,0,0),(4,21,365,0,0),(4,21,366,0,0),(4,21,367,0,0),(4,21,368,0,0),(4,21,369,0,0),(4,21,370,0,0),(4,21,371,0,0),(4,21,372,0,0),(4,21,373,0,0),(4,21,374,0,0),(4,21,375,0,0),(4,21,376,0,0),(4,21,377,0,0),(4,21,378,0,0),(4,21,379,0,0),(4,21,380,0,0),(4,21,381,0,0),(4,21,382,0,0),(4,21,383,0,0),(4,21,384,0,0),(4,21,385,0,0),(4,21,386,0,0),(4,21,387,0,0),(4,21,388,0,0),(4,22,389,0,0),(4,22,390,0,0),(4,22,391,0,0),(4,22,392,0,0),(4,22,393,0,0),(4,22,394,0,0),(4,22,395,0,0),(4,22,396,0,0),(4,22,397,0,0),(4,22,398,0,0),(4,22,399,0,0),(4,22,400,0,0),(4,22,401,0,0),(4,22,402,0,0),(4,22,403,0,0),(4,22,404,0,0),(4,22,405,0,0),(4,22,406,0,0),(4,22,407,0,0),(4,22,408,0,0),(4,22,409,0,0),(4,22,410,0,0),(4,22,411,0,0),(4,22,412,0,0),(4,22,413,0,0),(4,22,414,0,0),(4,22,415,0,0),(4,22,416,0,0),(4,22,417,0,0),(4,22,418,0,0),(4,22,419,0,0),(4,22,420,0,0),(4,22,421,0,0),(4,22,422,0,0),(4,22,423,0,0),(4,22,424,0,0),(4,22,425,0,0),(4,22,426,0,0),(4,22,427,0,0),(4,22,428,0,0),(4,22,429,0,0),(4,22,430,0,0),(4,22,431,0,0),(4,22,432,0,0),(4,22,433,0,0),(4,22,434,0,0),(4,22,435,0,0),(4,23,436,0,0),(4,23,437,0,0),(4,23,438,0,0),(4,23,439,0,0),(4,23,440,0,0),(4,23,441,0,0),(4,23,442,0,0),(4,23,443,0,0),(4,23,444,0,0),(4,23,445,0,0),(4,23,446,0,0),(4,23,447,0,0),(4,23,448,0,0),(4,23,449,0,0),(4,23,450,0,0),(4,23,451,0,0),(4,23,452,0,0),(4,23,453,0,0),(4,23,454,0,0),(4,23,455,0,0),(4,23,456,0,0),(4,23,457,0,0),(4,23,458,0,0),(4,23,459,0,0),(4,23,460,0,0),(4,23,461,0,0),(4,23,462,0,0),(4,23,463,0,0),(4,23,464,0,0),(4,23,465,0,0),(4,23,466,0,0),(4,23,467,0,0),(4,23,468,0,0),(4,23,469,0,0),(4,23,470,0,0),(4,23,471,0,0),(4,23,472,0,0),(4,23,473,0,0),(4,23,474,0,0),(4,29,475,0,0),(4,29,479,0,0),(4,29,480,0,0),(4,29,482,0,0),(4,29,489,0,0),(4,29,490,0,0),(4,29,491,0,0),(4,29,492,0,0),(4,29,493,0,0),(4,29,495,0,0),(4,29,496,0,0),(4,29,497,0,0),(4,29,498,0,0),(4,29,499,0,0),(4,29,504,0,0),(4,29,505,0,0),(4,29,506,0,0),(4,29,507,0,0),(4,29,515,0,0),(4,10,520,0,0),(4,10,521,0,0),(4,10,522,0,0),(4,10,523,0,0),(4,10,524,0,0),(4,10,525,0,0),(4,34,526,0,0),(4,34,527,0,0),(4,34,528,0,0),(4,34,529,0,0),(4,34,530,0,0),(4,34,531,0,0),(4,34,532,0,0),(4,34,533,0,0),(4,34,534,0,0),(4,34,535,0,0),(4,34,536,0,0),(4,34,537,0,0),(4,34,538,0,0),(4,34,539,0,0),(4,34,540,0,0),(4,34,541,0,0),(4,34,542,0,0),(4,34,543,0,0),(4,34,544,0,0),(4,34,545,0,0),(4,34,546,0,0),(4,34,547,0,0),(4,35,548,0,0),(4,35,549,0,0),(4,35,550,0,0),(4,35,551,0,0),(4,35,552,0,0),(4,35,553,0,0),(4,35,554,0,0),(4,35,555,0,0),(4,35,556,0,0),(4,35,557,0,0),(4,35,558,0,0),(4,35,559,0,0),(4,35,560,0,0),(4,35,561,0,0),(4,35,562,0,0),(4,35,563,0,0),(4,35,564,0,0),(4,35,565,0,0),(4,35,566,0,0),(4,35,567,0,0),(4,35,568,0,0),(4,35,569,0,0),(4,37,570,0,0),(4,37,571,0,0),(4,37,572,0,0),(4,37,573,0,0),(4,37,574,0,0),(4,37,575,0,0),(4,37,576,0,0),(4,37,577,0,0),(4,39,578,0,0),(4,39,579,0,0),(4,39,580,0,0),(4,39,581,0,0),(4,39,582,0,0),(4,39,583,0,0),(4,39,584,0,0),(4,39,585,0,0),(4,39,586,0,0),(4,39,587,0,0),(4,39,588,0,0),(4,39,589,0,0),(4,39,590,0,0),(4,40,591,0,0),(4,40,592,0,0),(4,40,593,0,0),(4,40,594,0,0),(4,40,595,0,0),(4,40,596,0,0),(4,40,597,0,0),(4,40,598,0,0),(4,40,599,0,0),(4,40,600,0,0),(4,40,601,0,0),(4,40,602,0,0),(4,40,603,0,0),(4,40,604,0,0),(4,40,605,0,0),(4,40,606,0,0),(4,40,607,0,0),(4,40,608,0,0),(4,41,609,0,0),(4,41,610,0,0),(4,41,611,0,0),(4,41,612,0,0),(4,41,613,0,0),(4,41,614,0,0),(4,41,615,0,0),(4,41,616,0,0),(4,41,617,0,0),(4,41,618,0,0),(4,41,619,0,0),(4,41,620,0,0),(4,41,621,0,0),(4,41,622,0,0),(4,41,623,0,0),(4,41,624,0,0),(4,41,625,0,0),(4,41,626,0,0),(4,41,627,0,0),(4,41,628,0,0),(4,42,629,0,0),(4,42,630,0,0),(4,42,631,0,0),(4,42,632,0,0),(4,42,633,0,0),(4,42,634,0,0),(4,43,635,0,0),(4,43,636,0,0),(4,43,637,0,0),(4,43,638,0,0),(4,43,639,0,0),(4,43,640,0,0),(4,43,641,0,0),(4,43,642,0,0),(4,43,643,0,0),(4,43,644,0,0),(4,43,645,0,0),(4,43,646,0,0),(4,43,647,0,0),(4,43,648,0,0),(4,43,649,0,0),(4,43,650,0,0),(4,43,651,0,0),(4,43,652,0,0),(4,43,653,0,0),(4,43,654,0,0),(4,43,655,0,0),(4,2,656,0,0),(4,29,657,0,0),(4,23,658,0,0),(4,23,659,0,0),(4,23,660,0,0),(4,23,661,0,0),(4,23,662,0,0),(4,23,663,0,0),(4,23,664,0,0),(4,23,665,0,0),(4,23,666,0,0),(4,22,667,0,0),(4,22,668,0,0),(4,22,669,0,0),(4,22,670,0,0),(4,22,671,0,0),(4,22,672,0,0),(4,22,673,0,0),(4,22,674,0,0),(4,22,675,0,0),(4,21,676,0,0),(4,21,677,0,0),(4,21,678,0,0),(4,21,679,0,0),(4,21,680,0,0),(4,21,681,0,0),(4,21,682,0,0),(4,21,683,0,0),(4,21,684,0,0),(4,20,685,0,0),(4,20,686,0,0),(4,20,687,0,0),(4,20,688,0,0),(4,20,689,0,0),(4,20,690,0,0),(4,20,691,0,0),(4,20,692,0,0),(4,20,693,0,0),(4,29,694,0,0),(4,40,695,0,0),(4,37,696,0,0),(4,29,697,0,0),(4,29,698,0,0),(4,29,699,0,0),(4,23,700,0,0),(4,22,701,0,0),(4,21,702,0,0),(4,20,703,0,0),(4,29,704,0,0),(4,6,705,0,0),(4,4,706,0,0),(4,2,707,0,0),(4,29,708,0,0),(4,23,709,0,0),(4,23,710,0,0),(4,21,711,0,0),(4,21,712,0,0),(4,18,713,0,0),(4,7,714,0,0),(4,37,715,0,0),(4,37,716,0,0),(4,23,717,0,0),(4,20,718,0,0),(4,21,719,0,0),(4,22,720,0,0),(4,29,721,0,0),(4,2,722,0,0),(4,13,723,0,0),(4,29,724,0,0),(4,29,725,0,0),(4,29,726,0,0),(4,29,727,0,0),(4,14,753,0,0),(4,35,754,0,0),(4,23,755,0,0),(4,29,756,0,0),(4,23,757,0,0),(4,23,758,0,0),(4,23,759,0,0),(4,20,760,0,0),(4,20,761,0,0),(4,20,762,0,0),(4,21,763,0,0),(4,22,764,0,0),(4,22,765,0,0),(4,22,766,0,0),(4,37,767,0,0),(4,37,768,0,0),(4,37,769,0,0),(4,2,770,0,0),(4,4,771,0,0),(4,6,772,0,0),(4,7,773,0,0),(4,8,774,0,0),(4,9,775,0,0),(4,10,776,0,0),(4,13,777,0,0),(4,14,778,0,0),(4,15,779,0,0),(4,16,780,0,0),(4,18,781,0,0),(4,19,782,0,0),(4,20,783,0,0),(4,21,784,0,0),(4,22,785,0,0),(4,23,786,0,0),(4,26,787,0,0),(4,10,788,0,0),(4,2,789,0,0),(4,4,790,0,0),(4,6,791,0,0),(4,7,792,0,0),(4,8,793,0,0),(4,9,794,0,0),(4,10,795,0,0),(4,13,796,0,0),(4,14,797,0,0),(4,15,798,0,0),(4,16,799,0,0),(4,18,800,0,0),(4,19,801,0,0),(4,20,802,0,0),(4,21,803,0,0),(4,22,804,0,0),(4,23,805,0,0),(4,26,806,0,0),(4,2,807,0,0),(4,4,808,0,0),(4,6,809,0,0),(4,7,810,0,0),(4,8,811,0,0),(4,9,812,0,0),(4,10,813,0,0),(4,13,814,0,0),(4,14,815,0,0),(4,15,816,0,0),(4,16,817,0,0),(4,18,818,0,0),(4,19,819,0,0),(4,20,820,0,0),(4,21,821,0,0),(4,22,822,0,0),(4,23,823,0,0),(4,26,824,0,0),(4,20,825,0,0),(4,21,826,0,0),(4,22,827,0,0),(4,23,828,0,0),(4,41,829,0,0),(4,41,830,0,0),(4,29,831,0,0),(4,22,832,0,0),(4,49,833,0,0),(4,49,834,0,0),(4,49,835,0,0),(4,49,836,0,0),(4,49,837,0,0),(4,49,838,0,0),(4,49,839,0,0),(4,49,840,0,0),(4,49,841,0,0),(4,49,842,0,0),(4,20,843,0,0),(4,21,844,0,0),(4,22,845,0,0),(4,23,846,0,0),(4,14,847,0,0),(4,20,848,0,0),(4,21,849,0,0),(4,22,850,0,0),(4,23,851,0,0),(4,20,852,0,0),(4,21,853,0,0),(4,22,854,0,0),(4,23,855,0,0),(4,29,856,0,0),(4,16,857,0,0),(4,29,858,0,0),(4,16,859,0,0),(4,16,860,0,0),(4,2,861,0,0),(4,6,862,0,0),(4,4,863,0,0),(4,7,864,0,0),(4,23,865,0,0),(4,20,866,0,0),(4,21,867,0,0),(4,22,868,0,0);
/*!40000 ALTER TABLE `vtiger_profile2field` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_profile2globalpermissions`
--

DROP TABLE IF EXISTS `vtiger_profile2globalpermissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_profile2globalpermissions` (
  `profileid` int NOT NULL,
  `globalactionid` int NOT NULL,
  `globalactionpermission` int DEFAULT NULL,
  PRIMARY KEY (`profileid`,`globalactionid`),
  KEY `idx_profile2globalpermissions` (`profileid`,`globalactionid`),
  CONSTRAINT `fk_1_vtiger_profile2globalpermissions` FOREIGN KEY (`profileid`) REFERENCES `vtiger_profile` (`profileid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_profile2globalpermissions`
--

LOCK TABLES `vtiger_profile2globalpermissions` WRITE;
/*!40000 ALTER TABLE `vtiger_profile2globalpermissions` DISABLE KEYS */;
INSERT INTO `vtiger_profile2globalpermissions` VALUES (1,1,0),(1,2,0),(2,1,1),(2,2,1),(3,1,1),(3,2,1),(4,1,1),(4,2,1);
/*!40000 ALTER TABLE `vtiger_profile2globalpermissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_profile2standardpermissions`
--

DROP TABLE IF EXISTS `vtiger_profile2standardpermissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_profile2standardpermissions` (
  `profileid` int NOT NULL,
  `tabid` int NOT NULL,
  `operation` int NOT NULL,
  `permissions` int DEFAULT NULL,
  PRIMARY KEY (`profileid`,`tabid`,`operation`),
  KEY `profile2standardpermissions_profileid_tabid_operation_idx` (`profileid`,`tabid`,`operation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_profile2standardpermissions`
--

LOCK TABLES `vtiger_profile2standardpermissions` WRITE;
/*!40000 ALTER TABLE `vtiger_profile2standardpermissions` DISABLE KEYS */;
INSERT INTO `vtiger_profile2standardpermissions` VALUES (1,2,0,0),(1,2,1,0),(1,2,2,0),(1,2,3,0),(1,2,4,0),(1,2,7,0),(1,4,0,0),(1,4,1,0),(1,4,2,0),(1,4,3,0),(1,4,4,0),(1,4,7,0),(1,6,0,0),(1,6,1,0),(1,6,2,0),(1,6,3,0),(1,6,4,0),(1,6,7,0),(1,7,0,0),(1,7,1,0),(1,7,2,0),(1,7,3,0),(1,7,4,0),(1,7,7,0),(1,8,0,0),(1,8,1,0),(1,8,2,0),(1,8,3,0),(1,8,4,0),(1,8,7,0),(1,9,0,0),(1,9,1,0),(1,9,2,0),(1,9,3,0),(1,9,4,0),(1,9,7,0),(1,10,0,0),(1,10,1,0),(1,10,2,0),(1,10,4,0),(1,13,0,0),(1,13,1,0),(1,13,2,0),(1,13,3,0),(1,13,4,0),(1,13,7,0),(1,14,0,0),(1,14,1,0),(1,14,2,0),(1,14,3,0),(1,14,4,0),(1,14,7,0),(1,15,0,0),(1,15,1,0),(1,15,2,0),(1,15,3,0),(1,15,4,0),(1,15,7,0),(1,16,0,0),(1,16,1,0),(1,16,2,0),(1,16,3,0),(1,16,4,0),(1,16,7,0),(1,18,0,0),(1,18,1,0),(1,18,2,0),(1,18,3,0),(1,18,4,0),(1,18,7,0),(1,19,0,0),(1,19,1,0),(1,19,2,0),(1,19,3,0),(1,19,4,0),(1,19,7,0),(1,20,0,0),(1,20,1,0),(1,20,2,0),(1,20,3,0),(1,20,4,0),(1,20,7,0),(1,21,0,0),(1,21,1,0),(1,21,2,0),(1,21,3,0),(1,21,4,0),(1,21,7,0),(1,22,0,0),(1,22,1,0),(1,22,2,0),(1,22,3,0),(1,22,4,0),(1,22,7,0),(1,23,0,0),(1,23,1,0),(1,23,2,0),(1,23,3,0),(1,23,4,0),(1,23,7,0),(1,25,0,1),(1,25,1,0),(1,25,2,0),(1,25,3,0),(1,25,4,0),(1,25,7,0),(1,26,0,0),(1,26,1,0),(1,26,2,0),(1,26,3,0),(1,26,4,0),(1,26,7,0),(1,34,0,0),(1,34,1,0),(1,34,2,0),(1,34,3,0),(1,34,4,0),(1,34,7,0),(1,35,0,0),(1,35,1,0),(1,35,2,0),(1,35,3,0),(1,35,4,0),(1,35,7,0),(1,37,0,0),(1,37,1,0),(1,37,2,0),(1,37,3,0),(1,37,4,0),(1,37,7,0),(1,39,0,0),(1,39,1,0),(1,39,2,0),(1,39,3,0),(1,39,4,0),(1,39,7,0),(1,40,0,0),(1,40,1,0),(1,40,2,0),(1,40,3,0),(1,40,4,0),(1,40,7,0),(1,41,0,0),(1,41,1,0),(1,41,2,0),(1,41,3,0),(1,41,4,0),(1,41,7,0),(1,42,0,0),(1,42,1,0),(1,42,2,0),(1,42,3,0),(1,42,4,0),(1,42,7,0),(1,43,0,0),(1,43,1,0),(1,43,2,0),(1,43,3,0),(1,43,4,0),(1,43,7,0),(1,49,0,0),(1,49,1,0),(1,49,2,0),(1,49,3,0),(1,49,4,0),(1,49,7,0),(2,2,0,0),(2,2,1,0),(2,2,2,0),(2,2,3,0),(2,2,4,0),(2,2,7,0),(2,4,0,0),(2,4,1,0),(2,4,2,0),(2,4,3,0),(2,4,4,0),(2,4,7,0),(2,6,0,0),(2,6,1,0),(2,6,2,0),(2,6,3,0),(2,6,4,0),(2,6,7,0),(2,7,0,0),(2,7,1,0),(2,7,2,0),(2,7,3,0),(2,7,4,0),(2,7,7,0),(2,8,0,0),(2,8,1,0),(2,8,2,0),(2,8,3,0),(2,8,4,0),(2,8,7,0),(2,9,0,0),(2,9,1,0),(2,9,2,0),(2,9,3,0),(2,9,4,0),(2,9,7,0),(2,10,0,0),(2,10,1,0),(2,10,2,0),(2,10,4,0),(2,13,0,1),(2,13,1,1),(2,13,2,1),(2,13,3,0),(2,13,4,0),(2,13,7,1),(2,14,0,0),(2,14,1,0),(2,14,2,0),(2,14,3,0),(2,14,4,0),(2,14,7,0),(2,15,0,0),(2,15,1,0),(2,15,2,0),(2,15,3,0),(2,15,4,0),(2,15,7,0),(2,16,0,0),(2,16,1,0),(2,16,2,0),(2,16,3,0),(2,16,4,0),(2,16,7,0),(2,18,0,0),(2,18,1,0),(2,18,2,0),(2,18,3,0),(2,18,4,0),(2,18,7,0),(2,19,0,0),(2,19,1,0),(2,19,2,0),(2,19,3,0),(2,19,4,0),(2,19,7,0),(2,20,0,0),(2,20,1,0),(2,20,2,0),(2,20,3,0),(2,20,4,0),(2,20,7,0),(2,21,0,0),(2,21,1,0),(2,21,2,0),(2,21,3,0),(2,21,4,0),(2,21,7,0),(2,22,0,0),(2,22,1,0),(2,22,2,0),(2,22,3,0),(2,22,4,0),(2,22,7,0),(2,23,0,0),(2,23,1,0),(2,23,2,0),(2,23,3,0),(2,23,4,0),(2,23,7,0),(2,25,0,1),(2,25,1,0),(2,25,2,0),(2,25,3,0),(2,25,4,0),(2,25,7,0),(2,26,0,0),(2,26,1,0),(2,26,2,0),(2,26,3,0),(2,26,4,0),(2,26,7,0),(2,34,0,0),(2,34,1,0),(2,34,2,0),(2,34,3,0),(2,34,4,0),(2,34,7,0),(2,35,0,0),(2,35,1,0),(2,35,2,0),(2,35,3,0),(2,35,4,0),(2,35,7,0),(2,37,0,0),(2,37,1,0),(2,37,2,0),(2,37,3,0),(2,37,4,0),(2,37,7,0),(2,39,0,0),(2,39,1,0),(2,39,2,0),(2,39,3,0),(2,39,4,0),(2,39,7,0),(2,40,0,0),(2,40,1,0),(2,40,2,0),(2,40,3,0),(2,40,4,0),(2,40,7,0),(2,41,0,0),(2,41,1,0),(2,41,2,0),(2,41,3,0),(2,41,4,0),(2,41,7,0),(2,42,0,0),(2,42,1,0),(2,42,2,0),(2,42,3,0),(2,42,4,0),(2,42,7,0),(2,43,0,0),(2,43,1,0),(2,43,2,0),(2,43,3,0),(2,43,4,0),(2,43,7,0),(2,49,0,0),(2,49,1,0),(2,49,2,0),(2,49,3,0),(2,49,4,0),(2,49,7,0),(3,2,0,1),(3,2,1,1),(3,2,2,1),(3,2,3,0),(3,2,4,0),(3,2,7,1),(3,4,0,0),(3,4,1,0),(3,4,2,0),(3,4,3,0),(3,4,4,0),(3,4,7,0),(3,6,0,0),(3,6,1,0),(3,6,2,0),(3,6,3,0),(3,6,4,0),(3,6,7,0),(3,7,0,0),(3,7,1,0),(3,7,2,0),(3,7,3,0),(3,7,4,0),(3,7,7,0),(3,8,0,0),(3,8,1,0),(3,8,2,0),(3,8,3,0),(3,8,4,0),(3,8,7,0),(3,9,0,0),(3,9,1,0),(3,9,2,0),(3,9,3,0),(3,9,4,0),(3,9,7,0),(3,10,0,0),(3,10,1,0),(3,10,2,0),(3,10,4,0),(3,13,0,0),(3,13,1,0),(3,13,2,0),(3,13,3,0),(3,13,4,0),(3,13,7,0),(3,14,0,0),(3,14,1,0),(3,14,2,0),(3,14,3,0),(3,14,4,0),(3,14,7,0),(3,15,0,0),(3,15,1,0),(3,15,2,0),(3,15,3,0),(3,15,4,0),(3,15,7,0),(3,16,0,0),(3,16,1,0),(3,16,2,0),(3,16,3,0),(3,16,4,0),(3,16,7,0),(3,18,0,0),(3,18,1,0),(3,18,2,0),(3,18,3,0),(3,18,4,0),(3,18,7,0),(3,19,0,0),(3,19,1,0),(3,19,2,0),(3,19,3,0),(3,19,4,0),(3,19,7,0),(3,20,0,0),(3,20,1,0),(3,20,2,0),(3,20,3,0),(3,20,4,0),(3,20,7,0),(3,21,0,0),(3,21,1,0),(3,21,2,0),(3,21,3,0),(3,21,4,0),(3,21,7,0),(3,22,0,0),(3,22,1,0),(3,22,2,0),(3,22,3,0),(3,22,4,0),(3,22,7,0),(3,23,0,0),(3,23,1,0),(3,23,2,0),(3,23,3,0),(3,23,4,0),(3,23,7,0),(3,25,0,1),(3,25,1,0),(3,25,2,0),(3,25,3,0),(3,25,4,0),(3,25,7,0),(3,26,0,0),(3,26,1,0),(3,26,2,0),(3,26,3,0),(3,26,4,0),(3,26,7,0),(3,34,0,0),(3,34,1,0),(3,34,2,0),(3,34,3,0),(3,34,4,0),(3,34,7,0),(3,35,0,0),(3,35,1,0),(3,35,2,0),(3,35,3,0),(3,35,4,0),(3,35,7,0),(3,37,0,0),(3,37,1,0),(3,37,2,0),(3,37,3,0),(3,37,4,0),(3,37,7,0),(3,39,0,0),(3,39,1,0),(3,39,2,0),(3,39,3,0),(3,39,4,0),(3,39,7,0),(3,40,0,0),(3,40,1,0),(3,40,2,0),(3,40,3,0),(3,40,4,0),(3,40,7,0),(3,41,0,0),(3,41,1,0),(3,41,2,0),(3,41,3,0),(3,41,4,0),(3,41,7,0),(3,42,0,0),(3,42,1,0),(3,42,2,0),(3,42,3,0),(3,42,4,0),(3,42,7,0),(3,43,0,0),(3,43,1,0),(3,43,2,0),(3,43,3,0),(3,43,4,0),(3,43,7,0),(3,49,0,0),(3,49,1,0),(3,49,2,0),(3,49,3,0),(3,49,4,0),(3,49,7,0),(4,2,0,1),(4,2,1,1),(4,2,2,1),(4,2,3,0),(4,2,4,0),(4,2,7,1),(4,4,0,1),(4,4,1,1),(4,4,2,1),(4,4,3,0),(4,4,4,0),(4,4,7,1),(4,6,0,1),(4,6,1,1),(4,6,2,1),(4,6,3,0),(4,6,4,0),(4,6,7,1),(4,7,0,1),(4,7,1,1),(4,7,2,1),(4,7,3,0),(4,7,4,0),(4,7,7,1),(4,8,0,1),(4,8,1,1),(4,8,2,1),(4,8,3,0),(4,8,4,0),(4,8,7,1),(4,9,0,1),(4,9,1,1),(4,9,2,1),(4,9,3,0),(4,9,4,0),(4,9,7,1),(4,10,0,0),(4,10,1,0),(4,10,2,0),(4,10,4,0),(4,13,0,1),(4,13,1,1),(4,13,2,1),(4,13,3,0),(4,13,4,0),(4,13,7,1),(4,14,0,1),(4,14,1,1),(4,14,2,1),(4,14,3,0),(4,14,4,0),(4,14,7,1),(4,15,0,1),(4,15,1,1),(4,15,2,1),(4,15,3,0),(4,15,4,0),(4,15,7,1),(4,16,0,1),(4,16,1,1),(4,16,2,1),(4,16,3,0),(4,16,4,0),(4,16,7,1),(4,18,0,1),(4,18,1,1),(4,18,2,1),(4,18,3,0),(4,18,4,0),(4,18,7,1),(4,19,0,1),(4,19,1,1),(4,19,2,1),(4,19,3,0),(4,19,4,0),(4,19,7,1),(4,20,0,1),(4,20,1,1),(4,20,2,1),(4,20,3,0),(4,20,4,0),(4,20,7,1),(4,21,0,1),(4,21,1,1),(4,21,2,1),(4,21,3,0),(4,21,4,0),(4,21,7,1),(4,22,0,1),(4,22,1,1),(4,22,2,1),(4,22,3,0),(4,22,4,0),(4,22,7,1),(4,23,0,1),(4,23,1,1),(4,23,2,1),(4,23,3,0),(4,23,4,0),(4,23,7,1),(4,25,0,1),(4,25,1,0),(4,25,2,0),(4,25,3,0),(4,25,4,0),(4,25,7,0),(4,26,0,1),(4,26,1,1),(4,26,2,1),(4,26,3,0),(4,26,4,0),(4,26,7,1),(4,34,0,0),(4,34,1,0),(4,34,2,0),(4,34,3,0),(4,34,4,0),(4,34,7,0),(4,35,0,0),(4,35,1,0),(4,35,2,0),(4,35,3,0),(4,35,4,0),(4,35,7,0),(4,37,0,0),(4,37,1,0),(4,37,2,0),(4,37,3,0),(4,37,4,0),(4,37,7,0),(4,39,0,0),(4,39,1,0),(4,39,2,0),(4,39,3,0),(4,39,4,0),(4,39,7,0),(4,40,0,0),(4,40,1,0),(4,40,2,0),(4,40,3,0),(4,40,4,0),(4,40,7,0),(4,41,0,0),(4,41,1,0),(4,41,2,0),(4,41,3,0),(4,41,4,0),(4,41,7,0),(4,42,0,0),(4,42,1,0),(4,42,2,0),(4,42,3,0),(4,42,4,0),(4,42,7,0),(4,43,0,0),(4,43,1,0),(4,43,2,0),(4,43,3,0),(4,43,4,0),(4,43,7,0),(4,49,0,0),(4,49,1,0),(4,49,2,0),(4,49,3,0),(4,49,4,0),(4,49,7,0);
/*!40000 ALTER TABLE `vtiger_profile2standardpermissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_profile2tab`
--

DROP TABLE IF EXISTS `vtiger_profile2tab`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_profile2tab` (
  `profileid` int DEFAULT NULL,
  `tabid` int DEFAULT NULL,
  `permissions` int NOT NULL DEFAULT '0',
  KEY `profile2tab_profileid_tabid_idx` (`profileid`,`tabid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_profile2tab`
--

LOCK TABLES `vtiger_profile2tab` WRITE;
/*!40000 ALTER TABLE `vtiger_profile2tab` DISABLE KEYS */;
INSERT INTO `vtiger_profile2tab` VALUES (1,1,0),(1,2,0),(1,3,0),(1,4,0),(1,6,0),(1,7,0),(1,8,0),(1,9,0),(1,10,0),(1,13,0),(1,14,0),(1,15,0),(1,16,0),(1,18,0),(1,19,0),(1,20,0),(1,21,0),(1,22,0),(1,23,0),(1,24,0),(1,25,0),(1,26,0),(1,27,0),(2,1,0),(2,2,0),(2,3,0),(2,4,0),(2,6,0),(2,7,0),(2,8,0),(2,9,0),(2,10,0),(2,13,0),(2,14,0),(2,15,0),(2,16,0),(2,18,0),(2,19,0),(2,20,0),(2,21,0),(2,22,0),(2,23,0),(2,24,0),(2,25,0),(2,26,0),(2,27,0),(3,1,0),(3,2,0),(3,3,0),(3,4,0),(3,6,0),(3,7,0),(3,8,0),(3,9,0),(3,10,0),(3,13,0),(3,14,0),(3,15,0),(3,16,0),(3,18,0),(3,19,0),(3,20,0),(3,21,0),(3,22,0),(3,23,0),(3,24,0),(3,25,0),(3,26,0),(3,27,0),(4,1,0),(4,2,0),(4,3,0),(4,4,0),(4,6,0),(4,7,0),(4,8,0),(4,9,0),(4,10,0),(4,13,0),(4,14,0),(4,15,0),(4,16,0),(4,18,0),(4,19,0),(4,20,0),(4,21,0),(4,22,0),(4,23,0),(4,24,0),(4,25,0),(4,26,0),(4,27,0),(1,30,0),(2,30,0),(3,30,0),(4,30,0),(1,31,0),(2,31,0),(3,31,0),(4,31,0),(1,32,0),(2,32,0),(3,32,0),(4,32,0),(1,33,0),(2,33,0),(3,33,0),(4,33,0),(1,34,0),(2,34,0),(3,34,0),(4,34,0),(1,35,0),(2,35,0),(3,35,0),(4,35,0),(1,36,0),(2,36,0),(3,36,0),(4,36,0),(1,37,0),(2,37,0),(3,37,0),(4,37,0),(1,38,0),(2,38,0),(3,38,0),(4,38,0),(1,39,0),(2,39,0),(3,39,0),(4,39,0),(1,40,0),(2,40,0),(3,40,0),(4,40,0),(1,41,0),(2,41,0),(3,41,0),(4,41,0),(1,42,0),(2,42,0),(3,42,0),(4,42,0),(1,43,0),(2,43,0),(3,43,0),(4,43,0),(1,44,0),(2,44,0),(3,44,0),(4,44,0),(1,45,0),(2,45,0),(3,45,0),(4,45,0),(1,47,0),(2,47,0),(3,47,0),(4,47,0),(1,48,0),(2,48,0),(3,48,0),(4,48,0),(1,49,0),(2,49,0),(3,49,0),(4,49,0);
/*!40000 ALTER TABLE `vtiger_profile2tab` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_profile2utility`
--

DROP TABLE IF EXISTS `vtiger_profile2utility`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_profile2utility` (
  `profileid` int NOT NULL,
  `tabid` int NOT NULL,
  `activityid` int NOT NULL,
  `permission` int DEFAULT NULL,
  PRIMARY KEY (`profileid`,`tabid`,`activityid`),
  KEY `profile2utility_profileid_tabid_activityid_idx` (`profileid`,`tabid`,`activityid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_profile2utility`
--

LOCK TABLES `vtiger_profile2utility` WRITE;
/*!40000 ALTER TABLE `vtiger_profile2utility` DISABLE KEYS */;
INSERT INTO `vtiger_profile2utility` VALUES (1,2,5,0),(1,2,6,0),(1,2,10,0),(1,4,5,0),(1,4,6,0),(1,4,8,0),(1,4,10,0),(1,6,5,0),(1,6,6,0),(1,6,8,0),(1,6,10,0),(1,7,5,0),(1,7,6,0),(1,7,8,0),(1,7,9,0),(1,7,10,0),(1,8,6,0),(1,9,5,0),(1,9,6,0),(1,13,5,0),(1,13,6,0),(1,13,8,0),(1,13,10,0),(1,14,5,0),(1,14,6,0),(1,14,10,0),(1,18,5,0),(1,18,6,0),(1,18,10,0),(1,19,5,1),(1,19,6,1),(1,19,10,1),(1,20,5,0),(1,20,6,0),(1,21,5,0),(1,21,6,0),(1,22,5,0),(1,22,6,0),(1,23,5,0),(1,23,6,0),(1,25,6,0),(1,25,11,0),(1,34,5,0),(1,34,6,0),(1,34,10,0),(1,35,5,0),(1,35,6,0),(1,35,10,0),(1,39,5,1),(1,39,6,1),(1,39,10,1),(1,40,5,0),(1,40,6,0),(1,40,10,0),(1,41,5,0),(1,41,6,0),(1,41,10,0),(1,49,5,0),(1,49,6,0),(1,49,8,0),(2,2,5,1),(2,2,6,1),(2,2,10,0),(2,4,5,1),(2,4,6,1),(2,4,8,0),(2,4,10,0),(2,6,5,1),(2,6,6,1),(2,6,8,0),(2,6,10,0),(2,7,5,1),(2,7,6,1),(2,7,8,0),(2,7,9,0),(2,7,10,0),(2,8,6,1),(2,9,5,0),(2,9,6,0),(2,13,5,1),(2,13,6,1),(2,13,8,0),(2,13,10,0),(2,14,5,1),(2,14,6,1),(2,14,10,0),(2,18,5,1),(2,18,6,1),(2,18,10,0),(2,19,5,1),(2,19,6,1),(2,19,10,1),(2,20,5,0),(2,20,6,0),(2,21,5,0),(2,21,6,0),(2,22,5,0),(2,22,6,0),(2,23,5,0),(2,23,6,0),(2,25,6,0),(2,25,11,0),(2,34,5,0),(2,34,6,0),(2,34,10,0),(2,35,5,0),(2,35,6,0),(2,35,10,0),(2,39,5,1),(2,39,6,1),(2,39,10,1),(2,40,5,0),(2,40,6,0),(2,40,10,0),(2,41,5,0),(2,41,6,0),(2,41,10,0),(2,49,5,0),(2,49,6,0),(2,49,8,0),(3,2,5,1),(3,2,6,1),(3,2,10,0),(3,4,5,1),(3,4,6,1),(3,4,8,0),(3,4,10,0),(3,6,5,1),(3,6,6,1),(3,6,8,0),(3,6,10,0),(3,7,5,1),(3,7,6,1),(3,7,8,0),(3,7,9,0),(3,7,10,0),(3,8,6,1),(3,9,5,0),(3,9,6,0),(3,13,5,1),(3,13,6,1),(3,13,8,0),(3,13,10,0),(3,14,5,1),(3,14,6,1),(3,14,10,0),(3,18,5,1),(3,18,6,1),(3,18,10,0),(3,19,5,1),(3,19,6,1),(3,19,10,1),(3,20,5,0),(3,20,6,0),(3,21,5,0),(3,21,6,0),(3,22,5,0),(3,22,6,0),(3,23,5,0),(3,23,6,0),(3,25,6,0),(3,25,11,0),(3,34,5,0),(3,34,6,0),(3,34,10,0),(3,35,5,0),(3,35,6,0),(3,35,10,0),(3,39,5,1),(3,39,6,1),(3,39,10,1),(3,40,5,0),(3,40,6,0),(3,40,10,0),(3,41,5,0),(3,41,6,0),(3,41,10,0),(3,49,5,0),(3,49,6,0),(3,49,8,0),(4,2,5,1),(4,2,6,1),(4,2,10,0),(4,4,5,1),(4,4,6,1),(4,4,8,1),(4,4,10,0),(4,6,5,1),(4,6,6,1),(4,6,8,1),(4,6,10,0),(4,7,5,1),(4,7,6,1),(4,7,8,1),(4,7,9,0),(4,7,10,0),(4,8,6,1),(4,9,5,0),(4,9,6,0),(4,13,5,1),(4,13,6,1),(4,13,8,1),(4,13,10,0),(4,14,5,1),(4,14,6,1),(4,14,10,0),(4,18,5,1),(4,18,6,1),(4,18,10,0),(4,19,5,1),(4,19,6,1),(4,19,10,1),(4,20,5,0),(4,20,6,0),(4,21,5,0),(4,21,6,0),(4,22,5,0),(4,22,6,0),(4,23,5,0),(4,23,6,0),(4,25,6,0),(4,25,11,0),(4,34,5,0),(4,34,6,0),(4,34,10,0),(4,35,5,0),(4,35,6,0),(4,35,10,0),(4,39,5,1),(4,39,6,1),(4,39,10,1),(4,40,5,0),(4,40,6,0),(4,40,10,0),(4,41,5,0),(4,41,6,0),(4,41,10,0),(4,49,5,0),(4,49,6,0),(4,49,8,0);
/*!40000 ALTER TABLE `vtiger_profile2utility` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_profile_seq`
--

DROP TABLE IF EXISTS `vtiger_profile_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_profile_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_profile_seq`
--

LOCK TABLES `vtiger_profile_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_profile_seq` DISABLE KEYS */;
INSERT INTO `vtiger_profile_seq` VALUES (4);
/*!40000 ALTER TABLE `vtiger_profile_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_progress`
--

DROP TABLE IF EXISTS `vtiger_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_progress` (
  `progressid` int NOT NULL AUTO_INCREMENT,
  `progress` varchar(200) NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT '0',
  `color` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`progressid`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_progress`
--

LOCK TABLES `vtiger_progress` WRITE;
/*!40000 ALTER TABLE `vtiger_progress` DISABLE KEYS */;
INSERT INTO `vtiger_progress` VALUES (2,'10%',1,276,2,NULL),(3,'20%',1,277,3,NULL),(4,'30%',1,278,4,NULL),(5,'40%',1,279,5,NULL),(6,'50%',1,280,6,NULL),(7,'60%',1,281,7,NULL),(8,'70%',1,282,8,NULL),(9,'80%',1,283,9,NULL),(10,'90%',1,284,10,NULL),(11,'100%',1,285,11,NULL);
/*!40000 ALTER TABLE `vtiger_progress` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_progress_seq`
--

DROP TABLE IF EXISTS `vtiger_progress_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_progress_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_progress_seq`
--

LOCK TABLES `vtiger_progress_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_progress_seq` DISABLE KEYS */;
INSERT INTO `vtiger_progress_seq` VALUES (11);
/*!40000 ALTER TABLE `vtiger_progress_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_project`
--

DROP TABLE IF EXISTS `vtiger_project`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_project` (
  `projectid` int NOT NULL,
  `projectname` varchar(255) DEFAULT NULL,
  `project_no` varchar(100) DEFAULT NULL,
  `startdate` date DEFAULT NULL,
  `targetenddate` date DEFAULT NULL,
  `actualenddate` date DEFAULT NULL,
  `targetbudget` varchar(255) DEFAULT NULL,
  `projecturl` varchar(255) DEFAULT NULL,
  `projectstatus` varchar(100) DEFAULT NULL,
  `projectpriority` varchar(100) DEFAULT NULL,
  `projecttype` varchar(100) DEFAULT NULL,
  `progress` varchar(100) DEFAULT NULL,
  `linktoaccountscontacts` varchar(100) DEFAULT NULL,
  `tags` varchar(1) DEFAULT NULL,
  `isconvertedfrompotential` int NOT NULL DEFAULT '0',
  `potentialid` varchar(100) DEFAULT NULL,
  `smcreatorid` int DEFAULT NULL,
  `smownerid` int DEFAULT NULL,
  `modifiedby` int DEFAULT NULL,
  `description` longtext,
  `createdtime` datetime DEFAULT NULL,
  `modifiedtime` datetime DEFAULT NULL,
  `viewedtime` datetime DEFAULT NULL,
  `deleted` int DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `smgroupid` int DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`projectid`),
  KEY `linktoaccountscontacts` (`linktoaccountscontacts`),
  KEY `potentialid` (`potentialid`),
  KEY `idx_info` (`deleted`,`modifiedtime`),
  KEY `idx_label` (`deleted`,`label`),
  KEY `idx_owner` (`deleted`,`smownerid`),
  CONSTRAINT `fk_crmid_vtiger_project` FOREIGN KEY (`projectid`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_project`
--

LOCK TABLES `vtiger_project` WRITE;
/*!40000 ALTER TABLE `vtiger_project` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_project` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_projectcf`
--

DROP TABLE IF EXISTS `vtiger_projectcf`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_projectcf` (
  `projectid` int NOT NULL,
  PRIMARY KEY (`projectid`),
  CONSTRAINT `fk_projectid_vtiger_projectcf` FOREIGN KEY (`projectid`) REFERENCES `vtiger_project` (`projectid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_projectcf`
--

LOCK TABLES `vtiger_projectcf` WRITE;
/*!40000 ALTER TABLE `vtiger_projectcf` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_projectcf` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_projectmilestone`
--

DROP TABLE IF EXISTS `vtiger_projectmilestone`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_projectmilestone` (
  `projectmilestoneid` int NOT NULL,
  `projectmilestonename` varchar(255) DEFAULT NULL,
  `projectmilestone_no` varchar(100) DEFAULT NULL,
  `projectmilestonedate` varchar(255) DEFAULT NULL,
  `projectid` varchar(100) DEFAULT NULL,
  `projectmilestonetype` varchar(100) DEFAULT NULL,
  `tags` varchar(1) DEFAULT NULL,
  `smcreatorid` int DEFAULT NULL,
  `smownerid` int DEFAULT NULL,
  `modifiedby` int DEFAULT NULL,
  `description` longtext,
  `createdtime` datetime DEFAULT NULL,
  `modifiedtime` datetime DEFAULT NULL,
  `viewedtime` datetime DEFAULT NULL,
  `deleted` int DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `smgroupid` int DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`projectmilestoneid`),
  KEY `projectid` (`projectid`),
  KEY `idx_info` (`deleted`,`modifiedtime`),
  KEY `idx_label` (`deleted`,`label`),
  KEY `idx_owner` (`deleted`,`smownerid`),
  CONSTRAINT `fk_crmid_vtiger_projectmilestone` FOREIGN KEY (`projectmilestoneid`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_projectmilestone`
--

LOCK TABLES `vtiger_projectmilestone` WRITE;
/*!40000 ALTER TABLE `vtiger_projectmilestone` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_projectmilestone` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_projectmilestonecf`
--

DROP TABLE IF EXISTS `vtiger_projectmilestonecf`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_projectmilestonecf` (
  `projectmilestoneid` int NOT NULL,
  PRIMARY KEY (`projectmilestoneid`),
  CONSTRAINT `fk_projectmilestoneid_vtiger_projectmilestonecf` FOREIGN KEY (`projectmilestoneid`) REFERENCES `vtiger_projectmilestone` (`projectmilestoneid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_projectmilestonecf`
--

LOCK TABLES `vtiger_projectmilestonecf` WRITE;
/*!40000 ALTER TABLE `vtiger_projectmilestonecf` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_projectmilestonecf` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_projectmilestonetype`
--

DROP TABLE IF EXISTS `vtiger_projectmilestonetype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_projectmilestonetype` (
  `projectmilestonetypeid` int NOT NULL AUTO_INCREMENT,
  `projectmilestonetype` varchar(200) NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT '0',
  `color` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`projectmilestonetypeid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_projectmilestonetype`
--

LOCK TABLES `vtiger_projectmilestonetype` WRITE;
/*!40000 ALTER TABLE `vtiger_projectmilestonetype` DISABLE KEYS */;
INSERT INTO `vtiger_projectmilestonetype` VALUES (2,'administrative',1,236,2,NULL),(3,'operative',1,237,3,NULL),(4,'other',1,238,4,NULL);
/*!40000 ALTER TABLE `vtiger_projectmilestonetype` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_projectmilestonetype_seq`
--

DROP TABLE IF EXISTS `vtiger_projectmilestonetype_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_projectmilestonetype_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_projectmilestonetype_seq`
--

LOCK TABLES `vtiger_projectmilestonetype_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_projectmilestonetype_seq` DISABLE KEYS */;
INSERT INTO `vtiger_projectmilestonetype_seq` VALUES (4);
/*!40000 ALTER TABLE `vtiger_projectmilestonetype_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_projectpriority`
--

DROP TABLE IF EXISTS `vtiger_projectpriority`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_projectpriority` (
  `projectpriorityid` int NOT NULL AUTO_INCREMENT,
  `projectpriority` varchar(200) NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT '0',
  `color` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`projectpriorityid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_projectpriority`
--

LOCK TABLES `vtiger_projectpriority` WRITE;
/*!40000 ALTER TABLE `vtiger_projectpriority` DISABLE KEYS */;
INSERT INTO `vtiger_projectpriority` VALUES (2,'low',1,272,2,NULL),(3,'normal',1,273,3,NULL),(4,'high',1,274,4,NULL);
/*!40000 ALTER TABLE `vtiger_projectpriority` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_projectpriority_seq`
--

DROP TABLE IF EXISTS `vtiger_projectpriority_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_projectpriority_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_projectpriority_seq`
--

LOCK TABLES `vtiger_projectpriority_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_projectpriority_seq` DISABLE KEYS */;
INSERT INTO `vtiger_projectpriority_seq` VALUES (4);
/*!40000 ALTER TABLE `vtiger_projectpriority_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_projectstatus`
--

DROP TABLE IF EXISTS `vtiger_projectstatus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_projectstatus` (
  `projectstatusid` int NOT NULL AUTO_INCREMENT,
  `projectstatus` varchar(200) NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT '0',
  `color` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`projectstatusid`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_projectstatus`
--

LOCK TABLES `vtiger_projectstatus` WRITE;
/*!40000 ALTER TABLE `vtiger_projectstatus` DISABLE KEYS */;
INSERT INTO `vtiger_projectstatus` VALUES (2,'prospecting',1,259,2,NULL),(3,'initiated',1,260,3,NULL),(4,'in progress',1,261,4,NULL),(5,'waiting for feedback',1,262,5,NULL),(6,'on hold',1,263,6,NULL),(7,'completed',1,264,7,NULL),(8,'delivered',1,265,8,NULL),(9,'archived',1,266,9,NULL);
/*!40000 ALTER TABLE `vtiger_projectstatus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_projectstatus_seq`
--

DROP TABLE IF EXISTS `vtiger_projectstatus_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_projectstatus_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_projectstatus_seq`
--

LOCK TABLES `vtiger_projectstatus_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_projectstatus_seq` DISABLE KEYS */;
INSERT INTO `vtiger_projectstatus_seq` VALUES (9);
/*!40000 ALTER TABLE `vtiger_projectstatus_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_projecttask`
--

DROP TABLE IF EXISTS `vtiger_projecttask`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_projecttask` (
  `projecttaskid` int NOT NULL,
  `projecttaskname` varchar(255) DEFAULT NULL,
  `projecttask_no` varchar(100) DEFAULT NULL,
  `projecttasktype` varchar(100) DEFAULT NULL,
  `projecttaskpriority` varchar(100) DEFAULT NULL,
  `projecttaskprogress` varchar(100) DEFAULT NULL,
  `projecttaskhours` varchar(255) DEFAULT NULL,
  `startdate` date DEFAULT NULL,
  `enddate` date DEFAULT NULL,
  `projectid` varchar(100) DEFAULT NULL,
  `projecttasknumber` int DEFAULT NULL,
  `tags` varchar(1) DEFAULT NULL,
  `projecttaskstatus` varchar(100) DEFAULT NULL,
  `smcreatorid` int DEFAULT NULL,
  `smownerid` int DEFAULT NULL,
  `modifiedby` int DEFAULT NULL,
  `description` longtext,
  `createdtime` datetime DEFAULT NULL,
  `modifiedtime` datetime DEFAULT NULL,
  `viewedtime` datetime DEFAULT NULL,
  `deleted` int DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `smgroupid` int DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`projecttaskid`),
  KEY `projectid` (`projectid`),
  KEY `idx_info` (`deleted`,`modifiedtime`),
  KEY `idx_label` (`deleted`,`label`),
  KEY `idx_owner` (`deleted`,`smownerid`),
  CONSTRAINT `fk_crmid_vtiger_projecttask` FOREIGN KEY (`projecttaskid`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_projecttask`
--

LOCK TABLES `vtiger_projecttask` WRITE;
/*!40000 ALTER TABLE `vtiger_projecttask` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_projecttask` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_projecttask_status_color`
--

DROP TABLE IF EXISTS `vtiger_projecttask_status_color`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_projecttask_status_color` (
  `status` varchar(255) DEFAULT NULL,
  `defaultcolor` varchar(50) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  UNIQUE KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_projecttask_status_color`
--

LOCK TABLES `vtiger_projecttask_status_color` WRITE;
/*!40000 ALTER TABLE `vtiger_projecttask_status_color` DISABLE KEYS */;
INSERT INTO `vtiger_projecttask_status_color` VALUES ('Open','#0099ff',NULL),('In Progress','#fdff00',NULL),('Completed','#3BBF67',NULL),('Deferred','#fbb11e',NULL),('Canceled','#660066',NULL);
/*!40000 ALTER TABLE `vtiger_projecttask_status_color` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_projecttaskcf`
--

DROP TABLE IF EXISTS `vtiger_projecttaskcf`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_projecttaskcf` (
  `projecttaskid` int NOT NULL,
  PRIMARY KEY (`projecttaskid`),
  CONSTRAINT `fk_projecttaskid_vtiger_projecttaskcf` FOREIGN KEY (`projecttaskid`) REFERENCES `vtiger_projecttask` (`projecttaskid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_projecttaskcf`
--

LOCK TABLES `vtiger_projecttaskcf` WRITE;
/*!40000 ALTER TABLE `vtiger_projecttaskcf` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_projecttaskcf` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_projecttaskpriority`
--

DROP TABLE IF EXISTS `vtiger_projecttaskpriority`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_projecttaskpriority` (
  `projecttaskpriorityid` int NOT NULL AUTO_INCREMENT,
  `projecttaskpriority` varchar(200) NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT '0',
  `color` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`projecttaskpriorityid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_projecttaskpriority`
--

LOCK TABLES `vtiger_projecttaskpriority` WRITE;
/*!40000 ALTER TABLE `vtiger_projecttaskpriority` DISABLE KEYS */;
INSERT INTO `vtiger_projecttaskpriority` VALUES (2,'low',1,244,2,NULL),(3,'normal',1,245,3,NULL),(4,'high',1,246,4,NULL);
/*!40000 ALTER TABLE `vtiger_projecttaskpriority` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_projecttaskpriority_seq`
--

DROP TABLE IF EXISTS `vtiger_projecttaskpriority_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_projecttaskpriority_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_projecttaskpriority_seq`
--

LOCK TABLES `vtiger_projecttaskpriority_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_projecttaskpriority_seq` DISABLE KEYS */;
INSERT INTO `vtiger_projecttaskpriority_seq` VALUES (4);
/*!40000 ALTER TABLE `vtiger_projecttaskpriority_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_projecttaskprogress`
--

DROP TABLE IF EXISTS `vtiger_projecttaskprogress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_projecttaskprogress` (
  `projecttaskprogressid` int NOT NULL AUTO_INCREMENT,
  `projecttaskprogress` varchar(200) NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT '0',
  `color` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`projecttaskprogressid`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_projecttaskprogress`
--

LOCK TABLES `vtiger_projecttaskprogress` WRITE;
/*!40000 ALTER TABLE `vtiger_projecttaskprogress` DISABLE KEYS */;
INSERT INTO `vtiger_projecttaskprogress` VALUES (2,'10%',1,248,2,NULL),(3,'20%',1,249,3,NULL),(4,'30%',1,250,4,NULL),(5,'40%',1,251,5,NULL),(6,'50%',1,252,6,NULL),(7,'60%',1,253,7,NULL),(8,'70%',1,254,8,NULL),(9,'80%',1,255,9,NULL),(10,'90%',1,256,10,NULL),(11,'100%',1,257,11,NULL);
/*!40000 ALTER TABLE `vtiger_projecttaskprogress` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_projecttaskprogress_seq`
--

DROP TABLE IF EXISTS `vtiger_projecttaskprogress_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_projecttaskprogress_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_projecttaskprogress_seq`
--

LOCK TABLES `vtiger_projecttaskprogress_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_projecttaskprogress_seq` DISABLE KEYS */;
INSERT INTO `vtiger_projecttaskprogress_seq` VALUES (11);
/*!40000 ALTER TABLE `vtiger_projecttaskprogress_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_projecttaskstatus`
--

DROP TABLE IF EXISTS `vtiger_projecttaskstatus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_projecttaskstatus` (
  `projecttaskstatusid` int NOT NULL AUTO_INCREMENT,
  `projecttaskstatus` varchar(200) NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT '0',
  `color` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`projecttaskstatusid`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_projecttaskstatus`
--

LOCK TABLES `vtiger_projecttaskstatus` WRITE;
/*!40000 ALTER TABLE `vtiger_projecttaskstatus` DISABLE KEYS */;
INSERT INTO `vtiger_projecttaskstatus` VALUES (2,'Open',0,290,2,NULL),(3,'In Progress',0,291,3,NULL),(4,'Completed',0,292,4,NULL),(5,'Deferred',0,293,5,NULL),(6,'Canceled',0,294,6,NULL);
/*!40000 ALTER TABLE `vtiger_projecttaskstatus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_projecttaskstatus_seq`
--

DROP TABLE IF EXISTS `vtiger_projecttaskstatus_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_projecttaskstatus_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_projecttaskstatus_seq`
--

LOCK TABLES `vtiger_projecttaskstatus_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_projecttaskstatus_seq` DISABLE KEYS */;
INSERT INTO `vtiger_projecttaskstatus_seq` VALUES (6);
/*!40000 ALTER TABLE `vtiger_projecttaskstatus_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_projecttasktype`
--

DROP TABLE IF EXISTS `vtiger_projecttasktype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_projecttasktype` (
  `projecttasktypeid` int NOT NULL AUTO_INCREMENT,
  `projecttasktype` varchar(200) NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT '0',
  `color` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`projecttasktypeid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_projecttasktype`
--

LOCK TABLES `vtiger_projecttasktype` WRITE;
/*!40000 ALTER TABLE `vtiger_projecttasktype` DISABLE KEYS */;
INSERT INTO `vtiger_projecttasktype` VALUES (2,'administrative',1,240,2,NULL),(3,'operative',1,241,3,NULL),(4,'other',1,242,4,NULL);
/*!40000 ALTER TABLE `vtiger_projecttasktype` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_projecttasktype_seq`
--

DROP TABLE IF EXISTS `vtiger_projecttasktype_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_projecttasktype_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_projecttasktype_seq`
--

LOCK TABLES `vtiger_projecttasktype_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_projecttasktype_seq` DISABLE KEYS */;
INSERT INTO `vtiger_projecttasktype_seq` VALUES (4);
/*!40000 ALTER TABLE `vtiger_projecttasktype_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_projecttype`
--

DROP TABLE IF EXISTS `vtiger_projecttype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_projecttype` (
  `projecttypeid` int NOT NULL AUTO_INCREMENT,
  `projecttype` varchar(200) NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT '0',
  `color` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`projecttypeid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_projecttype`
--

LOCK TABLES `vtiger_projecttype` WRITE;
/*!40000 ALTER TABLE `vtiger_projecttype` DISABLE KEYS */;
INSERT INTO `vtiger_projecttype` VALUES (2,'administrative',1,268,2,NULL),(3,'operative',1,269,3,NULL),(4,'other',1,270,4,NULL);
/*!40000 ALTER TABLE `vtiger_projecttype` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_projecttype_seq`
--

DROP TABLE IF EXISTS `vtiger_projecttype_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_projecttype_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_projecttype_seq`
--

LOCK TABLES `vtiger_projecttype_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_projecttype_seq` DISABLE KEYS */;
INSERT INTO `vtiger_projecttype_seq` VALUES (4);
/*!40000 ALTER TABLE `vtiger_projecttype_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_purchaseorder`
--

DROP TABLE IF EXISTS `vtiger_purchaseorder`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_purchaseorder` (
  `purchaseorderid` int NOT NULL,
  `subject` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `quoteid` int DEFAULT NULL,
  `vendorid` int DEFAULT NULL,
  `requisition_no` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `purchaseorder_no` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tracking_no` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contactid` int DEFAULT NULL,
  `duedate` date DEFAULT NULL,
  `carrier` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `adjustment` decimal(25,8) DEFAULT NULL,
  `salescommission` decimal(25,3) DEFAULT NULL,
  `exciseduty` decimal(25,3) DEFAULT NULL,
  `total` decimal(25,8) DEFAULT NULL,
  `subtotal` decimal(25,8) DEFAULT NULL,
  `taxtype` varchar(25) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `discount_percent` decimal(25,3) DEFAULT NULL,
  `discount_amount` decimal(25,8) DEFAULT NULL,
  `s_h_amount` decimal(25,8) DEFAULT NULL,
  `terms_conditions` text COLLATE utf8mb4_general_ci,
  `postatus` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `currency_id` int NOT NULL DEFAULT '1',
  `conversion_rate` decimal(10,3) NOT NULL DEFAULT '1.000',
  `compound_taxes_info` text COLLATE utf8mb4_general_ci,
  `pre_tax_total` decimal(25,8) DEFAULT NULL,
  `paid` decimal(25,8) DEFAULT NULL,
  `balance` decimal(25,8) DEFAULT NULL,
  `s_h_percent` int DEFAULT NULL,
  `tags` varchar(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `region_id` int DEFAULT NULL,
  `smcreatorid` int DEFAULT NULL,
  `smownerid` int DEFAULT NULL,
  `modifiedby` int DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_general_ci,
  `createdtime` datetime DEFAULT NULL,
  `modifiedtime` datetime DEFAULT NULL,
  `viewedtime` datetime DEFAULT NULL,
  `deleted` int DEFAULT NULL,
  `label` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `smgroupid` int DEFAULT NULL,
  `source` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`purchaseorderid`),
  KEY `purchaseorder_vendorid_idx` (`vendorid`),
  KEY `purchaseorder_quoteid_idx` (`quoteid`),
  KEY `purchaseorder_contactid_idx` (`contactid`),
  KEY `idx_info` (`deleted`,`modifiedtime`),
  KEY `idx_label` (`deleted`,`label`),
  KEY `idx_owner` (`deleted`,`smownerid`),
  CONSTRAINT `fk_4_vtiger_purchaseorder` FOREIGN KEY (`vendorid`) REFERENCES `vtiger_vendor` (`vendorid`) ON DELETE CASCADE,
  CONSTRAINT `fk_crmid_vtiger_purchaseorder` FOREIGN KEY (`purchaseorderid`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_purchaseorder`
--

LOCK TABLES `vtiger_purchaseorder` WRITE;
/*!40000 ALTER TABLE `vtiger_purchaseorder` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_purchaseorder` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_purchaseordercf`
--

DROP TABLE IF EXISTS `vtiger_purchaseordercf`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_purchaseordercf` (
  `purchaseorderid` int NOT NULL,
  PRIMARY KEY (`purchaseorderid`),
  CONSTRAINT `fk_1_vtiger_purchaseordercf` FOREIGN KEY (`purchaseorderid`) REFERENCES `vtiger_purchaseorder` (`purchaseorderid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_purchaseordercf`
--

LOCK TABLES `vtiger_purchaseordercf` WRITE;
/*!40000 ALTER TABLE `vtiger_purchaseordercf` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_purchaseordercf` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_quotes`
--

DROP TABLE IF EXISTS `vtiger_quotes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_quotes` (
  `quoteid` int NOT NULL,
  `subject` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `potentialid` int DEFAULT NULL,
  `quotestage` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `validtill` date DEFAULT NULL,
  `contactid` int DEFAULT NULL,
  `quote_no` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `subtotal` decimal(25,8) DEFAULT NULL,
  `carrier` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `shipping` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `inventorymanager` int DEFAULT NULL,
  `type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `adjustment` decimal(25,8) DEFAULT NULL,
  `total` decimal(25,8) DEFAULT NULL,
  `taxtype` varchar(25) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `discount_percent` decimal(25,3) DEFAULT NULL,
  `discount_amount` decimal(25,8) DEFAULT NULL,
  `s_h_amount` decimal(25,8) DEFAULT NULL,
  `accountid` int DEFAULT NULL,
  `terms_conditions` text COLLATE utf8mb4_general_ci,
  `currency_id` int NOT NULL DEFAULT '1',
  `conversion_rate` decimal(10,3) NOT NULL DEFAULT '1.000',
  `compound_taxes_info` text COLLATE utf8mb4_general_ci,
  `pre_tax_total` decimal(25,8) DEFAULT NULL,
  `s_h_percent` int DEFAULT NULL,
  `tags` varchar(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `region_id` int DEFAULT NULL,
  `smcreatorid` int DEFAULT NULL,
  `smownerid` int DEFAULT NULL,
  `modifiedby` int DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_general_ci,
  `createdtime` datetime DEFAULT NULL,
  `modifiedtime` datetime DEFAULT NULL,
  `viewedtime` datetime DEFAULT NULL,
  `deleted` int DEFAULT NULL,
  `label` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `smgroupid` int DEFAULT NULL,
  `source` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`quoteid`),
  KEY `quote_quotestage_idx` (`quotestage`),
  KEY `quotes_potentialid_idx` (`potentialid`),
  KEY `quotes_contactid_idx` (`contactid`),
  KEY `idx_info` (`deleted`,`modifiedtime`),
  KEY `idx_label` (`deleted`,`label`),
  KEY `idx_owner` (`deleted`,`smownerid`),
  CONSTRAINT `fk_3_vtiger_quotes` FOREIGN KEY (`potentialid`) REFERENCES `vtiger_potential` (`potentialid`) ON DELETE CASCADE,
  CONSTRAINT `fk_crmid_vtiger_quotes` FOREIGN KEY (`quoteid`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_quotes`
--

LOCK TABLES `vtiger_quotes` WRITE;
/*!40000 ALTER TABLE `vtiger_quotes` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_quotes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_quotesbillads`
--

DROP TABLE IF EXISTS `vtiger_quotesbillads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_quotesbillads` (
  `quotebilladdressid` int NOT NULL,
  `bill_city` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bill_code` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bill_country` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bill_state` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bill_street` text COLLATE utf8mb4_general_ci,
  `bill_pobox` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`quotebilladdressid`),
  CONSTRAINT `fk_1_vtiger_quotesbillads` FOREIGN KEY (`quotebilladdressid`) REFERENCES `vtiger_quotes` (`quoteid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_quotesbillads`
--

LOCK TABLES `vtiger_quotesbillads` WRITE;
/*!40000 ALTER TABLE `vtiger_quotesbillads` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_quotesbillads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_quotescf`
--

DROP TABLE IF EXISTS `vtiger_quotescf`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_quotescf` (
  `quoteid` int NOT NULL,
  PRIMARY KEY (`quoteid`),
  CONSTRAINT `fk_1_vtiger_quotescf` FOREIGN KEY (`quoteid`) REFERENCES `vtiger_quotes` (`quoteid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_quotescf`
--

LOCK TABLES `vtiger_quotescf` WRITE;
/*!40000 ALTER TABLE `vtiger_quotescf` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_quotescf` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_quotesshipads`
--

DROP TABLE IF EXISTS `vtiger_quotesshipads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_quotesshipads` (
  `quoteshipaddressid` int NOT NULL,
  `ship_city` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ship_code` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ship_country` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ship_state` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ship_street` text COLLATE utf8mb4_general_ci,
  `ship_pobox` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`quoteshipaddressid`),
  CONSTRAINT `fk_1_vtiger_quotesshipads` FOREIGN KEY (`quoteshipaddressid`) REFERENCES `vtiger_quotes` (`quoteid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_quotesshipads`
--

LOCK TABLES `vtiger_quotesshipads` WRITE;
/*!40000 ALTER TABLE `vtiger_quotesshipads` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_quotesshipads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_quotestage`
--

DROP TABLE IF EXISTS `vtiger_quotestage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_quotestage` (
  `quotestageid` int NOT NULL AUTO_INCREMENT,
  `quotestage` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT NULL,
  `color` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`quotestageid`),
  UNIQUE KEY `quotestage_quotestage_idx` (`quotestage`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_quotestage`
--

LOCK TABLES `vtiger_quotestage` WRITE;
/*!40000 ALTER TABLE `vtiger_quotestage` DISABLE KEYS */;
INSERT INTO `vtiger_quotestage` VALUES (1,'Created',0,139,0,NULL),(2,'Delivered',0,140,1,NULL),(3,'Reviewed',0,141,2,NULL),(4,'Accepted',0,142,3,NULL),(5,'Rejected',0,143,4,NULL);
/*!40000 ALTER TABLE `vtiger_quotestage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_quotestage_seq`
--

DROP TABLE IF EXISTS `vtiger_quotestage_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_quotestage_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_quotestage_seq`
--

LOCK TABLES `vtiger_quotestage_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_quotestage_seq` DISABLE KEYS */;
INSERT INTO `vtiger_quotestage_seq` VALUES (5);
/*!40000 ALTER TABLE `vtiger_quotestage_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_quotestagehistory`
--

DROP TABLE IF EXISTS `vtiger_quotestagehistory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_quotestagehistory` (
  `historyid` int NOT NULL AUTO_INCREMENT,
  `quoteid` int NOT NULL,
  `accountname` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `total` decimal(10,0) DEFAULT NULL,
  `quotestage` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lastmodified` datetime DEFAULT NULL,
  PRIMARY KEY (`historyid`),
  KEY `quotestagehistory_quoteid_idx` (`quoteid`),
  CONSTRAINT `fk_1_vtiger_quotestagehistory` FOREIGN KEY (`quoteid`) REFERENCES `vtiger_quotes` (`quoteid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_quotestagehistory`
--

LOCK TABLES `vtiger_quotestagehistory` WRITE;
/*!40000 ALTER TABLE `vtiger_quotestagehistory` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_quotestagehistory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_rating`
--

DROP TABLE IF EXISTS `vtiger_rating`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_rating` (
  `rating_id` int NOT NULL AUTO_INCREMENT,
  `rating` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT NULL,
  `color` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`rating_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_rating`
--

LOCK TABLES `vtiger_rating` WRITE;
/*!40000 ALTER TABLE `vtiger_rating` DISABLE KEYS */;
INSERT INTO `vtiger_rating` VALUES (2,'Acquired',1,145,1,NULL),(3,'Active',1,146,2,NULL),(4,'Market Failed',1,147,3,NULL),(5,'Project Cancelled',1,148,4,NULL),(6,'Shutdown',1,149,5,NULL);
/*!40000 ALTER TABLE `vtiger_rating` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_rating_seq`
--

DROP TABLE IF EXISTS `vtiger_rating_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_rating_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_rating_seq`
--

LOCK TABLES `vtiger_rating_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_rating_seq` DISABLE KEYS */;
INSERT INTO `vtiger_rating_seq` VALUES (6);
/*!40000 ALTER TABLE `vtiger_rating_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_recurring_frequency`
--

DROP TABLE IF EXISTS `vtiger_recurring_frequency`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_recurring_frequency` (
  `recurring_frequency_id` int DEFAULT NULL,
  `recurring_frequency` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sortorderid` int DEFAULT NULL,
  `presence` int DEFAULT NULL,
  `color` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_recurring_frequency`
--

LOCK TABLES `vtiger_recurring_frequency` WRITE;
/*!40000 ALTER TABLE `vtiger_recurring_frequency` DISABLE KEYS */;
INSERT INTO `vtiger_recurring_frequency` VALUES (2,'Daily',1,1,NULL),(3,'Weekly',2,1,NULL),(4,'Monthly',3,1,NULL),(5,'Quarterly',4,1,NULL),(6,'Half-Yearly',5,1,NULL),(7,'Yearly',6,1,NULL);
/*!40000 ALTER TABLE `vtiger_recurring_frequency` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_recurring_frequency_seq`
--

DROP TABLE IF EXISTS `vtiger_recurring_frequency_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_recurring_frequency_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_recurring_frequency_seq`
--

LOCK TABLES `vtiger_recurring_frequency_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_recurring_frequency_seq` DISABLE KEYS */;
INSERT INTO `vtiger_recurring_frequency_seq` VALUES (7);
/*!40000 ALTER TABLE `vtiger_recurring_frequency_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_recurringevents`
--

DROP TABLE IF EXISTS `vtiger_recurringevents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_recurringevents` (
  `recurringid` int NOT NULL AUTO_INCREMENT,
  `activityid` int NOT NULL,
  `recurringdate` date DEFAULT NULL,
  `recurringtype` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `recurringfreq` int DEFAULT NULL,
  `recurringinfo` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `recurringenddate` date DEFAULT NULL,
  PRIMARY KEY (`recurringid`),
  KEY `fk_1_vtiger_recurringevents` (`activityid`),
  CONSTRAINT `fk_1_vtiger_recurringevents` FOREIGN KEY (`activityid`) REFERENCES `vtiger_activity` (`activityid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_recurringevents`
--

LOCK TABLES `vtiger_recurringevents` WRITE;
/*!40000 ALTER TABLE `vtiger_recurringevents` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_recurringevents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_recurringtype`
--

DROP TABLE IF EXISTS `vtiger_recurringtype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_recurringtype` (
  `recurringeventid` int NOT NULL AUTO_INCREMENT,
  `recurringtype` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `sortorderid` int NOT NULL DEFAULT '0',
  `presence` int NOT NULL DEFAULT '1',
  `color` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`recurringeventid`),
  UNIQUE KEY `recurringtype_status_idx` (`recurringtype`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_recurringtype`
--

LOCK TABLES `vtiger_recurringtype` WRITE;
/*!40000 ALTER TABLE `vtiger_recurringtype` DISABLE KEYS */;
INSERT INTO `vtiger_recurringtype` VALUES (2,'Daily',1,1,NULL),(3,'Weekly',2,1,NULL),(4,'Monthly',3,1,NULL),(5,'Yearly',4,1,NULL);
/*!40000 ALTER TABLE `vtiger_recurringtype` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_recurringtype_seq`
--

DROP TABLE IF EXISTS `vtiger_recurringtype_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_recurringtype_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_recurringtype_seq`
--

LOCK TABLES `vtiger_recurringtype_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_recurringtype_seq` DISABLE KEYS */;
INSERT INTO `vtiger_recurringtype_seq` VALUES (5);
/*!40000 ALTER TABLE `vtiger_recurringtype_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_relatedlists`
--

DROP TABLE IF EXISTS `vtiger_relatedlists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_relatedlists` (
  `relation_id` int NOT NULL,
  `tabid` int DEFAULT NULL,
  `related_tabid` int DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sequence` int DEFAULT NULL,
  `label` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `presence` int NOT NULL DEFAULT '0',
  `actions` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `relationfieldid` int DEFAULT NULL,
  `source` varchar(25) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `relationtype` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`relation_id`),
  KEY `relatedlists_relation_id_idx` (`relation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_relatedlists`
--

LOCK TABLES `vtiger_relatedlists` WRITE;
/*!40000 ALTER TABLE `vtiger_relatedlists` DISABLE KEYS */;
INSERT INTO `vtiger_relatedlists` VALUES (1,6,4,'get_contacts',2,'Contacts',0,'add',72,'','1:N'),(2,6,2,'get_opportunities',3,'Potentials',0,'add',113,'','1:N'),(3,6,20,'get_quotes',4,'Quotes',0,'add',330,'','1:N'),(4,6,22,'get_salesorder',5,'Sales Order',0,'add',409,'','1:N'),(5,6,23,'get_invoices',6,'Invoice',0,'add',452,'','1:N'),(6,6,9,'get_activities',7,'Activities',0,'add',238,'','1:N'),(7,6,10,'get_emails',8,'Emails',0,'add',NULL,'','N:N'),(8,6,9,'get_history',9,'Activity History',0,'add',238,'','1:N'),(9,6,8,'get_attachments',10,'Documents',0,'add,select',NULL,'','N:N'),(10,6,13,'get_tickets',11,'HelpDesk',0,'add',157,'','1:N'),(11,6,14,'get_products',12,'Products',0,'select',NULL,'','N:N'),(12,7,9,'get_activities',2,'Activities',0,'add',238,'','1:N'),(13,7,10,'get_emails',3,'Emails',0,'add',NULL,'','N:N'),(14,7,9,'get_history',4,'Activity History',0,'add',238,'','1:N'),(15,7,8,'get_attachments',5,'Documents',0,'add,select',NULL,'','N:N'),(16,7,14,'get_products',6,'Products',0,'select',NULL,'','N:N'),(17,7,26,'get_campaigns',7,'Campaigns',0,'select',NULL,'','N:N'),(18,4,2,'get_opportunities',2,'Potentials',0,'add',722,'','1:N'),(19,4,9,'get_activities',3,'Activities',0,'add',239,'','1:N'),(20,4,10,'get_emails',4,'Emails',0,'add',NULL,'','N:N'),(21,4,13,'get_tickets',5,'HelpDesk',0,'add',723,'','1:N'),(22,4,20,'get_quotes',6,'Quotes',0,'add',319,'','1:N'),(23,4,21,'get_purchase_orders',7,'Purchase Order',0,'add',356,'','1:N'),(24,4,22,'get_salesorder',8,'Sales Order',0,'add',395,'','1:N'),(25,4,14,'get_products',9,'Products',0,'select',NULL,'','N:N'),(26,4,9,'get_history',10,'Activity History',0,'add',239,'','1:N'),(27,4,8,'get_attachments',11,'Documents',0,'add,select',NULL,'','N:N'),(28,4,26,'get_campaigns',12,'Campaigns',0,'select',NULL,'','N:N'),(29,4,23,'get_invoices',13,'Invoice',0,'add',439,'','1:N'),(30,2,9,'get_activities',2,'Activities',0,'add',238,'','1:N'),(31,2,4,'get_contacts',3,'Contacts',0,'select',NULL,'','N:N'),(32,2,14,'get_products',4,'Products',0,'select',NULL,'','N:N'),(33,2,0,'get_stage_history',5,'Sales Stage History',0,'',NULL,'','N:N'),(34,2,8,'get_attachments',6,'Documents',0,'add,select',NULL,'','N:N'),(35,2,20,'get_Quotes',7,'Quotes',0,'add',316,'','1:N'),(36,2,22,'get_salesorder',8,'Sales Order',0,'add',391,'','1:N'),(37,2,9,'get_history',9,'Activity History',0,'',238,'','1:N'),(38,14,13,'get_tickets',1,'HelpDesk',0,'add',159,'','1:N'),(39,14,8,'get_attachments',3,'Documents',0,'add,select',NULL,'','N:N'),(40,14,20,'get_quotes',4,'Quotes',0,'add',685,'','1:N'),(41,14,21,'get_purchase_orders',5,'Purchase Order',0,'add',676,'','1:N'),(42,14,22,'get_salesorder',6,'Sales Order',0,'add',667,'','1:N'),(43,14,23,'get_invoices',7,'Invoice',0,'add',658,'','1:N'),(44,14,19,'get_product_pricebooks',8,'PriceBooks',0,'ADD,SELECT',NULL,'','N:N'),(45,14,7,'get_leads',9,'Leads',0,'select',NULL,'','N:N'),(46,14,6,'get_accounts',10,'Accounts',0,'select',NULL,'','N:N'),(47,14,4,'get_contacts',11,'Contacts',0,'select',NULL,'','N:N'),(48,14,2,'get_opportunities',12,'Potentials',0,'select',NULL,'','N:N'),(49,14,14,'get_products',13,'Product Bundles',0,'add,select',NULL,'','N:N'),(50,14,14,'get_parent_products',14,'Parent Product',0,'',NULL,'','N:N'),(51,10,4,'get_contacts',1,'Contacts',0,'select,bulkmail',NULL,'','N:N'),(52,10,0,'get_users',2,'Users',0,'',NULL,'','N:N'),(53,10,8,'get_attachments',3,'Documents',0,'add,select',NULL,'','N:N'),(54,13,9,'get_activities',2,'Activities',0,'add',238,'','1:N'),(55,13,8,'get_attachments',3,'Documents',0,'add,select',NULL,'','N:N'),(56,13,0,'get_ticket_history',4,'Ticket History',0,'',NULL,'','N:N'),(57,13,9,'get_history',5,'Activity History',0,'add',238,'','1:N'),(58,19,14,'get_pricebook_products',2,'Products',0,'select',NULL,'','N:N'),(59,18,14,'get_products',1,'Products',0,'add,select',185,'','1:N'),(60,18,21,'get_purchase_orders',2,'Purchase Order',0,'add',353,'','1:N'),(61,18,4,'get_contacts',3,'Contacts',0,'select',NULL,'','N:N'),(62,18,10,'get_emails',4,'Emails',0,'add',NULL,'','N:N'),(63,20,22,'get_salesorder',1,'Sales Order',0,'',393,'','1:N'),(64,20,9,'get_activities',2,'Activities',0,'add',238,'','1:N'),(65,20,8,'get_attachments',3,'Documents',0,'add,select',NULL,'','N:N'),(66,20,9,'get_history',4,'Activity History',0,'',NULL,'','N:N'),(67,20,0,'get_quotestagehistory',5,'Quote Stage History',0,'',NULL,'','N:N'),(68,21,9,'get_activities',1,'Activities',0,'add',238,'','1:N'),(69,21,8,'get_attachments',2,'Documents',0,'add,select',NULL,'','N:N'),(70,21,9,'get_history',3,'Activity History',0,'',NULL,'','N:N'),(71,21,0,'get_postatushistory',4,'PurchaseOrder Status History',0,'',NULL,'','N:N'),(72,22,9,'get_activities',1,'Activities',0,'add',238,'','1:N'),(73,22,8,'get_attachments',2,'Documents',0,'add,select',NULL,'','N:N'),(74,22,23,'get_invoices',3,'Invoice',0,'',437,'','1:N'),(75,22,9,'get_history',4,'Activity History',0,'',NULL,'','N:N'),(76,22,0,'get_sostatushistory',5,'SalesOrder Status History',0,'',NULL,'','N:N'),(77,23,9,'get_activities',1,'Activities',0,'add',238,'','1:N'),(78,23,8,'get_attachments',2,'Documents',0,'add,select',NULL,'','N:N'),(79,23,9,'get_history',3,'Activity History',0,'',NULL,'','N:N'),(80,23,0,'get_invoicestatushistory',4,'Invoice Status History',0,'',NULL,'','N:N'),(81,9,0,'get_users',1,'Users',0,'',NULL,'','N:N'),(82,9,4,'get_contacts',2,'Contacts',0,'',NULL,'','N:N'),(83,26,4,'get_contacts',1,'Contacts',0,'add,select',NULL,'','N:N'),(84,26,7,'get_leads',2,'Leads',0,'add,select',NULL,'','N:N'),(85,26,2,'get_opportunities',3,'Potentials',0,'add',121,'','1:N'),(86,26,9,'get_activities',4,'Activities',0,'add',238,'','1:N'),(87,6,26,'get_campaigns',14,'Campaigns',0,'select',NULL,'','N:N'),(88,26,6,'get_accounts',5,'Accounts',0,'add,select',NULL,'','N:N'),(89,15,8,'get_attachments',2,'Documents',0,'add,select',NULL,'','N:N'),(90,34,13,'get_related_list',1,'HelpDesk',0,'ADD,SELECT',NULL,NULL,'N:N'),(91,34,8,'get_attachments',2,'Documents',0,'ADD,SELECT',NULL,NULL,'N:N'),(92,6,34,'get_merged_list',15,'Service Contracts',0,'ADD',531,NULL,'1:N'),(93,4,34,'get_dependents_list',14,'Service Contracts',0,'ADD',531,NULL,'1:N'),(94,13,34,'get_related_list',6,'Service Contracts',0,'ADD,SELECT',NULL,NULL,'N:N'),(95,35,13,'get_related_list',1,'HelpDesk',0,'ADD,SELECT',NULL,NULL,'N:N'),(96,35,20,'get_quotes',2,'Quotes',0,'ADD',685,NULL,'1:N'),(97,35,21,'get_purchase_orders',3,'Purchase Order',0,'ADD',676,NULL,'1:N'),(98,35,22,'get_salesorder',4,'Sales Order',0,'ADD',667,NULL,'1:N'),(99,35,23,'get_invoices',5,'Invoice',0,'ADD',658,NULL,'1:N'),(100,35,19,'get_service_pricebooks',6,'PriceBooks',0,'ADD',NULL,NULL,'N:N'),(101,35,7,'get_related_list',7,'Leads',0,'SELECT',NULL,NULL,'N:N'),(102,35,6,'get_related_list',8,'Accounts',0,'SELECT',NULL,NULL,'N:N'),(103,35,4,'get_related_list',9,'Contacts',0,'SELECT',NULL,NULL,'N:N'),(104,35,2,'get_related_list',10,'Potentials',0,'SELECT',NULL,NULL,'N:N'),(105,35,8,'get_attachments',11,'Documents',0,'ADD,SELECT',NULL,NULL,'N:N'),(106,13,35,'get_related_list',7,'Services',0,'SELECT',NULL,NULL,'N:N'),(107,7,35,'get_related_list',8,'Services',0,'SELECT',NULL,NULL,'N:N'),(108,6,35,'get_related_list',16,'Services',0,'SELECT',NULL,NULL,'N:N'),(109,4,35,'get_related_list',15,'Services',0,'SELECT',NULL,NULL,'N:N'),(110,2,35,'get_related_list',10,'Services',0,'SELECT',NULL,NULL,'N:N'),(111,19,35,'get_pricebook_services',3,'Services',0,'SELECT',NULL,NULL,'N:N'),(112,7,37,'get_comments',9,'ModComments',0,'',574,NULL,'1:N'),(113,4,37,'get_comments',16,'ModComments',0,'',696,NULL,'1:N'),(114,6,37,'get_comments',17,'ModComments',0,'',574,NULL,'1:N'),(115,2,37,'get_comments',11,'ModComments',0,'',574,NULL,'1:N'),(123,6,41,'get_merged_list',18,'Projects',0,'add',615,NULL,'1:N'),(124,4,41,'get_dependents_list',17,'Projects',0,'add',615,NULL,'1:N'),(125,13,41,'get_related_list',8,'Projects',0,'SELECT',NULL,NULL,'N:N'),(127,42,6,'get_related_list',1,'Accounts',0,' ',NULL,NULL,'N:N'),(128,42,4,'get_related_list',2,'Contacts',0,' ',NULL,NULL,'N:N'),(129,42,7,'get_related_list',3,'Leads',0,' ',NULL,NULL,'N:N'),(130,43,13,'get_related_list',1,'HelpDesk',0,'ADD,SELECT',NULL,NULL,'N:N'),(131,43,8,'get_attachments',2,'Documents',0,'ADD,SELECT',NULL,NULL,'N:N'),(132,6,43,'get_merged_list',19,'Assets',0,'ADD',647,NULL,'1:N'),(133,14,43,'get_dependents_list',15,'Assets',0,'ADD',636,NULL,'1:N'),(134,23,43,'get_dependents_list',5,'Assets',0,'ADD',642,NULL,'1:N'),(143,13,37,'get_comments',9,'ModComments',0,'',574,NULL,'1:N'),(144,15,37,'get_comments',3,'ModComments',0,'',574,NULL,'1:N'),(145,4,43,'get_dependents_list',18,'Assets',0,'ADD',648,NULL,'1:N'),(146,4,18,'get_vendors',19,'Vendors',0,'SELECT',NULL,'','N:N'),(147,2,23,'get_dependents_list',12,'Invoice',0,'ADD',755,NULL,'1:N'),(148,8,4,'get_related_list',1,'Contacts',0,'1',NULL,NULL,'N:N'),(149,8,6,'get_related_list',2,'Accounts',0,'1',NULL,NULL,'N:N'),(150,8,2,'get_related_list',3,'Potentials',0,'1',NULL,NULL,'N:N'),(151,8,7,'get_related_list',4,'Leads',0,'1',NULL,NULL,'N:N'),(152,8,14,'get_related_list',5,'Products',0,'1',NULL,NULL,'N:N'),(153,8,35,'get_related_list',6,'Services',0,'1',NULL,NULL,'N:N'),(154,8,41,'get_related_list',7,'Project',0,'1',NULL,NULL,'N:N'),(155,8,43,'get_related_list',8,'Assets',0,'1',NULL,NULL,'N:N'),(156,8,34,'get_related_list',9,'ServiceContracts',0,'1',NULL,NULL,'N:N'),(157,8,20,'get_related_list',10,'Quotes',0,'1',NULL,NULL,'N:N'),(158,8,23,'get_related_list',11,'Invoice',0,'1',NULL,NULL,'N:N'),(159,8,22,'get_related_list',12,'SalesOrder',0,'1',NULL,NULL,'N:N'),(160,8,21,'get_related_list',13,'PurchaseOrder',0,'1',NULL,NULL,'N:N'),(161,8,13,'get_related_list',14,'HelpDesk',0,'1',NULL,NULL,'N:N'),(162,8,15,'get_related_list',15,'Faq',0,'1',NULL,NULL,'N:N'),(168,40,8,'get_attachments',1,'Documents',0,'ADD,SELECT',NULL,NULL,'N:N'),(169,40,37,'get_comments',2,'ModComments',0,'',NULL,NULL,'N:N'),(170,41,40,'get_dependents_list',1,'Project Tasks',0,'ADD',NULL,NULL,'N:N'),(171,41,39,'get_dependents_list',2,'Project Milestones',0,'ADD',580,NULL,'N:N'),(172,41,8,'get_attachments',3,'Documents',0,'ADD,SELECT',NULL,NULL,'N:N'),(173,41,13,'get_related_list',4,'HelpDesk',0,'ADD,SELECT',NULL,NULL,'N:N'),(174,41,0,'get_gantt_chart',5,'Charts',0,'',598,NULL,NULL),(175,41,37,'get_comments',6,'ModComments',0,'',NULL,NULL,'N:N'),(176,2,10,'get_emails',13,'Emails',0,'ADD',NULL,NULL,'N:N'),(177,13,10,'get_emails',10,'Emails',0,'ADD',NULL,NULL,'N:N'),(178,49,9,'get_activities',1,'Activities',0,'ADD',238,NULL,'1:N'),(179,49,37,'get_comments',2,'ModComments',0,'',NULL,NULL,'N:N'),(180,13,43,'get_related_list',11,'Assets',0,'ADD,SELECT',NULL,NULL,'N:N');
/*!40000 ALTER TABLE `vtiger_relatedlists` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_relatedlists_rb`
--

DROP TABLE IF EXISTS `vtiger_relatedlists_rb`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_relatedlists_rb` (
  `entityid` int DEFAULT NULL,
  `action` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rel_table` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rel_column` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ref_column` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `related_crm_ids` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_relatedlists_rb`
--

LOCK TABLES `vtiger_relatedlists_rb` WRITE;
/*!40000 ALTER TABLE `vtiger_relatedlists_rb` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_relatedlists_rb` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_relatedlists_seq`
--

DROP TABLE IF EXISTS `vtiger_relatedlists_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_relatedlists_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_relatedlists_seq`
--

LOCK TABLES `vtiger_relatedlists_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_relatedlists_seq` DISABLE KEYS */;
INSERT INTO `vtiger_relatedlists_seq` VALUES (181);
/*!40000 ALTER TABLE `vtiger_relatedlists_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_relcriteria`
--

DROP TABLE IF EXISTS `vtiger_relcriteria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_relcriteria` (
  `queryid` int NOT NULL,
  `columnindex` int NOT NULL,
  `columnname` varchar(250) COLLATE utf8mb4_general_ci DEFAULT '',
  `comparator` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `value` varchar(512) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `groupid` int DEFAULT '1',
  `column_condition` varchar(256) COLLATE utf8mb4_general_ci DEFAULT 'and',
  PRIMARY KEY (`queryid`,`columnindex`),
  KEY `relcriteria_queryid_idx` (`queryid`),
  CONSTRAINT `fk_1_vtiger_relcriteria` FOREIGN KEY (`queryid`) REFERENCES `vtiger_selectquery` (`queryid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_relcriteria`
--

LOCK TABLES `vtiger_relcriteria` WRITE;
/*!40000 ALTER TABLE `vtiger_relcriteria` DISABLE KEYS */;
INSERT INTO `vtiger_relcriteria` VALUES (1,0,'vtiger_contactdetails:accountid:Contacts_Account_Name:account_id:V','n','',1,'and'),(2,0,'vtiger_contactdetails:accountid:Contacts_Account_Name:account_id:V','e','',1,'and'),(3,0,'vtiger_potential:potentialname:Potentials_Potential_Name:potentialname:V','n','',1,'and'),(7,0,'vtiger_potential:sales_stage:Potentials_Sales_Stage:sales_stage:V','e','Closed Won',1,'and'),(12,0,'vtiger_troubletickets:status:HelpDesk_Status:ticketstatus:V','n','Closed',1,'and'),(15,0,'vtiger_quotes:quotestage:Quotes_Quote_Stage:quotestage:V','n','Accepted',1,'and'),(15,1,'vtiger_quotes:quotestage:Quotes_Quote_Stage:quotestage:V','n','Rejected',1,'and'),(22,0,'vtiger_email_track:access_count:Emails_Access_Count:access_count:I','ny','',1,'and'),(23,0,'vtiger_email_track:access_count:Emails_Access_Count:access_count:I','ny','',1,'and'),(24,0,'vtiger_email_track:access_count:Emails_Access_Count:access_count:I','ny','',1,'and'),(25,0,'vtiger_email_track:access_count:Emails_Access_Count:access_count:I','ny','',1,'and');
/*!40000 ALTER TABLE `vtiger_relcriteria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_relcriteria_grouping`
--

DROP TABLE IF EXISTS `vtiger_relcriteria_grouping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_relcriteria_grouping` (
  `groupid` int NOT NULL,
  `queryid` int NOT NULL,
  `group_condition` varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `condition_expression` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`groupid`,`queryid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_relcriteria_grouping`
--

LOCK TABLES `vtiger_relcriteria_grouping` WRITE;
/*!40000 ALTER TABLE `vtiger_relcriteria_grouping` DISABLE KEYS */;
INSERT INTO `vtiger_relcriteria_grouping` VALUES (1,1,'','0'),(1,2,'','0'),(1,3,'','0'),(1,7,'','0'),(1,12,'','0'),(1,15,'','0 and 1'),(1,22,'','0'),(1,23,'','0'),(1,24,'','0'),(1,25,'','0');
/*!40000 ALTER TABLE `vtiger_relcriteria_grouping` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_reminder_interval`
--

DROP TABLE IF EXISTS `vtiger_reminder_interval`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_reminder_interval` (
  `reminder_intervalid` int NOT NULL AUTO_INCREMENT,
  `reminder_interval` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `sortorderid` int NOT NULL,
  `presence` int NOT NULL,
  PRIMARY KEY (`reminder_intervalid`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_reminder_interval`
--

LOCK TABLES `vtiger_reminder_interval` WRITE;
/*!40000 ALTER TABLE `vtiger_reminder_interval` DISABLE KEYS */;
INSERT INTO `vtiger_reminder_interval` VALUES (2,'1 Minute',1,1),(3,'5 Minutes',2,1),(4,'15 Minutes',3,1),(5,'30 Minutes',4,1),(6,'45 Minutes',5,1),(7,'1 Hour',6,1),(8,'1 Day',7,1);
/*!40000 ALTER TABLE `vtiger_reminder_interval` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_reminder_interval_seq`
--

DROP TABLE IF EXISTS `vtiger_reminder_interval_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_reminder_interval_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_reminder_interval_seq`
--

LOCK TABLES `vtiger_reminder_interval_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_reminder_interval_seq` DISABLE KEYS */;
INSERT INTO `vtiger_reminder_interval_seq` VALUES (8);
/*!40000 ALTER TABLE `vtiger_reminder_interval_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_report`
--

DROP TABLE IF EXISTS `vtiger_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_report` (
  `reportid` int NOT NULL,
  `folderid` int NOT NULL,
  `reportname` varchar(100) COLLATE utf8mb4_general_ci DEFAULT '',
  `description` varchar(250) COLLATE utf8mb4_general_ci DEFAULT '',
  `reporttype` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '',
  `queryid` int NOT NULL,
  `state` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'SAVED',
  `customizable` int DEFAULT '1',
  `category` int DEFAULT '1',
  `owner` int DEFAULT '1',
  `sharingtype` varchar(200) COLLATE utf8mb4_general_ci DEFAULT 'Private',
  PRIMARY KEY (`reportid`),
  KEY `report_queryid_idx` (`queryid`),
  KEY `report_folderid_idx` (`folderid`),
  CONSTRAINT `fk_2_vtiger_report` FOREIGN KEY (`queryid`) REFERENCES `vtiger_selectquery` (`queryid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_report`
--

LOCK TABLES `vtiger_report` WRITE;
/*!40000 ALTER TABLE `vtiger_report` DISABLE KEYS */;
INSERT INTO `vtiger_report` VALUES (1,1,'Contacts by Accounts','Contacts related to Accounts','tabular',1,'CUSTOM',1,1,1,'Public'),(2,1,'Contacts without Accounts','Contacts not related to Accounts','tabular',2,'CUSTOM',1,1,1,'Public'),(3,1,'Contacts by Potentials','Contacts related to Potentials','tabular',3,'CUSTOM',1,1,1,'Public'),(4,2,'Lead by Source','Lead by Source','summary',4,'CUSTOM',1,1,1,'Public'),(5,2,'Lead Status Report','Lead Status Report','summary',5,'CUSTOM',1,1,1,'Public'),(6,3,'Potential Pipeline','Potential Pipeline','summary',6,'CUSTOM',1,1,1,'Public'),(7,3,'Closed Potentials','Potential that have Won','tabular',7,'CUSTOM',1,1,1,'Public'),(8,4,'Last Month Activities','Last Month Activities','tabular',8,'CUSTOM',1,1,1,'Public'),(9,4,'This Month Activities','This Month Activities','tabular',9,'CUSTOM',1,1,1,'Public'),(10,5,'Tickets by Products','Tickets related to Products','tabular',10,'CUSTOM',1,1,1,'Public'),(11,5,'Tickets by Priority','Tickets by Priority','summary',11,'CUSTOM',1,1,1,'Public'),(12,5,'Open Tickets','Open Tickets','tabular',12,'CUSTOM',1,1,1,'Public'),(13,6,'Product Details','Product Detailed Report','tabular',13,'CUSTOM',1,1,1,'Public'),(14,6,'Products by Contacts','Products related to Contacts','tabular',14,'CUSTOM',1,1,1,'Public'),(15,7,'Open Quotes','Quotes that are Open','tabular',15,'CUSTOM',1,1,1,'Public'),(16,7,'Quotes Detailed Report','Quotes Detailed Report','tabular',16,'CUSTOM',1,1,1,'Public'),(17,8,'PurchaseOrder by Contacts','PurchaseOrder related to Contacts','tabular',17,'CUSTOM',1,1,1,'Public'),(18,8,'PurchaseOrder Detailed Report','PurchaseOrder Detailed Report','tabular',18,'CUSTOM',1,1,1,'Public'),(19,9,'Invoice Detailed Report','Invoice Detailed Report','tabular',19,'CUSTOM',1,1,1,'Public'),(21,11,'Campaign Expectations and Actuals','Campaign Expectations and Actuals','tabular',21,'CUSTOM',1,1,1,'Public'),(22,13,'LBL_REPORTING_EMAILS_TO_CUSTOMER_REPRESENTATIVES','LBL_REPORTING_EMAILS_TO_CUSTOMER_REPRESENTATIVES','tabular',22,'CUSTOM',1,1,1,'Public'),(23,13,'LBL_REPORTING_EMAILS_TO_CLIENT_COMPANIES','LBL_REPORTING_EMAILS_TO_CLIENT_COMPANIES','tabular',23,'CUSTOM',1,1,1,'Public'),(24,13,'LBL_LEAD_EMAIL_REPORTING','LBL_LEAD_EMAIL_REPORTING','tabular',24,'CUSTOM',1,1,1,'Public'),(25,13,'LBL_REPORTING_EMAILS_TO_SUPPLIERS','LBL_REPORTING_EMAILS_TO_SUPPLIERS','tabular',25,'CUSTOM',1,1,1,'Public');
/*!40000 ALTER TABLE `vtiger_report` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_report_sharegroups`
--

DROP TABLE IF EXISTS `vtiger_report_sharegroups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_report_sharegroups` (
  `reportid` int NOT NULL,
  `groupid` int NOT NULL,
  KEY `vtiger_report_sharegroups_ibfk_1` (`reportid`),
  KEY `vtiger_groups_groupid_ibfk_1` (`groupid`),
  CONSTRAINT `vtiger_groups_groupid_ibfk_1` FOREIGN KEY (`groupid`) REFERENCES `vtiger_groups` (`groupid`) ON DELETE CASCADE,
  CONSTRAINT `vtiger_report_reportid_ibfk_2` FOREIGN KEY (`reportid`) REFERENCES `vtiger_report` (`reportid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_report_sharegroups`
--

LOCK TABLES `vtiger_report_sharegroups` WRITE;
/*!40000 ALTER TABLE `vtiger_report_sharegroups` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_report_sharegroups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_report_shareusers`
--

DROP TABLE IF EXISTS `vtiger_report_shareusers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_report_shareusers` (
  `reportid` int NOT NULL,
  `userid` int NOT NULL,
  KEY `vtiger_report_shareusers_ibfk_1` (`reportid`),
  KEY `vtiger_users_userid_ibfk_1` (`userid`),
  CONSTRAINT `vtiger_reports_reportid_ibfk_1` FOREIGN KEY (`reportid`) REFERENCES `vtiger_report` (`reportid`) ON DELETE CASCADE,
  CONSTRAINT `vtiger_users_userid_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `vtiger_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_report_shareusers`
--

LOCK TABLES `vtiger_report_shareusers` WRITE;
/*!40000 ALTER TABLE `vtiger_report_shareusers` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_report_shareusers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_reportdatefilter`
--

DROP TABLE IF EXISTS `vtiger_reportdatefilter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_reportdatefilter` (
  `datefilterid` int NOT NULL,
  `datecolumnname` varchar(250) COLLATE utf8mb4_general_ci DEFAULT '',
  `datefilter` varchar(250) COLLATE utf8mb4_general_ci DEFAULT '',
  `startdate` date DEFAULT NULL,
  `enddate` date DEFAULT NULL,
  PRIMARY KEY (`datefilterid`),
  KEY `reportdatefilter_datefilterid_idx` (`datefilterid`),
  CONSTRAINT `fk_1_vtiger_reportdatefilter` FOREIGN KEY (`datefilterid`) REFERENCES `vtiger_report` (`reportid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_reportdatefilter`
--

LOCK TABLES `vtiger_reportdatefilter` WRITE;
/*!40000 ALTER TABLE `vtiger_reportdatefilter` DISABLE KEYS */;
INSERT INTO `vtiger_reportdatefilter` VALUES (8,'vtiger_crmentity:modifiedtime:modifiedtime:Calendar_Modified_Time','lastmonth','2005-05-01','2005-05-31'),(9,'vtiger_crmentity:modifiedtime:modifiedtime:Calendar_Modified_Time','thismonth','2005-06-01','2005-06-30');
/*!40000 ALTER TABLE `vtiger_reportdatefilter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_reportfilters`
--

DROP TABLE IF EXISTS `vtiger_reportfilters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_reportfilters` (
  `filterid` int NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_reportfilters`
--

LOCK TABLES `vtiger_reportfilters` WRITE;
/*!40000 ALTER TABLE `vtiger_reportfilters` DISABLE KEYS */;
INSERT INTO `vtiger_reportfilters` VALUES (1,'Private'),(2,'Public'),(3,'Shared');
/*!40000 ALTER TABLE `vtiger_reportfilters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_reportfolder`
--

DROP TABLE IF EXISTS `vtiger_reportfolder`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_reportfolder` (
  `folderid` int NOT NULL AUTO_INCREMENT,
  `foldername` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `description` varchar(250) COLLATE utf8mb4_general_ci DEFAULT '',
  `state` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'SAVED',
  PRIMARY KEY (`folderid`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_reportfolder`
--

LOCK TABLES `vtiger_reportfolder` WRITE;
/*!40000 ALTER TABLE `vtiger_reportfolder` DISABLE KEYS */;
INSERT INTO `vtiger_reportfolder` VALUES (1,'顧客企業と顧客担当者のレポート','Account and Contact Reports','SAVED'),(2,'見込み客のレポート','Lead Reports','SAVED'),(3,'案件のレポート','Potential Reports','SAVED'),(4,'活動のレポート','Activity Reports','SAVED'),(5,'チケットのレポート','HelpDesk Reports','SAVED'),(6,'製品のレポート','Product Reports','SAVED'),(7,'見積のレポート','Quote Reports','SAVED'),(8,'発注のレポート','PurchaseOrder Reports','SAVED'),(9,'請求のレポート','Invoice Reports','SAVED'),(10,'受注のレポート','SalesOrder Reports','SAVED'),(11,'キャンペーンのレポート','Campaign Reports','SAVED'),(12,'メールのレポート','Email Reports','SAVED'),(13,'メールのレポート','Email Reports','SAVED');
/*!40000 ALTER TABLE `vtiger_reportfolder` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_reportgroupbycolumn`
--

DROP TABLE IF EXISTS `vtiger_reportgroupbycolumn`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_reportgroupbycolumn` (
  `reportid` int DEFAULT NULL,
  `sortid` int DEFAULT NULL,
  `sortcolname` varchar(250) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dategroupbycriteria` varchar(250) COLLATE utf8mb4_general_ci DEFAULT NULL,
  KEY `fk_1_vtiger_reportgroupbycolumn` (`reportid`),
  CONSTRAINT `fk_1_vtiger_reportgroupbycolumn` FOREIGN KEY (`reportid`) REFERENCES `vtiger_report` (`reportid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_reportgroupbycolumn`
--

LOCK TABLES `vtiger_reportgroupbycolumn` WRITE;
/*!40000 ALTER TABLE `vtiger_reportgroupbycolumn` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_reportgroupbycolumn` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_reportmodules`
--

DROP TABLE IF EXISTS `vtiger_reportmodules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_reportmodules` (
  `reportmodulesid` int NOT NULL,
  `primarymodule` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `secondarymodules` varchar(250) COLLATE utf8mb4_general_ci DEFAULT '',
  `join_column` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`reportmodulesid`),
  CONSTRAINT `fk_1_vtiger_reportmodules` FOREIGN KEY (`reportmodulesid`) REFERENCES `vtiger_report` (`reportid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_reportmodules`
--

LOCK TABLES `vtiger_reportmodules` WRITE;
/*!40000 ALTER TABLE `vtiger_reportmodules` DISABLE KEYS */;
INSERT INTO `vtiger_reportmodules` VALUES (1,'Contacts','Accounts',NULL),(2,'Contacts','Accounts',NULL),(3,'Contacts','Potentials',NULL),(4,'Leads','',NULL),(5,'Leads','',NULL),(6,'Potentials','',NULL),(7,'Potentials','',NULL),(8,'Calendar','',NULL),(9,'Calendar','',NULL),(10,'HelpDesk','Products',NULL),(11,'HelpDesk','',NULL),(12,'HelpDesk','',NULL),(13,'Products','',NULL),(14,'Products','Contacts',NULL),(15,'Quotes','',NULL),(16,'Quotes','',NULL),(17,'PurchaseOrder','Contacts',NULL),(18,'PurchaseOrder','',NULL),(19,'Invoice','',NULL),(21,'Campaigns','',NULL),(22,'Contacts','Emails',NULL),(23,'Accounts','Emails',NULL),(24,'Leads','Emails',NULL),(25,'Vendors','Emails',NULL);
/*!40000 ALTER TABLE `vtiger_reportmodules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_reportsharing`
--

DROP TABLE IF EXISTS `vtiger_reportsharing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_reportsharing` (
  `reportid` int NOT NULL,
  `shareid` int NOT NULL,
  `setype` varchar(200) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_reportsharing`
--

LOCK TABLES `vtiger_reportsharing` WRITE;
/*!40000 ALTER TABLE `vtiger_reportsharing` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_reportsharing` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_reportsortcol`
--

DROP TABLE IF EXISTS `vtiger_reportsortcol`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_reportsortcol` (
  `sortcolid` int NOT NULL,
  `reportid` int NOT NULL,
  `columnname` varchar(250) COLLATE utf8mb4_general_ci DEFAULT '',
  `sortorder` varchar(250) COLLATE utf8mb4_general_ci DEFAULT 'Asc',
  PRIMARY KEY (`sortcolid`,`reportid`),
  KEY `fk_1_vtiger_reportsortcol` (`reportid`),
  CONSTRAINT `fk_1_vtiger_reportsortcol` FOREIGN KEY (`reportid`) REFERENCES `vtiger_report` (`reportid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_reportsortcol`
--

LOCK TABLES `vtiger_reportsortcol` WRITE;
/*!40000 ALTER TABLE `vtiger_reportsortcol` DISABLE KEYS */;
INSERT INTO `vtiger_reportsortcol` VALUES (1,4,'vtiger_leaddetails:leadsource:Leads_Lead_Source:leadsource:V','Ascending'),(1,5,'vtiger_leaddetails:leadstatus:Leads_Lead_Status:leadstatus:V','Ascending'),(1,6,'vtiger_potential:sales_stage:Potentials_Sales_Stage:sales_stage:V','Ascending'),(1,11,'vtiger_troubletickets:priority:HelpDesk_Priority:ticketpriorities:V','Ascending');
/*!40000 ALTER TABLE `vtiger_reportsortcol` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_reportsterm`
--

DROP TABLE IF EXISTS `vtiger_reportsterm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_reportsterm` (
  `reportstermid` int NOT NULL AUTO_INCREMENT,
  `reportsterm` varchar(200) NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT '0',
  `color` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`reportstermid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_reportsterm`
--

LOCK TABLES `vtiger_reportsterm` WRITE;
/*!40000 ALTER TABLE `vtiger_reportsterm` DISABLE KEYS */;
INSERT INTO `vtiger_reportsterm` VALUES (1,'Day',1,305,1,NULL),(2,'Week',1,306,2,NULL);
/*!40000 ALTER TABLE `vtiger_reportsterm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_reportsterm_seq`
--

DROP TABLE IF EXISTS `vtiger_reportsterm_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_reportsterm_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_reportsterm_seq`
--

LOCK TABLES `vtiger_reportsterm_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_reportsterm_seq` DISABLE KEYS */;
INSERT INTO `vtiger_reportsterm_seq` VALUES (2);
/*!40000 ALTER TABLE `vtiger_reportsterm_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_reportsummary`
--

DROP TABLE IF EXISTS `vtiger_reportsummary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_reportsummary` (
  `reportsummaryid` int NOT NULL,
  `summarytype` int NOT NULL,
  `columnname` varchar(250) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`reportsummaryid`,`summarytype`,`columnname`),
  KEY `reportsummary_reportsummaryid_idx` (`reportsummaryid`),
  CONSTRAINT `fk_1_vtiger_reportsummary` FOREIGN KEY (`reportsummaryid`) REFERENCES `vtiger_report` (`reportid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_reportsummary`
--

LOCK TABLES `vtiger_reportsummary` WRITE;
/*!40000 ALTER TABLE `vtiger_reportsummary` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_reportsummary` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_reporttype`
--

DROP TABLE IF EXISTS `vtiger_reporttype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_reporttype` (
  `reportid` int NOT NULL,
  `data` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`reportid`),
  CONSTRAINT `fk_1_vtiger_reporttype` FOREIGN KEY (`reportid`) REFERENCES `vtiger_report` (`reportid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_reporttype`
--

LOCK TABLES `vtiger_reporttype` WRITE;
/*!40000 ALTER TABLE `vtiger_reporttype` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_reporttype` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_role`
--

DROP TABLE IF EXISTS `vtiger_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_role` (
  `roleid` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `rolename` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `parentrole` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `depth` int DEFAULT NULL,
  `allowassignedrecordsto` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`roleid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_role`
--

LOCK TABLES `vtiger_role` WRITE;
/*!40000 ALTER TABLE `vtiger_role` DISABLE KEYS */;
INSERT INTO `vtiger_role` VALUES ('H1','Organization','H1',0,1),('H2','LBL_ADMIN','H1::H2',1,1),('H3','LBL_MANAGER','H1::H2::H3',2,1),('H4','LBL_GENERAL','H1::H2::H3::H4',3,1),('H5','LBL_PART-TIME_JOB','H1::H2::H3::H4::H5',4,1);
/*!40000 ALTER TABLE `vtiger_role` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_role2picklist`
--

DROP TABLE IF EXISTS `vtiger_role2picklist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_role2picklist` (
  `roleid` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `picklistvalueid` int NOT NULL,
  `picklistid` int NOT NULL,
  `sortid` int DEFAULT NULL,
  PRIMARY KEY (`roleid`,`picklistvalueid`,`picklistid`),
  KEY `role2picklist_roleid_picklistid_idx` (`roleid`,`picklistid`,`picklistvalueid`),
  KEY `fk_2_vtiger_role2picklist` (`picklistid`),
  CONSTRAINT `fk_1_vtiger_role2picklist` FOREIGN KEY (`roleid`) REFERENCES `vtiger_role` (`roleid`) ON DELETE CASCADE,
  CONSTRAINT `fk_2_vtiger_role2picklist` FOREIGN KEY (`picklistid`) REFERENCES `vtiger_picklist` (`picklistid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_role2picklist`
--

LOCK TABLES `vtiger_role2picklist` WRITE;
/*!40000 ALTER TABLE `vtiger_role2picklist` DISABLE KEYS */;
INSERT INTO `vtiger_role2picklist` VALUES ('H1',1,1,0),('H1',2,1,1),('H1',3,1,2),('H1',4,1,3),('H1',5,1,4),('H1',6,1,5),('H1',7,1,6),('H1',8,1,7),('H1',9,1,8),('H1',10,1,9),('H1',11,1,10),('H1',12,2,0),('H1',13,2,1),('H1',14,3,0),('H1',15,3,1),('H1',16,3,2),('H1',17,3,3),('H1',18,3,4),('H1',19,3,5),('H1',20,4,0),('H1',21,4,1),('H1',22,4,2),('H1',23,4,3),('H1',24,4,4),('H1',25,4,5),('H1',26,4,6),('H1',27,4,7),('H1',28,4,8),('H1',29,4,9),('H1',30,4,10),('H1',31,4,11),('H1',32,4,12),('H1',33,5,0),('H1',34,5,1),('H1',35,5,2),('H1',36,5,3),('H1',37,5,4),('H1',38,6,0),('H1',39,6,1),('H1',40,6,2),('H1',41,7,0),('H1',42,7,1),('H1',43,7,2),('H1',44,7,3),('H1',45,7,4),('H1',46,8,0),('H1',47,9,0),('H1',48,9,1),('H1',49,9,2),('H1',50,9,3),('H1',51,10,0),('H1',52,10,1),('H1',53,10,2),('H1',54,10,3),('H1',55,10,4),('H1',56,10,5),('H1',57,10,6),('H1',58,10,7),('H1',59,10,8),('H1',60,11,0),('H1',61,11,1),('H1',62,11,2),('H1',63,11,3),('H1',64,11,4),('H1',65,11,5),('H1',66,11,6),('H1',67,11,7),('H1',68,11,8),('H1',69,11,9),('H1',70,11,10),('H1',71,11,11),('H1',72,11,12),('H1',73,11,13),('H1',74,11,14),('H1',75,11,15),('H1',76,11,16),('H1',77,11,17),('H1',78,11,18),('H1',79,11,19),('H1',80,11,20),('H1',81,11,21),('H1',82,11,22),('H1',83,11,23),('H1',84,11,24),('H1',85,11,25),('H1',86,11,26),('H1',87,11,27),('H1',88,11,28),('H1',89,11,29),('H1',90,11,30),('H1',91,11,31),('H1',92,12,0),('H1',93,12,1),('H1',94,12,2),('H1',95,12,3),('H1',96,12,4),('H1',97,12,5),('H1',98,13,0),('H1',99,13,1),('H1',100,13,2),('H1',101,13,3),('H1',102,13,4),('H1',103,13,5),('H1',104,13,6),('H1',105,13,7),('H1',106,13,8),('H1',107,13,9),('H1',108,13,10),('H1',109,13,11),('H1',110,13,12),('H1',111,14,0),('H1',112,14,1),('H1',113,14,2),('H1',114,14,3),('H1',115,14,4),('H1',116,14,5),('H1',117,14,6),('H1',118,14,7),('H1',119,14,8),('H1',120,14,9),('H1',121,14,10),('H1',122,14,11),('H1',123,15,0),('H1',124,15,1),('H1',125,15,2),('H1',126,15,3),('H1',127,16,0),('H1',128,16,1),('H1',129,16,2),('H1',130,17,0),('H1',131,17,1),('H1',132,17,2),('H1',133,17,3),('H1',134,17,4),('H1',135,18,0),('H1',136,18,1),('H1',137,18,2),('H1',138,18,3),('H1',139,19,0),('H1',140,19,1),('H1',141,19,2),('H1',142,19,3),('H1',143,19,4),('H1',144,20,0),('H1',145,20,1),('H1',146,20,2),('H1',147,20,3),('H1',148,20,4),('H1',149,20,5),('H1',150,21,0),('H1',151,21,1),('H1',152,21,2),('H1',153,21,3),('H1',154,21,4),('H1',155,21,5),('H1',156,21,6),('H1',157,21,7),('H1',158,21,8),('H1',159,21,9),('H1',160,22,0),('H1',161,22,1),('H1',162,22,2),('H1',163,22,3),('H1',164,22,4),('H1',165,22,5),('H1',166,23,0),('H1',167,23,1),('H1',168,23,2),('H1',169,23,3),('H1',170,24,0),('H1',171,24,1),('H1',172,24,2),('H1',173,25,0),('H1',174,25,1),('H1',175,25,2),('H1',176,25,3),('H1',177,25,4),('H1',178,25,5),('H1',179,26,0),('H1',180,26,1),('H1',181,26,2),('H1',182,27,0),('H1',183,27,1),('H1',184,27,2),('H1',185,27,3),('H1',186,28,0),('H1',187,28,1),('H1',188,28,2),('H1',189,28,3),('H1',190,29,0),('H1',191,29,1),('H1',192,29,2),('H1',193,29,3),('H1',194,30,0),('H1',195,30,1),('H1',196,30,2),('H1',197,30,3),('H1',198,30,4),('H1',199,30,5),('H1',200,30,6),('H1',201,30,7),('H1',202,30,8),('H1',203,30,9),('H1',204,30,10),('H1',205,30,11),('H1',206,30,12),('H1',207,30,13),('H1',208,30,14),('H1',209,30,15),('H1',210,31,1),('H1',211,31,2),('H1',212,31,3),('H1',213,31,4),('H1',214,32,1),('H1',215,32,2),('H1',216,32,3),('H1',217,32,4),('H1',218,32,5),('H1',219,32,6),('H1',220,33,1),('H1',221,33,2),('H1',222,33,3),('H1',223,34,1),('H1',224,34,2),('H1',225,34,3),('H1',226,35,1),('H1',227,35,2),('H1',228,35,3),('H1',229,36,1),('H1',230,36,2),('H1',231,36,3),('H1',232,36,4),('H1',233,36,5),('H1',234,36,6),('H1',235,37,1),('H1',236,37,2),('H1',237,37,3),('H1',238,37,4),('H1',239,38,1),('H1',240,38,2),('H1',241,38,3),('H1',242,38,4),('H1',243,39,1),('H1',244,39,2),('H1',245,39,3),('H1',246,39,4),('H1',247,40,1),('H1',248,40,2),('H1',249,40,3),('H1',250,40,4),('H1',251,40,5),('H1',252,40,6),('H1',253,40,7),('H1',254,40,8),('H1',255,40,9),('H1',256,40,10),('H1',257,40,11),('H1',258,41,1),('H1',259,41,2),('H1',260,41,3),('H1',261,41,4),('H1',262,41,5),('H1',263,41,6),('H1',264,41,7),('H1',265,41,8),('H1',266,41,9),('H1',267,42,1),('H1',268,42,2),('H1',269,42,3),('H1',270,42,4),('H1',271,43,1),('H1',272,43,2),('H1',273,43,3),('H1',274,43,4),('H1',275,44,1),('H1',276,44,2),('H1',277,44,3),('H1',278,44,4),('H1',279,44,5),('H1',280,44,6),('H1',281,44,7),('H1',282,44,8),('H1',283,44,9),('H1',284,44,10),('H1',285,44,11),('H1',286,45,1),('H1',287,45,2),('H1',288,12,1),('H1',289,46,1),('H1',290,46,2),('H1',291,46,3),('H1',292,46,4),('H1',293,46,5),('H1',294,46,6),('H1',295,2,1),('H1',296,47,1),('H1',297,47,2),('H1',298,47,3),('H1',299,48,1),('H1',300,48,2),('H1',303,49,1),('H1',304,49,2),('H1',305,50,1),('H1',306,50,2),('H1',307,51,1),('H1',308,51,2),('H2',1,1,0),('H2',2,1,1),('H2',3,1,2),('H2',4,1,3),('H2',5,1,4),('H2',6,1,5),('H2',7,1,6),('H2',8,1,7),('H2',9,1,8),('H2',10,1,9),('H2',11,1,10),('H2',12,2,0),('H2',13,2,1),('H2',14,3,0),('H2',15,3,1),('H2',16,3,2),('H2',17,3,3),('H2',18,3,4),('H2',19,3,5),('H2',20,4,0),('H2',21,4,1),('H2',22,4,2),('H2',23,4,3),('H2',24,4,4),('H2',25,4,5),('H2',26,4,6),('H2',27,4,7),('H2',28,4,8),('H2',29,4,9),('H2',30,4,10),('H2',31,4,11),('H2',32,4,12),('H2',33,5,0),('H2',34,5,1),('H2',35,5,2),('H2',36,5,3),('H2',37,5,4),('H2',38,6,0),('H2',39,6,1),('H2',40,6,2),('H2',41,7,0),('H2',42,7,1),('H2',43,7,2),('H2',44,7,3),('H2',45,7,4),('H2',46,8,0),('H2',47,9,0),('H2',48,9,1),('H2',49,9,2),('H2',50,9,3),('H2',51,10,0),('H2',52,10,1),('H2',53,10,2),('H2',54,10,3),('H2',55,10,4),('H2',56,10,5),('H2',57,10,6),('H2',58,10,7),('H2',59,10,8),('H2',60,11,0),('H2',61,11,1),('H2',62,11,2),('H2',63,11,3),('H2',64,11,4),('H2',65,11,5),('H2',66,11,6),('H2',67,11,7),('H2',68,11,8),('H2',69,11,9),('H2',70,11,10),('H2',71,11,11),('H2',72,11,12),('H2',73,11,13),('H2',74,11,14),('H2',75,11,15),('H2',76,11,16),('H2',77,11,17),('H2',78,11,18),('H2',79,11,19),('H2',80,11,20),('H2',81,11,21),('H2',82,11,22),('H2',83,11,23),('H2',84,11,24),('H2',85,11,25),('H2',86,11,26),('H2',87,11,27),('H2',88,11,28),('H2',89,11,29),('H2',90,11,30),('H2',91,11,31),('H2',92,12,0),('H2',93,12,1),('H2',94,12,2),('H2',95,12,3),('H2',96,12,4),('H2',97,12,5),('H2',98,13,0),('H2',99,13,1),('H2',100,13,2),('H2',101,13,3),('H2',102,13,4),('H2',103,13,5),('H2',104,13,6),('H2',105,13,7),('H2',106,13,8),('H2',107,13,9),('H2',108,13,10),('H2',109,13,11),('H2',110,13,12),('H2',111,14,0),('H2',112,14,1),('H2',113,14,2),('H2',114,14,3),('H2',115,14,4),('H2',116,14,5),('H2',117,14,6),('H2',118,14,7),('H2',119,14,8),('H2',120,14,9),('H2',121,14,10),('H2',122,14,11),('H2',123,15,0),('H2',124,15,1),('H2',125,15,2),('H2',126,15,3),('H2',127,16,0),('H2',128,16,1),('H2',129,16,2),('H2',130,17,0),('H2',131,17,1),('H2',132,17,2),('H2',133,17,3),('H2',134,17,4),('H2',135,18,0),('H2',136,18,1),('H2',137,18,2),('H2',138,18,3),('H2',139,19,0),('H2',140,19,1),('H2',141,19,2),('H2',142,19,3),('H2',143,19,4),('H2',144,20,0),('H2',145,20,1),('H2',146,20,2),('H2',147,20,3),('H2',148,20,4),('H2',149,20,5),('H2',150,21,0),('H2',151,21,1),('H2',152,21,2),('H2',153,21,3),('H2',154,21,4),('H2',155,21,5),('H2',156,21,6),('H2',157,21,7),('H2',158,21,8),('H2',159,21,9),('H2',160,22,0),('H2',161,22,1),('H2',162,22,2),('H2',163,22,3),('H2',164,22,4),('H2',165,22,5),('H2',166,23,0),('H2',167,23,1),('H2',168,23,2),('H2',169,23,3),('H2',170,24,0),('H2',171,24,1),('H2',172,24,2),('H2',173,25,0),('H2',174,25,1),('H2',175,25,2),('H2',176,25,3),('H2',177,25,4),('H2',178,25,5),('H2',179,26,0),('H2',180,26,1),('H2',181,26,2),('H2',182,27,0),('H2',183,27,1),('H2',184,27,2),('H2',185,27,3),('H2',186,28,0),('H2',187,28,1),('H2',188,28,2),('H2',189,28,3),('H2',190,29,0),('H2',191,29,1),('H2',192,29,2),('H2',193,29,3),('H2',194,30,0),('H2',195,30,1),('H2',196,30,2),('H2',197,30,3),('H2',198,30,4),('H2',199,30,5),('H2',200,30,6),('H2',201,30,7),('H2',202,30,8),('H2',203,30,9),('H2',204,30,10),('H2',205,30,11),('H2',206,30,12),('H2',207,30,13),('H2',208,30,14),('H2',209,30,15),('H2',210,31,1),('H2',211,31,2),('H2',212,31,3),('H2',213,31,4),('H2',214,32,1),('H2',215,32,2),('H2',216,32,3),('H2',217,32,4),('H2',218,32,5),('H2',219,32,6),('H2',220,33,1),('H2',221,33,2),('H2',222,33,3),('H2',223,34,1),('H2',224,34,2),('H2',225,34,3),('H2',226,35,1),('H2',227,35,2),('H2',228,35,3),('H2',229,36,1),('H2',230,36,2),('H2',231,36,3),('H2',232,36,4),('H2',233,36,5),('H2',234,36,6),('H2',235,37,1),('H2',236,37,2),('H2',237,37,3),('H2',238,37,4),('H2',239,38,1),('H2',240,38,2),('H2',241,38,3),('H2',242,38,4),('H2',243,39,1),('H2',244,39,2),('H2',245,39,3),('H2',246,39,4),('H2',247,40,1),('H2',248,40,2),('H2',249,40,3),('H2',250,40,4),('H2',251,40,5),('H2',252,40,6),('H2',253,40,7),('H2',254,40,8),('H2',255,40,9),('H2',256,40,10),('H2',257,40,11),('H2',258,41,1),('H2',259,41,2),('H2',260,41,3),('H2',261,41,4),('H2',262,41,5),('H2',263,41,6),('H2',264,41,7),('H2',265,41,8),('H2',266,41,9),('H2',267,42,1),('H2',268,42,2),('H2',269,42,3),('H2',270,42,4),('H2',271,43,1),('H2',272,43,2),('H2',273,43,3),('H2',274,43,4),('H2',275,44,1),('H2',276,44,2),('H2',277,44,3),('H2',278,44,4),('H2',279,44,5),('H2',280,44,6),('H2',281,44,7),('H2',282,44,8),('H2',283,44,9),('H2',284,44,10),('H2',285,44,11),('H2',286,45,1),('H2',287,45,2),('H2',288,12,1),('H2',289,46,1),('H2',290,46,2),('H2',291,46,3),('H2',292,46,4),('H2',293,46,5),('H2',294,46,6),('H2',295,2,1),('H2',296,47,1),('H2',297,47,2),('H2',298,47,3),('H2',299,48,1),('H2',300,48,2),('H2',303,49,1),('H2',304,49,2),('H2',305,50,1),('H2',306,50,2),('H2',307,51,1),('H2',308,51,2),('H3',1,1,0),('H3',2,1,1),('H3',3,1,2),('H3',4,1,3),('H3',5,1,4),('H3',6,1,5),('H3',7,1,6),('H3',8,1,7),('H3',9,1,8),('H3',10,1,9),('H3',11,1,10),('H3',12,2,0),('H3',13,2,1),('H3',14,3,0),('H3',15,3,1),('H3',16,3,2),('H3',17,3,3),('H3',18,3,4),('H3',19,3,5),('H3',20,4,0),('H3',21,4,1),('H3',22,4,2),('H3',23,4,3),('H3',24,4,4),('H3',25,4,5),('H3',26,4,6),('H3',27,4,7),('H3',28,4,8),('H3',29,4,9),('H3',30,4,10),('H3',31,4,11),('H3',32,4,12),('H3',33,5,0),('H3',34,5,1),('H3',35,5,2),('H3',36,5,3),('H3',37,5,4),('H3',38,6,0),('H3',39,6,1),('H3',40,6,2),('H3',41,7,0),('H3',42,7,1),('H3',43,7,2),('H3',44,7,3),('H3',45,7,4),('H3',46,8,0),('H3',47,9,0),('H3',48,9,1),('H3',49,9,2),('H3',50,9,3),('H3',51,10,0),('H3',52,10,1),('H3',53,10,2),('H3',54,10,3),('H3',55,10,4),('H3',56,10,5),('H3',57,10,6),('H3',58,10,7),('H3',59,10,8),('H3',60,11,0),('H3',61,11,1),('H3',62,11,2),('H3',63,11,3),('H3',64,11,4),('H3',65,11,5),('H3',66,11,6),('H3',67,11,7),('H3',68,11,8),('H3',69,11,9),('H3',70,11,10),('H3',71,11,11),('H3',72,11,12),('H3',73,11,13),('H3',74,11,14),('H3',75,11,15),('H3',76,11,16),('H3',77,11,17),('H3',78,11,18),('H3',79,11,19),('H3',80,11,20),('H3',81,11,21),('H3',82,11,22),('H3',83,11,23),('H3',84,11,24),('H3',85,11,25),('H3',86,11,26),('H3',87,11,27),('H3',88,11,28),('H3',89,11,29),('H3',90,11,30),('H3',91,11,31),('H3',92,12,0),('H3',93,12,1),('H3',94,12,2),('H3',95,12,3),('H3',96,12,4),('H3',97,12,5),('H3',98,13,0),('H3',99,13,1),('H3',100,13,2),('H3',101,13,3),('H3',102,13,4),('H3',103,13,5),('H3',104,13,6),('H3',105,13,7),('H3',106,13,8),('H3',107,13,9),('H3',108,13,10),('H3',109,13,11),('H3',110,13,12),('H3',111,14,0),('H3',112,14,1),('H3',113,14,2),('H3',114,14,3),('H3',115,14,4),('H3',116,14,5),('H3',117,14,6),('H3',118,14,7),('H3',119,14,8),('H3',120,14,9),('H3',121,14,10),('H3',122,14,11),('H3',123,15,0),('H3',124,15,1),('H3',125,15,2),('H3',126,15,3),('H3',127,16,0),('H3',128,16,1),('H3',129,16,2),('H3',130,17,0),('H3',131,17,1),('H3',132,17,2),('H3',133,17,3),('H3',134,17,4),('H3',135,18,0),('H3',136,18,1),('H3',137,18,2),('H3',138,18,3),('H3',139,19,0),('H3',140,19,1),('H3',141,19,2),('H3',142,19,3),('H3',143,19,4),('H3',144,20,0),('H3',145,20,1),('H3',146,20,2),('H3',147,20,3),('H3',148,20,4),('H3',149,20,5),('H3',150,21,0),('H3',151,21,1),('H3',152,21,2),('H3',153,21,3),('H3',154,21,4),('H3',155,21,5),('H3',156,21,6),('H3',157,21,7),('H3',158,21,8),('H3',159,21,9),('H3',160,22,0),('H3',161,22,1),('H3',162,22,2),('H3',163,22,3),('H3',164,22,4),('H3',165,22,5),('H3',166,23,0),('H3',167,23,1),('H3',168,23,2),('H3',169,23,3),('H3',170,24,0),('H3',171,24,1),('H3',172,24,2),('H3',173,25,0),('H3',174,25,1),('H3',175,25,2),('H3',176,25,3),('H3',177,25,4),('H3',178,25,5),('H3',179,26,0),('H3',180,26,1),('H3',181,26,2),('H3',182,27,0),('H3',183,27,1),('H3',184,27,2),('H3',185,27,3),('H3',186,28,0),('H3',187,28,1),('H3',188,28,2),('H3',189,28,3),('H3',190,29,0),('H3',191,29,1),('H3',192,29,2),('H3',193,29,3),('H3',194,30,0),('H3',195,30,1),('H3',196,30,2),('H3',197,30,3),('H3',198,30,4),('H3',199,30,5),('H3',200,30,6),('H3',201,30,7),('H3',202,30,8),('H3',203,30,9),('H3',204,30,10),('H3',205,30,11),('H3',206,30,12),('H3',207,30,13),('H3',208,30,14),('H3',209,30,15),('H3',210,31,1),('H3',211,31,2),('H3',212,31,3),('H3',213,31,4),('H3',214,32,1),('H3',215,32,2),('H3',216,32,3),('H3',217,32,4),('H3',218,32,5),('H3',219,32,6),('H3',220,33,1),('H3',221,33,2),('H3',222,33,3),('H3',223,34,1),('H3',224,34,2),('H3',225,34,3),('H3',226,35,1),('H3',227,35,2),('H3',228,35,3),('H3',229,36,1),('H3',230,36,2),('H3',231,36,3),('H3',232,36,4),('H3',233,36,5),('H3',234,36,6),('H3',235,37,1),('H3',236,37,2),('H3',237,37,3),('H3',238,37,4),('H3',239,38,1),('H3',240,38,2),('H3',241,38,3),('H3',242,38,4),('H3',243,39,1),('H3',244,39,2),('H3',245,39,3),('H3',246,39,4),('H3',247,40,1),('H3',248,40,2),('H3',249,40,3),('H3',250,40,4),('H3',251,40,5),('H3',252,40,6),('H3',253,40,7),('H3',254,40,8),('H3',255,40,9),('H3',256,40,10),('H3',257,40,11),('H3',258,41,1),('H3',259,41,2),('H3',260,41,3),('H3',261,41,4),('H3',262,41,5),('H3',263,41,6),('H3',264,41,7),('H3',265,41,8),('H3',266,41,9),('H3',267,42,1),('H3',268,42,2),('H3',269,42,3),('H3',270,42,4),('H3',271,43,1),('H3',272,43,2),('H3',273,43,3),('H3',274,43,4),('H3',275,44,1),('H3',276,44,2),('H3',277,44,3),('H3',278,44,4),('H3',279,44,5),('H3',280,44,6),('H3',281,44,7),('H3',282,44,8),('H3',283,44,9),('H3',284,44,10),('H3',285,44,11),('H3',286,45,1),('H3',287,45,2),('H3',288,12,1),('H3',289,46,1),('H3',290,46,2),('H3',291,46,3),('H3',292,46,4),('H3',293,46,5),('H3',294,46,6),('H3',295,2,1),('H3',296,47,1),('H3',297,47,2),('H3',298,47,3),('H3',299,48,1),('H3',300,48,2),('H3',303,49,1),('H3',304,49,2),('H3',305,50,1),('H3',306,50,2),('H3',307,51,1),('H3',308,51,2),('H4',1,1,0),('H4',2,1,1),('H4',3,1,2),('H4',4,1,3),('H4',5,1,4),('H4',6,1,5),('H4',7,1,6),('H4',8,1,7),('H4',9,1,8),('H4',10,1,9),('H4',11,1,10),('H4',12,2,0),('H4',13,2,1),('H4',14,3,0),('H4',15,3,1),('H4',16,3,2),('H4',17,3,3),('H4',18,3,4),('H4',19,3,5),('H4',20,4,0),('H4',21,4,1),('H4',22,4,2),('H4',23,4,3),('H4',24,4,4),('H4',25,4,5),('H4',26,4,6),('H4',27,4,7),('H4',28,4,8),('H4',29,4,9),('H4',30,4,10),('H4',31,4,11),('H4',32,4,12),('H4',33,5,0),('H4',34,5,1),('H4',35,5,2),('H4',36,5,3),('H4',37,5,4),('H4',38,6,0),('H4',39,6,1),('H4',40,6,2),('H4',41,7,0),('H4',42,7,1),('H4',43,7,2),('H4',44,7,3),('H4',45,7,4),('H4',46,8,0),('H4',47,9,0),('H4',48,9,1),('H4',49,9,2),('H4',50,9,3),('H4',51,10,0),('H4',52,10,1),('H4',53,10,2),('H4',54,10,3),('H4',55,10,4),('H4',56,10,5),('H4',57,10,6),('H4',58,10,7),('H4',59,10,8),('H4',60,11,0),('H4',61,11,1),('H4',62,11,2),('H4',63,11,3),('H4',64,11,4),('H4',65,11,5),('H4',66,11,6),('H4',67,11,7),('H4',68,11,8),('H4',69,11,9),('H4',70,11,10),('H4',71,11,11),('H4',72,11,12),('H4',73,11,13),('H4',74,11,14),('H4',75,11,15),('H4',76,11,16),('H4',77,11,17),('H4',78,11,18),('H4',79,11,19),('H4',80,11,20),('H4',81,11,21),('H4',82,11,22),('H4',83,11,23),('H4',84,11,24),('H4',85,11,25),('H4',86,11,26),('H4',87,11,27),('H4',88,11,28),('H4',89,11,29),('H4',90,11,30),('H4',91,11,31),('H4',92,12,0),('H4',93,12,1),('H4',94,12,2),('H4',95,12,3),('H4',96,12,4),('H4',97,12,5),('H4',98,13,0),('H4',99,13,1),('H4',100,13,2),('H4',101,13,3),('H4',102,13,4),('H4',103,13,5),('H4',104,13,6),('H4',105,13,7),('H4',106,13,8),('H4',107,13,9),('H4',108,13,10),('H4',109,13,11),('H4',110,13,12),('H4',111,14,0),('H4',112,14,1),('H4',113,14,2),('H4',114,14,3),('H4',115,14,4),('H4',116,14,5),('H4',117,14,6),('H4',118,14,7),('H4',119,14,8),('H4',120,14,9),('H4',121,14,10),('H4',122,14,11),('H4',123,15,0),('H4',124,15,1),('H4',125,15,2),('H4',126,15,3),('H4',127,16,0),('H4',128,16,1),('H4',129,16,2),('H4',130,17,0),('H4',131,17,1),('H4',132,17,2),('H4',133,17,3),('H4',134,17,4),('H4',135,18,0),('H4',136,18,1),('H4',137,18,2),('H4',138,18,3),('H4',139,19,0),('H4',140,19,1),('H4',141,19,2),('H4',142,19,3),('H4',143,19,4),('H4',144,20,0),('H4',145,20,1),('H4',146,20,2),('H4',147,20,3),('H4',148,20,4),('H4',149,20,5),('H4',150,21,0),('H4',151,21,1),('H4',152,21,2),('H4',153,21,3),('H4',154,21,4),('H4',155,21,5),('H4',156,21,6),('H4',157,21,7),('H4',158,21,8),('H4',159,21,9),('H4',160,22,0),('H4',161,22,1),('H4',162,22,2),('H4',163,22,3),('H4',164,22,4),('H4',165,22,5),('H4',166,23,0),('H4',167,23,1),('H4',168,23,2),('H4',169,23,3),('H4',170,24,0),('H4',171,24,1),('H4',172,24,2),('H4',173,25,0),('H4',174,25,1),('H4',175,25,2),('H4',176,25,3),('H4',177,25,4),('H4',178,25,5),('H4',179,26,0),('H4',180,26,1),('H4',181,26,2),('H4',182,27,0),('H4',183,27,1),('H4',184,27,2),('H4',185,27,3),('H4',186,28,0),('H4',187,28,1),('H4',188,28,2),('H4',189,28,3),('H4',190,29,0),('H4',191,29,1),('H4',192,29,2),('H4',193,29,3),('H4',194,30,0),('H4',195,30,1),('H4',196,30,2),('H4',197,30,3),('H4',198,30,4),('H4',199,30,5),('H4',200,30,6),('H4',201,30,7),('H4',202,30,8),('H4',203,30,9),('H4',204,30,10),('H4',205,30,11),('H4',206,30,12),('H4',207,30,13),('H4',208,30,14),('H4',209,30,15),('H4',210,31,1),('H4',211,31,2),('H4',212,31,3),('H4',213,31,4),('H4',214,32,1),('H4',215,32,2),('H4',216,32,3),('H4',217,32,4),('H4',218,32,5),('H4',219,32,6),('H4',220,33,1),('H4',221,33,2),('H4',222,33,3),('H4',223,34,1),('H4',224,34,2),('H4',225,34,3),('H4',226,35,1),('H4',227,35,2),('H4',228,35,3),('H4',229,36,1),('H4',230,36,2),('H4',231,36,3),('H4',232,36,4),('H4',233,36,5),('H4',234,36,6),('H4',235,37,1),('H4',236,37,2),('H4',237,37,3),('H4',238,37,4),('H4',239,38,1),('H4',240,38,2),('H4',241,38,3),('H4',242,38,4),('H4',243,39,1),('H4',244,39,2),('H4',245,39,3),('H4',246,39,4),('H4',247,40,1),('H4',248,40,2),('H4',249,40,3),('H4',250,40,4),('H4',251,40,5),('H4',252,40,6),('H4',253,40,7),('H4',254,40,8),('H4',255,40,9),('H4',256,40,10),('H4',257,40,11),('H4',258,41,1),('H4',259,41,2),('H4',260,41,3),('H4',261,41,4),('H4',262,41,5),('H4',263,41,6),('H4',264,41,7),('H4',265,41,8),('H4',266,41,9),('H4',267,42,1),('H4',268,42,2),('H4',269,42,3),('H4',270,42,4),('H4',271,43,1),('H4',272,43,2),('H4',273,43,3),('H4',274,43,4),('H4',275,44,1),('H4',276,44,2),('H4',277,44,3),('H4',278,44,4),('H4',279,44,5),('H4',280,44,6),('H4',281,44,7),('H4',282,44,8),('H4',283,44,9),('H4',284,44,10),('H4',285,44,11),('H4',286,45,1),('H4',287,45,2),('H4',288,12,1),('H4',289,46,1),('H4',290,46,2),('H4',291,46,3),('H4',292,46,4),('H4',293,46,5),('H4',294,46,6),('H4',295,2,1),('H4',296,47,1),('H4',297,47,2),('H4',298,47,3),('H4',299,48,1),('H4',300,48,2),('H4',303,49,1),('H4',304,49,2),('H4',305,50,1),('H4',306,50,2),('H4',307,51,1),('H4',308,51,2),('H5',1,1,0),('H5',2,1,1),('H5',3,1,2),('H5',4,1,3),('H5',5,1,4),('H5',6,1,5),('H5',7,1,6),('H5',8,1,7),('H5',9,1,8),('H5',10,1,9),('H5',11,1,10),('H5',12,2,0),('H5',13,2,1),('H5',14,3,0),('H5',15,3,1),('H5',16,3,2),('H5',17,3,3),('H5',18,3,4),('H5',19,3,5),('H5',20,4,0),('H5',21,4,1),('H5',22,4,2),('H5',23,4,3),('H5',24,4,4),('H5',25,4,5),('H5',26,4,6),('H5',27,4,7),('H5',28,4,8),('H5',29,4,9),('H5',30,4,10),('H5',31,4,11),('H5',32,4,12),('H5',33,5,0),('H5',34,5,1),('H5',35,5,2),('H5',36,5,3),('H5',37,5,4),('H5',38,6,0),('H5',39,6,1),('H5',40,6,2),('H5',41,7,0),('H5',42,7,1),('H5',43,7,2),('H5',44,7,3),('H5',45,7,4),('H5',46,8,0),('H5',47,9,0),('H5',48,9,1),('H5',49,9,2),('H5',50,9,3),('H5',51,10,0),('H5',52,10,1),('H5',53,10,2),('H5',54,10,3),('H5',55,10,4),('H5',56,10,5),('H5',57,10,6),('H5',58,10,7),('H5',59,10,8),('H5',60,11,0),('H5',61,11,1),('H5',62,11,2),('H5',63,11,3),('H5',64,11,4),('H5',65,11,5),('H5',66,11,6),('H5',67,11,7),('H5',68,11,8),('H5',69,11,9),('H5',70,11,10),('H5',71,11,11),('H5',72,11,12),('H5',73,11,13),('H5',74,11,14),('H5',75,11,15),('H5',76,11,16),('H5',77,11,17),('H5',78,11,18),('H5',79,11,19),('H5',80,11,20),('H5',81,11,21),('H5',82,11,22),('H5',83,11,23),('H5',84,11,24),('H5',85,11,25),('H5',86,11,26),('H5',87,11,27),('H5',88,11,28),('H5',89,11,29),('H5',90,11,30),('H5',91,11,31),('H5',92,12,0),('H5',93,12,1),('H5',94,12,2),('H5',95,12,3),('H5',96,12,4),('H5',97,12,5),('H5',98,13,0),('H5',99,13,1),('H5',100,13,2),('H5',101,13,3),('H5',102,13,4),('H5',103,13,5),('H5',104,13,6),('H5',105,13,7),('H5',106,13,8),('H5',107,13,9),('H5',108,13,10),('H5',109,13,11),('H5',110,13,12),('H5',111,14,0),('H5',112,14,1),('H5',113,14,2),('H5',114,14,3),('H5',115,14,4),('H5',116,14,5),('H5',117,14,6),('H5',118,14,7),('H5',119,14,8),('H5',120,14,9),('H5',121,14,10),('H5',122,14,11),('H5',123,15,0),('H5',124,15,1),('H5',125,15,2),('H5',126,15,3),('H5',127,16,0),('H5',128,16,1),('H5',129,16,2),('H5',130,17,0),('H5',131,17,1),('H5',132,17,2),('H5',133,17,3),('H5',134,17,4),('H5',135,18,0),('H5',136,18,1),('H5',137,18,2),('H5',138,18,3),('H5',139,19,0),('H5',140,19,1),('H5',141,19,2),('H5',142,19,3),('H5',143,19,4),('H5',144,20,0),('H5',145,20,1),('H5',146,20,2),('H5',147,20,3),('H5',148,20,4),('H5',149,20,5),('H5',150,21,0),('H5',151,21,1),('H5',152,21,2),('H5',153,21,3),('H5',154,21,4),('H5',155,21,5),('H5',156,21,6),('H5',157,21,7),('H5',158,21,8),('H5',159,21,9),('H5',160,22,0),('H5',161,22,1),('H5',162,22,2),('H5',163,22,3),('H5',164,22,4),('H5',165,22,5),('H5',166,23,0),('H5',167,23,1),('H5',168,23,2),('H5',169,23,3),('H5',170,24,0),('H5',171,24,1),('H5',172,24,2),('H5',173,25,0),('H5',174,25,1),('H5',175,25,2),('H5',176,25,3),('H5',177,25,4),('H5',178,25,5),('H5',179,26,0),('H5',180,26,1),('H5',181,26,2),('H5',182,27,0),('H5',183,27,1),('H5',184,27,2),('H5',185,27,3),('H5',186,28,0),('H5',187,28,1),('H5',188,28,2),('H5',189,28,3),('H5',190,29,0),('H5',191,29,1),('H5',192,29,2),('H5',193,29,3),('H5',194,30,0),('H5',195,30,1),('H5',196,30,2),('H5',197,30,3),('H5',198,30,4),('H5',199,30,5),('H5',200,30,6),('H5',201,30,7),('H5',202,30,8),('H5',203,30,9),('H5',204,30,10),('H5',205,30,11),('H5',206,30,12),('H5',207,30,13),('H5',208,30,14),('H5',209,30,15),('H5',210,31,1),('H5',211,31,2),('H5',212,31,3),('H5',213,31,4),('H5',214,32,1),('H5',215,32,2),('H5',216,32,3),('H5',217,32,4),('H5',218,32,5),('H5',219,32,6),('H5',220,33,1),('H5',221,33,2),('H5',222,33,3),('H5',223,34,1),('H5',224,34,2),('H5',225,34,3),('H5',226,35,1),('H5',227,35,2),('H5',228,35,3),('H5',229,36,1),('H5',230,36,2),('H5',231,36,3),('H5',232,36,4),('H5',233,36,5),('H5',234,36,6),('H5',235,37,1),('H5',236,37,2),('H5',237,37,3),('H5',238,37,4),('H5',239,38,1),('H5',240,38,2),('H5',241,38,3),('H5',242,38,4),('H5',243,39,1),('H5',244,39,2),('H5',245,39,3),('H5',246,39,4),('H5',247,40,1),('H5',248,40,2),('H5',249,40,3),('H5',250,40,4),('H5',251,40,5),('H5',252,40,6),('H5',253,40,7),('H5',254,40,8),('H5',255,40,9),('H5',256,40,10),('H5',257,40,11),('H5',258,41,1),('H5',259,41,2),('H5',260,41,3),('H5',261,41,4),('H5',262,41,5),('H5',263,41,6),('H5',264,41,7),('H5',265,41,8),('H5',266,41,9),('H5',267,42,1),('H5',268,42,2),('H5',269,42,3),('H5',270,42,4),('H5',271,43,1),('H5',272,43,2),('H5',273,43,3),('H5',274,43,4),('H5',275,44,1),('H5',276,44,2),('H5',277,44,3),('H5',278,44,4),('H5',279,44,5),('H5',280,44,6),('H5',281,44,7),('H5',282,44,8),('H5',283,44,9),('H5',284,44,10),('H5',285,44,11),('H5',286,45,1),('H5',287,45,2),('H5',288,12,1),('H5',289,46,1),('H5',290,46,2),('H5',291,46,3),('H5',292,46,4),('H5',293,46,5),('H5',294,46,6),('H5',295,2,1),('H5',296,47,1),('H5',297,47,2),('H5',298,47,3),('H5',299,48,1),('H5',300,48,2),('H5',303,49,1),('H5',304,49,2),('H5',305,50,1),('H5',306,50,2),('H5',307,51,1),('H5',308,51,2);
/*!40000 ALTER TABLE `vtiger_role2picklist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_role2profile`
--

DROP TABLE IF EXISTS `vtiger_role2profile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_role2profile` (
  `roleid` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `profileid` int NOT NULL,
  PRIMARY KEY (`roleid`,`profileid`),
  KEY `role2profile_roleid_profileid_idx` (`roleid`,`profileid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_role2profile`
--

LOCK TABLES `vtiger_role2profile` WRITE;
/*!40000 ALTER TABLE `vtiger_role2profile` DISABLE KEYS */;
INSERT INTO `vtiger_role2profile` VALUES ('H2',1),('H3',2),('H4',2),('H5',2);
/*!40000 ALTER TABLE `vtiger_role2profile` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_role_seq`
--

DROP TABLE IF EXISTS `vtiger_role_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_role_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_role_seq`
--

LOCK TABLES `vtiger_role_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_role_seq` DISABLE KEYS */;
INSERT INTO `vtiger_role_seq` VALUES (5);
/*!40000 ALTER TABLE `vtiger_role_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_rollupcomments_settings`
--

DROP TABLE IF EXISTS `vtiger_rollupcomments_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_rollupcomments_settings` (
  `rollupid` int NOT NULL AUTO_INCREMENT,
  `userid` int NOT NULL,
  `tabid` int NOT NULL,
  `rollup_status` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`rollupid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_rollupcomments_settings`
--

LOCK TABLES `vtiger_rollupcomments_settings` WRITE;
/*!40000 ALTER TABLE `vtiger_rollupcomments_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_rollupcomments_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_rowheight`
--

DROP TABLE IF EXISTS `vtiger_rowheight`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_rowheight` (
  `rowheightid` int NOT NULL AUTO_INCREMENT,
  `rowheight` varchar(200) NOT NULL,
  `sortorderid` int DEFAULT NULL,
  `presence` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`rowheightid`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_rowheight`
--

LOCK TABLES `vtiger_rowheight` WRITE;
/*!40000 ALTER TABLE `vtiger_rowheight` DISABLE KEYS */;
INSERT INTO `vtiger_rowheight` VALUES (1,'wide',0,1),(2,'medium',1,1),(3,'narrow',2,1);
/*!40000 ALTER TABLE `vtiger_rowheight` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_rowheight_seq`
--

DROP TABLE IF EXISTS `vtiger_rowheight_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_rowheight_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_rowheight_seq`
--

LOCK TABLES `vtiger_rowheight_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_rowheight_seq` DISABLE KEYS */;
INSERT INTO `vtiger_rowheight_seq` VALUES (3);
/*!40000 ALTER TABLE `vtiger_rowheight_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_rss`
--

DROP TABLE IF EXISTS `vtiger_rss`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_rss` (
  `rssid` int NOT NULL,
  `rssurl` varchar(200) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `rsstitle` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rsstype` int DEFAULT '0',
  `starred` int DEFAULT '0',
  PRIMARY KEY (`rssid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_rss`
--

LOCK TABLES `vtiger_rss` WRITE;
/*!40000 ALTER TABLE `vtiger_rss` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_rss` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_sales_stage`
--

DROP TABLE IF EXISTS `vtiger_sales_stage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_sales_stage` (
  `sales_stage_id` int NOT NULL AUTO_INCREMENT,
  `sales_stage` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT NULL,
  `color` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`sales_stage_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_sales_stage`
--

LOCK TABLES `vtiger_sales_stage` WRITE;
/*!40000 ALTER TABLE `vtiger_sales_stage` DISABLE KEYS */;
INSERT INTO `vtiger_sales_stage` VALUES (1,'Prospecting',1,150,0,NULL),(2,'Qualification',1,151,1,NULL),(3,'Needs Analysis',1,152,2,NULL),(4,'Value Proposition',1,153,3,NULL),(5,'Id. Decision Makers',1,154,4,NULL),(6,'Perception Analysis',1,155,5,NULL),(7,'Proposal or Price Quote',1,156,6,NULL),(8,'Negotiation or Review',1,157,7,NULL),(9,'Closed Won',0,158,8,NULL),(10,'Closed Lost',0,159,9,NULL);
/*!40000 ALTER TABLE `vtiger_sales_stage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_sales_stage_seq`
--

DROP TABLE IF EXISTS `vtiger_sales_stage_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_sales_stage_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_sales_stage_seq`
--

LOCK TABLES `vtiger_sales_stage_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_sales_stage_seq` DISABLE KEYS */;
INSERT INTO `vtiger_sales_stage_seq` VALUES (10);
/*!40000 ALTER TABLE `vtiger_sales_stage_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_salesmanactivityrel`
--

DROP TABLE IF EXISTS `vtiger_salesmanactivityrel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_salesmanactivityrel` (
  `smid` int NOT NULL,
  `activityid` int NOT NULL,
  PRIMARY KEY (`smid`,`activityid`),
  KEY `salesmanactivityrel_activityid_idx` (`activityid`),
  KEY `salesmanactivityrel_smid_idx` (`smid`),
  CONSTRAINT `fk_2_vtiger_salesmanactivityrel` FOREIGN KEY (`smid`) REFERENCES `vtiger_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_salesmanactivityrel`
--

LOCK TABLES `vtiger_salesmanactivityrel` WRITE;
/*!40000 ALTER TABLE `vtiger_salesmanactivityrel` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_salesmanactivityrel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_salesmanattachmentsrel`
--

DROP TABLE IF EXISTS `vtiger_salesmanattachmentsrel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_salesmanattachmentsrel` (
  `smid` int NOT NULL,
  `attachmentsid` int NOT NULL,
  PRIMARY KEY (`smid`,`attachmentsid`),
  KEY `salesmanattachmentsrel_smid_idx` (`smid`),
  KEY `salesmanattachmentsrel_attachmentsid_idx` (`attachmentsid`),
  CONSTRAINT `fk_2_vtiger_salesmanattachmentsrel` FOREIGN KEY (`attachmentsid`) REFERENCES `vtiger_attachments` (`attachmentsid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_salesmanattachmentsrel`
--

LOCK TABLES `vtiger_salesmanattachmentsrel` WRITE;
/*!40000 ALTER TABLE `vtiger_salesmanattachmentsrel` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_salesmanattachmentsrel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_salesmanticketrel`
--

DROP TABLE IF EXISTS `vtiger_salesmanticketrel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_salesmanticketrel` (
  `smid` int NOT NULL,
  `id` int NOT NULL,
  PRIMARY KEY (`smid`,`id`),
  KEY `salesmanticketrel_smid_idx` (`smid`),
  KEY `salesmanticketrel_id_idx` (`id`),
  CONSTRAINT `fk_2_vtiger_salesmanticketrel` FOREIGN KEY (`smid`) REFERENCES `vtiger_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_salesmanticketrel`
--

LOCK TABLES `vtiger_salesmanticketrel` WRITE;
/*!40000 ALTER TABLE `vtiger_salesmanticketrel` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_salesmanticketrel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_salesorder`
--

DROP TABLE IF EXISTS `vtiger_salesorder`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_salesorder` (
  `salesorderid` int NOT NULL,
  `subject` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `potentialid` int DEFAULT NULL,
  `customerno` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `salesorder_no` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `quoteid` int DEFAULT NULL,
  `vendorterms` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contactid` int DEFAULT NULL,
  `vendorid` int DEFAULT NULL,
  `duedate` date DEFAULT NULL,
  `carrier` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pending` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `adjustment` decimal(25,8) DEFAULT NULL,
  `salescommission` decimal(25,3) DEFAULT NULL,
  `exciseduty` decimal(25,3) DEFAULT NULL,
  `total` decimal(25,8) DEFAULT NULL,
  `subtotal` decimal(25,8) DEFAULT NULL,
  `taxtype` varchar(25) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `discount_percent` decimal(25,3) DEFAULT NULL,
  `discount_amount` decimal(25,8) DEFAULT NULL,
  `s_h_amount` decimal(25,8) DEFAULT NULL,
  `accountid` int DEFAULT NULL,
  `terms_conditions` text COLLATE utf8mb4_general_ci,
  `purchaseorder` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sostatus` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `currency_id` int NOT NULL DEFAULT '1',
  `conversion_rate` decimal(10,3) NOT NULL DEFAULT '1.000',
  `enable_recurring` int DEFAULT '0',
  `compound_taxes_info` text COLLATE utf8mb4_general_ci,
  `pre_tax_total` decimal(25,8) DEFAULT NULL,
  `s_h_percent` int DEFAULT NULL,
  `tags` varchar(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `region_id` int DEFAULT NULL,
  `smcreatorid` int DEFAULT NULL,
  `smownerid` int DEFAULT NULL,
  `modifiedby` int DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_general_ci,
  `createdtime` datetime DEFAULT NULL,
  `modifiedtime` datetime DEFAULT NULL,
  `viewedtime` datetime DEFAULT NULL,
  `deleted` int DEFAULT NULL,
  `label` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `smgroupid` int DEFAULT NULL,
  `source` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`salesorderid`),
  KEY `salesorder_vendorid_idx` (`vendorid`),
  KEY `salesorder_contactid_idx` (`contactid`),
  KEY `idx_info` (`deleted`,`modifiedtime`),
  KEY `idx_label` (`deleted`,`label`),
  KEY `idx_owner` (`deleted`,`smownerid`),
  CONSTRAINT `fk_3_vtiger_salesorder` FOREIGN KEY (`vendorid`) REFERENCES `vtiger_vendor` (`vendorid`) ON DELETE CASCADE,
  CONSTRAINT `fk_crmid_vtiger_salesorder` FOREIGN KEY (`salesorderid`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_salesorder`
--

LOCK TABLES `vtiger_salesorder` WRITE;
/*!40000 ALTER TABLE `vtiger_salesorder` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_salesorder` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_salesordercf`
--

DROP TABLE IF EXISTS `vtiger_salesordercf`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_salesordercf` (
  `salesorderid` int NOT NULL,
  PRIMARY KEY (`salesorderid`),
  CONSTRAINT `fk_1_vtiger_salesordercf` FOREIGN KEY (`salesorderid`) REFERENCES `vtiger_salesorder` (`salesorderid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_salesordercf`
--

LOCK TABLES `vtiger_salesordercf` WRITE;
/*!40000 ALTER TABLE `vtiger_salesordercf` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_salesordercf` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_salutationtype`
--

DROP TABLE IF EXISTS `vtiger_salutationtype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_salutationtype` (
  `salutationid` int NOT NULL AUTO_INCREMENT,
  `salutationtype` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT NULL,
  `color` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`salutationid`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_salutationtype`
--

LOCK TABLES `vtiger_salutationtype` WRITE;
/*!40000 ALTER TABLE `vtiger_salutationtype` DISABLE KEYS */;
INSERT INTO `vtiger_salutationtype` VALUES (2,'Mr.',1,161,1,NULL),(3,'Ms.',1,162,2,NULL),(4,'Mrs.',1,163,3,NULL),(5,'Dr.',1,164,4,NULL),(6,'Prof.',1,165,5,NULL);
/*!40000 ALTER TABLE `vtiger_salutationtype` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_salutationtype_seq`
--

DROP TABLE IF EXISTS `vtiger_salutationtype_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_salutationtype_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_salutationtype_seq`
--

LOCK TABLES `vtiger_salutationtype_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_salutationtype_seq` DISABLE KEYS */;
INSERT INTO `vtiger_salutationtype_seq` VALUES (6);
/*!40000 ALTER TABLE `vtiger_salutationtype_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_scheduled_reports`
--

DROP TABLE IF EXISTS `vtiger_scheduled_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_scheduled_reports` (
  `reportid` int NOT NULL,
  `recipients` text COLLATE utf8mb4_general_ci,
  `schedule` text COLLATE utf8mb4_general_ci,
  `format` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `next_trigger_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`reportid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_scheduled_reports`
--

LOCK TABLES `vtiger_scheduled_reports` WRITE;
/*!40000 ALTER TABLE `vtiger_scheduled_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_scheduled_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_schedulereports`
--

DROP TABLE IF EXISTS `vtiger_schedulereports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_schedulereports` (
  `reportid` int DEFAULT NULL,
  `scheduleid` int DEFAULT NULL,
  `recipients` text,
  `schdate` varchar(20) DEFAULT NULL,
  `schtime` time DEFAULT NULL,
  `schdayoftheweek` varchar(100) DEFAULT NULL,
  `schdayofthemonth` varchar(100) DEFAULT NULL,
  `schannualdates` varchar(500) DEFAULT NULL,
  `specificemails` varchar(500) DEFAULT NULL,
  `next_trigger_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fileformat` varchar(10) DEFAULT 'CSV'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_schedulereports`
--

LOCK TABLES `vtiger_schedulereports` WRITE;
/*!40000 ALTER TABLE `vtiger_schedulereports` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_schedulereports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_seactivityrel`
--

DROP TABLE IF EXISTS `vtiger_seactivityrel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_seactivityrel` (
  `crmid` int NOT NULL,
  `activityid` int NOT NULL,
  PRIMARY KEY (`crmid`,`activityid`),
  KEY `seactivityrel_activityid_idx` (`activityid`),
  KEY `seactivityrel_crmid_idx` (`crmid`),
  CONSTRAINT `fk_2_vtiger_seactivityrel` FOREIGN KEY (`crmid`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_seactivityrel`
--

LOCK TABLES `vtiger_seactivityrel` WRITE;
/*!40000 ALTER TABLE `vtiger_seactivityrel` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_seactivityrel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_seactivityrel_seq`
--

DROP TABLE IF EXISTS `vtiger_seactivityrel_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_seactivityrel_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_seactivityrel_seq`
--

LOCK TABLES `vtiger_seactivityrel_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_seactivityrel_seq` DISABLE KEYS */;
INSERT INTO `vtiger_seactivityrel_seq` VALUES (1);
/*!40000 ALTER TABLE `vtiger_seactivityrel_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_seattachmentsrel`
--

DROP TABLE IF EXISTS `vtiger_seattachmentsrel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_seattachmentsrel` (
  `crmid` int NOT NULL,
  `attachmentsid` int NOT NULL,
  PRIMARY KEY (`crmid`,`attachmentsid`),
  KEY `seattachmentsrel_attachmentsid_idx` (`attachmentsid`),
  KEY `seattachmentsrel_crmid_idx` (`crmid`),
  KEY `seattachmentsrel_attachmentsid_crmid_idx` (`attachmentsid`,`crmid`),
  CONSTRAINT `fk_2_vtiger_seattachmentsrel` FOREIGN KEY (`crmid`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_seattachmentsrel`
--

LOCK TABLES `vtiger_seattachmentsrel` WRITE;
/*!40000 ALTER TABLE `vtiger_seattachmentsrel` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_seattachmentsrel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_selectcolumn`
--

DROP TABLE IF EXISTS `vtiger_selectcolumn`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_selectcolumn` (
  `queryid` int NOT NULL,
  `columnindex` int NOT NULL DEFAULT '0',
  `columnname` varchar(250) COLLATE utf8mb4_general_ci DEFAULT '',
  PRIMARY KEY (`queryid`,`columnindex`),
  KEY `selectcolumn_queryid_idx` (`queryid`),
  CONSTRAINT `fk_1_vtiger_selectcolumn` FOREIGN KEY (`queryid`) REFERENCES `vtiger_selectquery` (`queryid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_selectcolumn`
--

LOCK TABLES `vtiger_selectcolumn` WRITE;
/*!40000 ALTER TABLE `vtiger_selectcolumn` DISABLE KEYS */;
INSERT INTO `vtiger_selectcolumn` VALUES (1,0,'vtiger_contactdetails:lastname:Contacts_Last_Name:lastname:V'),(1,1,'vtiger_contactdetails:firstname:Contacts_First_Name:firstname:V'),(1,2,'vtiger_contactsubdetails:leadsource:Contacts_Lead_Source:leadsource:V'),(1,3,'vtiger_contactdetails:accountid:Contacts_Account_Name:account_id:V'),(1,4,'vtiger_account:industry:Accounts_industry:industry:V'),(1,5,'vtiger_contactdetails:email:Contacts_Email:email:E'),(2,0,'vtiger_contactdetails:lastname:Contacts_Last_Name:lastname:V'),(2,1,'vtiger_contactdetails:firstname:Contacts_First_Name:firstname:V'),(2,2,'vtiger_contactsubdetails:leadsource:Contacts_Lead_Source:leadsource:V'),(2,3,'vtiger_contactdetails:accountid:Contacts_Account_Name:account_id:V'),(2,4,'vtiger_account:industry:Accounts_industry:industry:V'),(2,5,'vtiger_contactdetails:email:Contacts_Email:email:E'),(3,0,'vtiger_contactdetails:lastname:Contacts_Last_Name:lastname:V'),(3,1,'vtiger_contactdetails:firstname:Contacts_First_Name:firstname:V'),(3,2,'vtiger_contactdetails:accountid:Contacts_Account_Name:account_id:V'),(3,3,'vtiger_contactdetails:email:Contacts_Email:email:E'),(3,4,'vtiger_potential:potentialname:Potentials_Potential_Name:potentialname:V'),(3,5,'vtiger_potential:sales_stage:Potentials_Sales_Stage:sales_stage:V'),(4,0,'vtiger_leaddetails:lastname:Leads_Last_Name:lastname:V'),(4,1,'vtiger_leaddetails:firstname:Leads_First_Name:firstname:V'),(4,2,'vtiger_leaddetails:company:Leads_Company:company:V'),(4,3,'vtiger_leaddetails:email:Leads_Email:email:E'),(4,4,'vtiger_leaddetails:leadsource:Leads_Lead_Source:leadsource:V'),(5,0,'vtiger_leaddetails:lastname:Leads_Last_Name:lastname:V'),(5,1,'vtiger_leaddetails:firstname:Leads_First_Name:firstname:V'),(5,2,'vtiger_leaddetails:company:Leads_Company:company:V'),(5,3,'vtiger_leaddetails:email:Leads_Email:email:E'),(5,4,'vtiger_leaddetails:leadsource:Leads_Lead_Source:leadsource:V'),(5,5,'vtiger_leaddetails:leadstatus:Leads_Lead_Status:leadstatus:V'),(6,0,'vtiger_potential:potentialname:Potentials_Potential_Name:potentialname:V'),(6,1,'vtiger_potential:amount:Potentials_Amount:amount:N'),(6,2,'vtiger_potential:potentialtype:Potentials_Type:opportunity_type:V'),(6,3,'vtiger_potential:leadsource:Potentials_Lead_Source:leadsource:V'),(6,4,'vtiger_potential:sales_stage:Potentials_Sales_Stage:sales_stage:V'),(7,0,'vtiger_potential:potentialname:Potentials_Potential_Name:potentialname:V'),(7,1,'vtiger_potential:amount:Potentials_Amount:amount:N'),(7,2,'vtiger_potential:potentialtype:Potentials_Type:opportunity_type:V'),(7,3,'vtiger_potential:leadsource:Potentials_Lead_Source:leadsource:V'),(7,4,'vtiger_potential:sales_stage:Potentials_Sales_Stage:sales_stage:V'),(8,0,'vtiger_activity:subject:Calendar_Subject:subject:V'),(8,1,'vtiger_contactdetailsCalendar:lastname:Calendar_Contact_Name:contact_id:I'),(8,2,'vtiger_activity:status:Calendar_Status:taskstatus:V'),(8,3,'vtiger_activity:priority:Calendar_Priority:taskpriority:V'),(8,4,'vtiger_usersCalendar:user_name:Calendar_Assigned_To:assigned_user_id:V'),(9,0,'vtiger_activity:subject:Calendar_Subject:subject:V'),(9,1,'vtiger_contactdetailsCalendar:lastname:Calendar_Contact_Name:contact_id:I'),(9,2,'vtiger_activity:status:Calendar_Status:taskstatus:V'),(9,3,'vtiger_activity:priority:Calendar_Priority:taskpriority:V'),(9,4,'vtiger_usersCalendar:user_name:Calendar_Assigned_To:assigned_user_id:V'),(10,0,'vtiger_troubletickets:title:HelpDesk_Title:ticket_title:V'),(10,1,'vtiger_troubletickets:status:HelpDesk_Status:ticketstatus:V'),(10,2,'vtiger_products:productname:Products_Product_Name:productname:V'),(10,3,'vtiger_products:discontinued:Products_Product_Active:discontinued:V'),(10,4,'vtiger_products:productcategory:Products_Product_Category:productcategory:V'),(10,5,'vtiger_products:manufacturer:Products_Manufacturer:manufacturer:V'),(11,0,'vtiger_troubletickets:title:HelpDesk_Title:ticket_title:V'),(11,1,'vtiger_troubletickets:priority:HelpDesk_Priority:ticketpriorities:V'),(11,2,'vtiger_troubletickets:severity:HelpDesk_Severity:ticketseverities:V'),(11,3,'vtiger_troubletickets:status:HelpDesk_Status:ticketstatus:V'),(11,4,'vtiger_troubletickets:category:HelpDesk_Category:ticketcategories:V'),(11,5,'vtiger_usersHelpDesk:user_name:HelpDesk_Assigned_To:assigned_user_id:V'),(12,0,'vtiger_troubletickets:title:HelpDesk_Title:ticket_title:V'),(12,1,'vtiger_troubletickets:priority:HelpDesk_Priority:ticketpriorities:V'),(12,2,'vtiger_troubletickets:severity:HelpDesk_Severity:ticketseverities:V'),(12,3,'vtiger_troubletickets:status:HelpDesk_Status:ticketstatus:V'),(12,4,'vtiger_troubletickets:category:HelpDesk_Category:ticketcategories:V'),(12,5,'vtiger_usersHelpDesk:user_name:HelpDesk_Assigned_To:assigned_user_id:V'),(13,0,'vtiger_products:productname:Products_Product_Name:productname:V'),(13,1,'vtiger_products:productcode:Products_Product_Code:productcode:V'),(13,2,'vtiger_products:discontinued:Products_Product_Active:discontinued:V'),(13,3,'vtiger_products:productcategory:Products_Product_Category:productcategory:V'),(13,4,'vtiger_products:website:Products_Website:website:V'),(13,5,'vtiger_vendorRelProducts:vendorname:Products_Vendor_Name:vendor_id:I'),(13,6,'vtiger_products:mfr_part_no:Products_Mfr_PartNo:mfr_part_no:V'),(14,0,'vtiger_products:productname:Products_Product_Name:productname:V'),(14,1,'vtiger_products:manufacturer:Products_Manufacturer:manufacturer:V'),(14,2,'vtiger_products:productcategory:Products_Product_Category:productcategory:V'),(14,3,'vtiger_contactdetails:lastname:Contacts_Last_Name:lastname:V'),(14,4,'vtiger_contactdetails:firstname:Contacts_First_Name:firstname:V'),(14,5,'vtiger_contactsubdetails:leadsource:Contacts_Lead_Source:leadsource:V'),(15,0,'vtiger_quotes:subject:Quotes_Subject:subject:V'),(15,1,'vtiger_potentialRelQuotes:potentialname:Quotes_Potential_Name:potential_id:I'),(15,2,'vtiger_quotes:quotestage:Quotes_Quote_Stage:quotestage:V'),(15,3,'vtiger_quotes:contactid:Quotes_Contact_Name:contact_id:V'),(15,4,'vtiger_usersRel1:user_name:Quotes_Inventory_Manager:assigned_user_id1:I'),(15,5,'vtiger_accountQuotes:accountname:Quotes_Account_Name:account_id:I'),(16,0,'vtiger_quotes:subject:Quotes_Subject:subject:V'),(16,1,'vtiger_potentialRelQuotes:potentialname:Quotes_Potential_Name:potential_id:I'),(16,2,'vtiger_quotes:quotestage:Quotes_Quote_Stage:quotestage:V'),(16,3,'vtiger_quotes:contactid:Quotes_Contact_Name:contact_id:V'),(16,4,'vtiger_usersRel1:user_name:Quotes_Inventory_Manager:assigned_user_id1:I'),(16,5,'vtiger_accountQuotes:accountname:Quotes_Account_Name:account_id:I'),(16,6,'vtiger_quotes:carrier:Quotes_Carrier:carrier:V'),(16,7,'vtiger_quotes:shipping:Quotes_Shipping:shipping:V'),(17,0,'vtiger_purchaseorder:subject:PurchaseOrder_Subject:subject:V'),(17,1,'vtiger_vendorRelPurchaseOrder:vendorname:PurchaseOrder_Vendor_Name:vendor_id:I'),(17,2,'vtiger_purchaseorder:tracking_no:PurchaseOrder_Tracking_Number:tracking_no:V'),(17,3,'vtiger_contactdetails:lastname:Contacts_Last_Name:lastname:V'),(17,4,'vtiger_contactdetails:firstname:Contacts_First_Name:firstname:V'),(17,5,'vtiger_contactsubdetails:leadsource:Contacts_Lead_Source:leadsource:V'),(17,6,'vtiger_contactdetails:email:Contacts_Email:email:E'),(18,0,'vtiger_purchaseorder:subject:PurchaseOrder_Subject:subject:V'),(18,1,'vtiger_vendorRelPurchaseOrder:vendorname:PurchaseOrder_Vendor_Name:vendor_id:I'),(18,2,'vtiger_purchaseorder:requisition_no:PurchaseOrder_Requisition_No:requisition_no:V'),(18,3,'vtiger_purchaseorder:tracking_no:PurchaseOrder_Tracking_Number:tracking_no:V'),(18,4,'vtiger_contactdetailsPurchaseOrder:lastname:PurchaseOrder_Contact_Name:contact_id:I'),(18,5,'vtiger_purchaseorder:carrier:PurchaseOrder_Carrier:carrier:V'),(18,6,'vtiger_purchaseorder:salescommission:PurchaseOrder_Sales_Commission:salescommission:N'),(18,7,'vtiger_purchaseorder:exciseduty:PurchaseOrder_Excise_Duty:exciseduty:N'),(18,8,'vtiger_usersPurchaseOrder:user_name:PurchaseOrder_Assigned_To:assigned_user_id:V'),(19,0,'vtiger_invoice:subject:Invoice_Subject:subject:V'),(19,1,'vtiger_invoice:salesorderid:Invoice_Sales_Order:salesorder_id:I'),(19,2,'vtiger_invoice:customerno:Invoice_Customer_No:customerno:V'),(19,3,'vtiger_invoice:exciseduty:Invoice_Excise_Duty:exciseduty:N'),(19,4,'vtiger_invoice:salescommission:Invoice_Sales_Commission:salescommission:N'),(19,5,'vtiger_accountInvoice:accountname:Invoice_Account_Name:account_id:I'),(20,0,'vtiger_salesorder:subject:SalesOrder_Subject:subject:V'),(20,1,'vtiger_quotesSalesOrder:subject:SalesOrder_Quote_Name:quote_id:I'),(20,2,'vtiger_contactdetailsSalesOrder:lastname:SalesOrder_Contact_Name:contact_id:I'),(20,3,'vtiger_salesorder:duedate:SalesOrder_Due_Date:duedate:D'),(20,4,'vtiger_salesorder:carrier:SalesOrder_Carrier:carrier:V'),(20,5,'vtiger_salesorder:sostatus:SalesOrder_Status:sostatus:V'),(20,6,'vtiger_accountSalesOrder:accountname:SalesOrder_Account_Name:account_id:I'),(20,7,'vtiger_salesorder:salescommission:SalesOrder_Sales_Commission:salescommission:N'),(20,8,'vtiger_salesorder:exciseduty:SalesOrder_Excise_Duty:exciseduty:N'),(20,9,'vtiger_usersSalesOrder:user_name:SalesOrder_Assigned_To:assigned_user_id:V'),(21,0,'vtiger_campaign:campaignname:Campaigns_Campaign_Name:campaignname:V'),(21,1,'vtiger_campaign:campaigntype:Campaigns_Campaign_Type:campaigntype:V'),(21,2,'vtiger_campaign:targetaudience:Campaigns_Target_Audience:targetaudience:V'),(21,3,'vtiger_campaign:budgetcost:Campaigns_Budget_Cost:budgetcost:I'),(21,4,'vtiger_campaign:actualcost:Campaigns_Actual_Cost:actualcost:I'),(21,5,'vtiger_campaign:expectedrevenue:Campaigns_Expected_Revenue:expectedrevenue:I'),(21,6,'vtiger_campaign:expectedsalescount:Campaigns_Expected_Sales_Count:expectedsalescount:N'),(21,7,'vtiger_campaign:actualsalescount:Campaigns_Actual_Sales_Count:actualsalescount:N'),(21,8,'vtiger_usersCampaigns:user_name:Campaigns_Assigned_To:assigned_user_id:V'),(22,0,'vtiger_contactdetails:lastname:Contacts_Last_Name:lastname:V'),(22,1,'vtiger_contactdetails:email:Contacts_Email:email:E'),(22,2,'vtiger_activity:subject:Emails_Subject:subject:V'),(22,3,'vtiger_email_track:access_count:Emails_Access_Count:access_count:I'),(23,0,'vtiger_account:accountname:Accounts_Account_Name:accountname:V'),(23,1,'vtiger_account:phone:Accounts_Phone:phone:V'),(23,2,'vtiger_account:email1:Accounts_Email:email1:E'),(23,3,'vtiger_activity:subject:Emails_Subject:subject:V'),(23,4,'vtiger_email_track:access_count:Emails_Access_Count:access_count:I'),(24,0,'vtiger_leaddetails:lastname:Leads_Last_Name:lastname:V'),(24,1,'vtiger_leaddetails:company:Leads_Company:company:V'),(24,2,'vtiger_leaddetails:email:Leads_Email:email:E'),(24,3,'vtiger_activity:subject:Emails_Subject:subject:V'),(24,4,'vtiger_email_track:access_count:Emails_Access_Count:access_count:I'),(25,0,'vtiger_vendor:vendorname:Vendors_Vendor_Name:vendorname:V'),(25,1,'vtiger_vendor:glacct:Vendors_GL_Account:glacct:V'),(25,2,'vtiger_vendor:email:Vendors_Email:email:E'),(25,3,'vtiger_activity:subject:Emails_Subject:subject:V'),(25,4,'vtiger_email_track:access_count:Emails_Access_Count:access_count:I');
/*!40000 ALTER TABLE `vtiger_selectcolumn` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_selectquery`
--

DROP TABLE IF EXISTS `vtiger_selectquery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_selectquery` (
  `queryid` int NOT NULL,
  `startindex` int DEFAULT '0',
  `numofobjects` int DEFAULT '0',
  PRIMARY KEY (`queryid`),
  KEY `selectquery_queryid_idx` (`queryid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_selectquery`
--

LOCK TABLES `vtiger_selectquery` WRITE;
/*!40000 ALTER TABLE `vtiger_selectquery` DISABLE KEYS */;
INSERT INTO `vtiger_selectquery` VALUES (1,0,0),(2,0,0),(3,0,0),(4,0,0),(5,0,0),(6,0,0),(7,0,0),(8,0,0),(9,0,0),(10,0,0),(11,0,0),(12,0,0),(13,0,0),(14,0,0),(15,0,0),(16,0,0),(17,0,0),(18,0,0),(19,0,0),(20,0,0),(21,0,0),(22,0,0),(23,0,0),(24,0,0),(25,0,0);
/*!40000 ALTER TABLE `vtiger_selectquery` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_selectquery_seq`
--

DROP TABLE IF EXISTS `vtiger_selectquery_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_selectquery_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_selectquery_seq`
--

LOCK TABLES `vtiger_selectquery_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_selectquery_seq` DISABLE KEYS */;
INSERT INTO `vtiger_selectquery_seq` VALUES (25);
/*!40000 ALTER TABLE `vtiger_selectquery_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_senotesrel`
--

DROP TABLE IF EXISTS `vtiger_senotesrel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_senotesrel` (
  `crmid` int NOT NULL,
  `notesid` int NOT NULL,
  PRIMARY KEY (`crmid`,`notesid`),
  KEY `senotesrel_notesid_idx` (`notesid`),
  KEY `senotesrel_crmid_idx` (`crmid`),
  CONSTRAINT `fk1_crmid` FOREIGN KEY (`crmid`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE,
  CONSTRAINT `fk_2_vtiger_senotesrel` FOREIGN KEY (`notesid`) REFERENCES `vtiger_notes` (`notesid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_senotesrel`
--

LOCK TABLES `vtiger_senotesrel` WRITE;
/*!40000 ALTER TABLE `vtiger_senotesrel` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_senotesrel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_seproductsrel`
--

DROP TABLE IF EXISTS `vtiger_seproductsrel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_seproductsrel` (
  `crmid` int NOT NULL,
  `productid` int NOT NULL,
  `setype` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `quantity` int DEFAULT '1',
  PRIMARY KEY (`crmid`,`productid`),
  KEY `seproductsrel_productid_idx` (`productid`),
  KEY `seproductrel_crmid_idx` (`crmid`),
  CONSTRAINT `fk_2_vtiger_seproductsrel` FOREIGN KEY (`productid`) REFERENCES `vtiger_products` (`productid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_seproductsrel`
--

LOCK TABLES `vtiger_seproductsrel` WRITE;
/*!40000 ALTER TABLE `vtiger_seproductsrel` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_seproductsrel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_service`
--

DROP TABLE IF EXISTS `vtiger_service`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_service` (
  `serviceid` int NOT NULL,
  `service_no` varchar(100) NOT NULL,
  `servicename` varchar(50) NOT NULL,
  `servicecategory` varchar(200) DEFAULT NULL,
  `qty_per_unit` decimal(11,2) DEFAULT '0.00',
  `unit_price` decimal(25,8) DEFAULT NULL,
  `sales_start_date` date DEFAULT NULL,
  `sales_end_date` date DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `discontinued` int NOT NULL DEFAULT '0',
  `service_usageunit` varchar(200) DEFAULT NULL,
  `website` varchar(100) DEFAULT NULL,
  `taxclass` varchar(200) DEFAULT NULL,
  `currency_id` int NOT NULL DEFAULT '1',
  `commissionrate` decimal(7,3) DEFAULT NULL,
  `tags` varchar(1) DEFAULT NULL,
  `purchase_cost` decimal(27,8) DEFAULT NULL,
  `smcreatorid` int DEFAULT NULL,
  `smownerid` int DEFAULT NULL,
  `modifiedby` int DEFAULT NULL,
  `description` longtext,
  `createdtime` datetime DEFAULT NULL,
  `modifiedtime` datetime DEFAULT NULL,
  `viewedtime` datetime DEFAULT NULL,
  `deleted` int DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `smgroupid` int DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`serviceid`),
  KEY `idx_info` (`deleted`,`modifiedtime`),
  KEY `idx_label` (`deleted`,`label`),
  KEY `idx_owner` (`deleted`,`smownerid`),
  CONSTRAINT `fk_1_vtiger_service` FOREIGN KEY (`serviceid`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_service`
--

LOCK TABLES `vtiger_service` WRITE;
/*!40000 ALTER TABLE `vtiger_service` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_service` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_service_usageunit`
--

DROP TABLE IF EXISTS `vtiger_service_usageunit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_service_usageunit` (
  `service_usageunitid` int NOT NULL AUTO_INCREMENT,
  `service_usageunit` varchar(200) NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT '0',
  `color` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`service_usageunitid`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_service_usageunit`
--

LOCK TABLES `vtiger_service_usageunit` WRITE;
/*!40000 ALTER TABLE `vtiger_service_usageunit` DISABLE KEYS */;
INSERT INTO `vtiger_service_usageunit` VALUES (1,'Hours',1,226,1,NULL),(2,'Days',1,227,2,NULL),(3,'Incidents',1,228,3,NULL);
/*!40000 ALTER TABLE `vtiger_service_usageunit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_service_usageunit_seq`
--

DROP TABLE IF EXISTS `vtiger_service_usageunit_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_service_usageunit_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_service_usageunit_seq`
--

LOCK TABLES `vtiger_service_usageunit_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_service_usageunit_seq` DISABLE KEYS */;
INSERT INTO `vtiger_service_usageunit_seq` VALUES (3);
/*!40000 ALTER TABLE `vtiger_service_usageunit_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_servicecategory`
--

DROP TABLE IF EXISTS `vtiger_servicecategory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_servicecategory` (
  `servicecategoryid` int NOT NULL AUTO_INCREMENT,
  `servicecategory` varchar(200) NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT '0',
  `color` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`servicecategoryid`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_servicecategory`
--

LOCK TABLES `vtiger_servicecategory` WRITE;
/*!40000 ALTER TABLE `vtiger_servicecategory` DISABLE KEYS */;
INSERT INTO `vtiger_servicecategory` VALUES (2,'Support',1,230,2,NULL),(3,'Installation',1,231,3,NULL),(4,'Migration',1,232,4,NULL),(5,'Customization',1,233,5,NULL),(6,'Training',1,234,6,NULL);
/*!40000 ALTER TABLE `vtiger_servicecategory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_servicecategory_seq`
--

DROP TABLE IF EXISTS `vtiger_servicecategory_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_servicecategory_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_servicecategory_seq`
--

LOCK TABLES `vtiger_servicecategory_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_servicecategory_seq` DISABLE KEYS */;
INSERT INTO `vtiger_servicecategory_seq` VALUES (6);
/*!40000 ALTER TABLE `vtiger_servicecategory_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_servicecf`
--

DROP TABLE IF EXISTS `vtiger_servicecf`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_servicecf` (
  `serviceid` int NOT NULL,
  PRIMARY KEY (`serviceid`),
  CONSTRAINT `fk_serviceid_vtiger_servicecf` FOREIGN KEY (`serviceid`) REFERENCES `vtiger_service` (`serviceid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_servicecf`
--

LOCK TABLES `vtiger_servicecf` WRITE;
/*!40000 ALTER TABLE `vtiger_servicecf` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_servicecf` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_servicecontracts`
--

DROP TABLE IF EXISTS `vtiger_servicecontracts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_servicecontracts` (
  `servicecontractsid` int DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `sc_related_to` int DEFAULT NULL,
  `tracking_unit` varchar(100) DEFAULT NULL,
  `total_units` decimal(5,2) DEFAULT NULL,
  `used_units` decimal(5,2) DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `planned_duration` varchar(256) DEFAULT NULL,
  `actual_duration` varchar(256) DEFAULT NULL,
  `contract_status` varchar(200) DEFAULT NULL,
  `priority` varchar(200) DEFAULT NULL,
  `contract_type` varchar(200) DEFAULT NULL,
  `progress` decimal(5,2) DEFAULT NULL,
  `contract_no` varchar(100) DEFAULT NULL,
  `tags` varchar(1) DEFAULT NULL,
  `smcreatorid` int DEFAULT NULL,
  `smownerid` int DEFAULT NULL,
  `modifiedby` int DEFAULT NULL,
  `description` longtext,
  `createdtime` datetime DEFAULT NULL,
  `modifiedtime` datetime DEFAULT NULL,
  `viewedtime` datetime DEFAULT NULL,
  `deleted` int DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `smgroupid` int DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL,
  UNIQUE KEY `uq_fk_vtiger_servicecontracts_servicecontractsid` (`servicecontractsid`),
  KEY `sc_related_to` (`sc_related_to`),
  KEY `idx_info` (`deleted`,`modifiedtime`),
  KEY `idx_label` (`deleted`,`label`),
  KEY `idx_owner` (`deleted`,`smownerid`),
  CONSTRAINT `fk_crmid_vtiger_servicecontracts` FOREIGN KEY (`servicecontractsid`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_servicecontracts`
--

LOCK TABLES `vtiger_servicecontracts` WRITE;
/*!40000 ALTER TABLE `vtiger_servicecontracts` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_servicecontracts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_servicecontractscf`
--

DROP TABLE IF EXISTS `vtiger_servicecontractscf`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_servicecontractscf` (
  `servicecontractsid` int NOT NULL,
  PRIMARY KEY (`servicecontractsid`),
  CONSTRAINT `fk_servicecontractsid_vtiger_servicecontractscf` FOREIGN KEY (`servicecontractsid`) REFERENCES `vtiger_servicecontracts` (`servicecontractsid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_servicecontractscf`
--

LOCK TABLES `vtiger_servicecontractscf` WRITE;
/*!40000 ALTER TABLE `vtiger_servicecontractscf` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_servicecontractscf` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_seticketsrel`
--

DROP TABLE IF EXISTS `vtiger_seticketsrel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_seticketsrel` (
  `crmid` int NOT NULL,
  `ticketid` int NOT NULL,
  PRIMARY KEY (`crmid`,`ticketid`),
  KEY `seticketsrel_crmid_idx` (`crmid`),
  KEY `seticketsrel_ticketid_idx` (`ticketid`),
  CONSTRAINT `fk_2_vtiger_seticketsrel` FOREIGN KEY (`ticketid`) REFERENCES `vtiger_troubletickets` (`ticketid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_seticketsrel`
--

LOCK TABLES `vtiger_seticketsrel` WRITE;
/*!40000 ALTER TABLE `vtiger_seticketsrel` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_seticketsrel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_settings_blocks`
--

DROP TABLE IF EXISTS `vtiger_settings_blocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_settings_blocks` (
  `blockid` int NOT NULL,
  `label` varchar(250) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sequence` int DEFAULT NULL,
  PRIMARY KEY (`blockid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_settings_blocks`
--

LOCK TABLES `vtiger_settings_blocks` WRITE;
/*!40000 ALTER TABLE `vtiger_settings_blocks` DISABLE KEYS */;
INSERT INTO `vtiger_settings_blocks` VALUES (1,'LBL_USER_MANAGEMENT',1),(4,'LBL_OTHER_SETTINGS',10),(5,'LBL_MODULE_MANAGER',2),(6,'LBL_AUTOMATION',3),(7,'LBL_CONFIGURATION',4),(8,'LBL_MARKETING_SALES',5),(9,'LBL_INVENTORY',6),(10,'LBL_MY_PREFERENCES',7),(11,'LBL_INTEGRATION',8);
/*!40000 ALTER TABLE `vtiger_settings_blocks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_settings_blocks_seq`
--

DROP TABLE IF EXISTS `vtiger_settings_blocks_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_settings_blocks_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_settings_blocks_seq`
--

LOCK TABLES `vtiger_settings_blocks_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_settings_blocks_seq` DISABLE KEYS */;
INSERT INTO `vtiger_settings_blocks_seq` VALUES (12);
/*!40000 ALTER TABLE `vtiger_settings_blocks_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_settings_field`
--

DROP TABLE IF EXISTS `vtiger_settings_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_settings_field` (
  `fieldid` int NOT NULL,
  `blockid` int DEFAULT NULL,
  `name` varchar(250) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `iconpath` varchar(300) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `linkto` text COLLATE utf8mb4_general_ci,
  `sequence` int DEFAULT NULL,
  `active` int DEFAULT '0',
  `pinned` int DEFAULT '0',
  PRIMARY KEY (`fieldid`),
  KEY `fk_1_vtiger_settings_field` (`blockid`),
  CONSTRAINT `fk_1_vtiger_settings_field` FOREIGN KEY (`blockid`) REFERENCES `vtiger_settings_blocks` (`blockid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_settings_field`
--

LOCK TABLES `vtiger_settings_field` WRITE;
/*!40000 ALTER TABLE `vtiger_settings_field` DISABLE KEYS */;
INSERT INTO `vtiger_settings_field` VALUES (1,1,'LBL_USERS','ico-users.gif','LBL_USER_DESCRIPTION','index.php?module=Users&parent=Settings&view=List',1,0,1),(2,1,'LBL_ROLES','ico-roles.gif','LBL_ROLE_DESCRIPTION','index.php?module=Roles&parent=Settings&view=Index',2,0,0),(3,1,'LBL_PROFILES','ico-profile.gif','LBL_PROFILE_DESCRIPTION','index.php?module=Profiles&parent=Settings&view=List',3,0,0),(4,1,'USERGROUPLIST','ico-groups.gif','LBL_GROUP_DESCRIPTION','index.php?module=Groups&parent=Settings&view=List',5,0,0),(5,1,'LBL_SHARING_ACCESS','shareaccess.gif','LBL_SHARING_ACCESS_DESCRIPTION','index.php?module=SharingAccess&parent=Settings&view=Index',4,0,0),(7,1,'LBL_LOGIN_HISTORY_DETAILS','set-IcoLoginHistory.gif','LBL_LOGIN_HISTORY_DESCRIPTION','index.php?module=LoginHistory&parent=Settings&view=List',6,0,0),(8,5,'VTLIB_LBL_MODULE_MANAGER','vtlib_modmng.gif','VTLIB_LBL_MODULE_MANAGER_DESCRIPTION','index.php?module=ModuleManager&parent=Settings&view=List',1,0,1),(9,5,'LBL_PICKLIST_EDITOR','picklist.gif','LBL_PICKLIST_DESCRIPTION','index.php?parent=Settings&module=Picklist&view=Index',6,0,1),(10,5,'LBL_PICKLIST_DEPENDENCY','picklistdependency.gif','LBL_PICKLIST_DEPENDENCY_DESCRIPTION','index.php?parent=Settings&module=PickListDependency&view=List',7,0,0),(11,7,'LBL_MENU_EDITOR','menueditor.png','LBL_MENU_DESC','index.php?module=MenuEditor&parent=Settings&view=Index',8,0,0),(14,7,'LBL_COMPANY_DETAILS','company.gif','LBL_COMPANY_DESCRIPTION','index.php?parent=Settings&module=Vtiger&view=CompanyDetails',1,0,0),(15,7,'LBL_MAIL_SERVER_SETTINGS','ogmailserver.gif','LBL_MAIL_SERVER_DESCRIPTION','index.php?parent=Settings&module=Vtiger&view=OutgoingServerDetail',4,0,0),(16,7,'LBL_CURRENCY_SETTINGS','currency.gif','LBL_CURRENCY_DESCRIPTION','index.php?parent=Settings&module=Currency&view=List',3,0,0),(17,9,'LBL_TAX_SETTINGS','taxConfiguration.gif','LBL_TAX_DESCRIPTION','index.php?module=Vtiger&parent=Settings&view=TaxIndex',1,0,0),(18,4,'LBL_SYSTEM_INFO','system.gif','LBL_SYSTEM_DESCRIPTION','index.php?module=Settings&submodule=Server&view=ProxyConfig',6,1,0),(20,4,'LBL_DEFAULT_MODULE_VIEW','set-IcoTwoTabConfig.gif','LBL_DEFAULT_MODULE_VIEW_DESC','index.php?module=Settings&action=DefModuleView&parenttab=Settings',2,0,0),(21,9,'INVENTORYTERMSANDCONDITIONS','terms.gif','LBL_INV_TANDC_DESCRIPTION','index.php?parent=Settings&module=Vtiger&view=TermsAndConditionsEdit',2,0,0),(22,5,'LBL_CUSTOMIZE_MODENT_NUMBER','settingsInvNumber.gif','LBL_CUSTOMIZE_MODENT_NUMBER_DESCRIPTION','index.php?module=Vtiger&parent=Settings&view=CustomRecordNumbering',4,0,0),(23,6,'LBL_MAIL_SCANNER','mailScanner.gif','LBL_MAIL_SCANNER_DESCRIPTION','index.php?parent=Settings&module=MailConverter&view=List',5,0,0),(24,6,'LBL_LIST_WORKFLOWS','settingsWorkflow.png','LBL_LIST_WORKFLOWS_DESCRIPTION','index.php?module=Workflows&parent=Settings&view=List',3,0,1),(25,7,'Configuration Editor','migrate.gif','LBL_CONFIG_EDITOR_DESCRIPTION','index.php?module=Vtiger&parent=Settings&view=ConfigEditorDetail',5,0,0),(26,6,'Scheduler','Cron.png','Allows you to Configure Cron Task','index.php?module=CronTasks&parent=Settings&view=List',2,0,0),(28,4,'ModTracker','set-IcoLoginHistory.gif','LBL_MODTRACKER_DESCRIPTION','index.php?module=ModTracker&action=BasicSettings&parenttab=Settings&formodule=ModTracker',9,0,0),(29,6,'Webforms','modules/Webforms/img/Webform.png','LBL_WEBFORMS_DESCRIPTION','index.php?module=Webforms&parent=Settings&view=List',1,0,0),(30,7,'LBL_CUSTOMER_PORTAL','portal_icon.png','PORTAL_EXTENSION_DESCRIPTION','index.php?module=CustomerPortal&parent=Settings&view=Index',2,0,0),(31,5,'LBL_EDIT_FIELDS','','LBL_LAYOUT_EDITOR_DESCRIPTION','index.php?module=LayoutEditor&parent=Settings&view=Index',2,0,0),(32,8,'LBL_LEAD_MAPPING','NULL','NULL','index.php?parent=Settings&module=Leads&view=MappingDetail',1,0,1),(33,8,'LBL_OPPORTUNITY_MAPPING','NULL','NULL','index.php?parent=Settings&module=Potentials&view=MappingDetail',2,0,1),(34,10,'My Preferences','NULL','NULL','index.php?module=Users&view=PreferenceDetail&parent=Settings&record=1',1,0,1),(35,10,'Calendar Settings','NULL','NULL','index.php?module=Users&parent=Settings&view=Calendar&record=1',2,0,1),(36,10,'LBL_MY_TAGS','NULL','NULL','index.php?module=Tags&parent=Settings&view=List&record=1',3,0,1),(39,5,'LanguageConverter',NULL,'LBL_LANGUAGECONVERTER_DESCRIPTION','index.php?module=LanguageConverter&view=List&parent=Settings',8,0,0),(40,7,'Parameters',NULL,NULL,'index.php?module=Parameters&view=List&parent=Settings',9,0,0);
/*!40000 ALTER TABLE `vtiger_settings_field` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_settings_field_seq`
--

DROP TABLE IF EXISTS `vtiger_settings_field_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_settings_field_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_settings_field_seq`
--

LOCK TABLES `vtiger_settings_field_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_settings_field_seq` DISABLE KEYS */;
INSERT INTO `vtiger_settings_field_seq` VALUES (40);
/*!40000 ALTER TABLE `vtiger_settings_field_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_sharedcalendar`
--

DROP TABLE IF EXISTS `vtiger_sharedcalendar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_sharedcalendar` (
  `userid` int NOT NULL,
  `sharedid` int NOT NULL,
  PRIMARY KEY (`userid`,`sharedid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_sharedcalendar`
--

LOCK TABLES `vtiger_sharedcalendar` WRITE;
/*!40000 ALTER TABLE `vtiger_sharedcalendar` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_sharedcalendar` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_sharedcalendartodoview`
--

DROP TABLE IF EXISTS `vtiger_sharedcalendartodoview`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_sharedcalendartodoview` (
  `sharedcalendartodoviewid` int NOT NULL AUTO_INCREMENT,
  `sharedcalendartodoview` varchar(200) NOT NULL,
  `sortorderid` int DEFAULT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `color` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`sharedcalendartodoviewid`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_sharedcalendartodoview`
--

LOCK TABLES `vtiger_sharedcalendartodoview` WRITE;
/*!40000 ALTER TABLE `vtiger_sharedcalendartodoview` DISABLE KEYS */;
INSERT INTO `vtiger_sharedcalendartodoview` VALUES (1,'Hidden',0,1,NULL),(2,'Self Todo',1,1,NULL),(3,'All Todo',2,1,NULL);
/*!40000 ALTER TABLE `vtiger_sharedcalendartodoview` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_sharedcalendartodoview_seq`
--

DROP TABLE IF EXISTS `vtiger_sharedcalendartodoview_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_sharedcalendartodoview_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_sharedcalendartodoview_seq`
--

LOCK TABLES `vtiger_sharedcalendartodoview_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_sharedcalendartodoview_seq` DISABLE KEYS */;
INSERT INTO `vtiger_sharedcalendartodoview_seq` VALUES (3);
/*!40000 ALTER TABLE `vtiger_sharedcalendartodoview_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_shareduserinfo`
--

DROP TABLE IF EXISTS `vtiger_shareduserinfo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_shareduserinfo` (
  `userid` int NOT NULL DEFAULT '0',
  `shareduserid` int NOT NULL DEFAULT '0',
  `color` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `visible` int DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_shareduserinfo`
--

LOCK TABLES `vtiger_shareduserinfo` WRITE;
/*!40000 ALTER TABLE `vtiger_shareduserinfo` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_shareduserinfo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_shippingtaxinfo`
--

DROP TABLE IF EXISTS `vtiger_shippingtaxinfo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_shippingtaxinfo` (
  `taxid` int NOT NULL,
  `taxname` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `taxlabel` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `percentage` decimal(7,3) DEFAULT NULL,
  `deleted` int DEFAULT NULL,
  `method` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `type` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `compoundon` varchar(400) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `regions` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`taxid`),
  KEY `shippingtaxinfo_taxname_idx` (`taxname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_shippingtaxinfo`
--

LOCK TABLES `vtiger_shippingtaxinfo` WRITE;
/*!40000 ALTER TABLE `vtiger_shippingtaxinfo` DISABLE KEYS */;
INSERT INTO `vtiger_shippingtaxinfo` VALUES (1,'shtax1','VAT',4.500,0,'Simple','Fixed','[]','[]'),(2,'shtax2','Sales',10.000,0,'Simple','Fixed','[]','[]'),(3,'shtax3','Service',12.500,0,'Simple','Fixed','[]','[]');
/*!40000 ALTER TABLE `vtiger_shippingtaxinfo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_shippingtaxinfo_seq`
--

DROP TABLE IF EXISTS `vtiger_shippingtaxinfo_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_shippingtaxinfo_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_shippingtaxinfo_seq`
--

LOCK TABLES `vtiger_shippingtaxinfo_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_shippingtaxinfo_seq` DISABLE KEYS */;
INSERT INTO `vtiger_shippingtaxinfo_seq` VALUES (3);
/*!40000 ALTER TABLE `vtiger_shippingtaxinfo_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_shorturls`
--

DROP TABLE IF EXISTS `vtiger_shorturls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_shorturls` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uid` varchar(50) DEFAULT NULL,
  `handler_path` varchar(400) DEFAULT NULL,
  `handler_class` varchar(100) DEFAULT NULL,
  `handler_function` varchar(100) DEFAULT NULL,
  `handler_data` varchar(255) DEFAULT NULL,
  `onetime` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_shorturls`
--

LOCK TABLES `vtiger_shorturls` WRITE;
/*!40000 ALTER TABLE `vtiger_shorturls` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_shorturls` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_smsnotifier`
--

DROP TABLE IF EXISTS `vtiger_smsnotifier`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_smsnotifier` (
  `smsnotifierid` int NOT NULL,
  `message` text,
  `status` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`smsnotifierid`),
  CONSTRAINT `fk_crmid_vtiger_smsnotifier` FOREIGN KEY (`smsnotifierid`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_smsnotifier`
--

LOCK TABLES `vtiger_smsnotifier` WRITE;
/*!40000 ALTER TABLE `vtiger_smsnotifier` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_smsnotifier` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_smsnotifier_servers`
--

DROP TABLE IF EXISTS `vtiger_smsnotifier_servers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_smsnotifier_servers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `password` varchar(255) DEFAULT NULL,
  `isactive` int DEFAULT NULL,
  `providertype` varchar(50) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `parameters` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_smsnotifier_servers`
--

LOCK TABLES `vtiger_smsnotifier_servers` WRITE;
/*!40000 ALTER TABLE `vtiger_smsnotifier_servers` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_smsnotifier_servers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_smsnotifier_status`
--

DROP TABLE IF EXISTS `vtiger_smsnotifier_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_smsnotifier_status` (
  `smsnotifierid` int DEFAULT NULL,
  `tonumber` varchar(20) DEFAULT NULL,
  `status` varchar(10) DEFAULT NULL,
  `smsmessageid` varchar(50) DEFAULT NULL,
  `needlookup` int DEFAULT '1',
  `statusid` int NOT NULL AUTO_INCREMENT,
  `statusmessage` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`statusid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_smsnotifier_status`
--

LOCK TABLES `vtiger_smsnotifier_status` WRITE;
/*!40000 ALTER TABLE `vtiger_smsnotifier_status` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_smsnotifier_status` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_smsnotifiercf`
--

DROP TABLE IF EXISTS `vtiger_smsnotifiercf`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_smsnotifiercf` (
  `smsnotifierid` int NOT NULL,
  PRIMARY KEY (`smsnotifierid`),
  CONSTRAINT `fk_smsnotifierid_vtiger_smsnotifiercf` FOREIGN KEY (`smsnotifierid`) REFERENCES `vtiger_smsnotifier` (`smsnotifierid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_smsnotifiercf`
--

LOCK TABLES `vtiger_smsnotifiercf` WRITE;
/*!40000 ALTER TABLE `vtiger_smsnotifiercf` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_smsnotifiercf` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_soapservice`
--

DROP TABLE IF EXISTS `vtiger_soapservice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_soapservice` (
  `id` int DEFAULT NULL,
  `type` varchar(25) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sessionid` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_soapservice`
--

LOCK TABLES `vtiger_soapservice` WRITE;
/*!40000 ALTER TABLE `vtiger_soapservice` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_soapservice` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_sobillads`
--

DROP TABLE IF EXISTS `vtiger_sobillads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_sobillads` (
  `sobilladdressid` int NOT NULL,
  `bill_city` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bill_code` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bill_country` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bill_state` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bill_street` text COLLATE utf8mb4_general_ci,
  `bill_pobox` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`sobilladdressid`),
  CONSTRAINT `fk_1_vtiger_sobillads` FOREIGN KEY (`sobilladdressid`) REFERENCES `vtiger_salesorder` (`salesorderid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_sobillads`
--

LOCK TABLES `vtiger_sobillads` WRITE;
/*!40000 ALTER TABLE `vtiger_sobillads` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_sobillads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_soshipads`
--

DROP TABLE IF EXISTS `vtiger_soshipads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_soshipads` (
  `soshipaddressid` int NOT NULL,
  `ship_city` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ship_code` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ship_country` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ship_state` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ship_street` text COLLATE utf8mb4_general_ci,
  `ship_pobox` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`soshipaddressid`),
  CONSTRAINT `fk_1_vtiger_soshipads` FOREIGN KEY (`soshipaddressid`) REFERENCES `vtiger_salesorder` (`salesorderid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_soshipads`
--

LOCK TABLES `vtiger_soshipads` WRITE;
/*!40000 ALTER TABLE `vtiger_soshipads` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_soshipads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_sostatus`
--

DROP TABLE IF EXISTS `vtiger_sostatus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_sostatus` (
  `sostatusid` int NOT NULL AUTO_INCREMENT,
  `sostatus` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT NULL,
  `color` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`sostatusid`),
  UNIQUE KEY `sostatus_sostatus_idx` (`sostatus`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_sostatus`
--

LOCK TABLES `vtiger_sostatus` WRITE;
/*!40000 ALTER TABLE `vtiger_sostatus` DISABLE KEYS */;
INSERT INTO `vtiger_sostatus` VALUES (1,'Created',0,166,0,NULL),(2,'Approved',0,167,1,NULL),(3,'Delivered',0,168,2,NULL),(4,'Cancelled',0,169,3,NULL);
/*!40000 ALTER TABLE `vtiger_sostatus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_sostatus_seq`
--

DROP TABLE IF EXISTS `vtiger_sostatus_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_sostatus_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_sostatus_seq`
--

LOCK TABLES `vtiger_sostatus_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_sostatus_seq` DISABLE KEYS */;
INSERT INTO `vtiger_sostatus_seq` VALUES (4);
/*!40000 ALTER TABLE `vtiger_sostatus_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_sostatushistory`
--

DROP TABLE IF EXISTS `vtiger_sostatushistory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_sostatushistory` (
  `historyid` int NOT NULL AUTO_INCREMENT,
  `salesorderid` int NOT NULL,
  `accountname` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `total` decimal(10,0) DEFAULT NULL,
  `sostatus` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lastmodified` datetime DEFAULT NULL,
  PRIMARY KEY (`historyid`),
  KEY `sostatushistory_salesorderid_idx` (`salesorderid`),
  CONSTRAINT `fk_1_vtiger_sostatushistory` FOREIGN KEY (`salesorderid`) REFERENCES `vtiger_salesorder` (`salesorderid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_sostatushistory`
--

LOCK TABLES `vtiger_sostatushistory` WRITE;
/*!40000 ALTER TABLE `vtiger_sostatushistory` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_sostatushistory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_sqltimelog`
--

DROP TABLE IF EXISTS `vtiger_sqltimelog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_sqltimelog` (
  `id` int DEFAULT NULL,
  `type` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `data` text COLLATE utf8mb4_general_ci,
  `started` decimal(20,6) DEFAULT NULL,
  `ended` decimal(20,6) DEFAULT NULL,
  `loggedon` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_sqltimelog`
--

LOCK TABLES `vtiger_sqltimelog` WRITE;
/*!40000 ALTER TABLE `vtiger_sqltimelog` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_sqltimelog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_start_hour`
--

DROP TABLE IF EXISTS `vtiger_start_hour`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_start_hour` (
  `start_hourid` int NOT NULL AUTO_INCREMENT,
  `start_hour` varchar(200) NOT NULL,
  `sortorderid` int DEFAULT NULL,
  `presence` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`start_hourid`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_start_hour`
--

LOCK TABLES `vtiger_start_hour` WRITE;
/*!40000 ALTER TABLE `vtiger_start_hour` DISABLE KEYS */;
INSERT INTO `vtiger_start_hour` VALUES (1,'00:00',0,1),(2,'01:00',1,1),(3,'02:00',2,1),(4,'03:00',3,1),(5,'04:00',4,1),(6,'05:00',5,1),(7,'06:00',6,1),(8,'07:00',7,1),(9,'08:00',8,1),(10,'09:00',9,1),(11,'10:00',10,1),(12,'11:00',11,1),(13,'12:00',12,1),(14,'13:00',13,1),(15,'14:00',14,1),(16,'15:00',15,1),(17,'16:00',16,1),(18,'17:00',17,1),(19,'18:00',18,1),(20,'19:00',19,1),(21,'20:00',20,1),(22,'21:00',21,1),(23,'22:00',22,1),(24,'23:00',23,1);
/*!40000 ALTER TABLE `vtiger_start_hour` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_start_hour_seq`
--

DROP TABLE IF EXISTS `vtiger_start_hour_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_start_hour_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_start_hour_seq`
--

LOCK TABLES `vtiger_start_hour_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_start_hour_seq` DISABLE KEYS */;
INSERT INTO `vtiger_start_hour_seq` VALUES (24);
/*!40000 ALTER TABLE `vtiger_start_hour_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_status`
--

DROP TABLE IF EXISTS `vtiger_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_status` (
  `statusid` int NOT NULL AUTO_INCREMENT,
  `status` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`statusid`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_status`
--

LOCK TABLES `vtiger_status` WRITE;
/*!40000 ALTER TABLE `vtiger_status` DISABLE KEYS */;
INSERT INTO `vtiger_status` VALUES (1,'Active',0,1),(2,'Inactive',1,1);
/*!40000 ALTER TABLE `vtiger_status` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_status_seq`
--

DROP TABLE IF EXISTS `vtiger_status_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_status_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_status_seq`
--

LOCK TABLES `vtiger_status_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_status_seq` DISABLE KEYS */;
INSERT INTO `vtiger_status_seq` VALUES (2);
/*!40000 ALTER TABLE `vtiger_status_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_systems`
--

DROP TABLE IF EXISTS `vtiger_systems`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_systems` (
  `id` int NOT NULL,
  `server` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `server_port` int DEFAULT NULL,
  `server_username` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `server_password` text COLLATE utf8mb4_general_ci,
  `server_type` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `smtp_auth` varchar(5) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `server_path` varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `from_email_field` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_systems`
--

LOCK TABLES `vtiger_systems` WRITE;
/*!40000 ALTER TABLE `vtiger_systems` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_systems` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_tab`
--

DROP TABLE IF EXISTS `vtiger_tab`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_tab` (
  `tabid` int NOT NULL DEFAULT '0',
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `tabsequence` int DEFAULT NULL,
  `tablabel` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `modifiedby` int DEFAULT NULL,
  `modifiedtime` int DEFAULT NULL,
  `customized` int DEFAULT NULL,
  `ownedby` int DEFAULT NULL,
  `isentitytype` int NOT NULL DEFAULT '1',
  `trial` int NOT NULL DEFAULT '0',
  `version` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `parent` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `source` varchar(255) COLLATE utf8mb4_general_ci DEFAULT 'custom',
  `issyncable` tinyint(1) DEFAULT '0',
  `allowduplicates` tinyint(1) DEFAULT '1',
  `sync_action_for_duplicates` int DEFAULT '1',
  `editreadonlydisplay` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`tabid`),
  UNIQUE KEY `tab_name_idx` (`name`),
  KEY `tab_modifiedby_idx` (`modifiedby`),
  KEY `tab_tabid_idx` (`tabid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_tab`
--

LOCK TABLES `vtiger_tab` WRITE;
/*!40000 ALTER TABLE `vtiger_tab` DISABLE KEYS */;
INSERT INTO `vtiger_tab` VALUES (1,'Dashboard',0,12,'Dashboards',NULL,NULL,0,1,0,0,NULL,'Analytics',NULL,1,1,1,0),(2,'Potentials',0,7,'Potentials',NULL,NULL,0,0,1,0,NULL,'Sales',NULL,1,1,1,0),(3,'Home',0,1,'Home',NULL,NULL,0,1,0,0,NULL,NULL,NULL,1,1,1,0),(4,'Contacts',0,6,'Contacts',NULL,NULL,0,0,1,0,NULL,'Sales',NULL,1,1,1,0),(6,'Accounts',0,5,'Accounts',NULL,NULL,0,0,1,0,NULL,'Sales',NULL,1,0,1,0),(7,'Leads',0,4,'Leads',NULL,NULL,0,0,1,0,NULL,'Sales',NULL,1,1,1,0),(8,'Documents',0,9,'Documents',NULL,NULL,0,0,1,0,NULL,'Tools',NULL,1,1,1,0),(9,'Calendar',0,3,'Calendar',NULL,NULL,0,0,1,0,NULL,'Tools',NULL,1,1,1,0),(10,'Emails',0,10,'Emails',NULL,NULL,0,0,1,0,NULL,'Tools',NULL,1,1,1,0),(13,'HelpDesk',0,11,'HelpDesk',NULL,NULL,0,0,1,0,NULL,'Support',NULL,1,1,1,0),(14,'Products',0,8,'Products',NULL,NULL,0,0,1,0,NULL,'Inventory',NULL,1,1,1,0),(15,'Faq',0,-1,'Faq',NULL,NULL,0,1,1,0,NULL,'Support',NULL,1,1,1,0),(16,'Events',2,-1,'Events',NULL,NULL,0,0,1,0,NULL,NULL,NULL,1,1,1,0),(18,'Vendors',0,-1,'Vendors',NULL,NULL,0,0,1,0,NULL,'Inventory',NULL,1,1,1,0),(19,'PriceBooks',0,-1,'PriceBooks',NULL,NULL,0,1,1,0,NULL,'Inventory',NULL,1,1,1,0),(20,'Quotes',0,-1,'Quotes',NULL,NULL,0,0,1,0,NULL,'Sales',NULL,1,1,1,0),(21,'PurchaseOrder',0,-1,'PurchaseOrder',NULL,NULL,0,0,1,0,NULL,'Inventory',NULL,1,1,1,0),(22,'SalesOrder',0,-1,'SalesOrder',NULL,NULL,0,0,1,0,NULL,'Sales',NULL,1,1,1,0),(23,'Invoice',0,-1,'Invoice',NULL,NULL,0,0,1,0,NULL,'Sales',NULL,1,1,1,0),(24,'Rss',0,-1,'Rss',NULL,NULL,0,1,0,0,NULL,'Tools',NULL,1,1,1,0),(25,'Reports',0,-1,'Reports',NULL,NULL,0,1,0,0,NULL,'Analytics',NULL,1,1,1,0),(26,'Campaigns',0,-1,'Campaigns',NULL,NULL,0,0,1,0,NULL,'Marketing',NULL,1,1,1,0),(27,'Portal',0,-1,'Portal',NULL,NULL,0,1,0,0,NULL,'Tools',NULL,1,1,1,0),(28,'Webmails',0,-1,'Webmails',NULL,NULL,0,1,1,0,NULL,NULL,NULL,1,1,1,0),(29,'Users',0,-1,'Users',NULL,NULL,0,1,0,0,NULL,NULL,NULL,1,1,1,0),(30,'Import',0,-1,'Import',NULL,NULL,1,0,0,0,'1.7','',NULL,1,1,1,0),(31,'ModTracker',0,-1,'ModTracker',NULL,NULL,0,0,0,0,'1.2','',NULL,1,1,1,0),(32,'MailManager',0,-1,'MailManager',NULL,NULL,1,0,0,0,'1.9','Tools',NULL,1,1,1,0),(33,'WSAPP',0,-1,'WSAPP',NULL,NULL,1,0,0,0,'3.4.4','',NULL,1,1,1,0),(34,'ServiceContracts',0,-1,'Service Contracts',NULL,NULL,0,0,1,0,'2.4','Support',NULL,1,1,1,0),(35,'Services',0,-1,'Services',NULL,NULL,0,0,1,0,'2.6','Inventory',NULL,1,1,1,0),(36,'Mobile',0,-1,'Mobile',NULL,NULL,1,0,0,0,'2.0','',NULL,1,1,1,0),(37,'ModComments',0,-1,'Comments',NULL,NULL,0,0,1,0,'2.1','',NULL,1,1,1,0),(38,'RecycleBin',0,-1,'Recycle Bin',NULL,NULL,0,0,0,0,'1.5','Tools',NULL,1,1,1,0),(39,'ProjectMilestone',0,-1,'ProjectMilestone',NULL,NULL,0,0,1,0,'3.0','Support',NULL,1,1,1,0),(40,'ProjectTask',0,-1,'ProjectTask',NULL,NULL,0,0,1,0,'3.1','Support',NULL,1,1,1,0),(41,'Project',0,-1,'Project',NULL,NULL,0,0,1,0,'3.3','Support',NULL,1,1,1,0),(42,'SMSNotifier',0,-1,'SMSNotifier',NULL,NULL,0,0,1,0,'2.1','Sales',NULL,1,1,1,0),(43,'Assets',0,-1,'Assets',NULL,NULL,0,0,1,0,'2.0','Inventory',NULL,1,1,1,0),(44,'Webforms',0,-1,'Webforms',NULL,NULL,0,0,0,0,'1.6','',NULL,1,1,1,0),(45,'CustomerPortal',0,-1,'CustomerPortal',NULL,NULL,0,0,0,0,'1.4','',NULL,1,1,1,0),(47,'EmailTemplates',0,-1,'Email Templates',NULL,NULL,1,0,0,0,'1.0','Tools',NULL,1,1,1,0),(48,'PDFTemplates',0,-1,'PDFTemplates',NULL,NULL,1,0,0,0,'0','Tools','custom',0,1,1,0),(49,'Dailyreports',0,-1,'Dailyreports',NULL,NULL,1,0,1,0,'0','Sales','custom',0,1,1,0);
/*!40000 ALTER TABLE `vtiger_tab` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_tab_info`
--

DROP TABLE IF EXISTS `vtiger_tab_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_tab_info` (
  `tabid` int DEFAULT NULL,
  `prefname` varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `prefvalue` varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL,
  KEY `fk_1_vtiger_tab_info` (`tabid`),
  CONSTRAINT `fk_1_vtiger_tab_info` FOREIGN KEY (`tabid`) REFERENCES `vtiger_tab` (`tabid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_tab_info`
--

LOCK TABLES `vtiger_tab_info` WRITE;
/*!40000 ALTER TABLE `vtiger_tab_info` DISABLE KEYS */;
INSERT INTO `vtiger_tab_info` VALUES (30,'vtiger_min_version','6.0.0rc'),(30,'vtiger_max_version','7.*'),(31,'vtiger_min_version','6.0.0rc'),(32,'vtiger_min_version','6.0.0RC'),(33,'vtiger_min_version','6.0.0rc'),(34,'vtiger_min_version','6.0.0rc'),(35,'vtiger_min_version','6.0.0rc'),(35,'vtiger_max_version','7.*'),(36,'vtiger_min_version','6.0.0rc'),(37,'vtiger_min_version','6.0.0rc'),(37,'vtiger_max_version','7.*'),(38,'vtiger_min_version','6.0.0rc'),(38,'vtiger_max_version','7.*'),(39,'vtiger_min_version','6.0.0rc'),(39,'vtiger_max_version','7.*'),(40,'vtiger_min_version','6.0.0rc'),(41,'vtiger_min_version','6.0.0rc'),(42,'vtiger_min_version','6.0.0'),(42,'vtiger_max_version','7.*'),(43,'vtiger_min_version','6.0.0rc'),(43,'vtiger_max_version','7.*'),(44,'vtiger_min_version','6.0.0rc'),(44,'vtiger_max_version','7.*'),(45,'vtiger_min_version','6.0.0rc'),(45,'vtiger_max_version','7.*'),(47,'vtiger_min_version','6.0.0rc'),(47,'vtiger_max_version','7.*');
/*!40000 ALTER TABLE `vtiger_tab_info` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_taskpriority`
--

DROP TABLE IF EXISTS `vtiger_taskpriority`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_taskpriority` (
  `taskpriorityid` int NOT NULL AUTO_INCREMENT,
  `taskpriority` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT NULL,
  `color` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`taskpriorityid`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_taskpriority`
--

LOCK TABLES `vtiger_taskpriority` WRITE;
/*!40000 ALTER TABLE `vtiger_taskpriority` DISABLE KEYS */;
INSERT INTO `vtiger_taskpriority` VALUES (1,'High',1,170,0,NULL),(2,'Medium',1,171,1,NULL),(3,'Low',1,172,2,NULL);
/*!40000 ALTER TABLE `vtiger_taskpriority` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_taskpriority_seq`
--

DROP TABLE IF EXISTS `vtiger_taskpriority_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_taskpriority_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_taskpriority_seq`
--

LOCK TABLES `vtiger_taskpriority_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_taskpriority_seq` DISABLE KEYS */;
INSERT INTO `vtiger_taskpriority_seq` VALUES (3);
/*!40000 ALTER TABLE `vtiger_taskpriority_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_taskstatus`
--

DROP TABLE IF EXISTS `vtiger_taskstatus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_taskstatus` (
  `taskstatusid` int NOT NULL AUTO_INCREMENT,
  `taskstatus` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT NULL,
  `color` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`taskstatusid`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_taskstatus`
--

LOCK TABLES `vtiger_taskstatus` WRITE;
/*!40000 ALTER TABLE `vtiger_taskstatus` DISABLE KEYS */;
INSERT INTO `vtiger_taskstatus` VALUES (1,'Not Started',0,173,0,NULL),(2,'In Progress',0,174,1,NULL),(3,'Completed',0,175,2,NULL),(4,'Pending Input',0,176,3,NULL),(5,'Deferred',0,177,4,NULL),(6,'Planned',0,178,5,NULL);
/*!40000 ALTER TABLE `vtiger_taskstatus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_taskstatus_seq`
--

DROP TABLE IF EXISTS `vtiger_taskstatus_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_taskstatus_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_taskstatus_seq`
--

LOCK TABLES `vtiger_taskstatus_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_taskstatus_seq` DISABLE KEYS */;
INSERT INTO `vtiger_taskstatus_seq` VALUES (6);
/*!40000 ALTER TABLE `vtiger_taskstatus_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_taxclass`
--

DROP TABLE IF EXISTS `vtiger_taxclass`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_taxclass` (
  `taxclassid` int NOT NULL AUTO_INCREMENT,
  `taxclass` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `sortorderid` int NOT NULL DEFAULT '0',
  `presence` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`taxclassid`),
  UNIQUE KEY `taxclass_carrier_idx` (`taxclass`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_taxclass`
--

LOCK TABLES `vtiger_taxclass` WRITE;
/*!40000 ALTER TABLE `vtiger_taxclass` DISABLE KEYS */;
INSERT INTO `vtiger_taxclass` VALUES (1,'SalesTax',0,1),(2,'Vat',1,1);
/*!40000 ALTER TABLE `vtiger_taxclass` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_taxclass_seq`
--

DROP TABLE IF EXISTS `vtiger_taxclass_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_taxclass_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_taxclass_seq`
--

LOCK TABLES `vtiger_taxclass_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_taxclass_seq` DISABLE KEYS */;
INSERT INTO `vtiger_taxclass_seq` VALUES (2);
/*!40000 ALTER TABLE `vtiger_taxclass_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_taxregions`
--

DROP TABLE IF EXISTS `vtiger_taxregions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_taxregions` (
  `regionid` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`regionid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_taxregions`
--

LOCK TABLES `vtiger_taxregions` WRITE;
/*!40000 ALTER TABLE `vtiger_taxregions` DISABLE KEYS */;
INSERT INTO `vtiger_taxregions` VALUES (1,'LBL_JAPAN');
/*!40000 ALTER TABLE `vtiger_taxregions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_ticketcategories`
--

DROP TABLE IF EXISTS `vtiger_ticketcategories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_ticketcategories` (
  `ticketcategories_id` int NOT NULL AUTO_INCREMENT,
  `ticketcategories` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `presence` int NOT NULL DEFAULT '0',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT NULL,
  `color` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`ticketcategories_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_ticketcategories`
--

LOCK TABLES `vtiger_ticketcategories` WRITE;
/*!40000 ALTER TABLE `vtiger_ticketcategories` DISABLE KEYS */;
INSERT INTO `vtiger_ticketcategories` VALUES (1,'Big Problem',1,179,0,NULL),(2,'Small Problem',1,180,1,NULL),(3,'Other Problem',1,181,2,NULL);
/*!40000 ALTER TABLE `vtiger_ticketcategories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_ticketcategories_seq`
--

DROP TABLE IF EXISTS `vtiger_ticketcategories_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_ticketcategories_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_ticketcategories_seq`
--

LOCK TABLES `vtiger_ticketcategories_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_ticketcategories_seq` DISABLE KEYS */;
INSERT INTO `vtiger_ticketcategories_seq` VALUES (3);
/*!40000 ALTER TABLE `vtiger_ticketcategories_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_ticketcf`
--

DROP TABLE IF EXISTS `vtiger_ticketcf`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_ticketcf` (
  `ticketid` int NOT NULL,
  `from_portal` varchar(3) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`ticketid`),
  CONSTRAINT `fk_1_vtiger_ticketcf` FOREIGN KEY (`ticketid`) REFERENCES `vtiger_troubletickets` (`ticketid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_ticketcf`
--

LOCK TABLES `vtiger_ticketcf` WRITE;
/*!40000 ALTER TABLE `vtiger_ticketcf` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_ticketcf` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_ticketcomments`
--

DROP TABLE IF EXISTS `vtiger_ticketcomments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_ticketcomments` (
  `commentid` int NOT NULL AUTO_INCREMENT,
  `ticketid` int DEFAULT NULL,
  `comments` text COLLATE utf8mb4_general_ci,
  `ownerid` int NOT NULL DEFAULT '0',
  `ownertype` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `createdtime` datetime NOT NULL,
  PRIMARY KEY (`commentid`),
  KEY `ticketcomments_ticketid_idx` (`ticketid`),
  CONSTRAINT `fk_1_vtiger_ticketcomments` FOREIGN KEY (`ticketid`) REFERENCES `vtiger_troubletickets` (`ticketid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_ticketcomments`
--

LOCK TABLES `vtiger_ticketcomments` WRITE;
/*!40000 ALTER TABLE `vtiger_ticketcomments` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_ticketcomments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_ticketpriorities`
--

DROP TABLE IF EXISTS `vtiger_ticketpriorities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_ticketpriorities` (
  `ticketpriorities_id` int NOT NULL AUTO_INCREMENT,
  `ticketpriorities` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `presence` int NOT NULL DEFAULT '0',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT NULL,
  `color` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`ticketpriorities_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_ticketpriorities`
--

LOCK TABLES `vtiger_ticketpriorities` WRITE;
/*!40000 ALTER TABLE `vtiger_ticketpriorities` DISABLE KEYS */;
INSERT INTO `vtiger_ticketpriorities` VALUES (1,'Low',1,182,0,NULL),(2,'Normal',1,183,1,NULL),(3,'High',1,184,2,NULL),(4,'Urgent',1,185,3,NULL);
/*!40000 ALTER TABLE `vtiger_ticketpriorities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_ticketpriorities_seq`
--

DROP TABLE IF EXISTS `vtiger_ticketpriorities_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_ticketpriorities_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_ticketpriorities_seq`
--

LOCK TABLES `vtiger_ticketpriorities_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_ticketpriorities_seq` DISABLE KEYS */;
INSERT INTO `vtiger_ticketpriorities_seq` VALUES (4);
/*!40000 ALTER TABLE `vtiger_ticketpriorities_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_ticketseverities`
--

DROP TABLE IF EXISTS `vtiger_ticketseverities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_ticketseverities` (
  `ticketseverities_id` int NOT NULL AUTO_INCREMENT,
  `ticketseverities` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `presence` int NOT NULL DEFAULT '0',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT NULL,
  `color` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`ticketseverities_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_ticketseverities`
--

LOCK TABLES `vtiger_ticketseverities` WRITE;
/*!40000 ALTER TABLE `vtiger_ticketseverities` DISABLE KEYS */;
INSERT INTO `vtiger_ticketseverities` VALUES (1,'Minor',1,186,0,NULL),(2,'Major',1,187,1,NULL),(3,'Feature',1,188,2,NULL),(4,'Critical',1,189,3,NULL);
/*!40000 ALTER TABLE `vtiger_ticketseverities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_ticketseverities_seq`
--

DROP TABLE IF EXISTS `vtiger_ticketseverities_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_ticketseverities_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_ticketseverities_seq`
--

LOCK TABLES `vtiger_ticketseverities_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_ticketseverities_seq` DISABLE KEYS */;
INSERT INTO `vtiger_ticketseverities_seq` VALUES (4);
/*!40000 ALTER TABLE `vtiger_ticketseverities_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_ticketstatus`
--

DROP TABLE IF EXISTS `vtiger_ticketstatus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_ticketstatus` (
  `ticketstatus_id` int NOT NULL AUTO_INCREMENT,
  `ticketstatus` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `presence` int NOT NULL DEFAULT '0',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT NULL,
  `color` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`ticketstatus_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_ticketstatus`
--

LOCK TABLES `vtiger_ticketstatus` WRITE;
/*!40000 ALTER TABLE `vtiger_ticketstatus` DISABLE KEYS */;
INSERT INTO `vtiger_ticketstatus` VALUES (1,'Open',0,190,0,NULL),(2,'In Progress',0,191,1,NULL),(3,'Wait For Response',0,192,2,NULL),(4,'Closed',0,193,3,NULL);
/*!40000 ALTER TABLE `vtiger_ticketstatus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_ticketstatus_seq`
--

DROP TABLE IF EXISTS `vtiger_ticketstatus_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_ticketstatus_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_ticketstatus_seq`
--

LOCK TABLES `vtiger_ticketstatus_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_ticketstatus_seq` DISABLE KEYS */;
INSERT INTO `vtiger_ticketstatus_seq` VALUES (4);
/*!40000 ALTER TABLE `vtiger_ticketstatus_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_time_zone`
--

DROP TABLE IF EXISTS `vtiger_time_zone`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_time_zone` (
  `time_zoneid` int NOT NULL AUTO_INCREMENT,
  `time_zone` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `sortorderid` int NOT NULL DEFAULT '0',
  `presence` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`time_zoneid`)
) ENGINE=InnoDB AUTO_INCREMENT=98 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_time_zone`
--

LOCK TABLES `vtiger_time_zone` WRITE;
/*!40000 ALTER TABLE `vtiger_time_zone` DISABLE KEYS */;
INSERT INTO `vtiger_time_zone` VALUES (1,'Pacific/Midway',1,1),(2,'Pacific/Samoa',0,1),(3,'Pacific/Honolulu',2,1),(4,'America/Anchorage',3,1),(5,'America/Los_Angeles',5,1),(6,'America/Tijuana',4,1),(7,'America/Denver',6,1),(8,'America/Chihuahua',8,1),(9,'America/Mazatlan',7,1),(10,'America/Phoenix',9,1),(11,'America/Regina',10,1),(12,'America/Tegucigalpa',14,1),(13,'America/Chicago',13,1),(14,'America/Mexico_City',12,1),(15,'America/Monterrey',11,1),(16,'America/New_York',18,1),(17,'America/Bogota',19,1),(18,'America/Lima',16,1),(19,'America/Rio_Branco',15,1),(20,'America/Indiana/Indianapolis',17,1),(21,'America/Caracas',20,1),(22,'America/Halifax',25,1),(23,'America/Manaus',22,1),(24,'America/Santiago',21,1),(25,'America/La_Paz',23,1),(26,'America/Cuiaba',24,1),(27,'America/Asuncion',26,1),(28,'America/St_Johns',27,1),(29,'America/Argentina/Buenos_Aires',30,1),(30,'America/Sao_Paulo',31,1),(31,'America/Godthab',29,1),(32,'America/Montevideo',28,1),(33,'Atlantic/South_Georgia',32,1),(34,'Atlantic/Azores',34,1),(35,'Atlantic/Cape_Verde',33,1),(36,'Europe/London',37,1),(37,'UTC',36,1),(38,'Africa/Monrovia',38,1),(39,'Africa/Casablanca',35,1),(40,'Europe/Belgrade',40,1),(41,'Europe/Sarajevo',42,1),(42,'Europe/Brussels',41,1),(43,'Africa/Algiers',43,1),(44,'Europe/Amsterdam',39,1),(45,'Europe/Minsk',52,1),(46,'Africa/Cairo',47,1),(47,'Europe/Helsinki',49,1),(48,'Europe/Athens',45,1),(49,'Europe/Istanbul',50,1),(50,'Asia/Jerusalem',51,1),(51,'Asia/Amman',44,1),(52,'Asia/Beirut',46,1),(53,'Africa/Windhoek',53,1),(54,'Africa/Harare',48,1),(55,'Asia/Kuwait',55,1),(56,'Asia/Baghdad',54,1),(57,'Africa/Nairobi',57,1),(58,'Asia/Tehran',58,1),(59,'Asia/Tbilisi',61,1),(60,'Europe/Moscow',56,1),(61,'Asia/Muscat',59,1),(62,'Asia/Baku',60,1),(63,'Asia/Yerevan',62,1),(64,'Asia/Karachi',63,1),(65,'Asia/Tashkent',64,1),(66,'Asia/Kolkata',65,1),(67,'Asia/Colombo',66,1),(68,'Asia/Katmandu',67,1),(69,'Asia/Dhaka',69,1),(70,'Asia/Almaty',68,1),(71,'Asia/Yekaterinburg',70,1),(72,'Asia/Rangoon',71,1),(73,'Asia/Novosibirsk',73,1),(74,'Asia/Bangkok',72,1),(75,'Asia/Brunei',74,1),(76,'Asia/Krasnoyarsk',75,1),(77,'Asia/Ulaanbaatar',79,1),(78,'Asia/Kuala_Lumpur',76,1),(79,'Asia/Taipei',78,1),(80,'Australia/Perth',77,1),(81,'Asia/Irkutsk',80,1),(82,'Asia/Seoul',81,1),(83,'Asia/Tokyo',82,1),(84,'Australia/Darwin',84,1),(85,'Australia/Adelaide',83,1),(86,'Australia/Canberra',86,1),(87,'Australia/Brisbane',85,1),(88,'Australia/Hobart',88,1),(89,'Asia/Vladivostok',89,1),(90,'Pacific/Guam',87,1),(91,'Asia/Yakutsk',90,1),(92,'Pacific/Fiji',93,1),(93,'Asia/Kamchatka',94,1),(94,'Pacific/Auckland',92,1),(95,'Asia/Magadan',95,1),(96,'Pacific/Tongatapu',96,1),(97,'Etc/GMT-11',91,1);
/*!40000 ALTER TABLE `vtiger_time_zone` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_time_zone_seq`
--

DROP TABLE IF EXISTS `vtiger_time_zone_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_time_zone_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_time_zone_seq`
--

LOCK TABLES `vtiger_time_zone_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_time_zone_seq` DISABLE KEYS */;
INSERT INTO `vtiger_time_zone_seq` VALUES (96);
/*!40000 ALTER TABLE `vtiger_time_zone_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_tmp_read_group_rel_sharing_per`
--

DROP TABLE IF EXISTS `vtiger_tmp_read_group_rel_sharing_per`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_tmp_read_group_rel_sharing_per` (
  `userid` int NOT NULL,
  `tabid` int NOT NULL,
  `relatedtabid` int NOT NULL,
  `sharedgroupid` int NOT NULL,
  PRIMARY KEY (`userid`,`tabid`,`relatedtabid`,`sharedgroupid`),
  KEY `tmp_read_group_rel_sharing_per_userid_sharedgroupid_tabid` (`userid`,`sharedgroupid`,`tabid`),
  CONSTRAINT `fk_4_vtiger_tmp_read_group_rel_sharing_per` FOREIGN KEY (`userid`) REFERENCES `vtiger_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_tmp_read_group_rel_sharing_per`
--

LOCK TABLES `vtiger_tmp_read_group_rel_sharing_per` WRITE;
/*!40000 ALTER TABLE `vtiger_tmp_read_group_rel_sharing_per` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_tmp_read_group_rel_sharing_per` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_tmp_read_group_sharing_per`
--

DROP TABLE IF EXISTS `vtiger_tmp_read_group_sharing_per`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_tmp_read_group_sharing_per` (
  `userid` int NOT NULL,
  `tabid` int NOT NULL,
  `sharedgroupid` int NOT NULL,
  PRIMARY KEY (`userid`,`tabid`,`sharedgroupid`),
  KEY `tmp_read_group_sharing_per_userid_sharedgroupid_idx` (`userid`,`sharedgroupid`),
  CONSTRAINT `fk_3_vtiger_tmp_read_group_sharing_per` FOREIGN KEY (`userid`) REFERENCES `vtiger_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_tmp_read_group_sharing_per`
--

LOCK TABLES `vtiger_tmp_read_group_sharing_per` WRITE;
/*!40000 ALTER TABLE `vtiger_tmp_read_group_sharing_per` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_tmp_read_group_sharing_per` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_tmp_read_user_rel_sharing_per`
--

DROP TABLE IF EXISTS `vtiger_tmp_read_user_rel_sharing_per`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_tmp_read_user_rel_sharing_per` (
  `userid` int NOT NULL,
  `tabid` int NOT NULL,
  `relatedtabid` int NOT NULL,
  `shareduserid` int NOT NULL,
  PRIMARY KEY (`userid`,`tabid`,`relatedtabid`,`shareduserid`),
  KEY `tmp_read_user_rel_sharing_per_userid_shared_reltabid_idx` (`userid`,`shareduserid`,`relatedtabid`),
  CONSTRAINT `fk_4_vtiger_tmp_read_user_rel_sharing_per` FOREIGN KEY (`userid`) REFERENCES `vtiger_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_tmp_read_user_rel_sharing_per`
--

LOCK TABLES `vtiger_tmp_read_user_rel_sharing_per` WRITE;
/*!40000 ALTER TABLE `vtiger_tmp_read_user_rel_sharing_per` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_tmp_read_user_rel_sharing_per` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_tmp_read_user_sharing_per`
--

DROP TABLE IF EXISTS `vtiger_tmp_read_user_sharing_per`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_tmp_read_user_sharing_per` (
  `userid` int NOT NULL,
  `tabid` int NOT NULL,
  `shareduserid` int NOT NULL,
  PRIMARY KEY (`userid`,`tabid`,`shareduserid`),
  KEY `tmp_read_user_sharing_per_userid_shareduserid_idx` (`userid`,`shareduserid`),
  CONSTRAINT `fk_3_vtiger_tmp_read_user_sharing_per` FOREIGN KEY (`userid`) REFERENCES `vtiger_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_tmp_read_user_sharing_per`
--

LOCK TABLES `vtiger_tmp_read_user_sharing_per` WRITE;
/*!40000 ALTER TABLE `vtiger_tmp_read_user_sharing_per` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_tmp_read_user_sharing_per` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_tmp_write_group_rel_sharing_per`
--

DROP TABLE IF EXISTS `vtiger_tmp_write_group_rel_sharing_per`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_tmp_write_group_rel_sharing_per` (
  `userid` int NOT NULL,
  `tabid` int NOT NULL,
  `relatedtabid` int NOT NULL,
  `sharedgroupid` int NOT NULL,
  PRIMARY KEY (`userid`,`tabid`,`relatedtabid`,`sharedgroupid`),
  KEY `tmp_write_group_rel_sharing_per_userid_sharedgroupid_tabid_idx` (`userid`,`sharedgroupid`,`tabid`),
  CONSTRAINT `fk_4_vtiger_tmp_write_group_rel_sharing_per` FOREIGN KEY (`userid`) REFERENCES `vtiger_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_tmp_write_group_rel_sharing_per`
--

LOCK TABLES `vtiger_tmp_write_group_rel_sharing_per` WRITE;
/*!40000 ALTER TABLE `vtiger_tmp_write_group_rel_sharing_per` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_tmp_write_group_rel_sharing_per` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_tmp_write_group_sharing_per`
--

DROP TABLE IF EXISTS `vtiger_tmp_write_group_sharing_per`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_tmp_write_group_sharing_per` (
  `userid` int NOT NULL,
  `tabid` int NOT NULL,
  `sharedgroupid` int NOT NULL,
  PRIMARY KEY (`userid`,`tabid`,`sharedgroupid`),
  KEY `tmp_write_group_sharing_per_UK1` (`userid`,`sharedgroupid`),
  CONSTRAINT `fk_3_vtiger_tmp_write_group_sharing_per` FOREIGN KEY (`userid`) REFERENCES `vtiger_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_tmp_write_group_sharing_per`
--

LOCK TABLES `vtiger_tmp_write_group_sharing_per` WRITE;
/*!40000 ALTER TABLE `vtiger_tmp_write_group_sharing_per` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_tmp_write_group_sharing_per` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_tmp_write_user_rel_sharing_per`
--

DROP TABLE IF EXISTS `vtiger_tmp_write_user_rel_sharing_per`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_tmp_write_user_rel_sharing_per` (
  `userid` int NOT NULL,
  `tabid` int NOT NULL,
  `relatedtabid` int NOT NULL,
  `shareduserid` int NOT NULL,
  PRIMARY KEY (`userid`,`tabid`,`relatedtabid`,`shareduserid`),
  KEY `tmp_write_user_rel_sharing_per_userid_sharduserid_tabid_idx` (`userid`,`shareduserid`,`tabid`),
  CONSTRAINT `fk_4_vtiger_tmp_write_user_rel_sharing_per` FOREIGN KEY (`userid`) REFERENCES `vtiger_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_tmp_write_user_rel_sharing_per`
--

LOCK TABLES `vtiger_tmp_write_user_rel_sharing_per` WRITE;
/*!40000 ALTER TABLE `vtiger_tmp_write_user_rel_sharing_per` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_tmp_write_user_rel_sharing_per` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_tmp_write_user_sharing_per`
--

DROP TABLE IF EXISTS `vtiger_tmp_write_user_sharing_per`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_tmp_write_user_sharing_per` (
  `userid` int NOT NULL,
  `tabid` int NOT NULL,
  `shareduserid` int NOT NULL,
  PRIMARY KEY (`userid`,`tabid`,`shareduserid`),
  KEY `tmp_write_user_sharing_per_userid_shareduserid_idx` (`userid`,`shareduserid`),
  CONSTRAINT `fk_3_vtiger_tmp_write_user_sharing_per` FOREIGN KEY (`userid`) REFERENCES `vtiger_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_tmp_write_user_sharing_per`
--

LOCK TABLES `vtiger_tmp_write_user_sharing_per` WRITE;
/*!40000 ALTER TABLE `vtiger_tmp_write_user_sharing_per` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_tmp_write_user_sharing_per` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_tracker`
--

DROP TABLE IF EXISTS `vtiger_tracker`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_tracker` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` varchar(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `module_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `item_id` varchar(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `item_summary` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_tracker`
--

LOCK TABLES `vtiger_tracker` WRITE;
/*!40000 ALTER TABLE `vtiger_tracker` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_tracker` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_tracking_unit`
--

DROP TABLE IF EXISTS `vtiger_tracking_unit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_tracking_unit` (
  `tracking_unitid` int NOT NULL AUTO_INCREMENT,
  `tracking_unit` varchar(200) NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT '0',
  `color` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`tracking_unitid`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_tracking_unit`
--

LOCK TABLES `vtiger_tracking_unit` WRITE;
/*!40000 ALTER TABLE `vtiger_tracking_unit` DISABLE KEYS */;
INSERT INTO `vtiger_tracking_unit` VALUES (2,'Hours',1,211,2,NULL),(3,'Days',1,212,3,NULL),(4,'Incidents',1,213,4,NULL);
/*!40000 ALTER TABLE `vtiger_tracking_unit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_tracking_unit_seq`
--

DROP TABLE IF EXISTS `vtiger_tracking_unit_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_tracking_unit_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_tracking_unit_seq`
--

LOCK TABLES `vtiger_tracking_unit_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_tracking_unit_seq` DISABLE KEYS */;
INSERT INTO `vtiger_tracking_unit_seq` VALUES (4);
/*!40000 ALTER TABLE `vtiger_tracking_unit_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_troubletickets`
--

DROP TABLE IF EXISTS `vtiger_troubletickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_troubletickets` (
  `ticketid` int NOT NULL,
  `ticket_no` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `groupname` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `parent_id` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `product_id` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `priority` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `severity` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `category` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `solution` longtext COLLATE utf8mb4_general_ci,
  `update_log` text COLLATE utf8mb4_general_ci,
  `version_id` int DEFAULT NULL,
  `hours` decimal(25,8) DEFAULT NULL,
  `days` decimal(25,8) DEFAULT NULL,
  `contact_id` int DEFAULT NULL,
  `tags` varchar(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `smcreatorid` int DEFAULT NULL,
  `smownerid` int DEFAULT NULL,
  `modifiedby` int DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_general_ci,
  `createdtime` datetime DEFAULT NULL,
  `modifiedtime` datetime DEFAULT NULL,
  `viewedtime` datetime DEFAULT NULL,
  `deleted` int DEFAULT NULL,
  `label` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `smgroupid` int DEFAULT NULL,
  `source` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`ticketid`),
  KEY `troubletickets_ticketid_idx` (`ticketid`),
  KEY `troubletickets_status_idx` (`status`),
  KEY `contact_id` (`contact_id`),
  KEY `parent_id` (`parent_id`),
  KEY `idx_info` (`deleted`,`modifiedtime`),
  KEY `idx_label` (`deleted`,`label`),
  KEY `idx_owner` (`deleted`,`smownerid`),
  CONSTRAINT `fk_1_vtiger_troubletickets` FOREIGN KEY (`ticketid`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_troubletickets`
--

LOCK TABLES `vtiger_troubletickets` WRITE;
/*!40000 ALTER TABLE `vtiger_troubletickets` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_troubletickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_usageunit`
--

DROP TABLE IF EXISTS `vtiger_usageunit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_usageunit` (
  `usageunitid` int NOT NULL AUTO_INCREMENT,
  `usageunit` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `presence` int NOT NULL DEFAULT '1',
  `picklist_valueid` int NOT NULL DEFAULT '0',
  `sortorderid` int DEFAULT NULL,
  `color` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`usageunitid`),
  UNIQUE KEY `usageunit_usageunit_idx` (`usageunit`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_usageunit`
--

LOCK TABLES `vtiger_usageunit` WRITE;
/*!40000 ALTER TABLE `vtiger_usageunit` DISABLE KEYS */;
INSERT INTO `vtiger_usageunit` VALUES (1,'Box',1,194,0,NULL),(2,'Carton',1,195,1,NULL),(3,'Dozen',1,196,2,NULL),(4,'Each',1,197,3,NULL),(5,'Hours',1,198,4,NULL),(6,'Impressions',1,199,5,NULL),(7,'Lb',1,200,6,NULL),(8,'M',1,201,7,NULL),(9,'Pack',1,202,8,NULL),(10,'Pages',1,203,9,NULL),(11,'Pieces',1,204,10,NULL),(12,'Quantity',1,205,11,NULL),(13,'Reams',1,206,12,NULL),(14,'Sheet',1,207,13,NULL),(15,'Spiral Binder',1,208,14,NULL),(16,'Sq Ft',1,209,15,NULL);
/*!40000 ALTER TABLE `vtiger_usageunit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_usageunit_seq`
--

DROP TABLE IF EXISTS `vtiger_usageunit_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_usageunit_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_usageunit_seq`
--

LOCK TABLES `vtiger_usageunit_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_usageunit_seq` DISABLE KEYS */;
INSERT INTO `vtiger_usageunit_seq` VALUES (16);
/*!40000 ALTER TABLE `vtiger_usageunit_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_user2mergefields`
--

DROP TABLE IF EXISTS `vtiger_user2mergefields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_user2mergefields` (
  `userid` int DEFAULT NULL,
  `tabid` int DEFAULT NULL,
  `fieldid` int DEFAULT NULL,
  `visible` int DEFAULT NULL,
  KEY `userid_tabid_idx` (`userid`,`tabid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_user2mergefields`
--

LOCK TABLES `vtiger_user2mergefields` WRITE;
/*!40000 ALTER TABLE `vtiger_user2mergefields` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_user2mergefields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_user2role`
--

DROP TABLE IF EXISTS `vtiger_user2role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_user2role` (
  `userid` int NOT NULL,
  `roleid` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`userid`),
  KEY `user2role_roleid_idx` (`roleid`),
  CONSTRAINT `fk_2_vtiger_user2role` FOREIGN KEY (`userid`) REFERENCES `vtiger_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_user2role`
--

LOCK TABLES `vtiger_user2role` WRITE;
/*!40000 ALTER TABLE `vtiger_user2role` DISABLE KEYS */;
INSERT INTO `vtiger_user2role` VALUES (1,'H2');
/*!40000 ALTER TABLE `vtiger_user2role` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_user_credentials`
--

DROP TABLE IF EXISTS `vtiger_user_credentials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_user_credentials` (
  `id` int NOT NULL AUTO_INCREMENT,
  `userid` int NOT NULL,
  `type` varchar(7) NOT NULL,
  `device_name` varchar(64) DEFAULT NULL,
  `totp_secret` varchar(32) DEFAULT NULL,
  `passkey_credential` json DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_user_credentials`
--

LOCK TABLES `vtiger_user_credentials` WRITE;
/*!40000 ALTER TABLE `vtiger_user_credentials` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_user_credentials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_user_lock`
--

DROP TABLE IF EXISTS `vtiger_user_lock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_user_lock` (
  `userid` int NOT NULL,
  `signature_count` tinyint(1) NOT NULL,
  `lock_time` datetime DEFAULT NULL,
  PRIMARY KEY (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_user_lock`
--

LOCK TABLES `vtiger_user_lock` WRITE;
/*!40000 ALTER TABLE `vtiger_user_lock` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_user_lock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_user_module_preferences`
--

DROP TABLE IF EXISTS `vtiger_user_module_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_user_module_preferences` (
  `userid` int NOT NULL,
  `tabid` int NOT NULL,
  `default_cvid` int NOT NULL,
  PRIMARY KEY (`userid`,`tabid`),
  KEY `fk_2_vtiger_user_module_preferences` (`tabid`),
  CONSTRAINT `fk_2_vtiger_user_module_preferences` FOREIGN KEY (`tabid`) REFERENCES `vtiger_tab` (`tabid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_user_module_preferences`
--

LOCK TABLES `vtiger_user_module_preferences` WRITE;
/*!40000 ALTER TABLE `vtiger_user_module_preferences` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_user_module_preferences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_users`
--

DROP TABLE IF EXISTS `vtiger_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_password` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cal_color` varchar(25) COLLATE utf8mb4_general_ci DEFAULT '#E6FAD8',
  `first_name` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `last_name` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reports_to_id` varchar(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_admin` varchar(3) COLLATE utf8mb4_general_ci DEFAULT '0',
  `currency_id` int NOT NULL DEFAULT '1',
  `description` text COLLATE utf8mb4_general_ci,
  `date_entered` timestamp NOT NULL,
  `date_modified` datetime DEFAULT NULL,
  `modified_user_id` varchar(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `title` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `department` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone_home` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone_mobile` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone_work` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone_other` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone_fax` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email1` varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email2` varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `secondaryemail` varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(25) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `signature` text COLLATE utf8mb4_general_ci,
  `address_street` text COLLATE utf8mb4_general_ci,
  `address_city` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address_state` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address_country` varchar(25) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address_postalcode` varchar(9) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_preferences` text COLLATE utf8mb4_general_ci,
  `tz` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `holidays` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `namedays` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `workdays` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `weekstart` int DEFAULT NULL,
  `date_format` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `hour_format` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'am/pm',
  `start_hour` varchar(30) COLLATE utf8mb4_general_ci DEFAULT '10:00',
  `end_hour` varchar(30) COLLATE utf8mb4_general_ci DEFAULT '23:00',
  `is_owner` varchar(100) COLLATE utf8mb4_general_ci DEFAULT '0',
  `activity_view` varchar(200) COLLATE utf8mb4_general_ci DEFAULT 'Today',
  `lead_view` varchar(200) COLLATE utf8mb4_general_ci DEFAULT 'Today',
  `imagename` varchar(250) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted` int NOT NULL DEFAULT '0',
  `confirm_password` varchar(300) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `internal_mailer` varchar(3) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1',
  `reminder_interval` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reminder_next_time` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `crypt_type` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'MD5',
  `accesskey` varchar(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `theme` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `language` varchar(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `time_zone` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `currency_grouping_pattern` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `currency_decimal_separator` varchar(2) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `currency_grouping_separator` varchar(2) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `currency_symbol_placement` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `userlabel` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `no_of_currency_decimals` varchar(2) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `truncate_trailing_zeros` varchar(3) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dayoftheweek` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `callduration` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `othereventduration` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `calendarsharedtype` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `default_record_view` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `leftpanelhide` varchar(3) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rowheight` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `defaulteventstatus` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `defaultactivitytype` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `hidecompletedevents` int DEFAULT NULL,
  `defaultcalendarview` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `defaultlandingpage` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sharedcalendartodoview` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_user_name_idx` (`user_name`),
  KEY `user_user_password_idx` (`user_password`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_users`
--

LOCK TABLES `vtiger_users` WRITE;
/*!40000 ALTER TABLE `vtiger_users` DISABLE KEYS */;
INSERT INTO `vtiger_users` VALUES (1,'admin','$2y$10$FrHfeDFVU4x0oBHhxJ3WQOdgNYbpVBZAaFcdKPkK0UcAiYKlGsRde','#E6FAD8','','システム管理者','','on',1,'','0000-00-00 00:00:00',NULL,NULL,'','','','','','','','admin@example.com','','','Active','','','','','','',NULL,NULL,NULL,NULL,NULL,NULL,'yyyy-mm-dd','24','00:00','23:00','1','This Week','Today','',0,'$2y$10$odQjUAFPIEGomVC/SnCvGOd4uR3cWqemTHjix5RTpcysyj/BqueWW','1','1 Minute',NULL,'PHASH','PqllmJsVZ0Kv5ICx','alphagrey','ja_jp','Asia/Tokyo','123,456,789','.',',','$1.0','システム管理者','','','Monday','30','30','public','Summary','','','','',0,'','','');
/*!40000 ALTER TABLE `vtiger_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_users2group`
--

DROP TABLE IF EXISTS `vtiger_users2group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_users2group` (
  `groupid` int NOT NULL,
  `userid` int NOT NULL,
  PRIMARY KEY (`groupid`,`userid`),
  KEY `users2group_groupname_uerid_idx` (`groupid`,`userid`),
  KEY `fk_2_vtiger_users2group` (`userid`),
  CONSTRAINT `fk_2_vtiger_users2group` FOREIGN KEY (`userid`) REFERENCES `vtiger_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_users2group`
--

LOCK TABLES `vtiger_users2group` WRITE;
/*!40000 ALTER TABLE `vtiger_users2group` DISABLE KEYS */;
INSERT INTO `vtiger_users2group` VALUES (3,1);
/*!40000 ALTER TABLE `vtiger_users2group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_users_last_import`
--

DROP TABLE IF EXISTS `vtiger_users_last_import`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_users_last_import` (
  `id` int NOT NULL AUTO_INCREMENT,
  `assigned_user_id` varchar(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bean_type` varchar(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bean_id` varchar(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deleted` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`assigned_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_users_last_import`
--

LOCK TABLES `vtiger_users_last_import` WRITE;
/*!40000 ALTER TABLE `vtiger_users_last_import` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_users_last_import` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_users_seq`
--

DROP TABLE IF EXISTS `vtiger_users_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_users_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_users_seq`
--

LOCK TABLES `vtiger_users_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_users_seq` DISABLE KEYS */;
INSERT INTO `vtiger_users_seq` VALUES (4);
/*!40000 ALTER TABLE `vtiger_users_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_vendor`
--

DROP TABLE IF EXISTS `vtiger_vendor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_vendor` (
  `vendorid` int NOT NULL,
  `vendor_no` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `vendorname` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(256) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `website` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `glacct` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `category` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `street` text COLLATE utf8mb4_general_ci,
  `city` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `state` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pobox` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `postalcode` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `tags` varchar(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `smcreatorid` int DEFAULT NULL,
  `smownerid` int DEFAULT NULL,
  `modifiedby` int DEFAULT NULL,
  `createdtime` datetime DEFAULT NULL,
  `modifiedtime` datetime DEFAULT NULL,
  `viewedtime` datetime DEFAULT NULL,
  `deleted` int DEFAULT NULL,
  `label` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `smgroupid` int DEFAULT NULL,
  `source` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`vendorid`),
  KEY `idx_info` (`deleted`,`modifiedtime`),
  KEY `idx_label` (`deleted`,`label`),
  KEY `idx_owner` (`deleted`,`smownerid`),
  CONSTRAINT `fk_1_vtiger_vendor` FOREIGN KEY (`vendorid`) REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_vendor`
--

LOCK TABLES `vtiger_vendor` WRITE;
/*!40000 ALTER TABLE `vtiger_vendor` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_vendor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_vendorcf`
--

DROP TABLE IF EXISTS `vtiger_vendorcf`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_vendorcf` (
  `vendorid` int NOT NULL,
  PRIMARY KEY (`vendorid`),
  CONSTRAINT `fk_1_vtiger_vendorcf` FOREIGN KEY (`vendorid`) REFERENCES `vtiger_vendor` (`vendorid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_vendorcf`
--

LOCK TABLES `vtiger_vendorcf` WRITE;
/*!40000 ALTER TABLE `vtiger_vendorcf` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_vendorcf` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_vendorcontactrel`
--

DROP TABLE IF EXISTS `vtiger_vendorcontactrel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_vendorcontactrel` (
  `vendorid` int NOT NULL,
  `contactid` int NOT NULL,
  PRIMARY KEY (`vendorid`,`contactid`),
  KEY `vendorcontactrel_vendorid_idx` (`vendorid`),
  KEY `vendorcontactrel_contact_idx` (`contactid`),
  CONSTRAINT `fk_2_vtiger_vendorcontactrel` FOREIGN KEY (`vendorid`) REFERENCES `vtiger_vendor` (`vendorid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_vendorcontactrel`
--

LOCK TABLES `vtiger_vendorcontactrel` WRITE;
/*!40000 ALTER TABLE `vtiger_vendorcontactrel` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_vendorcontactrel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_version`
--

DROP TABLE IF EXISTS `vtiger_version`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_version` (
  `id` int NOT NULL AUTO_INCREMENT,
  `old_version` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `current_version` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_version`
--

LOCK TABLES `vtiger_version` WRITE;
/*!40000 ALTER TABLE `vtiger_version` DISABLE KEYS */;
INSERT INTO `vtiger_version` VALUES (1,'8.0.2','8.0.2');
/*!40000 ALTER TABLE `vtiger_version` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_version_seq`
--

DROP TABLE IF EXISTS `vtiger_version_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_version_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_version_seq`
--

LOCK TABLES `vtiger_version_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_version_seq` DISABLE KEYS */;
INSERT INTO `vtiger_version_seq` VALUES (1);
/*!40000 ALTER TABLE `vtiger_version_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_visibility`
--

DROP TABLE IF EXISTS `vtiger_visibility`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_visibility` (
  `visibilityid` int NOT NULL AUTO_INCREMENT,
  `visibility` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `sortorderid` int NOT NULL DEFAULT '0',
  `presence` int NOT NULL DEFAULT '1',
  `color` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`visibilityid`),
  UNIQUE KEY `visibility_visibility_idx` (`visibility`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_visibility`
--

LOCK TABLES `vtiger_visibility` WRITE;
/*!40000 ALTER TABLE `vtiger_visibility` DISABLE KEYS */;
INSERT INTO `vtiger_visibility` VALUES (1,'Private',2,1,NULL),(2,'Public',1,1,NULL);
/*!40000 ALTER TABLE `vtiger_visibility` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_visibility_seq`
--

DROP TABLE IF EXISTS `vtiger_visibility_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_visibility_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_visibility_seq`
--

LOCK TABLES `vtiger_visibility_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_visibility_seq` DISABLE KEYS */;
INSERT INTO `vtiger_visibility_seq` VALUES (2);
/*!40000 ALTER TABLE `vtiger_visibility_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_webform_file_fields`
--

DROP TABLE IF EXISTS `vtiger_webform_file_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_webform_file_fields` (
  `id` int NOT NULL AUTO_INCREMENT,
  `webformid` int NOT NULL,
  `fieldname` varchar(100) NOT NULL,
  `fieldlabel` varchar(100) NOT NULL,
  `required` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_vtiger_webforms` (`webformid`),
  CONSTRAINT `fk_vtiger_webforms` FOREIGN KEY (`webformid`) REFERENCES `vtiger_webforms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_webform_file_fields`
--

LOCK TABLES `vtiger_webform_file_fields` WRITE;
/*!40000 ALTER TABLE `vtiger_webform_file_fields` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_webform_file_fields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_webforms`
--

DROP TABLE IF EXISTS `vtiger_webforms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_webforms` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `publicid` varchar(100) NOT NULL,
  `enabled` int NOT NULL DEFAULT '1',
  `targetmodule` varchar(50) NOT NULL,
  `description` text,
  `ownerid` int NOT NULL,
  `returnurl` varchar(250) DEFAULT NULL,
  `captcha` int NOT NULL DEFAULT '0',
  `roundrobin` int NOT NULL DEFAULT '0',
  `roundrobin_userid` varchar(256) DEFAULT NULL,
  `roundrobin_logic` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `webformname` (`name`),
  UNIQUE KEY `publicid` (`id`),
  KEY `webforms_webforms_id_idx` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_webforms`
--

LOCK TABLES `vtiger_webforms` WRITE;
/*!40000 ALTER TABLE `vtiger_webforms` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_webforms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_wordtemplates`
--

DROP TABLE IF EXISTS `vtiger_wordtemplates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_wordtemplates` (
  `templateid` int NOT NULL,
  `filename` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `module` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `date_entered` timestamp NOT NULL,
  `parent_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `data` longblob NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `filesize` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `filetype` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `deleted` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`templateid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_wordtemplates`
--

LOCK TABLES `vtiger_wordtemplates` WRITE;
/*!40000 ALTER TABLE `vtiger_wordtemplates` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_wordtemplates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_ws_entity`
--

DROP TABLE IF EXISTS `vtiger_ws_entity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_ws_entity` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `handler_path` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `handler_class` varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
  `ismodule` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_ws_entity`
--

LOCK TABLES `vtiger_ws_entity` WRITE;
/*!40000 ALTER TABLE `vtiger_ws_entity` DISABLE KEYS */;
INSERT INTO `vtiger_ws_entity` VALUES (1,'Campaigns','include/Webservices/VtigerModuleOperation.php','VtigerModuleOperation',1),(2,'Invoice','include/Webservices/LineItem/VtigerInventoryOperation.php','VtigerInventoryOperation',1),(3,'SalesOrder','include/Webservices/LineItem/VtigerInventoryOperation.php','VtigerInventoryOperation',1),(4,'PurchaseOrder','include/Webservices/LineItem/VtigerInventoryOperation.php','VtigerInventoryOperation',1),(5,'Quotes','include/Webservices/LineItem/VtigerInventoryOperation.php','VtigerInventoryOperation',1),(6,'Faq','include/Webservices/VtigerModuleOperation.php','VtigerModuleOperation',1),(7,'Vendors','include/Webservices/VtigerModuleOperation.php','VtigerModuleOperation',1),(8,'PriceBooks','include/Webservices/VtigerModuleOperation.php','VtigerModuleOperation',1),(9,'Calendar','include/Webservices/VtigerModuleOperation.php','VtigerModuleOperation',1),(10,'Leads','include/Webservices/VtigerModuleOperation.php','VtigerModuleOperation',1),(11,'Accounts','include/Webservices/VtigerModuleOperation.php','VtigerModuleOperation',1),(12,'Contacts','include/Webservices/VtigerModuleOperation.php','VtigerModuleOperation',1),(13,'Potentials','include/Webservices/VtigerModuleOperation.php','VtigerModuleOperation',1),(14,'Products','include/Webservices/VtigerProductOperation.php','VtigerProductOperation',1),(15,'Documents','include/Webservices/VtigerModuleOperation.php','VtigerModuleOperation',1),(16,'Emails','include/Webservices/VtigerModuleOperation.php','VtigerModuleOperation',1),(17,'HelpDesk','include/Webservices/VtigerModuleOperation.php','VtigerModuleOperation',1),(18,'Events','include/Webservices/VtigerModuleOperation.php','VtigerModuleOperation',1),(19,'Users','include/Webservices/VtigerModuleOperation.php','VtigerModuleOperation',1),(20,'Groups','include/Webservices/VtigerActorOperation.php','VtigerActorOperation',0),(21,'Currency','include/Webservices/VtigerActorOperation.php','VtigerActorOperation',0),(22,'DocumentFolders','include/Webservices/VtigerActorOperation.php','VtigerActorOperation',0),(23,'CompanyDetails','include/Webservices/VtigerCompanyDetails.php','VtigerCompanyDetails',0),(24,'ServiceContracts','include/Webservices/VtigerModuleOperation.php','VtigerModuleOperation',1),(25,'Services','include/Webservices/VtigerProductOperation.php','VtigerProductOperation',1),(26,'ModComments','include/Webservices/VtigerModuleOperation.php','VtigerModuleOperation',1),(27,'ProjectMilestone','include/Webservices/VtigerModuleOperation.php','VtigerModuleOperation',1),(28,'ProjectTask','include/Webservices/VtigerModuleOperation.php','VtigerModuleOperation',1),(29,'Project','include/Webservices/VtigerModuleOperation.php','VtigerModuleOperation',1),(30,'SMSNotifier','include/Webservices/VtigerModuleOperation.php','VtigerModuleOperation',1),(31,'Assets','include/Webservices/VtigerModuleOperation.php','VtigerModuleOperation',1),(32,'LineItem','include/Webservices/LineItem/VtigerLineItemOperation.php','VtigerLineItemOperation',0),(33,'Tax','include/Webservices/LineItem/VtigerTaxOperation.php','VtigerTaxOperation',0),(34,'ProductTaxes','include/Webservices/LineItem/VtigerProductTaxesOperation.php','VtigerProductTaxesOperation',0),(35,'Dailyreports','include/Webservices/VtigerModuleOperation.php','VtigerModuleOperation',1);
/*!40000 ALTER TABLE `vtiger_ws_entity` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_ws_entity_fieldtype`
--

DROP TABLE IF EXISTS `vtiger_ws_entity_fieldtype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_ws_entity_fieldtype` (
  `fieldtypeid` int NOT NULL AUTO_INCREMENT,
  `table_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `field_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `fieldtype` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`fieldtypeid`),
  UNIQUE KEY `vtiger_idx_1_tablename_fieldname` (`table_name`,`field_name`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_ws_entity_fieldtype`
--

LOCK TABLES `vtiger_ws_entity_fieldtype` WRITE;
/*!40000 ALTER TABLE `vtiger_ws_entity_fieldtype` DISABLE KEYS */;
INSERT INTO `vtiger_ws_entity_fieldtype` VALUES (1,'vtiger_attachmentsfolder','createdby','reference'),(2,'vtiger_organizationdetails','logoname','file'),(3,'vtiger_organizationdetails','phone','phone'),(4,'vtiger_organizationdetails','fax','phone'),(5,'vtiger_organizationdetails','website','url'),(6,'vtiger_inventoryproductrel','productid','reference'),(7,'vtiger_inventoryproductrel','id','reference'),(8,'vtiger_inventoryproductrel','incrementondel','autogenerated'),(9,'vtiger_producttaxrel','productid','reference'),(10,'vtiger_producttaxrel','taxid','reference');
/*!40000 ALTER TABLE `vtiger_ws_entity_fieldtype` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_ws_entity_fieldtype_seq`
--

DROP TABLE IF EXISTS `vtiger_ws_entity_fieldtype_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_ws_entity_fieldtype_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_ws_entity_fieldtype_seq`
--

LOCK TABLES `vtiger_ws_entity_fieldtype_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_ws_entity_fieldtype_seq` DISABLE KEYS */;
INSERT INTO `vtiger_ws_entity_fieldtype_seq` VALUES (10);
/*!40000 ALTER TABLE `vtiger_ws_entity_fieldtype_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_ws_entity_name`
--

DROP TABLE IF EXISTS `vtiger_ws_entity_name`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_ws_entity_name` (
  `entity_id` int NOT NULL,
  `name_fields` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `index_field` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `table_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_ws_entity_name`
--

LOCK TABLES `vtiger_ws_entity_name` WRITE;
/*!40000 ALTER TABLE `vtiger_ws_entity_name` DISABLE KEYS */;
INSERT INTO `vtiger_ws_entity_name` VALUES (20,'groupname','groupid','vtiger_groups'),(21,'currency_name','id','vtiger_currency_info'),(22,'foldername','folderid','vtiger_attachmentsfolder'),(23,'organizationname','groupid','vtiger_organizationdetails'),(33,'taxlabel','taxid','vtiger_inventorytaxinfo');
/*!40000 ALTER TABLE `vtiger_ws_entity_name` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_ws_entity_referencetype`
--

DROP TABLE IF EXISTS `vtiger_ws_entity_referencetype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_ws_entity_referencetype` (
  `fieldtypeid` int NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`fieldtypeid`,`type`),
  CONSTRAINT `vtiger_fk_1_actors_referencetype` FOREIGN KEY (`fieldtypeid`) REFERENCES `vtiger_ws_entity_fieldtype` (`fieldtypeid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_ws_entity_referencetype`
--

LOCK TABLES `vtiger_ws_entity_referencetype` WRITE;
/*!40000 ALTER TABLE `vtiger_ws_entity_referencetype` DISABLE KEYS */;
INSERT INTO `vtiger_ws_entity_referencetype` VALUES (5,'Users'),(6,'Products'),(7,'Invoice'),(7,'PurchaseOrder'),(7,'Quotes'),(7,'SalesOrder'),(9,'Products'),(10,'Tax');
/*!40000 ALTER TABLE `vtiger_ws_entity_referencetype` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_ws_entity_seq`
--

DROP TABLE IF EXISTS `vtiger_ws_entity_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_ws_entity_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_ws_entity_seq`
--

LOCK TABLES `vtiger_ws_entity_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_ws_entity_seq` DISABLE KEYS */;
INSERT INTO `vtiger_ws_entity_seq` VALUES (35);
/*!40000 ALTER TABLE `vtiger_ws_entity_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_ws_entity_tables`
--

DROP TABLE IF EXISTS `vtiger_ws_entity_tables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_ws_entity_tables` (
  `webservice_entity_id` int NOT NULL,
  `table_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`webservice_entity_id`,`table_name`),
  CONSTRAINT `fk_1_vtiger_ws_actor_tables` FOREIGN KEY (`webservice_entity_id`) REFERENCES `vtiger_ws_entity` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_ws_entity_tables`
--

LOCK TABLES `vtiger_ws_entity_tables` WRITE;
/*!40000 ALTER TABLE `vtiger_ws_entity_tables` DISABLE KEYS */;
INSERT INTO `vtiger_ws_entity_tables` VALUES (20,'vtiger_groups'),(21,'vtiger_currency_info'),(22,'vtiger_attachmentsfolder'),(23,'vtiger_organizationdetails'),(32,'vtiger_inventoryproductrel'),(33,'vtiger_inventorytaxinfo'),(34,'vtiger_producttaxrel');
/*!40000 ALTER TABLE `vtiger_ws_entity_tables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_ws_fieldinfo`
--

DROP TABLE IF EXISTS `vtiger_ws_fieldinfo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_ws_fieldinfo` (
  `id` varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
  `property_name` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `property_value` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_ws_fieldinfo`
--

LOCK TABLES `vtiger_ws_fieldinfo` WRITE;
/*!40000 ALTER TABLE `vtiger_ws_fieldinfo` DISABLE KEYS */;
INSERT INTO `vtiger_ws_fieldinfo` VALUES ('vtiger_organizationdetails.organization_id','upload.path','1');
/*!40000 ALTER TABLE `vtiger_ws_fieldinfo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_ws_fieldtype`
--

DROP TABLE IF EXISTS `vtiger_ws_fieldtype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_ws_fieldtype` (
  `fieldtypeid` int NOT NULL AUTO_INCREMENT,
  `uitype` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `fieldtype` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`fieldtypeid`),
  UNIQUE KEY `uitype_idx` (`uitype`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_ws_fieldtype`
--

LOCK TABLES `vtiger_ws_fieldtype` WRITE;
/*!40000 ALTER TABLE `vtiger_ws_fieldtype` DISABLE KEYS */;
INSERT INTO `vtiger_ws_fieldtype` VALUES (1,'15','picklist'),(2,'16','picklist'),(3,'19','text'),(4,'20','text'),(5,'21','text'),(6,'24','text'),(7,'3','autogenerated'),(8,'11','phone'),(9,'33','multipicklist'),(10,'17','url'),(11,'85','skype'),(12,'56','boolean'),(13,'156','boolean'),(14,'53','owner'),(15,'61','file'),(16,'28','file'),(17,'13','email'),(18,'71','currency'),(19,'72','currency'),(20,'50','reference'),(21,'51','reference'),(22,'57','reference'),(23,'58','reference'),(24,'73','reference'),(25,'75','reference'),(26,'76','reference'),(27,'78','reference'),(28,'80','reference'),(29,'81','reference'),(30,'101','reference'),(31,'52','reference'),(32,'357','reference'),(33,'59','reference'),(34,'66','reference'),(35,'77','reference'),(36,'68','reference'),(37,'117','reference'),(38,'26','reference'),(39,'10','reference'),(40,'98','reference'),(41,'69','image'),(42,'999','blank');
/*!40000 ALTER TABLE `vtiger_ws_fieldtype` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_ws_operation`
--

DROP TABLE IF EXISTS `vtiger_ws_operation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_ws_operation` (
  `operationid` int NOT NULL AUTO_INCREMENT,
  `name` varchar(128) COLLATE utf8mb4_general_ci NOT NULL,
  `handler_path` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `handler_method` varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
  `type` varchar(8) COLLATE utf8mb4_general_ci NOT NULL,
  `prelogin` int NOT NULL,
  PRIMARY KEY (`operationid`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_ws_operation`
--

LOCK TABLES `vtiger_ws_operation` WRITE;
/*!40000 ALTER TABLE `vtiger_ws_operation` DISABLE KEYS */;
INSERT INTO `vtiger_ws_operation` VALUES (1,'login','include/Webservices/Login.php','vtws_login','POST',1),(2,'retrieve','include/Webservices/Retrieve.php','vtws_retrieve','GET',0),(3,'create','include/Webservices/Create.php','vtws_create','POST',0),(4,'update','include/Webservices/Update.php','vtws_update','POST',0),(5,'delete','include/Webservices/Delete.php','vtws_delete','POST',0),(6,'sync','include/Webservices/GetUpdates.php','vtws_sync','GET',0),(7,'query','include/Webservices/Query.php','vtws_query','GET',0),(8,'logout','include/Webservices/Logout.php','vtws_logout','POST',0),(9,'listtypes','include/Webservices/ModuleTypes.php','vtws_listtypes','GET',0),(10,'getchallenge','include/Webservices/AuthToken.php','vtws_getchallenge','GET',1),(11,'describe','include/Webservices/DescribeObject.php','vtws_describe','GET',0),(12,'extendsession','include/Webservices/ExtendSession.php','vtws_extendSession','POST',1),(13,'convertlead','include/Webservices/ConvertLead.php','vtws_convertlead','POST',0),(14,'revise','include/Webservices/Revise.php','vtws_revise','POST',0),(15,'changePassword','include/Webservices/ChangePassword.php','vtws_changePassword','POST',0),(16,'deleteUser','include/Webservices/DeleteUser.php','vtws_deleteUser','POST',0),(17,'wsapp_register','modules/WSAPP/api/ws/Register.php','wsapp_register','POST',0),(18,'wsapp_deregister','modules/WSAPP/api/ws/DeRegister.php','wsapp_deregister','POST',0),(19,'wsapp_get','modules/WSAPP/api/ws/Get.php','wsapp_get','POST',0),(20,'wsapp_put','modules/WSAPP/api/ws/Put.php','wsapp_put','POST',0),(21,'wsapp_map','modules/WSAPP/api/ws/Map.php','wsapp_map','POST',0),(22,'mobile.fetchallalerts','modules/Mobile/api/wsapi.php','mobile_ws_fetchAllAlerts','POST',0),(23,'mobile.alertdetailswithmessage','modules/Mobile/api/wsapi.php','mobile_ws_alertDetailsWithMessage','POST',0),(24,'mobile.fetchmodulefilters','modules/Mobile/api/wsapi.php','mobile_ws_fetchModuleFilters','POST',0),(25,'mobile.fetchrecord','modules/Mobile/api/wsapi.php','mobile_ws_fetchRecord','POST',0),(26,'mobile.fetchrecordwithgrouping','modules/Mobile/api/wsapi.php','mobile_ws_fetchRecordWithGrouping','POST',0),(27,'mobile.filterdetailswithcount','modules/Mobile/api/wsapi.php','mobile_ws_filterDetailsWithCount','POST',0),(28,'mobile.listmodulerecords','modules/Mobile/api/wsapi.php','mobile_ws_listModuleRecords','POST',0),(29,'mobile.saverecord','modules/Mobile/api/wsapi.php','mobile_ws_saveRecord','POST',0),(30,'mobile.syncModuleRecords','modules/Mobile/api/wsapi.php','mobile_ws_syncModuleRecords','POST',0),(31,'mobile.query','modules/Mobile/api/wsapi.php','mobile_ws_query','POST',0),(32,'mobile.querywithgrouping','modules/Mobile/api/wsapi.php','mobile_ws_queryWithGrouping','POST',0),(33,'retrieve_inventory','include/Webservices/LineItem/RetrieveInventory.php','vtws_retrieve_inventory','GET',0),(34,'relatedtypes','include/Webservices/RelatedTypes.php','vtws_relatedtypes','GET',0),(35,'retrieve_related','include/Webservices/RetrieveRelated.php','vtws_retrieve_related','GET',0),(36,'query_related','include/Webservices/QueryRelated.php','vtws_query_related','GET',0),(37,'add_related','include/Webservices/AddRelated.php','vtws_add_related','POST',0),(38,'files_retrieve','include/Webservices/FileRetrieve.php','vtws_file_retrieve','GET',0);
/*!40000 ALTER TABLE `vtiger_ws_operation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_ws_operation_parameters`
--

DROP TABLE IF EXISTS `vtiger_ws_operation_parameters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_ws_operation_parameters` (
  `operationid` int NOT NULL AUTO_INCREMENT,
  `name` varchar(128) COLLATE utf8mb4_general_ci NOT NULL,
  `type` varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
  `sequence` int NOT NULL,
  PRIMARY KEY (`operationid`,`name`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_ws_operation_parameters`
--

LOCK TABLES `vtiger_ws_operation_parameters` WRITE;
/*!40000 ALTER TABLE `vtiger_ws_operation_parameters` DISABLE KEYS */;
INSERT INTO `vtiger_ws_operation_parameters` VALUES (1,'accessKey','String',2),(1,'username','String',1),(2,'id','String',1),(3,'element','encoded',2),(3,'elementType','String',1),(4,'element','encoded',1),(5,'id','String',1),(6,'elementType','String',2),(6,'modifiedTime','DateTime',1),(6,'syncType','string',3),(7,'query','String',1),(8,'sessionName','String',1),(9,'fieldTypeList','encoded',1),(10,'username','String',1),(11,'elementType','String',1),(13,'accountName','String',3),(13,'assignedTo','String',2),(13,'avoidPotential','Boolean',4),(13,'element','encoded',6),(13,'leadId','String',1),(13,'potential','Encoded',5),(14,'element','Encoded',1),(15,'confirmPassword','String',4),(15,'id','String',1),(15,'newPassword','String',3),(15,'oldPassword','String',2),(16,'id','String',1),(16,'newOwnerId','String',2),(17,'synctype','string',2),(17,'type','string',1),(18,'key','string',2),(18,'type','string',1),(19,'key','string',1),(19,'module','string',2),(19,'token','string',3),(20,'element','encoded',2),(20,'key','string',1),(21,'element','encoded',2),(21,'key','string',1),(23,'alertid','string',1),(24,'module','string',1),(25,'record','string',1),(26,'record','string',1),(27,'filterid','string',1),(28,'elements','encoded',1),(29,'module','string',1),(29,'record','string',2),(29,'values','encoded',3),(30,'module','string',1),(30,'page','string',3),(30,'syncToken','string',2),(31,'module','string',1),(31,'page','string',3),(31,'query','string',2),(32,'module','string',1),(32,'page','string',3),(32,'query','string',2),(33,'id','String',1),(34,'elementType','string',1),(35,'id','string',1),(35,'relatedLabel','string',3),(35,'relatedType','string',2),(36,'id','string',2),(36,'query','string',1),(36,'relatedLabel','string',3),(37,'relatedRecordId','string',2),(37,'relationIdLabel','string',3),(37,'sourceRecordId','string',1),(38,'id','string',1);
/*!40000 ALTER TABLE `vtiger_ws_operation_parameters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_ws_operation_seq`
--

DROP TABLE IF EXISTS `vtiger_ws_operation_seq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_ws_operation_seq` (
  `id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_ws_operation_seq`
--

LOCK TABLES `vtiger_ws_operation_seq` WRITE;
/*!40000 ALTER TABLE `vtiger_ws_operation_seq` DISABLE KEYS */;
INSERT INTO `vtiger_ws_operation_seq` VALUES (38);
/*!40000 ALTER TABLE `vtiger_ws_operation_seq` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_ws_referencetype`
--

DROP TABLE IF EXISTS `vtiger_ws_referencetype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_ws_referencetype` (
  `fieldtypeid` int NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`fieldtypeid`,`type`),
  CONSTRAINT `fk_1_vtiger_referencetype` FOREIGN KEY (`fieldtypeid`) REFERENCES `vtiger_ws_fieldtype` (`fieldtypeid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_ws_referencetype`
--

LOCK TABLES `vtiger_ws_referencetype` WRITE;
/*!40000 ALTER TABLE `vtiger_ws_referencetype` DISABLE KEYS */;
INSERT INTO `vtiger_ws_referencetype` VALUES (20,'Accounts'),(21,'Accounts'),(22,'Contacts'),(23,'Campaigns'),(24,'Accounts'),(25,'Vendors'),(26,'Potentials'),(27,'Quotes'),(28,'SalesOrder'),(29,'Vendors'),(30,'Users'),(31,'Users'),(32,'Accounts'),(32,'Contacts'),(32,'Leads'),(32,'Users'),(32,'Vendors'),(33,'Products'),(34,'Accounts'),(34,'Campaigns'),(34,'HelpDesk'),(34,'Invoice'),(34,'Leads'),(34,'Potentials'),(34,'PurchaseOrder'),(34,'Quotes'),(34,'SalesOrder'),(35,'Users'),(36,'Accounts'),(36,'Contacts'),(37,'Currency'),(38,'DocumentFolders');
/*!40000 ALTER TABLE `vtiger_ws_referencetype` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_ws_userauthtoken`
--

DROP TABLE IF EXISTS `vtiger_ws_userauthtoken`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_ws_userauthtoken` (
  `userid` int NOT NULL,
  `token` varchar(36) COLLATE utf8mb4_general_ci NOT NULL,
  `expiretime` int NOT NULL,
  PRIMARY KEY (`userid`,`expiretime`),
  UNIQUE KEY `userid_idx` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_ws_userauthtoken`
--

LOCK TABLES `vtiger_ws_userauthtoken` WRITE;
/*!40000 ALTER TABLE `vtiger_ws_userauthtoken` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_ws_userauthtoken` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_wsapp`
--

DROP TABLE IF EXISTS `vtiger_wsapp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_wsapp` (
  `appid` int NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `appkey` varchar(255) DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`appid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_wsapp`
--

LOCK TABLES `vtiger_wsapp` WRITE;
/*!40000 ALTER TABLE `vtiger_wsapp` DISABLE KEYS */;
INSERT INTO `vtiger_wsapp` VALUES (1,'vtigerCRM','6a44c7edb635d','user');
/*!40000 ALTER TABLE `vtiger_wsapp` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_wsapp_handlerdetails`
--

DROP TABLE IF EXISTS `vtiger_wsapp_handlerdetails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_wsapp_handlerdetails` (
  `type` varchar(200) NOT NULL,
  `handlerclass` varchar(100) DEFAULT NULL,
  `handlerpath` varchar(300) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_wsapp_handlerdetails`
--

LOCK TABLES `vtiger_wsapp_handlerdetails` WRITE;
/*!40000 ALTER TABLE `vtiger_wsapp_handlerdetails` DISABLE KEYS */;
INSERT INTO `vtiger_wsapp_handlerdetails` VALUES ('Outlook','OutlookHandler','modules/WSAPP/Handlers/OutlookHandler.php'),('vtigerCRM','vtigerCRMHandler','modules/WSAPP/Handlers/vtigerCRMHandler.php'),('vtigerSyncLib','WSAPP_VtigerSyncEventHandler','modules/WSAPP/synclib/handlers/VtigerSyncEventHandler.php'),('Google_vtigerHandler','Google_Vtiger_Handler','modules/Google/handlers/Vtiger.php'),('Google_vtigerSyncHandler','Google_VtigerSync_Handler','modules/Google/handlers/VtigerSync.php');
/*!40000 ALTER TABLE `vtiger_wsapp_handlerdetails` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_wsapp_logs_basic`
--

DROP TABLE IF EXISTS `vtiger_wsapp_logs_basic`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_wsapp_logs_basic` (
  `id` int NOT NULL AUTO_INCREMENT,
  `extensiontabid` int DEFAULT NULL,
  `module` varchar(50) NOT NULL,
  `sync_datetime` datetime NOT NULL,
  `app_create_count` int DEFAULT NULL,
  `app_update_count` int DEFAULT NULL,
  `app_delete_count` int DEFAULT NULL,
  `app_skip_count` int DEFAULT NULL,
  `vt_create_count` int DEFAULT NULL,
  `vt_update_count` int DEFAULT NULL,
  `vt_delete_count` int DEFAULT NULL,
  `vt_skip_count` int DEFAULT NULL,
  `userid` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_wsapp_logs_basic`
--

LOCK TABLES `vtiger_wsapp_logs_basic` WRITE;
/*!40000 ALTER TABLE `vtiger_wsapp_logs_basic` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_wsapp_logs_basic` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_wsapp_logs_details`
--

DROP TABLE IF EXISTS `vtiger_wsapp_logs_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_wsapp_logs_details` (
  `id` int NOT NULL,
  `app_create_ids` mediumtext,
  `app_update_ids` mediumtext,
  `app_delete_ids` mediumtext,
  `app_skip_info` mediumtext,
  `vt_create_ids` mediumtext,
  `vt_update_ids` mediumtext,
  `vt_delete_ids` mediumtext,
  `vt_skip_info` mediumtext,
  KEY `vtiger_wsapp_logs_basic_ibfk_1` (`id`),
  CONSTRAINT `vtiger_wsapp_logs_basic_ibfk_1` FOREIGN KEY (`id`) REFERENCES `vtiger_wsapp_logs_basic` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_wsapp_logs_details`
--

LOCK TABLES `vtiger_wsapp_logs_details` WRITE;
/*!40000 ALTER TABLE `vtiger_wsapp_logs_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_wsapp_logs_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_wsapp_queuerecords`
--

DROP TABLE IF EXISTS `vtiger_wsapp_queuerecords`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_wsapp_queuerecords` (
  `syncserverid` int DEFAULT NULL,
  `details` varchar(300) DEFAULT NULL,
  `flag` varchar(100) DEFAULT NULL,
  `appid` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_wsapp_queuerecords`
--

LOCK TABLES `vtiger_wsapp_queuerecords` WRITE;
/*!40000 ALTER TABLE `vtiger_wsapp_queuerecords` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_wsapp_queuerecords` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_wsapp_recordmapping`
--

DROP TABLE IF EXISTS `vtiger_wsapp_recordmapping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_wsapp_recordmapping` (
  `id` int NOT NULL AUTO_INCREMENT,
  `serverid` varchar(10) DEFAULT NULL,
  `clientid` varchar(255) DEFAULT NULL,
  `clientmodifiedtime` datetime DEFAULT NULL,
  `appid` int DEFAULT NULL,
  `servermodifiedtime` datetime DEFAULT NULL,
  `serverappid` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_wsapp_recordmapping`
--

LOCK TABLES `vtiger_wsapp_recordmapping` WRITE;
/*!40000 ALTER TABLE `vtiger_wsapp_recordmapping` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_wsapp_recordmapping` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vtiger_wsapp_sync_state`
--

DROP TABLE IF EXISTS `vtiger_wsapp_sync_state`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vtiger_wsapp_sync_state` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(200) DEFAULT NULL,
  `stateencodedvalues` varchar(300) NOT NULL,
  `userid` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vtiger_wsapp_sync_state`
--

LOCK TABLES `vtiger_wsapp_sync_state` WRITE;
/*!40000 ALTER TABLE `vtiger_wsapp_sync_state` DISABLE KEYS */;
/*!40000 ALTER TABLE `vtiger_wsapp_sync_state` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-01 17:02:52
