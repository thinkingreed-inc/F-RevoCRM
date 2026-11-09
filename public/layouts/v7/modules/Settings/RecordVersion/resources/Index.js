/**
 * 版管理の設定（システム設定 → モジュール管理 → 版管理）
 */
Settings_Vtiger_Index_Js('Settings_RecordVersion_Index_Js', {}, {

	getContainer: function () {
		return jQuery('#recordVersionSettings');
	},

	getMessage: function (key) {
		return this.getContainer().find('#recordVersionMessages').data(key) || '';
	},

	/**
	 * 設定を保存する API を呼ぶ（成功なら行の内容、失敗ならメッセージを返す）
	 */
	callSaveApi: function (params) {
		var deferred = jQuery.Deferred();
		var data = jQuery.extend({module: 'RecordVersion', parent: 'Settings', api: 'Save'}, params);
		if (typeof csrfMagicName !== 'undefined') {
			data[csrfMagicName] = csrfMagicToken;
		}
		jQuery.ajax({url: 'index.php', type: 'POST', data: data, dataType: 'json'}).done(function (response) {
			if (response && response.success === false) {
				deferred.reject(response.error && response.error.message);
				return;
			}
			deferred.resolve(response);
		}).fail(function (xhr) {
			var message = '';
			if (xhr.responseJSON && xhr.responseJSON.error) {
				message = xhr.responseJSON.error.message;
			}
			deferred.reject(message);
		});
		return deferred.promise();
	},

	updateRow: function (row, data) {
		var enabled = data.enabled ? '1' : '0';
		row.find('.recordVersionEnabled').val(enabled).data('previous', enabled);
		row.find('.recordVersionRelationLabels').text(data.relations_text);
	},

	showError: function (message) {
		app.helper.showErrorNotification({message: message || this.getMessage('save-failed')});
	},

	registerEnabledChange: function () {
		var self = this;
		this.getContainer().on('change', '.recordVersionEnabled', function () {
			var select = jQuery(this);
			var row = select.closest('tr');
			var enabled = select.val();
			var previous = String(select.data('previous'));
			if (enabled === previous) {
				return;
			}
			var format = self.getMessage(enabled === '1' ? 'confirm-enable' : 'confirm-disable');
			var message = format.replace('%s', row.data('label'));
			app.helper.showConfirmationBox({message: message}).then(function () {
				app.helper.showProgress();
				self.callSaveApi({mode: 'module', tabid: row.data('tabid'), enabled: enabled}).done(function (data) {
					self.updateRow(row, data);
					app.helper.showSuccessNotification({message: self.getMessage('saved')});
				}).fail(function (error) {
					select.val(previous);
					self.showError(error);
				}).always(function () {
					app.helper.hideProgress();
				});
			}, function () {
				select.val(previous);
			});
		});
	},

	registerEditRelations: function () {
		var self = this;
		this.getContainer().on('click', '.recordVersionEditRelations', function () {
			var row = jQuery(this).closest('tr');
			var params = {module: 'RecordVersion', parent: 'Settings', view: 'EditRelations', tabid: row.data('tabid')};
			app.helper.showProgress();
			app.request.get({data: params}).then(function (error, html) {
				app.helper.hideProgress();
				if (error) {
					self.showError();
					return;
				}
				app.helper.showModal(html, {
					cb: function (modal) {
						modal.find('#recordVersionRelationsForm').on('submit', function (e) {
							e.preventDefault();
							var relationIds = [];
							jQuery(this).find('input[name="relation_ids[]"]:checked').each(function () {
								relationIds.push(jQuery(this).val());
							});
							app.helper.showProgress();
							self.callSaveApi({mode: 'relations', tabid: row.data('tabid'), relation_ids: relationIds}).done(function (data) {
								self.updateRow(row, data);
								app.helper.hideModal();
								app.helper.showSuccessNotification({message: self.getMessage('saved')});
							}).fail(function (error) {
								self.showError(error);
							}).always(function () {
								app.helper.hideProgress();
							});
						});
					}
				});
			});
		});
	},

	registerEvents: function () {
		this._super();
		this.registerEnabledChange();
		this.registerEditRelations();
	}
});
