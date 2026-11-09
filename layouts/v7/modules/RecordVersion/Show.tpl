{*<!--
/*+***********************************************************************************
 * 過去の版の表示（表示のみ）
 *************************************************************************************/
-->*}
{strip}
<div class="col-sm-12 col-xs-12 recordVersionPage" id="recordVersionShowView">
	<div class="detailViewContainer">
		<div class="clearfix" style="margin:10px 0">
			<h4 class="pull-left">{$RECORD_LABEL|escape:'html'}</h4>
			<div class="pull-right btn-group">
				{if $PREV_URL neq ''}<a class="btn btn-default" id="recordVersionPrev" href="{$PREV_URL}">{vtranslate('LBL_PREVIOUS_VERSION', $RV_MODULE)}</a>{/if}
				<a class="btn btn-default" id="recordVersionNext" href="{$NEXT_URL}">{vtranslate('LBL_NEXT_VERSION', $RV_MODULE)}</a>
				<a class="btn btn-default" href="{$HISTORY_URL}">{vtranslate('LBL_VERSION_HISTORY', $RV_MODULE)}</a>
				<a class="btn btn-primary" id="recordVersionBackToCurrent" href="{$DETAIL_URL}">{vtranslate('LBL_BACK_TO_CURRENT', $RV_MODULE)}</a>
			</div>
		</div>
		<div class="alert alert-warning" id="recordVersionNotice">
			<div>{$VERSION_MESSAGE|escape:'html'}</div>
			<div style="margin-top:5px"><strong>{vtranslate('LBL_REVISE_REASON', $RV_MODULE)}：</strong><span style="white-space:pre-wrap">{$REASON|escape:'html'}</span></div>
		</div>
		<div class="details">
			{include file="FieldBlocks.tpl"|vtemplate_path:$RV_MODULE}

			{if $INVENTORY}
				<div class="block recordVersionBlock" id="recordVersionLineItems">
					<div><h4>{vtranslate('LBL_LINE_ITEMS', $RV_MODULE)}</h4></div>
					<hr>
					<p class="text-muted">
						{vtranslate('LBL_TAX_MODE', $RV_MODULE)}：{$INVENTORY.tax_mode_label|escape:'html'}
						&nbsp;&nbsp;{vtranslate('LBL_CURRENCY', $RV_MODULE)}：{$INVENTORY.currency|escape:'html'}
						&nbsp;&nbsp;{vtranslate('LBL_CONVERSION_RATE', $RV_MODULE)}：{$INVENTORY.conversion_rate|escape:'html'}
					</p>
					<table class="table table-bordered lineItemsTable">
						<thead>
							<tr>
								<th>{vtranslate('LBL_ITEM_NAME', $RV_MODULE)}</th>
								<th class="text-right">{vtranslate('LBL_QUANTITY', $RV_MODULE)}</th>
								<th class="text-right">{vtranslate('LBL_LIST_PRICE', $RV_MODULE)}</th>
								<th class="text-right">{vtranslate('LBL_ITEM_TOTAL', $RV_MODULE)}</th>
								<th class="text-right">{vtranslate('LBL_ITEM_DISCOUNT', $RV_MODULE)}</th>
								{if $INVENTORY.is_individual}<th class="text-right">{vtranslate('LBL_ITEM_TAX', $RV_MODULE)}</th>{/if}
								<th class="text-right">{vtranslate('LBL_NET_PRICE', $RV_MODULE)}</th>
							</tr>
						</thead>
						<tbody>
							{foreach item=LINE from=$INVENTORY.lines}
								<tr>
									<td>
										<strong>{$LINE.name|escape:'html'}</strong>
										{if $LINE.code neq ''}<br><span class="text-muted">{$LINE.code|escape:'html'}</span>{/if}
										{if $LINE.comment neq ''}<br><span style="white-space:pre-wrap">{$LINE.comment|escape:'html'}</span>{/if}
									</td>
									<td class="text-right">{$LINE.quantity}</td>
									<td class="text-right">{$LINE.list_price}</td>
									<td class="text-right">{$LINE.product_total}</td>
									<td class="text-right">{$LINE.discount_total}</td>
									{if $INVENTORY.is_individual}<td class="text-right">{foreach item=TAX from=$LINE.taxes}<div>{$TAX}</div>{/foreach}</td>{/if}
									<td class="text-right">{$LINE.net_price}</td>
								</tr>
							{/foreach}
						</tbody>
					</table>
					<table class="table table-bordered pull-right" style="width:50%">
						<tbody>
							<tr><td>{vtranslate('LBL_ITEMS_TOTAL', $RV_MODULE)}</td><td class="text-right">{$INVENTORY.sub_total}</td></tr>
							<tr><td>{vtranslate('LBL_OVERALL_DISCOUNT', $RV_MODULE)} {$INVENTORY.discount_label}</td><td class="text-right">{$INVENTORY.discount_total}</td></tr>
							{foreach item=CHARGE from=$INVENTORY.charges}
								<tr><td>{$CHARGE.name|escape:'html'}</td><td class="text-right">{$CHARGE.value}</td></tr>
							{/foreach}
							<tr><td>{vtranslate('LBL_PRE_TAX_TOTAL', $RV_MODULE)}</td><td class="text-right">{$INVENTORY.pre_tax_total}</td></tr>
							{if !$INVENTORY.is_individual}
								{foreach item=TAX from=$INVENTORY.group_taxes}
									<tr><td>{$TAX.label|escape:'html'}</td><td class="text-right">{$TAX.amount}</td></tr>
								{/foreach}
							{/if}
							{foreach item=CHARGE from=$INVENTORY.charges}
								{foreach item=TAX from=$CHARGE.taxes}
									<tr><td>{vtranslate('LBL_TAXES_FOR_CHARGES', $RV_MODULE)}：{$TAX.label|escape:'html'}</td><td class="text-right">{$TAX.amount}</td></tr>
								{/foreach}
							{/foreach}
							<tr><td>{vtranslate('LBL_ADJUSTMENT', $RV_MODULE)}</td><td class="text-right">{$INVENTORY.adjustment}</td></tr>
							<tr><td><strong>{vtranslate('LBL_GRAND_TOTAL', $RV_MODULE)}</strong></td><td class="text-right"><strong>{$INVENTORY.grand_total}</strong></td></tr>
						</tbody>
					</table>
					<div class="clearfix"></div>
				</div>
				<br>
			{/if}

			{foreach item=RELATION from=$RELATIONS}
				<div class="block recordVersionBlock recordVersionRelation" data-related-module="{$RELATION.related_module}">
					<div><h4>{$RELATION.label|escape:'html'}</h4></div>
					<hr>
					{if $RELATION.rows|@count eq 0}
						<p class="text-muted recordVersionRelationEmpty">{$RELATION.empty_message|escape:'html'}</p>
					{else}
						<table class="table table-bordered listViewEntriesTable">
							<thead>
								<tr class="listViewHeaders">
									{foreach item=COLUMN from=$RELATION.columns}<th>{$COLUMN|escape:'html'}</th>{/foreach}
									{if $RELATION.is_documents}<th></th>{/if}
									<th></th>
								</tr>
							</thead>
							<tbody>
								{foreach item=ROW from=$RELATION.rows}
									<tr class="recordVersionRelatedRow cursorPointer" data-url="{$ROW.detail_url}" data-id="{$ROW.id}">
										{foreach item=CELL from=$ROW.cells}<td>{$CELL}</td>{/foreach}
										{if $RELATION.is_documents}
											<td class="recordVersionDocument">
												{if $ROW.document.url neq ''}
													<a href="{$ROW.document.url|escape:'html'}" target="_blank" rel="noopener noreferrer">{$ROW.document.file_name|escape:'html'}</a>
												{elseif $ROW.document.download_url neq ''}
													{$ROW.document.file_name|escape:'html'}&nbsp;({$ROW.document.version_label|escape:'html'})&nbsp;
													<a href="{$ROW.document.download_url}">{vtranslate('LBL_DOWNLOAD', $RV_MODULE)}</a>
												{else}
													<span class="text-muted">{if $ROW.document.file_name neq ''}{$ROW.document.file_name|escape:'html'}&nbsp;{/if}{vtranslate('LBL_NO_FILE', $RV_MODULE)}</span>
												{/if}
											</td>
										{/if}
										<td>
											{if $ROW.current_url neq ''}<a class="recordVersionOpenCurrent" href="{$ROW.current_url}">{vtranslate('LBL_OPEN_CURRENT_RECORD', $RV_MODULE)}</a>{/if}
										</td>
									</tr>
								{/foreach}
							</tbody>
						</table>
					{/if}
				</div>
				<br>
			{/foreach}
		</div>
	</div>
</div>
{/strip}
