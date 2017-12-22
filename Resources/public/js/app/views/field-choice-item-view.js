define(function(require) {
    'use strict';

    var FieldChoiceItem;
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var FieldChoiceView = require('oroentity/js/app/views/field-choice-view');

    FieldChoiceItem = BaseView.extend({
        template: require('text!orodotmailer/templates/field-choice-item.html'),
        events: {
            'click [data-role="remove-item"]': 'onRemove'
        },
        removable: true,
        fieldChoiceOptions: null,
        initialize: function(options) {
            _.extend(this, _.pick(options, 'fieldChoiceOptions'));
            FieldChoiceItem.__super__.getTemplateData.call(this, options);
        },
        render: function() {
            FieldChoiceItem.__super__.render.call(this);
            var $input = this.$('input');
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

    return FieldChoiceItem;
});
