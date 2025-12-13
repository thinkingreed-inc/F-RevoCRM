Calendar_Calendar_Js('Calendar_SharedCalendar_Js', {

	currentInstance : false,
	calendarViewContainer : false

}, {
	userList: [],

	getCalendarViewContainer : function() {
		if(!Calendar_SharedCalendar_Js.calendarViewContainer.length) {
			Calendar_SharedCalendar_Js.calendarViewContainer = jQuery('#sharedcalendar');
		}
		return Calendar_SharedCalendar_Js.calendarViewContainer;
	},

	getFeedRequestParams : function(start,end,feedCheckbox) {
		var thisInstance = this;
		var URL_Search = new URLSearchParams(location.search);
		var isSharedCalendar = (URL_Search.get('view') == 'SharedCalendar');
		if (isSharedCalendar && feedCheckbox.data('calendarFeed') === 'Calendar') {
			return thisInstance.getToDoFeedRequestParams(start, end, feedCheckbox);
		}

		var dateFormat = 'YYYY-MM-DD';
		var startDate = start.format(dateFormat);
		var endDate = end.format(dateFormat);
		return {
			'start' : startDate,
			'end' : endDate,
			'type' : feedCheckbox.data('calendarFeed'),
			'userid' : feedCheckbox.data('calendarUserid'),
			'group' : feedCheckbox.data('calendarGroup'),
			'color' : feedCheckbox.data('calendarFeedColor'),
			'textColor' : feedCheckbox.data('calendarFeedTextcolor')
		};
	},

	getToDoFeedRequestParams : function(start,end,feedCheckbox) {
		var dateFormat = 'YYYY-MM-DD';
		var startDate = start.format(dateFormat);
		var endDate = end.format(dateFormat);
		return {
			'start': startDate,
			'end': endDate,
			'type': feedCheckbox.data('calendarFeed'),
			'fieldname': feedCheckbox.data('calendarFieldname'),
			'color': feedCheckbox.data('calendarFeedColor'),
			'textColor': feedCheckbox.data('calendarFeedTextcolor'),
			'conditions': feedCheckbox.data('calendarFeedConditions'),
			'userid' : feedCheckbox.data('calendarUserid')
		};
	},

	removeEvents : function(feedCheckbox) {
		var module = feedCheckbox.data('calendarFeed');
		var userId = feedCheckbox.data('calendarUserid');
		this.getCalendarViewContainer().fullCalendar('removeEvents', 
		function(eventObj) {
			// ここでIDのみでON/OFFしてるのでカレンダーとTODOでいい感じに切り分ける必要ある
			return module === eventObj.module && parseInt(userId) === parseInt(eventObj.userid);
		});
	},

	_colorize : function(feedCheckbox) {
		var thisInstance = this;
		var sourcekey = feedCheckbox.data('calendarSourcekey');
		var color = feedCheckbox.data('calendarFeedColor');
		if(color === '' || typeof color === 'undefined') {
			color = app.storage.get(sourcekey);
			if(!color) {
				// feedCheckbox.closest('.calendar-feed-indicator')のbackground-colorを取得し、存在すればそのcolorを使用
				var bgColor = feedCheckbox.closest('.calendar-feed-indicator').css('background-color');
				if (bgColor && bgColor !== 'rgba(0, 0, 0, 0)' && bgColor !== 'transparent') {
					color = bgColor;
					app.storage.set(sourcekey, color);
				} else {
					color = thisInstance.getRandomColor();
					app.storage.set(sourcekey, color);
				}
			}

			if (color && color.startsWith('rgb')) {
				// rgbまたはrgbaをhexに変換
				var rgb = color.replace(/[^\d,]/g, '').split(',');
				var r = parseInt(rgb[0], 10);
				var g = parseInt(rgb[1], 10);
				var b = parseInt(rgb[2], 10);
				color = "#" + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
				app.storage.set(sourcekey, color);
			}

			feedCheckbox.data('calendarFeedColor',color);
			feedCheckbox.closest('.calendar-feed-indicator').css({'background-color':color});
		}
	},

	colorizeFeed : function(feedCheckbox) {
		this._colorize(feedCheckbox);
		this.assignFeedTextColor(feedCheckbox);
	},

	registerAddUserCalendarViewActions : function(modalContainer) {
		this.registerColorEditorEvents(modalContainer);
	},

	showAddUserCalendarView : function() {
		var thisInstance = this;
		var params = {
			module : app.getModuleName(),
			view : 'UserCalendarViews',
			mode : 'addUserCalendar'
		};
		app.helper.showProgress();
		app.request.post({'data':params}).then(function(e,data) {
			app.helper.hideProgress();
			if(!e) {
				if(jQuery(data).find('select[name="usersList"] > option').length) {
					app.helper.showModal(data,{
						'cb' : function(modalContainer) {
							thisInstance.registerAddUserCalendarViewActions(modalContainer);
						}
					});
				} else {
					app.helper.showErrorNotification({
						'message' : app.vtranslate('JS_NO_CALENDAR_VIEWS_TO_ADD')
					});
				}
			} else {
				console.log("network error : ",e);
			}
		});
	},

	showAddCalendarFeedEditor : function() {
		this.showAddUserCalendarView();
	},

	registerUserChangeEvent : function(modalContainer) {
		var thisInstance = this;
		var calendarFeedList = jQuery('#calendarview-feeds > ul.feedslist');
		modalContainer.find('select[name="usersList"]').on('change', 
		function() {
			var currentUserId = jQuery(this).val();
			var currentColor = thisInstance.getRandomColor();
			var feedCheckbox = calendarFeedList.find('input[data-calendar-userid="'+currentUserId+'"]');
			if(feedCheckbox.length) {
				currentColor = feedCheckbox.data('calendarFeedColor');
			}
			modalContainer.find('.selectedColor').val(currentColor);
			modalContainer.find('.calendarColorPicker').ColorPickerSetColor(currentColor);
		});
	},

	saveFeedSettings : function(modalContainer) {
		var thisInstance = this;
		var selectedType = modalContainer.find('.selectedType');
		var selectedUserId = selectedType.val();
		var selectedUserName = selectedType.data('typename');
		var calendarGroup = selectedType.data('calendarGroup');
		var selectedColor = modalContainer.find('.selectedColor').val();
		var editorMode = modalContainer.find('.editorMode').val();

		var params = {
			module: 'Calendar',
			action: 'CalendarUserActions',
			mode : 'addUserCalendar',
			selectedUser : selectedUserId,
			selectedColor : selectedColor
		};

		app.helper.showProgress();
		app.request.post({'data':params}).then(function(e) {
			if(!e) {
				var calendarFeedList = jQuery('#calendarview-feeds > ul.feedslist');
				var message = app.vtranslate('JS_CALENDAR_VIEW_COLOR_UPDATED_SUCCESSFULLY');
				if(editorMode === 'create') {
					var feedIndicatorTemplate = jQuery('#calendarview-feeds').find('ul.dummy > li.feed-indicator-template');
					feedIndicatorTemplate.removeClass('.feed-indicator-template');
					var newFeedIndicator = feedIndicatorTemplate.clone(true,true);
					newFeedIndicator.find('span:first').addClass('userName textOverflowEllipsis').text(selectedUserName).attr('title',selectedUserName);
					var newFeedCheckbox = newFeedIndicator.find('.toggleCalendarFeed');
					newFeedCheckbox.attr('data-calendar-sourcekey','Events_'+selectedUserId).
					attr('data-calendar-feed','Events').
					attr('data-calendar-fieldlabel',selectedUserName).
					attr('data-calendar-userid',selectedUserId).
					attr('data-calendar-group',calendarGroup).
					attr('checked','checked');
					calendarFeedList.append(newFeedIndicator);
					message = app.vtranslate('JS_CALENDAR_VIEW_ADDED_SUCCESSFULLY');
				}

				var contrast = app.helper.getColorContrast(selectedColor);
				var textColor = (contrast === 'dark') ? 'white' : 'black';
				var feedCheckbox = calendarFeedList.find('input[data-calendar-userid="'+selectedUserId+'"]');
				feedCheckbox.data('calendarFeedColor',selectedColor).
				data('calendarFeedTextcolor',textColor);
				var feedIndicator = feedCheckbox.closest('.calendar-feed-indicator');
				feedIndicator.css({'background-color':selectedColor,'color':textColor});
				thisInstance.refreshFeed(feedCheckbox);

				app.helper.hideProgress();
				app.helper.hideModal();
				app.helper.showSuccessNotification({'message':message});
			} else {
				console.log("error : ",e);
			}
		});

	},

	registerColorEditorSaveEvent : function(modalContainer) {
		var thisInstance = this;
		modalContainer.find('[name="saveButton"]').on('click', function() {
			jQuery(this).attr('disabled','disabled');
			var usersList = modalContainer.find('select[name="usersList"]');
			var selectedUser = usersList.find('option:selected');
			var selectedType = modalContainer.find('.selectedType');
			selectedType.val(usersList.val()).data(
				'typename',
				selectedUser.text()
			).data(
				'calendarGroup',
				selectedUser.data('calendarGroup')
			);
			thisInstance.saveFeedSettings(modalContainer);
		});        
	},

	registerColorEditorEvents : function(modalContainer,feedIndicator) {
		var thisInstance = this;
		var editorMode = modalContainer.find('.editorMode').val();

		var colorPickerHost = modalContainer.find('.calendarColorPicker');
		var selectedColor = modalContainer.find('.selectedColor');
		thisInstance.initializeColorPicker(colorPickerHost, {}, function(hsb, hex, rgb) {
			var selectedColorCode = '#'+hex;
			selectedColor.val(selectedColorCode);
		});

		thisInstance.registerUserChangeEvent(modalContainer);

		var usersList = modalContainer.find('select[name="usersList"]');
		if(editorMode === 'edit') {
			var feedCheckbox = feedIndicator.find('input[type="checkbox"].toggleCalendarFeed');
			usersList.select2('val',feedCheckbox.data('calendarUserid'));
		}
		usersList.trigger('change');

		thisInstance.registerColorEditorSaveEvent(modalContainer);
	},

	showColorEditor : function(feedIndicator) {
		var thisInstance = this;
		var params = {
			module : app.getModuleName(),
			view : 'UserCalendarViews',
			mode : 'editUserCalendar'
		};
		app.helper.showProgress();
		app.request.post({'data':params}).then(function(e,data) {
			app.helper.hideProgress();
			if(!e) {
				app.helper.showModal(data,{
					'cb' : function(modalContainer) {
						thisInstance.registerColorEditorEvents(modalContainer,feedIndicator);
					}
				});
			} else {
				console.log("network error : ",e);
			}
		});
	},

	getFeedDeleteParameters : function(feedCheckbox) {
		return {
			module: 'Calendar',
			action: 'CalendarUserActions',
			mode : 'deleteUserCalendar',
			userid : feedCheckbox.data('calendarUserid')
		};
	},

	/* get user list by selected role or group */
	getUserList : function(id, target) {
		var thisInstance = Calendar_SharedCalendar_Js.currentInstance;
		var aDeferred = jQuery.Deferred();

		if(!id) {
			//default is "My group".
			target = 'Calendar';
		}

		var params = {
			module: 'Calendar',
			action: 'CalendarUserActions',
			mode: 'getUserList',
			id : id,
			target : target
		};

		AppConnector.request(params).then(function(response) {
			var result = response['result'];
			thisInstance.userList = result;
			aDeferred.resolve();
		},
		function(error){
			aDeferred.reject();
		});

		return aDeferred.promise();
	},

	/* change user list event */
	changeUserList : function(callback) {
		var thisInstance = Calendar_SharedCalendar_Js.currentInstance;
		var id = jQuery("#calendar-groups").val();
		var target = "Calendar";

		if(/^[0-9]+$/.test(id)) {// numbers only is group id.
			target = "Groups";
			jQuery(".calendar-sidebar-tab > .sidebar-widget-header > .sidebar-header").parent().hide();
		} else if (id != "default") {
			target = "Roles";
			jQuery(".calendar-sidebar-tab > .sidebar-widget-header > .sidebar-header").parent().hide();
		} else {
			jQuery(".calendar-sidebar-tab > .sidebar-widget-header > .sidebar-header").parent().show();
		}

		var promise = Calendar_SharedCalendar_Js.currentInstance.getUserList(id, target).then(function(){
			var $area = $(".list-group.feedslist");
			var myId = null;
			$area.children().each(function(){
				var eventsTargets = jQuery(this).find('input[type="checkbox"].toggleCalendarFeed:not(.toggleSharedTodo)');
				var todoTargets = jQuery(this).find('input[type="checkbox"].toggleCalendarFeed.toggleSharedTodo');
				if($(this).is(".mine")) {
					myId = eventsTargets.attr("data-calendar-userid");
					// 自分以外のユーザーの活動を移動した場合に、自分の予定が変更されないためrefreshFeedを実行する
					thisInstance.refreshFeed($(".activitytype-indicator.calendar-feed-indicator.mine").find("input[type='checkbox']"));
				} else {
					// thisInstance.disableFeed(sourceKey);
					thisInstance.removeEvents(eventsTargets);
					if (todoTargets.length) {
						thisInstance.removeEvents(todoTargets);
					}
					$(this).remove();
				}
			})

			var users = thisInstance.userList['users'];
			var sharedInfo = thisInstance.userList['sharedinfo'] ? thisInstance.userList['sharedinfo'] : {};
			var cashDisabledFeedsStorageKey = thisInstance.getDisabledFeeds();

			Object.keys(users).forEach(function (id) {
				var user = app.getDecodedValue(users[id]);
				if(id == myId) {
					thisInstance.refreshFeed($(".activitytype-indicator.calendar-feed-indicator.mine").find("input[type='checkbox']"));
					return ;//continue
				}
				var color = sharedInfo[id] ? sharedInfo[id]['color'] : "";
				var visible = sharedInfo[id] ? sharedInfo[id]['visible'] : "0";
				if(target != 'Calendar' || visible == 1 || !sharedInfo[id]) {
					var feedIndicatorTemplate = jQuery('#calendarview-feeds').find('ul.dummy > li.feed-indicator-template');
					feedIndicatorTemplate.removeClass('.feed-indicator-template');
					var newFeedIndicator = feedIndicatorTemplate.clone(true,true);
					newFeedIndicator.find('span:first').addClass('userName textOverflowEllipsis').text(user).attr('title',user);
					var newFeedCheckbox = newFeedIndicator.find('input[type="checkbox"].toggleCalendarFeed:not(.toggleSharedTodo)');
					newFeedCheckbox.attr('data-calendar-sourcekey','Events_'+id).
					attr('data-calendar-feed','Events').
					attr('data-calendar-fieldlabel',user).
					attr('data-calendar-userid',id).
					attr('data-calendar-group',"").
					attr('checked','checked');
					$area.append(newFeedIndicator);

					var contrast = app.helper.getColorContrast(color);
					var textColor = (contrast === 'dark') ? 'white' : 'black';
					newFeedIndicator.css("background-color", color)
						.css("color", textColor);
					newFeedCheckbox.data('calendarFeedColor',color)
						.data('calendarFeedTextcolor',textColor);

					thisInstance.colorizeFeed(newFeedCheckbox);

					if(cashDisabledFeedsStorageKey.indexOf('Events_'+id) !== -1){
						// thisInstance.disableFeed('Events_'+id);
						newFeedCheckbox.removeAttr('checked');
					}else{
						// thisInstance.enableFeed('Events_'+id);
					}
					thisInstance.addEvents(newFeedCheckbox);

					var todoFeedCheckbox = newFeedIndicator.find('input[type="checkbox"].toggleCalendarFeed.toggleSharedTodo');
					if(todoFeedCheckbox.length && jQuery('input[type="checkbox"].toggleTodoFeed').bootstrapSwitch('state')) {
						todoFeedCheckbox.attr('data-calendar-sourcekey','Calendar_'+id).
						attr('data-calendar-feed','Calendar').
						attr('data-calendar-fieldlabel',user).
						attr('data-calendar-fieldname','date_start,due_date').
						attr('data-calendar-type','Calendar_'+id).
						attr('data-calendar-userid',id).
						attr('data-calendar-group',"").
						attr('checked','checked');

						todoFeedCheckbox.data('calendarFeedColor',color)
							.data('calendarFeedTextcolor',textColor);

						thisInstance.addEvents(todoFeedCheckbox);
					}


					if(target != "Calendar") {
						newFeedIndicator.find("i").remove();
					}
				}
			});
			thisInstance.restoreFeedsState($("#module-filters"));
		});
		if(callback) {
			promise.then(callback);
		}
	},

	setGroupSelectEnable : function() {
		var elem = jQuery('#calendar-groups');
		elem.select2().prop('disabled',false);
		elem.prev().css("display", "block");
	},
	setGroupSelectDisable : function() {
		var elem = jQuery('#calendar-groups');
		elem.select2().prop('disabled',true);
		elem.prev().css("display", "block");
	},

	registerGroupChangeEvent : function() {
		var elem = jQuery('#calendar-groups');
		elem.on('change', this.changeUserList);
		app.showSelect2ElementView(elem);
		elem.prev().css("display", "block");
	},

	initializeCalendar : function() {
		var calendarConfigs = this.getCalendarConfigs();
		this.getCalendarViewContainer().fullCalendar(calendarConfigs);
		this.performPostRenderCustomizations();
	},

	registerEvents : function() {
		this._super();
		Calendar_SharedCalendar_Js.currentInstance = this;
	}
});