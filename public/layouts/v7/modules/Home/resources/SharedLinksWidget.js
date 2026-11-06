/*+**********************************************************************************
 * ダッシュボードのウィジェット「共有リンク」の枠の調整
 *
 * ウィジェットの中身（リンクの一覧と開き方）は Web コンポーネント <shared-links-widget>（React）が受け持つ。
 * ここではダッシュボード（vtiger）側の枠だけを扱う。
 *
 * 共通の Vtiger_Widget_Js は、読み込み時と大きさを変えたときに中身の高さを
 * 「今の高さ − 40px」と固定値で書き込むだけで、ウィジェットの大きさを見ていない。
 * そのため大きくしても中身が広がらず、スクロールバーが出たままになる。
 * Vtiger_Widget_Js.getInstance は Home_<名前>_Widget_Js があればそれを使う。
 * 仕様: docs/specs/shared_links_widget.md
 ************************************************************************************/
if (typeof Vtiger_Widget_Js !== 'undefined') {
	Vtiger_Widget_Js('Home_SharedLinks_Widget_Js', {}, {

		postLoadWidget: function () {
			this._super();
			this.fitContentHeight();
		},

		postResizeWidget: function () {
			this.fitContentHeight();
		},

		postRefreshWidget: function () {
			this._super();
			this.fitContentHeight();
		},

		/** 中身の高さ = ウィジェットの内側の下端（フッターの上）− 中身の上端 */
		fitContentHeight: function () {
			var container = this.getContainer();
			var content = container.find('.dashboardWidgetContent');
			if (content.length === 0) {
				return;
			}
			var footer = container.find('.dashBoardWidgetFooter');
			var contentTop = content.offset().top - container.offset().top;
			var footerHeight = footer.length ? footer.outerHeight() : 0;
			var height = container.innerHeight() - contentTop - footerHeight;
			content.css('height', Math.max(0, Math.floor(height)) + 'px');
		}
	});
}

/*
 * 「ウィジェットの追加」メニュー
 *
 * 共通の Vtiger_DashBoard_Js.addWidget は、追加したウィジェットと同じ名前（data-name）の項目を
 * メニューからすべて隠す。共有リンクのウィジェットはどれも data-name="SharedLinks" のため、
 * 1 つ追加するとほかの共有リンクのウィジェットまで選べなくなる。
 * 押した項目だけを隠したままにし（追加済みの印を付ける）、ほかの共有リンクの項目は表示し直す。
 * 項目の onclick（addWidget）は押した要素で先に動くため、ここ（document で受ける）はその後に動く。
 */
if (typeof jQuery !== 'undefined') {
	jQuery(document).on('click', '.widgetsList a[data-name="SharedLinks"]', function () {
		var item = jQuery(this);
		item.parent().attr('data-shared-links-added', '1');
		item.closest('.widgetsList')
			.find('a[data-name="SharedLinks"]')
			.not(item)
			.parent()
			.not('[data-shared-links-added]')
			.show();
	});
}
