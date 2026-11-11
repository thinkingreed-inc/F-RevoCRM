{*<!--
/*+***********************************************************************************
 * 過去の版の項目（ブロックごと）と「削除された項目」
 * FIELD_BLOCKS・DELETED_FIELDS の値は、表示用に整えた HTML（RecordVersion_Display_Model）
 *************************************************************************************/
-->*}
{strip}
{foreach item=BLOCK from=$FIELD_BLOCKS}
	<div class="block recordVersionBlock">
		<div><h4 class="textOverflowEllipsis maxWidth50">{$BLOCK.label|escape:'html'}</h4></div>
		<hr>
		<div class="blockData">
			<table class="table detailview-table no-border">
				<tbody>
					{foreach item=FIELD from=$BLOCK.fields name=fieldLoop}
						{if $smarty.foreach.fieldLoop.index % 2 eq 0}<tr>{/if}
						<td class="fieldLabel textOverflowEllipsis" style="width:20%"><span class="muted">{$FIELD.label|escape:'html'}</span></td>
						<td class="fieldValue" style="width:30%"><span class="value">{$FIELD.value}</span></td>
						{if $smarty.foreach.fieldLoop.index % 2 eq 1 || $smarty.foreach.fieldLoop.last}
							{if $smarty.foreach.fieldLoop.index % 2 eq 0}<td class="fieldLabel"></td><td></td>{/if}
							</tr>
						{/if}
					{/foreach}
				</tbody>
			</table>
		</div>
	</div>
	<br>
{/foreach}
{if $DELETED_FIELDS|@count gt 0}
	<div class="block recordVersionBlock recordVersionDeletedFields">
		<div><h4 class="textOverflowEllipsis maxWidth50">{vtranslate('LBL_DELETED_FIELDS', $RV_MODULE)}</h4></div>
		<hr>
		<div class="blockData">
			<table class="table detailview-table no-border">
				<tbody>
					{foreach item=FIELD from=$DELETED_FIELDS}
						<tr>
							<td class="fieldLabel textOverflowEllipsis" style="width:20%"><span class="muted">{$FIELD.label|escape:'html'}</span></td>
							<td class="fieldValue"><span class="value">{$FIELD.value}</span></td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		</div>
	</div>
	<br>
{/if}
{/strip}
