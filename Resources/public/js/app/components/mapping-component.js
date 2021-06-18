define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const SegmentComponent = require('orosegment/js/app/components/segment-component');
    const EntityFieldsCollection = require('oroquerydesigner/js/app/models/entity-fields-collection');
    const EntityStructureDataProvider = require('oroentity/js/app/services/entity-structure-data-provider');
    const FieldChoiceItemView = require('orodotmailer/js/app/views/field-choice-item-view');
    const MappingModel = require('orodotmailer/js/app/models/mapping-model');
    const DeleteConfirmation = require('oroui/js/delete-confirmation');

    const MappingComponent = SegmentComponent.extend({
        defaults: {
            entityChoice: '',
            valueSource: '',
            dataProviderFilterPreset: 'dotmailer',
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

        fieldRowViews: null,

        /**
         * @inheritdoc
         */
        constructor: function MappingComponent(options) {
            MappingComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.processOptions(options);
            this._deferredInit();
            EntityStructureDataProvider.createDataProvider({}, this).then(function(provider) {
                this._init(provider);
                this._resolveDeferredInit();
            }.bind(this));
        },

        _init: function(provider) {
            this.dataProvider = provider;
            this.$storage = $(this.options.valueSource);
            this.fieldRowViews = [];
            this.initEntityChangeEvents();
            this.setupDataProvider();
            this.initMapping();
            this.initIntegrationChangeEvents();

            this.form = this.$storage.closest('form');
        },

        eventNamespace: function() {
            return '.' + this.cid;
        },

        initIntegrationChangeEvents: function() {
            const confirm = new DeleteConfirmation({
                title: __('Change Integration Confirmation'),
                okText: __('Yes'),
                content: __(this.options.channel.changeChannelConfirmMessage)
            });

            const self = this;
            const $channelChoice = $(this.options.channel.channelChoice);
            $channelChoice.data('previous', $channelChoice.val());
            $channelChoice.on('change', function() {
                const data = self.load() || [];
                const requiresConfirm = _.some(data, function(value) {
                    return !_.isEmpty(value);
                });

                const ok = function() {
                    const data = {};
                    self.trigger('resetData', data);
                    self.save(data);
                    $channelChoice.data('previous', $channelChoice.val());
                };

                const cancel = function() {
                    const oldVal = $channelChoice.data('previous');
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
         */
        addEntityFieldRow: function(value) {
            const itemView = new FieldChoiceItemView({
                autoRender: true,
                noWrap: true,
                fieldChoiceOptions: {
                    entity: this.entityClassName,
                    filterPreset: this.options.dataProviderFilterPreset,
                    select2: {
                        placeholder: this.options.select2FieldChoicePlaceholoder,
                        pageableResults: true,
                        dropdownAutoWidth: true
                    }
                }
            });

            this.$editorForm.find('[data-role="fields-container"]').append(itemView.el);
            this.fieldRowViews.push(itemView);
            this.listenTo(itemView, {
                change: function() {
                    this.updateSyncCheckbox();
                    this.updateOriginField();
                },
                remove: function(cid) {
                    const view = _.findWhere(this.fieldRowViews, {cid: cid});

                    if (view) {
                        this.removeFieldRowView(view);
                        this.updateOriginField();
                    }
                }
            });
            if (value) {
                itemView.setValue(value);
            }
            this.updateSyncCheckbox();
            return itemView;
        },

        removeFieldRowView: function(view) {
            this.stopListening(view);
            this.fieldRowViews = _.without(this.fieldRowViews, view);
            view.dispose();
            this.updateSyncCheckbox();
        },

        /**
         * Handle two way sync checkbox behaviour
         */
        initSyncCheckbox: function() {
            this.$syncCheckbox = this.$editorForm.find('[data-purpose=two-way-sync-selector]');
            // disable checkbox if we have more than 1 entity field selected
            this.$editorForm.on('after-reset' + this.eventNamespace(), function() {
                this.$syncCheckbox.prop('checked', false);
            }.bind(this));
        },

        updateSyncCheckbox: function() {
            let disabled = this.fieldRowViews.length > 1;

            if (!disabled) {
                disabled = Boolean(_.detect(this.fieldRowViews, function(view) {
                    const value = view.getValue();
                    if (value) {
                        const path = this.dataProvider.pathToEntityChainSafely(value);
                        if (path.length > 2) {
                            return true;
                        }
                    }
                }, this));
            }

            if (disabled) {
                this.$syncCheckbox.prop('checked', false);
            }
            this.$syncCheckbox.prop('disabled', disabled);
        },

        initFieldTable: function() {
            // setup confirmation dialog for delete item
            this.confirmView = new DeleteConfirmation({content: ''});
            this.confirmView.on('ok', function() {
                this.collection.remove(this.confirmView.model);
            }.bind(this));
            this.confirmView.on('hidden', function() {
                delete this.model;
            });
            const template = _.template($(this.options.select2FieldChoiceTemplate).text());
            this.$table.itemsManagerTable({
                collection: this.collection,
                itemTemplate: $(this.options.mapping.itemTemplate).html(),
                itemRender: function(tmpl, data) {
                    try {
                        const fields = data.entityFields.split(',');
                        const fieldsRendered = _.map(fields, function(field) {
                            return this.formatChoice(field, template);
                        }, this);
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
                }.bind(this),
                deleteHandler: function(model, data) {
                    this.confirmView.setContent(data.message);
                    this.confirmView.model = model;
                    this.confirmView.open();
                }.bind(this)
            });
        },

        initFieldCollection: function() {
            const collection = new EntityFieldsCollection(this.load('mapping'), {
                model: MappingModel,
                dataProvider: this.dataProvider
            });
            this.listenTo(collection, 'add remove change', function() {
                this.save(collection.toJSON(), 'mapping');
            });

            this.collection = collection;
        },

        initEditorForm: function() {
            this.$editorForm
                .on('click' + this.eventNamespace(), '.add-field', this.onAddFieldClick.bind(this))
                .on('before-save' + this.eventNamespace(), _.debounce(this.checkFieldRowExistOnError.bind(this)))
                .on('change' + this.eventNamespace(), MappingComponent.ORIGIN_FIELDS_SELECTOR,
                    this.onOriginFieldsChange.bind(this));

            this.$editorForm.itemsManagerEditor($.extend(this.options.mapping.editor, {
                collection: this.collection,
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
                        // keeping selected field name to show on the grid
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
        },

        /**
         * Initializes Mappings
         */
        initMapping: function() {
            this.$table = $(this.options.mapping.itemContainer);
            this.$editorForm = $(this.options.mapping.form);
            this.$entityFieldsInput = this.$editorForm.find(MappingComponent.ORIGIN_FIELDS_SELECTOR);

            if (this.$table.length === 0 || this.$editorForm.length === 0) {
                // there's no mapping
                return;
            }

            this.initSyncCheckbox();
            this.initFieldCollection();
            this.initFieldTable();
            this.initEditorForm();

            this.on('validate-data', function(issues) {
                if (this.$editorForm.itemsManagerEditor('hasChanges')) {
                    issues.push({
                        component: __('oro.dotmailer.datafieldmapping.editor'),
                        type: MappingComponent.UNSAVED_CHANGES_ISSUE
                    });
                }
                if (!this.collection.isValid()) {
                    issues.push({
                        component: __('oro.dotmailer.datafieldmapping.editor'),
                        type: MappingComponent.INVALID_DATA_ISSUE
                    });
                }
            }.bind(this));

            this.once('before-submit', function() {
                this.collection.removeInvalidModels();
                this.$editorForm.itemsManagerEditor('reset');
            }.bind(this));

            this.on('resetData', function(data) {
                data.mappings = [];
                this.$table.itemsManagerTable('reset');
                this.$editorForm.itemsManagerEditor('reset');
            }.bind(this));
        },

        onAddFieldClick: function() {
            this.addEntityFieldRow();
        },

        checkFieldRowExistOnError: function() {
            if (this.fieldRowViews.length === 0 && this.$entityFieldsInput.hasClass('error')) {
                this.addEntityFieldRow();
            }
        },

        updateOriginField: function() {
            let values = _.invoke(this.fieldRowViews, 'getValue');

            values = _.compact(values).join(',');

            if (this.$entityFieldsInput.val() !== values) {
                this.ignoreFieldChange = true;
                this.$entityFieldsInput.val(values).trigger('change');
                delete this.ignoreFieldChange;

                this.checkFieldRowExistOnError();
            }
        },

        onOriginFieldsChange: function(e) {
            if (this.ignoreFieldChange) {
                return;
            }

            const values = $(e.currentTarget).val().split(',');
            _.each(this.fieldRowViews, this.removeFieldRowView, this);
            _.each(values, this.addEntityFieldRow, this);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.$editorForm.off(this.eventNamespace());
            this.$editorForm.itemsManagerEditor('destroy');
            this.$table.itemsManagerTable('destroy');
            delete this.$editorForm;
            delete this.$table;
            MappingComponent.__super__.dispose.call(this);
        }
    }, {
        INVALID_DATA_ISSUE: 'INVALID_DATA',
        UNSAVED_CHANGES_ISSUE: 'UNSAVED_CHANGES',
        ORIGIN_FIELDS_SELECTOR: '[data-purpose=multiple-entityfield-selector]'
    });

    return MappingComponent;
});
