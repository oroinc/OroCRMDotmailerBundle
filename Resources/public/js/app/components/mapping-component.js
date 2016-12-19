define(function(require) {
    'use strict';

    var MappingComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var SegmentComponent = require('orosegment/js/app/components/segment-component');
    var FieldsCollection = require('orosegment/js/app/models/fields-collection');
    var __ = require('orotranslation/js/translator');
    var MappingModel = require('orocrmdotmailer/js/items-manager/mapping-model');
    var DeleteConfirmation = require('oroui/js/delete-confirmation');

    MappingComponent = SegmentComponent.extend({
        defaults: {
            entityChoice: '',
            valueSource: '',
            fieldsLoader: {
                loadingMaskParent: '',
                router: null,
                routingParams: {},
                fieldsData: [],
                confirmMessage: '',
                loadEvent: 'fieldsLoaded'
            },
            mapping: {
                editor: {},
                form: '',
                itemContainer: '',
                itemTemplate: ''
            },
            channel: {
                channelChoice: '',
                changeChannelConfirmMessage: ''
            },
            select2FieldChoiceTemplate: '',
            select2FieldChoicePlaceholoder: '',
            entities: [],
            initEntityChangeEvents: true
        },

        initialize: function(options) {
            this.processOptions(options);
            this.$storage = $(this.options.valueSource);

            this.initEntityFieldsUtil();
            this.$fieldsLoader = this.initFieldsLoader();
            this.initMapping();
            if (this.options.initEntityChangeEvents) {
                this.initEntityChangeEvents();
            }
            this.initIntegrationChangeEvents();

            this.form = this.$storage.parents('form');
            this.form.submit(_.bind(this.onBeforeSubmit, this));
        },

        initIntegrationChangeEvents: function() {
            var confirm = new DeleteConfirmation({
                title: __('Change Integration Confirmation'),
                okText: __('Yes'),
                content: __(this.options.channel.changeChannelConfirmMessage)
            });

            var self = this;
            var $channelChoice = $(this.options.channel.channelChoice);
            $channelChoice.data('previous', $channelChoice.val());
            $channelChoice.on('change', function() {
                var data = self.load() || [];
                var requiresConfirm = _.some(data, function(value) {
                    return !_.isEmpty(value);
                });

                var ok = function() {
                    var data = {};
                    self.trigger('resetData', data);
                    self.save(data);
                    $channelChoice.data('previous', $channelChoice.val());
                };

                var cancel = function() {
                    var oldVal = $channelChoice.data('previous');
                    $channelChoice.val(oldVal).change();
                };

                if (requiresConfirm) {
                    confirm.on('ok', ok);
                    confirm.on('cancel', cancel);
                    confirm.once('hidden', function() {
                        confirm.off('ok');
                        confirm.off('cancel');
                    });
                    confirm.open();
                } else {
                    ok();
                }
            });

            this.once('dispose:before', function() {
                confirm.dispose();
            });
        },

        /**
         * Combines options
         *
         * @param {Object} options
         */
        processOptions: function(options) {
            this.options = {};
            $.extend(true, this.options, this.defaults, options);
        },

        /**
         * Add row with entity field selector
         *
         * @param {Object} container
         * @param {boolean} withRemove
         */
        addEntityFieldRow: function(container, withRemove) {
            if (_.isUndefined(withRemove)) {
                withRemove = true;
            }
            var template = _.template($('#field-row-template').text());
            var $fieldsContainer = container.find('.fields-container');
            $fieldsContainer.append(template({withRemove: withRemove}));
            var $input = this._getEntityFields($fieldsContainer).last();
            $input.fieldChoice({
                fieldsLoaderSelector: this.$fieldsLoader,
                select2: {placeholder: this.options.select2FieldChoicePlaceholoder}
            });
            this.trigger('entity-field-count-changed');
        },

        /**
         * Remove row with entity field selector
         *
         * @param {Object} row
         */
        removeEntityFieldRow: function(row) {
            this._getEntityFields(row).fieldChoice('destroy');
            row.remove();
            this.trigger('entity-field-count-changed');
        },

        /**
         * Handle add/remove field clicks
         *
         * @param {Object} container
         */
        initFieldRowActions: function(container) {
            var self = this;
            container.on('click', '.add-field', function() {
                self.addEntityFieldRow(container);
            });
            container.on('click', '.remove-field', function() {
                self.removeEntityFieldRow($(this).parent());
            });

            //adding first field row
            this.addEntityFieldRow(container, false);
        },

        /**
         * Return entity fields selector elements
         *
         * @param {Object} container
         * @returns {*}
         * @private
         */
        _getEntityFields: function(container) {
            return container.find('[data-purpose=entityfield-selector]');
        },

        /**
         * Handle two way sync checkbox behaviour
         *
         * @param {Object} container
         */
        initTwoWaySyncHandlers: function(container) {
            var self = this;
            var $twoWaySyncCheckbox = container.find('[data-purpose=two-way-sync-selector]');
            var disableCheckbox = function(disabled) {
                if (disabled) {
                    $twoWaySyncCheckbox.prop('checked', false);
                }
                $twoWaySyncCheckbox.prop('disabled', disabled);
            };
            //disable checkbox if we have more than 1 entity field selected
            this.on('entity-field-count-changed', function() {
                var disabled = self._getEntityFields(container).length > 1;
                disableCheckbox(disabled);
            });
            //disable checkbox if a relation field was selected (more than 2 pathes in the chain)
            this._getEntityFields(container).on('change', function() {
                var path = self.entityFieldsUtil.pathToEntityChain($(this).val());
                var disabled = path.length > 2;
                disableCheckbox(disabled);
            });
            //uncheck checkbox on reset (when cancel button is clicked)
            container.on('after-reset', function() {
                $twoWaySyncCheckbox.prop('checked', false);
            });
        },

        /**
         * Initializes Mappings
         */
        initMapping: function() {
            var self = this;
            var options = this.options.mapping;
            var $table = $(options.itemContainer);
            var $editor = $(options.form);

            if (_.isEmpty($table) || _.isEmpty($editor)) {
                // there's no mapping
                return;
            }

            this.initFieldRowActions($editor);
            this.initTwoWaySyncHandlers($editor);

            // prepare collection for Items Manager
            var collection = new FieldsCollection(this.load('mapping'), {
                model: MappingModel,
                entityFieldsUtil: this.entityFieldsUtil
            });
            this.listenTo(collection, 'add remove change', function() {
                this.save(collection.toJSON(), 'mapping');
            });

            // setup confirmation dialog for delete item
            var confirm = new DeleteConfirmation({content: ''});
            confirm.on('ok', function() {
                collection.remove(this.model);
            });
            confirm.on('hidden', function() {
                delete this.model;
            });

            var $multipleEntityField = $editor.find('[data-purpose=multiple-entityfield-selector]');
            $editor.on('before-save', function() {
                var fieldsData = [];
                self._getEntityFields($editor).each(function() {
                    fieldsData.push($(this).val());
                });
                fieldsData = fieldsData.join(',');
                $multipleEntityField.val(fieldsData);
            });

            $multipleEntityField.on('change', function() {
                var value = $(this).val();
                value = value.split(',');
                //create necessary entity fields dropdowns
                var toAdd = value.length - self._getEntityFields($editor).length;
                _(toAdd).times(function() {
                    self.addEntityFieldRow($editor);
                });
                //Update values for entity field dropdowns. Remove the rest if necessary
                self._getEntityFields($editor).each(function(index, fieldChoice) {
                    if (index < value.length) {
                        $(fieldChoice).fieldChoice('instance').setValue(value[index]);
                    } else {
                        self.removeEntityFieldRow($(fieldChoice).parent());
                    }
                });
            });

            $editor.itemsManagerEditor($.extend(options.editor, {
                collection: collection,
                setter: function($el, name, value) {
                    if (name === 'dataField') {
                        value = value.value;
                    }
                    if (name === 'isTwoWaySync') {
                        if (value) {
                            $el.prop('checked', true);
                        }
                    }

                    return value;
                },
                getter: function($el, name, value) {
                    if (name === 'dataField') {
                        //keeping selected field name to show on the grid
                        value = $el.select2('data') && {
                            name: $el.select2('data').name,
                            value: value
                        };
                    }
                    if (name === 'isTwoWaySync') {
                        value = $el.is(':checked') ? 1 : 0;
                    }

                    return value;
                }
            }));

            this.on('validate-data', function(issues) {
                if ($editor.itemsManagerEditor('hasChanges')) {
                    issues.push({
                        component: __('orocrm.dotmailer.datafieldmapping.editor'),
                        type: MappingComponent.UNSAVED_CHANGES_ISSUE
                    });
                }
                if (!collection.isValid()) {
                    issues.push({
                        component: __('orocrm.dotmailer.datafieldmapping.editor'),
                        type: MappingComponent.INVALID_DATA_ISSUE
                    });
                }
            });

            this.on('before-submit', function() {
                collection.removeInvalidModels();
                $editor.itemsManagerEditor('reset');
            });

            var template = _.template($(this.options.select2FieldChoiceTemplate).text());
            $table.itemsManagerTable({
                collection: collection,
                itemTemplate: $(options.itemTemplate).html(),
                itemRender: function(tmpl, data) {
                    try {
                        var fieldsRendered = [];
                        var fields = data.entityFields.split(',');
                        _.each(fields, function(field) {
                            fieldsRendered.push(self.formatChoice(field, template));
                        });
                        data.entityFields = fieldsRendered.join(' + ');
                    } catch (e) {
                        data.entityFields = __('oro.querydesigner.field_not_found');
                        data.deleted = true;
                    }
                    data.dataField = data.dataField.name;
                    if (data.isTwoWaySync) {
                        data.isTwoWaySync = __('Yes');
                    } else {
                        data.isTwoWaySync = __('No');
                    }

                    return tmpl(data);
                },
                deleteHandler: function(model, data) {
                    confirm.setContent(data.message);
                    confirm.model = model;
                    confirm.open();
                }
            });

            this.on('resetData', function(data) {
                data.mappings = [];
                $table.itemsManagerTable('reset');
                $editor.itemsManagerEditor('reset');
            });

            this.once('dispose:before', function() {
                confirm.dispose();
                collection.dispose();
                $editor.itemsManagerEditor('destroy');
                $table.itemsManagerTable('destroy');
            }, this);
        }
    }, {
        INVALID_DATA_ISSUE: 'INVALID_DATA',
        UNSAVED_CHANGES_ISSUE: 'UNSAVED_CHANGES'
    });

    return MappingComponent;
});
