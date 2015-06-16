define(function (require) {
    'use strict';
    var Component,
        $ = require('jquery'),
        _ = require('underscore'),
        __ = require('orotranslation/js/translator'),
        Select2AutocompleteChannelAwareComponent = require('orocrmchannel/js/app/components/select2-autocompletechannel-aware-component');
    Component = Select2AutocompleteChannelAwareComponent.extend({
        processExtraConfig: function (select2Config, params) {
            Component.__super__.processExtraConfig(select2Config, params);
            var parentDataFunction = select2Config.ajax.data;
            select2Config.ajax.data = function () {
                var result = parentDataFunction.apply(this, arguments);
                result.query += ';' + params.marketingListId;
                return result;
            }
            return select2Config;
        }
    });
    return Component;
});
