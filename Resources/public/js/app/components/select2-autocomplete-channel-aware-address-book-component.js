define(function(require) {
    'use strict';

    var Component;
    var _ = require('underscore');
    var Select2AutocompleteChannelAwareComponent = require('oro/select2-autocomplete-channel-aware-component');

    Component = Select2AutocompleteChannelAwareComponent.extend({
        marketingListId: '',
        initialize: function(options) {
            this.marketingListId = _.result(options, 'marketing_list_id') || this.marketingListId;
            Component.__super__.initialize.call(this, options);
        },
        makeQuery: function(query) {
            var result = Component.__super__.makeQuery.call(this, query);
            return result + ';' + this.marketingListId;
        }
    });
    return Component;
});
