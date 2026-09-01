{*+**********************************************************************************
* スケジューラ一覧の見出し。
*
* Settings/Vtiger/ListViewHeader.tpl の内容に、並列実行の設定表示を足したもの。
*
* 表示は #listViewContent の外に置く。保存後の一覧再読み込み（List.js の
* loadListViewRecords）は #listViewContent の中身を差し替えるため、中に置くと消える。
*
* 一覧の表と左端を揃えるため、ListViewContents.tpl と同じ col-sm-12 で包む。
* 見た目は style.css ではなく style 属性で指定している。style.css はブラウザに
* キャッシュされていると反映されず、文字が詰まって表示されてしまうため。
*************************************************************************************}

{strip}
	<div class="col-sm-12 col-xs-12">
		<div style="padding: 10px 0 4px; font-size: 12px; line-height: 1.7; color: #666;">
			<span style="font-weight: bold;">{vtranslate('LBL_MAX_PARALLEL', $QUALIFIED_MODULE)}</span>
			{* style 属性は 1 行で書く。{strip} が改行を詰めるため複数行にすると壊れやすい *}
			<span style="display: inline-block; min-width: 1.6em; margin: 0 6px; padding: 1px 7px; border-radius: 3px; background-color: #eceff1; color: #333; font-weight: bold; text-align: center;">{if $IS_PARALLEL_SUPPORTED}{$MAX_PARALLEL}{else}1{/if}</span>
			<span style="color: #888;">
				{if $IS_PARALLEL_SUPPORTED}
					{vtranslate('LBL_MAX_PARALLEL_INFO', $QUALIFIED_MODULE)}
				{else}
					{vtranslate('LBL_PARALLEL_UNAVAILABLE_INFO', $QUALIFIED_MODULE)}
				{/if}
			</span>
		</div>
	</div>
	<div class="listViewPageDiv" id="listViewContent">

{/strip}
