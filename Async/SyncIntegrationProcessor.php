<?php

namespace Oro\Bundle\DotmailerBundle\Async;

use Oro\Bundle\DotmailerBundle\ImportExport\Reader\AbstractExportReader;
use Oro\Bundle\IntegrationBundle\Async\SyncIntegrationProcessor as BaseSyncIntegrationProcessor;

class SyncIntegrationProcessor extends BaseSyncIntegrationProcessor
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::SYNC_INTEGRATION];
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareJobName(array $body)
    {
        $jobName = 'oro_integration:sync_integration:' . $body['integration_id'];
        if (!empty($body['connector_parameters'][AbstractExportReader::ADDRESS_BOOK_RESTRICTION_OPTION])) {
            $jobName .= sprintf(
                ':address-book:%s',
                $body['connector_parameters'][AbstractExportReader::ADDRESS_BOOK_RESTRICTION_OPTION]
            );
        }

        return $jobName;
    }
}
