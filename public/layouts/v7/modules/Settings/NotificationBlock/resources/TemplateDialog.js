/**
 * 通知ブロック（#1965）：項目設定の「メール文面の設定」ダイアログ
 *
 * 項目設定（LayoutEditor.js）のボタンから、このファイルを読み込んで open() を呼ぶ。
 * 本文の編集欄はワークフローのメール送信と同じ Jodit を使う（項目設定の画面では読み込まれていないので、ここで読み込む）。
 */
window.Settings_NotificationBlock_TemplateDialog_Js = {

	editorLoaded: null,

	open: function (tabId) {
		var self = this;
		app.helper.showProgress();
		jQuery.when(self.loadEditor(), app.request.get({data: {module: 'NotificationBlock', parent: 'Settings', view: 'TemplateDialog', tabid: tabId}}))
			.then(function (editor, response) {
				app.helper.hideProgress();
				var error = response[0];
				var html = response[1];
				if (error) {
					app.helper.showErrorNotification({message: error.message});
					return;
				}
				app.helper.showModal(html, {
					cb: function (modal) {
						self.register(modal);
					}
				});
			}, function () {
				app.helper.hideProgress();
			});
	},

	/**
	 * Jodit と、製品のラッパー（Vtiger_Jodit_Js）を一度だけ読み込む
	 */
	loadEditor: function () {
		if (this.editorLoaded) {
			return this.editorLoaded;
		}
		var deferred = jQuery.Deferred();
		if (typeof Vtiger_Jodit_Js !== 'undefined' && typeof Jodit !== 'undefined') {
			deferred.resolve();
		} else {
			if (jQuery('link[href*="jodit.fat.min.css"]').length === 0) {
				jQuery('<link rel="stylesheet" type="text/css">').attr('href', 'libraries/jodit/jodit.fat.min.css').appendTo('head');
			}
			jQuery.ajax({url: 'libraries/jodit/jodit.fat.min.js', dataType: 'script', cache: true}).then(function () {
				return jQuery.ajax({url: 'layouts/v7/modules/Vtiger/resources/JoditEditor.js', dataType: 'script', cache: true});
			}).then(function () {
				deferred.resolve();
			}, function () {
				deferred.reject();
			});
		}
		this.editorLoaded = deferred.promise();
		return this.editorLoaded;
	},

	register: function (modal) {
		var self = this;
		var form = modal.find('#notificationBlockTemplateForm');
		var errorBox = form.find('.notificationBlockTemplateError');

		form.find('.notificationBlockTemplateLanguage').each(function () {
			var pane = jQuery(this);
			var textarea = pane.find('.notificationBlockBody');
			new Vtiger_Jodit_Js().loadJoditEditor(textarea);
			var editor = Vtiger_Jodit_Js.getInstance(textarea.attr('id'));
			// 差し込みを入れる先（最後に触ったのが件名か本文か）
			pane.data('insertTarget', 'body');
			var markEdited = function () {
				pane.data('useDefault', 0);
			};
			pane.find('.notificationBlockSubject').on('focus', function () {
				pane.data('insertTarget', 'subject');
			}).on('input', markEdited);
			if (editor) {
				editor.on('focus', function () {
					pane.data('insertTarget', 'body');
				});
				editor.on('change', markEdited);
			}

			pane.find('.notificationBlockPlaceholder').on('change', function () {
				var code = jQuery(this).val();
				if (!code) {
					return;
				}
				if (pane.data('insertTarget') === 'subject') {
					self.insertAtCaret(pane.find('.notificationBlockSubject'), code);
				} else if (editor) {
					editor.insertHtml(jQuery('<div>').text(code).html());
				}
				markEdited();
				jQuery(this).val('');
			});

			// 表示中の言語を既定の文面に戻す（保存すると、その言語は未設定の状態に戻る）
			pane.find('.notificationBlockResetTemplate').on('click', function () {
				pane.find('.notificationBlockSubject').val(pane.data('defaultSubject'));
				if (editor) {
					editor.setData(pane.data('defaultBody'));
				}
				pane.data('useDefault', 1);
			});
		});

		form.on('submit', function (e) {
			e.preventDefault();
			var submit = form.find('#notificationBlockTemplateSave');
			if (submit.prop('disabled')) {
				return;
			}
			var maxLength = parseInt(form.data('subjectMaxLength'), 10);
			var templates = {};
			var tooLong = false;
			form.find('.notificationBlockTemplateLanguage').each(function () {
				var pane = jQuery(this);
				var textarea = pane.find('.notificationBlockBody');
				var editor = Vtiger_Jodit_Js.getInstance(textarea.attr('id'));
				var subject = jQuery.trim(pane.find('.notificationBlockSubject').val());
				var body = editor ? editor.getData() : textarea.val();
				if (subject.length > maxLength) {
					tooLong = true;
				}
				// 「既定の文面のまま」（未設定に戻す）は、件名・本文が本当に既定の文面と同じときだけにする
				// （入力を検知する前に書き換えられても、書き換えた内容を捨てないため）
				var useDefault = String(pane.data('useDefault')) === '1'
					&& subject === String(pane.data('defaultSubject'))
					&& self.textOf(body) === self.textOf(String(pane.data('defaultBody')));
				templates[pane.data('language')] = {
					subject: subject,
					body: body,
					use_default: useDefault ? '1' : '0'
				};
			});
			if (tooLong) {
				errorBox.text(form.data('messageSubjectTooLong')).removeClass('hide');
				return;
			}
			errorBox.addClass('hide');
			submit.prop('disabled', true);
			var data = {
				module: 'NotificationBlock',
				parent: 'Settings',
				api: 'SaveTemplate',
				tabid: form.data('tabid'),
				templates: JSON.stringify(templates)
			};
			if (typeof csrfMagicName !== 'undefined') {
				data[csrfMagicName] = csrfMagicToken;
			}
			jQuery.ajax({url: 'index.php', type: 'POST', data: data, dataType: 'json'}).done(function (response) {
				if (!response || response.success === false) {
					errorBox.text((response && response.error && response.error.message) || '').removeClass('hide');
					submit.prop('disabled', false);
					return;
				}
				app.helper.hideModal();
				app.helper.showSuccessNotification({message: response.message});
			}).fail(function (xhr) {
				var message = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error.message : '';
				errorBox.text(message).removeClass('hide');
				submit.prop('disabled', false);
			});
		});
	},

	/**
	 * 本文の比べ方：エディターが HTML の書き方を整えることがあるので、文字だけで比べる
	 */
	textOf: function (html) {
		return jQuery.trim(jQuery('<div>').html(html || '').text().replace(/\s+/g, ' '));
	},

	insertAtCaret: function (input, text) {
		var element = input.get(0);
		var start = element.selectionStart || 0;
		var end = element.selectionEnd || 0;
		var value = input.val();
		input.val(value.substring(0, start) + text + value.substring(end));
		element.selectionStart = element.selectionEnd = start + text.length;
		input.trigger('focus');
	}
};
