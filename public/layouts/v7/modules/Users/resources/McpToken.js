/*+***********************************************************************************
 * The contents of this file are subject to the Vtiger Public License Version 1.2
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: frevo-mcp (https://github.com/ratorin/frevo-mcp)
 * The Initial Developer of the Original Code is ratorin.
 * Portions created by ratorin are Copyright (C) ratorin.
 * All Rights Reserved.
 *************************************************************************************/

Vtiger.Class("Users_McpToken_Js",{},{

	// 発行直後に受け取った平文を保持する（画面を離れる/リロードで消える）
	currentToken : '',

	// MCP エンドポイント URL（ブロックの data 属性から取得）
	mcpUrl : '',

	/**
	 * クライアントごとのコマンドと説明。{URL} と {TOKEN} を差し込む
	 */
	getClientTemplates : function() {
		return {
			claude_code: {
				command: 'claude mcp add --transport http frevo-crm {URL} \\\n  --header "Authorization: Bearer {TOKEN}"',
				note: '--scope user を付けると、どのリポジトリからでも使えます。'
			},
			codex: {
				command: '# ~/.codex/config.toml\n[mcp_servers.frevo-crm]\nurl = "{URL}"\nhttp_headers = { Authorization = "Bearer {TOKEN}" }',
				note: '~/.codex/config.toml に追記します。'
			},
			copilot: {
				command: '// .vscode/mcp.json\n{\n  "servers": {\n    "frevo-crm": {\n      "type": "http",\n      "url": "{URL}",\n      "headers": { "Authorization": "Bearer {TOKEN}" }\n    }\n  }\n}',
				note: 'ワークスペースの .vscode/mcp.json に記述します。'
			},
			cursor: {
				command: '// ~/.cursor/mcp.json\n{\n  "mcpServers": {\n    "frevo-crm": {\n      "url": "{URL}",\n      "headers": { "Authorization": "Bearer {TOKEN}" }\n    }\n  }\n}',
				note: '~/.cursor/mcp.json に記述します。'
			},
			claude_desktop: {
				command: '// claude_desktop_config.json\n{\n  "mcpServers": {\n    "frevo-crm": {\n      "command": "npx",\n      "args": ["mcp-remote", "{URL}", "--header", "Authorization: Bearer {TOKEN}"]\n    }\n  }\n}',
				note: 'claude_desktop_config.json に記述します。'
			}
		};
	},

	/**
	 * 選択したクライアントのコマンドと説明を描画する
	 */
	fillClientConfig : function(client) {
		var tpl = this.getClientTemplates()[client];
		if (!tpl) {
			return;
		}
		var command = tpl.command.replace(/\{URL\}/g, this.mcpUrl).replace(/\{TOKEN\}/g, this.currentToken);
		jQuery('#mcpClientCommand').text(command);
		jQuery('#mcpClientNote').text(tpl.note);
	},

	/**
	 * 発行した行を一覧の先頭に追加する（リロード不要）
	 */
	prependTokenRow : function(data) {
		var expiresText;
		if (!data.expires_at) {
			expiresText = app.vtranslate('JS_MCP_TOKEN_EXPIRES_NONE');
		} else {
			expiresText = app.htmlEncode(data.expires_at);
		}
		var revokeLabel = app.vtranslate('JS_MCP_TOKEN_REVOKE');
		var $row = jQuery(
			'<tr data-row-id="' + app.htmlEncode(data.id) + '">' +
			'<td>' + app.htmlEncode(data.label) + '</td>' +
			'<td class="mcpPrefix">' + (data.prefix ? app.htmlEncode(data.prefix) + '…' : '—') + '</td>' +
			'<td>' + app.htmlEncode(data.created_at) + '</td>' +
			'<td>—</td>' +
			'<td>' + expiresText + '</td>' +
			'<td class="text-right">' +
				'<button type="button" class="btn btn-danger btn-xs mcpTokenRevokeBtn" ' +
					'data-id="' + app.htmlEncode(data.id) + '" data-label="' + app.htmlEncode(data.label) + '">' +
					revokeLabel + '</button>' +
			'</td></tr>'
		);
		jQuery('#mcpTokenListBody').prepend($row);
		jQuery('#mcpTokenEmpty').hide();
	},

	/**
	 * クリップボードにコピーする（非対応環境は execCommand にフォールバック）
	 */
	copyText : function(text) {
		var done = function() {
			if (app && app.helper) {
				app.helper.showSuccessNotification({ message: app.vtranslate('JS_MCP_TOKEN_COPIED') });
			}
		};
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(text).then(done);
		} else {
			var $tmp = jQuery('<textarea>').val(text).appendTo('body').select();
			document.execCommand('copy');
			$tmp.remove();
			done();
		}
	},

	/**
	 * トークン発行ボタン
	 */
	registerIssueEvent : function() {
		var thisInstance = this;
		jQuery('#mcpTokenIssueBtn').off('click.mcpToken').on('click.mcpToken', function() {
			var $btn = jQuery(this);
			var label = jQuery.trim(jQuery('#mcpTokenLabel').val());
			var expires = jQuery('#mcpTokenExpires').val();

			if (!label) {
				app.helper.showErrorNotification({ message: app.vtranslate('JS_MCP_TOKEN_LABEL_REQUIRED') });
				return;
			}

			$btn.prop('disabled', true);
			app.helper.showProgress();

			app.request.post({ data: {
				module: 'Users',
				action: 'SaveMcpTokenAjax',
				label: label,
				expires_days: expires
			}}).then(function(err, data) {
				app.helper.hideProgress();
				$btn.prop('disabled', false);

				if (err === null && data && data.token) {
					thisInstance.currentToken = data.token;
					jQuery('#mcpTokenPlain').text(data.token);
					jQuery('#mcpTokenResult').show();

					// クライアントの設定を表示し、既定タブ（Claude Code）を描画
					jQuery('#mcpTokenClientConfig').show();
					jQuery('.mcpClientTabs li').removeClass('active').first().addClass('active');
					thisInstance.fillClientConfig('claude_code');

					thisInstance.prependTokenRow(data);
					jQuery('#mcpTokenLabel').val('');
				} else {
					var msg = (err && err.message) ? err.message : app.vtranslate('JS_MCP_TOKEN_ISSUE_FAILED');
					app.helper.showErrorNotification({ message: msg });
				}
			});
		});
	},

	/**
	 * 平文コピーボタン
	 */
	registerCopyPlainEvent : function() {
		var thisInstance = this;
		jQuery('#mcpTokenCopyBtn').off('click.mcpToken').on('click.mcpToken', function() {
			thisInstance.copyText(jQuery('#mcpTokenPlain').text());
		});
	},

	/**
	 * クライアント設定タブの切替
	 */
	registerClientTabEvent : function() {
		var thisInstance = this;
		jQuery('.mcpClientTabs a').off('click.mcpToken').on('click.mcpToken', function() {
			jQuery('.mcpClientTabs li').removeClass('active');
			jQuery(this).closest('li').addClass('active');
			thisInstance.fillClientConfig(jQuery(this).data('client'));
		});
	},

	/**
	 * クライアントコマンドのコピーボタン
	 */
	registerClientCopyEvent : function() {
		var thisInstance = this;
		jQuery('#mcpClientCopyBtn').off('click.mcpToken').on('click.mcpToken', function() {
			thisInstance.copyText(jQuery('#mcpClientCommand').text());
		});
	},

	/**
	 * トークン失効ボタン
	 */
	registerRevokeEvent : function() {
		jQuery(document).off('click.mcpTokenRevoke').on('click.mcpTokenRevoke', '.mcpTokenRevokeBtn', function() {
			var id = jQuery(this).data('id');
			var label = jQuery(this).data('label');
			var $row = jQuery(this).closest('tr');
			var message = app.vtranslate('JS_MCP_TOKEN_REVOKE_CONFIRM').replace('%s', label);
			// htmlSupportEnable:false でラベルをテキストとして扱う（既定は html() 挿入のため）
			app.helper.showConfirmationBox({ message: message, htmlSupportEnable: false }).then(function() {
				app.helper.showProgress();
				app.request.post({ data: {
					module: 'Users',
					action: 'DeleteMcpTokenAjax',
					record: id
				}}).then(function(err, data) {
					app.helper.hideProgress();
					if (err === null && data && data.success) {
						app.helper.showSuccessNotification({ message: app.vtranslate('JS_MCP_TOKEN_REVOKED') });
						// 個人設定画面では失効した行を消す（管理画面は無効行を残す。役割分担）
						$row.remove();
						if (jQuery('#mcpTokenListBody tr').length === 0) {
							jQuery('#mcpTokenEmpty').show();
						}
					} else {
						var msg = (err && err.message) ? err.message : app.vtranslate('JS_MCP_TOKEN_REVOKE_FAILED');
						app.helper.showErrorNotification({ message: msg });
					}
				});
			});
		});
	},

	/**
	 * MCP トークンブロックが存在する画面でのみイベントを登録する
	 */
	registerEvents : function() {
		var $block = jQuery('.mcp_token_block');
		if ($block.length === 0) {
			return;
		}
		this.mcpUrl = $block.data('mcp-url') || '';
		this.registerIssueEvent();
		this.registerCopyPlainEvent();
		this.registerClientTabEvent();
		this.registerClientCopyEvent();
		this.registerRevokeEvent();
	}

});
