define(function(require) {
    'use strict';

    const _ = require('underscore');
    const EntityFieldModel = require('oroquerydesigner/js/app/models/entity-field-model');

    const MappingModel = EntityFieldModel.extend({
        fieldAttribute: 'entityFields',

        defaults: {
            id: null,
            entityFields: null,
            dataField: null,
            isTwoWaySync: null
        },

        /**
         * @inheritdoc
         */
        constructor: function MappingModel(attrs, options) {
            MappingModel.__super__.constructor.call(this, attrs, options);
        },

        /**
         * @inheritdoc
         */
        validate: function(attrs, options) {
            let error;
            try {
                const paths = attrs[this.fieldAttribute].split(',');
                _.each(paths, function(path) {
                    this.dataProvider.pathToEntityChain(path);
                }, this);
            } catch (e) {
                error = e.message;
            }
            return error;
        }
    });

    return MappingModel;
});
