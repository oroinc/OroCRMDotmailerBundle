/*jslint nomen: true*/
/*global define*/
define(['jquery', 'oroui/js/mediator', 'oroui/js/app/components/widget-component'], function ($, mediator, WidgetComponent) {
    'use strict';

    /**
     * @export  orocrmdotmailer/js/sync-buttons-handler
     * @class   orocrm.dotmailer.handler.syncButtons
     *
     * @param {string} syncButtonsSelector JQuery selector
     */
    return function (syncButtonsSelector) {
        const updateSyncSettingsAction = 'connect-with-dotmailer-setting-update';
        const startSyncAction = 'sync-with-dotmailer';

        var self = this;

        /**
         * @this jQuery current button
         */
        this.startSyncDelegate = function(){
            var successMessage = this.data('success-message');
            var failMessage = this.data('fail-message');
            $.post(this.data('url')).done(function() {
                if (successMessage) {
                    mediator.execute('addMessage', 'success', successMessage);
                }
                mediator.execute('refreshPage');
            }).fail(function(){
                if (successMessage) {
                    mediator.execute('showFlashMessage', 'error', failMessage);
                }
            });
        };

        /**
         * @this jQuery current button
         */
        this.updateSettingsDelegate = function(){
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

                _bindEnvironmentEvent: function (widget) {
                    this.listenTo(widget, 'formSave', function () {
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
                        'url': this.data('url')
                    }
                }
            );
            widget.openWidget();
        };

        this.syncButtonsClickHandlerDelegate = function(){
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

        this.bindElementsEvents = function () {
            $(syncButtonsSelector).click(this.syncButtonsClickHandlerDelegate);
        };

        this.bindElementsEvents.call(this);
    };
});
