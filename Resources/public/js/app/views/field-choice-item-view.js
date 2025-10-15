import _ from 'underscore';
import BaseView from 'oroui/js/app/views/base/view';
import FieldChoiceView from 'oroentity/js/app/views/field-choice-view';
import template from 'text-loader!orodotmailer/templates/field-choice-item.html';

const FieldChoiceItem = BaseView.extend({
    template,
    events: {
        'click [data-role="remove-item"]': 'onRemove'
    },
    removable: true,
    fieldChoiceOptions: null,
    /**
     * @inheritdoc
     */
    constructor: function FieldChoiceItem(options) {
        FieldChoiceItem.__super__.constructor.call(this, options);
    },
    /**
     * @inheritdoc
     */
    initialize: function(options) {
        _.extend(this, _.pick(options, 'fieldChoiceOptions'));
        FieldChoiceItem.__super__.getTemplateData.call(this, options);
    },
    render: function() {
        FieldChoiceItem.__super__.render.call(this);
        const $input = this.$('input');
        this.subview('field-choice', new FieldChoiceView(_.extend({
            autoRender: true,
            el: $input
        }, this.fieldChoiceOptions)));
        this.listenTo(this.subview('field-choice'), 'change', function(field) {
            this.trigger('change', field);
        });
    },

    onRemove: function(e) {
        e.preventDefault();
        this.trigger('remove', this.cid);
    },

    getValue: function() {
        return this.subview('field-choice').getValue();
    },

    setValue: function(value) {
        this.subview('field-choice').setValue(value);
    }
});

export default FieldChoiceItem;
