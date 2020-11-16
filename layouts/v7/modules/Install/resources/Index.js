/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

jQuery.Class('Install_Index_Js', {}, {

	registerEventForStep3: function () {
		jQuery('#recheck').on('click', function () {
			window.location.reload();
		});

		jQuery('input[name="step4"]').on('click', function (e) {
			var elements = jQuery('.no')
			if (elements.length > 0) {
				var msg = "いくつかの設定が異なりますが、このまま進めますか？";
				if (confirm(msg)) {
					jQuery('form[name="step3"]').submit();
					return true;
				} else {
					return false;
				}
			}
			jQuery('form[name="step3"]').submit();
		});
	},

	registerEventForStep4: function () {
		jQuery('input[type="text"]').not('.short').css('width', '210px');
		jQuery('input[type="password"]').css('width', '210px');

		jQuery('input[name="create_db"]').on('click', function () {
			var userName = jQuery('#root_user');
			var password = jQuery('#root_password');
			var classU = userName.attr('class');
			if (classU == 'hide')
				userName.removeClass('hide');
			else
				userName.addClass('hide');

			var classP = password.attr('class');
			if (classP == 'hide')
				password.removeClass('hide');
			else
				password.addClass('hide');
		});

		if (jQuery('input[name="create_db"]').prop('checked'))
		{
			jQuery('#root_user').removeClass("hide");
			jQuery('#root_password').removeClass("hide");
		}

		function clearPasswordError() {
			jQuery('#passwordError').html('');
		}

		function setPasswordError(message) {
			jQuery('#passwordError').html(message);
		}

		function checkRetypePassword() {
			var password = jQuery('input[name="password"]').val();
			var retypePassword = jQuery('input[name="retype_password"]').val();
			if (password !== retypePassword && password != '' && retypePassword != '') {
				setPasswordError('パスワードが正しくありません。再度入力してください。');
				return false;
			}
			return true;
		}

		function checkStrengthPassword() {
			var password = jQuery('input[name="password"]').val();
			if(password.lenght < 8) {
				setPasswordError('8文字以上のパスワードにしてください。');
				return false;
			}
			if(!/[a-z]/.test(password)
					|| !/[A-Z]/.test(password)
					|| !/([0-9])/.test(password)
					|| !/[!"#$%&'()\*\+\-\.,\/:;<=>?@\[\\\]^_`{|}~]/.test(password)
				) {
				setPasswordError('複雑なパスワードを指定してください（アルファベット大文字・小文字、数字、記号を含む8文字以上）');
				return false;
			}
			return true;
		}

		//This is not an event, we check if create_db is checked
		jQuery('input[name="retype_password"]').on('blur', function (e) {
			clearPasswordError();
			var isError = false;
			isError = !checkRetypePassword();
			isError = !checkStrengthPassword();
		});

		jQuery('input[name="password"]').on('blur', function (e) {
			clearPasswordError();
			isError = !checkRetypePassword();
			isError = !checkStrengthPassword();
		});

		jQuery('input[name="retype_password"]').on('keypress', function (e) {
			clearPasswordError();
			isError = !checkRetypePassword();
			isError = !checkStrengthPassword();
		});

		jQuery('input[name="step5"]').on('click', function () {
			var error = false;
			var validateFieldNames = ['db_hostname', 'db_username', 'db_name', 'password', 'retype_password', 'lastname', 'admin_email'];
			for (var fieldName in validateFieldNames) {
				var field = jQuery('input[name="' + validateFieldNames[fieldName] + '"]');
				if (field.val() == '') {
					field.addClass('error').focus();
					error = true;
					break;
				} else {
					field.removeClass('error');
				}
			}

			var createDatabase = jQuery('input[name="create_db"]:checked');
			if (createDatabase.length > 0) {
				var dbRootUser = jQuery('input[name="db_root_username"]');
				if (dbRootUser.val() == '') {
					dbRootUser.addClass('error').focus();
					error = true;
				} else {
					dbRootUser.removeClass('error');
				}
			}
			var password = jQuery('#passwordError');
			if (password.html() != '') {
				error = true;
			}

			var emailField = jQuery('input[name="admin_email"]');
			var regex = /^[_/a-zA-Z0-9*]+([!"#$%&'()*+,./:;<=>?\^_`{|}~-]?[a-zA-Z0-9/_/-])*@[a-zA-Z0-9]+([\_\-\.]?[a-zA-Z0-9]+)*\.([\-\_]?[a-zA-Z0-9])+(\.?[a-zA-Z0-9]+)?$/;
			if (!regex.test(emailField.val()) && emailField.val() != '') {
				var invalidEmailAddress = true;
				emailField.addClass('error').focus();
				error = true;
			} else {
				emailField.removeClass('error');
			}

			if (error) {
				var content;
				if (invalidEmailAddress) {
					content = '<div class="col-sm-12">' +
							'<div class="alert errorMessageContent">' +
							'<button class="close" data-dismiss="alert" type="button">x</button>' +
							'メールアドレスが正しくありません。' +
							'</div>' +
							'</div>';
				} else {
					content = '<div class="col-sm-12">' +
							'<div class="alert errorMessageContent">' +
							'<button class="close" data-dismiss="alert" type="button">x</button>' +
							'必須の入力がされていません。' +
							'</div>' +
							'</div>';
				}
				jQuery('#errorMessage').html(content).removeClass('hide')
			} else {
				jQuery('form[name="step4"]').submit();
			}
		});
	},

	registerEventForStep5: function () {
		jQuery('input[name="step6"]').on('click', function () {
			var error = jQuery('#errorMessage');
			if (error.length) {
				alert('Please resolve the error before proceeding with the installation');
				return false;
			} else {
				jQuery('form[name="step5"]').submit().hide();
			}
		});
	},

	registerEventForStep6: function () {
		jQuery('input[name="step7"]').on('click', function () {
			var lastname = jQuery('input[name="lastname"]').val();
			if (lastname == "") {
				alert('氏名を入力してください。');
				return;
			}
			var email = jQuery('input[name="email"]').val();
			if (email == "") {
				alert('メールアドレスを入力してください。');
				return;
			}
			var reg_survey = "";
			jQuery('input[name="reg_survey\[\]"]:checked').each(function(){
				$this = jQuery(this);
				reg_survey += $this.val();
			});
			if (reg_survey == "") {
				alert('一つ以上チェックを付けてください。');
				return;
			}
			jQuery('#progressIndicator').removeClass('hide').addClass('show');
			jQuery('form[name="step6"]').submit().hide();
		});
	},

	registerEvents: function () {
		jQuery('input[name="back"]').on('click', function () {
			var createDatabase = jQuery('input[name="create_db"]:checked');
			if (createDatabase.length > 0) {
				jQuery('input[name="create_db"]').removeAttr('checked');
			}
			window.history.back();
		});
		this.registerEventForStep3();
		this.registerEventForStep4();
		this.registerEventForStep5();
		this.registerEventForStep6();
	}
});
jQuery(document).ready(function() {
	var indexInstance = new Install_Index_Js();
	indexInstance.registerEvents();
});
