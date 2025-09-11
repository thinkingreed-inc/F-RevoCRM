{************************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************}

 {assign var=WIDGET_TITLE value=$WIDGET->getTitle()}
 {assign var=WIDGET_URL value=$WIDGET->getUrl()}
 {assign var=WIDGETID value=$WIDGET->get('id')}
 
 <div class="iframeWidgetContainer" style="height: 100%;">
     <div class="row" style="height: 100%;">
         <div class="col-sm-12" style="height: 100%;">
             <div class="iframeConfigForm" style="display:none; margin-bottom:10px;">
                 <div class="form-group">
                     <label class="control-label">Title:</label>
                     <input type="text" class="form-control" id="iframeTitle_{$WIDGETID}" value="{$WIDGET_TITLE}" placeholder="Enter title">
                 </div>
                 <div class="form-group">
                     <label class="control-label">URL:</label>
                     <input type="text" class="form-control" id="iframeUrl_{$WIDGETID}" value="{$WIDGET_URL}" placeholder="https://example.com or /index.php?module=Calendar">
                 </div>
                 <div class="form-group">
                     <button type="button" class="btn btn-success" onclick="Vtiger_IFrameWidget_Widget_Js.prototype.saveIFrameWidget('{$WIDGETID}');">Save</button>
                     <button type="button" class="btn btn-default" onclick="Vtiger_IFrameWidget_Widget_Js.prototype.cancelEdit('{$WIDGETID}');">Cancel</button>
                 </div>
             </div>
             
             <div class="iframeDisplay" style="height: 100%;">
                 {if $WIDGET_URL && $WIDGET_URL != 'https://www.example.com'}
                     <iframe 
                         id="iframe_{$WIDGETID}"
                         src="{$WIDGET_URL}" 
                         width="100%" 
                         height="100%"
                         frameborder="0"
                         scrolling="auto"
                         style="min-height: 300px;"
                         sandbox="allow-same-origin allow-scripts allow-popups allow-forms">
                         <p>Your browser does not support iframes.</p>
                     </iframe>
                 {else}
                     <div class="text-center text-muted" style="padding:50px; height: 100%; display: flex; flex-direction: column; justify-content: center;">
                         <i class="fa fa-external-link" style="font-size:48px;"></i>
                         <h4>IFrame Widget</h4>
                         <p>Click the settings icon to configure this widget.</p>
                     </div>
                 {/if}
             </div>
         </div>
     </div>
 </div>
 
 