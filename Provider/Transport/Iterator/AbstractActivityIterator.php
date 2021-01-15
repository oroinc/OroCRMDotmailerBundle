<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use Doctrine\Persistence\ManagerRegistry;
use DotMailer\Api\DataTypes\JsonArray;
use DotMailer\Api\Resources\IResources;
use Oro\Bundle\DotmailerBundle\Provider\Transport\AdditionalResource;

abstract class AbstractActivityIterator extends AbstractIterator
{
    const CAMPAIGN_KEY = 'related_campaign';
    const EMAIL_CAMPAIGN_KEY = 'related_email_campaign';
    const MARKETING_CAMPAIGN_KEY = 'related_marketing_campaign';
    const MARKETING_ACTIVITY_TYPE_KEY = 'marketing_ac_type';
    const ENTITY_CLASS_KEY = 'entity_class';
    const ENTITY_ID_KEY = 'entity_id';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var IResources
     */
    protected $dotmailerResources;

    /**
     * @var AdditionalResource
     */
    protected $additionalResource;

    /**
     * {@inheritdoc}
     */
    protected $batchSize = 1000;

    /**
     * @var int
     */
    protected $campaignOriginId;

    /**
     * @var int
     */
    protected $emailCampaignId;

    /**
     * @var int
     */
    protected $campaignId;

    /**
     * @var array
     */
    protected $addressBooks;

    /**
     * @var bool
     */
    protected $isInit;

    /**
     * @var \DateTime
     */
    protected $lastSyncDate;

    /**
     * Flag to control if related entity data(id and class) should be added to the items
     * @var bool
     */
    protected $includeEntityData = true;

    /**
     * @param IResources $dotmailerResources
     * @param ManagerRegistry $registry
     * @param int $campaignOriginId
     * @param int $emailCampaignId
     * @param int $campaignId
     * @param array $addressBooks
     * @param bool $isInit
     * @param \DateTime $lastSyncDate
     * @param AdditionalResource $additionalResource
     */
    public function __construct(
        IResources $dotmailerResources,
        ManagerRegistry $registry,
        $campaignOriginId,
        $emailCampaignId,
        $campaignId,
        $addressBooks,
        $isInit = false,
        \DateTime $lastSyncDate = null,
        AdditionalResource $additionalResource = null
    ) {
        $this->dotmailerResources = $dotmailerResources;
        $this->registry = $registry;
        $this->campaignOriginId = $campaignOriginId;
        $this->emailCampaignId = $emailCampaignId;
        $this->campaignId = $campaignId;
        $this->addressBooks = $addressBooks;
        $this->isInit = $isInit;
        $this->lastSyncDate = $lastSyncDate;
        $this->additionalResource = $additionalResource;
    }

    /**
     * @param int $take Count of requested records
     * @param int $skip Count of skipped records
     *
     * @return array
     */
    protected function getItems($take, $skip)
    {
        if (!$this->isInit || is_null($this->lastSyncDate)) {
            $items = $this->getAllActivities($take, $skip);
        } else {
            $items = $this->getActivitiesSinceDate($take, $skip);
        }

        $items = $items->toArray();
        foreach ($items as &$item) {
            $item[self::CAMPAIGN_KEY] = $this->campaignOriginId;
            $item[self::MARKETING_ACTIVITY_TYPE_KEY] = $this->getMarketingActivityType();
            $item[self::EMAIL_CAMPAIGN_KEY] = $this->emailCampaignId;
            $item[self::MARKETING_CAMPAIGN_KEY] = $this->campaignId;
        }

        if ($this->includeEntityData && $items) {
            $items = $this->getItemsWithEntitiesData($items);
        }

        return $items;
    }

    /**
     * Add information about target entities to the items array
     *
     * @param array $items
     * @return array
     */
    protected function getItemsWithEntitiesData($items)
    {
        /**
         * Group activities data by contact origin id.
         * In some cases it's not possible to know which exact entity was used in the campaign
         * since we only have dotmailer contact id in the activity response.
         * So we need to assign activity to Oro entities from all campaign's address books,
         * related to the same contact id.
         */
        $itemsByContactId = [];
        foreach ($items as $item) {
            if (!isset($itemsByContactId[$item['contactid']])) {
                $itemsByContactId[$item['contactid']] = [];
            }
            $itemsByContactId[$item['contactid']][] = $item;
        }
        $contactOriginIds = array_keys($itemsByContactId);
        $entitiesData = $this->registry->getRepository('OroDotmailerBundle:Contact')
            ->getEntitiesDataByOriginIds($contactOriginIds, $this->addressBooks);
        $allItems = [];
        foreach ($entitiesData as $entityData) {
            $contactId = $entityData['originId'];
            foreach ($itemsByContactId[$contactId] as $item) {
                $item[self::ENTITY_ID_KEY] = $entityData['entityId'];
                $item[self::ENTITY_CLASS_KEY] = $entityData['entityClass'];
                $allItems[] = $item;
            }
        }
        /**
         * If we found less items than in the initial API call(for example, if a dotmailer contact is not related
         * to any Oro entity), fill the rest with empty arrays to make sure the iterator works as expected.
         */
        $diffCount = count($items) - count($allItems);
        if ($diffCount > 0) {
            for ($i = 0; $i < $diffCount; $i++) {
                $allItems[] = [];
            }
        }

        return $allItems;
    }

    /**
     * @param int $take Count of requested records
     * @param int $skip Count of skipped records
     *
     * @return JsonArray
     */
    abstract protected function getAllActivities($take, $skip);

    /**
     * @param int $take Count of requested records
     * @param int $skip Count of skipped records
     *
     * @return JsonArray
     */
    abstract protected function getActivitiesSinceDate($take, $skip);

    /**
     * Get type of marketing activity
     *
     * @return string
     */
    abstract protected function getMarketingActivityType();
}
