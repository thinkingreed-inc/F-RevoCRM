/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Vtiger_Detail_Js("Project_Detail_Js",{
    
	gantt: false,
    
	showEditColorModel: function (url, e) {
		var element = jQuery(e);
		app.helper.showProgress();
		app.request.post({url: url}).then(function(error, data) {
			if (data) {
				app.helper.hideProgress();
				var callback = function (data) {
					Project_Detail_Js.registerEditColorPreSaveEvent(data, element);
                    var form = jQuery('#editColor');
                    var params = {
                        submitHandler: function(form) {
                            Project_Detail_Js.saveColor(jQuery(form));
                        }
                    };
                    form.vtValidate(params);
				}
                app.helper.showModal(data, {cb: callback});
			}
		});
	},

	registerEditColorPreSaveEvent: function (data, element) {
		var selectedColorField = data.find('.selectedColor');
		var color = element.data('color');

		if (color) {
			selectedColorField.val(color);
			var customParams = {
				color: color
			};
		} else {
			//if color is not present select random color
			var randomColor = '#' + (0x1000000 + (Math.random()) * 0xffffff).toString(16).substr(1, 6);
			selectedColorField.val(randomColor);
			//color picker params for add calendar view
			var customParams = {
				color: randomColor
			};
		}

		//register color picker
		var params = {
			flat: true,
			onChange: function (hsb, hex, rgb) {
				var selectedColor = '#' + hex;
				selectedColorField.val(selectedColor);
			}
		};

		if (typeof customParams != 'undefined') {
			params = jQuery.extend(params, customParams);
		}

		data.find('.colorPicker').ColorPicker(params);

		//on change of status, update color picker with the status color
		var selectElement = data.find('[name=taskstatus]');
		selectElement.on('change', function () {
			var selectedOption = selectElement.find('option:selected');
			var color = selectedOption.data('color');
			selectedColorField.val(color);
			data.find('.colorPicker').ColorPickerSetColor(color);
		});
	},

	saveColor: function (form) {
		var color = form.find('.selectedColor').val();
		var status = form.find('[name=taskstatus]').val();

		app.helper.showProgress();
		var params = {
			'module': app.getModuleName(),
			'action': 'SaveAjax',
			'mode': 'saveColor',
			'color': color,
			'status': status
		}
		app.request.post({data: params}).then(
			function(error, data) {
				app.helper.hideProgress();
				app.helper.hideModal();
				if (!error) {
                    app.helper.showSuccessNotification({message: app.vtranslate('JS_COLOR_SAVED_SUCESSFULLY')});
					// to reload chart
					jQuery('[data-label-key=Chart]').click();
				} else {
                    app.helper.showErrorNotification({message: error});
				}
			}
		);

	}
}, {

	detailViewRecentTicketsTabLabel : 'HelpDesk',
	detailViewRecentTasksTabLabel : 'Project Tasks',
	detailViewRecentMileStonesLabel : 'Project Milestones',
	
	/**
	 * Function to register event for create related record
	 * in summary view widgets
	 */
	registerSummaryViewContainerEvents : function(summaryViewContainer){
		this._super(summaryViewContainer);
		this.registerStatusChangeEventForWidget();
		this.registerEventsForTasksWidget(summaryViewContainer);
	},
	
	/**
	* Function to get records according to ticket status
	*/
	registerStatusChangeEventForWidget : function(){
		var thisInstance = this;
		jQuery('[name="ticketstatus"],[name="projecttaskstatus"],[name="projecttaskprogress"]').on('change',function(e){
            var picklistName = this.name;
			var statusCondition = {};
			var params = {};
			var currentElement = jQuery(e.currentTarget);
			var summaryWidgetContainer = currentElement.closest('.summaryWidgetContainer');
			var widgetDataContainer = summaryWidgetContainer.find('.widget_contents');
			var referenceModuleName = widgetDataContainer.find('[name="relatedModule"]').val();
			var recordId = thisInstance.getRecordId();
			var module = app.getModuleName();
			var selectedStatus = currentElement.find('option:selected').val();
			if(selectedStatus.length > 0 && referenceModuleName == "HelpDesk"){
                var searchInfo = new Array();
                searchInfo.push('ticketstatus');
                searchInfo.push('e');
                searchInfo.push(selectedStatus);
                statusCondition['ticketstatus'] = searchInfo;
				params['whereCondition'] = JSON.stringify(statusCondition);
			} else if(referenceModuleName == "ProjectTask" && picklistName == 'projecttaskstatus'){
				if(selectedStatus.length > 0) {
                    var searchInfo = new Array();
                    searchInfo.push('projecttaskstatus');
                    searchInfo.push('e');
                    searchInfo.push(selectedStatus);
                    statusCondition['projecttaskstatus'] = searchInfo;
					params['whereCondition'] = JSON.stringify(statusCondition);
				}
				jQuery('[name="projecttaskprogress"]').val('').select2("val", '');
			}
            else if(referenceModuleName == "ProjectTask" && picklistName == 'projecttaskprogress'){
				if(selectedStatus.length > 0) {
                    var searchInfo = new Array();
                    searchInfo.push('projecttaskprogress');
                    searchInfo.push('e');
                    searchInfo.push(selectedStatus);
                    statusCondition['projecttaskprogress'] = searchInfo;
					params['whereCondition'] = JSON.stringify(statusCondition);
				}
				jQuery('[name="projecttaskstatus"]').val('').select2("val", '');
			}
			
			params['record'] = recordId;
			params['view'] = 'Detail';
			params['module'] = module;
			params['page'] = widgetDataContainer.find('[name="page"]').val();
			params['limit'] = widgetDataContainer.find('[name="pageLimit"]').val();
			params['relatedModule'] = referenceModuleName;
			params['mode'] = 'showRelatedRecords';
			app.helper.showProgress();
			app.request.post({data: params}).then(
				function(error, data) {
                    app.helper.hideProgress();
					widgetDataContainer.html(data);
				}
			);
	   })
	},
	
	/**
	 * Function to load module summary of Projects
	 */
	loadModuleSummary : function(){
		var summaryParams = {};
		summaryParams['module'] = app.getModuleName();
		summaryParams['view'] = "Detail";
		summaryParams['mode'] = "showModuleSummaryView";
		summaryParams['record'] = jQuery('#recordId').val();
		
		app.request.post({data: summaryParams}).then(
			function(error, data) {
				jQuery('.summaryView').html(data);
			}
		);
	},
    
    /**
     * Function to load the gantt chart
     */
    loadGanttChart : function(container) {
        var gantt;
        //load templates
        jQuery("#ganttemplates").loadTemplates();

        // here starts gantt initialization
        gantt = new GanttMaster();
        var workSpace = $("#workSpace");
        workSpace.css({height:$("#workSpace").parent().height() - 20});
        gantt.init(workSpace);

        var ret;
        ret = JSON.parse($("#projecttasks").val());

        gantt.loadProject(ret);
        gantt.checkpoint(); //empty the undo stack

        $(window).resize(function(){
          workSpace.trigger("resize.gantt");
        })
        jQuery('.toggleButton').click(function() {
          workSpace.trigger("resize.gantt");
        });

		Project_Detail_Js.gantt = gantt;

		// Added to make default sortorder of startdate to be ascending
		var element = jQuery('.gdfTable.fixHead').find('.gdfColHeader[data-name=startdate]');
		element.data('nextorder', 'asc');
		element.trigger('click');
    },
    
    loadContents : function(url,data) {
        var detailContentsHolder = this.getContentHolder();
        var thisInstance = this;
        var aDeferred = jQuery.Deferred();
        if(url.indexOf('index.php') < 0) {
            url = 'index.php?' + url;
        }
        var params = [];
        params.url = url;
        if(typeof data != 'undefined'){
            params.data = data;
        }
        app.helper.showProgress();
        app.request.pjax(params).then(function(error,response){
            detailContentsHolder.html(response);
            aDeferred.resolve(response);
            app.helper.hideProgress();
            if(detailContentsHolder.find('#workSpace').length !=0) {
                thisInstance.loadGanttChart(detailContentsHolder);
            }
        });
        return aDeferred.promise();
	},
	
	/**
	 * Function to register events for project tasks widget
	 */
	registerEventsForTasksWidget : function(summaryViewContainer) {
		var thisInstance = this;
		var tasksWidget = summaryViewContainer.find('.widgetContainer_tasks');
		tasksWidget.on('click', '.editTaskDetails', function(e) {
			var currentTarget = jQuery(e.currentTarget);
			var newValue = currentTarget.text();
			var element = currentTarget.closest('ul.dropdown-menu');
			var editElement = element.closest('.dropdown');
			var oldValue = element.data('oldValue');
			if(currentTarget.hasClass('emptyOption')) {
				newValue = '';
			}
            vtUtils.hideValidationMessage(editElement);
			if(element.data('mandatory') && newValue.length <= 0) {
				var result = app.vtranslate('JS_REQUIRED_FIELD');
                vtUtils.showValidationMessage(editElement, result);
				return false;
			}
			if(oldValue != newValue) {
				var params = {
					action : 'SaveAjax',
					record : element.data('recordid'),
					field : element.data('fieldname'),
					value : newValue,
					module : 'ProjectTask'
				};
				app.helper.showProgress();
				app.request.post({data: params}).then(
					function(error, data) {
                        app.helper.hideProgress();
						thisInstance.showRelatedRecords(tasksWidget);
					}
				);
			}
		})
	},
	
	/**
	 * Function to get the related records list
	 * summary view widget
	 */
	showRelatedRecords : function(summaryWidgetContainer) {
		var widgetHeaderContainer = summaryWidgetContainer.find('.widget_header');
		var widgetDataContainer = summaryWidgetContainer.find('.widget_contents');
		var referenceModuleName = widgetHeaderContainer.find('[name="relatedModule"]').val();
		var module = app.getModuleName();
		var params = {};
			
		if(referenceModuleName == 'ProjectTask') {
			var statusCondition = {};
			var selectedStatus = jQuery('[name="projecttaskstatus"]', widgetHeaderContainer).val();
			if(typeof selectedStatus != "undefined" && selectedStatus.length > 0) {
				statusCondition['vtiger_projecttask.projecttaskstatus'] = selectedStatus;
				params['whereCondition'] = statusCondition;
			}
			var selectedProgress = jQuery('[name="projecttaskprogress"]', widgetHeaderContainer).val();
			if(typeof selectedProgress != "undefined" && selectedProgress.length > 0) {
				statusCondition['vtiger_projecttask.projecttaskprogress'] = selectedProgress;
				params['whereCondition'] = statusCondition;
			}
		}

		params['record'] = this.getRecordId();
		params['view'] = 'Detail';
		params['module'] = module;
		params['page'] = widgetDataContainer.find('[name="page"]').val();
		params['limit'] = widgetDataContainer.find('[name="pageLimit"]').val();
		params['relatedModule'] = referenceModuleName;
		params['mode'] = 'showRelatedRecords';
        
        app.helper.showProgress();
		app.request.post({data: params}).then(
			function(error, data) {
                app.helper.hideProgress();
				widgetDataContainer.html(data);
			}
		);
	},
    
    registerGanttChartEvents : function(container) {
        this.registerZoomButtons(container);
        this.registerTaskEdit(container);
        this.registerRecordUpdateEvent(container);
		this.registerGanttSorting(container);
    },
    
    registerZoomButtons : function(container) {
        
        container.on('click', '.zoomIn', function(e){
            e.preventDefault();
            jQuery("#workSpace").trigger('zoomPlus.gantt');
        });
        
        container.on('click', '.zoomOut', function(e){
            e.preventDefault();
            jQuery("#workSpace").trigger('zoomMinus.gantt');
        });
    },
    
    registerTaskEdit : function (container) {
        var thisInstance = this;
        container.on('click', '.editTask', function(e) {
            var element = jQuery(e.currentTarget);
            var params = {
                'module' : 'ProjectTask',
                'view'   : 'QuickEditAjax',
                'returnview' : 'Detail',
                'returnmode' : 'showChart',
                'returnmodule' : app.getModuleName(),
                'returnrecord' : thisInstance.getRecordId(),
                'parentid' : thisInstance.getRecordId(),
                'record' : element.data('recordid')
            }
            app.helper.showProgress();
            app.request.post({data: params}).then(
                function(error, data) {
                    app.helper.hideProgress();
                    var callBackFunction = function(data) {
                        var form = data.find('.recordEditView');
                        var params = {
                            submitHandler: function(form) {
                                form = jQuery(form);
                                if(form.attr('id') == 'projectTaskQuickEditForm') {
                                    app.helper.showProgress();
									 thisInstance.saveTask(form).then(function(err, data) {
                                        app.helper.hideProgress();
										if (err === null) {
											jQuery('.vt-notification').remove();
											app.helper.hideModal();
											// to reload chart
											jQuery('[data-label-key=Chart]').click();
										} else {
											app.event.trigger('post.save.failed', err);
										}
                                    });
                                }
                            },
                            validationMeta: quickcreate_uimeta
                        };
                        form.vtValidate(params);
                    }
                    var modalWindowParams = {
                        cb : callBackFunction
                    }
                    app.helper.showModal(data, modalWindowParams);
                }                              
            );
        }); 
    },
    
    registerRecordUpdateEvent : function(container) {
        container.on('updateTaskRecord.gantt','#workSpace', function(e,task) {
            var dateFormat = vtUtils.getMomentDateFormat();
            var startDate = moment(task.start).format(dateFormat);
            var endDate = moment(task.end).format(dateFormat);
            if((task.oldstart != '' && task.oldend != '') && (task.oldstart != startDate || task.oldend != endDate)) {
                var params = {
                    'module' : 'ProjectTask',
                    'action' : 'SaveTask',
                    'record' : task.recordid,
                    'startdate' : startDate,
                    'enddate' : endDate  
                }
                app.helper.showProgress();
                app.request.post({data: params}).then(
					function(error, data) {
                        app.helper.hideProgress();
						if (error === null) {
							jQuery('.vt-notification').remove();
						} else {
							app.event.trigger('post.save.failed', error);
						}
                    }
                );
            }
        });
    },
    
    sortResults: function(arr, prop, asc) {
        var thisInstance = this;
        arr = arr.sort(function(a, b) {
            if (asc) { 
                if (a[prop] === parseInt(a[prop], 10) && b[prop] === parseInt(b[prop], 10)) {
                    return a[prop]-b[prop];
                } else if( thisInstance.isDate(a[prop]) && thisInstance.isDate(b[prop])) {
                    return new Date(a[prop]).getTime() - new Date(b[prop]).getTime();
                } else {
                    return thisInstance.sortAlphabetically(a[prop], b[prop]);
                }
            } else {  
                if (a[prop] === parseInt(a[prop], 10) && b[prop] === parseInt(b[prop], 10)) {
                    return b[prop]-a[prop];
                } else if( thisInstance.isDate(a[prop]) && thisInstance.isDate(b[prop])) {
                    return new Date(b[prop]).getTime() - new Date(a[prop]).getTime();
                } else {                
                    return thisInstance.sortAlphabetically(b[prop], a[prop]);
                }
            }
        });
            
        return arr;
    },
    
    isDate: function(date) {
        return (new Date(date) !== "Invalid Date" && !isNaN(new Date(date)) ) ? true : false;
    },
    
    sortAlphabetically : function(a, b) {
        var nameA = a.toLowerCase();
        var nameB = b.toLowerCase()
        if (nameA < nameB) {
            return -1;
        }
        if (nameA > nameB) {
            return 1;
        }
        
        return 0;
    },
    
    registerGanttSorting : function(container) { 
        var thisInstance = this;
        container.on('click', '.gdfColHeader', function(e) {
            var element = jQuery(e.currentTarget);
            var text = element.data('text');
            var name = element.data('name');
            var order = element.data('nextorder');
            if(name) {
                container.find('.gdfColHeader .fa.fa-chevron-down').remove();
                container.find('.gdfColHeader .fa.fa-chevron-up').remove();
                var descTemplate = '<i class="fa fa-chevron-down"></i> ' + text;
                var ascTemplate = '<i class="fa fa-chevron-up"></i>' + text ;
                if(!order) {
                    order = false;  
                    element.html(descTemplate);
                } else if(order == 'asc') {
                    order = true;
                    element.html(ascTemplate);
                } else if(order == 'desc') {
                    order = false;
                    element.html(descTemplate);
                }
            
                var data = JSON.parse($("#projecttasks").val());
                data.tasks = thisInstance.sortResults(data.tasks, name, order);
                if(order == false) {
                    order = 'asc';
                } else {
                    order = 'desc';
                }
                element.data('nextorder', order);
                var gantt = Project_Detail_Js.gantt;
                gantt.loadProject(data);
                gantt.checkpoint(); //empty the undo stack
            }
        });
    },
    
    saveTask : function(form) {
        var aDeferred = jQuery.Deferred();
        var formData = form.serializeFormData();
        app.request.post({data: formData}).then(
                function(error, data) {
                    //TODO: App Message should be shown
                    aDeferred.resolve(error, data);
                },
                function(textStatus, errorThrown) {
                    aDeferred.reject(textStatus, errorThrown);
                }
        );
        return aDeferred.promise();
    },
	
	registerEvents : function(){
		var detailContentsHolder = this.getContentHolder();
		var thisInstance = this;
		this._super();
		
		detailContentsHolder.on('click','.moreRecentMilestones', function(){
			var recentMilestonesTab = thisInstance.getTabByLabel(thisInstance.detailViewRecentMileStonesLabel);
			recentMilestonesTab.trigger('click');
		});
		
		detailContentsHolder.on('click','.moreRecentTickets', function(){
			var recentTicketsTab = thisInstance.getTabByLabel(thisInstance.detailViewRecentTicketsTabLabel);
			recentTicketsTab.trigger('click');
		});
		
		detailContentsHolder.on('click','.moreRecentTasks', function(){
			var recentTasksTab = thisInstance.getTabByLabel(thisInstance.detailViewRecentTasksTabLabel);
			recentTasksTab.trigger('click');
		});
        
        var detailViewContainer = jQuery('.detailViewContainer');
        thisInstance.registerGanttChartEvents(detailViewContainer);
        if(detailViewContainer.find('#workSpace').length != 0) {
            this.loadGanttChart(detailViewContainer);
        }
	}
})