<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\DataConverter;

class ActivityContactDataConverter extends AbstractDataConverter
{
    /**
     * {@inheritdoc}
     */
    protected function getHeaderConversionRules()
    {
        return [
            'email' => 'email',
            'numopens' => 'numOpens',
            'numpageviews' => 'numPageViews',
            'numclicks' => 'numClicks',
            'numforwards' => 'numForwards',
            'numestimatedforwards' => 'numEstimatedForwards',
            'numreplies' => 'numReplies',
            'datesent' => 'dateSent',
            'datefirstopened' => 'dateFirstOpened',
            'datelastopened' => 'dateLastOpened',
            'firstopenip' => 'firstOpenIp',
            'unsubscribed' => 'unsubscribed',
            'softbounced' => 'softBounced',
            'hardbounced' => 'hardBounced',
            'contactid' => 'contact:originId',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getBackendHeader()
    {
        return
            [
                'email',
                'numopens',
                'numpageviews',
                'numclicks',
                'numforwards',
                'numestimatedforwards',
                'numreplies',
                'datesent',
                'datefirstopened',
                'datelastopened',
                'firstopenip',
                'unsubscribed',
                'softbounced',
                'hardbounced',
                'contactid',
            ];
    }
}
