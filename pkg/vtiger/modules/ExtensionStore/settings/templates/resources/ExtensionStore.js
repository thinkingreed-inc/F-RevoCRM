/* 
 * Copyright (C) www.vtiger.com. All rights reserved.
 * @license Proprietary
 */

jQuery.Class('Settings_ExtensionStore_Js', {
    showPopover : function(e) {
        var ele = jQuery(e);
        var options = {
            placement : ele.data('position'),
            trigger   : 'hover',
        };
        ele.popover(options);
    },
}, {
    /**
     * Function to get import module index params
     */
    getImportModuleIndexParams: function() {
        var params = {
            'module': app.getModuleName(),
            'parent': app.getParentModuleName(),
            'view': 'ExtensionStore',
        };
        return params;
    },
    /**
     * Function to get import module with respect to view
     */
    getImportModuleStepView: function(params) {
        var aDeferred = jQuery.Deferred();
        var progressIndicatorElement = jQuery.progressIndicator({
            'position': 'html',
            'blockInfo': {
                'enabled': true
            }
        });

        AppConnector.request(params).then(
                function(data) {
                    progressIndicatorElement.progressIndicator({'mode': 'hide'});
                    aDeferred.resolve(data);
                },
                function(error) {
                    progressIndicatorElement.progressIndicator({'mode': 'hide'});
                    aDeferred.reject(error);
                }
        );
        return aDeferred.promise();
    },
    /**
     * Function to register raty
     */
    registerRaty: function() {
        jQuery('.rating').raty({
            score: function() {
                return this.getAttribute('data-score');
            },
            readOnly: function() {
                return this.getAttribute('data-readonly');
            }
        });
    },
    /**
     * Function to register event for index of import module
     */
    registerEventForIndexView: function() {
        this.registerRaty();
        app.showScrollBar(jQuery('.extensionDescription'), {'height': '120px', 'width': '100%', 'railVisible': true});
    },
    /**
     * Function to register event related to Import extrension Modules in index
     */
    registerEventsForExtensionStore: function(container) {
        var thisInstance = this;

        jQuery(container).on('click', '.installExtension, .installPaidExtension', function(e) {
            thisInstance.installExtension(e);
        });
        
        jQuery(container).on('keydown', '#searchExtension', function(e) {
            var currentTarget = jQuery(e.currentTarget);
            var code = e.keyCode;
            if (code == 13) {
                var searchTerm = currentTarget.val();
                var params = {
                    'module': app.getModuleName(),
                    'parent': app.getParentModuleName(),
                    'view': 'ExtensionStore',
                    'mode': 'searchExtension',
                    'searchTerm': searchTerm,
                    'type': 'Extension'
                };

                var progressIndicatorElement = jQuery.progressIndicator({
                    'position': 'html',
                    'blockInfo': {
                        'enabled': true
                    }
                });
                AppConnector.request(params).then(
                        function(data) {
                            jQuery('#extensionContainer').html(data);
                            thisInstance.registerRaty();
                            thisInstance.registerEventForIndexView();
                            progressIndicatorElement.progressIndicator({'mode': 'hide'});
                        },
                        function(error) {
                            progressIndicatorElement.progressIndicator({'mode': 'hide'});
                        }
                );
            }
        });

        jQuery(container).on('click', '#logintoMarketPlace', function(e) {
            var loginAccountModal = jQuery(container).find('.loginAccount').clone(true, true);
            loginAccountModal.removeClass('hide');
            var progressIndicatorElement = jQuery.progressIndicator();

            var callBackFunction = function(data) {
                jQuery(data).on('click', '[name="signUp"]', function(e) {
                    app.hideModalWindow();
                    var signUpAccountModal = jQuery(container).find('.signUpAccount').clone(true, true);
                    signUpAccountModal.removeClass('hide');

                    var callBackSignupFunction = function(data) {
                        var form = data.find('.signUpForm');
                        var params = app.getvalidationEngineOptions(true);
                        params.onValidationComplete = function(form, valid) {
                            if (valid) {
                                var formData = form.serializeFormData();
                                var progressIndicatorElement = jQuery.progressIndicator();
                                AppConnector.request(formData).then(
                                        function(data) {
                                            if (data.success) {
                                                progressIndicatorElement.progressIndicator({'mode': 'hide'});
                                                app.hideModalWindow();
                                                location.reload();
                                            } else {
                                                progressIndicatorElement.progressIndicator({'mode': 'hide'});
                                                app.hideModalWindow();
                                                var error = data['error']['message'];
                                                var params = {
                                                    text: error,
                                                    type: 'error',
                                                    title : app.vtranslate('JS_WARNING')
                                                };
                                                Settings_Vtiger_Index_Js.showMessage(params);
                                            }
                                        }
                                );
                            }
                            return false;
                        };
                        form.validationEngine(params);
                    };

                    app.showModalWindow(signUpAccountModal, function(data) {
                        if (typeof callBackFunction == 'function') {
                            callBackSignupFunction(data);
                        }
                    }, {'width': '1000px'});
                });

                var form = data.find('.loginForm');
                var params = app.getvalidationEngineOptions(true);
                params.onValidationComplete = function(form, valid) {
                    if (valid) {
                        var formData = form.serializeFormData();
                        var savePassword = form.find('input[name="savePassword"]:checked').length;
                        if (savePassword) {
                            formData["savePassword"] = true;
                        } else {
                            formData["savePassword"] = false;
                        }
                        var progressIndicatorElement = jQuery.progressIndicator();
                        AppConnector.request(formData).then(
                                function(data) {
                                    if (data.success) {
                                        progressIndicatorElement.progressIndicator({'mode': 'hide'});
                                        app.hideModalWindow();
                                        location.reload();
                                    } else {
                                        progressIndicatorElement.progressIndicator({'mode': 'hide'});
                                        app.hideModalWindow();
                                        var error = data.error.message;
                                        if (error.length) {
                                            var params = {
                                                    type: 'error',
                                                    text: error,
                                                    title : app.vtranslate('JS_WARNING')
                                            };
                                            Settings_Vtiger_Index_Js.showMessage(params);
                                        }
                                    }
                                }
                        );
                    }
                    return false;
                };
                form.validationEngine(params);
            }

            app.showModalWindow(loginAccountModal, function(data) {
                progressIndicatorElement.progressIndicator({'mode': 'hide'});
                if (typeof callBackFunction == 'function') {
                    callBackFunction(data);
                }
            }, {'width': '1000px'});

        });
        

     jQuery(container).on('click', '#logoutMarketPlace', function(e) {
            var element = jQuery(e.currentTarget);
            var aDeferred = jQuery.Deferred();
            var message = app.vtranslate('JS_LBL_ARE_YOU_SURE_YOU_WANT_TO_LOGOUT_FROM_EXTENSION');
   
            Vtiger_Helper_Js.showConfirmationBox({
                'message' : message
            }).then(     
                function(e) {
                    var params = {
                        'module': app.getModuleName(),
                        'parent': app.getParentModuleName(),
                        'action' : "Basic",
                        'mode' : "logoutMarketPlace"
                    };
                    var progressIndicatorElement = jQuery.progressIndicator();
       
                    AppConnector.request(params).then(
                        function(data) {
                            progressIndicatorElement.progressIndicator({
                                'mode': 'hide'
                            });
                            location.reload();
                            aDeferred.resolve(data);
                        },
                        function(error) {
                            progressIndicatorElement.progressIndicator({
                                'mode': 'hide'
                            });
                            aDeferred.reject(error);
                        }
                        );
                    return aDeferred.promise();
                },
                function(error, err){
                }
                );
           
        });
       /* 
        * Function related extension store pro
         jQuery(container).on('click', '#registerUser', function(e) {
            var loginAccountModal = jQuery(container).find('.loginAccount').clone(true, true);
            loginAccountModal.removeClass('hide');

            var callBackFunction = function(data) {
                jQuery(data).on('click', '[name="signUp"]', function(e) {
                    app.hideModalWindow();
                    var signUpAccountModal = jQuery(container).find('.signUpAccount').clone(true, true);
                    signUpAccountModal.removeClass('hide');

                    var callBackSignupFunction = function(data) {
                        var form = data.find('.signUpForm');
                        var params = app.getvalidationEngineOptions(true);
                        params.onValidationComplete = function(form, valid) {
                            if (valid) {
                                var formData = form.serializeFormData();
                                var progressIndicatorElement = jQuery.progressIndicator();
                                AppConnector.request(formData).then(
                                        function(data) {
                                            if (data['success'] == 'true') {
                                                progressIndicatorElement.progressIndicator({'mode': 'hide'});
                                                app.hideModalWindow();
                                                location.reload();
                                            } else {
                                                progressIndicatorElement.progressIndicator({'mode': 'hide'});
                                                app.hideModalWindow();
                                                var error = data['error'];
                                                var params = {
                                                    text: error
                                                };
                                                Settings_Vtiger_Index_Js.showMessage(params);
                                            }
                                        }
                                );
                            }
                            return false;
                        };
                        form.validationEngine(params);
                    };

                    app.showModalWindow(signUpAccountModal, function(data) {
                        if (typeof callBackFunction == 'function') {
                            callBackSignupFunction(data);
                        }
                    }, {'width': '1000px'});
                });

                var form = data.find('.loginForm');
                var params = app.getvalidationEngineOptions(true);
                params.onValidationComplete = function(form, valid) {
                    if (valid) {
                        var formData = form.serializeFormData();
                        var savePassword = form.find('input[name="savePassword"]:checked').length;
                        if (savePassword) {
                            formData["savePassword"] = true;
                        } else {
                            formData["savePassword"] = false;
                        }
                        formData["userAction"] = 'register';
                        var progressIndicatorElement = jQuery.progressIndicator();
                        AppConnector.request(formData).then(
                                function(data) {
                                    if (data.success) {
                                        progressIndicatorElement.progressIndicator({'mode': 'hide'});
                                        app.hideModalWindow();
                                        location.reload();
                                    } else {
                                        progressIndicatorElement.progressIndicator({'mode': 'hide'});
                                        app.hideModalWindow();
                                        var error = data.error.message;
                                        if (error.length) {
                                            var params = {
                                                text: error
                                            };
                                            Settings_Vtiger_Index_Js.showMessage(params);
                                        }
                                    }
                                }
                        );
                    }
                    return false;
                };
                form.validationEngine(params);
            };

            app.showModalWindow(loginAccountModal, function(data) {
                if (typeof callBackFunction == 'function') {
                    callBackFunction(data);
                }
            }, {'width': '1000px'});
        });*/
        
        jQuery(container).on('click', '#setUpCardDetails', function(e) {
            var element = jQuery(e.currentTarget);
            var setUpCardModal = jQuery(container).find('.setUpCardModal').clone(true, true);
            setUpCardModal.removeClass('hide');
            var progressIndicatorElement = jQuery.progressIndicator();

            var callBackFunction = function(data) {
                jQuery(data).on('click', '[name="resetButton"]', function(e) {
                    jQuery(data).find('[name="cardNumber"],[name="expMonth"],[name="expYear"],[name="cvccode"]').val('');
                });
                var form = data.find('.setUpCardForm');
                var params = app.getvalidationEngineOptions(true);
                params.onValidationComplete = function(form, valid) {
                    if (valid) {
                        form.find('.saveButton').attr('disabled','true');
                        var formData = form.serializeFormData();
                        var progressIndicatorElement = jQuery.progressIndicator();
                        AppConnector.request(formData).then(
                                function(data) {
                                    if (data.success) {
                                        var result = data.result;
                                        jQuery(container).find('[name="customerCardId"]').val(data.result.id);
                                        progressIndicatorElement.progressIndicator({'mode': 'hide'});
                                        jQuery(container).find('.setUpCardModal').find('[name="cardNumber"]').val(result['number']);
                                        jQuery(container).find('.setUpCardModal').find('[name="expMonth"]').val(result['expmonth']);
                                        jQuery(container).find('.setUpCardModal').find('[name="expYear"]').val(result['expyear']);
                                        jQuery(container).find('.setUpCardModal').find('[name="cvccode"]').val(result['cvc']);
                                        element.html(app.vtranslate('JS_UPDATE_CARD_DETAILS'));
                                        app.hideModalWindow();
                                        Settings_Vtiger_Index_Js.showMessage({text:app.vtranslate('JS_CARD_DETAILS_UPDATED')});
                                    } else {
                                        progressIndicatorElement.progressIndicator({'mode': 'hide'});
                                        app.hideModalWindow();
                                        var errorMessage = data.error.message;
                                        var params = {
                                            type:'error',
                                            text: errorMessage,
                                            title : app.vtranslate('LBL_WARNING')
                                        };
                                        Settings_Vtiger_Index_Js.showMessage(params);
                                    }
                                }
                        );
                    }
                    return false;
                };
                form.validationEngine(params);
            };

            app.showModalWindow(setUpCardModal, function(data) {
                progressIndicatorElement.progressIndicator({'mode': 'hide'});
                if (typeof callBackFunction == 'function') {
                    callBackFunction(data);
                }
            }, {'width': '1000px'});
        });

        jQuery(container).on('click', '.oneclickInstallFree, .oneclickInstallPaid', function(e) {
            var element = jQuery(e.currentTarget);
            var extensionContainer = element.closest('.extension_container');
            var extensionId = extensionContainer.find('[name="extensionId"]').val();
            var moduleAction = extensionContainer.find('[name="moduleAction"]').val();
            var extensionName = extensionContainer.find('[name="extensionName"]').val();
	     var message = app.vtranslate('JS_LBL_ARE_YOU_SURE_YOU_WANT_TO_INSTALL_THIS_EXTENSION');
   
             Vtiger_Helper_Js.showConfirmationBox({'message' : message}).then(     
             	function(e) {
            if(element.hasClass('loginRequired')){
                var loginError = app.vtranslate('JS_PLEASE_LOGIN_TO_MARKETPLACE_FOR_INSTALLING_EXTENSION');
                var loginErrorParam = {
                    text: loginError,
                    'type' : 'error'
                };
                Settings_Vtiger_Index_Js.showMessage(loginErrorParam);
                return false;
            }
            var params = {
                'module': app.getModuleName(),
                'parent': app.getParentModuleName(),
                'view': 'ExtensionStore',
                'mode': 'oneClickInstall',
                'extensionId': extensionId,
                'moduleAction': moduleAction,
                'extensionName': extensionName
            };

            if (element.hasClass('oneclickInstallPaid')) {
                var trial = element.data('trial');
                if (!trial) {
                    var customerCardId = jQuery(container).find('[name="customerCardId"]').val();
                    if (customerCardId.length == 0) {
                        var cardSetupError = app.vtranslate('JS_PLEASE_SETUP_CARD_DETAILS_TO_INSTALL_THIS_EXTENSION');
                        var params = {
                            text: cardSetupError
                        };
                        Settings_Vtiger_Index_Js.showMessage(params);
                        return false;
                    }
                } else {
                    params['trial'] = trial;
                }
            }
            thisInstance.getImportModuleStepView(params).then(function(installationLogData) {
                var callBackFunction = function(data) {
                    app.showScrollBar(jQuery('#installationLog'), {'height': '150px'});
                    var installationStatus = jQuery(data).find('[name="installationStatus"]').val();

                    if (installationStatus == "success") {
                        if (!trial) {
                            element.closest('span').html('<span class="alert alert-info">' + app.vtranslate('JS_INSTALLED') + '</span>');
                            extensionContainer.find('[name="moduleAction"]').val(app.vtranslate('JS_INSTALLED'));
                        } else if ((element.hasClass('oneclickInstallPaid')) && trial) {
                            thisInstance.updateTrialStatus(true, extensionName).then(function(data) {
                                if (data.success) {
                                    element.closest('span').prepend('<span class="alert alert-info">' + app.vtranslate('JS_TRIAL_INSTALLED') + '</span> &nbsp; &nbsp;');
                                    element.remove();
                                }
                            });
                        } else if ((element.hasClass('oneclickInstallPaid')) && (!trial)) {
                            thisInstance.updateTrialStatus(false, extensionName).then(function(data) {
                                if (data.success) {
                                    element.closest('span').html('<span class="alert alert-info">' + app.vtranslate('JS_INSTALLED') + '</span>');
                                    extensionContainer.find('[name="moduleAction"]').val(app.vtranslate('JS_INSTALLED'));
                                }
                            });
                        }
                    }
                };
                var modalData = {
                    data: installationLogData,
                    css: {'width': '60%', 'height': 'auto'},
                    cb: callBackFunction
                };
                app.showModalWindow(modalData);
            });
           },
				function(error, err){
							}
       );
        });

        jQuery(container).on('click', '#installLoader', function(e) {
            var extensionLoaderModal = jQuery(container).find('.extensionLoader').clone(true, true);
            extensionLoaderModal.removeClass('hide');

            var callBackFunction = function(data) {

            };
            app.showModalWindow(extensionLoaderModal, function(data) {
                if (typeof callBackFunction == 'function') {
                    callBackFunction(data);
                }
            }, {'width': '1000px'});
        });
    },
    updateTrialStatus: function(trialStatus, extensionName) {
        var trialParams = {
            'module': app.getModuleName(),
            'parent': app.getParentModuleName(),
            'action': 'Basic',
            'mode': 'updateTrialMode',
            'extensionName': extensionName
        };
        if (trialStatus) {
            trialParams['trial'] = 1;
        } else {
            trialParams['trial'] = 0;
        }
        this.getImportModuleStepView(trialParams).then(function(data) {
            return data;
        });
    },
    installExtension: function(e) {
        var thisInstance = this;
        var element = jQuery(e.currentTarget);
        thisInstance.ExtensionDetails(element);
    },
    /**
     * Function to download Extension
     */
    ExtensionDetails: function(element) {
        var thisInstance = this;
        var extensionContainer = element.closest('.extension_container');
        var extensionId = extensionContainer.find('[name="extensionId"]').val();
        var moduleAction = extensionContainer.find('[name="moduleAction"]').val();
        var extensionName = extensionContainer.find('[name="extensionName"]').val();
        var params = {
            'module': app.getModuleName(),
            'parent': app.getParentModuleName(),
            'view': 'ExtensionStore',
            'mode': 'detail',
            'extensionId': extensionId,
            'moduleAction': moduleAction,
            'extensionName': extensionName
        };

        this.getImportModuleStepView(params).then(function(data) {
            var detailContentsHolder = jQuery('.contentsDiv');
            detailContentsHolder.html(data);
            jQuery(window).scrollTop(10);
            thisInstance.registerEventsForExtensionStoreDetail(detailContentsHolder);
        });
    },
    /**
     * Function to register event related to Import extrension Modules in detail
     */
    registerEventsForExtensionStoreDetail: function(container) {
        var container = jQuery(container);
        app.showScrollBar(jQuery('div.scrollableTab'), {'width': '100%', 'height': '400px'});
        var thisInstance = this;
        this.registerRaty();
        slider = jQuery('#imageSlider').bxSlider({
            auto: true,
            pause: 1000,
            randomStart: true,
            autoHover: true
        });
        jQuery("#screenShots").on('click', function() {
            slider.reloadSlider();
        });

        container.find('#installExtension').on('click', function(e) {
            var element = jQuery(e.currentTarget);
	    var message = app.vtranslate('JS_LBL_ARE_YOU_SURE_YOU_WANT_TO_INSTALL_THIS_EXTENSION');
   
             Vtiger_Helper_Js.showConfirmationBox({'message' : message}).then(     
             	function(e) {
            
            if(element.hasClass('loginRequired')){
                var loginError = app.vtranslate('JS_PLEASE_LOGIN_TO_MARKETPLACE_FOR_INSTALLING_EXTENSION');
                var loginErrorParam = {
                    text: loginError,
                    'type' : 'error'
                };
                Settings_Vtiger_Index_Js.showMessage(loginErrorParam);
                return false;
            }
            
            if(element.hasClass('setUpCard')){
                var paidError = app.vtranslate('JS_PLEASE_SETUP_CARD_DETAILS_TO_INSTALL_EXTENSION');
                var paidErrorParam = {
                    text: paidError,
                    'type' : 'error'
                };
                Settings_Vtiger_Index_Js.showMessage(paidErrorParam);
                return false;
            }
            var extensionId = jQuery('[name="extensionId"]').val();
            var targetModule = jQuery('[name="targetModule"]').val();
            var moduleType = jQuery('[name="moduleType"]').val();
            var moduleAction = jQuery('[name="moduleAction"]').val();
            var fileName = jQuery('[name="fileName"]').val();

            var params = {
                'module': app.getModuleName(),
                'parent': app.getParentModuleName(),
                'view': 'ExtensionStore',
                'mode': 'installationLog',
                'extensionId': extensionId,
                'moduleAction': moduleAction,
                'targetModule': targetModule,
                'moduleType': moduleType,
                'fileName': fileName
            }

            thisInstance.getImportModuleStepView(params).then(function(installationLogData) {
                var callBackFunction = function(data) {
                    var installationStatus = jQuery(data).find('[name="installationStatus"]').val();
                    if (installationStatus == "success") {
                        jQuery('#installExtension').remove();
                        jQuery('#launchExtension').removeClass('hide');
                        jQuery('.writeReview').removeClass('hide');
                    }
                    app.showScrollBar(jQuery('#installationLog'), {'height': '150px'});
                };
                var modalData = {
                    data: installationLogData,
                    css: {'width': '60%', 'height': 'auto'},
                    cb: callBackFunction
                };
                app.showModalWindow(modalData);
            });
            },
							function(error, err){
							}
       );
        });

        container.find('#uninstallModule').on('click', function(e) {
            var element = jQuery(e.currentTarget);
            var extensionName = container.find('[name="targetModule"]').val();
            if(element.hasClass('loginRequired')){
                var loginError = app.vtranslate('JS_PLEASE_LOGIN_TO_MARKETPLACE_FOR_UNINSTALLING_EXTENSION');
                var loginErrorParam = {
                    text: loginError,
                    'type' : 'error'
                };
                Settings_Vtiger_Index_Js.showMessage(loginErrorParam);
                return false;
            }
            var params = {
                'module': app.getModuleName(),
                'parent': app.getParentModuleName(),
                'action': 'Basic',
                'mode': 'uninstallExtension',
                'extensionName': extensionName
            };

            var progressIndicatorElement = jQuery.progressIndicator();
            AppConnector.request(params).then(
                    function(data) {
                        if (data.success) {
                            progressIndicatorElement.progressIndicator({'mode': 'hide'});
                            container.find('#declineExtension').trigger('click');
                        }
                    });

        });

        container.find('#declineExtension').on('click', function() {
            var params = thisInstance.getImportModuleIndexParams();
            thisInstance.getImportModuleStepView(params).then(function(data) {
                var detailContentsHolder = jQuery('.contentsDiv');
                detailContentsHolder.html(data);
                thisInstance.registerEventForIndexView();
            });
        });

        container.on('click', '.writeReview', function(e) {
            var customerReviewModal = jQuery(container).find('.customerReviewModal').clone(true, true);
            customerReviewModal.removeClass('hide');

            var callBackFunction = function(data) {
                var form = data.find('.customerReviewForm');
                form.find('.rating').raty();
                var params = app.getvalidationEngineOptions(true);
                params.onValidationComplete = function(form, valid) {
                    if (valid) {
                        var review = form.find('[name="customerReview"]').val();
                        var listingId = form.find('[name="extensionId"]').val();
                        var rating = form.find('[name="score"]').val();
                        var params = {
                            'module': app.getModuleName(),
                            'parent': app.getParentModuleName(),
                            'action': 'Basic',
                            'mode': 'postReview',
                            'comment': review,
                            'listing': listingId,
                            'rating': rating
                        }
                        var progressIndicatorElement = jQuery.progressIndicator();
                        AppConnector.request(params).then(
                                function(data) {
                                    if (data['success']) {
                                        var result = data['result'];
                                        if (result) {
                                            var html = '<div class="row-fluid" style="margin: 8px 0 15px;">' +
                                                        '<div class="span3">'+
                                                            '<div data-score="' + rating + '" class="rating" data-readonly="true"></div>'+
                                                            '<div>'+result.Customer.firstname + ' ' + result.Customer.lastname + '</div>'+
                                                            '<div class="muted">'+(result.createdon).substring(4) +'</div>'+
                                                         '</div>'+
                                                         '<div class="span9">'+ result.comment+'</div>'+
                                                        '</div><hr>';
                                            container.find('.customerReviewContainer').append(html);
                                            container.find('.rating').raty({
                                                score: function() {
                                                    return this.getAttribute('data-score');
                                                }
                                            });
                                        }
                                        progressIndicatorElement.progressIndicator({'mode': 'hide'});
                                        app.hideModalWindow();
                                    }
                                }
                        );
                    }
                    return false;
                }
                form.validationEngine(params);
            }

            app.showModalWindow(customerReviewModal, function(data) {
                if (typeof callBackFunction == 'function') {
                    callBackFunction(data);
                }
            }, {'width': '1000px'});
        });
    },
    
    registerEvents: function() {
        var detailContentsHolder = jQuery('.contentsDiv');
        this.registerEventForIndexView();
        this.registerEventsForExtensionStore(detailContentsHolder);
    }
});

jQuery(document).ready(function() {
    var settingExtensionStoreInstance = new Settings_ExtensionStore_Js();
    settingExtensionStoreInstance.registerEvents();
    var mode = jQuery('[name="mode"]').val();
    if(mode == 'detail'){
        settingExtensionStoreInstance.registerEventsForExtensionStoreDetail(jQuery('.contentsDiv'));
    }
});



