/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Vtiger_IFrameWidget_Widget_Js("Vtiger_IFrameWidget_Widget_Js", {}, {
    
    getContainer: function(widgetId) {
        return jQuery('#' + widgetId);
    },
    
    editWidget: function(widgetId) {
        var container = this.getContainer(widgetId);
        var configForm = container.find('.iframeConfigForm');
        var display = container.find('.iframeDisplay');
        
        configForm.show();
        display.hide();
    },
    
    cancelEdit: function(widgetId) {
        var container = this.getContainer(widgetId);
        var configForm = container.find('.iframeConfigForm');
        var display = container.find('.iframeDisplay');
        
        configForm.hide();
        display.show();
    },
    
    saveIFrameWidget: function(widgetId) {
        var container = this.getContainer(widgetId);
        var titleField = container.find('#iframeTitle_' + widgetId);
        var urlField = container.find('#iframeUrl_' + widgetId);
        
        var title = titleField.val();
        var url = urlField.val();
        
        if (!title || !url) {
            app.helper.showErrorNotification({message: 'Title and URL are required'});
            return;
        }
        
        if (!this.isValidUrl(url)) {
            app.helper.showErrorNotification({message: 'Please enter a valid URL'});
            return;
        }
        
        var params = {
            module: 'Vtiger',
            action: 'IFrameWidgetAjax',
            mode: 'save',
            widgetid: widgetId,
            title: title,
            url: url
        };
        
        app.request.post({data: params}).then(function(err, data) {
            if (data && data.success) {
                // Reload the widget to show updated content
                location.reload();
            } else {
                app.helper.showErrorNotification({message: 'Failed to save widget'});
            }
        });
    },
    
    isValidUrl: function(string) {
        // Allow empty or whitespace URLs (will use default)
        if (!string || !string.trim()) {
            return false;
        }
        
        string = string.trim();
        
        // Allow relative URLs starting with /
        if (string.startsWith('/')) {
            return true;
        }
        
        // Allow query strings starting with ?
        if (string.startsWith('?')) {
            return true;
        }
        
        // Allow localhost URLs
        if (string.startsWith('http://localhost') || string.startsWith('https://localhost')) {
            return true;
        }
        
        // Allow standard URLs
        try {
            new URL(string);
            return true;
        } catch (_) {
            // For other cases, do basic validation
            return /^https?:\/\//.test(string) || string.includes('://');
        }
    },
    
    registerEvents: function() {
        this._super();
    }
});