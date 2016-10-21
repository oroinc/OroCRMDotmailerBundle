<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Connector;

class DataFieldConnector extends AbstractDotmailerConnector
{
    const TYPE = 'datafield';
    const IMPORT_JOB = 'dotmailer_datafield_import';

    /**
     * {@inheritdoc}
     */
    protected function getConnectorSource()
    {
        $this->logger->info('Importing Data Fields.');

        return $this->transport->getDataFields();
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'oro.dotmailer.connector.data_field.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getImportJobName()
    {
        return self::IMPORT_JOB;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return self::TYPE;
    }
}
