/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
Vtiger.Class('Settings_Parameters_Js', {

	//holds the current instance
	currentInstance : false,

	//WebComponentのイベントを登録済みかどうか（document に委譲するため一度だけ登録する）
	componentEventsRegistered : false,

	/**
	 * 編集アイコン（ListViewRecordActions.tpl の RECORD_LINK）から呼ばれる
	 */
	triggerEdit : function(event, id) {
		if (event) {
			if (event.preventDefault) event.preventDefault();
			if (event.stopPropagation) event.stopPropagation();
			if (event.stopImmediatePropagation) event.stopImmediatePropagation();
		}
		var instance = Settings_Parameters_Js.currentInstance;
		if (instance) {
			instance.showEditView(id);
		}
		return false;
	}

}, {

	//constructor
	init : function() {
		Settings_Parameters_Js.currentInstance = this;
	},

	/**
	 * 行のどこをクリックしても編集ダイアログを開く。
	 * 編集アイコンも行の中にあるため、このハンドラひとつで両方を拾う。
	 * 一覧を再描画しても効くよう document に委譲する。
	 */
	registerRowClick : function() {
		var thisInstance = this;
		jQuery(document).off('click.parameterEdit', '#listview-table tr.listViewEntries')
			.on('click.parameterEdit', '#listview-table tr.listViewEntries', function(e) {
				var row = jQuery(e.currentTarget);
				if (row.find('.fa-pencil').length <= 0) {
					return;
				}
				thisInstance.showEditView(row.data('id'));
			});
	},

	/**
	 * 編集ダイアログ（React WebComponent）のイベントを登録する
	 * WebComponent 側は bubbles:true で発火するため document で受ける
	 */
	registerParameterEditComponent : function() {
		if (Settings_Parameters_Js.componentEventsRegistered) {
			return;
		}
		Settings_Parameters_Js.componentEventsRegistered = true;

		var isTarget = function(target) {
			return !!target && target.id === 'parameterEditComponent';
		};

		// 保存成功時: サーバー側の値を反映するため一覧を再読み込みする
		document.addEventListener('save', function(e) {
			if (!isTarget(e.target)) {
				return;
			}
			window.location.reload();
		});

		// キャンセル・閉じる時: is-open を false に戻す
		document.addEventListener('cancel', function(e) {
			if (!isTarget(e.target)) {
				return;
			}
			e.target.setAttribute('is-open', 'false');
		});

		document.addEventListener('open-change', function(e) {
			if (!isTarget(e.target)) {
				return;
			}
			// e.detail が false または { isOpen: false } の両方に対応
			var detail = e.detail;
			if (detail === false || (detail && typeof detail === 'object' && detail.isOpen === false)) {
				e.target.setAttribute('is-open', 'false');
			}
		});
	},

	registerEvents : function() {
		this.registerRowClick();
		this.registerParameterEditComponent();
	},

	/**
	 * 編集ダイアログ（React WebComponent）を開く
	 */
	showEditView : function(id) {
		var component = document.getElementById('parameterEditComponent');
		if (!component || !id) {
			return false;
		}
		component.setAttribute('record-id', id);
		component.setAttribute('is-open', 'true');
		return false;
	}

});
