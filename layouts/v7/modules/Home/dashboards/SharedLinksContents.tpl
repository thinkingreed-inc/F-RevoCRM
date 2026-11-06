{*+**********************************************************************************
 * 共有リンクのウィジェットの中身
 *
 * 表示とリンクの開き方は Web コンポーネント <shared-links-widget>（React）が受け持つ。
 * 公開範囲の判定とユーザー情報の差し込みはサーバー（Home_SharedLinks_Dashboard）で済ませ、
 * 見ているユーザーに表示できるリンクだけを属性で渡す。
 ************************************************************************************}
<div class="sharedLinksWidget">
	<shared-links-widget links="{$SHARED_LINKS_JSON|escape:'html'}" labels="{$SHARED_LINKS_LABELS_JSON|escape:'html'}"{if $IS_ADMIN} settings-url="index.php?module=SharedLinks&amp;parent=Settings&amp;view=List"{/if}></shared-links-widget>
</div>
