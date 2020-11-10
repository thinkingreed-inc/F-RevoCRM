<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

include_once 'include/Webservices/Create.php';
include_once 'vtlib/Vtiger/Mailer.php';
include_once 'vtlib/Vtiger/Version.php';

class MailManager_Mail_View extends MailManager_Abstract_View {

	public function process(Vtiger_Request $request) {
		$db = PearDatabase::getInstance();
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$moduleName = $request->getModule();
		$response = new MailManager_Response();

		if ('open' == $this->getOperationArg($request)) {
			$foldername = $request->get('_folder');
			$connector = $this->getConnector($foldername);

			$folder = $connector->folderInstance($foldername);
			$connector->markMailRead($request->get('_msgno'));

			$mail = $connector->openMail($request->get('_msgno'), $foldername);
			$connector->updateFolder($folder, SA_MESSAGES|SA_UNSEEN);

			$viewer = $this->getViewer($request);
			$viewer->assign('FOLDER', $folder);
			$viewer->assign('MAIL', $mail);
			$viewer->assign('USERNAME', $this->mMailboxModel->mUsername);
			$viewer->assign('ATTACHMENTS', $mail->attachments(false));
			$body = $mail->body();
			$inlineAttachments = $mail->inlineAttachments();
			if(is_array($inlineAttachments)) {
				foreach($inlineAttachments as $index => $att) {
					$cid = $att['cid'];
					$attch_name = Vtiger_MailRecord::__mime_decode($att['filename']);
					$id = $mail->muid();
					$src = "index.php?module=MailManager&view=Index&_operation=mail&_operationarg=attachment_dld&_muid=$id&_atname=".urlencode($attch_name);
					$body = preg_replace('/cid:'.$cid.'/', $src, $body);
					$inline_cid[$attch_name] = $cid;
				}
			}
			$viewer->assign('INLINE_ATT', $inline_cid);
			$viewer->assign('BODY', $body);
			$viewer->assign('MODULE', $moduleName);
			$uicontent = $viewer->view('MailOpen.tpl', 'MailManager', true);

			$metainfo  = array(
					'from' => $mail->from(), 'subject' => $mail->subject(),
					'msgno' => $mail->msgNo(), 'msguid' => $mail->uniqueid(),
					'folder' => $foldername, 'to' => $mail->to() );

			$response->isJson(true);
			$response->setResult( array(
					'folder' => $foldername, 'unread' => $folder->unreadCount(),
					'ui' => $uicontent, 'meta' => $metainfo )
			);

		} else if ('mark' == $this->getOperationArg($request) && $request->validateWriteAccess()) {

			$foldername = $request->get('_folder');
			$connector = $this->getConnector($foldername);

			$folder = $connector->folderInstance($foldername);
			$connector->updateFolder($folder, SA_UNSEEN);

			if ('unread' == $request->get('_markas')) {
				$msgNos = explode(',', $request->get('_msgno'));
				foreach($msgNos as $msgNo) {
					$connector->markMailUnread($msgNo);
				}
			} else if('read' == $request->get('_markas')) {
				$msgNos = explode(',', $request->get('_msgno'));
				foreach($msgNos as $msgNo) {
					$connector->markMailRead($msgNo);
				}
			}

			$response->isJson(true);
			$response->setResult ( array('folder' => $foldername, 'unread' => $folder->unreadCount()+1,
					'status' => true, 'msgno' => $request->get('_msgno') ));

		} else if('delete' == $this->getOperationArg($request)&& $request->validateWriteAccess()) {

			$msg_no = $request->get('_msgno');
			$foldername = $request->get('_folder');
			$connector = $this->getConnector($foldername);
			$connector->deleteMail($msg_no);

			$response->isJson(true);
			$response->setResult(array('folder' => $foldername,'status'=>true));

		} else if('move' == $this->getOperationArg($request) && $request->validateWriteAccess()) {

			$msg_no = $request->get('_msgno');
			$foldername = $request->get('_folder');

			$moveToFolder = $request->get('_moveFolder');
			$connector = $this->getConnector($foldername);
			$connector->moveMail($msg_no, $moveToFolder);

			$response->isJson(true);
			$response->setResult(array('folder' => $foldername,'status'=>true));

		} else if ('send' == $this->getOperationArg($request) && $request->validateWriteAccess()) {

			require_once 'modules/MailManager/Config.php';
			$memory_limit = MailManager_Config_Model::get('MEMORY_LIMIT');
			ini_set('memory_limit', $memory_limit);

			$to_string = rtrim($request->get('to'), ',');
			$connector = $this->getConnector('__vt_drafts');

			if (!empty($to_string)) {
				$toArray = explode(',', $to_string);
				foreach($toArray as $to) {
					$relatedtos = MailManager::lookupMailInVtiger($to, $currentUserModel);
					$referenceArray = Array('Contacts','Accounts','Leads');
					for($j=0; $j<count($referenceArray); $j++) {
						$val = $referenceArray[$j];
						if (!empty($relatedtos) && is_array($relatedtos)) {
							for($i=0; $i<count($relatedtos); $i++) {
								if($i == count($relatedtos)-1) {
									$relateto = vtws_getIdComponents($relatedtos[$i]['record']);
									$parentIds = $relateto[1]."@1";
								} elseif($relatedtos[$i]['module'] == $val) {
									$relateto = vtws_getIdComponents($relatedtos[$i]['record']);
									$parentIds = $relateto[1]."@1";
									break;
								}
							}
						}
						if(isset ($parentIds)) {
							break;
						}
					}
					if($parentIds == '') {
						if(count($relatedtos) > 0) {
							$relateto = vtws_getIdComponents($relatedtos[0]['record']);
							$parentIds = $relateto[1]."@1";
							break;
						}
					}

					$cc_string = rtrim($request->get('cc'), ',');
					$bcc_string= rtrim($request->get('bcc'), ',');
					$subject   = $request->get('subject');
					$body      = $request->get('body');

					if($relateto[1]!= NULL && $relateto[0] != '19') {
						$entityId = $relateto[1];
						$parent_module = getSalesEntityType($entityId);
						$description = getMergedDescription($body,$entityId,$parent_module);
					} else {
						if($relateto[0] == '19') $parentIds = $relateto[1].'@-1';
						$description = $body;
					}

					$fromEmail = $connector->getFromEmailAddress();
					$userFullName = getFullNameFromArray('Users', $currentUserModel->getData());
					$userId = $currentUserModel->getId();

					$mailer = new Vtiger_Mailer();
					$mailer->IsHTML(true);
					$mailer->ConfigSenderInfo($fromEmail, $userFullName, $currentUserModel->get('email1'));
					$mailer->Subject = $subject;
					$mailer->Body = $description;
					$mailer->addSignature($userId);
					if($mailer->Signature != '') {
						$mailer->Body.= $mailer->Signature;
					}

					$ccs = empty($cc_string)? array() : explode(',', $cc_string);
					$bccs= empty($bcc_string)?array() : explode(',', $bcc_string);
					$emailId = $request->get('emailid');

					$attachments = $connector->getAttachmentDetails($emailId);
					$mailer->AddAddress($to);
					foreach($ccs as $cc) $mailer->AddCC($cc);
					foreach($bccs as $bcc)$mailer->AddBCC($bcc);

					if(is_array($attachments)) {
						foreach($attachments as $attachment) {
							$fileNameWithPath = vglobal('root_directory').$attachment['path'].$attachment['fileid']."_".$attachment['attachment'];
							if(is_file($fileNameWithPath)) {
								$mailer->AddAttachment($fileNameWithPath, $attachment['attachment']);
							}
						}
					}
					$status = $mailer->Send(true);

					if ($status === true) {
						$email = CRMEntity::getInstance('Emails');
						$email->column_fields['assigned_user_id'] = $currentUserModel->getId();
						$email->column_fields['date_start'] = date('Y-m-d');
						$email->column_fields['time_start'] = date('H:i');
						$email->column_fields['parent_id'] = $parentIds;
						$email->column_fields['subject'] = $mailer->Subject;
						$email->column_fields['description'] = $mailer->Body;
						$email->column_fields['activitytype'] = 'Emails';
						$email->column_fields['from_email'] = $mailer->From;
						$email->column_fields['saved_toid'] = $to;
						$email->column_fields['ccmail'] = $cc_string;
						$email->column_fields['bccmail'] = $bcc_string;
						$email->column_fields['email_flag'] = 'SENT';

						if(empty($emailId)) {
							$email->save('Emails');
						} else {
							$email->id = $emailId;
							$email->mode = 'edit';
							$email->save('Emails');
						}

						$realid = explode("@", $parentIds);
						$mycrmid = $realid[0];
						$params = array($mycrmid, $email->id);

						if ($realid[1] == -1) {
							$db->pquery('DELETE FROM vtiger_salesmanactivityrel WHERE smid=? AND activityid=?',$params);
							$db->pquery('INSERT INTO vtiger_salesmanactivityrel VALUES (?,?)', $params);
						} else {
							$db->pquery('DELETE FROM vtiger_seactivityrel WHERE crmid=? AND activityid=?', $params);
							$db->pquery('INSERT INTO vtiger_seactivityrel VALUES (?,?)', $params);
						}
					}
				}
			}

			if ($status === true) {
				$response->isJson(true);
				$response->setResult( array('sent'=> true) );
			} else {
				$response->isJson(true);
				$response->setError(112, 'please verify outgoing server.');
			}

		} else if ('attachment_dld' == $this->getOperationArg($request)) {
			$attachmentName = $request->getRaw('_atname');
			$attachmentName= str_replace(' ', '_', $attachmentName);
			$attachmentId   = $request->get('_atid');

			if (MailManager_Utils_Helper::allowedFileExtension($attachmentName)) {
				// This is to handle larger uploads
				$memory_limit = MailManager_Config_Model::get('MEMORY_LIMIT');
				ini_set('memory_limit', $memory_limit);

				$mail = new MailManager_Message_Model(false, false);
				$mail->readFromDB($request->get('_muid'));
				$attachment = $mail->attachments(true, $attachmentName, $attachmentId);
				//As we are sending attachment name, it will return only that attachment details
				if($attachment[0]['data']) {
					header("Content-type: application/octet-stream");
					header("Pragma: public");
					header("Cache-Control: private");
					header("Content-Disposition: attachment; filename=\"$attachmentName\"");
					echo $attachment[0]['data'];
				} else {
					header("Content-Disposition: attachment; filename=INVALIDFILE");
					echo "";
				}
			} else {
				header("Content-Disposition: attachment; filename=INVALIDFILE");
				echo "";
			}
			flush();
			exit;
		} elseif('getdraftmail' == $this->getOperationArg($request) && $request->validateWriteAccess()) {

			$connector = $this->getConnector('__vt_drafts');
			$draftMail = $connector->getDraftMail($request);
			$response->isJson(true);
			$response->setResult(array($draftMail));

		} elseif('save' == $this->getOperationArg($request) && $request->validateWriteAccess()) {

			$connector = $this->getConnector('__vt_drafts');
			$draftId = $connector->saveDraft($request);

			$response->isJson(true);
			if(!empty($draftId)) {
				$response->setResult( array('success'=> true,'emailid'=>$draftId) );
			} else {
				$response->setResult( array('success'=> false,'error'=>"Draft was not saved") );
			}

		} elseif('deleteAttachment' == $this->getOperationArg($request) && $request->validateWriteAccess()) {

			$connector = $this->getConnector('__vt_drafts');
			$deleteResponse = $connector->deleteAttachment($request);

			$response->isJson(true);
			$response->setResult(array('success'=> $deleteResponse));

		} elseif('forward' == $this->getOperationArg($request) && $request->validateWriteAccess()) {

			$messageId = $request->get('messageid');
			$folderName = $request->get('folder');

			$connector = $this->getConnector($folderName);
			$mail = $connector->openMail($messageId, $folderName);
			$attachments = $mail->attachments(true);

			$inlineAttachments = $mail->inlineAttachments();
			$draftConnector = $this->getConnector('__vt_drafts');
			$draftId = $draftConnector->saveDraft($request);

			if(is_array($inlineAttachments)) {
				foreach($inlineAttachments as $index => $att) {
					$cid = $att['cid'];
					$attch_name = imap_utf8($att['filename']);
					$id = $mail->muid();
					$src = "index.php?module=MailManager&view=Index&_operation=mail&_operationarg=attachment_dld&_muid=$id&_atname=$attch_name";
					$body = preg_replace('/cid:'.$cid.'/', $src, $body);
					$inline_cid[$attch_name] = $cid;
				}
			}

			if (!empty($attachments)) {
				foreach($attachments as $aValue) {
					if(!empty($inline_cid[$aValue['filename']])) {
						continue;
					}
					$attachInfo = $mail->__SaveAttachmentFile($aValue['filename'], $aValue['data']);
					if(is_array($attachInfo) && !empty($attachInfo) && $attachInfo['size'] > 0) {

						if(!MailManager::checkModuleWriteAccessForCurrentUser('Documents')) return;

						$document = CRMEntity::getInstance('Documents');
						$document->column_fields['notes_title']      = $attachInfo['name'];
						$document->column_fields['filename']         = $attachInfo['name'];
						$document->column_fields['filestatus']       = 1;
						$document->column_fields['filelocationtype'] = 'I';
						$document->column_fields['folderid']         = 1; // Default Folder
						$document->column_fields['filesize']		 = $attachInfo['size'];
						$document->column_fields['assigned_user_id'] = $currentUserModel->getId();
						$document->save('Documents');

						$draftConnector->saveAttachmentRel($document->id, $attachInfo['attachid']);
						$draftConnector->saveEmailDocumentRel($draftId, $document->id);
						$draftConnector->saveAttachmentRel($draftId, $attachInfo['attachid']);

						$attachmentInfo[] = array('name'=>$attachInfo['name'], 'size'=>$attachInfo['size'], 'emailid'=>$draftId, 'docid'=>$document->id);
					}
					unset($aValue['data']);
				}
			}
			$response->isJson(true);
			$response->setResult(array('attachments'=>$attachmentInfo, 'emailid'=>$draftId));
		}
		return $response;
	}

	public function validateRequest(Vtiger_Request $request) {
		return $request->validateReadAccess();
	}
}
?>
