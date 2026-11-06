{*+**********************************************************************************
 * 共有リンクの一覧
 *
 * POST のリンクは <form> を書き出さない。csrf-magic が <form method="post"> に
 * CRM の CSRF トークンを埋め込み、リンク先へ送ってしまうため。
 * 押したときに SharedLinksWidget.js がフォームを組み立てて送信する。
 ************************************************************************************}
<div class="sharedLinksWidget">
	{if $SHARED_LINKS|@count gt 0}
		<ul class="sharedLinksList" style="list-style: none; margin: 0; padding: 0;">
			{foreach item=LINK from=$SHARED_LINKS}
				<li style="padding: 6px 0; border-bottom: 1px solid #eee;">
					<a class="sharedLink" data-shared-link="{$LINK['options_json']|escape:'html'}"
						{if $LINK['method'] eq 'GET'}href="{$LINK['url']|escape:'html'}" target="_blank" rel="{if $LINK['noreferrer']}noopener noreferrer{else}noopener{/if}"{else}href="javascript:void(0);"{/if}>
						<i class="fa fa-external-link" style="margin-right: 4px;"></i>{$LINK['title']|escape:'html'}
					</a>
					{if $LINK['description'] neq ''}
						<div class="text-muted" style="font-size: 12px; margin: 2px 0 0 18px; white-space: pre-wrap;">{$LINK['description']|escape:'html'}</div>
					{/if}
				</li>
			{/foreach}
		</ul>
	{else}
		<div class="noDataMsg text-center text-muted" style="padding: 20px 0;">
			{vtranslate('LBL_NO_SHARED_LINKS', $MODULE_NAME)}
			{if $IS_ADMIN}
				<div style="margin-top: 6px;">
					<a href="index.php?module=SharedLinks&parent=Settings&view=List">{vtranslate('LBL_SHARED_LINKS_SETTINGS', $MODULE_NAME)}</a>
				</div>
			{/if}
		</div>
	{/if}
</div>
