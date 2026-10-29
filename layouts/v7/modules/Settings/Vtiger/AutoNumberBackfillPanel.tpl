{strip}
<div class="autoNumberBackfill" data-test-id="autonumber-backfill" data-module="{$SOURCE_MODULE_NAME}" data-field="{$SELECTED_FIELD_NAME}" data-label-targets="{vtranslate('LBL_AUTONUMBER_BACKFILL_TARGETS', 'Vtiger')}" data-label-range="{vtranslate('LBL_AUTONUMBER_BACKFILL_RANGE', 'Vtiger')}">
		<hr />
		<div class="form-group">
			<label class="control-label fieldLabel col-lg-3 col-md-3 col-sm-3"><strong>{vtranslate('LBL_AUTONUMBER_BACKFILL', 'Vtiger')}</strong></label>
			<div class="controls col-lg-6 col-md-6 col-sm-6"><div class="muted">{vtranslate('LBL_AUTONUMBER_BACKFILL_HELP', 'Vtiger')}</div></div>
		</div>
		<div class="form-group js-backfill-range">
			<label class="control-label fieldLabel col-lg-3 col-md-3 col-sm-3">{vtranslate('LBL_AUTONUMBER_BACKFILL_CREATED_FROM', 'Vtiger')}</label>
			<div class="controls col-lg-6 col-md-6 col-sm-6"><input type="date" class="inputElement js-backfill-from" style="width: 45%" /></div>
		</div>
		<div class="form-group js-backfill-range">
			<label class="control-label fieldLabel col-lg-3 col-md-3 col-sm-3">{vtranslate('LBL_AUTONUMBER_BACKFILL_CREATED_TO', 'Vtiger')}</label>
			<div class="controls col-lg-6 col-md-6 col-sm-6"><input type="date" class="inputElement js-backfill-to" style="width: 45%" /></div>
		</div>
		<div class="form-group">
			<div class="col-lg-3 col-md-3 col-sm-3"></div>
			<div class="controls col-lg-6 col-md-6 col-sm-6">
				<button type="button" class="btn btn-default js-backfill-preview">{vtranslate('LBL_AUTONUMBER_BACKFILL_PREVIEW', 'Vtiger')}</button>
				<button type="button" class="btn btn-primary js-backfill-start hide">{vtranslate('LBL_AUTONUMBER_BACKFILL_START', 'Vtiger')}</button>
				<button type="button" class="btn btn-default js-backfill-pause hide">{vtranslate('LBL_AUTONUMBER_BACKFILL_PAUSE', 'Vtiger')}</button>
				<button type="button" class="btn btn-default js-backfill-resume hide">{vtranslate('LBL_AUTONUMBER_BACKFILL_RESUME', 'Vtiger')}</button>
				<button type="button" class="btn btn-danger js-backfill-cancel hide">{vtranslate('LBL_AUTONUMBER_BACKFILL_CANCEL', 'Vtiger')}</button>
			</div>
		</div>
		<div class="form-group js-backfill-info hide">
			<div class="col-lg-3 col-md-3 col-sm-3"></div>
			<div class="controls col-lg-6 col-md-6 col-sm-6">
				<div class="js-backfill-preview-text"></div>
				<div class="muted js-backfill-warning hide">{vtranslate('LBL_AUTONUMBER_BACKFILL_WARNING', 'Vtiger')}</div>
				<div class="progress js-backfill-progress hide" style="margin: 8px 0;"><div class="progress-bar js-backfill-bar" role="progressbar" style="width: 0%"></div></div>
				<div class="js-backfill-status" data-test-id="autonumber-backfill-status"></div>
				<div class="muted js-backfill-nocron hide">{vtranslate('LBL_AUTONUMBER_BACKFILL_NOCRON', 'Vtiger')}</div>
				<div class="text-danger js-backfill-error"></div>
			</div>
		</div>
	</div>
{/strip}
