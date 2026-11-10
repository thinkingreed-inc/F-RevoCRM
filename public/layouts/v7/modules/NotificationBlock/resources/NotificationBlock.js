/**
 * 通知ブロック（#1965）の画面の処理
 *
 * 全画面で読み込む（vtiger_links の HEADERSCRIPT。通知ブロックのテンプレートからも、未読み込みなら読み込む）。
 * 詳細画面の「詳細」「概要」は画面を開いたあとに中身を差し替えるので、イベントは document に付ける。
 *
 * - 通知先の入力欄：役割・グループ・ユーザーのタブで候補を出し、入力した文字で絞り込んで複数選ぶ
 * - 詳細画面の通知先の直接編集
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
			// 入力欄の外を押したら候補を閉じる
			doc.on('mousedown', function (e) {
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
				editor.removeClass('hide');
				self.openPanel(editor.find('.notificationBlockPicker'));
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
		 * 通知ブロックの中身（通知先・ボタン・直近の送信履歴）を読み直す
		 */
		reloadBlock: function (container) {
			var deferred = jQuery.Deferred();
			jQuery.ajax({
				url: 'index.php',
				type: 'GET',
				dataType: 'html',
				data: {module: 'NotificationBlock', view: 'Block', record: container.data('record')}
			}).done(function (html) {
				container.find('.notificationBlockContents').replaceWith(html);
				deferred.resolve();
			}).fail(function () {
				deferred.reject();
			});
			return deferred.promise();
		},

		/**
		 * 通知ブロックの見た目（候補の一覧を入力欄の下に開く、鉛筆はマウスを乗せたときだけ出す）
		 */
		injectStyles: function () {
			if (jQuery('#notificationBlockStyles').length > 0) {
				return;
			}
			var css = [
				'.notificationBlockPicker{position:relative;}',
				'.notificationBlockPickerChips .notificationBlockChip{display:inline-block;margin:2px 4px 2px 0;padding:4px 6px;font-size:12px;font-weight:normal;}',
				'.notificationBlockPickerChips .notificationBlockChipRemove{color:#fff;text-decoration:none;}',
				'.notificationBlockPickerInput{border:none;outline:none;min-width:160px;padding:2px;}',
				'.notificationBlockPickerPanel{position:absolute;z-index:1060;left:0;right:0;background:#fff;border:1px solid #ccc;',
				'box-shadow:0 4px 8px rgba(0,0,0,0.15);padding:8px;}',
				'.notificationBlockPickerSearchRow{margin:8px 0;}',
				'.notificationBlockPickerSearch{width:100%;}',
				'.notificationBlockPickerCandidates{max-height:240px;overflow-y:auto;margin:0;}',
				'.notificationBlockCandidate:hover{background:#f0f4f8;}',
				'.notificationBlockTargetsRow .notificationBlockEditTargets{visibility:hidden;}',
				'.notificationBlockTargetsRow:hover .notificationBlockEditTargets{visibility:visible;}'
			].join('');
			jQuery('<style id="notificationBlockStyles" type="text/css">').text(css).appendTo('head');
		},

		registerEvents: function () {
			this.injectStyles();
			this.registerPickerEvents();
			this.registerInlineEditEvents();
		}
	};

	jQuery(function () {
		window.NotificationBlock_Js.registerEvents();
	});
})();
