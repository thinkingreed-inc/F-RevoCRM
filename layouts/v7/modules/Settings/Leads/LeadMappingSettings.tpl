{*+**********************************************************************************
 * リードマッピング設定（項目のマッピング・昇格画面に出す項目・昇格時デフォルト値・昇格済み判定条件）
 * modules/Settings/Leads/views/MappingDetail.php
 * 画面本体は Web コンポーネント（assets/react-web-components）で描画する
 ************************************************************************************}
<div class="container-fluid" style="padding: 0; min-height: 600px;">
	<lead-mapping-settings></lead-mapping-settings>
	<div style="padding: 0 16px 16px; text-align: right; font-size: 12px;">
		<a href="index.php?parent=Settings&module=Leads&view=MappingDetail&legacy=1">{vtranslate('LBL_CONVERT_SETTINGS_LEGACY', $QUALIFIED_MODULE)}</a>
	</div>
</div>
