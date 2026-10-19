/*+***********************************************************************************
 * The contents of this file are subject to the Vtiger Public License Version 1.2
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: frevo-mcp (https://github.com/ratorin/frevo-mcp)
 * The Initial Developer of the Original Code is ratorin.
 * Portions created by ratorin are Copyright (C) ratorin.
 * All Rights Reserved.
 *************************************************************************************/
/**
 * MCP連携アプリケーション管理 - フロントエンドJS
 *
 * 利用者での絞り込み → ページ再読込
 * 解除Ajax → 確認ダイアログ後にリロード
 */
Settings_Vtiger_List_Js("Settings_MCPOAuthApps_List_Js", {}, {

	/**
	 * ページ送り・並べ替えでも絞り込みを保つ（親の既定パラメータには乗らないため足す）
	 */
	getDefaultParams: function () {
		var params = this._super();
		var userName = jQuery('#mcpOAuthFilterUserName').val();
		if (userName) {
			params['search_key'] = 'user_name';
			params['search_value'] = userName;
		}
		return params;
	},

	/**
	 * 一覧を絞り込んで開き直す
	 */
	openWithFilter: function (userName) {
		var url = 'index.php?module=MCPOAuthApps&parent=Settings&view=List';
		if (userName) {
			url += '&search_key=user_name&search_value=' + encodeURIComponent(userName);
		}
		window.location.href = url;
	},

	/**
	 * 検索行の検索・クリア（Enter でも検索する）
	 */
	registerFilterEvents: function () {
		var thisInstance = this;

		jQuery('#mcpOAuthSearchBtn').off('click.mcpOAuth').on('click.mcpOAuth', function () {
			thisInstance.openWithFilter(jQuery.trim(jQuery('#mcpOAuthUserFilter').val()));
		});

		jQuery('#mcpOAuthUserFilter').off('keypress.mcpOAuth').on('keypress.mcpOAuth', function (e) {
			if (e.which === 13) {
				thisInstance.openWithFilter(jQuery.trim(jQuery(this).val()));
			}
		});

		jQuery('#mcpOAuthClearBtn').off('click.mcpOAuth').on('click.mcpOAuth', function () {
			thisInstance.openWithFilter('');
		});
	},

	/**
	 * 解除ボタン
	 */
	registerRevokeEvent: function () {
		var thisInstance = this;
		jQuery(document).off('click.mcpOAuthRevoke').on('click.mcpOAuthRevoke', '.btnRevokeMcpOAuth', function () {
			var recordId = jQuery(this).data('id');
			var label = jQuery(this).data('label');
			var message = app.vtranslate('JS_MCP_OAUTH_REVOKE_CONFIRM').replace('%s', label);

			// htmlSupportEnable:false でラベルをテキストとして扱う（既定は html() 挿入のため）
			app.helper.showConfirmationBox({ message: message, htmlSupportEnable: false }).then(function () {
				app.helper.showProgress();

				var params = {
					module: 'MCPOAuthApps',
					parent: 'Settings',
					action: 'Delete',
					record: recordId
				};

				app.request.post({ data: params }).then(function (err, data) {
					app.helper.hideProgress();

					if (err === null && data && data.success) {
						app.helper.showSuccessNotification({ message: app.vtranslate('JS_MCP_OAUTH_REVOKED') });
						jQuery('#pageNumber').val(1);
						thisInstance.loadListViewRecords();
					} else {
						var msg = (err && err.message) ? err.message : app.vtranslate('JS_MCP_OAUTH_REVOKE_FAILED');
						app.helper.showErrorNotification({ message: msg });
					}
				});
			});
		});
	},

	/**
	 * ページ送りで一覧を差し替えた後、差し替わった検索行のイベントを付け直す
	 */
	postLoadListViewRecords: function (res) {
		this._super(res);
		this.registerFilterEvents();
	},

	/**
	 * イベント登録
	 *
	 * _super() は呼ばない（MCPTokens/resources/List.js と同じ流儀）。
	 * ページングは LoginHistory と同じく initializePaginationEvents() を直接呼んで登録する。
	 */
	registerEvents: function () {
		this.initializePaginationEvents();
		this.registerFilterEvents();
		this.registerRevokeEvent();
	}

});
