define(function(require) {
    'use strict';

    const EntityStructureDataProvider = require('oroentity/js/app/services/entity-structure-data-provider');

    EntityStructureDataProvider.defineFilterPreset('dotmailer', {
        optionsFilter: {exclude: false},
        exclude: [
            {relationType: 'manyToMany'},
            {relationType: 'oneToMany'}
        ]
    });
});
