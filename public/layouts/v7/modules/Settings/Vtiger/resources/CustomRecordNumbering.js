/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Vtiger.Class('Settings_Vtiger_CustomRecordNumbering_Js', {}, {
	
	form : false,
	getForm : function(){
		if(this.form === false){
			this.form = jQuery('#EditView');
		}
        return this.form;
	},
	
    
	init : function() {
       this.addComponents();
	},
   
	addComponents : function (){
	   this.addComponent('Vtiger_Index_Js');
	},
	/**
	 * モジュール・項目を切り替えたら、その設定の画面を開き直す
	 */
	registerOnChangeEventOfSourceModule : function(){
		var editViewForm = this.getForm();
		var reload = function(sourceModule, field){
			var url = 'index.php?module=' + app.getModuleName() + '&parent=' + app.getParentModuleName() + '&view=CustomRecordNumbering&sourceModule=' + encodeURIComponent(sourceModule);
			if (field) {
				url += '&field=' + encodeURIComponent(field);
			}
			window.location.href = url;
		};
		editViewForm.find('[name="sourceModule"]').on('change', function(){
			reload(editViewForm.find('[name="sourceModule"]').val(), '');
		});
		editViewForm.find('[name="field"]').on('change', function(){
			reload(editViewForm.find('[name="sourceModule"]').val(), editViewForm.find('[name="field"]').val());
		});
	},

	/**
	 * 自動採番の設定を保存する
	 */
	saveModuleCustomNumbering : function(form){
		var params = {
			'module' : app.getModuleName(),
			'parent' : app.getParentModuleName(),
			'action' : "CustomRecordNumberingAjax",
			'mode' : "saveModuleCustomNumberingData",
			'sourceModule' : form.find('[name="sourceModule"]').val(),
			'field' : form.find('[name="field"]').val(),
			'format' : jQuery.trim(form.find('[name="format"]').val()),
			'reset_type' : form.find('[name="reset_type"]').val(),
			'start_number' : jQuery.trim(form.find('[name="start_number"]').val()),
			'base_date' : form.find('[name="base_date"]').val(),
			'effective_from' : jQuery.trim(form.find('[name="effective_from"]').val()),
			'active' : form.find('[name="active"]:checkbox').is(':checked') ? '1' : '0'
		};
		var sourceModuleLabel = form.find('[name="sourceModule"] option:selected').text();

		jQuery('.saveButton').attr("disabled","disabled");
		app.request.post({"data":params}).then(
			function(err, data){
				if(err === null){
					app.helper.showSuccessNotification({'message':app.vtranslate('JS_RECORD_NUMBERING_SAVED_SUCCESSFULLY_FOR')+" "+jQuery.trim(sourceModuleLabel)});
				}else{
					jQuery('.saveButton').removeAttr('disabled');
					app.helper.showErrorNotification({'message':err.message});
				}
			});
	},

	/**
	 * 自動採番項目の遡及採番（値が空の既存レコードに後から番号を付ける）
	 *
	 * 処理の本体はサーバー側のジョブ。この画面は進み具合を読み（status）、開いている間は kick で
	 * 少しずつ進めて速くする。画面を閉じても、定期ジョブ（cron）が進め続ける。
	 */
	registerAutoNumberBackfillEvents : function(){
		var panel = this.getForm().find('.autoNumberBackfill');
		if (panel.length === 0) {
			return;
		}
		var moduleName = panel.data('module');
		var fieldName = panel.data('field');
		var timer = null;
		var kicking = false;

		var request = function (mode, extra, isWrite) {
			var data = jQuery.extend({module: moduleName, api: 'AutoNumber', mode: mode, field: fieldName}, extra || {});
			if (isWrite && typeof csrfMagicName !== 'undefined' && typeof csrfMagicToken !== 'undefined') {
				data[csrfMagicName] = csrfMagicToken;
			}
			return jQuery.ajax({url: 'index.php', type: isWrite ? 'POST' : 'GET', data: data, dataType: 'json'});
		};
		var showError = function (xhr) {
			var message = '';
			try {
				message = xhr.responseJSON.error.message;
			} catch (e) {
				message = '';
			}
			panel.find('.js-backfill-info').removeClass('hide');
			panel.find('.js-backfill-error').text(message);
		};
		var range = function () {
			return {created_from: panel.find('.js-backfill-from').val(), created_to: panel.find('.js-backfill-to').val()};
		};
		var format = function (text, values) {
			var index = 0;
			return text.replace(/%s/g, function () {
				return values[index++];
			});
		};
		var label = function (key) {
			return panel.data('label-' + key) || '';
		};

		var render = function (job) {
			var unfinished = job && job.unfinished;
			var running = unfinished && job.status !== 'paused';
			panel.find('.js-backfill-pause').toggleClass('hide', !running);
			panel.find('.js-backfill-resume').toggleClass('hide', !(unfinished && job.status === 'paused'));
			panel.find('.js-backfill-cancel').toggleClass('hide', !unfinished);
			panel.find('.js-backfill-preview').toggleClass('hide', !!unfinished);
			panel.find('.js-backfill-range').toggleClass('hide', !!unfinished);
			if (!unfinished) {
				panel.find('.js-backfill-start').toggleClass('hide', panel.find('.js-backfill-start').data('ready') !== true);
			} else {
				panel.find('.js-backfill-start').addClass('hide');
			}
			if (!job) {
				panel.find('.js-backfill-progress, .js-backfill-nocron').addClass('hide');
				return;
			}
			panel.find('.js-backfill-info').removeClass('hide');
			panel.find('.js-backfill-progress').removeClass('hide');
			panel.find('.js-backfill-nocron').toggleClass('hide', !unfinished);
			var percent = job.total > 0 ? Math.min(100, Math.round(job.processed * 100 / job.total)) : 100;
			panel.find('.js-backfill-bar').css('width', percent + '%');
			var text = job.status_label + ' ' + job.processed + ' / ' + job.total;
			if (job.failed > 0) {
				text += ' (' + job.failed + ' NG)';
			}
			if (job.last_number) {
				text += ' [' + job.last_number + ']';
			}
			panel.find('.js-backfill-status').text(text);
			panel.find('.js-backfill-error').text('');
		};

		var poll = function () {
			request('status').then(function (response) {
				var job = response.job;
				render(job);
				if (job && job.unfinished) {
					if (job.status !== 'paused' && !kicking) {
						// この画面を開いている間は、同じ処理を呼んで進みを速くする
						kicking = true;
						request('kick', {}, true).always(function () {
							kicking = false;
						});
					}
					timer = setTimeout(poll, 2000);
				} else {
					timer = null;
				}
			}, function () {
				timer = setTimeout(poll, 5000);
			});
		};
		var startPolling = function () {
			if (timer === null) {
				timer = setTimeout(poll, 0);
			}
		};

		panel.on('click', '.js-backfill-preview', function () {
			panel.find('.js-backfill-error').text('');
			request('preview', range()).then(function (response) {
				panel.find('.js-backfill-info').removeClass('hide');
				var text = format(label('targets'), [response.total]);
				if (response.total > 0) {
					text += ' / ' + format(label('range'), [response.first, response.last]);
				}
				panel.find('.js-backfill-preview-text').text(text);
				panel.find('.js-backfill-warning').toggleClass('hide', response.total === 0);
				panel.find('.js-backfill-start').data('ready', response.total > 0).toggleClass('hide', response.total === 0);
			}, showError);
		});
		panel.on('click', '.js-backfill-start', function () {
			request('start', range(), true).then(function (response) {
				panel.find('.js-backfill-start').data('ready', false);
				render(response.job);
				startPolling();
			}, showError);
		});
		panel.on('click', '.js-backfill-pause, .js-backfill-resume, .js-backfill-cancel', function (e) {
			var mode = jQuery(e.currentTarget).hasClass('js-backfill-pause') ? 'pause' : (jQuery(e.currentTarget).hasClass('js-backfill-resume') ? 'resume' : 'cancel');
			request(mode, {}, true).then(function (response) {
				render(response.job);
				if (mode !== 'cancel') {
					startPolling();
				}
			}, showError);
		});

		// 開いたときの状態（実行中のジョブがあれば進み具合を出す）
		request('status').then(function (response) {
			render(response.job);
			if (response.job && response.job.unfinished) {
				startPolling();
			}
		}, showError);
	},

	/**
	 * 設定の入力を変えたら保存できるようにする
	 */
	registerChangeEventForSettings : function() {
		this.getForm().find('[name="format"],[name="reset_type"],[name="start_number"],[name="base_date"],[name="effective_from"],[name="active"]:checkbox').on('change input',function(){
			jQuery('.saveButton').removeAttr('disabled');
		});
	},

	/**
	 * Function to register events
	 */
	registerEvents : function(){
		var thisInstance = this;
		var editViewForm = thisInstance.getForm();
		thisInstance.registerOnChangeEventOfSourceModule();
		thisInstance.registerChangeEventForSettings();
		thisInstance.registerAutoNumberBackfillEvents();

		editViewForm.on('submit', function(e){
			e.preventDefault();
		});

		var params = {
			submitHandler : function(form) {
				thisInstance.saveModuleCustomNumbering(jQuery(form));
			}
		};
		editViewForm.vtValidate(params);

		var instance = new Settings_Vtiger_Index_Js();
		instance.registerBasicSettingsEvents();
	}
});
