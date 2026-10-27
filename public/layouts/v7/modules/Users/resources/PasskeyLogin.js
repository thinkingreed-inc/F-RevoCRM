/**
 * ログイン画面の「パスキーでログイン」ボタン。
 * ID/PW 欄の入力は見ない。チャレンジを取り、discoverable credential を選ばせ、
 * 結果を #passkeyOnlyLoginForm に入れて PasskeyLogin アクションへ POST する。
 * ログイン画面には言語ファイルの JS 文言が無いので、文言はボタンの data 属性から読む。
 */
(function ($) {
	function base64ToUint8Array(str) {
		var binaryString = atob(str);
		var bytes = new Uint8Array(binaryString.length);
		for (var i = 0; i < binaryString.length; i++) {
			bytes[i] = binaryString.charCodeAt(i);
		}
		return bytes;
	}

	$(function () {
		var $btn = $('#passkeyOnlyLoginBtn');
		var $form = $('#passkeyOnlyLoginForm');
		var $message = $('#validationMessage');
		if (!$btn.length || !$form.length) {
			return;
		}

		if (!window.PublicKeyCredential || !navigator.credentials || !navigator.credentials.get) {
			$btn.prop('disabled', true);
			return;
		}

		function showError(key) {
			$message.removeClass('hide').text($btn.data(key));
			$btn.prop('disabled', false);
		}

		$btn.on('click', function () {
			$btn.prop('disabled', true);
			$message.addClass('hide');

			$.ajax({
				url: 'index.php',
				type: 'POST',
				dataType: 'json',
				data: { module: 'Users', action: 'ChallengePasskeyAjax' }
			}).done(function (response) {
				if (!response || !response.success) {
					showError('msgError');
					return;
				}
				var challenge;
				try {
					challenge = base64ToUint8Array(response.result);
				} catch (e) {
					showError('msgError');
					return;
				}
				navigator.credentials.get({
					publicKey: {
						challenge: challenge,
						allowCredentials: [],
						userVerification: 'required',
						timeout: 60000
					}
				}).then(function (credential) {
					$form.find('input[name="credential"]').val(JSON.stringify(credential));
					$form.get(0).submit();
				}).catch(function (error) {
					if (error && error.name === 'NotAllowedError') {
						showError('msgCanceled');
					} else if (error && error.name === 'AbortError') {
						showError('msgAborted');
					} else {
						showError('msgError');
					}
				});
			}).fail(function () {
				showError('msgError');
			});
		});
	});
})(jQuery);
