define([
    'jquery',
    'backbone',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/mediator'
], function($, Backbone, _, __, mediator) {
    'use strict';

    /**
     * @export  orodotmailer/js/integration-connection
     * @class   orodotmailer.IntegrationConnection
     * @extends Backbone.View
     */
    const IntegrationConnection = Backbone.View.extend({
        /**
         * Array of fields that should be submitted for form update
         */
        fieldsSets: {
            channel: []
        },

        requiredOptions: ['channelSelector', 'fieldsSets', 'formSelector'],

        /**
         * @inheritdoc
         */
        constructor: function IntegrationConnection(...args) {
            IntegrationConnection.__super__.constructor.apply(this, args);
        },

        /**
         * @param options Object
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            const requiredMissed = this.requiredOptions.filter(function(option) {
                return _.isUndefined(options[option]);
            });
            if (requiredMissed.length) {
                throw new TypeError('Missing required option(s): ' + requiredMissed.join(','));
            }

            _.extend(this.fieldsSets, options.fieldsSets);

            $(options.channelSelector).on('change', this.changeHandler.bind(this));
        },

        /**
         * Updates form via ajax
         *
         * @param {$.Event} e
         */
        changeHandler: function(e) {
            const $form = $(this.options.formSelector);
            let data = $form.serializeArray();
            const url = $form.attr('action');
            const fieldsSet = this.fieldsSets.channel;

            data = _.filter(data, function(field) {
                return _.indexOf(fieldsSet, field.name) !== -1;
            });

            mediator.execute('submitPage', {url: url, type: $form.attr('method'), data: $.param(data)});
        }
    });

    return IntegrationConnection;
});
