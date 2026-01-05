<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Connector;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

/**
 * Data Field Connector
 */
class DataFieldConnector extends AbstractDotmailerConnector
{
    public const TYPE = 'datafield';
    public const IMPORT_JOB = 'dotmailer_datafield_import';

    public const FORCE_SYNC_FLAG = 'datafields-force-sync';

    /** @var  ConfigManager */
    protected $configManager;

    public function setConfigManager(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * If no sync was running, keep previous sync date
     */
    #[\Override]
    protected function updateContextLastSyncDate(?\DateTime $date = null)
    {
        if ($this->getSourceIterator() instanceof \EmptyIterator) {
            $date = $this->getLastSyncDate();
        }

        parent::updateContextLastSyncDate($date);
    }

    #[\Override]
    protected function getConnectorSource()
    {
        $this->logger->info('Importing Data Fields.');
        $isForceSync = $this->getContext()->hasOption(self::FORCE_SYNC_FLAG);
        if (!$isForceSync && $this->configManager) {
            $syncInterval = $this->configManager->get('oro_dotmailer.datafields_sync_interval');
            if ($syncInterval) {
                //Skip data fields import to run them only by desired interval
                $interval = \DateInterval::createFromDateString($syncInterval);

                $dateToCheck = new \DateTime('now', new \DateTimeZone('UTC'));
                $dateToCheck->sub($interval);

                $lastSyncDate = $this->getLastSyncDate();

                if ($lastSyncDate && $lastSyncDate > $dateToCheck) {
                    $this->logger->info(
                        sprintf(
                            'Data Fields are up to date, interval is %s',
                            $syncInterval
                        )
                    );

                    return new \EmptyIterator();
                }
            }
        }

        return $this->transport->getDataFields();
    }

    #[\Override]
    public function getLabel(): string
    {
        return 'oro.dotmailer.connector.data_field.label';
    }

    #[\Override]
    public function getImportJobName()
    {
        return self::IMPORT_JOB;
    }

    #[\Override]
    public function getType()
    {
        return self::TYPE;
    }
}
