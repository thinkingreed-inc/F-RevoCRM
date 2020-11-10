/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Vtiger_List_Js("MailManager_List_Js", {}, {

	getContainer : function() {
		return jQuery('.main-container');
	},

	loadFolders : function(folder) {
		app.helper.showProgress(app.vtranslate("JSLBL_Loading_Please_Wait")+"...");
		var self = this;
		var params = {
			'module' : app.getModuleName(),
			'view' : 'Index',
			'_operation' : 'folder',
			'_operationarg' : 'getFoldersList'
		}
		app.request.post({"data" : params}).then(function(error, responseData) {
			app.helper.hideProgress();
			self.getContainer().find('#folders_list').html(responseData);
			self.getContainer().find('#folders_list').mCustomScrollbar({
				setHeight: 550,
				autoExpandScrollbar: true,
				scrollInertia: 200,
				autoHideScrollbar: true,
				theme : "dark-3"
			});
			self.registerFolderClickEvent();
			if(folder) {
				self.openFolder(folder);
			} else {
				self.openFolder('INBOX');
			}
			self.registerAutoRefresh();
		});
	},

	registerAutoRefresh : function() {
		var self = this;
		var container = self.getContainer();
		var timeout = parseInt(container.find('#refresh_timeout').val());
		var folder = container.find('.mm_folder.active').data('foldername');
		if(timeout > 0) {
			setTimeout(function() {
				var thisInstance = new MailManager_List_Js();
				if(folder && typeof folder != "undefined") {
					thisInstance.loadFolders(folder);
				} else {
					thisInstance.loadFolders();
				}
			}, timeout);
		}
	},

	registerFolderClickEvent : function() {
		var self = this;
		var container = self.getContainer();
		container.find('.mm_folder').click(function(e) {
			var folderElement = jQuery(e.currentTarget);
			var folderName = folderElement.data('foldername');
			container.find('.mm_folder').each(function(i, ele) {
				jQuery(ele).removeClass('active');
			});
			folderElement.addClass('active');
			if(folderName == 'vt_drafts') {
				self.openDraftFolder();
			} else {
				self.openFolder(folderName);
			}
		});
	},

	registerComposeEmail : function() {
		var self = this;
		var container = self.getContainer();
		container.find('#mail_compose').click(function() {
			var params = {
				step : "step1",
				module : "MailManager",
				view : "MassActionAjax",
				mode : "showComposeEmailForm",
				selected_ids : "[]",
				excluded_ids : "[]"
			};
			self.openComposeEmailForm(null, params);
		});
	},

	registerSettingsEdit : function() {
		var self = this;
		var container = this.getContainer();
		container.find('.mailbox_setting').click(function() {
			app.helper.showProgress(app.vtranslate("JSLBL_Loading_Please_Wait")+"...");
			var params = {
				'module' : 'MailManager',
				'view' : 'Index',
				'_operation' : 'settings',
				'_operationarg' : 'edit'
			};
			var popupInstance = Vtiger_Popup_Js.getInstance();
			popupInstance.showPopup(params, '', function(data) {
				app.helper.hideProgress();
				self.handleSettingsEvents(data);
				self.registerDeleteMailboxEvent(data);
				self.registerSaveMailboxEvent(data);
			});
		});
	},

	handleSettingsEvents : function(data) {
		var settingContainer = jQuery(data);
		settingContainer.find('#serverType').on('change', function(e) {
			var element = jQuery(e.currentTarget);
			var serverType = element.val();
			var useServer = '', useProtocol = '', useSSLType = '', useCert = '';
			if(serverType == 'gmail' || serverType == 'yahoo') {
				useServer = 'imap.gmail.com';
				if(serverType == 'yahoo') {
					useServer = 'imap.mail.yahoo.com';
				}
				useProtocol = 'IMAP4';
				useSSLType = 'ssl';
				useCert = 'novalidate-cert';
				settingContainer.find('.settings_details').removeClass('hide');
				settingContainer.find('.additional_settings').addClass('hide');
			} else if(serverType == 'fastmail') {
				useServer = 'mail.messagingengine.com';
				useProtocol = 'IMAP2';
				useSSLType = 'tls';
				useCert = 'novalidate-cert';
				settingContainer.find('.settings_details').removeClass('hide');
				settingContainer.find('.additional_settings').addClass('hide');
			} else if(serverType == 'other') {
				useServer = '';
				useProtocol = 'IMAP4';
				useSSLType = 'ssl';
				useCert = 'novalidate-cert';
				settingContainer.find('.settings_details').removeClass('hide');
				settingContainer.find('.additional_settings').removeClass('hide');
			} else {
				settingContainer.find('.settings_details').addClass('hide');
			}

			settingContainer.find('.refresh_settings').show();
			settingContainer.find('#_mbox_user').val('');
			settingContainer.find('#_mbox_pwd').val('');
			settingContainer.find('[name="_mbox_sent_folder"]').val('');
			settingContainer.find('.selectFolderValue').addClass('hide');
			settingContainer.find('.selectFolderDesc').removeClass('hide');
			if(useProtocol != '') {
				settingContainer.find('#_mbox_server').val(useServer);
				settingContainer.find('.mbox_protocol').each(function(i, node) {
					if(jQuery(node).val() == useProtocol) {
						jQuery(node).attr('checked', true);
					}
				});
				settingContainer.find('.mbox_ssltype').each(function(i, node) {
					if(jQuery(node).val() == useSSLType) {
						jQuery(node).attr('checked', true);
					}
				});
				settingContainer.find('.mbox_certvalidate').each(function(i, node) {
					if(jQuery(node).val() == useCert) {
						jQuery(node).attr('checked', true);
					}
				});
			}
		});
	},

	registerDeleteMailboxEvent : function(data) {
		var settingContainer = jQuery(data);
		settingContainer.find('#deleteMailboxBtn').click(function(e) {
			e.preventDefault();
			app.helper.showProgress(app.vtranslate("JSLBL_Deleting")+"...");
			var params = {
				'module' : 'MailManager',
				'view' : 'Index',
				'_operation' : 'settings',
				'_operationarg' : 'remove'
			};
			app.request.post({"data" : params}).then(function(error, responseData) {
				app.helper.hideProgress();
				if(responseData.status) {
					window.location.reload();
				}
			});
		});
	},

	registerSaveMailboxEvent : function(data) {
		var settingContainer = jQuery(data);
		settingContainer.find('#saveMailboxBtn').click(function(e) {
			e.preventDefault();
			var form = settingContainer.find('#EditView');
			var data = form.serializeFormData();
			var params = {
				position: {
					'my' : 'bottom left',
					'at' : 'top left',
					'container' : jQuery('#EditView')
			}};
			var errorMsg = app.vtranslate('JS_REQUIRED_FIELD');
			if(data['_mbox_server'] == "") {
				vtUtils.showValidationMessage(settingContainer.find('#_mbox_server'), errorMsg, params);
				return false;
			} else {
				vtUtils.hideValidationMessage(settingContainer.find('#_mbox_server'));
			}
			if(data['_mbox_user'] == "") {
				vtUtils.showValidationMessage(settingContainer.find('#_mbox_user'), errorMsg, params);
				return false;
			} else {
				vtUtils.hideValidationMessage(settingContainer.find('#_mbox_user'));
			}
			if(data['_mbox_pwd'] == "") {
				vtUtils.showValidationMessage(settingContainer.find('#_mbox_pwd'), errorMsg, params);
				return false;
			} else {
				vtUtils.hideValidationMessage(settingContainer.find('#_mbox_pwd'));
			}
			app.helper.showProgress(app.vtranslate("JSLBL_Saving_And_Verifying")+"...");
			var params = {
				'module' : 'MailManager',
				'view' : 'Index',
				'_operation' : 'settings',
				'_operationarg' : 'save'
			};
			jQuery.extend(params, data);
			app.request.post({"data" : params}).then(function(error, responseData) {
				app.helper.hideModal();
				app.helper.hideProgress();
				if(error) {
					app.helper.showAlertNotification({'message' : error.message});
				} else if(responseData.mailbox) {
					window.location.reload();
				}
			});
		});
	},

	registerInitialLayout : function() {
		var self = this;
		var container = self.getContainer();
		if(container.find('#isMailBoxExists').val() == "0") {
			container.find('#modnavigator').addClass('hide');
			container.find('#listViewContent').addClass('paddingLeft0');
		}
	},

	openFolder : function(folderName, page, query, type) {
		var self = this;
		app.helper.showProgress(app.vtranslate("JSLBL_Loading_Please_Wait")+"...");
		if(!page) {
			page = 0;
		}
		var container = self.getContainer();
		vtUtils.hideValidationMessage(container.find('#mailManagerSearchbox'));
		var params = {
			'module' : 'MailManager',
			'view' : 'Index',
			'_operation' : 'folder',
			'_operationarg' : 'open',
			'_folder' : folderName,
			'_page' : page
		};
		if(query) {
			params['q'] = query;
		}
		if(type) {
			params['type'] = type;
		}
		app.request.post({"data" : params}).then(function(error, responseData) {
			container.find('#mails_container').removeClass('col-lg-12');
			container.find('#mails_container').addClass('col-lg-5');
			container.find('#mailPreviewContainer').removeClass('hide');
			container.find('#mails_container').html(responseData);
			app.helper.hideProgress();
			self.registerMoveMailDropdownClickEvent();
			self.registerMailCheckBoxClickEvent();
			self.registerScrollForMailList();
			self.registerMainCheckboxClickEvent();
			self.registerPrevPageClickEvent();
			self.registerNextPageClickEvent();
			self.registerSearchEvent();
			self.registerFolderMailDeleteEvent();
			self.registerMoveMailToFolder();
			self.registerMarkMessageAsUnread();
			self.registerMailClickEvent();
			self.registerMarkMessageAsRead();
			self.clearPreviewContainer();
			self.loadMailContents(folderName);
			container.find('#searchType').trigger('change');
	});
	},

	/**
	 * Function to load the body of all mails in folder list
	 * @param {type} folderName
	 * @returns {undefined}
	 */
	loadMailContents : function(folderName){
		var mailids = jQuery('input[name="folderMailIds"]').val();
		if (typeof mailids !== 'undefined') {
			mailids = mailids.split(",");
			var params = {
				'module' : 'MailManager',
				'action' : 'Folder',
				'mode' : 'showMailContent',
				'mailids' : mailids,
				'folderName':folderName
			};
			app.request.post({"data" : params}).then(function(error, responseData) {
				for(var k in responseData){
					var messageContent = responseData[k];
					var messageEle = jQuery('#mmMailEntry_'+k);
					messageEle.find('.mmMailDesc').html(messageContent);
				}
			});
		}
	},

	registerFolderMailDeleteEvent : function() {
		var self = this;
		var container = self.getContainer();
		container.find('#mmDeleteMail').click(function(e) {
			var folder = jQuery(e.currentTarget).data('folder');
			var msgNos = new Array();
			container.find('.mailCheckBox').each(function(i, ele) {
				var element = jQuery(ele);
				if(element.is(":checked")) {
					msgNos.push(element.closest('.mailEntry').find('.msgNo').val());
				}
			});
			if(msgNos.length <= 0) {
				app.helper.showAlertBox({message:app.vtranslate('JSLBL_NO_EMAILS_SELECTED')});
				return false;
			} else {
				app.helper.showConfirmationBox({'message' : app.vtranslate('LBL_DELETE_CONFIRMATION')}).then(function() {
					app.helper.showProgress(app.vtranslate("JSLBL_Deleting")+"...");
					var params = {
						'module' : 'MailManager',
						'view' : 'Index',
						'_operation' : 'mail',
						'_operationarg' : 'delete',
						'_folder' : folder,
						'_msgno' : msgNos.join(',')
					};
					app.request.post({data : params}).then(function(err,data) {
						app.helper.hideProgress();
						if(data.status) {
							app.helper.showSuccessNotification({'message': app.vtranslate('JSLBL_MAILS_DELETED')});
							self.updateUnreadCount("-"+self.getUnreadCountByMsgNos(msgNos), folder);
							self.updatePagingCount(msgNos.length);
							for(var i = 0; i < msgNos.length; i++) {
								container.find('#mmMailEntry_'+msgNos[i]).remove();
							}
							var openedMsgNo = container.find('#mmMsgNo').val();
							if(jQuery.inArray(openedMsgNo, msgNos) !== -1) {
								self.clearPreviewContainer();
							}
						}
					});
				});
			}
		});
	},

	updatePagingCount : function(deletedCount) {
		var pagingDataElement = jQuery('.pageInfoData');
		var pagingElement = jQuery('.pageInfo');
		if(pagingDataElement.length != 0){
			var total = pagingDataElement.data('total');
			var start = pagingDataElement.data('start');
			var end = pagingDataElement.data('end');
			var labelOf = pagingDataElement.data('label-of');
			total = total - deletedCount;
			pagingDataElement.data('total', total);
			pagingElement.html(start+' '+'-'+' '+end+' '+labelOf+' '+total+'&nbsp;&nbsp;');
		}
	},

	registerMoveMailToFolder : function() {
		var self = this;
		var container = self.getContainer();
		var moveToDropDown = container.find('#mmMoveToFolder');
		moveToDropDown.on('click','a',function(e) {
			var element = jQuery(e.currentTarget);
			var moveToFolder = element.closest('li').data('movefolder');
			var folder = element.closest('li').data('folder');
			var msgNos = new Array();
			container.find('.mailCheckBox').each(function(i, ele) {
				var element = jQuery(ele);
				if(element.is(":checked")) {
					msgNos.push(element.closest('.mailEntry').find('.msgNo').val());
				}
			});
			if(msgNos.length <= 0) {
				container.find('.moveToFolderDropDown').removeClass('open');
				app.helper.showAlertBox({message:app.vtranslate('JSLBL_NO_EMAILS_SELECTED')});
				return false;
			} else {
				app.helper.showProgress(app.vtranslate("JSLBL_MOVING")+"...");
				var params = {
					'module' : 'MailManager',
					'view' : 'Index',
					'_operation' : 'mail',
					'_operationarg' : 'move',
					'_folder' : folder,
					'_moveFolder' : moveToFolder,
					'_msgno' : msgNos.join(',')
				};
				app.request.post({data : params}).then(function(err,data) {
					app.helper.hideProgress();
					if(data.status) {
						app.helper.showSuccessNotification({'message': app.vtranslate('JSLBL_MAIL_MOVED')});
						var unreadCount = self.getUnreadCountByMsgNos(msgNos);
						self.updateUnreadCount("-"+unreadCount, folder);
						self.updateUnreadCount("+"+unreadCount, moveToFolder);
						for(var i = 0; i < msgNos.length; i++) {
							container.find('#mmMailEntry_'+msgNos[i]).remove();
						}
						container.find('.moveToFolderDropDown').removeClass('open');
					}
				});
			}
		});
	},

	registerMarkMessageAsUnread : function() {
		var self = this;
		var container = self.getContainer();
		container.find('#mmMarkAsUnread').click(function(e) {
			var folder = jQuery(e.currentTarget).data('folder');
			var msgNos = new Array();
			container.find('.mailCheckBox').each(function(i, ele) {
				var element = jQuery(ele);
				if(element.is(":checked")) {
					msgNos.push(element.closest('.mailEntry').find('.msgNo').val());
				}
			});
			if(msgNos.length <= 0) {
				app.helper.showAlertBox({message:app.vtranslate('JSLBL_NO_EMAILS_SELECTED')});
				return false;
			} else {
				app.helper.showProgress(app.vtranslate("JSLBL_Updating")+"...");
				var params = {
					'module' : 'MailManager',
					'view' : 'Index',
					'_operation' : 'mail',
					'_operationarg' : 'mark',
					'_folder' : folder,
					'_msgno' : msgNos.join(','),
					'_markas' : 'unread'
				};
				app.request.post({data : params}).then(function(err,data) {
					app.helper.hideProgress();
					if(data.status) {
						app.helper.showSuccessNotification({'message': app.vtranslate('JSLBL_MAILS_MARKED_UNREAD')});
						self.markMessageUnread(msgNos);
						self.updateUnreadCount("+"+self.getUnreadCountByMsgNos(msgNos), folder);
					}
				});
			}
		});
	},

	registerMarkMessageAsRead : function() {
		var self = this;
		var container = self.getContainer();
		container.find('#mmMarkAsRead').click(function(e) {
			var folder = jQuery(e.currentTarget).data('folder');
			var msgNos = new Array();
			container.find('.mailCheckBox').each(function(i, ele) {
				var element = jQuery(ele);
				if(element.is(":checked")) {
					msgNos.push(element.closest('.mailEntry').find('.msgNo').val());
				}
			});
			if(msgNos.length <= 0) {
				app.helper.showAlertBox({message:app.vtranslate('JSLBL_NO_EMAILS_SELECTED')});
				return false;
			} else {
				app.helper.showProgress(app.vtranslate("JSLBL_Updating")+"...");
				var params = {
					'module' : 'MailManager',
					'view' : 'Index',
					'_operation' : 'mail',
					'_operationarg' : 'mark',
					'_folder' : folder,
					'_msgno' : msgNos.join(','),
					'_markas' : 'read'
				};
				app.request.post({data : params}).then(function(err,data) {
					app.helper.hideProgress();
					if(data.status) {
						app.helper.showSuccessNotification({'message': app.vtranslate('JSLBL_MAILS_MARKED_READ')});
						self.markMessageRead(msgNos);
						self.updateUnreadCount("-"+self.getUnreadCountByMsgNos(msgNos), folder);
					}
				});
			}
		});
	},

	registerSearchEvent : function() {
		var self = this;
		var container = self.getContainer();
		container.find('#mm_searchButton').click(function() {
			var query = container.find('#mailManagerSearchbox').val();
			if(query.trim() == '') {
				vtUtils.showValidationMessage(container.find('#mailManagerSearchbox'), app.vtranslate('JSLBL_ENTER_SOME_VALUE'));
				return false;
			} else {
				vtUtils.hideValidationMessage(container.find('#mailManagerSearchbox'));
			}
			var folder = container.find('#mailManagerSearchbox').data('foldername');
			var type = container.find('#searchType').val();
			self.openFolder(folder, 0, query, type);
		});
	},

	markMessageUnread : function(msgNos) {
		var self = this;
		var container = self.getContainer();
		if(typeof msgNos == "string") {
			msgNos = new Array(msgNos);
		}
		if(typeof msgNos == "object") {
			for(var i = 0; i < msgNos.length; i++) {
				var msgNo = msgNos[i];
				var msgEle = container.find('#mmMailEntry_'+msgNo);
				msgEle.removeClass('mmReadEmail');
				msgEle.data('read', "0");
				var nameSubject = "<strong>" + msgEle.find('.nameSubjectHolder').html() + "</strong>";
				msgEle.find('.nameSubjectHolder').html(nameSubject);
			}
		}
	},

	markMessageRead : function(msgNos) {
		var self = this;
		var container = self.getContainer();
		if(typeof msgNos == "string") {
			msgNos = new Array(msgNos);
		}
		if(typeof msgNos == "object") {
			for(var i = 0; i < msgNos.length; i++) {
				var msgNo = msgNos[i];
				var msgEle = container.find('#mmMailEntry_'+msgNo);
				msgEle.addClass('mmReadEmail');
				msgEle.data('read', "1");
				var nameSubject = msgEle.find('.nameSubjectHolder').find('strong').html();
				msgEle.find('.nameSubjectHolder').html(nameSubject);
			}
		}
	},

	getUnreadCountByMsgNos : function(msgNos) {
		var count = 0;
		var self = this;
		var container = self.getContainer();
		for(var i = 0; i < msgNos.length; i++) {
			var isRead = parseInt(container.find('#mmMailEntry_'+msgNos[i]).data('read'));
			if(isRead == 0) {
				count++;
			}
		}
		return count;
	},

	registerMailCheckBoxClickEvent : function() {
		var self = this;
		var container = self.getContainer();
		container.find('.mailCheckBox').click(function(e) {
			var element = jQuery(e.currentTarget);
			if(element.is(":checked")) {
				element.closest('.mailEntry').addClass('highLightMail');
				element.closest('.mailEntry').removeClass('fontBlack');
				element.closest('.mailEntry').addClass('whiteFont');
				element.closest('.mailEntry').removeClass('mmReadEmail');
				element.closest('.mailEntry').find('.mmDateTimeValue').addClass('mmListDateDivSelected');
			} else {
				var isRead = element.closest('.mailEntry').data('read');
				if(parseInt(isRead)) {
					element.closest('.mailEntry').addClass('mmReadEmail');
					element.closest('.mailEntry').removeClass('highLightMail');
				} else {
					element.closest('.mailEntry').removeClass('highLightMail');
				}
				element.closest('.mailEntry').find('.mmDateTimeValue').removeClass('mmListDateDivSelected');
				element.closest('.mailEntry').addClass('fontBlack');
			}
		});
	},

	registerMoveMailDropdownClickEvent : function() {
		var self = this;
		var container = self.getContainer();
		container.find('.moveToFolderDropDown').click(function(e) {
			e.stopImmediatePropagation();
			var element = jQuery(e.currentTarget);
			element.addClass('open');
		});
	},

	registerScrollForMailList : function() {
		var self = this;
		self.getContainer().find('#emailListDiv').mCustomScrollbar({
			setHeight: 600,
			autoExpandScrollbar: true,
			scrollInertia: 200,
			autoHideScrollbar: true,
			theme : "dark-3"
		});
	},

	registerMainCheckboxClickEvent : function() {
		var self = this;
		var container = self.getContainer();
		container.find('#mainCheckBox').click(function(e) {
			var element = jQuery(e.currentTarget);
			if(element.is(":checked")) {
				container.find('.mailCheckBox').each(function(i, ele) {
					jQuery(ele).prop('checked', true);
					jQuery(ele).closest('.mailEntry').addClass('highLightMail');
					jQuery(ele).closest('.mailEntry').removeClass('fontBlack');
					jQuery(ele).closest('.mailEntry').addClass('whiteFont');
					jQuery(ele).closest('.mailEntry').removeClass('mmReadEmail');
					jQuery(ele).closest('.mailEntry').find('.mmDateTimeValue').addClass('mmListDateDivSelected');
				});
			} else {
				container.find('.mailCheckBox').each(function(i, ele) {
					jQuery(ele).prop('checked', false);
					var isRead = jQuery(ele).closest('.mailEntry').data('read');
					if(parseInt(isRead)) {
						jQuery(ele).closest('.mailEntry').addClass('mmReadEmail');
						jQuery(ele).closest('.mailEntry').removeClass('highLightMail');
					} else {
						jQuery(ele).closest('.mailEntry').removeClass('highLightMail');
					}
					jQuery(ele).closest('.mailEntry').find('.mmDateTimeValue').removeClass('mmListDateDivSelected');
					jQuery(ele).closest('.mailEntry').addClass('fontBlack');
				});
			}
		});
	},

	registerPrevPageClickEvent : function() {
		var self = this;
		var container = self.getContainer();
		container.find('#PreviousPageButton').click(function(e) {
			var element = jQuery(e.currentTarget);
			var folder = element.data('folder');
			var page = element.data('page');
			self.openFolder(folder, page, jQuery('#mailManagerSearchbox').val(), jQuery('#searchType').val());
		});
	},

	registerNextPageClickEvent : function() {
		var self = this;
		var container = self.getContainer();
		container.find('#NextPageButton').click(function(e) {
			var element = jQuery(e.currentTarget);
			var folder = element.data('folder');
			var page = element.data('page');
			self.openFolder(folder, page, jQuery('#mailManagerSearchbox').val(), jQuery('#searchType').val());
		});
	},

	registerMailClickEvent : function() {
		var self = this;
		var container = self.getContainer();
		container.find('.mmfolderMails').click(function(e) {
			var emailElement = jQuery(e.currentTarget);
			var parentEle = emailElement.closest('.mailEntry');
			var msgNo = emailElement.find('.msgNo').val();
			var params = {
				'module' : 'MailManager',
				'view' : 'Index',
				'_operation' : 'mail',
				'_operationarg' : 'open',
				'_folder' : parentEle.data('folder'),
				'_msgno' : msgNo
			};
			app.helper.showProgress(app.vtranslate("JSLBL_Opening")+"...");
			app.request.post({data : params}).then(function(err, data) {
				app.helper.hideProgress();
				var uiContent = data.ui;
				var unreadCount = self.getUnreadCountByMsgNos(new Array(msgNo));
				jQuery(parentEle).addClass('mmReadEmail');
				jQuery(parentEle).data('read', "1");
				var nameSubject = jQuery(parentEle).find('.nameSubjectHolder').find('strong').html();
				jQuery(parentEle).find('.nameSubjectHolder').html(nameSubject);
				container.find('#mailPreviewContainer').html(uiContent);
				self.highLightMail(msgNo);
				self.registerMailDeleteEvent();
				self.registerForwardEvent();
				self.registerPrintEvent();
				self.registerReplyEvent();
				self.registerReplyAllEvent();
				self.showRelatedActions();
				self.registerMailPaginationEvent();
				container.find('.emailDetails').popover({html: true});
				self.updateUnreadCount("-"+unreadCount, jQuery(parentEle).data('folder'));
				self.loadContentsInIframe(container.find('#mmBody'));
			});
		});
	},

	loadContentsInIframe : function(element) {
		var bodyContent = element.html();
		element.html('<iframe id="bodyFrame" style="width: 100%; border: none;"></iframe>');
		var frameElement = jQuery("#bodyFrame")[0].contentWindow.document;
		frameElement.open();
		frameElement.close();
		jQuery('#bodyFrame').contents().find('html').html(bodyContent);
		jQuery('#bodyFrame').contents().find('html').find('a').on('click', function(e) {
			e.preventDefault();
			var url = jQuery(e.currentTarget).attr('href');
			window.open(url, '_blank');
		});
	},

	highLightMail : function(msgNo) {
		var self = this;
		var container = self.getContainer();
		container.find('.mailEntry').each(function(i, ele) {
			var element = jQuery(ele);
			var isRead = element.data('read');
			if(parseInt(isRead)) {
				element.addClass('mmReadEmail');
				element.removeClass('highLightMail');
			} else {
				element.removeClass('highLightMail');
			}
			element.find('.mmDateTimeValue').removeClass('mmListDateDivSelected');
			element.addClass('fontBlack');
		});
		var selectedMailEle = container.find('#mmMailEntry_'+msgNo);
		selectedMailEle.addClass('highLightMail');
		selectedMailEle.removeClass('fontBlack');
		selectedMailEle.addClass('whiteFont');
		selectedMailEle.removeClass('mmReadEmail');
		selectedMailEle.find('.mmDateTimeValue').addClass('mmListDateDivSelected');
	},

	registerMailPaginationEvent : function() {
		var self = this;
		var container = self.getContainer();
		container.find('.mailPagination').click(function(e) {
			var element = jQuery(e.currentTarget);
			var msgNo = element.data('msgno');
			var folder = element.data('folder');
			var params = {
				'module' : 'MailManager',
				'view' : 'Index',
				'_operation' : 'mail',
				'_operationarg' : 'open',
				'_folder' : folder,
				'_msgno' : msgNo
			};
			app.helper.showProgress(app.vtranslate("JSLBL_Opening")+"...");
			app.request.post({data : params}).then(function(err, data) {
				app.helper.hideProgress();
				var uiContent = data.ui;
				container.find('#mmMailEntry_'+msgNo).addClass('mmReadEmail');
				container.find('#mmMailEntry_'+msgNo).data('read', "1");
				var nameSubject = container.find('#mmMailEntry_'+msgNo).find('.nameSubjectHolder').find('strong').html();
				container.find('#mmMailEntry_'+msgNo).find('.nameSubjectHolder').html(nameSubject);
				container.find('#mailPreviewContainer').html(uiContent);
				self.registerMailDeleteEvent();
				self.registerForwardEvent();
				self.registerReplyEvent();
				self.registerReplyAllEvent();
				self.showRelatedActions();
				self.registerMailPaginationEvent();
				self.highLightMail(msgNo);
				self.loadContentsInIframe(container.find('#mmBody'));
			});
		});
	},

	registerMailDeleteEvent : function() {
		var self = this;
		var container = self.getContainer();
		container.find('#mmDelete').click(function() {
			var msgNo = jQuery('#mmMsgNo').val();
			var folder = jQuery('#mmFolder').val();
			app.helper.showConfirmationBox({'message' : app.vtranslate('LBL_DELETE_CONFIRMATION')}).then(function() {
				app.helper.showProgress(app.vtranslate("JSLBL_Deleting")+"...");
				var params = {
					'module' : 'MailManager',
					'view' : 'Index',
					'_operation' : 'mail',
					'_operationarg' : 'delete',
					'_folder' : folder,
					'_msgno' : msgNo
				};
				app.request.post({data : params}).then(function(err,data) {
					app.helper.hideProgress();
					if(data.status) {
						container.find('#mmMailEntry_'+msgNo).remove();
						var previewHtml = '<div class="mmListMainContainer">\n\
										<center><strong>'+app.vtranslate('JSLBL_NO_MAIL_SELECTED_DESC')+'</center></strong></div>';
						jQuery('#mailPreviewContainer').html(previewHtml);
					}
				});
			});
		});
	},

	registerForwardEvent : function() {
		var self = this;
		var container = self.getContainer();
		container.find('#mmForward').click(function() {
			app.helper.showProgress(app.vtranslate("JSLBL_Loading")+"...");
			var msgNo = jQuery('#mmMsgNo').val();
			var from = jQuery('#mmFrom').val();
			var to = jQuery('#mmTo').val();
			var cc = jQuery('#mmCc').val() ? jQuery('#mmCc').val() : '';
			var subject = JSON.parse(jQuery('#mmSubject').val());
			var body = jQuery('#mmBody').find('iframe#bodyFrame').contents().find('html').html();
			var date = jQuery('#mmDate').val();
			var folder = jQuery('#mmFolder').val();

			var fwdMsgMetaInfo = app.vtranslate('JSLBL_FROM') + from + '<br/>'+
					app.vtranslate('JSLBL_DATE') + date + '<br/>'+
					app.vtranslate('JSLBL_SUBJECT') + subject;
			if (to != '' && to != null) {
				fwdMsgMetaInfo += '<br/>'+app.vtranslate('JSLBL_TO') + to;
			}
			if (cc != '' && cc != null) {
				fwdMsgMetaInfo += '<br/>'+app.vtranslate('JSLBL_CC') + cc;
			}
			fwdMsgMetaInfo += '<br/>';

			var fwdSubject = (subject.toUpperCase().indexOf('FWD:') == 0) ? subject : 'Fwd: ' + subject;
			var fwdBody = '<p></p><p>'+app.vtranslate('JSLBL_FORWARD_MESSAGE_TEXT')+'<br/>'+fwdMsgMetaInfo+'</p>'+body;
			var attchmentCount = parseInt(container.find('#mmAttchmentCount').val());
			if(attchmentCount) {
				var params = {
					'module' : 'MailManager',
					'view' : 'Index',
					'_operation' : 'mail',
					'_operationarg' : 'forward',
					'messageid' : encodeURIComponent(msgNo),
					'folder' : encodeURIComponent(folder),
					'subject' : encodeURIComponent(fwdSubject),
					'body' : encodeURIComponent(fwdBody)
				};
				app.request.post({'data' : params}).then(function(err, data) {
					var draftId = data.emailid;
					var newParams = {
						'module' : 'Emails',
						'view' : 'ComposeEmail',
						'mode' : 'emailEdit',
						'record' : draftId
					};
					app.request.post({data : newParams}).then(function(err,data) {
						app.helper.hideProgress();
						if(err === null) {
							var dataObj = jQuery(data);
							var descriptionContent = dataObj.find('#iframeDescription').val();
							app.helper.showModal(data, {cb : function() {
								var editInstance = new Emails_MassEdit_Js();
								editInstance.registerEvents();
								jQuery('#emailPreviewIframe').contents().find('html').html(descriptionContent);
								jQuery("#emailPreviewIframe").height(jQuery('#emailPreviewIframe').contents().find('html').height());
							}});
						}
					});
				});
			} else {
				app.helper.hideProgress();
				var params = {
					'step' : "step1",
					'module' : "MailManager",
					'view' : "MassActionAjax",
					'mode' : "showComposeEmailForm",
					'selected_ids' : "[]",
					'excluded_ids' : "[]",
				}
				self.openComposeEmailForm("forward", params, {'subject' : fwdSubject, 'body' : fwdBody});
			}
		});
	},

	registerPrintEvent : function() {
		var self = this;
		var container = self.getContainer();
		container.find('#mmPrint').click(function() {
			var subject = JSON.parse(container.find('#mmSubject').val());
			var from = container.find('#mmFrom').val();
			var to = container.find('#mmTo').val();
			var cc = container.find('#mmCc').val();
			var date = container.find('#mmDate').val();
			var body = jQuery('#mmBody').find('iframe#bodyFrame').contents().find('html').html();

			var content = window.open();
			content.document.write("<b>"+subject+"</b><br>");
			content.document.write(app.vtranslate("JSLBL_FROM")+" "+from +"<br>");
			content.document.write(app.vtranslate("JSLBL_TO")+" "+to+"<br>");
			if(cc) {
				content.document.write(app.vtranslate("JSLBL_CC")+" "+cc+"<br>");
			}
			content.document.write(app.vtranslate("JSLBL_DATE")+" "+date+"<br>");
			content.document.write("<br><br>"+body);
			content.print();
		});
	},

	registerReplyEvent : function() {
		var self = this;
		self.getContainer().find('#mmReply').click(function() {
			self.openReplyEmail(false);
		});
	},

	registerReplyAllEvent : function() {
		var self = this;
		self.getContainer().find('#mmReplyAll').click(function() {
			self.openReplyEmail(true);
		});
	},

	openReplyEmail : function(all) {
		var self = this;
		if (typeof(all) == 'undefined') {
			all = true;
		}
		var mUserName = jQuery('#mmUserName').val();
		var from = jQuery('#mmFrom').val();
		var to = all ? jQuery('#mmTo').val() : '';
		var cc = all ? jQuery('#mmCc').val() : '';

		var mailIds = '';
		if(to != null) {
			mailIds = to;
		}
		if(cc != null) {
			mailIds = mailIds ? mailIds+','+cc : cc;
		}

		mailIds = mailIds.replace(/\s+/g, '');

		var emails = mailIds.split(',');
		for(var i = 0; i < emails.length ; i++) {
			if(emails[i].indexOf(mUserName) != -1){
				emails.splice(i,1);
			}
		}
		mailIds = emails.join(',');

		mailIds = mailIds.replace(',,', ',');
		if(mailIds.charAt(mailIds.length-1) == ',') {
			mailIds = mailIds.slice(0, -1);
		} else if(mailIds.charAt(0) == ','){
			mailIds = mailIds.slice(1);
		}

		var subject = JSON.parse(jQuery('#mmSubject').val());
		var body = jQuery('#mmBody').find('iframe#bodyFrame').contents().find('html').html();
		var date = jQuery('#mmDate').val();

		var replySubject = (subject.toUpperCase().indexOf('RE:') == 0) ? subject : 'Re: ' + subject;
		var replyBody = '<p></br></br></p><p style="margin:0;padding:0;">On '+date+', '+from+' wrote :</p><blockquote style="border:0;margin:0;border-left:1px solid gray;padding:0 0 0 2px;">'+body+'</blockquote><br />';
		var parentRecord = new Array();
		var linktoElement = jQuery('[name=_mlinkto]');
		linktoElement.each(function(index){
			var value = jQuery(this).val();
			if(value) {
				parentRecord.push(value);
			}
		});
		var params = {
			'step' : "step1",
			'module' : "MailManager",
			'view' : "MassActionAjax",
			'mode' : "showComposeEmailForm",
			'linktomodule' : 'true', 
			'excluded_ids' : "[]",
			'to' : '["'+from+'"]'
		}
		if(parentRecord.length) {
			params['selected_ids'] = parentRecord;
		} else {
			params['selected_ids'] = "[]";
		}
		if(mailIds) {
			self.openComposeEmailForm("replyall", params, {'subject' : replySubject, 'body' : replyBody, 'ids' : mailIds});
		} else {
			self.openComposeEmailForm("reply", params, {'subject' : replySubject, 'body' : replyBody});
		}
	},

	showRelatedActions : function() {
		var self = this;
		var container = self.getContainer();
		var from = container.find('#mmFrom').val();
		var to = container.find('#mmTo').val();
		var folder = container.find('#mmFolder').val();
		var msgNo = container.find('#mmMsgNo').val();
		var msgUid = container.find('#mmMsgUid').val();

		var params = {
			'module' : 'MailManager',
			'view' : 'Index',
			'_operation' : 'relation',
			'_operationarg' : 'find',
			'_mfrom' : from,
			'_mto' : to,
			'_folder' : folder,
			'_msgno' : msgNo,
			'_msguid' : msgUid
		};

		app.request.post({data : params}).then(function(err, data) {
			container.find('#relationBlock').html(data.ui);
			self.handleRelationActions();
			app.helper.showVerticalScroll(container.find('#relationBlock .recordScroll'), {autoHideScrollbar: true});
			var iframeHeight = jQuery('#mails_container').height() - (200 + jQuery('#mailManagerActions').height());
			var contentHeight = jQuery('#bodyFrame').contents().find('html').height();
			if (contentHeight > iframeHeight) {
				jQuery('#bodyFrame').css({'height': iframeHeight});
			} else {
				jQuery('#bodyFrame').css({'height': contentHeight});
			}
		});
	},

	openDraftFolder : function(page, query, type) {
		var self = this;
		app.helper.showProgress(app.vtranslate("JSLBL_Loading_Please_Wait")+"...");
		if(!page) {
			page = 0;
		}
		var container = self.getContainer();
		vtUtils.hideValidationMessage(container.find('#mailManagerSearchbox'));
		var params = {
			'module' : 'MailManager',
			'view' : 'Index',
			'_operation' : 'folder',
			'_operationarg' : 'drafts',
			'_page' : page
		};
		if(query) {
			params['q'] = query;
		}
		if(type) {
			params['type'] = type;
		}
		app.request.post({"data" : params}).then(function(error, responseData) {
			container.find('#mails_container').removeClass('col-lg-5');
			container.find('#mails_container').addClass('col-lg-12');
			container.find('#mails_container').html(responseData);
			container.find('#mailPreviewContainer').addClass('hide');
			app.helper.hideProgress();
			self.registerMoveMailDropdownClickEvent();
			self.registerMailCheckBoxClickEvent();
			self.registerScrollForMailList();
			self.registerMainCheckboxClickEvent();
			self.registerDraftPrevPageClickEvent();
			self.registerDraftNextPageClickEvent();
			self.registerDraftMailClickEvent();
			self.registerDraftSearchEvent();
			self.registerDraftDeleteEvent();
			self.clearPreviewContainer();
		});
	},

	registerDraftPrevPageClickEvent : function() {
		var self = this;
		var container = self.getContainer();
		container.find('#PreviousPageButton').click(function(e) {
			var element = jQuery(e.currentTarget);
			var page = element.data('page');
			self.openDraftFolder(page);
		});
	},

	registerDraftNextPageClickEvent : function() {
		var self = this;
		var container = self.getContainer();
		container.find('#NextPageButton').click(function(e) {
			var element = jQuery(e.currentTarget);
			var page = element.data('page');
			self.openDraftFolder(page);
		});
	},

	registerDraftMailClickEvent : function() {
		var self = this;
		var container = self.getContainer();
		container.find('.draftEmail').click(function(e) {
			e.preventDefault();
			var element = jQuery(e.currentTarget);
			var msgNo = element.find('.msgNo').val();
			var params = {
				'module' : 'Emails',
				'view' : 'ComposeEmail',
				'mode' : 'emailEdit',
				'record' : msgNo
			};
			app.helper.showProgress(app.vtranslate("JSLBL_Opening")+"...");
			app.request.post({data : params}).then(function(err,data) {
				app.helper.hideProgress();
				if(err === null) {
					var dataObj = jQuery(data);
					var descriptionContent = dataObj.find('#iframeDescription').val();
					app.helper.showModal(data, {cb:function() {
						var editInstance = new Emails_MassEdit_Js();
						editInstance.registerEvents();
						jQuery('#emailPreviewIframe').contents().find('html').html(descriptionContent);
						jQuery("#emailPreviewIframe").height(jQuery('.email-body-preview').height());
					}});
				}
			});
		});
	},

	registerDraftSearchEvent : function() {
		var self = this;
		var container = self.getContainer();
		container.find('#mm_searchButton').click(function() {
			var query = container.find('#mailManagerSearchbox').val();
			if(query.trim() == '') {
				vtUtils.showValidationMessage(container.find('#mailManagerSearchbox'), app.vtranslate('JSLBL_ENTER_SOME_VALUE'));
				return false;
			} else {
				vtUtils.hideValidationMessage(container.find('#mailManagerSearchbox'));
			}
			var type = container.find('#searchType').val();
			self.openDraftFolder(0, query, type);
		});
	},

	registerDraftDeleteEvent : function() {
		var self = this;
		var container = self.getContainer();
		container.find('#mmDeleteMail').click(function() {
			var msgNos = new Array();
			container.find('.mailCheckBox').each(function(i, ele) {
				var element = jQuery(ele);
				if(element.is(":checked")) {
					msgNos.push(element.closest('.mailEntry').find('.msgNo').val());
				}
			});
			if(msgNos.length <= 0) {
				app.helper.showAlertBox({message:app.vtranslate('JSLBL_NO_EMAILS_SELECTED')});
				return false;
			} else {
				app.helper.showConfirmationBox({'message' : app.vtranslate('LBL_DELETE_CONFIRMATION')}).then(function() {
					app.helper.showProgress(app.vtranslate("JSLBL_Deleting")+"...");
					var params = {
						'module' : 'MailManager',
						'view' : 'Index',
						'_operation' : 'mail',
						'_operationarg' : 'delete',
						'_folder' : '__vt_drafts',
						'_msgno' : msgNos.join(',')
					};
					app.request.post({data : params}).then(function(err,data) {
						app.helper.hideProgress();
						if(data.status) {
							self.openDraftFolder();
							app.helper.showSuccessNotification({'message': app.vtranslate('JSLBL_MAILS_DELETED')});
						}
					});
				});
			}
		});
	},

	updateUnreadCount : function(count, folder) {
		var self = this;
		var container = self.getContainer();
		if(!folder) {
			folder = container.find('.mm_folder.active').data('foldername');
		}
		var newCount;
		if(typeof count == "number") {
			newCount = parseInt(count);
		} else {
			var oldCount = parseInt(container.find('.mm_folder[data-foldername="'+folder+'"]').find('.mmUnreadCountBadge').text());
			if(count.substr(0, 1) == "+") {
				newCount = oldCount + (parseInt(count.substr(1, (count.length - 1))));
			} else if(count.substr(0, 1) == "-") {
				newCount = oldCount - (parseInt(count.substr(1, (count.length - 1))));
			} else {
				newCount = parseInt(count);
			}
		}
		container.find('.mm_folder[data-foldername="'+folder+'"]').find('.mmUnreadCountBadge').text(newCount);
		if(newCount > 0) {
			container.find('.mm_folder[data-foldername="'+folder+'"]').find('.mmUnreadCountBadge').removeClass("hide");
		} else {
			container.find('.mm_folder[data-foldername="'+folder+'"]').find('.mmUnreadCountBadge').addClass("hide");
		}
	},

	handleRelationActions : function() {
		var self = this;
		var container = self.getContainer();
		container.find('#_mlinktotype').on('change', function(e) {
			var element = jQuery(e.currentTarget);
			var actionType = element.data('action');
			var module = element.val();
			var relatedRecord = self.getRecordForRelation();
			if(relatedRecord !== false) {
				if(actionType == "associate") {
					if(module == 'Emails') {
						self.associateEmail(relatedRecord);
					} else if(module == "ModComments") {
						self.associateComment(relatedRecord);
					} else if(module) {
						self.createRelatedRecord(module);
					}
				} else if(module) {
					self.createRelatedRecord(module);
				}
			}
			self.resetRelationDropdown();
		});
	},

	associateEmail : function(relatedRecord) {
		var self = this;
		var container = self.getContainer();
		var params = {
			'module' : 'MailManager',
			'view' : 'Index',
			'_operation' : 'relation',
			'_operationarg' : 'link',
			'_mlinkto' : relatedRecord,
			'_mlinktotype' : 'Emails',
			'_folder' : container.find('#mmFolder').val(),
			'_msgno' : container.find('#mmMsgNo').val()
		}
		app.helper.showProgress(app.vtranslate('JSLBL_Associating')+'...');
		app.request.post({data : params}).then(function(err,data) {
			if (err === null) {
				app.helper.showSuccessNotification({'message':''});
				app.helper.hideProgress();
			} else {
				app.helper.showErrorNotification({"message": err});
			}
		});
	},

	associateComment : function(relatedRecord) {
		var self = this;
		var container = self.getContainer();
		var params = {
			'module' : 'MailManager',
			'view' : 'Index',
			'_operation' : 'relation',
			'_operationarg' : 'commentwidget',
			'_mlinkto' : relatedRecord,
			'_mlinktotype' : 'ModComments',
			'_folder' : container.find('#mmFolder').val(),
			'_msgno' : container.find('#mmMsgNo').val()
		}
		app.helper.showProgress(app.vtranslate('JSLBL_Loading')+'...');
		app.request.post({data : params}).then(function(err, data) {
			app.helper.hideProgress();
			app.helper.showModal(data, {'cb' : function(data) {
				jQuery('[name="saveButton"]', data).on('click',function(e){
					e.preventDefault();
					self.saveComment(data);
				});
			}});
		});
	},

	createRelatedRecord : function(module) {
		var self = this;
		var container = self.getContainer();
		var relatedRecord = self.getRecordForRelation();
		var msgNo = container.find('#mmMsgNo').val();
		var folder = container.find('#mmFolder').val();
		var params = {
			'module' : 'MailManager',
			'view' : 'Index',
			'_operation' : 'relation',
			'_operationarg' : 'create_wizard',
			'_mlinktotype' : module,
			'_folder' : folder,
			'_msgno' : msgNo
		};
		if(relatedRecord && relatedRecord !== null) {
			params['_mlinkto'] = relatedRecord;
		}
		app.helper.showProgress(app.vtranslate('JSLBL_Loading')+'...');
		app.request.post({data : params}).then(function(err, data) {
			app.helper.hideProgress();
			app.helper.showModal(data);
			var form = jQuery('form[name="QuickCreate"]');
			app.event.trigger('post.QuickCreateForm.show',form);
			vtUtils.applyFieldElementsView(form);
			var moduleName = form.find('[name="module"]').val();
			var targetClass = app.getModuleSpecificViewClass('Edit', moduleName);
			var targetInstance = new window[targetClass]();
			targetInstance.registerBasicEvents(form);
			var newParams = {};
			newParams.callbackFunction = function() {
				app.helper.hideModal();
				self.showRelatedActions();
			};
			newParams.requestParams = params;
			self.quickCreateSave(form, newParams);
			app.helper.hideProgress();
		});
	},

	/**
	 * Register Quick Create Save Event
	 * @param {type} form
	 * @returns {undefined}
	 */
	quickCreateSave : function(form,invokeParams){
		var container = this.getContainer();
		var params = {
			submitHandler: function(form) {
				// to Prevent submit if already submitted
				jQuery("button[name='saveButton']").attr("disabled","disabled");
				if(this.numberOfInvalids() > 0) {
					return false;
				}
				var formData = jQuery(form).serialize();
				var requestParams = invokeParams.requestParams;

				// replacing default parameters for custom handlings in mail manager
				formData = formData.replace('module=', 'xmodule=').replace('action=', 'xaction=');
				if(requestParams) {
					requestParams['_operationarg'] = 'create';
					if(requestParams['_mlinktotype'] == 'Events') {
						requestParams['_mlinktotype'] = 'Calendar';
					}
					jQuery.each(requestParams, function(key, value){
						formData += "&"+key+"="+value;
					});
				}

				app.request.post({data:formData}).then(function(err,data){
                    if(err === null) {
						if (!data.error) {
							jQuery('.vt-notification').remove();
							app.event.trigger("post.QuickCreateForm.save",data,jQuery(form).serializeFormData());
							app.helper.hideModal();
							app.helper.showSuccessNotification({"message":app.vtranslate('JS_RECORD_CREATED')});
							invokeParams.callbackFunction(data, err);
						} else {
							jQuery("button[name='saveButton']").removeAttr('disabled');
							app.event.trigger('post.save.failed', data);
						}
                    }else{
						app.event.trigger("post.QuickCreateForm.save",data,jQuery(form).serializeFormData());
                        app.helper.showErrorNotification({"message":err});
                    }
                });
			}
		};
		form.vtValidate(params);
	},

	saveComment : function(data) {
		var _mlinkto = jQuery('[name="_mlinkto"]', data).val();
		var _mlinktotype = jQuery('[name="_mlinktotype"]', data).val();
		var _msgno = jQuery('[name="_msgno"]', data).val();
		var _folder = jQuery('[name="_folder"]', data).val();
		var commentcontent = jQuery('[name="commentcontent"]', data).val();
		if(commentcontent.trim() == "") {
			var validationParams = {
				position: {
					'my' : 'bottom left',
					'at' : 'top left',
					'container' : jQuery('#commentContainer', data)
				}
			};
			var errorMsg = app.vtranslate('JSLBL_CANNOT_ADD_EMPTY_COMMENT');
			vtUtils.showValidationMessage(jQuery('[name="commentcontent"]', data), errorMsg, validationParams);
			return false;
		} else {
			vtUtils.hideValidationMessage(jQuery('[name="commentcontent"]', data));
		}
		var params = {
			'module' : 'MailManager',
			'view' : 'Index',
			'_operation' : 'relation',
			'_operationarg' : 'create',
			'commentcontent' : commentcontent,
			'_mlinkto' : _mlinkto,
			'_mlinktotype' : _mlinktotype,
			'_msgno' : _msgno,
			'_folder' : _folder
		}
		app.helper.showProgress(app.vtranslate('JSLBL_Saving')+'...');
		app.request.post({'data' : params}).then(function(err, response) {
			app.helper.hideProgress();
			if(response.ui) {
				app.helper.showSuccessNotification({'message':''});
				app.helper.hideModal();
			} else {
				app.helper.showAlertBox({'message' : app.vtranslate("JSLBL_FAILED_ADDING_COMMENT")});
			}
		});
	},

	getRecordForRelation : function() {
		var self = this;
		var container = self.getContainer();
		var element = container.find('[name="_mlinkto"]');
		if(element.length > 0) {
			if(element.length == 1) {
				element.attr('checked', true);
				return element.val();
			} else {
				selected = false;
				element.each(function(i, ele) {
					if(jQuery(ele).is(":checked")) {
						selected = true;
					}
				});
				if(selected) {
					return container.find('[name="_mlinkto"]:checked').val();
				} else {
					app.helper.showAlertBox({'message' : app.vtranslate("JSLBL_PLEASE_SELECT_ATLEAST_ONE_RECORD")});
					return false;
				}
			}
		} else {
			return null;
		}
	},

	resetRelationDropdown : function() {
		this.getContainer().find('#_mlinktotype').val("");
	},

	openComposeEmailForm : function(type, params, data) {
		Vtiger_Index_Js.showComposeEmailPopup(params, function(response) {
			var descEle = jQuery(response).find('#description');
			if(type == "reply" || type == "forward") {
				jQuery('#subject', response).val(data.subject);
				descEle.val(data.body);
				jQuery('[name="cc"]', response).val("");
				jQuery('.ccContainer', response).addClass("hide");
				jQuery('#ccLink', response).css("display", "");
			} else if(type == "replyall") {
				jQuery('#subject', response).val(data.subject);
				descEle.val(data.body);
				var mailIds = data.ids;
				if(mailIds) {
					jQuery('.ccContainer', response).removeClass("hide");
					jQuery('#ccLink', response).css("display", "none");
					jQuery('[name="cc"]', response).val(mailIds);
				}
			} else {
				jQuery('#subject', response).val("");
				descEle.val("");
				jQuery('[name="cc"]', response).val("");
				jQuery('.ccContainer', response).addClass("hide");
				jQuery('#ccLink', response).css("display", "");
			}
		});
	},

	clearPreviewContainer : function() {
		var previewHtml = '<div class="mmListMainContainer">\n\
							<center><strong>'+app.vtranslate('JSLBL_NO_MAIL_SELECTED_DESC')+'</center></strong></div>';
		this.getContainer().find('#mailPreviewContainer').html(previewHtml);
	},

	registerRefreshFolder : function() {
		var self = this;
		var container = self.getContainer();
		container.find('.mailbox_refresh').click(function() {
			var folder = container.find('.mm_folder.active').data('foldername');
			if(folder == 'vt_drafts') {
				self.openDraftFolder();
			} else {
				self.openFolder(folder);
			}
		});
	},

	registerSearchTypeChangeEvent : function() {
		var container = this.getContainer();
		container.on('change', '#searchType', function(e){
			var element = jQuery(e.currentTarget);
			var searchBox = jQuery('#mailManagerSearchbox');
			if(element.val() == 'ON'){
				searchBox.addClass('dateField');
				searchBox.parent().append('<span class="date-addon input-group-addon"><i class="fa fa-calendar"></i></span>');
				vtUtils.registerEventForDateFields(searchBox);
			} else {
				searchBox.datepicker('remove');
				searchBox.removeClass('dateField');
				searchBox.parent().find('.date-addon').remove();
			}
		});
	},

	registerPostMailSentEvent: function () {
		app.event.on('post.mail.sent', function (event, data) {
			var resultEle = jQuery(data);
			var success = resultEle.find('.mailSentSuccessfully');
			if (success.length > 0) {
				app.helper.showModal(data);
			}
		});
	},

	registerEvents : function() {
		var self = this;
		self.loadFolders();
		self.registerComposeEmail();
		self.registerSettingsEdit();
		self.registerInitialLayout();
		self.registerRefreshFolder();
		self.registerSearchTypeChangeEvent();
		self.registerPostMailSentEvent();
	}
});
