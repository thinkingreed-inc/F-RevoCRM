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

	function arrayBufferToBase64Url(buffer) {
		if (!buffer) {
			return null;
		}
		var bytes = new Uint8Array(buffer);
		var binary = '';
		for (var i = 0; i < bytes.length; i++) {
			binary += String.fromCharCode(bytes[i]);
		}
		return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
	}

	// PublicKeyCredential.toJSON() は古いブラウザに無いため、サーバーが読む形に自前で詰める
	function serializeCredential(credential) {
		var response = credential.response;
		return {
			id: credential.id,
			rawId: arrayBufferToBase64Url(credential.rawId),
			type: credential.type,
			response: {
				clientDataJSON: arrayBufferToBase64Url(response.clientDataJSON),
				authenticatorData: arrayBufferToBase64Url(response.authenticatorData),
				signature: arrayBufferToBase64Url(response.signature),
				userHandle: arrayBufferToBase64Url(response.userHandle)
			},
			clientExtensionResults: credential.getClientExtensionResults ? credential.getClientExtensionResults() : {},
			authenticatorAttachment: credential.authenticatorAttachment || null
		};
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
					$form.find('input[name="credential"]').val(JSON.stringify(serializeCredential(credential)));
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
