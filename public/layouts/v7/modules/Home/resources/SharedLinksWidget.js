/*+**********************************************************************************
 * ダッシュボードのウィジェット「共有リンク」
 *
 * リンクを押したときに、設定どおりの開き方（別タブ / 別ウィンドウ）で開く。
 * - GET・別タブ: <a target="_blank"> のまま、ブラウザに任せる
 * - GET・別ウィンドウ: 通常のクリックだけ受けて window.open で開く
 *   （中クリック・Ctrl+クリックはブラウザの通常の動きのまま）
 * - POST: 押したときにフォームを組み立てて送信する
 *
 * POST のフォームを HTML に書き出さないのは、csrf-magic が <form method="post"> に
 * CRM の CSRF トークンを埋め込み、リンク先へ送ってしまうため。
 * 仕様: docs/specs/shared_links_widget.md
 ************************************************************************************/
(function ($) {
	'use strict';

	var SharedLinksWidget = {

		/** 同じリンクは同じウィンドウを使い回す */
		getWindowName: function (link) {
			return 'frSharedLink_' + link.id;
		},

		/**
		 * window.open に渡すサイズと位置。今のウィンドウの中央に来るようにし、
		 * 画面より大きいときは画面に収まるサイズに縮める
		 */
		getWindowFeatures: function (link) {
			var screenWidth = window.screen.availWidth || window.outerWidth;
			var screenHeight = window.screen.availHeight || window.outerHeight;
			var width = Math.min(link.width, screenWidth);
			var height = Math.min(link.height, screenHeight);
			var baseLeft = typeof window.screenX === 'number' ? window.screenX : window.screenLeft;
			var baseTop = typeof window.screenY === 'number' ? window.screenY : window.screenTop;
			var left = baseLeft + Math.max(0, Math.round((window.outerWidth - width) / 2));
			var top = baseTop + Math.max(0, Math.round((window.outerHeight - height) / 2));
			return 'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top
				+ ',resizable=yes,scrollbars=yes';
		},

		openGetInWindow: function (link) {
			var features = this.getWindowFeatures(link) + ',noopener' + (link.noreferrer ? ',noreferrer' : '');
			// noopener を付けると成功しても null が返るため、ブロックされたかは判定しない
			window.open(link.url, this.getWindowName(link), features);
		},

		openPost: function (link) {
			if (link.openMode !== 'window') {
				this.submitPost(link, '_blank');
				return;
			}
			var name = this.getWindowName(link);
			var opened = window.open('about:blank', name, this.getWindowFeatures(link));
			if (!opened) {
				app.helper.showErrorNotification({message: app.vtranslate('JS_SHARED_LINK_POPUP_BLOCKED')});
				return;
			}
			this.submitPost(link, name);
			// 送信先のページから CRM の画面を操作できないようにする
			try {
				opened.opener = null;
			} catch (e) {
				// 別オリジンへ遷移済みなら設定できないが、その場合も参照は残らない
			}
		},

		submitPost: function (link, target) {
			var form = document.createElement('form');
			form.method = 'POST';
			form.action = link.url;
			form.target = target;
			form.style.display = 'none';
			form.setAttribute('rel', link.noreferrer ? 'noopener noreferrer' : 'noopener');

			$.each(link.params || [], function (index, param) {
				var input = document.createElement('input');
				input.type = 'hidden';
				input.name = param.name;
				input.value = param.value;
				form.appendChild(input);
			});

			// 念のため、CRM の CSRF トークンが入っていれば取り除く
			if (typeof csrfMagicName !== 'undefined') {
				$(form).find('input').filter(function () {
					return this.name === csrfMagicName;
				}).remove();
			}

			document.body.appendChild(form);
			form.submit();
			document.body.removeChild(form);
		},

		registerEvents: function () {
			var thisInstance = this;
			$(document).on('click', '.sharedLinksWidget a.sharedLink', function (e) {
				var link = $(this).data('sharedLink');
				if (!link || typeof link !== 'object') {
					return;
				}
				if (link.method === 'GET') {
					var isPlainClick = e.button === 0 && !e.ctrlKey && !e.metaKey && !e.shiftKey && !e.altKey;
					if (link.openMode !== 'window' || !isPlainClick) {
						return;
					}
					e.preventDefault();
					thisInstance.openGetInWindow(link);
					return;
				}
				e.preventDefault();
				thisInstance.openPost(link);
			});
		}
	};

	$(function () {
		SharedLinksWidget.registerEvents();
	});
})(jQuery);
