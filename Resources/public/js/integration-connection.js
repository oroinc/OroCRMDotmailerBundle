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
    return Backbone.View.extend({
        /**
         * Array of fields that should be submitted for form update
         */
        fieldsSets: {
            channel: []
        },

        requiredOptions: ['channelSelector', 'fieldsSets', 'formSelector'],

        /**
         * @param options Object
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            var requiredMissed = this.requiredOptions.filter(function(option) {
                return _.isUndefined(options[option]);
            });
            if (requiredMissed.length) {
                throw new TypeError('Missing required option(s): ' + requiredMissed.join(','));
            }

            _.extend(this.fieldsSets, options.fieldsSets);

            $(options.channelSelector).on('change', _.bind(this.changeHandler, this));
        },

        /**
         * Updates form via ajax
         *
         * @param {$.Event} e
         */
        changeHandler: function(e) {
            //$('#flash-messages .flash-messages-holder').find('.top-messages').remove();

            var $form = $(this.options.formSelector);
            var data = $form.serializeArray();
            var url = $form.attr('action');
            var fieldsSet = this.fieldsSets.channel;

            data = _.filter(data, function(field) {
                return _.indexOf(fieldsSet, field.name) !== -1;
            });

            mediator.execute('submitPage', {url: url, type: $form.attr('method'), data: $.param(data)});
        }
    });
});
