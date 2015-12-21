<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport;

use Doctrine\Common\Collections\ArrayCollection;

use DotMailer\Api\DataTypes\ApiCampaign;
use DotMailer\Api\DataTypes\ApiCampaignSend;
use DotMailer\Api\DataTypes\ApiContactImport;
use DotMailer\Api\DataTypes\ApiFileMedia;
use DotMailer\Api\DataTypes\ApiResubscribeResult;
use DotMailer\Api\DataTypes\ApiTransactionalDataImport;
use DotMailer\Api\DataTypes\ApiTransactionalDataImportReport;
use DotMailer\Api\DataTypes\ApiTransactionalDataList;
use DotMailer\Api\DataTypes\Int32List;
use DotMailer\Api\Resources\IResources;
use DotMailer\Api\DataTypes\ApiContactResubscription;

use Guzzle\Iterator\AppendIterator;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;

use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\Entity\Contact;
use OroCRM\Bundle\DotmailerBundle\Exception\RequiredOptionException;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\ActivityContactIterator;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\AddressBookIterator;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\CampaignIterator;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\CampaignSummaryIterator;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\ExportFaultsReportIterator;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\UnsubscribedContactIterator;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\UnsubscribedFromAccountContactIterator;
use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator\ContactIterator;

class DotmailerTransport implements TransportInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    const DEFAULT_START_SYNC_DATE = '1971';

    /**
     * @var IResources
     */
    protected $dotmailerResources;

    /**
     * @var DotmailerResourcesFactory
     */
    protected $dotMailerResFactory;

    /**
     * @var Mcrypt
     */
    protected $encryptor;

    /**
     * @param DotmailerResourcesFactory $dotmailerResourcesFactory
     * @param Mcrypt                    $encryptor
     */
    public function __construct(
        DotmailerResourcesFactory $dotmailerResourcesFactory,
        Mcrypt $encryptor
    ) {
        $this->dotMailerResFactory = $dotmailerResourcesFactory;
        $this->encryptor = $encryptor;
    }

    /**
     * {@inheritdoc}
     */
    public function init(Transport $transportEntity)
    {
        $settings = $transportEntity->getSettingsBag();
        $username = $settings->get('username');
        if (!$username) {
            throw new RequiredOptionException('username');
        }

        $password = $settings->get('password');
        $password = $this->encryptor->decryptData($password);

        if (!$password) {
            throw new RequiredOptionException('password');
        }

        $this->dotmailerResources = $this->dotMailerResFactory->createResources($username, $password, $this->logger);
    }

    /**
     * @param AddressBook[]  $addressBooks
     *
     * @return ContactIterator
     */
    public function getAddressBookContacts($addressBooks)
    {
        $iterator = new AppendIterator();
        foreach ($addressBooks as $addressBook) {
            $iterator->append(
                new ContactIterator(
                    $this->dotmailerResources,
                    $addressBook->getOriginId(),
                    $addressBook->getLastImportedAt(),
                    true,
                    1000,
                    0
                )
            );
        }

        return $iterator;
    }

    /**
     * @param \DateTime|null $dateSince
     *
     * @return ContactIterator
     */
    public function getContacts($dateSince = null)
    {
        return new ContactIterator($this->dotmailerResources, null, $dateSince);
    }

    /**
     * @return \Iterator
     */
    public function getAddressBooks()
    {
        return new AddressBookIterator($this->dotmailerResources);
    }

    /**
     * @param AddressBook[] $addressBooks
     *
     * @return UnsubscribedContactIterator
     */
    public function getUnsubscribedContacts(array $addressBooks)
    {
        $defaultLastSyncDate = date_create_from_format('Y', self::DEFAULT_START_SYNC_DATE, new \DateTimeZone('UTC'));

        $iterator = new AppendIterator();
        foreach ($addressBooks as $addressBook) {
            $lastSyncDate = $addressBook->getLastImportedAt() ?: $defaultLastSyncDate;
            $iterator->append(
                new UnsubscribedContactIterator($this->dotmailerResources, $addressBook->getOriginId(), $lastSyncDate)
            );
        }

        return $iterator;
    }

    /**
     * @param \DateTime $lastSyncDate
     *
     * @return UnsubscribedFromAccountContactIterator
     */
    public function getUnsubscribedFromAccountsContacts(\DateTime $lastSyncDate = null)
    {
        if (!$lastSyncDate) {
            return new \EmptyIterator();
        }

        return new UnsubscribedFromAccountContactIterator($this->dotmailerResources, $lastSyncDate);
    }

    /**
     * @param array $addressBooks
     *
     * @return \Iterator
     */
    public function getCampaigns(array $addressBooks = [])
    {
        $iterator = new AppendIterator();
        foreach ($addressBooks as $addressBook) {
            $iterator->append(
                new CampaignIterator($this->dotmailerResources, $addressBook['originId'])
            );
        }

        return $iterator;
    }

    /**
     * @param int $campaignId
     *
     * @return ApiCampaign
     */
    public function getCampaignById($campaignId)
    {
        return $this->dotmailerResources->GetCampaignById($campaignId);
    }

    /**
     * @param array|ArrayCollection $campaignsToSynchronize
     * @param \DateTime             $lastSyncDate = null
     *
     * @return \Iterator
     */
    public function getActivityContacts(array $campaignsToSynchronize = [], \DateTime $lastSyncDate = null)
    {

        $iterator = new AppendIterator();
        foreach ($campaignsToSynchronize as $campaign) {
            $iterator->append(
                new ActivityContactIterator(
                    $this->dotmailerResources,
                    $campaign['originId'],
                    $campaign['isInit'],
                    $lastSyncDate
                )
            );
        }

        return $iterator;
    }

    /**
     * @param array $campaignsToSynchronize
     *
     * @return \Iterator
     */
    public function getCampaignSummary(array $campaignsToSynchronize = [])
    {
        return new CampaignSummaryIterator($this->dotmailerResources, $campaignsToSynchronize);
    }

    /**
     * @param Contact     $contact
     * @param AddressBook $addressBook
     *
     * @return ApiResubscribeResult
     */
    public function resubscribeAddressBookContact(Contact $contact, AddressBook $addressBook)
    {
        $apiContactResubscription = new ApiContactResubscription(
            [
                'UnsubscribedContact' => [
                    'Email' => $contact->getEmail(),
                ],
                'PreferredLocale' => '',
                'ReturnUrlToUseIfChallenged' => '',
            ]
        );

        return $this->dotmailerResources->PostAddressBookContactsResubscribe(
            $addressBook->getOriginId(),
            $apiContactResubscription
        );
    }

    /**
     * @param Contact $contact
     *
     * @return ApiResubscribeResult
     */
    public function resubscribeContact(Contact $contact)
    {
        return $this->dotmailerResources->PostContactsResubscribe(
            new ApiContactResubscription(
                [
                    'UnsubscribedContact' => [
                        'Email' => $contact->getEmail(),
                    ],
                    'PreferredLocale' => '',
                    'ReturnUrlToUseIfChallenged' => '',
                ]
            )
        );
    }

    /**
     * @param int[] $removingItemsOriginIds
     * @param int   $addressBookOriginId
     */
    public function removeContactsFromAddressBook(array $removingItemsOriginIds, $addressBookOriginId)
    {
        $contactIdsList = new Int32List($removingItemsOriginIds);
        $this->dotmailerResources->PostAddressBookContactsDelete($addressBookOriginId, $contactIdsList);
    }

    /**
     * @param string $contactsCsv
     * @param int    $addressBookOriginId
     *
     * @return ApiContactImport
     */
    public function exportAddressBookContacts($contactsCsv, $addressBookOriginId)
    {
        $apiFileMedia = new ApiFileMedia(['FileName' => 'contacts.csv', 'Data' => $contactsCsv]);
        return $this->dotmailerResources->PostAddressBookContactsImport($addressBookOriginId, $apiFileMedia);
    }

    /**
     * @param string[] $importIds Array of GUID
     * @param int $addressBookId Address Book Id
     *
     * @return \Iterator
     */
    public function getAddressBookExportReports($importIds, $addressBookId)
    {
        $appendIterator = new AppendIterator();
        foreach ($importIds as $importId) {
            $iterator = new ExportFaultsReportIterator($this->dotmailerResources, $addressBookId, $importId);
            $appendIterator->append($iterator);
        }

        return $appendIterator;
    }

    /**
     * @param string                   $collectionName
     * @param ApiTransactionalDataList $list
     *
     * @return ApiTransactionalDataImport
     */
    public function updateContactsTransactionalData($collectionName, ApiTransactionalDataList $list)
    {
        return $this->dotmailerResources
            ->PostContactsTransactionalDataImport($collectionName, $list);
    }

    /**
     * @param $importId
     *
     * @return ApiTransactionalDataImportReport
     */
    public function getContactDataImportReport($importId)
    {
        return $this->dotmailerResources->GetContactsTransactionalDataImportReport($importId);
    }

    /**
     * Send campaign to contacts specified by contactIds or addressBookIds or all contacts
     * if no parameters (except campaignId) defined
     *
     * @param string $campaignId
     * @param array  $contactIds
     * @param array  $addressBookIds
     *
     * @return ApiCampaignSend
     */
    public function sendCampaign($campaignId, $contactIds = [], array $addressBookIds = [])
    {
        $apiCampaignSend = new ApiCampaignSend(
            [
                'CampaignId'     => $campaignId,
                'ContactIds'     => $contactIds,
                'AddressBookIds' => $addressBookIds,
            ]
        );

        return $this->dotmailerResources->PostCampaignsSend($apiCampaignSend);
    }

    /**
     * @return IResources
     */
    public function getDotmailerResource()
    {
        return $this->dotmailerResources;
    }

    /**
     * @param string $importId
     *
     * @return ApiContactImport
     */
    public function getImportStatus($importId)
    {
        return $this->dotmailerResources->GetContactsImportByImportId($importId);
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'orocrm.dotmailer.integration_transport.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsFormType()
    {
        return 'orocrm_dotmailer_transport_setting_type';
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsEntityFQCN()
    {
        return 'OroCRM\\Bundle\\DotmailerBundle\\Entity\\DotmailerTransport';
    }
}
