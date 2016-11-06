define(function(require) {
    'use strict';

    var MappingModel;
    var _ = require('underscore');
    var EntityFieldModel = require('oroquerydesigner/js/items-manager/entity-field-model');

    MappingModel = EntityFieldModel.extend({
        fieldAttribute: 'entityField',

        defaults: {
            entityField: null,
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
                var self = this;
                _.each(paths, function(path) {
                    self.entityFieldsUtil.pathToEntityChain(path);
                });
            } catch (e) {
                error = e.message;
            }
            return error;
        }
    });

    return MappingModel;
});
