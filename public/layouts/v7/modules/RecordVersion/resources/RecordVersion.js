/**
 * 版管理（#1964）の画面の処理
 *
 * - 詳細画面の「改版」：ボタンを押したときにこのファイルを読み込み、改版のダイアログを開く
 *   （vtiger_links の DETAILVIEWBASIC から RecordVersion_Js.openReviseDialog() を呼ぶ）
 * - 過去の版の表示：関連レコードの当時の全項目を小窓で出す
 */
window.RecordVersion_Js = {

	/**
	 * 改版のダイアログを開く
	 */
	openReviseDialog: function (moduleName, recordId) {
		var self = this;
		app.helper.showProgress();
		app.request.get({data: {module: 'RecordVersion', view: 'ReviseDialog', record: recordId}}).then(function (error, html) {
			app.helper.hideProgress();
			if (error) {
				app.helper.showErrorNotification({message: error.message});
				return;
			}
			app.helper.showModal(html, {
				cb: function (modal) {
					self.registerReviseForm(modal);
				}
			});
		});
	},

	registerReviseForm: function (modal) {
		var form = modal.find('#recordVersionReviseForm');
		var errorBox = form.find('.recordVersionReviseError');
		var showError = function (message) {
			errorBox.text(message).removeClass('hide');
		};
		form.on('submit', function (e) {
			e.preventDefault();
			var submit = form.find('#recordVersionReviseSubmit');
			if (submit.length === 0 || submit.prop('disabled')) {
				return;
			}
			var reason = jQuery.trim(form.find('#recordVersionReason').val());
			if (reason === '') {
				showError(form.data('message-required'));
				return;
			}
			if (reason.length > parseInt(form.data('max-length'), 10)) {
				showError(form.data('message-too-long'));
				return;
			}
			errorBox.addClass('hide');
			// 処理が終わるまで「改版する」は押せなくする
			submit.prop('disabled', true);
			var data = {
				module: 'RecordVersion',
				api: 'Revise',
				record: form.data('record'),
				reason: reason,
				expected_version: form.data('expected-version')
			};
			if (typeof csrfMagicName !== 'undefined') {
				data[csrfMagicName] = csrfMagicToken;
			}
			jQuery.ajax({url: 'index.php', type: 'POST', data: data, dataType: 'json'}).done(function (response) {
				if (!response || response.success === false || !response.edit_url) {
					showError((response && response.error && response.error.message) || form.data('message-failed'));
					submit.prop('disabled', false);
					return;
				}
				// 改版したら、続けて編集画面を開く（保存せずに閉じても改版は取り消さない）
				window.location.href = response.edit_url;
			}).fail(function () {
				showError(form.data('message-failed'));
				submit.prop('disabled', false);
			});
		});
	},

	/**
	 * 過去の版の表示：関連レコードの行を押すと、当時の全項目を小窓で出す
	 */
	registerShowView: function () {
		var container = jQuery('#recordVersionShowView');
		if (container.length === 0) {
			return;
		}
		container.on('click', '.recordVersionRelatedRow', function (e) {
			if (jQuery(e.target).closest('a').length > 0) {
				return;
			}
			var url = jQuery(this).data('url');
			app.helper.showProgress();
			app.request.get({url: url}).then(function (error, html) {
				app.helper.hideProgress();
				if (error) {
					app.helper.showErrorNotification({message: error.message});
					return;
				}
				app.helper.showModal(html);
			});
		});
	}
};

jQuery(function () {
	window.RecordVersion_Js.registerShowView();
});
