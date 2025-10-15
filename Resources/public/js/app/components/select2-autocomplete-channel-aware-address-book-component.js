import _ from 'underscore';
import Select2AutocompleteChannelAwareComponent from 'oro/select2-autocomplete-channel-aware-component';

const Select2AutoCompleteChannelComponent = Select2AutocompleteChannelAwareComponent.extend({
    marketingListId: '',

    /**
     * @inheritdoc
     */
    constructor: function Select2AutoCompleteChannelComponent(options) {
        Select2AutoCompleteChannelComponent.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        this.marketingListId = _.result(options, 'marketing_list_id') || this.marketingListId;
        Select2AutoCompleteChannelComponent.__super__.initialize.call(this, options);
    },

    makeQuery: function(query) {
        const result = Select2AutoCompleteChannelComponent.__super__.makeQuery.call(this, query);
        return result + ';' + this.marketingListId;
    }
});
export default Select2AutoCompleteChannelComponent;
