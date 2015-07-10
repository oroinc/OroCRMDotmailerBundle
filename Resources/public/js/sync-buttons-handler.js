define([
    'jquery',
    'oroui/js/mediator',
    'oroui/js/app/components/widget-component'
], function($, mediator, WidgetComponent) {
    'use strict';

    var console = window.console;

    /**
     * @export  orocrmdotmailer/js/sync-buttons-handler
     * @class   orocrm.dotmailer.handler.syncButtons
     *
     * @param {string} syncButtonsSelector JQuery selector
     */
    return function(syncButtonsSelector) {
        var updateSyncSettingsAction = 'connect-with-dotmailer-setting-update';
        var startSyncAction = 'sync-with-dotmailer';

        var self = this;

        /**
         * @this jQuery current button
         */
        this.startSyncDelegate = function() {
            mediator.execute('showLoading');
            $.post(this.data('url')).done(function(data) {
                mediator.execute('addMessage', 'success', data.message);
                mediator.execute('refreshPage');
            }).fail(function(data) {
                mediator.execute('showFlashMessage', 'error', data.message);
            }).always(function() {
                mediator.execute('hideLoading');
            });
        };

        /**
         * @this jQuery current button
         */
        this.updateSettingsDelegate = function() {
            var message = this.data('message');
            var StatelessWidgetComponent = WidgetComponent.extend({
                defaults: {
                    type: 'dialog',
                    options: {
                        stateEnabled: false,
                        incrementalPosition: false,
                        loadingMaskEnabled: true,
                        dialogOptions: {
                            modal: true,
                            resizable: false,
                            width: 510,
                            autoResize: true
                        }
                    }
                },

                _bindEnvironmentEvent: function(widget) {
                    this.listenTo(widget, 'formSave', function() {
                        widget.remove();
                        if (message) {
                            mediator.execute('addMessage', 'success', message);
                        }
                        mediator.execute('refreshPage');
                    });
                }
            });

            var widget = new StatelessWidgetComponent(
                {
                    options: {
                        'url': this.data('url'),
                        'title': this.data('title')
                    }
                }
            );
            widget.openWidget();
        };

        this.syncButtonsClickHandlerDelegate = function() {
            var $this = $(this);
            var action = $this.data('action');
            switch (action) {
                case startSyncAction:
                    self.startSyncDelegate.call($this);
                    return;
                case updateSyncSettingsAction:
                    self.updateSettingsDelegate.call($this);
                    return;
            }
            if (console && console.warn) {
                console.warn('Unrecognized sync button action');
            }
        };

        this.bindElementsEvents = function() {
            $(syncButtonsSelector).click(this.syncButtonsClickHandlerDelegate);
        };

        this.bindElementsEvents.call(this);
    };
});
