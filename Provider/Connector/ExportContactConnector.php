<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Connector;

use Guzzle\Iterator\AppendIterator;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\ImportExport\Reader\AbstractExportReader;
use Oro\Bundle\DotmailerBundle\Model\ExportManager;
use Oro\Bundle\DotmailerBundle\Provider\MarketingListItemsQueryBuilderProvider;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\MarketingListItemIterator;
use Oro\Bundle\IntegrationBundle\Provider\AllowedConnectorInterface;
use Oro\Bundle\IntegrationBundle\Provider\ParametrizedAllowedConnectorInterface;

class ExportContactConnector extends AbstractDotmailerConnector implements
    AllowedConnectorInterface,
    ParametrizedAllowedConnectorInterface,
    ParallelizableInterface
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
        $addressBook = $this->getAddressBooksToSync();
        $marketingListItemIterator = new MarketingListItemIterator(
            $addressBook,
            $this->marketingListItemsQueryBuilderProvider,
            $this->getContext()
        );
        $iterator->append($marketingListItemIterator);

        return $iterator;
    }

    /**
     * @return AddressBook[]
     */
    protected function getAddressBooksToSync()
    {
        $addressBookId = $this->getContext()->getOption(AbstractExportReader::ADDRESS_BOOK_RESTRICTION_OPTION);

        return $this->getAddressBookById($this->getChannel(), $addressBookId);
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'oro.dotmailer.connector.contact_export.label';
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
     *
     * @deprecated
     * @see isAllowedParametrized
     */
    public function isAllowed(Channel $integration, array $processedConnectorsStatuses)
    {
        return true;
    }

    /**
     * @{@inheritdoc}
     */
    public function isAllowedParametrized(Channel $integration, array $processedConnectorsStatuses, array $parameters)
    {
        if (empty($parameters[AbstractExportReader::ADDRESS_BOOK_RESTRICTION_OPTION])) {
            return false;
        }

        $addressBook = $this->getAddressBookById(
            $integration,
            $parameters[AbstractExportReader::ADDRESS_BOOK_RESTRICTION_OPTION]
        );

        return $addressBook
            && $this->exportManager->isExportFinishedForAddressBook($integration, $addressBook)
            && $this->exportManager->isExportFaultsProcessedForAddressBook($integration, $addressBook);
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

    /**
     * @param Channel $integration
     * @param int $addressBookId
     *
     * @return AddressBook|bool
     */
    protected function getAddressBookById(Channel $integration, $addressBookId)
    {
        $addressBooks = $this->managerRegistry
            ->getRepository('OroDotmailerBundle:AddressBook')
            ->getConnectedAddressBooks(
                $integration,
                $addressBookId
            );

        return reset($addressBooks);
    }
}
