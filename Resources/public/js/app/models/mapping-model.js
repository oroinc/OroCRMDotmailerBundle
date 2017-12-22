define(function(require) {
    'use strict';

    var MappingModel;
    var _ = require('underscore');
    var EntityFieldModel = require('oroquerydesigner/js/app/models/entity-field-model');

    MappingModel = EntityFieldModel.extend({
        fieldAttribute: 'entityFields',

        defaults: {
            id: null,
            entityFields: null,
            dataField: null,
            isTwoWaySync: null
        },

        /**
         * @inheritDoc
         */
        validate: function(attrs, options) {
            var error;
            try {
                var paths = attrs[this.fieldAttribute].split(',');
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
