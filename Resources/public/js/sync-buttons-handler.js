define([
    'jquery',
    'oroui/js/mediator',
    'oroui/js/app/components/widget-component'
], function($, mediator, WidgetComponent) {
    'use strict';

    const console = window.console;

    /**
     * @export  orodotmailer/js/sync-buttons-handler
     * @class   oro.dotmailer.handler.syncButtons
     *
     * @param {string} syncButtonsSelector JQuery selector
     */
    return function(syncButtonsSelector) {
        const updateSyncSettingsAction = 'connect-with-dotmailer-setting-update';
        const startSyncAction = 'sync-with-dotmailer';

        const self = this;

        /**
         * @this jQuery current button
         */
        this.startSyncDelegate = function() {
            mediator.execute('showLoading');
            $.post(this.data('url')).done(function(data) {
                mediator.execute('addMessage', 'success', data.message);
                mediator.execute('refreshPage');
            }).always(function() {
                mediator.execute('hideLoading');
            });
        };

        /**
         * @this jQuery current button
         */
        this.updateSettingsDelegate = function() {
            const message = this.data('message');
            const StatelessWidgetComponent = WidgetComponent.extend({
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

                /**
                 * @inheritdoc
                 */
                constructor: function StatelessWidgetComponent(options) {
                    StatelessWidgetComponent.__super__.constructor.call(this, options);
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

            const widget = new StatelessWidgetComponent(
                {
                    options: {
                        url: this.data('url'),
                        title: this.data('title')
                    }
                }
            );
            widget.openWidget();
        };

        this.syncButtonsClickHandlerDelegate = function() {
            const $this = $(this);
            const action = $this.data('action');
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

        this.bindElementsEvents();
    };
});
