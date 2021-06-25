define([
    'jquery',
    'backbone',
    'underscore',
    'orotranslation/js/translator',
    'oroui/js/mediator'
], function($, Backbone, _, __, mediator) {
    'use strict';

    /**
     * @export  orodotmailer/js/datafield-view
     * @class   orodotmailer.datafieldView
     * @extends Backbone.View
     */
    const DataFieldCiew = Backbone.View.extend({
        /**
         * @const
         */
        UPDATE_MARKER: 'formUpdateMarker',

        /**
         * Array of fields that should be submitted for form update
         * Depends on what exact field changed
         */
        fieldsSets: {
            type: []
        },

        requiredOptions: ['typeSelector', 'fieldsSets', 'formSelector'],

        /**
         * @inheritdoc
         */
        constructor: function DataFieldCiew(options) {
            DataFieldCiew.__super__.constructor.call(this, options);
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

            $(options.typeSelector).on('change', this.processChange.bind(this));
        },

        /**
         * Updates form via ajax, renders dynamic fields
         *
         * @param {$.Event} e
         */
        processChange: function(e) {
            const $form = $(this.options.formSelector);
            let data = $form.serializeArray();
            const url = $form.attr('action');
            const fieldsSet = this.fieldsSets.type;

            data = _.filter(data, function(field) {
                return _.indexOf(fieldsSet, field.name) !== -1;
            });
            data.push({name: this.UPDATE_MARKER, value: 1});

            mediator.execute('submitPage', {url: url, type: $form.attr('method'), data: $.param(data)});
        }
    });

    return DataFieldCiew;
});
