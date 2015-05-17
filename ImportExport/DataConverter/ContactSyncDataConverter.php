<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\DataConverter;

use OroCRM\Bundle\DotmailerBundle\Provider\MarketingListItemsQueryBuilderProvider;

class ContactSyncDataConverter extends AbstractDataConverter
{
    /**
     * {@inheritdoc}
     */
    protected function getHeaderConversionRules()
    {
        return [
            MarketingListItemsQueryBuilderProvider::CONTACT_EMAIL_FIELD     => 'email',
            MarketingListItemsQueryBuilderProvider::CONTACT_FIRS_NAME_FIELD => 'firstName',
            MarketingListItemsQueryBuilderProvider::CONTACT_LAST_NAME_FIELD => 'lastName',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getBackendHeader()
    {
        return [];
    }
}
