/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

Settings_Vtiger_List_Js("Settings_CronTasks_List_Js",{
   
	triggerEditEvent: function(editUrl) {

        app.request.post({"url":editUrl}).then(function(err, data) {
			if(data) {
                app.helper.showModal(data);
            }
           var listViewInstance = Settings_CronTasks_List_Js.getInstance();
            listViewInstance.registerSaveEvent();
            listViewInstance.registerScheduleTypeEvent();
            listViewInstance.registerFrequencyAlignmentEvent();
            // モーダルは読み込み完了後に差し込まれるため、ツールチップはここで有効化する
            vtUtils.enableTooltips();
		});
	},

	/**
	 * 直前の実行ログをモーダルで表示する
	 */
	triggerLogEvent: function(logUrl) {
		app.request.post({"url":logUrl}).then(function(err, data) {
			if(data) {
				app.helper.showModal(data);
			}
		});
	}
},{

	/**
	 * 実行タイミングの種別に応じて入力欄を切り替える
	 * （周期 / 毎日 / 毎週＋曜日 / 毎月＋日）
	 */
	registerScheduleTypeEvent : function() {
		var thisInstance = this;
		var scheduleType = jQuery('#schedule_type');
		if (scheduleType.length === 0) {
			return;
		}
		scheduleType.on('change', function() {
			var type = jQuery(this).val();
			jQuery('.scheduleIntervalRow').toggle(type === 'interval');
			jQuery('.scheduleWeeklyRow').toggle(type === 'weekly');
			jQuery('.scheduleMonthlyRow').toggle(type === 'monthly');
			// 時刻は毎日・毎週・毎月に共通
			jQuery('.scheduleTimeRow').toggle(type !== 'interval');
			if (type === 'interval') {
				thisInstance.updateFrequencyAlignmentWarning();
			}
		});
	},

	/**
	 * 入力した周期が 1 日を割り切れるかを確かめ、割り切れない場合に注意を出す。
	 *
	 * 割り切れる周期は実行予定時刻が固定のグリッドに乗るため、実行が遅れても
	 * 次回以降へずれが持ち越されない。割り切れない周期は前回からの相対になる。
	 */
	registerFrequencyAlignmentEvent : function() {
		var thisInstance = this;
		jQuery('#frequencyValue').on('keyup change', function() {
			thisInstance.updateFrequencyAlignmentWarning();
		});
		jQuery('#time_format').on('change', function() {
			thisInstance.updateFrequencyAlignmentWarning();
		});
		thisInstance.updateFrequencyAlignmentWarning();
	},

	updateFrequencyAlignmentWarning : function() {
		var warning = jQuery('#frequencyNotAlignedWarning');
		if (warning.length === 0) {
			return;
		}
		var frequency = this.getFrequencyValue();
		var aligned = frequency > 0 && frequency <= 86400 && (86400 % frequency === 0);
		warning.toggle(frequency > 0 && !aligned);
	},

	/**
	 * 周期の入力欄から秒数を組み立てる
	 */
	getFrequencyValue : function() {
		var frequencyValue = parseInt(jQuery('#frequencyValue').val(), 10);
		if (isNaN(frequencyValue)) {
			return 0;
		}
		frequencyValue = frequencyValue * 60;
		if (jQuery('#time_format').val() === 'hours') {
			frequencyValue = frequencyValue * 60;
		}
		return frequencyValue;
	},

    registerSaveEvent : function() {
		var thisInstance = this;
        jQuery('#cronJobSaveAjax').on('submit',function(e){
            e.preventDefault();
            var form = jQuery(e.currentTarget);

			var scheduleType = jQuery('#schedule_type').val();
			if (scheduleType !== 'interval') {
				// 決まった時刻に実行する。時刻は 0 時からの経過分で渡す
				var hour = parseInt(jQuery('#runAtHour').val(), 10) || 0;
				var minute = parseInt(jQuery('#runAtMinute').val(), 10) || 0;
				jQuery('#run_at_minutes').val((hour * 60) + minute);

				// 曜日・日は該当する種別のときだけ送る。
				// 曜日は複数選択できるのでカンマ区切りにまとめる。
				var weekdays = '';
				if (scheduleType === 'weekly') {
					var selected = [];
					jQuery('.runOnWeekdayCheckbox:checked').each(function() {
						selected.push(jQuery(this).val());
					});
					weekdays = selected.join(',');
					if (weekdays === '') {
						var weekdayElement = jQuery('.runOnWeekdayCheckbox').first();
						vtUtils.showValidationMessage(weekdayElement,
								app.vtranslate('JS_PLEASE_SELECT_AT_LEAST_ONE_WEEKDAY'), {
							position: {
								my: 'bottom left',
								at: 'top left',
								container: weekdayElement.closest('.form-group')
							}
						});
						e.preventDefault();
						return;
					}
				}
				jQuery('#run_on_weekdays').val(weekdays);
				jQuery('#run_on_day').val(
						scheduleType === 'monthly' ? jQuery('#runOnDay').val() : '');

				// 周期そのものは使わないが、表示や移行前データのために目安の値を送る
				if (scheduleType === 'weekly') {
					jQuery('#frequency').val(7 * 24 * 60 * 60);
				} else if (scheduleType === 'monthly') {
					jQuery('#frequency').val(30 * 24 * 60 * 60);
				} else {
					jQuery('#frequency').val(24 * 60 * 60);
				}
			} else {
				jQuery('#run_at_minutes').val('');
				jQuery('#run_on_weekdays').val('');
				jQuery('#run_on_day').val('');

				var frequencyElement = jQuery('#frequencyValue');
				var frequencyValue = thisInstance.getFrequencyValue();
				var minimumFrequency = jQuery('#minimumFrequency').val();
				if (frequencyValue < minimumFrequency) {
					var message = app.vtranslate('JS_VALUE_SHOULD_NOT_BE_LESS_THAN');
					var minutes = app.vtranslate('JS_MINUTES');
					vtUtils.showValidationMessage(frequencyElement, message+' '+(minimumFrequency / 60)+' '+minutes, {
						position: {
							my: 'bottom left',
							at: 'top left',
							container: frequencyElement.closest('.form-group')
						}
					});
					e.preventDefault();
					return;
				}
				jQuery('#frequency').val(frequencyValue);
			}

			// タイムアウトは空欄なら 0（= config.inc.php の既定値に従う）
			var retryTimeoutElement = jQuery('#retryTimeoutValue');
			if (retryTimeoutElement.length > 0) {
				var retryTimeout = parseInt(retryTimeoutElement.val(), 10);
				if (isNaN(retryTimeout) || retryTimeout < 0) {
					retryTimeout = 0;
				}
				jQuery('#retry_timeout').val(retryTimeout * 60);
			}

			// 実行ログの保持世代数。空欄は「既定値に従う」として空で送る
			var logRetentionElement = jQuery('#logRetentionValue');
			if (logRetentionElement.length > 0) {
				var logRetention = jQuery.trim(logRetentionElement.val());
				if (logRetention === '') {
					jQuery('#log_retention_count').val('');
				} else {
					var parsedRetention = parseInt(logRetention, 10);
					jQuery('#log_retention_count').val(
							(isNaN(parsedRetention) || parsedRetention < 0) ? '' : parsedRetention);
				}
			}

			app.helper.showProgress();
			app.helper.hideModal();
            var params = form.serializeFormData();

            app.request.post({"data":params}).then(function(err,data){

                if(err === null) {
                    app.helper.hideProgress();
                    thisInstance.loadListViewRecords();
                }else{
                    app.helper.showErrorNotification({'message':err.message});
                }
            });
			e.preventDefault();
		});
	},
    
    loadListViewRecords : function(urlParams) {
        var thisInstance = this;
        var aDeferred = jQuery.Deferred();
        var defParams = this.getDefaultParams();
        if(typeof urlParams === "undefined") {
            urlParams = {};
        }
        if(typeof urlParams.search_params === "undefined") {
            urlParams.search_params = JSON.stringify(thisInstance.getListSearchParams(false));
        }
        urlParams = jQuery.extend(defParams, urlParams);
        app.helper.showProgress();
		
        app.request.get({data:urlParams}).then(function(err, res){
            aDeferred.resolve(res);
            var container = thisInstance.getListViewContainer();
			container.html(res);
            thisInstance.registerSortableEvent(); 
            app.helper.hideProgress();
            app.event.trigger('post.listViewFilter.click');
		});
        return aDeferred.promise();
    },
    
    
    registerSortableEvent : function() {
		var thisInstance = this;
		var sequenceList = {};
		var tbody = jQuery('tbody');
		
		tbody.sortable({
			// 行のどこでも掴めると、閲覧しようとしただけで並べ替えてしまう。
			// 左端のハンドルからだけドラッグできるようにする。
			'handle' : '.listViewDragHandle',
			'cancel' : 'a, i, input, select, button',
			'helper' : function(e,ui){
				//while dragging helper elements td element will take width as contents width
				//so we are explicity saying that it has to be same width so that element will not
				//look like distrubed
				ui.children().each(function(index,element){
					element = jQuery(element);
					element.width(element.width());
				});
                return ui;
			},
			'containment' : tbody,
			'revert' : true,
			update: function(e, ui ) {
				jQuery('tbody tr').each(function(i){
					sequenceList[++i] = jQuery(this).data('id');
                    
				});
				var params = {
					sequencesList : JSON.stringify(sequenceList),
					module : app.getModuleName(),
					parent : app.getParentModuleName(),
					action : 'UpdateSequence'
				};
				app.request.post({"data":params}).then(function(err,data) {
                    if(err === null){
						thisInstance.loadListViewRecords(); 
                    }
				});
			}
		});
	},

	registerEvents : function() {
		this.registerSortableEvent();
		this.registerPostListLoadListener();
	}
});