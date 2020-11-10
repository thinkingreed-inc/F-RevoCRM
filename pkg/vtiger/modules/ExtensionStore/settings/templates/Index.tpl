{*<!--
* 
* Copyright (C) www.vtiger.com. All rights reserved.
* @license Proprietary
*
-->*}
{strip}
    <div class="container-fluid" id="importModules">
        <div class="widget_header row-fluid">
            <span class="span6">
                <h3>{vtranslate('LBL_VTIGER_EXTENSION_STORE', $QUALIFIED_MODULE)}</h3>
            </span>
        </div><hr>

        <div class="row-fluid">
            <span class="span6">
                <div class="row-fluid">
                    <input type="text" id="searchExtension" class="span7 extensionSearch" placeholder="{vtranslate('LBL_SEARCH_FOR_AN_EXTENSION', $QUALIFIED_MODULE)}"/>
                </div>
            </span>
            <span class="span6">
                <span class="pull-right">
                    {if (!$PASSWORD_STATUS)}
                        <button id="logintoMarketPlace" class="btn btn-primary">{vtranslate('LBL_LOGIN_TO_MARKET_PLACE', $QUALIFIED_MODULE)}</button>
                    {elseif $IS_PRO}
                        <button id="setUpCardDetails" class="btn btn-primary ">{if !empty($CUSTOMER_PROFILE['CustomerCardId'])}{vtranslate('LBL_UPDATE_CARD_DETAILS', $QUALIFIED_MODULE)}{else}{vtranslate('LBL_SETUP_CARD_DETAILS', $QUALIFIED_MODULE)}{/if}</button>&nbsp;
			<button id="logoutMarketPlace" class="btn btn-primary pull-right">{vtranslate('LBL_LOGOUT', $QUALIFIED_MODULE)}</button>
                    {/if}
                    {if $PASSWORD_STATUS && !$IS_PRO}
                        <span class="btn-toolbar">
                            <span class="btn-group">
                                <button class='btn btn-danger' id="installLoader"><strong>{vtranslate('LBL_PHP_EXTENSION_LOADER_IS_NOT_AVAIABLE', $QUALIFIED_MODULE)}</strong></button>
                            </span>
                        </span>
                    {/if}
                </span>
            </span>
        </div>

        <div class="contents" id="extensionContainer">
            {include file='ExtensionModules.tpl'|@vtemplate_path:$QUALIFIED_MODULE}
        </div>
        
        <!-- Setup card detals form  start-->          
        <div class="modal setUpCardModal hide">
            <div class="modal-header contentsBackground">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h3>{vtranslate('LBL_SETUP_CARD', $QUALIFIED_MODULE)}</h3>
            </div>
            <form class="form-horizontal setUpCardForm">
                <input type="hidden" name="customerId" value="{$CUSTOMER_PROFILE['id']}" />
                <input type="hidden" name="customerCardId" value="{$CUSTOMER_PROFILE['CustomerCardId']}" />
                <input type="hidden" name="module" value="ExtensionStore" />
                <input type="hidden" name="parent" value="Settings" />
                <input type="hidden" name="action" value="Basic" />
                <input type="hidden" name="mode" value="updateCardDetails" />
                <div class="modal-body">
                    <div class="control-group">
                        <span class="control-label">
                            <span class="redColor">*</span>&nbsp;
                            {vtranslate('LBL_CARD_NUMBER', $QUALIFIED_MODULE)}
                        </span>
                        <div class="controls">
                            <input class="span3" type="text" placeholder="{vtranslate('LBL_CARD_NUMBER_PLACEHOLDER', $QUALIFIED_MODULE)}" name="cardNumber" value="{if !empty($CUSTOMER_CARD_INFO)} {$CUSTOMER_CARD_INFO['number']}{/if}" data-validation-engine="validate[required]" data-mask="9999-9999-9999-9999"/>
                        </div>
                    </div>
                    <div class="control-group">
                        <span class="control-label">
                            <span class="redColor">*</span>&nbsp;
                            {vtranslate('LBL_EXPIRY_DATE', $QUALIFIED_MODULE)}
                        </span>
                        <div class="controls">
                            <input style="width: 40px;" placeholder="mm" type="text" name="expMonth" value="{if !empty($CUSTOMER_CARD_INFO)} {$CUSTOMER_CARD_INFO['expmonth']}{/if}" data-validation-engine="validate[required]" data-mask="99" />
                            &nbsp;-&nbsp;
                            <input style="width: 40px;" placeholder="yyyy" type="text" name="expYear" value="{if !empty($CUSTOMER_CARD_INFO)} {$CUSTOMER_CARD_INFO['expyear']}{/if}" data-validation-engine="validate[required]" data-mask="9999" />
                        </div>
                    </div>
                    <div class="control-group">
                        <span class="control-label">
                            <span class="redColor">*</span>&nbsp;
                            {vtranslate('LBL_SECURITY_CODE', $QUALIFIED_MODULE)}
                        </span>
                        <div class="controls">
                            <input style="width: 40px;" type="text" name="cvccode" value="{if !empty($CUSTOMER_CARD_INFO)} *** {/if}" data-validation-engine="validate[required]" data-mask="999"/>
                            &nbsp;&nbsp;
                            <span class="icon icon-question-sign" id="helpSecurityCode" onmouseover="Settings_ExtensionStore_Js.showPopover(this)" data-title="{vtranslate('LBL_WHAT_IS_SECURITY_CODE', $QUALIFIED_MODULE)}" data-content="{vtranslate('LBL_SECURITY_CODE_HELP_CONTENT', $QUALIFIED_MODULE)}" data-position="right"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <div class="span3">
                            <span class="pull-left"><button class="btn btn-danger" type="button" name="resetButton"><strong>{vtranslate('LBL_RESET', $QUALIFIED_MODULE)}</strong></button></span>
                        </div>
                        <div class="span9">
                            <div class="pull-right">
                                <div class="pull-right cancelLinkContainer" style="margin-top:0px;">
                                    <a class="cancelLink" type="reset" data-dismiss="modal">{vtranslate('LBL_CANCEL', $MODULE)}</a>
                                </div>
                                    <button class="btn btn-success saveButton" type="submit" name="saveButton"><strong>{vtranslate('LBL_SAVE', $MODULE)}</strong></button>
                            </div>
                        </div>  
                    </div>
                </div>
            </form>
        </div>
        <!-- Setup card detals form  end-->                              

        <!-- Signup form  start-->                              
        <div class="modal signUpAccount hide">
            <div class="modal-header contentsBackground">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h3>{vtranslate('LBL_SIGN_UP_FOR_FREE', $QUALIFIED_MODULE)}</h3>
            </div>
            <form class="form-horizontal signUpForm">
                <input type="hidden" name="module" value="ExtensionStore" />
                <input type="hidden" name="parent" value="Settings" />
                <input type="hidden" name="action" value="Basic" />
                <input type="hidden" name="userAction" value="signup" />
                <input type="hidden" name="mode" value="registerAccount" />
                <div class="modal-body">
                    <div class="control-group">
                        <span class="control-label">
                            <span class="redColor">*</span>&nbsp;
                            {vtranslate('LBL_EMAIL_ADDRESS', $QUALIFIED_MODULE)}
                        </span>
                        <div class="controls">
                            <input type="text" name="emailAddress" data-validation-engine="validate[required, funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" />
                        </div>
                    </div>
                    <div class="control-group">
                        <span class="control-label">
                            <span class="redColor">*</span>&nbsp;
                            {vtranslate('LBL_FIRST_NAME', $QUALIFIED_MODULE)}
                        </span>
                        <div class="controls">
                            <input type="text" name="firstName" data-validation-engine="validate[required, funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" />
                        </div>
                    </div>
                    <div class="control-group">
                        <span class="control-label">
                            <span class="redColor">*</span>&nbsp;
                            {vtranslate('LBL_LAST_NAME', $QUALIFIED_MODULE)}
                        </span>
                        <div class="controls">
                            <input type="text" name="lastName" data-validation-engine="validate[required, funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" />
                        </div>
                    </div>
                    <div class="control-group">
                        <span class="control-label">
                            <span class="redColor">*</span>&nbsp;
                            {vtranslate('LBL_COMPANY_NAME', $QUALIFIED_MODULE)}
                        </span>
                        <div class="controls">
                            <input type="text" name="companyName" data-validation-engine="validate[required, funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" />
                        </div>
                    </div>
                    <div class="control-group">
                        <span class="control-label">
                            <span class="redColor">*</span>&nbsp;
                            {vtranslate('LBL_PASSWORD', $QUALIFIED_MODULE)}
                        </span>
                        <div class="controls">
                            <input type="password" name="password" data-validation-engine="validate[required, funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" />
                        </div>
                    </div>
                    <div class="control-group">
                        <span class="control-label">
                            <span class="redColor">*</span>&nbsp;
                            {vtranslate('LBL_CONFIRM_PASSWORD', $QUALIFIED_MODULE)}
                        </span>
                        <div class="controls">
                            <input type="password" name="confirmPassword" data-validation-engine="validate[required, funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" />
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <div class="pull-right">
                            <div class="pull-right cancelLinkContainer" style="margin-top:0px;">
                                <a class="cancelLink" type="reset" data-dismiss="modal">{vtranslate('LBL_CANCEL', $MODULE)}</a>
                            </div>
                            <button class="btn btn-success" type="submit" name="saveButton"><strong>{vtranslate('LBL_REGISTER', $QUALIFIED_MODULE)}</strong></button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <!-- Signup form  end-->                
        
        <!-- Login form  start-->
        <div class="modal loginAccount hide">
            <div class="modal-header contentsBackground">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h3>{vtranslate('LBL_MARKETPLACE_REGISTRATION', $QUALIFIED_MODULE)}</h3>
            </div>
            <form class="form-horizontal loginForm">
                <input type="hidden" name="module" value="ExtensionStore" />
                <input type="hidden" name="parent" value="Settings" />
                <input type="hidden" name="action" value="Basic" />
                <input type="hidden" name="userAction" value="login" />
                <input type="hidden" name="mode" value="registerAccount" />
                <div class="modal-body">
                    <div class="control-group">
                        <span class="control-label">
                            <span class="redColor">*</span>&nbsp;
                            {vtranslate('LBL_EMAIL', $QUALIFIED_MODULE)}
                        </span>
                        <div class="controls">
                            {if $REGISTRATION_STATUS}
                                <input type="hidden" name="emailAddress" value="{$USER_NAME}" />
                                <span class="control-label"><span class="pull-left">{$USER_NAME}</span></span>
                            {else}
                                <input type="text" name="emailAddress" data-validation-engine="validate[required, custom[email],funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" />
                            {/if}
                        </div>
                    </div>
                    <div class="control-group">
                        <span class="control-label">
                            <span class="redColor">*</span>&nbsp;
                            {vtranslate('LBL_PASSWORD', $QUALIFIED_MODULE)}
                        </span>
                        <div class="controls">
                            <input type="password" name="password" data-validation-engine="validate[required, funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" />
                        </div>
                    </div>
                    <div class="control-group">
                        <span class="control-label"></span>
                        <div class="controls">
                            <span>
                                <input type="checkbox" name="savePassword" /> &nbsp; &nbsp;{vtranslate('LBL_REMEMBER_ME', $QUALIFIED_MODULE)}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <div class="span6">
                            {if !$REGISTRATION_STATUS}
                                <div class="row-fluid">
                                    <a href="#" name="signUp">{vtranslate('LBL_CREATE_AN_ACCOUNT', $QUALIFIED_MODULE)}</a>
                                </div>
                            {else}&nbsp;
                            {/if}
                        </div>
                        <div class="span6">
                            <div class="pull-right">
                                <div class="pull-right cancelLinkContainer" style="margin-top:0px;">
                                    <a class="cancelLink" type="reset" data-dismiss="modal">{vtranslate('LBL_CANCEL', $MODULE)}</a>
                                </div>
                                <button class="btn btn-success" type="submit" name="saveButton"><strong>{vtranslate('LBL_LOGIN', $QUALIFIED_MODULE)}</strong></button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <!-- Login form end -->
        
        {if $LOADER_REQUIRED}
            <div class="modal extensionLoader hide">
                <div class="modal-header contentsBackground">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h3>{vtranslate('LBL_INSTALL_EXTENSION_LOADER', $QUALIFIED_MODULE)}</h3>
                </div>
                <div class="modal-body">
                    <div class="row-fluid">
                        <p>{vtranslate('LBL_TO_CONTINUE_USING_EXTENSION_STORE', $QUALIFIED_MODULE)}<a href="https://marketplace.vtiger.com/loaderfiles/{$LOADER_INFO['loader_file']}">{vtranslate('LBL_DOWNLOAD', $QUALIFIED_MODULE)}</a>{vtranslate('LBL_COMPATIABLE_EXTENSION', $QUALIFIED_MODULE)}</p>
                    </div>
                    <div class="row-fluid">
                        <p>{vtranslate('LBL_MORE_DETAILS_ON_INSTALLATION', $QUALIFIED_MODULE)}<a onclick=window.open("http://community.vtiger.com/help/vtigercrm/php/extension-loader.html")>{vtranslate('LBL_READ_HERE', $QUALIFIED_MODULE)}</a></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <div class="pull-right">
                            <div class="pull-right cancelLinkContainer" style="margin-top:0px;">
                                <button class="btn btn-success" data-dismiss="modal">{vtranslate('LBL_OK', $QUALIFIED_MODULE)}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        {/if}
    </div>
{/strip}
