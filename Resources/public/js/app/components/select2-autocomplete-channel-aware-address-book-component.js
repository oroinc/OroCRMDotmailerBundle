define(function(require) {
    'use strict';

    var select2AutoCompleteChannelComponent;
    var _ = require('underscore');
    var Select2AutocompleteChannelAwareComponent = require('oro/select2-autocomplete-channel-aware-component');

    select2AutoCompleteChannelComponent = Select2AutocompleteChannelAwareComponent.extend({
        marketingListId: '',

        /**
         * @inheritDoc
         */
        constructor: function select2AutoCompleteChannelComponent() {
            select2AutoCompleteChannelComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.marketingListId = _.result(options, 'marketing_list_id') || this.marketingListId;
            select2AutoCompleteChannelComponent.__super__.initialize.call(this, options);
        },

        makeQuery: function(query) {
            var result = select2AutoCompleteChannelComponent.__super__.makeQuery.call(this, query);
            return result + ';' + this.marketingListId;
        }
    });
    return select2AutoCompleteChannelComponent;
});
