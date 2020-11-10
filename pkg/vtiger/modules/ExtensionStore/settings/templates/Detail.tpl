{*<!--
* 
* Copyright (C) www.vtiger.com. All rights reserved.
* @license Proprietary
*
-->*}
{strip}
	<div class="container-fluid detailViewInfo extensionDetails" style='margin-top:0px;'>
		{if !($ERROR)}
            <input type="hidden" name="mode" value="{$smarty.request.mode}" />
			<input type="hidden" name="extensionId" value="{$EXTENSION_ID}" />
			<input type="hidden" name="targetModule" value="{$EXTENSION_DETAIL->get('name')}" />
			<input type="hidden" name="moduleAction" value="{$MODULE_ACTION}" />
			<div class="row-fluid contentHeader">
                            <div class="span6">
                                <div style="margin-bottom: 5px;"><span  class="font-x-x-large">{$EXTENSION_DETAIL->get('name')}</span>&nbsp;<span class="muted">{vtranslate('LBL_BY', $QUALIFIED_MODULE)}&nbsp;{$AUTHOR_INFO['firstname']}&nbsp;{$AUTHOR_INFO['lastname']}</span></div>
                                {assign var=ON_RATINGS value=$EXTENSION_DETAIL->get('avgrating')}
                                <div class="row-fluid">
                                    <span data-score="{$ON_RATINGS}" class="rating span5" data-readonly="true"></span>
                                    <span class="span6">({count($CUSTOMER_REVIEWS)} {vtranslate('LBL_REVIEWS', $QUALIFIED_MODULE)})</span>
                                </div>
                            </div>
                            <div class="span6">
                                <div class="pull-right extensionDetailActions">
                                    {if ($MODULE_ACTION eq 'Installed')}
                                        <button class="btn btn-danger {if ($REGISTRATION_STATUS) and ($PASSWORD_STATUS)}authenticated {else} loginRequired{/if}" type="button" style="margin-right: 6px;" id="uninstallModule"><strong>{vtranslate('LBL_UNINSTALL', $QUALIFIED_MODULE)}</strong></button>
                                    {else}
                                        {if $EXTENSION_DETAIL->get('isprotected') && $IS_PRO && ($EXTENSION_DETAIL->get('price') gt 0)}
                                            <button class="btn btn-info {if (!$CUSTOMER_PROFILE['CustomerCardId'])} setUpCard{/if}{if ($REGISTRATION_STATUS) and ($PASSWORD_STATUS)} authenticated {else} loginRequired{/if}" type="button" id="installExtension"><strong>{vtranslate('LBL_BUY',$QUALIFIED_MODULE)}${$EXTENSION_DETAIL->get('price')}</strong></button>
                                        {elseif (!$EXTENSION_DETAIL->get('isprotected')) && ($EXTENSION_DETAIL->get('price') gt 0)}
                                            <button class="btn btn-info {if (!$CUSTOMER_PROFILE['CustomerCardId'])} setUpCard{/if}{if ($REGISTRATION_STATUS) and ($PASSWORD_STATUS)} authenticated {else} loginRequired{/if}" type="button" id="installExtension"><strong>{vtranslate('LBL_BUY',$QUALIFIED_MODULE)}${$EXTENSION_DETAIL->get('price')}</strong></button>
                                        {elseif !$EXTENSION_DETAIL->get('isprotected') && (($EXTENSION_DETAIL->get('price') eq 0) || ($EXTENSION_DETAIL->get('price') eq 'Free'))}
                                            <button class="btn btn-success {if ($REGISTRATION_STATUS) and ($PASSWORD_STATUS)}authenticated {else} loginRequired{/if}" type="button" id="installExtension"><strong>{vtranslate($MODULE_ACTION, $QUALIFIED_MODULE)}</strong></button>
                                        {elseif $EXTENSION_DETAIL->get('isprotected') && $IS_PRO && (($EXTENSION_DETAIL->get('price') eq 0) || ($EXTENSION_DETAIL->get('price') eq 'Free'))}
                                            <button class="btn btn-success {if ($REGISTRATION_STATUS) and ($PASSWORD_STATUS)}authenticated {else} loginRequired{/if}" type="button" id="installExtension"><strong>{vtranslate($MODULE_ACTION, $QUALIFIED_MODULE)}</strong></button>
                                        {/if}
                                    {/if}

                                    <button class="btn btn-info {if $MODULE_ACTION eq 'Installed'}{if $EXTENSION_MODULE_MODEL->get('extnType') eq 'language'}hide{/if}{else}hide{/if}" type="button" id="launchExtension" onclick="location.href='index.php?module={$EXTENSION_DETAIL->get('name')}&view=List'"><strong>{vtranslate('LBL_LAUNCH', $QUALIFIED_MODULE)}</strong></button>
                                    <a class="cancelLink" type="reset" id="declineExtension">{vtranslate('LBL_CANCEL', $MODULE)}</a>
                                </div>
                                <div class="clearfix"></div>
                            </div>
			</div>
                        <div class="tabbable margin0px" style="padding-bottom: 20px;">
                            <ul id="extensionTab" class="nav nav-tabs" style="margin-bottom: 0px; padding-bottom: 0px;">
                                <li class="active"><a href="#description" data-toggle="tab"><strong>{vtranslate('LBL_DESCRIPTION', $QUALIFIED_MODULE)}</strong></a></li>
                                <li><a href="#CustomerReviews" data-toggle="tab"><strong>{vtranslate('LBL_CUSTOMER_REVIEWS', $QUALIFIED_MODULE)}</strong></a></li>
                                <li><a href="#Author" data-toggle="tab"><strong>{vtranslate('LBL_PUBLISHER', $QUALIFIED_MODULE)}</strong></a></li>
                            </ul>
                            <div class="tab-content row-fluid boxSizingBorderBox" style="background-color: #fff; padding: 20px; border: 1px solid #ddd; border-top-width: 0px;">
                                <div class="tab-pane active" id="description">
                                    <div style="width:90%;padding: 0px 5%;">
                                        <div class="row-fluid">
                                            <ul id="imageSlider" class="imageSlider">
                                            {foreach $SCREEN_SHOTS as $key=>$SCREEN_SHOT}
                                                <li>
                                                    <div class="slide">
                                                        <img src="{$SCREEN_SHOT->get('screenShotURL')}" class="sliderImage"/>
                                                    </div>
                                                </li>
                                            {/foreach}
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="scrollableTab">
                                        <p>{$EXTENSION_DETAIL->get('description')}</p>
                                        <p></p>
                                    </div>
                                </div>
                                <div class="tab-pane row-fluid" id="CustomerReviews">
                                    <div class="row-fluid boxSizingBorderBox" style="padding-bottom: 15px;">
                                        <div class="span6">
                                            <div class="pull-left">
                                                <div style="font-size: 55px; padding: 20px 17px 0 0;">{$ON_RATINGS}</div>
                                            </div>
                                            <div class="pull-left">
                                                <span data-score="{$ON_RATINGS}" class="rating" data-readonly="true"></span>
                                                <div>out of 5</div>
                                                <div>({$ON_RATINGS} Reviews)</div>
                                            </div>
                                        </div>
                                        {if ($REGISTRATION_STATUS) and ($PASSWORD_STATUS)}
                                            <div class="span6">
                                                <div class="pull-right">
                                                    <button type="button" class="writeReview margin0px pull-right {if $MODULE_ACTION neq 'Installed'} hide{/if}">{vtranslate('LBL_WRITE_A_REVIEW', $QUALIFIED_MODULE)}</button>
                                                </div>
                                            </div>
                                        {/if}
                                    </div><hr>
                                    <div class="scrollableTab">
                                        <div class="customerReviewContainer" style="">
                                            {foreach $CUSTOMER_REVIEWS as $key=>$CUSTOMER_REVIEW}
                                                <div class="row-fluid" style="margin: 8px 0 15px;">
                                                    <div class="span3">
                                                        {assign var=ON_RATINGS value=$CUSTOMER_REVIEW['rating']}
                                                        <div data-score="{$ON_RATINGS}" class="rating" data-readonly="true"></div>
                                                        {assign var=CUSTOMER_INFO value= $CUSTOMER_REVIEW['customer']}
                                                        <div>
                                                            {assign var=REVIEW_CREATED_TIME value=$CUSTOMER_REVIEW['createdon']|replace:'T':' '}
                                                            {$CUSTOMER_INFO['firstname']}&nbsp;{$CUSTOMER_INFO['lastname']}
                                                        </div>
                                                        <div class="muted">{Vtiger_Util_Helper::formatDateTimeIntoDayString($REVIEW_CREATED_TIME)|substr:4}</div>
                                                    </div>
                                                    <div class="span9">{$CUSTOMER_REVIEW['comment']}</div>
                                                </div>
                                                <hr>
                                            {/foreach}
                                        </div>
                                   </div>
                                </div>
                                 <div class="tab-pane row-fluid" id="Author">
                                    <div class="scrollableTab">
                                        <div class="row-fluid">
                                            <div class="span6">
                                                {if !empty($AUTHOR_INFO['company'])}
                                                    <div class="font-x-x-large authorInfo">{$AUTHOR_INFO['company']}</div>
                                                {else}
                                                    <div class="font-x-x-large authorInfo">{$AUTHOR_INFO['firstname']}&nbsp;{$AUTHOR_INFO['lastname']}</div>
                                                {/if}
                                                    <div class="authorInfo">{$AUTHOR_INFO['phone']}</div>
                                                    <div class="authorInfo">{$AUTHOR_INFO['email']}</div>
                                                    <div class="authorInfo"><a href="{$AUTHOR_INFO['website']}" target="_blank">{$AUTHOR_INFO['website']}</a></div>
                                              </div>
                                              <div class="span6"></div>
                                         </div>
                                    </div>
                                </div>
                            </div>
                        </div>
		{else}
			<div class="row-fluid">{$ERROR_MESSAGE}</div>
		{/if}
                <div class="modal customerReviewModal hide">
                    <div class="modal-header contentsBackground">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h3>{vtranslate('LBL_CUSTOMER_REVIEW', $QUALIFIED_MODULE)}</h3>
                    </div>
                    <form class="form-horizontal customerReviewForm">
                        <input type="hidden" name="extensionId" value="{$EXTENSION_ID}" />
                        <div class="modal-body">
                            <div class="control-group">
                                <span class="control-label">
                                    <span class="redColor">*</span>&nbsp;
                                    {vtranslate('LBL_REVIEW', $QUALIFIED_MODULE)}
                                </span>
                                <div class="controls">
                                    <textarea name="customerReview" data-validation-engine="validate[required, funcCall[Vtiger_Base_Validator_Js.invokeValidation]]"></textarea>
                                </div>
                            </div>
                            <div class="control-group">
                                <span class="control-label">
                                    {vtranslate('LBL_RATE_IT', $QUALIFIED_MODULE)}
                                </span>
                                <div class="controls">
                                    <div class="span5 rating"></div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <div class="row-fluid">
                                <div class="span12">
                                    <div class="pull-right">
                                        <div class="pull-right cancelLinkContainer" style="margin-top:0px;">
                                                <a class="cancelLink" type="reset" data-dismiss="modal">{vtranslate('LBL_CANCEL', $MODULE)}</a>
                                        </div>
                                        <button class="btn btn-success" type="submit" name="saveButton"><strong>{vtranslate('LBL_SAVE', $MODULE)}</strong></button>
                                    </div>
                                </div>  
                            </div>
                        </div>
                    </form>
                </div>
            </div>
{/strip}