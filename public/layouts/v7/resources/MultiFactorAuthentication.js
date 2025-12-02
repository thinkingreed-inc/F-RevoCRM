window.FR_MultiFactorAuthentication_Js = {
    storageKey: 'fr_mfa_preference',
    cookieKey: 'fr_mfa_preference',
    elements: {},

    /**
     * MFA設定の保存形式を正規化する。
     * 旧形式や文字列入力を統一し、内部処理できる共通フォーマットに変換する。
     */
    normalizePreference: function (value) {
        if (!value) return null;

        let parsed = value;
        if (typeof value === 'string') {
            try { parsed = JSON.parse(value); }
            catch {
                return { method: value, confirmed: true };
            }
        }

        if (typeof parsed !== 'object') return null;

        const method = parsed.method || parsed.value || parsed.type;
        return method ? { method, confirmed: parsed.confirmed === true } : null;
    },

    /**
     * Base64 文字列を Uint8Array に変換する。
     * WebAuthn API が要求するバイナリ形式を生成するために使用。
     */
    base64ToUint8Array: function(str) {
        var binaryString = atob(str);
        var bytes = new Uint8Array(binaryString.length);
        for (var i = 0; i < binaryString.length; i++) {
            bytes[i] = binaryString.charCodeAt(i);
        }
        return bytes;
    },

    /**
     * ArrayBuffer を Base64 文字列に変換する。
     * WebAuthn API のレスポンスを文字列形式に変換するために使用。
     */
    arrayBufferToBase64: function(buffer) {
        var binary = '';
        var bytes = new Uint8Array(buffer);
        var len = bytes.byteLength;
        for (var i = 0; i < len; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return btoa(binary);
    },

    /**
     * preference 情報を localStorage または cookie に保存する。
     * 利用可能なストレージを自動判定して保存処理を行う。
     */
    setPreference: function (value) {
        const pref = this.normalizePreference(value);
        if (!pref?.method) return this.removePreference();

        const payload = JSON.stringify({
            method: pref.method,
            confirmed: pref.confirmed === true
        });

        try {
            localStorage.setItem(this.storageKey, payload);
        } catch {
            document.cookie = `${this.cookieKey}=${encodeURIComponent(payload)}; path=/; max-age=${86400 * 365}`;
        }
    },

    /**
     * 保存済みの MFA preference を取得する。
     * localStorage → cookie の優先順で情報を読み込み、統一形式に変換して返す。
     */
    getPreference: function () {
        let stored = null;

        try {
            stored = localStorage.getItem(this.storageKey);
        } catch {}

        if (!stored) {
            const name = `${this.cookieKey}=`;
            const cookie = document.cookie.split(';').map(v => v.trim());
            const found = cookie.find(entry => entry.indexOf(name) === 0);
            if (found) stored = decodeURIComponent(found.substring(name.length));
        }

        return this.normalizePreference(stored);
    },

    /**
     * preference 情報を完全に削除する。
     * localStorage と cookie の両方をクリアして初期状態に戻す。
     */
    removePreference: function () {
        try { localStorage.removeItem(this.storageKey); } catch {}
        document.cookie = `${this.cookieKey}=; path=/; max-age=0`;
    },
    cacheDom: function () {
        this.elements = {
            passkeyForm: document.getElementById('passkeyForm'),
            totpForm: document.getElementById('totpForm'),
            divider: document.getElementById('mfa_divider'),
            checkbox: document.getElementById('remember_mfa_checkbox'),
            label: document.getElementById('remember_mfa_label'),
            resetButton: document.getElementById('reset_mfa_preference_btn'),
            wrapper: document.getElementById('remember_mfa_wrapper'),
            passkeyHidden: document.getElementById('remember_mfa_passkey'),
            totpHidden: document.getElementById('remember_mfa_totp')
        };
    },

    /**
     * ログイン失敗メッセージが画面に存在するか判定する。
     * MFA画面の初期表示切替に利用される重要な判定処理。
     */
    detectLoginFailure: function () {
        const node = document.querySelector('.failureMessage');
        return !!node?.textContent.trim();
    },

    /**
     * ログイン結果に応じて MFA の初期表示を制御する。
     * 失敗時は保持中の設定の確認有無により画面切替、成功時は preference を適用する。
     */
    handleInitialState: function () {
        const failed = this.detectLoginFailure();
        let pref = this.getPreference();

        // 初回ログイン成功時に confirmed を true に確定させる
        if (!failed && pref?.method && !pref.confirmed) {
            this.setPreference({ method: pref.method, confirmed: true });
            pref = this.getPreference();
        }

        if (failed) {
            pref?.confirmed ? this.applyPreferredMfa() : this.clearPreferredMfa(true);
            return;
        }

        this.applyPreferredMfa();
    },

     /**
     * MFA 画面の UI イベントを登録する。
     * チェックボックス、リセットボタン、各フォームの submit に応じて設定を保存する。
     */
    bindEvents: function () {
        const refs = this.elements;
        const self = this;

        // 保存チェック変更時の処理
        refs.checkbox?.addEventListener('change', () => {
            refs.checkbox.checked ? self.updateRememberHidden() : self.clearPreferredMfa(true);
        });

        // 方式選択リセット
        refs.resetButton?.addEventListener('click', () => self.clearPreferredMfa(true));

        // TOTP 送信時
        $(document).on('submit', '#totpForm', () =>
            self.persistPreferredMfa('totp', { applyNow: false })
        );

        // Passkey 送信時
        $(document).on('submit', '#passkeyForm', () =>
            self.persistPreferredMfa('passkey', { applyNow: false })
        );
    },

    /**
     * WebAuthn Passkey 発行（登録）要求を生成する。
     * サーバから取得した challenge を元に WebAuthn API の create() を実行する。
     */
    createCredentials: function (challenge) {
        var userid = document.querySelector("#passkeyForm input[name='userid']")?.value || '';
        var username = document.querySelector("#passkeyForm input[name='username']")?.value || '';
        var hostname = document.querySelector("#passkeyForm input[name='hostname']")?.value || location.hostname;
        return navigator.credentials.create({
            publicKey: {
                challenge: challenge,
                rp: {
                    id: hostname,
                    name: "F-RevoCRM",
                },
                user: {
                    id: Uint8Array.from( userid, function(c) { return c.charCodeAt(0); }),
                    name: username,
                    displayName: username,
                },
                pubKeyCredParams: [
                    { type: "public-key", alg: -7 },
                    { type: "public-key", alg: -8 },
                    { type: "public-key", alg: -257 }
                ],
                excludeCredentials: [],
                authenticatorSelection: {
                    authenticatorAttachment: "platform",
                    requireResidentKey: true,
                    userVerification: "required"
                },
                timeout: 180000,
                hints: ["client-device"]
            }
        });
    },

    /**
     * Passkey（WebAuthn）登録イベントを設定する。
     * ボタン押下 → challenge 取得 → WebAuthn 実行 → サーバ登録 の流れを処理する。
     */
    registerPasskeyEvents: function() {
        var self = this;
        $(document).on("click", "#passkeyAdd", function(e) {
            e.preventDefault();
            const $btn = $(this).prop('disabled', true);

            $.post('index.php', {
                module: 'Users',
                action: 'ChallengePasskeyAjax'
            }, function (response) {
                if (!response.success) return $btn.prop('disabled', false);

                const challengeBytes = self.base64ToUint8Array(response.result);
                $('input[name="challenge"]').val(challengeBytes);

                if (!navigator.credentials?.create)
                    return console.error('WEBAUTHN ERROR'), $btn.prop('disabled', false);

                self.createCredentials(challengeBytes)
                    .then(cred => {
                        var serverPayload = {
                            id: cred.id,
                            type: cred.type,
                            rawId: self.arrayBufferToBase64(cred.rawId),
                            response: {
                                clientDataJSON: self.arrayBufferToBase64(cred.response.clientDataJSON),
                                attestationObject: self.arrayBufferToBase64(cred.response.attestationObject)
                            },
                            clientExtensionResults: cred.clientExtensionResults
                        };
                        // #passkeyFormをpostする
                        var form = $("#passkeyForm");
                        app.request.post({
                            url: 'index.php',
                            data: {
                                'module': 'Users',
                                'action': 'SaveMultiFactorAuthenticationAjax',
                                'challenge': challengeBytes,
                                'credential': JSON.stringify(serverPayload),
                                'device_name': form.find('[name="device_name"]').val(),
                                'userid': form.find('[name="userid"]').val(),
                                'username': form.find('[name="username"]').val(),
                                'type': 'passkey'
                            }
                        }).then((err, data) => {
                            if (err) {
                                app.helper.showErrorNotification({ message: err });
                                return $btn.prop('disabled', false);
                            }
                            if (data.login === 'true') return location.href = data.link;

                            app.helper.showSuccessNotification({ message: app.vtranslate('JS_ADD_MULTI_FACTOR_AUTHENTICATION_FINISH') });
                            app.helper.hideModal();
                            location.reload();
                        });
                    })
                    .catch(() => $btn.prop('disabled', false));

            }, 'json');
        });
    },

    /**
     * TOTP（ワンタイムパスワード）登録イベントを処理する。
     * 入力した TOTP 情報をサーバへ送信し、登録またはログイン処理を行う。
     */
    registerTotpEvents: function() {
        $(document).on("click", "#totpAdd", function(e) {
            e.preventDefault();
            var form = $("#totpForm");
            var params = {
                url: 'index.php',
                data: {
                    'module': 'Users',
                    'action': 'SaveMultiFactorAuthenticationAjax',
                    'secret' : form.find('[name="secret"]').val(),
                    'view' : form.find('[name="view"]').val(),
                    'userid' : form.find('[name="userid"]').val(),
                    'device_name': form.find('[name="device_name"]').val(),
                    'totp_code': form.find('[name="totp_code"]').val(),
                    'type' : 'totp',
                }
            };
            app.request.post(params).then(function(err, data) {
                if (err === null) {
                    if( data.login === 'true' ) {
                        window.location.href = data.link;
                    } else{
                        app.helper.showSuccessNotification({'message': app.vtranslate('JS_ADD_MULTI_FACTOR_AUTHENTICATION_FINISH', 'Users')});
                        app.helper.hideModal();
                        location.reload();
                    }
                } else {
                    app.helper.showErrorNotification({'message': err});
                }
            });
        });
    },

    /**
     * QRコードを生成し TOTP 用の登録情報を表示する。
     * Google Authenticator / Authy などのアプリ登録に利用される。
     */
    createQRCode: function( elementId, qrcodeURL) {
        var qrcode = new QRCode(document.getElementById(elementId), {
            text: qrcodeURL,
            cls: "img-responsive",
            width: 200,
            height: 200,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
        return qrcode;
    },

    /**
     * 登録済み MFA 認証情報（Passkey / TOTP）を削除するイベント。
     * 管理画面からユーザーがデバイス登録を削除する場合に使用される。
     */
    registerDeleteCredentialEvent: function(element) {
		$(document).on('click', '.deleteCredential', function(){
			var $this = $(this);
            $this.prop('disabled', true);
			var credentialId = $(this).data('id');
			if (confirm(app.vtranslate('JS_CONFIRM_DELETE_CREDENTIAL', 'Users'))) {
				$.ajax({
					url: 'index.php',
					type: 'POST',
					data: {
						recordid: $('input[name="record_id"]').val(),
						module: 'Users',
						action: 'DeleteAjax',
						mode: 'credential',
						credentialid: credentialId,
					},
					success: function(response) {
						if (response.success) {
							alert(app.vtranslate('JS_USER_CREDENTIAL_DELETE_SUCCESS', 'Users'));
							location.reload();
						} else {
							alert(app.vtranslate('JS_USER_CREDENTIAL_DELETE_FAILED', 'Users'));
						}
					},
					error: function() {
						alert(app.vtranslate('JS_USER_CREDENTIAL_DELETE_FAILED', 'Users'));
					}
				});
			}
			$this.prop('disabled', false);
		});
	},

    /**
     * Passkey（WebAuthn）でログインする処理。
     * challenge 取得 → WebAuthn get() 実行 → サーバ送信 → ログイン確定 の流れを処理する。
     */
    authenticationPasskeyEvent: function() {
        var self = this;
        $(document).on("click", "#passkeyLoginBtn", function(e){
            var $this = $(this);
            $this.prop('disabled', true);
            $.ajax({
                url: 'index.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    module: 'Users',
                    action: 'ChallengePasskeyAjax',
                },
                success: function(response) {
                    if (response.success) {
                        var challenge = response.result;
                        var challengeBytes = self.base64ToUint8Array(challenge);
                        $('input[name="challenge"]').val(challengeBytes);

                        if (!navigator.credentials || !navigator.credentials.create) {
                            app.helper.showErrorNotification({'message': app.vtranslate('JS_WEBAUTHN_ERROR')});
                            $(this).prop('disabled', false);
                            return;
                        }
                        try {
                            navigator.credentials.get({
                                publicKey: {
                                    challenge: challengeBytes,
                                    allowCredentials: response.allowCredentials,
                                    userVerification: 'required'
                                }
                            }).then(function(credential) {
                                var credentialData = JSON.stringify(credential);
                                $('input[name="credential"]').val(credentialData);
                                $('#passkeyForm').submit();
                            }).catch(function(error) {
                                if (error.name === 'NotAllowedError') {
                                    app.helper.showErrorNotification({'message': app.vtranslate('JS_MULTI_FACTOR_AUTHENTICATION_USER_CHANCELED')});
                                } else if (error.name === 'AbortError') {
                                    app.helper.showErrorNotification({'message': app.vtranslate('JS_MULTI_FACTOR_AUTHENTICATION_CHANCELED')});
                                } else {
                                    app.helper.showErrorNotification({'message': app.vtranslate('JS_WEBAUTHN_ERROR')});
                                }
                                
                            });
                        } catch (error) {
                            app.helper.showErrorNotification({'message': app.vtranslate('JS_WEBAUTHN_ERROR')});
                        }
                    }
                    $this.prop('disabled', false);
                }
            });
        });
    },
    
    /**
     * ユーザーが選択した MFA（Passkey / TOTP）設定を保存する。
     * チェックボックス ON の場合は preference を更新し、OFF の場合は削除する。
     * applyNow=true の場合は即座に UI を切り替える。
     */
    persistPreferredMfa: function (method, opts) {
        if (!this.elements.checkbox) this.cacheDom();
        const checkbox = this.elements.checkbox;
        if (!checkbox) return;

        if (checkbox.checked) {
            const exist = this.getPreference();
            const confirmed = !!(exist && exist.method === method && exist.confirmed);
            this.setPreference({ method, confirmed });
        } else {
            this.removePreference();
        }

        opts?.applyNow ? this.applyPreferredMfa() : this.updateRememberHidden();
    },

    /**
     * UI に適用する MFA 表示切り替え処理。
     * 記憶済みの MFA がある場合はその方式のみを表示、
     * なければ Passkey / TOTP 両方を表示する。
     */
    applyPreferredMfa: function (opts) {
        const refs = this.elements;
        if (!refs.passkeyForm || !refs.totpForm) this.cacheDom();

        const pref = opts?.forceShowAll ? null : this.getPreference();
        const method = pref?.confirmed ? pref.method : null;

        const show = el => el && el.style.removeProperty('display');
        const hide = el => el && (el.style.display = 'none');

        if (method === 'passkey') {
            show(refs.passkeyForm);
            hide(refs.totpForm);
            hide(refs.divider);
            refs.checkbox.checked = true;
            hide(refs.label);
            refs.resetButton.style.display = 'inline';
            refs.wrapper.style.justifyContent = 'flex-end';
        } else if (method === 'totp') {
            show(refs.totpForm);
            hide(refs.passkeyForm);
            hide(refs.divider);
            refs.checkbox.checked = true;
            hide(refs.label);
            refs.resetButton.style.display = 'inline';
            refs.wrapper.style.justifyContent = 'flex-end';
        } else {
            show(refs.passkeyForm);
            show(refs.totpForm);
            refs.divider?.style.removeProperty('display');
            refs.checkbox.checked = false;
            refs.label.style.display = 'flex';
            refs.resetButton.style.display = 'none';
            refs.wrapper.style.justifyContent = 'center';
        }

        this.updateRememberHidden();
    },

    /**
     * hidden フィールドに「記憶する」状態を反映する。
     * '1' = 記憶する、'0' = 記憶しない。
     * フォーム送信時のサーバ側判定に利用される。
     */
    updateRememberHidden: function () {
        if (!this.elements.passkeyHidden) this.cacheDom();
        const refs = this.elements;

        const val = refs.checkbox?.checked ? '1' : '0';
        refs.passkeyHidden.value = val;
        refs.totpHidden.value = val;
    },

    /**
     * 保存済み MFA preference を削除し UI を初期状態へ戻す。
     * force=true の場合は強制的に両方式を表示する。
     */
    clearPreferredMfa: function (force) {
        this.removePreference();
        if (!this.elements.checkbox) this.cacheDom();

        this.elements.checkbox.checked = false;
        this.applyPreferredMfa(force ? { forceShowAll: true } : undefined);
        this.updateRememberHidden();
    },

    /**
     * 初期化処理：
     * 1. DOM キャッシュ
     * 2. 各イベント登録
     * 3. ログイン失敗判定 → 表示切替処理実行
     */
    init: function () {
        this.cacheDom();
        this.bindEvents();
        this.handleInitialState();
    }
};
jQuery(function () {
    window.FR_MultiFactorAuthentication_Js.init();
});
