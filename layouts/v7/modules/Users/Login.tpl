{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}
{* modules/Users/views/Login.php *}

{strip}
	<style>
		body {
			background: url(layouts/v7/resources/Images/login-background.jpg);
			background-position: center;
			background-size: cover;
			width: 100%;
			background-repeat: no-repeat;
		}
		hr {
			margin-top: 15px;
			background-color: #7C7C7C;
			height: 2px;
			border-width: 0;
		}

		.app-footer p {
			margin-top: 0px;
		}

		.footer {
			background-color: #fbfbfb;
			height:26px;
		}
		
		.bar {
			position: relative;
			display: block;
			width: 100%;
		}
		.bar:before, .bar:after {
			content: '';
			width: 0;
			bottom: 1px;
			position: absolute;
			height: 1px;
			background: #35aa47;
			transition: all 0.2s ease;
		}
		.bar:before {
			left: 50%;
		}
		.bar:after {
			right: 50%;
		}
	</style>

	<span class="app-nav"></span>
	<div class="container-fluid loginPageContainer">
		<div class="col-lg-5 col-md-12 col-sm-12 col-xs-12">
			<div class="loginDiv login-widgetHeight">
				<img class="img-responsive user-logo" src="{$COMPANY_LOGO->get('imagepath')}" alt="{$COMPANY_LOGO->get('alt')}">
				<div>
					<span class="{if !$ERROR}hide{/if} failureMessage" id="validationMessage">{vtranslate($MESSAGE,'Users')}</span>
					<span class="{if !$MAIL_STATUS}hide{/if} successMessage">{vtranslate($MESSAGE,'Users')}</span>
				</div>

				<div id="loginFormDiv">
					<form class="form-horizontal" method="POST" action="index.php">
						<input class="login-input" type="hidden" name="module" value="Users"/>
						<input class="login-input" type="hidden" name="action" value="Login"/>
						<div class="group">
							<input class="login-input" id="username" type="text" name="username" placeholder="{vtranslate('User Name','Users')}">
							<span class="bar"></span>
							<label class="login-label">{vtranslate('User Name','Users')}</label>
						</div>
						<div class="group">
							<input class="login-input" id="password" type="password" name="password" placeholder="{vtranslate('Password','Users')}" >
							<span class="bar"></span>
							<label class="login-label">{vtranslate('Password','Users')}</label>
						</div>
						<div class="group">
							<button type="submit" class="login-button login-buttonBlue">{vtranslate('LBL_LOGIN','Users')}</button><br>
							<a class="forgotPasswordLink" style="color: #15c;">{vtranslate('LBL_FORGET_PASSWORD','Users')}</a>
						</div>
					</form>
				</div>

				<div id="forgotPasswordDiv" class="hide">
					<form class="form-horizontal" action="forgotPassword.php" method="POST">
						<div class="group">
							<input class="login-input" id="fusername" type="text" name="username" placeholder="{vtranslate('User Name','Users')}" >
							<span class="bar"></span>
							<label class="login-label">{vtranslate('User Name','Users')}</label>
						</div>
						<div class="group">
							<input class="login-input" id="email" type="email" name="emailId" placeholder="{vtranslate('LBL_MAILADDRESS','Users')}" >
							<span class="bar"></span>
							<label class="login-label">{vtranslate('LBL_MAILADDRESS','Users')}</label>
						</div>
						<div class="group">
							<button type="submit" class="login-button login-buttonBlue forgot-submit-btn">{vtranslate('LBL_SUBMIT','Users')}</button><br>
							<span>{vtranslate('LBL_SEND_PASSWORD','Users')}<a class="forgotPasswordLink pull-right" style="color: #15c;">{vtranslate('LBL_BACK_TO_LOGIN','Users')}</a></span>
						</div>
					</form>
				</div>
			</div>
		</div>

		<div class="col-lg-1 hidden-xs hidden-sm hidden-md">
		<div class="separatorDiv"></div>
		</div>

		<div class="col-lg-5 hidden-xs hidden-sm hidden-md">
			<div class="marketingDiv widgetHeight">
				{if $JSON_DATA}
					<div class="scrollContainer">
						{assign var=ALL_BLOCKS_COUNT value=0}
						{foreach key=BLOCK_NAME item=BLOCKS_DATA from=$JSON_DATA}
							{if $BLOCKS_DATA}
								<div>
									<h4>{$BLOCKS_DATA[0].heading}</h4>
									<ul class="bxslider">
										{foreach item=BLOCK_DATA from=$BLOCKS_DATA}
											<li class="slide">
												{assign var=ALL_BLOCKS_COUNT value=$ALL_BLOCKS_COUNT+1}
												{if $BLOCK_DATA.image}
													<div class="col-lg-3" style="min-height: 100px;"><img src="{$BLOCK_DATA.image}" style="width: 100%;height: 100%;margin-top: 10px;"/></div>
													<div class="col-lg-9">
												{else}
													<div class="col-lg-12">
												{/if}
												<div title="{$BLOCK_DATA.summary}">
													<h3><b>{$BLOCK_DATA.displayTitle}</b></h3>
													{$BLOCK_DATA.displaySummary}<br><br>
													{$BLOCK_DATA.pubDate}<br>
													<a href="{$BLOCK_DATA.url}" target="_blank"><u>{$BLOCK_DATA.urlalt}</u></a>
													<br><br>
												</div>
												{if $BLOCK_DATA.image}
													</div>
												{else}
													</div>
												{/if}
											</li>
										{/foreach}
									</ul>
								</div>
								{if $ALL_BLOCKS_COUNT neq $DATA_COUNT}
									<br>
									<hr>
								{/if}
							{/if}
						{/foreach}
					</div>
				{else}
					<div class="inActiveImgDiv">
						<div>
							<h4>{vtranslate("LBL_F-Revo_Notice","Users")}</h4>
						</div>
						<a href="https://f-revocrm.jp" target="_blank" style="margin-right: 25px;"><img src="https://f-revocrm.jp/frevowp/wp-content/uploads/2021/09/image_frevo_top.png" style="width: 85%; height: 100%; margin-top: 25px;"/></a>
					</div>
				{/if}
				</div>
			</div>
		</div>

		<script>
			jQuery(document).ready(function () {
				var validationMessage = jQuery('#validationMessage');
				var forgotPasswordDiv = jQuery('#forgotPasswordDiv');

				var loginFormDiv = jQuery('#loginFormDiv');
				loginFormDiv.find('#password').focus();

				loginFormDiv.find('a').click(function () {
					loginFormDiv.toggleClass('hide');
					forgotPasswordDiv.toggleClass('hide');
					validationMessage.addClass('hide');
				});

				forgotPasswordDiv.find('a').click(function () {
					loginFormDiv.toggleClass('hide');
					forgotPasswordDiv.toggleClass('hide');
					validationMessage.addClass('hide');
				});

				loginFormDiv.find('button').on('click', function () {
					var username = loginFormDiv.find('#username').val();
					var password = jQuery('#password').val();
					var result = true;
					var errorMessage = '';
					if (username === '' & password === '') {
						errorMessage = "{vtranslate('LBL_ENTER_USERNAME_AND_PASSWORD','Users')}";
						result = false;
					} else if (username === '') {
						errorMessage = "{vtranslate('LBL_USER_NAME','Users')}";
						result = false;
					} else if (password === '') {
						errorMessage = "{vtranslate('LBL_ENTER_PASSWORD','Users')}";
						result = false;
					}
					if (errorMessage) {
						validationMessage.removeClass('hide').text(errorMessage);
					}
					return result;
				});

				forgotPasswordDiv.find('button').on('click', function () {
					var username = jQuery('#forgotPasswordDiv #fusername').val();
					var email = jQuery('#email').val();

					var email1 = email.replace(/^\s+/, '').replace(/\s+$/, '');
					var emailFilter = /^[^@]+@[^@.]+\.[^@]*\w\w$/;
					var illegalChars = /[\(\)\<\>\,\;\:\\\"\[\]]/;

					var result = true;
					var errorMessage = '';
					if (username === '' & (!emailFilter.test(email1) || email == '')) {
						errorMessage = '{vtranslate('LBL_ENTER_USERNAME_AND_MAILADDRESS','Users')}";';
						result = false;
					} else if (username === '') {
						errorMessage = '{vtranslate('LBL_ENTER_USERNAME','Users')}";';
						result = false;
					} else if (!emailFilter.test(email1) || email == '') {
						errorMessage = '{vtranslate('LBL_ENTER_MAILADDRESS','Users')}";';
						result = false;
					} else if (email.match(illegalChars)) {
						errorMessage = '{vtranslate('LBL_INVALID_MAILADDRESS','Users')}";';
						result = false;
					}
					if (errorMessage) {
						validationMessage.removeClass('hide').text(errorMessage);
					}
					return result;
				});
				jQuery('input').blur(function (e) {
					var currentElement = jQuery(e.currentTarget);
					if (currentElement.val()) {
						currentElement.addClass('used');
					} else {
						currentElement.removeClass('used');
					}
				});

				var ripples = jQuery('.ripples');
				ripples.on('click.Ripples', function (e) {
					jQuery(e.currentTarget).addClass('is-active');
				});

				ripples.on('animationend webkitAnimationEnd mozAnimationEnd oanimationend MSAnimationEnd', function (e) {
					jQuery(e.currentTarget).removeClass('is-active');
				});
				loginFormDiv.find('#username').focus();

				var slider = jQuery('.bxslider').bxSlider({
					auto: true,
					pause: 4000,
					nextText: "",
					prevText: "",
					autoHover: true
				});
				jQuery('.bx-prev, .bx-next, .bx-pager-item').live('click',function(){ slider.startAuto(); });
				jQuery('.bx-wrapper .bx-viewport').css('background-color', 'transparent');
				jQuery('.bx-wrapper .bxslider li').css('text-align', 'left');
				jQuery('.bx-wrapper .bx-pager').css('bottom', '-15px');

				var params = {
					theme		: 'dark-thick',
					setHeight	: '100%',
					advanced	:	{
										autoExpandHorizontalScroll:true,
										setTop: 0
									}
				};
				jQuery('.scrollContainer').mCustomScrollbar(params);
			});
		</script>
		</div>
	{/strip}