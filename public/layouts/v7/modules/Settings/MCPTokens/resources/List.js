/**
 * MCPトークン管理 - フロントエンドJS
 *
 * 発行Ajax → 平文トークンをモーダル表示（1度のみ）
 * 無効化Ajax → 確認ダイアログ後にリロード
 */
Settings_Vtiger_List_Js("Settings_MCPTokens_List_Js", {}, {

	/**
	 * 「新規発行」ボタンクリック → モーダル表示
	 */
	registerCreateTokenEvent: function () {
		jQuery('#btnCreateToken').off('click.mcp').on('click.mcp', function () {
			// フォームリセット
			jQuery('#createTokenForm')[0].reset();
			jQuery('#createTokenModal').modal('show');
		});
	},

	/**
	 * 発行フォーム送信
	 */
	registerSubmitTokenEvent: function () {
		var thisInstance = this;
		jQuery('#btnSubmitToken').off('click.mcp').on('click.mcp', function () {
			var $btn = jQuery(this);
			// 送信中は二重送信を防ぐ
			if ($btn.prop('disabled')) {
				return;
			}
			var userid = jQuery('#tokenUserid').val();
			var label = jQuery.trim(jQuery('#tokenLabel').val());

			// クライアント側バリデーション
			if (!userid) {
				app.helper.showErrorNotification({ message: app.vtranslate('JS_MCP_SELECT_USER_REQUIRED') });
				return;
			}
			if (!label) {
				app.helper.showErrorNotification({ message: app.vtranslate('JS_MCP_LABEL_REQUIRED') });
				return;
			}

			$btn.prop('disabled', true);
			app.helper.showProgress();

			var params = {
				module: 'MCPTokens',
				parent: 'Settings',
				action: 'SaveAjax',
				userid: userid,
				label: label,
				expires_days: jQuery('#tokenExpires').val()
			};

			app.request.post({ data: params }).then(function (err, data) {
				app.helper.hideProgress();
				$btn.prop('disabled', false);

				if (err === null && data && data.token) {
					// 発行モーダルを閉じる
					jQuery('#createTokenModal').modal('hide');

					// 平文トークンを結果モーダルに表示
					jQuery('#generatedToken').val(data.token);
					jQuery('#tokenResultModal').modal('show');
				} else {
					var msg = (err && err.message) ? err.message : app.vtranslate('JS_MCP_ISSUE_FAILED');
					app.helper.showErrorNotification({ message: msg });
				}
			});
		});
	},

	/**
	 * トークンコピーボタン
	 */
	registerCopyTokenEvent: function () {
		jQuery('#btnCopyToken').off('click.mcp').on('click.mcp', function () {
			var tokenInput = document.getElementById('generatedToken');
			tokenInput.select();
			tokenInput.setSelectionRange(0, 99999);

			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(tokenInput.value).then(function () {
					app.helper.showSuccessNotification({ message: app.vtranslate('JS_MCP_COPIED') });
				});
			} else {
				// フォールバック
				document.execCommand('copy');
				app.helper.showSuccessNotification({ message: app.vtranslate('JS_MCP_COPIED') });
			}
		});
	},

	/**
	 * 結果モーダルを閉じたらページリロード
	 */
	registerCloseResultEvent: function () {
		jQuery('#btnCloseTokenResult').off('click.mcp').on('click.mcp', function () {
			jQuery('#tokenResultModal').modal('hide');
			// 平文をDOMから完全消去
			jQuery('#generatedToken').val('');
			window.location.reload();
		});
	},

	/**
	 * 無効化ボタン
	 */
	registerDisableTokenEvent: function () {
		jQuery(document).off('click.mcpDisable').on('click.mcpDisable', '.btnDisableToken', function () {
			var tokenId = jQuery(this).data('id');
			var tokenLabel = jQuery(this).data('label');
			var message = app.vtranslate('JS_MCP_DISABLE_CONFIRM').replace('%s', tokenLabel);

			// htmlSupportEnable:false でラベルをテキストとして扱う（既定は html() 挿入のため）
			app.helper.showConfirmationBox({ message: message, htmlSupportEnable: false }).then(function () {
				app.helper.showProgress();

				var params = {
					module: 'MCPTokens',
					parent: 'Settings',
					action: 'Delete',
					record: tokenId
				};

				app.request.post({ data: params }).then(function (err, data) {
					app.helper.hideProgress();

					if (err === null && data && data.success) {
						app.helper.showSuccessNotification({ message: app.vtranslate('JS_MCP_DISABLED') });
						window.location.reload();
					} else {
						var msg = (err && err.message) ? err.message : app.vtranslate('JS_MCP_DISABLE_FAILED');
						app.helper.showErrorNotification({ message: msg });
					}
				});
			});
		});
	},

	/**
	 * イベント登録
	 */
	registerEvents: function () {
		this.registerCreateTokenEvent();
		this.registerSubmitTokenEvent();
		this.registerCopyTokenEvent();
		this.registerCloseResultEvent();
		this.registerDisableTokenEvent();
	}
});
