window.Settings_Users_MultiFactorAuthentication_Js = {
    base64ToUint8Array: function(str) {
        var binaryString = atob(str);
        var bytes = new Uint8Array(binaryString.length);
        for (var i = 0; i < binaryString.length; i++) {
            bytes[i] = binaryString.charCodeAt(i);
        }
        return bytes;
    },

    arrayBufferToBase64: function(buffer) {
        var binary = '';
        var bytes = new Uint8Array(buffer);
        var len = bytes.byteLength;
        for (var i = 0; i < len; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return btoa(binary);
    },

    createCredentials: function(challenge) {
        var userid = $("#passkeyForm input[name='userid']").val();
        var username = $("#passkeyForm input[name='username']").val();
        var hostname = $("#passkeyForm input[name='hostname']").val();
        return navigator.credentials.create({
            publicKey: {
                challenge: challenge,
                rp: {
                    id: hostname,
                    name: "F-revocrm",
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

    registerPasskeyEvents: function() {
        var self = this;
        $(document).on("click", "#passkeyAdd", function(e) {
            e.preventDefault();
            $.ajax({
                url: 'index.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    module: 'Users',
                    action: 'ChallengePasskeyAjax'
                },
                success: function(response) {
                    if (response.success) {
                        var challenge = response.result;
                        var challengeBytes = self.base64ToUint8Array(challenge);
                        $('input[name="challenge"]').val(challengeBytes);

                        if (!navigator.credentials || !navigator.credentials.create) {
                            console.error(app.vtranslate('JS_WEBAUTHN_ERROR', 'Users'));
                            return;
                        }

                        Settings_Users_MultiFactorAuthentication_Js.createCredentials(challengeBytes).then(function(cred) {
                            var clientDataJSON = self.arrayBufferToBase64(cred.response.clientDataJSON);
                            var attestationObject = self.arrayBufferToBase64(cred.response.attestationObject);
                            var rawId = self.arrayBufferToBase64(cred.rawId);

                            var credentialForServer = {
                                id: cred.id,
                                type: cred.type,
                                rawId: rawId,
                                response: {
                                    clientDataJSON: clientDataJSON,
                                    attestationObject: attestationObject
                                },
                                clientExtensionResults: cred.clientExtensionResults
                            };
                            // #passkeyFormをpostする
                            var form = $("#passkeyForm");
                            var params = {
                                'data': {
                                    'module': 'Users',
                                    'action': 'SaveAjax',
                                    'mode': 'addMultiFactorAuthenticationStep3',
                                    'challenge': challengeBytes,
                                    'credential': JSON.stringify(credentialForServer),
                                    'device_name': form.find('[name="device_name"]').val(),
                                    'userid': form.find('[name="userid"]').val(),
                                    'username': form.find('[name="username"]').val()
                                }
                            };
                            app.request.post(params).then(function(err, data) {
                                if (err === null) {
                                    if( data.login === 'true' ) {
                                        // 初回ログイン時はログイン処理を行う
                                        var link = '' + form.find('[name="userid"]').val();
                                        // link先へリダイレクト
                                        window.location.href = link;
                                    }
                                    else
                                    {
                                        app.helper.hideModal();
                                        var successMessage = app.vtranslate(data.message);
                                        app.helper.showSuccessNotification({"message": successMessage});
                                        location.reload();
                                    }
                                    
                                } else {
                                    app.helper.showErrorNotification({"message": err});
                                }
                            });
                        }).catch(function(error) {
                            console.error("Error creating credentials:", error);
                        });
                    } else {
                        alert(response.error);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                }
            });
        });
    },

    createQRCode: function(qrcodeURL) {
        var qrcode = new QRCode(document.getElementById("qrcode"), {
            text: qrcodeURL,
            cls: "img-responsive",
            width: 256,
            height: 256,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
        return qrcode;
    },

    registerDeleteCredentialEvent: function(element) {
        var credentialId = $(element).data('id');
        if (confirm(app.vtranslate('JS_CONFIRM_DELETE_CREDENTIAL', 'Users'))) {
            $.ajax({
                url: 'index.php',
                type: 'POST',
                data: {
                    module: 'Users',
                    action: 'DeleteAjax',
                    mode: 'credential',
                    credential_id: credentialId,
                },
                success: function(response) {
                    if (response.success) {
                        alert(app.vtranslate('JS_USER_CREDENTIAL_DELETE_SUCCESS', 'Users'));
                        location.reload();
                    } else {
                        alert(app.vtranslate('JS__USER_CREDENTIAL_DELETE_FAILED', 'Users'));
                    }
                },
                error: function() {
                    alert(app.vtranslate('JS_USER_CREDENTIAL_DELETE_FAILED', 'Users'));
                }
            });
        }
    }
};