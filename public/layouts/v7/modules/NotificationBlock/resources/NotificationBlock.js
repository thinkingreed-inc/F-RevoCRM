/**
 * 通知ブロック（#1965）の画面の処理
 *
 * 全画面で読み込む（vtiger_links の HEADERSCRIPT。通知ブロックのテンプレートからも、未読み込みなら読み込む）。
 * 詳細画面の「詳細」「概要」は画面を開いたあとに中身を差し替えるので、イベントは document に付ける。
 *
 * - 通知先の入力欄：役割・グループ・ユーザーのタブで候補を出し、入力した文字で絞り込んで複数選ぶ
 * - 詳細画面の通知先の直接編集
 * - 送信ダイアログ・送信履歴のダイアログ（通知ブロックの「送信履歴（N 件）」から開く）・宛先の一覧
 * - 作成時の送信の結果を、保存したあとに移った先の画面で出す
 */
(function () {
	if (typeof window.NotificationBlock_Js !== 'undefined') {
		return;
	}

	window.NotificationBlock_Js = {

		/** 候補を探す間隔（入力が止まってから） */
		searchDelay: 300,

		messages: function (element) {
			var container = jQuery(element).closest('.notificationBlockContainer');
			var messages = container.data('messages');
			return messages && typeof messages === 'object' ? messages : {};
		},

		csrfData: function (data) {
			if (typeof csrfMagicName !== 'undefined') {
				data[csrfMagicName] = csrfMagicToken;
			}
			return data;
		},

		/**
		 * apis（JSON）へ POST する。成功なら中身だけの JSON、失敗なら {success: false, error: {message}} が返る
		 */
		postApi: function (data, options) {
			var deferred = jQuery.Deferred();
			jQuery.ajax(jQuery.extend({url: 'index.php', type: 'POST', data: this.csrfData(data), dataType: 'json'}, options || {}))
				.done(function (response) {
					if (!response || response.success === false) {
						deferred.reject((response && response.error && response.error.message) || '', false);
						return;
					}
					deferred.resolve(response);
				})
				.fail(function (xhr, textStatus) {
					deferred.reject('', textStatus === 'timeout' || xhr.status === 0 || xhr.status >= 500);
				});
			return deferred.promise();
		},

		/* ------------------------------------------------------------------
		 * 通知先の入力欄
		 * ------------------------------------------------------------------ */

		registerPickerEvents: function () {
			var self = this;
			var doc = jQuery(document);

			doc.on('click', '.notificationBlockPickerChips', function (e) {
				if (jQuery(e.target).closest('.notificationBlockChipRemove').length > 0) {
					return;
				}
				self.openPanel(jQuery(this).closest('.notificationBlockPicker'));
			});
			doc.on('focus', '.notificationBlockPickerInput', function () {
				self.openPanel(jQuery(this).closest('.notificationBlockPicker'));
			});
			doc.on('click', '.notificationBlockPickerPanel .nav-tabs a', function (e) {
				e.preventDefault();
				var link = jQuery(this);
				var picker = link.closest('.notificationBlockPicker');
				link.closest('.nav-tabs').find('li').removeClass('active');
				link.closest('li').addClass('active');
				self.search(picker);
				picker.find('.notificationBlockPickerSearch').trigger('focus');
			});
			doc.on('input', '.notificationBlockPickerSearch, .notificationBlockPickerInput', function () {
				var field = jQuery(this);
				var picker = field.closest('.notificationBlockPicker');
				if (field.hasClass('notificationBlockPickerInput')) {
					picker.find('.notificationBlockPickerSearch').val(field.val());
				}
				clearTimeout(picker.data('searchTimer'));
				picker.data('searchTimer', setTimeout(function () {
					self.search(picker);
				}, self.searchDelay));
			});
			doc.on('click', '.notificationBlockCandidate', function (e) {
				e.preventDefault();
				var item = jQuery(this);
				var picker = item.closest('.notificationBlockPicker');
				self.addChip(picker, item.data('target'));
				item.remove();
				picker.find('.notificationBlockPickerInput').val('');
			});
			doc.on('click', '.notificationBlockChipRemove', function (e) {
				e.preventDefault();
				var picker = jQuery(this).closest('.notificationBlockPicker');
				jQuery(this).closest('.notificationBlockChip').remove();
				self.updateValue(picker);
				if (!picker.find('.notificationBlockPickerPanel').hasClass('hide')) {
					self.search(picker);
				}
			});
			// 入力欄の外を押したら候補を閉じる。押し下げた時点で閉じると、ボタンの位置がずれてクリックが
			// 空振りする（「保存」など）ため、クリックが終わってから閉じる
			doc.on('click', function (e) {
				// 押した候補・× はその場で画面から消すので、消えた要素のクリックは外側とみなさない
				if (!jQuery.contains(document.documentElement, e.target)) {
					return;
				}
				jQuery('.notificationBlockPicker').each(function () {
					if (!jQuery.contains(this, e.target)) {
						jQuery(this).find('.notificationBlockPickerPanel').addClass('hide');
					}
				});
			});
			// 作成・編集画面で、入力欄で Enter を押してもフォームを送らない
			doc.on('keydown', '.notificationBlockPickerInput, .notificationBlockPickerSearch', function (e) {
				if (e.keyCode === 13) {
					e.preventDefault();
				}
			});
		},

		openPanel: function (picker) {
			var panel = picker.find('.notificationBlockPickerPanel');
			if (panel.hasClass('hide')) {
				panel.removeClass('hide');
				this.search(picker);
			}
		},

		/**
		 * 選んだ種類のタブで、入力した文字に合う候補を探す（選んだものは除く）
		 */
		search: function (picker) {
			var self = this;
			var type = picker.find('.notificationBlockPickerPanel .nav-tabs li.active a').data('type') || 'role';
			var query = picker.find('.notificationBlockPickerSearch').val() || '';
			var exclude = [];
			picker.find('.notificationBlockChip[data-type="' + type + '"]').each(function () {
				exclude.push(String(jQuery(this).data('id')));
			});
			var requestId = (picker.data('requestId') || 0) + 1;
			picker.data('requestId', requestId);
			jQuery.ajax({
				url: 'index.php',
				type: 'GET',
				dataType: 'json',
				data: {module: 'NotificationBlock', api: 'SearchTargets', type: type, q: query, exclude: exclude.join(',')}
			}).done(function (response) {
				// 入力を続けたときに、古い結果で上書きしない
				if (picker.data('requestId') !== requestId) {
					return;
				}
				self.renderCandidates(picker, response && response.items ? response.items : [], response && response.more);
			});
		},

		renderCandidates: function (picker, items, more) {
			var messages = this.messages(picker);
			var list = picker.find('.notificationBlockPickerCandidates').empty();
			if (items.length === 0) {
				list.append(jQuery('<li class="text-muted" style="padding: 4px 8px;">').text(messages.LBL_NO_CANDIDATES || ''));
			}
			jQuery.each(items, function (index, item) {
				var link = jQuery('<a href="javascript:void(0)" class="notificationBlockCandidate" style="display: block; padding: 4px 8px;">')
					.text(item.label)
					.data('target', item);
				list.append(jQuery('<li>').append(link));
			});
			picker.find('.notificationBlockPickerMore').toggleClass('hide', !more);
		},

		addChip: function (picker, target) {
			if (!target) {
				return;
			}
			var messages = this.messages(picker);
			var chip = jQuery('<span class="notificationBlockChip label label-info">')
				.attr({'data-type': target.type, 'data-id': target.id, 'data-label': target.label, 'data-disabled': '0'})
				.text(target.text || target.label);
			chip.append('&nbsp;').append(
				jQuery('<a href="javascript:void(0)" class="notificationBlockChipRemove">&times;</a>').attr('title', messages.LBL_REMOVE_TARGET || '')
			);
			picker.find('.notificationBlockPickerInput').before(chip);
			this.updateValue(picker);
		},

		getTargets: function (picker) {
			var targets = [];
			picker.find('.notificationBlockChip').each(function () {
				var chip = jQuery(this);
				targets.push({type: String(chip.data('type')), id: String(chip.data('id'))});
			});
			return targets;
		},

		/**
		 * 選んだ通知先を、保存のときに送る値（JSON）に入れる
		 */
		updateValue: function (picker) {
			picker.find('.notificationBlockTargetsValue').val(JSON.stringify(this.getTargets(picker)));
		},

		/* ------------------------------------------------------------------
		 * 詳細画面の通知先の直接編集（保存しても通知は送らない）
		 * ------------------------------------------------------------------ */

		registerInlineEditEvents: function () {
			var self = this;
			var doc = jQuery(document);
			doc.on('click', '.notificationBlockEditTargets', function (e) {
				e.preventDefault();
				var row = jQuery(this).closest('.notificationBlockTargetsRow');
				var editor = row.find('.notificationBlockInlineEditor');
				editor.data('original', editor.find('.notificationBlockPickerChips').html());
				row.find('.notificationBlockTargetsView').addClass('hide');
				// 候補の一覧は入力欄を押したときに開く（開いたままだと「保存」を押しにくい）
				editor.removeClass('hide');
			});
			doc.on('click', '.notificationBlockCancelTargets', function (e) {
				e.preventDefault();
				var row = jQuery(this).closest('.notificationBlockTargetsRow');
				var editor = row.find('.notificationBlockInlineEditor');
				var picker = editor.find('.notificationBlockPicker');
				picker.find('.notificationBlockPickerChips').html(editor.data('original'));
				picker.find('.notificationBlockPickerPanel').addClass('hide');
				self.updateValue(picker);
				editor.addClass('hide');
				row.find('.notificationBlockTargetsView').removeClass('hide');
			});
			doc.on('click', '.notificationBlockSaveTargets', function (e) {
				e.preventDefault();
				var button = jQuery(this);
				if (button.prop('disabled')) {
					return;
				}
				var container = button.closest('.notificationBlockContainer');
				var picker = button.closest('.notificationBlockInlineEditor').find('.notificationBlockPicker');
				var messages = self.messages(container);
				button.prop('disabled', true);
				self.postApi({
					module: 'NotificationBlock',
					api: 'SaveTargets',
					record: container.data('record'),
					targets: JSON.stringify(self.getTargets(picker))
				}).then(function (response) {
					app.helper.showSuccessNotification({message: response.message});
					self.reloadBlock(container);
				}, function (message) {
					button.prop('disabled', false);
					app.helper.showErrorNotification({message: message || messages.LBL_TARGETS_SAVE_FAILED || ''});
				});
			});
		},

		/**
		 * 通知ブロックの中身（通知先・ボタン・送信履歴の件数と「送信中」）を読み直す。
		 * 同じレコードの送信履歴のダイアログを開いていれば、その中身も読み直す
		 */
		reloadBlock: function (container) {
			var deferred = jQuery.Deferred();
			var recordId = container.data('record');
			jQuery.ajax({
				url: 'index.php',
				type: 'GET',
				dataType: 'html',
				data: {module: 'NotificationBlock', view: 'Block', record: recordId}
			}).done(function (html) {
				container.find('.notificationBlockContents').replaceWith(html);
				deferred.resolve();
			}).fail(function () {
				deferred.reject();
			});
			this.reloadHistoryDialog(recordId);
			return deferred.promise();
		},

		/**
		 * 開いている送信履歴のダイアログの、送信履歴の一覧を読み直す（宛先の一覧に切り替えていれば、そのままにする）
		 */
		reloadHistoryDialog: function (recordId) {
			var historyDialog = jQuery('#notificationBlockHistoryDialog');
			if (historyDialog.length === 0 || !historyDialog.is(':visible') || String(historyDialog.data('record')) !== String(recordId)) {
				return;
			}
			jQuery.ajax({
				url: 'index.php',
				type: 'GET',
				dataType: 'html',
				data: {module: 'NotificationBlock', view: 'History', record: recordId}
			}).done(function (html) {
				var pane = historyDialog.find('.notificationBlockHistoryListPane');
				var fresh = jQuery('<div>').html(html).find('.notificationBlockHistoryListPane');
				if (fresh.length > 0) {
					pane.html(fresh.html());
				}
			});
		},

		/* ------------------------------------------------------------------
		 * 送信ダイアログ・送信履歴
		 * ------------------------------------------------------------------ */

		/** 送信の応答を待つ時間（ミリ秒）。過ぎたら「時間がかかっています」と出す（送信はサーバーで最後まで続く） */
		sendTimeout: 90000,

		registerSendEvents: function () {
			var self = this;
			var doc = jQuery(document);
			doc.on('click', '.notificationBlockSend', function (e) {
				e.preventDefault();
				var container = jQuery(this).closest('.notificationBlockContainer');
				self.openModal({module: 'NotificationBlock', view: 'SendDialog', record: container.data('record')}, function (modal) {
					self.registerSendForm(modal, container);
				});
			});
			doc.on('click', '.notificationBlockShowHistory', function (e) {
				e.preventDefault();
				var container = jQuery(this).closest('.notificationBlockContainer');
				self.openModal({module: 'NotificationBlock', view: 'History', record: container.data('record')});
			});
			doc.on('click', '.notificationBlockShowRecipients', function (e) {
				e.preventDefault();
				self.showRecipients(jQuery(this));
			});
			doc.on('click', '.notificationBlockBackToHistory', function (e) {
				e.preventDefault();
				self.backToHistory(jQuery(this).closest('#notificationBlockHistoryDialog'));
			});
		},

		/**
		 * 送信履歴のダイアログで宛先の人数を押したときに、ダイアログを重ねて開かず、中身を宛先の一覧
		 * （RecipientContent.tpl）に切り替える。
		 * app.helper.showModal() は画面に 1 つの入れ物（.myModal）を使い回し、前のダイアログが閉じ終わったときに
		 * 入れ物を空にするため、開いたまま別のダイアログを開くと新しい中身まで消えて、暗い背景だけが残る
		 */
		showRecipients: function (link) {
			var historyDialog = link.closest('#notificationBlockHistoryDialog');
			if (historyDialog.length === 0) {
				return;
			}
			var params = {
				module: 'NotificationBlock',
				view: 'History',
				record: historyDialog.data('record'),
				historyid: link.data('historyid'),
				mode: 'recipient_content'
			};
			app.helper.showProgress();
			app.request.get({data: params}).then(function (error, html) {
				app.helper.hideProgress();
				if (error) {
					app.helper.showErrorNotification({message: error.message});
					return;
				}
				var body = historyDialog.find('.modal-body');
				// 戻ったときに同じ位置へ戻せるよう、送信履歴の一覧のスクロールの位置を覚えておく
				historyDialog.data('historyScrollTop', body.scrollTop());
				historyDialog.find('.notificationBlockHistoryRecipientHolder').html(html);
				historyDialog.find('.notificationBlockHistoryListPane').addClass('hide');
				historyDialog.find('.notificationBlockHistoryRecipientPane').removeClass('hide');
				body.scrollTop(0);
			});
		},

		backToHistory: function (historyDialog) {
			historyDialog.find('.notificationBlockHistoryRecipientPane').addClass('hide');
			historyDialog.find('.notificationBlockHistoryRecipientHolder').empty();
			historyDialog.find('.notificationBlockHistoryListPane').removeClass('hide');
			historyDialog.find('.modal-body').scrollTop(historyDialog.data('historyScrollTop') || 0);
		},

		openModal: function (params, callback) {
			app.helper.showProgress();
			app.request.get({data: params}).then(function (error, html) {
				app.helper.hideProgress();
				if (error) {
					app.helper.showErrorNotification({message: error.message});
					return;
				}
				app.helper.showModal(html, {
					cb: function (modal) {
						if (callback) {
							callback(modal);
						}
					}
				});
			});
		},

		/**
		 * 「送信」：押したら処理が終わるまで押せなくする（二重送信を防ぐ）
		 */
		registerSendForm: function (modal, container) {
			var self = this;
			var form = modal.find('#notificationBlockSendForm');
			var errorBox = form.find('.notificationBlockSendError');
			var messages = self.messages(container);
			form.on('submit', function (e) {
				e.preventDefault();
				var submit = form.find('#notificationBlockSendSubmit');
				if (submit.length === 0 || submit.prop('disabled')) {
					return;
				}
				var comment = form.find('#notificationBlockComment').val() || '';
				if (comment.length > parseInt(form.data('maxLength'), 10)) {
					errorBox.text(form.data('messageTooLong')).removeClass('hide');
					return;
				}
				errorBox.addClass('hide');
				submit.prop('disabled', true);
				app.helper.showProgress();
				self.postApi({module: 'NotificationBlock', api: 'Send', record: form.data('record'), comment: comment}, {timeout: self.sendTimeout})
					.then(function (response) {
						app.helper.hideProgress();
						app.helper.hideModal();
						self.notify(response.message, response.notify_type);
						self.reloadBlock(container);
					}, function (message, isTimeout) {
						app.helper.hideProgress();
						app.helper.hideModal();
						// 画面の応答が先に切れても、送信はサーバーで続けている
						if (isTimeout) {
							app.helper.showAlertNotification({message: messages.LBL_SEND_TIMEOUT || ''});
						} else {
							app.helper.showErrorNotification({message: message || messages.LBL_SEND_ERROR || ''});
						}
						self.reloadBlock(container);
					});
			});
		},

		notify: function (message, type) {
			if (!message) {
				return;
			}
			if (type === 'success') {
				app.helper.showSuccessNotification({message: message});
			} else if (type === 'info') {
				app.helper.showAlertNotification({message: message});
			} else {
				app.helper.showErrorNotification({message: message});
			}
		},

		/* ------------------------------------------------------------------
		 * 作成時の送信の結果（保存したあとに移った先の画面で 1 回だけ出す）
		 * ------------------------------------------------------------------ */

		showFlash: function () {
			var self = this;
			if (document.cookie.indexOf('notificationblock_flash=1') === -1) {
				return;
			}
			jQuery.ajax({url: 'index.php', type: 'GET', dataType: 'json', data: {module: 'NotificationBlock', api: 'Flash'}})
				.done(function (response) {
					if (response && response.message) {
						self.notify(response.message, response.type);
					}
					// 作成時の送信の結果を受け取ったら、この画面の通知ブロックの件数・「送信中」も読み直す
					jQuery('.notificationBlockContainer').each(function () {
						var container = jQuery(this);
						if (container.find('.notificationBlockContents').length > 0 && container.data('record')) {
							self.reloadBlock(container);
						}
					});
				});
		},

		/**
		 * 通知ブロックの見た目（候補の一覧を入力欄の下に出す、鉛筆はマウスを乗せたときだけ出す）
		 */
		injectStyles: function () {
			if (jQuery('#notificationBlockStyles').length > 0) {
				return;
			}
			var css = [
				'.notificationBlockPicker{position:relative;}',
				// 通知先のバッジ（詳細画面・作成・編集画面で共通）。色は Bootstrap の label（本家の税の地域の一覧と同じ）、
				// 余白・行の高さ・角丸は select2 の複数選択で選んだ値（.select2-search-choice）に合わせ、
				// 文字は周りの項目の値と同じ大きさ・太さにする（label の既定は 75%・太字・上下の余白がそろわない）
				'.notificationBlockTarget,.notificationBlockPickerChips .notificationBlockChip{display:inline-block;margin:0 5px 5px 0;',
				'padding:3px 5px;font-size:inherit;font-weight:normal;line-height:13px;border-radius:3px;vertical-align:middle;white-space:normal;}',
				'.notificationBlockPickerChips .notificationBlockChipRemove{color:#fff;text-decoration:none;}',
				'.notificationBlockPickerInput{border:none;outline:none;min-width:160px;padding:2px;}',
				// 候補の一覧は重ねて開かず、ページの中に押し下げて出す（作成・編集画面の下に固定の「保存」の帯や、
				// 直接編集の「保存」「キャンセル」に隠れないように）
				'.notificationBlockPickerPanel{background:#fff;border:1px solid #ccc;padding:8px;margin-top:4px;}',
				'.notificationBlockPickerSearchRow{margin:8px 0;}',
				'.notificationBlockPickerSearch{width:100%;}',
				'.notificationBlockPickerCandidates{max-height:240px;overflow-y:auto;margin:0;}',
				'.notificationBlockCandidate:hover{background:#f0f4f8;}',
				'.notificationBlockTargetsRow .notificationBlockEditTargets{visibility:hidden;}',
				// 項目設定の「ブロックの種類」で選べない「通知ブロック」（select2 は背景だけ灰色にするので、文字も
				// Bootstrap のメニューで選べない項目と同じ色にする）
				'.select2-results .select2-disabled.notificationBlockOptionDisabled .select2-result-label{color:#777;}',
				'.notificationBlockTargetsRow:hover .notificationBlockEditTargets{visibility:visible;}'
			].join('');
			jQuery('<style id="notificationBlockStyles" type="text/css">').text(css).appendTo('head');
		},

		registerEvents: function () {
			this.injectStyles();
			this.registerPickerEvents();
			this.registerInlineEditEvents();
			this.registerSendEvents();
			this.showFlash();
		}
	};

	jQuery(function () {
		window.NotificationBlock_Js.registerEvents();
	});
})();
