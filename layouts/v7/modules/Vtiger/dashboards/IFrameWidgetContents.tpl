{************************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************}

 {assign var=WIDGET_URL value=$WIDGET->getUrl()}
 {assign var=WIDGETID value=$WIDGET->get('id')}

 <div class="iframeWidgetContainer" style="height: 100%;">
     <div class="row" style="height: 100%;">
         <div class="col-sm-12" style="height: 100%;">
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
                     <h4>{vtranslate('LBL_IFRAME_WIDGET', $MODULE_NAME)}</h4>
                     <p>Please add a new widget with valid URL.</p>
                 </div>
             {/if}
         </div>
     </div>
 </div>
 
 