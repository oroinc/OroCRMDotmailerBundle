<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Connector;

use Guzzle\Iterator\AppendIterator;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\AllowedConnectorInterface;
use OroCRM\Bundle\DotmailerBundle\Exception\RuntimeException;
use OroCRM\Bundle\DotmailerBundle\ImportExport\Reader\AbstractExportReader;
use OroCRM\Bundle\DotmailerBundle\Model\ExportManager;
use OroCRM\Bundle\DotmailerBundle\Provider\MarketingListItemsQueryBuilderProvider;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\MarketingListItemIterator;

class ExportContactConnector extends AbstractDotmailerConnector implements AllowedConnectorInterface
{
    const TYPE = 'contact_export';
    const EXPORT_JOB = 'dotmailer_contact_export';

    /**
     * @var MarketingListItemsQueryBuilderProvider
     */
    protected $marketingListItemsQueryBuilderProvider;

    /**
     * @var ExportManager
     */
    protected $exportManager;

    /**
     * {@inheritdoc}
     */
    protected function getConnectorSource()
    {
        $this->logger->info('Preparing Contacts for Export');

        $iterator = new AppendIterator();
        $addressBookId = $this->getContext()->getOption(AbstractExportReader::ADDRESS_BOOK_RESTRICTION_OPTION);

        if ($addressBookId) {
            $addressBook = $this->managerRegistry
                ->getRepository('OroCRMDotmailerBundle:AddressBook')
                ->find($addressBookId);
            if (!$addressBook) {
                throw new RuntimeException("Address book '{$addressBookId}' not found");
            }

            $addressBooks = [$addressBook];
        } else {
            $addressBooks = $this->managerRegistry->getRepository('OroCRMDotmailerBundle:AddressBook')
                ->getAddressBooksToSync($this->getChannel());
        }

        foreach ($addressBooks as $addressBook) {
            $marketingListItemIterator = new MarketingListItemIterator(
                $addressBook,
                $this->marketingListItemsQueryBuilderProvider,
                $this->getContext()
            );
            $iterator->append($marketingListItemIterator);
        }

        return $iterator;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'orocrm.dotmailer.connector.contact.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getImportJobName()
    {
        return self::EXPORT_JOB;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return self::TYPE;
    }

    /**
     * {@inheritdoc}
     */
    public function isAllowed(Channel $integration, array $processedConnectorsStatuses)
    {
        return $this->exportManager->isExportFinished($integration);
    }

    /**
     * @param MarketingListItemsQueryBuilderProvider $marketingListItemsQueryBuilderProvider
     *
     * @return ExportContactConnector
     */
    public function setMarketingListItemsQueryBuilderProvider(
        MarketingListItemsQueryBuilderProvider $marketingListItemsQueryBuilderProvider = null
    ) {
        $this->marketingListItemsQueryBuilderProvider = $marketingListItemsQueryBuilderProvider;

        return $this;
    }

    /**
     * @param ExportManager $exportManager
     *
     * @return ExportContactConnector
     */
    public function setExportManager(ExportManager $exportManager)
    {
        $this->exportManager = $exportManager;

        return $this;
    }
}
